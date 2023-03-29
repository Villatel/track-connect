<?php
namespace plugins\api;

class pluginApi{
    protected $endpoint;
    protected $client;
    protected $url;

    private $base_uri;
    private $public_key;
    private $private_key;

    public function __construct()
    {
        $this->base_uri = env('TRACK_BASE_URI');
        $this->public_key = env('TRACK_PUBLIC_KEY');
        $this->private_key = env('TRACK_PRIVATE_KEY');
    }

    public function request($params = [])
    {
        $curl = curl_init();
        $opts = [
            CURLOPT_URL => $this->base_uri.$params['endpoint'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Basic ".base64_encode($this->public_key.":".$this->private_key)
            ],
        ];
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        return ($error) ? "cURL Error #:$error" : json_decode($response);
    }

    public function getEndPoint()
    {
        return $this->endpoint;
    }

    public function getUnitCount($endpoint = false)
    {
        global $wpdb;

        $nextEndpoint = $endpoint ? str_replace($this->base_uri, '', $endpoint) : '/api/pms/units?size=100';
        $unitsRequest = $this->request(['endpoint' => $nextEndpoint]);

        $unitIds = [];
        $units = $unitsRequest->_embedded->units;
        foreach ($units as $unit) {
            $unitIds [] = $unit->id;
        }

        $posts = $wpdb->get_results(
            "SELECT posts.ID AS id FROM `".$wpdb->prefix."posts` AS posts
            WHERE post_type='listing' AND post_status ='publish'
            AND ID IN(SELECT post_id FROM `".$wpdb->prefix."postmeta` WHERE meta_key='_listing_unit_id' AND meta_value NOT IN (".implode(
                ',',
                $unitIds
            ).") ) "
        );


        static $resultUnitIds = [];
        $resultUnitIds = array_merge($resultUnitIds, $unitIds);

        if (isset($unitsRequest->_links->next)) {
            $this->getUnitCount($unitsRequest->_links->next->href);
        }

        return (object)['response' => count($resultUnitIds)];
    }


    public function removeActive()
    {
        global $wpdb;

        $term = $wpdb->get_row(
            "SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
        WHERE slug = 'active' 
        LIMIT 1;"
        );
        $wpdb->delete($wpdb->prefix.'term_relationships', ['term_taxonomy_id' => $term->term_taxonomy_id]);
    }

    public function getUnits($page = 1, $size = 25, $nodeTypeId = 0)
    {
        global $wpdb;

        if(!$page){
            $page = 1;
        }
        if(!$size){
            $size = 25;
        }
        $unitsCreated = 0;
        $unitsUpdated = 0;
        $unitsRemoved = 0;
        $lodgingTypes = [];
        if (get_option('track_connect_lodging_types')) {
            $lodgingTypes = (array)json_decode(get_option('track_connect_lodging_types'));
        }

        $amenities = $this->getAmenities();

        $units = $this->request(['endpoint' => '/api/pms/units?includeDescriptions=1&page='.$page.'&size='.$size]);

        if (isset($units) && $units->status !== 404) {
            $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
        WHERE slug = 'active' 
        LIMIT 1;");
            $wpdb->delete( $wpdb->prefix.'term_relationships', array('term_taxonomy_id' => 0));
            $wpdb->delete( $wpdb->prefix.'term_relationships', array('object_id' => 0));
            $activeTerm = $term->term_taxonomy_id;

            $unitsRemoved = 0;
            $count = 0;

            foreach ($units->_embedded->units as $unit) {
                //check unit status
                $isDraft = true;

                if (isset($unit->custom->pms_units_is_website)){
                    if ($unit->custom->pms_units_is_website) {
                        $isDraft = false;
                        if (!$unit->_embedded->node->custom->pms_nodes_website_activate) {
                            $isDraft = true;
                        }
                    }
                } else {
                    $isDraft = false;
                }

                $count++;
                if (!isset($unit->occupancy) || $unit->occupancy == 0) {
                    $occupancy =  isset($unit->rooms) && $unit->rooms >= 1 ? count($unit->rooms) : 2;
                } else {
                    $occupancy = $unit->occupancy;
                }

                //set unit prices
                $unitPricing = $this->get_aggregate_availability_pricing_data($unit->id);
                $unitPrices = [];
                foreach ($unitPricing as $unitPrice) {
                    $unitPrices['min'][] = $unitPrice['rate']['min'];
                    $unitPrices['max'][] = $unitPrice['rate']['max'];
                }
                $unitPrices['min'] = $unitPrices['min'] ? array_unique($unitPrices['min']) : [];
                $unitPrices['max'] = $unitPrices['max'] ? array_unique($unitPrices['max']) : [];
                sort($unitPrices['min']);
                rsort($unitPrices['max']);

                $unit->min_rate = array_shift($unitPrices['min']);
                $unit->max_rate = array_shift($unitPrices['max']);


                $unit->images = [];
                $unitImages = $this->request([
                    'endpoint' => "/api/pms/units/$unit->id/images?size=-1"
                ]);

                foreach ($unitImages->_embedded->images as $image) {
                    $unit->images [] = (object)[
                        'url' => $image->original,
                        'text' => $image->name,
                        'id' => $image->id,
                        'rank' => $image->order,
//                        'type' => stristr($image->type, '/', true)
                        'type' => false
                    ];
                }

                if (count($unit->images)) {
                    usort($unit->images, function ($a, $b) {
                        return $a->rank - $b->rank;
                    });
                }
                $today = date('Y-m-d H:i:s');

//                $unit->amenities = [];
//                foreach ($unit->amenitiesIds as $unitAmenityId) {
//                    $unit->amenities [] = $amenities[$unitAmenityId];
//                }

                $post = $wpdb->get_row(
                    "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$unit->id."' LIMIT 1;"
                );
                if (isset($post->post_id)) {
                    $unitsUpdated++;
                    $post_id = $post->post_id;

                    //excludes
                    $youtube = null;
                    $youtube_id = null;
                    $youtube = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_youtube_id' LIMIT 1;"
                    );
                    if ($youtube) {
                        $youtube_id = $youtube->meta_value;
                    }
                    $custom_desc = null;
                    $custom_desc = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_disable_sync_description' LIMIT 1;"
                    );
                    if ($custom_desc) {
                        $custom_desc = $custom_desc->meta_value;
                    }
                    $yoast = null;
                    $yoast_linkdex = null;
                    $yoast = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_linkdex' LIMIT 1;"
                    );
                    if ($yoast) {
                        $yoast_linkdex = $yoast->meta_value;
                    }
                    $yoast = null;
                    $yoast_metadesc = null;
                    $yoast = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_metadesc' LIMIT 1;"
                    );
                    if ($yoast) {
                        $yoast_metadesc = $yoast->meta_value;
                    }
                    $yoast = null;
                    $yoast_title = null;
                    $yoast = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_title' LIMIT 1;"
                    );
                    if ($yoast) {
                        $yoast_title = $yoast->meta_value;
                    }
                    $yoast = null;
                    $yoast_focuskw = null;
                    $yoast = $wpdb->get_row(
                        "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_focuskw' LIMIT 1;"
                    );
                    if ($yoast) {
                        $yoast_focuskw = $yoast->meta_value;
                    }

