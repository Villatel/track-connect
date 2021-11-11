<?php
namespace plugins\api;

class pluginApi{
	protected $endpoint;
	protected $client;
	protected $url;

	public function __construct($domain,$token, $secret, $debug = 0){
        $this->domain = $domain;	
        $this->token = $token;
        $this->secret = $secret;
        $this->debug = 	$debug;
        $this->endpoint = 'https://'.strtolower($domain).'.trackhs.com';
	}
    
    public function getEndPoint(){
        return $this->endpoint;
    }
    
    public function getUnitCount(){
        global $wpdb;
        $units = wp_remote_post($this->endpoint.'/api/wordpress/unit-count/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
            'body' => array(
    			'token'     => $this->token
			    )
			)
        );
           
        // Disable inactive units      
        $ids = json_decode($units['body'])->units;
        
        $posts = $wpdb->get_results("SELECT posts.ID AS id FROM `".$wpdb->prefix."posts` AS posts
            WHERE post_type='listing' AND post_status ='publish'
            AND ID IN(SELECT post_id FROM `".$wpdb->prefix."postmeta` WHERE meta_key='_listing_unit_id' AND meta_value NOT IN (".implode(',',$ids).") ) ");
        
		if(!empty($posts)){
    		foreach($posts as $post){
    			$wpdb->update( $wpdb->posts, 
                    array( 'post_status' => 'draft' ), 
                    array( 'ID' => $post->id ), 
                    array( '%s' ), array( '%d' ) );
            }
        }
              
        return json_decode($units['body']);
    }
    
    public function removeActive(){
        global $wpdb;
        
        $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
        WHERE slug = 'active' 
        LIMIT 1;");
        $wpdb->delete( $wpdb->prefix.'term_relationships', array('term_taxonomy_id' => $term->term_taxonomy_id));
    } 
    
    public function getComplexes($nodeTypeId = null){
        global $wpdb;
        if($nodeTypeId){
    		$units = wp_remote_post($this->endpoint.'/api/wordpress/complexes/',
    		array(
    			'timeout'     => 500,
                'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
    			'body' => array(
        			'token'     => $this->token,
        			'nodetype'  => $nodeTypeId
    			    )
    			)
            );
             
            $this->getAmenities();
            
            // Check for complex id
//            $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."posts' AND column_name = 'complex_id'"  );
//            if(empty($row)){
//                $wpdb->query("ALTER TABLE ".$wpdb->prefix."posts ADD COLUMN complex_id bigint(20)");
//            }
//
//            // Check for grouping column
//            $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."posts' AND column_name = 'group_id'"  );
//            if(empty($row)){
//                $wpdb->query("ALTER TABLE ".$wpdb->prefix."posts ADD COLUMN group_id varchar(50)");
//            }
//
//            // Check for parent column
//            $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix."posts' AND column_name = 'parent_listing'"  );
//            if(empty($row)){
//                $wpdb->query("ALTER TABLE ".$wpdb->prefix."posts ADD COLUMN parent_listing bigint(20)");
//            }
        
            $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
            JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
            WHERE slug = 'active' 
            LIMIT 1;");
            $activeTerm = $term->term_taxonomy_id;
            $domain = strtolower($this->domain);
            $unitsCreated = 0;
            $unitsUpdated = 0;
            $lodgingTypes = [];
            if(get_option('track_connect_lodging_types')){
                $lodgingTypes = (array)json_decode(get_option('track_connect_lodging_types'));
            }

            foreach(json_decode($units['body'])->response as $id => $unit){
                if(isset($unit->images)){
                    if(count($unit->images)){
                        usort($unit->images, function($a, $b) {
                            return $a->rank - $b->rank;
                        });
                    }                      
                }
                if(!isset($unit->name)){
                    continue;
                }
                $today = date('Y-m-d H:i:s');
                
                
    			$post = $wpdb->get_row("SELECT ID as post_id FROM ".$wpdb->prefix."posts WHERE complex_id = '".$id."' LIMIT 1;");
    			if(isset($post->post_id)){
        			$post_id = $post->post_id;
        			
        			//excludes
        			$youtube_id = null;
        			$youtube = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_youtube_id' LIMIT 1;");
        			if($youtube){
        				$youtube_id = $youtube->meta_value;
        			}
        			$custom_desc = null;
        			$custom_desc = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_disable_sync_description' LIMIT 1;");
        			if($custom_desc){
        				$custom_desc = $custom_desc->meta_value;
        			}
        			$yoast = null;
        			$yoast_linkdex = null;
        			$yoast = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_linkdex' LIMIT 1;");
        			if($yoast){
        				$yoast_linkdex = $yoast->meta_value;
        			}
        			$yoast = null;
        			$yoast_metadesc = null;
        			$yoast = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_metadesc' LIMIT 1;");
        			if($yoast){
        				$yoast_metadesc = $yoast->meta_value;
        			}
        			$yoast = null;
        			$yoast_title = null;
        			$yoast = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_title' LIMIT 1;");
        			if($yoast){
        				$yoast_title = $yoast->meta_value;
        			}
        			$yoast = null;
        			$yoast_focuskw = null;
        			$yoast = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_focuskw' LIMIT 1;");
        			if($yoast){
        				$yoast_focuskw = $yoast->meta_value;
        			}
        			
        			$wpdb->query("DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key != '_thumbnail_id'  ;");
        			$wpdb->query( $wpdb->prepare( 
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
                    		( %d, %s, %s )
                    	", 
                        array(
                            $post_id,'_listing_complex_id', $id,
                            $post_id,'_listing_complex_parent', 1,
                            $post_id,'_listing_lodging_type', isset($unit->lodgingtype)?$unit->lodgingtype:null,
                            $post_id,'_listing_lodging_type_name', isset($unit->lodgingtypename)?$unit->lodgingtypename:null,
                            $post_id,'_listing_min_bedrooms', isset($unit->minbedrooms)?$unit->minbedrooms:0,
                            $post_id,'_listing_max_bedrooms', isset($unit->maxbedrooms)?$unit->maxbedrooms:0,
                            $post_id,'_listing_min_bathrooms', isset($unit->minfullbathrooms)?$unit->minfullbathrooms:0,
                            $post_id,'_listing_max_bathrooms', isset($unit->maxfullbathrooms)?$unit->maxfullbathrooms:0,
                            $post_id,'_listing_min_persons', isset($unit->minpersons)?$unit->minpersons:0,
                            $post_id,'_listing_max_persons', isset($unit->maxpersons)?$unit->maxpersons:0,
                            $post_id,'_listing_overview', isset($unit->overview)?$unit->overview:null,
                            $post_id,'_listing_bedrooms', isset($unit->minbedrooms)?$unit->minbedrooms:null,
                            $post_id,'_listing_bathrooms', isset($unit->minbathrooms)?$unit->minbathrooms:null,
                            $post_id,'_listing_images', isset($unit->images)?json_encode($unit->images):null,
                            $post_id,'_listing_amenities', isset($unit->amenities)?json_encode($unit->amenities):null,
                            $post_id,'_listing_address', isset($unit->address)?$unit->address:null,
                            $post_id,'_listing_city', isset($unit->city)?$unit->city:null,
                            $post_id,'_listing_state', isset($unit->state)?$unit->state:null,
                            $post_id,'_listing_zip', isset($unit->zip)?$unit->zip:null,
                            $post_id,'_listing_occupancy', isset($unit->occupancy)?$unit->occupancy:null,
                            $post_id,'_listing_min_rate', isset($unit->min_rate)?$unit->min_rate:0,
                            $post_id,'_listing_max_rate', isset($unit->max_rate)?$unit->max_rate:0,
                            $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate)?$unit->min_weekly_rate:0,
                            $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate)?$unit->max_weekly_rate:0,
                            $post_id,'_listing_domain', $domain,
                            $post_id,'_listing_first_image', isset($unit->images)?$unit->images[0]->url:null,
                            $post_id,'_listing_youtube_id', (!$youtube_id)?null:$youtube_id,
                            $post_id,'_listing_disable_sync_description', (!$custom_desc)?null:$custom_desc,                        
                            $post_id,'_yoast_wpseo_linkdex', (!$yoast_linkdex)?null:$yoast_linkdex,
                            $post_id,'_yoast_wpseo_metadesc', (!$yoast_metadesc)?null:$yoast_metadesc,
                            $post_id,'_yoast_wpseo_title', (!$yoast_title)?null:$yoast_title,
                            $post_id,'_yoast_wpseo_focuskw', (!$yoast_focuskw)?null:$yoast_focuskw
                        )
                    ));

                    if($unit->lodgingtypes){
                        foreach ($unit->lodgingtypes as $lodgingTypeId => $lodgingTypeName){
                            update_post_meta($post_id, '_listing_lodging_type_'.$lodgingTypeId, $lodgingTypeId);
                            $lodgingTypes[$lodgingTypeId] = $lodgingTypeName;
                        }
                    }

                    $my_post = array(
                          'ID'           => $post_id,
                          'post_title'   => $unit->name,
                          'post_author'  => 1,
                          'comment_status' => 'closed',
                          'ping_status' => 'closed',
                          'post_modified' => $today,
                          'post_modified_gmt' => $today,
                          'post_name' => $this->slugify($unit->name),
                          'post_type' => 'listing',
                          'parent_listing'  => 1,
                    );              
                    wp_update_post( $my_post );
                    
                    $wpdb->update( $wpdb->posts, 
                        array( 'group_id' => 'c-'.$id ), 
                        array( 'ID' => $post_id ), 
                        array( '%s' ), array( '%d' ) );
                    
                    //Create the Status  
                    $getActive = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = ".$activeTerm);
                    if(!$getActive){
                        $wpdb->insert( $wpdb->prefix.'term_relationships',
                        array( 'object_id' => $post_id, 'term_taxonomy_id' => $activeTerm ));
                    }
                    
                    // Create image
                    if(isset($unit->images)){
                        $image = $wpdb->get_row("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                        if(!$image){
                            if($unit->images[0]->url && $unit->images[0]->url > ''){
                                $this->createImage($post_id,$unit->images[0]->url);
                            }
                        }
                    }

                    // Update the Amenities
                    if(isset($unit->amenities)){
                        foreach($unit->amenities as $amenity){
                            $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                            JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                            WHERE amenity_id = '".$amenity->id."' ;");
                            if($term){
                                $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$term->term_taxonomy_id."';");
                                $wpdb->query("INSERT INTO ".$wpdb->prefix."term_relationships set
                                    object_id = '".$post_id."',
                                    term_taxonomy_id = '".$term->term_taxonomy_id."';");
                            }
                        }
                    }
                    $unitsUpdated++;
                    
    			}else{
                    $unitsCreated++;                
            
                    $wpdb->query( $wpdb->prepare( 
                    	"
                    		INSERT INTO $wpdb->posts
                    		( post_author, comment_status, ping_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_title, post_status, post_name, post_type, group_id, parent_listing, complex_id)
                    		VALUES 
                    		( %d, %s, %s,  %s,  %s,  %s,  %s,  %s, %s,  %s,  %s, %d, %d, %d )
                    	", 
                    	array(
                            1,'closed','closed',$today,$today,$today,$today,$unit->name,'publish',$this->slugify($unit->name),'listing','c-'.$id,1,$id
                    	)
                    ));
                    $post_id = $wpdb->insert_id;                 
                                     
                    $wpdb->query( $wpdb->prepare( 
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
                    		( %d, %s, %s )
                    	", 
                        array(
                            $post_id,'_listing_complex_id', $id,
                            $post_id,'_listing_complex_parent', 1,
                            $post_id,'_listing_lodging_type', isset($unit->lodgingtype)?$unit->lodgingtype:null,
                            $post_id,'_listing_lodging_type_name', isset($unit->lodgingtypename)?$unit->lodgingtypename:null,
                            $post_id,'_listing_min_bedrooms', isset($unit->minbedrooms)?$unit->minbedrooms:0,
                            $post_id,'_listing_max_bedrooms', isset($unit->maxbedrooms)?$unit->maxbedrooms:0,
                            $post_id,'_listing_min_bathrooms', isset($unit->minfullbathrooms)?$unit->minfullbathrooms:0,
                            $post_id,'_listing_max_bathrooms', isset($unit->maxfullbathrooms)?$unit->maxfullbathrooms:0,
                            $post_id,'_listing_min_persons', isset($unit->minpersons)?$unit->minpersons:0,
                            $post_id,'_listing_max_persons', isset($unit->maxpersons)?$unit->maxpersons:0,
                            $post_id,'_listing_overview', isset($unit->overview)?$unit->overview:null,
                            $post_id,'_listing_bedrooms', isset($unit->rooms)?$unit->rooms:null,
                            $post_id,'_listing_bathrooms', isset($unit->bath)?$unit->bath:null,
                            $post_id,'_listing_images', isset($unit->images)?json_encode($unit->images):null,
                            $post_id,'_listing_amenities', isset($unit->amenities)?json_encode($unit->amenities):null,
                            $post_id,'_listing_address', isset($unit->address)?$unit->address:null,
                            $post_id,'_listing_city', isset($unit->city)?$unit->city:null,
                            $post_id,'_listing_state', isset($unit->state)?$unit->state:null,
                            $post_id,'_listing_zip', isset($unit->zip)?$unit->zip:null,
                            $post_id,'_listing_occupancy', isset($unit->occupancy)?$unit->occupancy:null,
                            $post_id,'_listing_min_rate', isset($unit->min_rate)?$unit->min_rate:0,
                            $post_id,'_listing_max_rate', isset($unit->max_rate)?$unit->max_rate:0,
                            $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate)?$unit->min_weekly_rate:0,
                            $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate)?$unit->max_weekly_rate:0,
                            $post_id,'_listing_domain', $domain,
                            $post_id,'_listing_first_image', isset($unit->images)?$unit->images[0]->url:null,
                            $post_id,'_listing_youtube_id', !isset($youtube_id)?null:$youtube_id
                        )
                    ));