                    $parent = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId) ? 0 : 1;

                    $wpdb->query(
                        "DELETE FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key != '_thumbnail_id'  ;"
                    );
                    $wpdb->query(
                        $wpdb->prepare(
                            "
                		INSERT INTO $wpdb->postmeta
                		( post_id, meta_key, meta_value )
                		VALUES 
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s )
                	",
                            [
                                $post_id,'_listing_unit_id', $unit->id,
                                $post_id,'_listing_complex_id', $unit->nodeId,
                                $post_id,'_listing_complex_parent', $parent,

                                $post_id,'_listing_lodging_type', $unit->lodgingType->id ?? null,
                                $post_id,'_listing_lodging_type_name', $unit->lodgingType->id ? $lodgingTypes[$unit->lodgingType->id] : null,
                                $post_id,'_listing_overview', $unit->shortDescription ?? null,
                                $post_id,'_listing_bed_types', json_encode($unit->bedTypes),

                                $post_id,'_listing_bedrooms', $unit->bedrooms,
                                $post_id,'_listing_min_bedrooms', isset($unit->bedrooms) ? $unit->bedrooms : 0,
                                $post_id,'_listing_max_bedrooms', isset($unit->bedrooms) ? $unit->bedrooms : 0,

                                $post_id,'_listing_latitude', isset($unit->latitude) ? $unit->latitude : 0,
                                $post_id,'_listing_longitude', isset($unit->longitude) ? $unit->longitude : 0,

                                $post_id,'_listing_area', isset($unit->area) ? $unit->area : 0,

                                $post_id,'_listing_bathrooms', $unit->fullBathrooms + $unit->halfBathrooms,

                                $post_id,'_listing_fullbath', $unit->fullBathrooms,
                                $post_id,'_listing_halfbath', $unit->halfBathrooms,
                                $post_id,'_listing_threequarterbath', $unit->threeQuarterBathrooms,

                                $post_id,'_listing_images', json_encode($unit->images),
                                $post_id,'_listing_amenities', json_encode($unit->amenities),
                                $post_id,'_listing_address', $unit->streetAddress,
                                $post_id,'_listing_city', $unit->locality,
                                $post_id,'_listing_state', $unit->region,
                                $post_id,'_listing_zip', $unit->postal,
                                $post_id,'_listing_occupancy', $occupancy,
                                $post_id,'_listing_min_rate', isset($unit->min_rate) ? $unit->min_rate : 0,
                                $post_id,'_listing_max_rate', isset($unit->max_rate) ? $unit->max_rate : 0,
                                $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate) ? $unit->min_weekly_rate : 0,
                                $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate) ? $unit->max_weekly_rate : 0,
                                $post_id,'_listing_domain', 'villatel',
                                $post_id,'_listing_first_image', ($unit->images) ? $unit->images[0]->url :null,
                                $post_id,'_listing_youtube_id', (!$youtube_id) ? null : $youtube_id,
                                $post_id,'_listing_disable_sync_description', (!$custom_desc) ? null : $custom_desc,
                                $post_id,'_yoast_wpseo_linkdex', (!$yoast_linkdex) ? null : $yoast_linkdex,
                                $post_id,'_yoast_wpseo_metadesc', (!$yoast_metadesc) ? null : $yoast_metadesc,
                                $post_id,'_yoast_wpseo_title', (!$yoast_title) ? null : $yoast_title,
                                $post_id,'_yoast_wpseo_focuskw', (!$yoast_focuskw) ? null : $yoast_focuskw
                            ]
                        ));

                    if (isset($unit->lodgingType) && isset($unit->lodgingType->name)) {
                        update_post_meta($post_id, '_listing_lodging_type_'.$unit->lodgingType->id, $unit->lodgingType->id);
                        $lodgingTypes[$unit->lodgingType->id] = $unit->lodgingType->name;
                    }

                    $my_post = [
                        'ID' => $post_id,
                        'post_title' => $unit->name,
                        'post_author' => 1,
                        'comment_status' => 'closed',
                        'ping_status' => 'closed',
                        'post_modified' => $today,
                        'post_modified_gmt' => $today,
                        'post_name' => $this->slugify($unit->name),
                        'post_type' => 'listing',
                        'post_status' => $isDraft ? 'draft' : 'publish'
                    ];
                    wp_update_post($my_post);

                    if (!$custom_desc) {
                        $wpdb->update(
                            $wpdb->posts,
                            ['post_content' => $unit->longDescription],
                            ['ID' => $post_id],
                            ['%s'], ['%d']
                        );
                    }

                    $group_id = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId) ? 'c-'.$unit->node : 'u-'.$unit->id;

                    $wpdb->query(
                        "UPDATE ".$wpdb->prefix."posts set
                unit_id = '".$unit->id."', group_id = '".$group_id."', parent_listing = ".$parent."                    
                WHERE ID = '".$post_id."' ;"
                    );

                    // Create image
                    if (isset($unit->images)) {
                        $image = $wpdb->get_row(
                            "SELECT post_id FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;"
                        );
                        if (!$image) {
                            if ($unit->images[0]->url && $unit->images[0]->url > '') {
                                $this->createImage($post_id, $unit->images[0]->url);
                            }
                        }
                    }


                    //Delete previous if exist
                    $wpdb->query(
                        "DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$activeTerm."';"
                    );

                    // Update the Status;
                    $wpdb->insert(
                        $wpdb->prefix.'term_relationships',
                        ['object_id' => $post_id, 'term_taxonomy_id' => $activeTerm]
                    );

                    // Update the Amenities
                    foreach ($unit->amenities as $amenity) {
                        $term = $wpdb->get_row(
                            "SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                    JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                    WHERE amenity_id = '".$amenity->id."' ;"
                        );
                        if ($term) {
                            $wpdb->query(
                                "DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$term->term_taxonomy_id."';"
                            );
                            $wpdb->query(
                                "INSERT INTO ".$wpdb->prefix."term_relationships set
                            object_id = '".$post_id."',
                            term_taxonomy_id = '".$term->term_taxonomy_id."';"
                            );
                        }
                    }
                } else {
                    $unitsCreated++;

                    $group_id = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId) ? 'c-'.$unit->node : 'u-'.$unit->id;
                    $parent = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId) ? 0 : 1;

                    $wpdb->query(
                        $wpdb->prepare(
                            "
                		INSERT INTO $wpdb->posts
                		( unit_id, post_author, comment_status, ping_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_title, post_status, post_name, post_type,group_id,parent_listing)
                		VALUES 
                		( %d, %d, %s, %s,  %s,  %s,  %s,  %s,  %s, %s,  %s,  %s, %s, %d )
                	",
                            [
                                $unit->id,
                                1,
                                'closed',
                                'closed',
                                $today,
                                $today,
                                $today,
                                $today,
                                $unit->name,
                                $isDraft ? 'draft' : 'publish',
                                $this->slugify($unit->name),
                                'listing',
                                $group_id,
                                $parent
                            ]
                        )
                    );
                    $post_id = $wpdb->insert_id;

                    $wpdb->update(
                        $wpdb->posts,
                        ['post_content' => $unit->longDescription],
                        ['ID' => $post_id],
                        ['%s'], ['%d']
                    );

                    $wpdb->query(
                        $wpdb->prepare(
                            "
                		INSERT INTO $wpdb->postmeta
                		( post_id, meta_key, meta_value )
                		VALUES 
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s ),
                		( %d, %s, %s )
                	",
                            [
                                $post_id,'_listing_unit_id', $unit->id,
                                $post_id,'_listing_complex_id', $unit->nodeId,
                                $post_id,'_listing_complex_parent', $parent,

                                $post_id,'_listing_lodging_type', isset($unit->lodgingType) ? $unit->lodgingType->id : null,
                                $post_id,'_listing_lodging_type_name', $unit->lodgingType->name ?? null,
                                $post_id,'_listing_overview', $unit->shortDescription ?? null,
                                $post_id,'_listing_bed_types', json_encode($unit->bedTypes),

                                $post_id,'_listing_bedrooms', $unit->bedrooms,
                                $post_id,'_listing_min_bedrooms', isset($unit->bedrooms) ? $unit->bedrooms : 0,
                                $post_id,'_listing_max_bedrooms', isset($unit->bedrooms) ? $unit->bedrooms : 0,

                                $post_id,'_listing_latitude', isset($unit->latitude) ? $unit->latitude : 0,
                                $post_id,'_listing_longitude', isset($unit->longitude) ? $unit->longitude : 0,

                                $post_id,'_listing_area', isset($unit->area) ? $unit->area : 0,

                                $post_id,'_listing_bathrooms', $unit->fullBathrooms + $unit->halfBathrooms,

                                $post_id,'_listing_fullbath', $unit->fullBathrooms,
                                $post_id,'_listing_halfbath', $unit->halfBathrooms,
                                $post_id,'_listing_threequarterbath', $unit->threeQuarterBathrooms,

                                $post_id,'_listing_images', json_encode($unit->images),
                                $post_id,'_listing_amenities', json_encode($unit->amenities),
                                $post_id,'_listing_address', $unit->streetAddress,
                                $post_id,'_listing_city', $unit->locality,
                                $post_id,'_listing_state', $unit->region,
                                $post_id,'_listing_zip', $unit->postal,
                                $post_id,'_listing_occupancy', $occupancy,
                                $post_id,'_listing_min_rate', isset($unit->min_rate) ? $unit->min_rate : 0,
                                $post_id,'_listing_max_rate', isset($unit->max_rate) ? $unit->max_rate : 0,
                                $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate) ? $unit->min_weekly_rate : 0,
                                $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate) ? $unit->max_weekly_rate : 0,
                                $post_id,'_listing_domain', 'villatel',
                                $post_id,'_listing_first_image', ($unit->images) ? $unit->images[0]->url :null,
                                $post_id,'_listing_youtube_id', (!$youtube_id) ? null : $youtube_id,
                                $post_id,'_listing_disable_sync_description', (!$custom_desc) ? null : $custom_desc,
                                $post_id,'_yoast_wpseo_linkdex', (!$yoast_linkdex) ? null : $yoast_linkdex,
                                $post_id,'_yoast_wpseo_metadesc', (!$yoast_metadesc) ? null : $yoast_metadesc,
                                $post_id,'_yoast_wpseo_title', (!$yoast_title) ? null : $yoast_title,
                                $post_id,'_yoast_wpseo_focuskw', (!$yoast_focuskw) ? null : $yoast_focuskw
                            ]
                        ));

                    if (isset($unit->lodgingtype) && isset($unit->lodgingType->name)) {
                        update_post_meta($post_id, '_listing_lodging_type_'.$unit->lodgingType->id, $unit->lodgingType->id);
                        $lodgingTypes[$unit->lodgingType->id] = $unit->lodgingType->name;
                    }

                    // Create image
                    $image = $wpdb->get_row(
                        "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;"
                    );
                    if ($image) {
                        if (count($unit->images)) {
                            if (!$image->post_id && $unit->images[0]->url && $unit->images[0]->url > '') {
                                $this->createImage($post_id, $unit->images[0]->url);
                            }
                        }
                    }

                    //Create the Status
                    $wpdb->insert(
                        $wpdb->prefix.'term_relationships',
                        ['object_id' => $post_id, 'term_taxonomy_id' => $activeTerm]
                    );

                    // Setup amenities as features
                    if (isset($unit->amenities)) {
                        foreach ($unit->amenities as $amenity) {
                            $term = $wpdb->get_row(
                                "SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                        WHERE amenity_id = '".$amenity->id."';"
                            );
                            if ($term) {
                                $wpdb->insert(
                                    $wpdb->prefix.'term_relationships',
                                    ['object_id' => $post_id, 'term_taxonomy_id' => $term->term_taxonomy_id],
                                    ['%d', '%d']
                                );
                            }
                        }
                    }
                }
            }

            update_option('track_connect_lodging_types', json_encode($lodgingTypes));

            return [
                'updated' => $unitsUpdated + $unitsCreated
            ];
        }
        return false;
    }

    public function get_aggregate_availability_pricing_data($unit_id)
    {
        $pricing = $this->get_unit_pricing($unit_id);
        $availability = $this->get_unit_availability($unit_id);
        $rates = (array)$pricing->rateTypes[0]->rates;
        $rates_array = array();
        foreach ($availability as $idx => $day) {
            $date = $day->date;
            if (!isset($rates[$date])) {
                continue;
            }
            $rate = (array)$rates[$date];
            $rate['avail'] = $day->count;
            $rate['price'] = $rate['rate'];
            $rate['rate'] = array(
                'min' => $rate['rate'],
                'max' => $rate['rate']
            );
            $rate['min'] = $rate['stay']->min ?? 0;
            $rate['depart'] = 0;
            $rates_array[$date] = $rate;
        }
        return $rates_array;
    }

    public function get_unit_pricing($unit_id)
    {
        $max_booking_days = env('TRACK_MAX_BOOKING_DAYS') ?: '720'; // default to 720 days
        $end_date = date('Y-m-d', strtotime('+'.$max_booking_days.' days'));

        return $this->request([
            'endpoint' => "/api/pms/units/$unit_id/pricing?"."endDate=$end_date",
        ]);
    }

    public function get_unit_availability($unit_id)
    {
        return $this->request([
            'endpoint' => "/api/v2/pms/units/$unit_id/availability"
        ]);
    }

    public function rebuildTaxonomies()
    {
        global $wpdb;

        $wpdb->query(
            "UPDATE ".$wpdb->prefix."term_taxonomy SET count = (
            SELECT COUNT(*) FROM ".$wpdb->prefix."term_relationships rel
                LEFT JOIN ".$wpdb->prefix."posts po ON (po.ID = rel.object_id)
                WHERE 
                    rel.term_taxonomy_id = ".$wpdb->prefix."term_taxonomy.term_taxonomy_id
                    AND 
                    ".$wpdb->prefix."term_taxonomy.taxonomy NOT IN ('link_category')
                    AND 
                    po.post_status IN ('publish', 'future')
            )"
        );
    }

    public function getUnitNodes()
    {
        global $wpdb;

        $nodes = $this->request([
            'endpoint' => "/api/pms/nodes"
        ]);

        $wpdb->query("UPDATE ".$wpdb->prefix."track_node_types set active = 0");

        if (!isset($nodes->errors) && isset($nodes->_embedded->nodes)) {
            foreach ($nodes->_embedded->nodes as $node) {
                $nodeType = $wpdb->get_row(
                    "SELECT id FROM ".$wpdb->prefix."track_node_types WHERE type_id = '".$node->_embedded->type->id."';"
                );

                if (!$nodeType) {
                    $wpdb->insert(
                        $wpdb->prefix.'track_node_types',
                        [
                            'name' => $node->_embedded->type->name,
                            'type_id' => $node->_embedded->type->id,
                            'active' => 1
                        ],
                        ['%s', '%d', '%d']
                    );
                } else {
                    $wpdb->update(
                        $wpdb->prefix.'track_node_types',
                        ['name' => $node->_embedded->type->name, 'active' => 1],
                        ['type_id' => $node->_embedded->type->id],
                        ['%s', '%d'], ['%d']
                    );
                }

                $term = $wpdb->get_row("SELECT term_id FROM ".$wpdb->prefix."terms WHERE node_id = '".$node->id."';");
                if (!$term) {
                    $wpdb->insert(
                        $wpdb->prefix.'terms',
                        [
                            'name' => $node->name,
                            'node_id' => $node->id,
                            'node_type_id' => $node->_embedded->type->id,
                            'slug' => $this->slugify($node->name)
                        ],
                        ['%s', '%d', '%d', '%s']
                    );
                    $termId = $wpdb->insert_id;

                    $wpdb->insert(
                        $wpdb->prefix.'term_taxonomy',
                        ['term_id' => $termId, 'taxonomy' => 'locations', 'parent' => 0],
                        ['%d', '%s', '%d']
                    );
                    $term_taxonomy_id = $wpdb->insert_id;

                    $nodeUnits = $this->request(['endpoint' => '/api/pms/units?size=-1&nodeId='.$node->id]);
                    if (isset($nodeUnits) && $nodeUnits->status !== 404) {
                        foreach ($nodeUnits->_embedded->units as $unit) {
                            $post = $wpdb->get_row(
                                "SELECT ID FROM ".$wpdb->prefix."posts WHERE unit_id = '".$unit->id."';"
                            );
                            if ($post) {
                                $wpdb->query(
                                    "DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post->ID."' AND term_taxonomy_id = '".$term_taxonomy_id."';"
                                );
                                $wpdb->insert(
                                    $wpdb->prefix.'term_relationships',
                                    ['object_id' => $post->ID, 'term_taxonomy_id' => $term_taxonomy_id],
                                    ['%d', '%d']
                                );
                            }
                        }
                    }
                } else {
                    $wpdb->update(
                        $wpdb->prefix.'terms',
                        [
                            'name' => $node->name,
                            'node_type_id' => $node->_embedded->type->id,
                            'slug' => $this->slugify($node->name)
                        ],
                        ['node_id' => $node->id],
                        ['%s', '%d', '%s'], ['%d']
                    );

                    $term = $wpdb->get_row(
                        "SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                WHERE node_id = '".$node->id."'  
                LIMIT 1;"
                    );

                    if ($term) {
                        $nodeUnits = $this->request(['endpoint' => '/api/pms/units?size=-1&nodeId='.$node->id]);
                        if (isset($nodeUnits) && $nodeUnits->status !== 404) {
                            foreach ($nodeUnits->_embedded->units as $unit) {
                                $post = $wpdb->get_row(
                                    "SELECT ID FROM ".$wpdb->prefix."posts WHERE unit_id = '".$unit->id."';"
                                );
                                if ($post) {
                                    $wpdb->query(
                                        "DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post->ID."' AND term_taxonomy_id = '".$term->term_taxonomy_id."';"
                                    );
                                    $wpdb->insert(
                                        $wpdb->prefix.'term_relationships',
                                        ['object_id' => $post->ID, 'term_taxonomy_id' => $term->term_taxonomy_id],
                                        ['%d', '%d']
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function getAmenities()
    {
        global $wpdb;

        $amenityArray = $this->request([
            'endpoint' => "/api/pms/units/amenities?size=-1"
        ]);

        $wpdb->query("UPDATE ".$wpdb->prefix."track_amenities set active = 0");

        $amenitiesResult = [];
        foreach ($amenityArray->_embedded->amenities as $amenity) {
            $amenitiesResult [$amenity->id] = (object)[
                'name' => $amenity->name,
                'id' => $amenity->id,
                'type' => $amenity->groupName,
            ];

            $amenityId = $wpdb->get_row(
                "SELECT id FROM ".$wpdb->prefix."track_amenities WHERE amenity_id = '".$amenity->id."';"
            );
            if (!$amenityId) {
                $wpdb->insert(
                    $wpdb->prefix.'track_amenities',
                    [
                        'active' => 1,
                        'name' => $amenity->name,
                        'amenity_id' => $amenity->id,
                        'group_name' => $amenity->groupName,
                        'group_id' => $amenity->groupId
                    ],
                    ['%d', '%s', '%d', '%s', '%d']
                );
            } else {
                $wpdb->update(
                    $wpdb->prefix.'track_amenities',
                    [
                        'active' => 1,
                        'name' => $amenity->name,
                        'group_id' => $amenity->groupId,
                        'group_name' => $amenity->groupName
                    ],
                    ['amenity_id' => $amenity->id],
                    ['%d', '%s', '%d', '%s'], ['%d']
                );
            }

            $term = $wpdb->get_row("SELECT term_id FROM ".$wpdb->prefix."terms WHERE amenity_id = '".$amenity->id."';");
            if (!$term) {
                $wpdb->insert(
                    $wpdb->prefix.'terms',
                    [
                        'name' => $amenity->name,
                        'amenity_id' => $amenity->id,
                        'slug' => $this->slugify($amenity->name)
                    ],
                    ['%d', '%d', '%s']
                );

                $wpdb->insert(
                    $wpdb->prefix.'term_taxonomy',
                    [
                        'term_id' => $term->term_id,
                        'taxonomy' => 'features',
                        'description' => $amenity->groupName,
                        'parent' => 0
                    ],
                    ['%d', '%s', '%s', '%d']
                );
            } else {
                $wpdb->update(
                    $wpdb->prefix.'terms',
                    ['name' => $amenity->name, 'slug' => $this->slugify($amenity->name)],
                    ['amenity_id' => $amenity->id],
                    ['%s', '%s',], ['%d']
                );

                $amenityTax = $wpdb->get_row(
                    "SELECT term_id FROM ".$wpdb->prefix."term_taxonomy WHERE term_id = '".$term->term_id."';"
                );
                if ($amenityTax) {
                    $wpdb->update(
                        $wpdb->prefix.'term_taxonomy',
                        ['description' => $amenity->groupName],
                        ['term_id' => $term->term_id],
                        ['%s'], ['%d']
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->prefix.'term_taxonomy',
                        [
                            'term_id' => $term->term_id,
                            'taxonomy' => 'features',
                            'description' => $amenity->groupName,
                            'parent' => 0
                        ],
                        ['%d', '%s', '%s', '%d']
                    );
                }
            }
        }

        return $amenitiesResult;
    }

    public function getAvailableUnits($checkin,$checkout,$bedrooms = false,$nodeTypeId = false,$rateType = 1){
		global $wpdb;

		$checkin = date('Y-m-d', strtotime($checkin));
		$checkout = date('Y-m-d', strtotime($checkout));

		$units = wp_remote_post($this->endpoint.'/api/wordpress/available-units/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
			'body' => array(
    			'token'     => $this->token,
			    'checkin'   => $checkin,
			    'checkout'  => $checkout,
			    'bedrooms'  => false,
			    'ratetype'  => $rateType
			    )
			)
        );

		$unitArray = [];
		$rateArray = [];

		if($this->debug == 1){
			print_r(json_decode($units['body']));
		}

		if(json_decode($units['body'])->success == false){
			return [
				'success' => false,
				'message' => json_decode($units['body'])->message
			];
		}

		foreach(json_decode($units['body'])->response->available_nodes as $avail){

    		if(isset($avail->unit)){
		        $query = $wpdb->get_row("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$avail->unit."' LIMIT 1; ");
		        if(isset($query->post_id)){
                    $unitArray[] = $query->post_id;
                    $rateArray[$avail->unit] = $avail->rate;
                }
            }
        }

        return [
        	'success' => true,
        	'units'   => $unitArray,
        	'rates'	  => $rateArray
        ];
    }

    public function getReservedDates($unitId){
		global $wpdb;

		$units = wp_remote_get($this->endpoint.'/api/pms/units/'.$unitId.'/availability/', [
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( $this->token . ':' . $this->secret)
                ]
            ]
        );

		$dates = json_decode($units['body'])->dates;

		$result = [];
		$previousDate = [];
		foreach ($dates as $k => $v){
		    $date = new \DateTime($k);
		    $date->modify('-1 Day');
		    if(!$v->avail && array_key_exists($date->format('Y-m-d'), $previousDate) && !$previousDate[$date->format('Y-m-d')]){
		        $result[$k] = $k;
            }
            $previousDate[$k] = $v->avail;
        }
        return $result;
    }

    public function getQuote($unitId,$checkin,$checkout,$persons){

		$quote = wp_remote_post($this->endpoint.'/api/wordpress/quote/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
			'body' => array(
    			'token'     => $this->token,
			    'cid'       => $unitId,
			    'checkin'   => $checkin,
			    'checkout'  => $checkout,
			    'persons'   => $persons
			    )
			)
        );

        return json_decode($quote['body']);
    }

	static public function slugify($text){
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)){
			return 'n-a';
		}

		return $text;
	}

	public function createImage($post_id,$url){
    	// Add Featured Image to Post
        $image_url  = $url; // Define the image URL here
        $upload_dir = wp_upload_dir(); // Set upload folder
        $image_data = file_get_contents($image_url); // Get image data
        $filename   = basename($image_url); // Create image file name

        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Create the image  file on the server
        file_put_contents( $file, $image_data );

        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );

        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

        // Include image.php
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attach_id );
	}

}