                    if($unit->lodgingtypes){
                        foreach ($unit->lodgingtypes as $lodgingTypeId => $lodgingTypeName){
                            update_post_meta($post_id, '_listing_lodging_type_'.$lodgingTypeId, $lodgingTypeId);
                            $lodgingTypes[$lodgingTypeId] = $lodgingTypeName;
                        }
                    }

                    // Create image
                    $image = $wpdb->get_row("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                    if($image){
                        if(count($unit->images)){
                            if(!$image->post_id && $unit->images[0]->url && $unit->images[0]->url > ''){
                                $this->createImage($post_id,$unit->images[0]->url);
                            }
                        }
                    }
                    
                    //Create the Status    
                    $wpdb->insert( $wpdb->prefix.'term_relationships',
                        array( 'object_id' => $post_id, 'term_taxonomy_id' => $activeTerm ));
                    
                    // Setup amenities as features
                    if(isset($unit->amenities)){
                        foreach($unit->amenities as $amenity){
                            $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                            JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                            WHERE amenity_id = '".$amenity->id."';");
                            if($term){
                                $wpdb->insert( $wpdb->prefix.'term_relationships',
                                        array( 'object_id' => $post_id, 'term_taxonomy_id' => $term->term_taxonomy_id ),  
                                        array( '%d', '%d' ) );
                            }
                        }
                    }
                    
    			}			
    
    		}

    		/**
             * It's possible that if they delete a lodging type it won't get removed, if that happens we can just delete
             * the option manually and re-sync.
             */

    		if(count($lodgingTypes)){
                update_option('track_connect_lodging_types', json_encode($lodgingTypes));
            }

            $this->rebuildTaxonomies();
    
            return array(
                'updated'   => $unitsUpdated,
                'created'   => $unitsCreated
            );

        }      
        
    }
       
	public function getUnits($page = 1, $size = 25, $nodeTypeId = 0){
		global $wpdb;
        
        if(!$page){
            $page = 1;
        }
        if(!$size){
            $size = 25;
        }
        $domain = strtolower($this->domain);
        $unitsCreated = 0;
        $unitsUpdated = 0;
        $unitsRemoved = 0;
        $lodgingTypes = [];
        if(get_option('track_connect_lodging_types')){
            $lodgingTypes = (array)json_decode(get_option('track_connect_lodging_types'));
        }

        
        $this->getAmenities();
        $units = wp_remote_post($this->endpoint.'/api/wordpress/units/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
			'body' => array(
    			'token'     => $this->token,
    			'page'      => $page,
    			'size'      => $size
			    )
			)
        );
        
        $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
        WHERE slug = 'active' 
        LIMIT 1;");
        $wpdb->delete( $wpdb->prefix.'term_relationships', array('term_taxonomy_id' => 0));
        $wpdb->delete( $wpdb->prefix.'term_relationships', array('object_id' => 0));
        $activeTerm = $term->term_taxonomy_id;
                
        $unitsRemoved = 0;
		$count = 0;
		if($this->debug == 1){
			print_r(json_decode($units['body']));
		}
		
		foreach(json_decode($units['body'])->response as $id => $unit){
            $count++;
			if (!isset($unit->occupancy) || $unit->occupancy == 0) {
				$occupancy =  isset($unit->rooms) && $unit->rooms >= 1 ? $unit->rooms * 2 : 2;
			} else {
				$occupancy = $unit->occupancy;
			}
            
            if(count($unit->images)){
                usort($unit->images, function($a, $b) {
                    return $a->rank - $b->rank;
                });
            }                      
            $today = date('Y-m-d H:i:s');
            
			$post = $wpdb->get_row("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_listing_unit_id' AND meta_value = '".$id."' LIMIT 1;");
			if(isset($post->post_id)){
    			$unitsUpdated++;
    			$post_id = $post->post_id;
    			
    			//excludes
    			$youtube_id = null;
    			$youtube_id = null;
    			$youtube = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_youtube_id' LIMIT 1;");
    			if($youtube){
    				$youtube_id = $youtube->meta_value;
    			}
    			$custom_desc = null;
    			$custom_desc = null;
    			$custom_desc = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_listing_disable_sync_description' LIMIT 1;");
    			if($custom_desc){
    				$custom_desc = $custom_desc->meta_value;
    			}
    			$yoast = null;
    			$yoast_linkdex = null;
    			$yoast = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_linkdex' LIMIT 1;");
    			if($yoast){
    				$yoast_linkdex = $yoast->meta_value;
    			}
    			$yoast = null;
    			$yoast_metadesc = null;
    			$yoast = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_metadesc' LIMIT 1;");
    			if($yoast){
    				$yoast_metadesc = $yoast->meta_value;
    			}
    			$yoast = null;
    			$yoast_title = null;
    			$yoast = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_title' LIMIT 1;");
    			if($yoast){
    				$yoast_title = $yoast->meta_value;
    			}
    			$yoast = null;
    			$yoast_focuskw = null;
    			$yoast = $wpdb->get_row("SELECT meta_value FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_yoast_wpseo_focuskw' LIMIT 1;");
    			if($yoast){
    				$yoast_focuskw = $yoast->meta_value;
    			}
    			
    			$parent = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId)?0:1;
    			
    			$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key != '_thumbnail_id'  ;");
    			$wpdb->query( $wpdb->prepare( 
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
                    array(
                        $post_id,'_listing_unit_id', $id,
                        $post_id,'_listing_complex_id', $unit->node,
                        $post_id,'_listing_complex_parent', $parent,
                        
                        $post_id,'_listing_lodging_type', isset($unit->lodgingtype)?$unit->lodgingtype:null,
                        $post_id,'_listing_lodging_type_name', isset($unit->lodgingtypename)?$unit->lodgingtypename:null,
                        $post_id,'_listing_overview', isset($unit->overview)?$unit->overview:null,
                        $post_id,'_listing_bed_types', json_encode($unit->bedtypes),
                        
                        $post_id,'_listing_bedrooms', $unit->rooms,
                        $post_id,'_listing_min_bedrooms', isset($unit->rooms)?$unit->rooms:0,
                        $post_id,'_listing_max_bedrooms', isset($unit->rooms)?$unit->rooms:0,
                        
                        $post_id,'_listing_latitude', isset($unit->latitude)?$unit->latitude:0,
                        $post_id,'_listing_longitude', isset($unit->longitude)?$unit->longitude:0,
                        
                        $post_id,'_listing_area', isset($unit->area)?$unit->area:0,

                        $post_id,'_listing_bathrooms', $unit->bath,
                        $post_id,'_listing_fullbath', $unit->fullbath,
                        $post_id,'_listing_halfbath', $unit->halfbath,
                        $post_id,'_listing_threequarterbath', $unit->threequarterbath,

                        $post_id,'_listing_images', json_encode($unit->images),
                        $post_id,'_listing_amenities', json_encode($unit->amenities),
                        $post_id,'_listing_address', $unit->address,
                        $post_id,'_listing_city', $unit->city,
                        $post_id,'_listing_state', $unit->state,
                        $post_id,'_listing_zip', $unit->zip,
                        $post_id,'_listing_occupancy', $occupancy,
                        $post_id,'_listing_min_rate', isset($unit->min_rate)?$unit->min_rate:0,
                        $post_id,'_listing_max_rate', isset($unit->max_rate)?$unit->max_rate:0,
                        $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate)?$unit->min_weekly_rate:0,
                        $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate)?$unit->max_weekly_rate:0,
                        $post_id,'_listing_domain', $domain,
                        $post_id,'_listing_first_image', ($unit->images)?$unit->images[0]->url:null,
                        $post_id,'_listing_youtube_id', (!$youtube_id)?null:$youtube_id,
                        $post_id,'_listing_disable_sync_description', (!$custom_desc)?null:$custom_desc,                        
                        $post_id,'_yoast_wpseo_linkdex', (!$yoast_linkdex)?null:$yoast_linkdex,
                        $post_id,'_yoast_wpseo_metadesc', (!$yoast_metadesc)?null:$yoast_metadesc,
                        $post_id,'_yoast_wpseo_title', (!$yoast_title)?null:$yoast_title,
                        $post_id,'_yoast_wpseo_focuskw', (!$yoast_focuskw)?null:$yoast_focuskw
                    )
                ));

                if(isset($unit->lodgingtype) && isset($unit->lodgingtypename)){
                    update_post_meta($post_id, '_listing_lodging_type_'.$unit->lodgingtype, $unit->lodgingtype);
                    $lodgingTypes[$unit->lodgingtype] = $unit->lodgingtypename;
                }

                $my_post = array(
                      'ID'           => $post_id,
                      'post_title'   => $unit->name,
                      'post_author'  => 1,
                      'comment_status' => 'closed',
                      'ping_status' => 'closed',
                      'post_modified' => $today,
                      'post_modified_gmt' => $today,
                      'post_name' => $this->slugify($unit->name),
                      'post_type' => 'listing',
                      'post_status' => 'publish'
                );              
                wp_update_post( $my_post );
                

                if(!$custom_desc){
                    $wpdb->update( $wpdb->posts, 
                        array( 'post_content' => $unit->description ), 
                        array( 'ID' => $post_id ), 
                        array( '%s' ), array( '%d' ) );
                }
                
                $group_id = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId)?'c-'.$unit->node:'u-'.$id;
                
                $wpdb->query("UPDATE ".$wpdb->prefix."posts set
                unit_id = '".$id."', group_id = '".$group_id."', parent_listing = ".$parent."                    
                WHERE ID = '".$post_id."' ;");
                
                // Create image
                if(isset($unit->images)){
                    $image = $wpdb->get_row("SELECT post_id FROM $wpdb->postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                    if(!$image){
                        if($unit->images[0]->url && $unit->images[0]->url > ''){
                            $this->createImage($post_id,$unit->images[0]->url);
                        }
                    }
                }
                  
                // Update the Status;         
                $wpdb->insert( $wpdb->prefix.'term_relationships',
                    array( 'object_id' => $post_id, 'term_taxonomy_id' => $activeTerm ));
                
                // Update the Amenities
                foreach($unit->amenities as $amenity){
                    $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                    JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                    WHERE amenity_id = '".$amenity->id."' ;");
                    if($term){
                        $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post_id."' AND term_taxonomy_id = '".$term->term_taxonomy_id."';");
                        $wpdb->query("INSERT INTO ".$wpdb->prefix."term_relationships set
                            object_id = '".$post_id."',
                            term_taxonomy_id = '".$term->term_taxonomy_id."';");
                    }
                }
                
			}else{
                $unitsCreated++;                
                
                $group_id = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId)?'c-'.$unit->node:'u-'.$id;
                $parent = ($nodeTypeId > 0 && $unit->nodetype == $nodeTypeId)?0:1;
                
                $wpdb->query( $wpdb->prepare( 
                	"
                		INSERT INTO $wpdb->posts
                		( unit_id, post_author, comment_status, ping_status, post_date, post_date_gmt, post_modified, post_modified_gmt, post_title, post_status, post_name, post_type,group_id,parent_listing)
                		VALUES 
                		( %d, %d, %s, %s,  %s,  %s,  %s,  %s,  %s, %s,  %s,  %s, %s, %d )
                	", 
                	array(
                        $id,1,'closed','closed',$today,$today,$today,$today,$unit->name,'publish',$this->slugify($unit->name),'listing',$group_id,$parent
                	)
                ));
                $post_id = $wpdb->insert_id;
                
                $wpdb->update( $wpdb->posts, 
                    array( 'post_content' => $unit->description ), 
                    array( 'ID' => $post_id ), 
                    array( '%s' ), array( '%d' ) );
                
                                 
                $wpdb->query( $wpdb->prepare( 
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
                    array(
                        $post_id,'_listing_unit_id', $id,
                        $post_id,'_listing_complex_id', $unit->node,
                        $post_id,'_listing_complex_parent', $parent,
                        $post_id,'_listing_lodging_type', isset($unit->lodgingtype)?$unit->lodgingtype:null,
                        $post_id,'_listing_lodging_type_name', isset($unit->lodgingtypename)?$unit->lodgingtypename:null,
                        $post_id,'_listing_overview', isset($unit->overview)?$unit->overview:null,
                        $post_id,'_listing_bed_types', json_encode($unit->bedtypes),
                        
                        $post_id,'_listing_bedrooms', $unit->rooms,
                        $post_id,'_listing_min_bedrooms', isset($unit->rooms)?$unit->rooms:0,
                        $post_id,'_listing_max_bedrooms', isset($unit->rooms)?$unit->rooms:0,
                        
                        $post_id,'_listing_latitude', isset($unit->latitude)?$unit->latitude:0,
                        $post_id,'_listing_longitude', isset($unit->longitude)?$unit->longitude:0,
                        
                        $post_id,'_listing_area', isset($unit->area)?$unit->area:0,

                        $post_id,'_listing_bathrooms', $unit->bath,
                        $post_id,'_listing_fullbath', $unit->fullbath,
                        $post_id,'_listing_halfbath', $unit->halfbath,
                        $post_id,'_listing_threequarterbath', $unit->threequarterbath,

                        $post_id,'_listing_images', json_encode($unit->images),
                        $post_id,'_listing_amenities', json_encode($unit->amenities),
                        $post_id,'_listing_address', $unit->address,
                        $post_id,'_listing_city', $unit->city,
                        $post_id,'_listing_state', $unit->state,
                        $post_id,'_listing_zip', $unit->zip,
                        $post_id,'_listing_occupancy', $occupancy,
                        $post_id,'_listing_min_rate', isset($unit->min_rate)?$unit->min_rate:0,
                        $post_id,'_listing_max_rate', isset($unit->max_rate)?$unit->max_rate:0,
                        $post_id,'_listing_min_weekly_rate', isset($unit->min_weekly_rate)?$unit->min_weekly_rate:0,
                        $post_id,'_listing_max_weekly_rate', isset($unit->max_weekly_rate)?$unit->max_weekly_rate:0,
                        $post_id,'_listing_domain', $domain,
                        $post_id,'_listing_first_image', ($unit->images)?$unit->images[0]->url:null,
                        $post_id,'_listing_youtube_id', null
                    )
                ));

                if(isset($unit->lodgingtype) && isset($unit->lodgingtypename)){
                    update_post_meta($post_id, '_listing_lodging_type_'.$unit->lodgingtype, $unit->lodgingtype);
                    $lodgingTypes[$unit->lodgingtype] = $unit->lodgingtypename;
                }

                // Create image
                $image = $wpdb->get_row("SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id' LIMIT 1;");
                if($image){
                    if(count($unit->images)){
                        if(!$image->post_id && $unit->images[0]->url && $unit->images[0]->url > ''){
                            $this->createImage($post_id,$unit->images[0]->url);
                        }
                    }
                }
                
                //Create the Status    
                $wpdb->insert( $wpdb->prefix.'term_relationships',
                    array( 'object_id' => $post_id, 'term_taxonomy_id' => $activeTerm ));
                    
                // Setup amenities as features
                if(isset($unit->amenities)){
                    foreach($unit->amenities as $amenity){
                        $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                        JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                        WHERE amenity_id = '".$amenity->id."';");
                        if($term){
                            $wpdb->insert( $wpdb->prefix.'term_relationships',
                                    array( 'object_id' => $post_id, 'term_taxonomy_id' => $term->term_taxonomy_id ),  
                                    array( '%d', '%d' ) );
                        }
                    }
                }
                
			}			

		}

		update_option('track_connect_lodging_types', json_encode($lodgingTypes));
        
        return array(
            'updated'   => $unitsUpdated + $unitsCreated
        );
		
	}
	
	public function rebuildTaxonomies(){
    	global $wpdb;
    	
    	$wpdb->query("UPDATE ".$wpdb->prefix."term_taxonomy SET count = (
            SELECT COUNT(*) FROM ".$wpdb->prefix."term_relationships rel
                LEFT JOIN ".$wpdb->prefix."posts po ON (po.ID = rel.object_id)
                WHERE 
                    rel.term_taxonomy_id = ".$wpdb->prefix."term_taxonomy.term_taxonomy_id
                    AND 
                    ".$wpdb->prefix."term_taxonomy.taxonomy NOT IN ('link_category')
                    AND 
                    po.post_status IN ('publish', 'future')
            )"  );
	}
    
    public function getUnitNodes(){
        global $wpdb;
        
        $nodes = wp_remote_post($this->endpoint.'/api/wordpress/unit-nodes/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
			'body' => array(
    			'token'     => $this->token
			    )
			)
        );
        
        $wpdb->query("UPDATE ".$wpdb->prefix."track_node_types set active = 0");
        
        if(isset(json_decode($nodes['body'])->response)){
        foreach(json_decode($nodes['body'])->response as $nodeId => $node){
            $nodeType = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."track_node_types WHERE type_id = '".$node->typeid."';");
            if(!$nodeType){
                $wpdb->insert( $wpdb->prefix.'track_node_types',
                    array( 'name' => $node->typename, 'type_id' => $node->typeid, 'active' => 1 ),  
                    array( '%s', '%d' , '%d' ) );

            }else{
                $wpdb->update( $wpdb->prefix.'track_node_types',
                    array( 'name' => $node->typename, 'active' => 1 ), 
                    array( 'type_id' => $node->typeid ), 
                    array( '%s', '%d' ), array( '%d' ) );
            }
            
            $term = $wpdb->get_row("SELECT term_id FROM ".$wpdb->prefix."terms WHERE node_id = '".$nodeId."';");
            if(!$term){
                $wpdb->insert( $wpdb->prefix.'terms',
                    array( 'name' => $node->name, 'node_id' => $nodeId, 'node_type_id' => $node->typeid, 'slug' => $this->slugify($node->name) ),  
                    array( '%s', '%d' , '%d', '%s' ) );
                $termId = $wpdb->insert_id;
                
                $wpdb->insert( $wpdb->prefix.'term_taxonomy',
                    array( 'term_id' => $termId, 'taxonomy' => 'locations', 'parent' => 0 ),  
                    array( '%d', '%s', '%d' ) );
                $term_taxonomy_id = $wpdb->insert_id;
                    
                if(isset($node->units)){ 
                    foreach($node->units as $unitId){
                        $post = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix."posts WHERE unit_id = '".$unitId."';");
                        if($post){
                            $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post->ID."' AND term_taxonomy_id = '".$term_taxonomy_id."';");
                            $wpdb->insert( $wpdb->prefix.'term_relationships',
                                array( 'object_id' => $post->ID, 'term_taxonomy_id' => $term_taxonomy_id ),  
                                array( '%d', '%d' ) );
                        }
                    }
                }
                      
            }else{
                $wpdb->update( $wpdb->prefix.'terms',
                    array( 'name' => $node->name, 'node_type_id' => $node->typeid, 'slug' => $this->slugify($node->name) ), 
                    array( 'node_id' => $nodeId ), 
                    array( '%s', '%d', '%s' ), array( '%d' ) );
                
                $term = $wpdb->get_row("SELECT term_taxonomy_id FROM ".$wpdb->prefix."term_taxonomy
                JOIN ".$wpdb->prefix."terms ON ".$wpdb->prefix."terms.term_id = ".$wpdb->prefix."term_taxonomy.term_id
                WHERE node_id = '".$nodeId."'  
                LIMIT 1;");             
                
                if($term){   
                    if(isset($node->units)){      
                        foreach($node->units as $unitId){
                            $post = $wpdb->get_row("SELECT ID FROM ".$wpdb->prefix."posts WHERE unit_id = '".$unitId."';");
                            if($post){
                                $wpdb->query("DELETE FROM ".$wpdb->prefix."term_relationships WHERE object_id = '".$post->ID."' AND term_taxonomy_id = '".$term->term_taxonomy_id."';");
                                $wpdb->insert( $wpdb->prefix.'term_relationships',
                                    array( 'object_id' => $post->ID, 'term_taxonomy_id' => $term->term_taxonomy_id ),  
                                    array( '%d', '%d' ) );
                            }
                        }
                    }
                }
            }
            
                    
        } 
        }  
    }
    
    public function getAmenities(){
        global $wpdb;
        
        $amenityArray = wp_remote_post($this->endpoint.'/api/wordpress/amenities/',
		array(
			'timeout'     => 500,
            'user-agent' => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; TrackConnect/'. WP_LISTINGS_VERSION .'; ' . get_bloginfo( 'url' ) ),
			'body' => array(
    			'token'     => $this->token
			    )
			)
        );
        
        $wpdb->query("UPDATE ".$wpdb->prefix."track_amenities set active = 0");
          
        foreach(json_decode($amenityArray['body'])->response as $amenity){
            
            $amenityId = $wpdb->get_row("SELECT id FROM ".$wpdb->prefix."track_amenities WHERE amenity_id = '".$amenity->id."';");
            if(!$amenityId){
                $wpdb->insert( $wpdb->prefix.'track_amenities',
                    array( 'active' => 1, 'name' => $amenity->name, 'amenity_id' => $amenity->id, 'group_name' => $amenity->groupname, 'group_id' => $amenity->groupid ),  
                    array( '%d', '%s', '%d', '%s', '%d' ) );

            }else{
                $wpdb->update( $wpdb->prefix.'track_amenities',
                    array( 'active' => 1, 'name' => $amenity->name, 'group_id' => $amenity->groupid, 'group_name' => $amenity->groupname), 
                    array( 'amenity_id' => $amenity->id ), 
                    array( '%d','%s','%d','%s' ), array( '%d' ) );
            }
            
            $term = $wpdb->get_row("SELECT term_id FROM ".$wpdb->prefix."terms WHERE amenity_id = '".$amenity->id."';");
            if(!$term){
                 $wpdb->insert( $wpdb->prefix.'terms',
                    array( 'name' => $amenity->name, 'amenity_id' => $amenity->id, 'slug' => $this->slugify($amenity->name) ),  
                    array( '%d', '%d', '%s' ) );
                
                 $wpdb->insert( $wpdb->prefix.'term_taxonomy',
                    array( 'term_id' => $term->term_id, 'taxonomy' => 'features', 'description' => $amenity->groupname, 'parent' => 0 ),  
                    array( '%d', '%s', '%s', '%d' ) );
                
                    
            }else{
                $wpdb->update( $wpdb->prefix.'terms',
                    array( 'name' => $amenity->name, 'slug' => $this->slugify($amenity->name) ), 
                    array( 'amenity_id' => $amenity->id ), 
                    array( '%s', '%s', ), array( '%d' ) );
                
                $amenityTax = $wpdb->get_row("SELECT term_id FROM ".$wpdb->prefix."term_taxonomy WHERE term_id = '".$term->term_id."';");
                if($amenityTax){
                $wpdb->update( $wpdb->prefix.'term_taxonomy',
                    array( 'description' => $amenity->groupname ), 
                    array( 'term_id' => $term->term_id ), 
                    array( '%s' ), array( '%d' ) ); 
                }else{
                    $wpdb->insert( $wpdb->prefix.'term_taxonomy',
                    array( 'term_id' => $term->term_id, 'taxonomy' => 'features', 'description' => $amenity->groupname, 'parent' => 0 ),  
                    array( '%d', '%s', '%s', '%d' ) );
                }                 
            }            
        }   
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