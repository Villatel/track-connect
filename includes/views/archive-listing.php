<?php
/**
 * The template for displaying Listing Archive pages
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 * @package Track Connect
 * @since 0.1.0
 */

$options = get_option('plugin_wp_listings_settings');
$checkin = (isset($_REQUEST['checkin'])) ? $_REQUEST['checkin'] : false;
$checkout = (isset($_REQUEST['checkout'])) ? $_REQUEST['checkout'] : false;
$bedrooms = (isset($_REQUEST['bedrooms'])) ? $_REQUEST['bedrooms'] : null;
$lowRate = (isset($_REQUEST['low'])) ? $_REQUEST['low'] : 0;
$highRate = (isset($_REQUEST['high'])) ? $_REQUEST['high'] : 0;
$lowBed = (isset($_REQUEST['lowbed'])) ? $_REQUEST['lowbed'] : null;
$highBed = (isset($_REQUEST['highbed'])) ? $_REQUEST['highbed'] : null;
$sleeps = (isset($_REQUEST['sleeps'])) ? $_REQUEST['sleeps'] : 0;
$debug = (isset($_REQUEST['track_debug'])) ? $_REQUEST['track_debug'] : 0;
$lodgingType = (isset($_REQUEST['lodging'])) ? $_REQUEST['lodging'] : 0;

$availableUnits = false;
$checkAvailability = false;
$linkString = '';

if ($checkin && $checkout) {
    $linkString = "?checkin=$checkin&checkout=$checkout";
    $checkAvailability = true;
    require_once(__DIR__ . '/../api/request.php');
    $request = new plugins\api\pluginApi($options['wp_listings_domain'], $options['wp_listings_token'], $options['wp_listings_secret'], $debug);
    $availableUnits = $request->getAvailableUnits($checkin, $checkout, false);
}


if(!function_exists('mam_posts_query')){
// Retrieve consistent random set of posts with pagination
    function mam_posts_query($query) {
        global $mam_posts_query;
        if ($mam_posts_query && strpos($query, 'ORDER BY RAND()') !== false) {
            $query = str_replace('ORDER BY RAND()',$mam_posts_query,$query);
        }
        return $query;
    }
}

if(!function_exists('multi_tax_terms')){
    function multi_tax_terms($where) {
        global $wp_query;
        if ( strpos($wp_query->query_vars['term'], ',') !== false && strpos($where, "AND 0") !== false ) {
            // it's failing because taxonomies can't handle multiple terms
            //first, get the terms
            $term_arr = explode(",", $wp_query->query_vars['term']);
            foreach($term_arr as $term_item) {
                $terms[] = get_terms($wp_query->query_vars['taxonomy'], array('slug' => $term_item));
            }

            //next, get the id of posts with that term in that tax
            foreach ( $terms as $term ) {
                $term_ids[] = $term[0]->term_id;
            }

            $post_ids = get_objects_in_term($term_ids, $wp_query->query_vars['taxonomy']);

            if ( !is_wp_error($post_ids) && count($post_ids) ) {
                // build the new query
                $new_where = " AND ".$wpdb->prefix."posts.ID IN (" . implode(', ', $post_ids) . ") ";
                // re-add any other query vars via concatenation on the $new_where string below here

                // now, sub out the bad where with the good
                $where = str_replace("AND 0", $new_where, $where);
            } else {
                // give up
            }
        }
        return $where;
    }
}


if(!function_exists('archive_listing_loop')){
    function archive_listing_loop() {

        global $post,$wp_query,$wp_the_query,$availableUnits,$checkAvailability,$bedrooms,$checkin,$checkout,$mam_posts_query,$lowRate,$highRate,$lowBed,$highBed, $sleeps, $lodgingType;

        $count = 0; // start counter at 0
        $unitsAvailable = true;

        if($checkAvailability && $availableUnits['success'] == false){
            echo '<div align="center" style="padding:25px;">'.$availableUnits['message'].'</div>';
            $unitsAvailable = false;
        }

        if($checkAvailability && !count($availableUnits['units'])){
            $unitsAvailable = false;
        }

        $avgRates = null;
        $avgRates = $availableUnits['rates'];

        // Start the Loop.
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type'         => 'listing',
            'posts_per_page'    => 15,
            'paged'             => $paged,
            'order'             => 'ASC',
            'orderby'           => 'rand'
        );

        add_filter('query','mam_posts_query');
        $seed = date('G');
        $mam_posts_query = " ORDER BY rand($seed) "; // Turn on filter

        $metaArgs = array();
        if($lowRate > 0){
            $metaArgs[] = array(
                'key' => '_listing_min_rate',
                'compare' => '>=',
                'value' => $lowRate,
                'type' => 'numeric',
            );
        }

        if($highRate > 0 && $highRate < 2500){
            $metaArgs[] = array(
                'key' => '_listing_max_rate',
                'compare' => '<=',
                'value' => $highRate,
                'type' => 'numeric',
            );
        }

        if($lowBed > 0){
            $metaArgs[] = array(
                'key' => '_listing_bedrooms',
                'value' => $lowBed,
                'compare' => '>=',
                'type' => 'numeric',
            );
        }
        if($highBed > 0){
            $metaArgs[] = array(
                'key' => '_listing_bedrooms',
                'value' => $highBed,
                'compare' => '<=',
                'type' => 'numeric',
            );
        }
        if($bedrooms > 0){
            $metaArgs[] = array(
                'key' => '_listing_bedrooms',
                'value' => $bedrooms,
                'compare' => '=',
                'type' => 'numeric',
            );
        }

        if($sleeps > 0){
            $metaArgs[] = array(
                'key' => '_listing_occupancy',
                'value' => $sleeps,
                'compare' => '>=',
                'type' => 'numeric',
            );
        }

        if($lodgingType){
            $metaArgs[] = array(
                'key' => '_listing_lodging_type_'.$lodgingType,
                'compare' => 'EXISTS'
            );
        }

        if(count($metaArgs)){
            $args += array('meta_query' => array(
                $metaArgs
            ));
        }


        if($checkAvailability){
            $args += array('post__in' => $availableUnits['units']);
        }
        $taxargs = array();
        if(get_query_var('status') != ''){
            $taxargs[] = array(
                'taxonomy'          => 'status',
                'field'             => 'slug',
                'terms'             => get_query_var('status')
            );
        }
        if(get_query_var('features') != ''){
            $taxargs[] = array(
                'taxonomy'          => 'features',
                'field'             => 'slug',
                'terms'             => get_query_var('features'),
                'operator'          => 'AND'
            );
        }
        if(get_query_var('locations')){
            $taxargs[] = array(
                'taxonomy'          => 'locations',
                'field'             => 'slug',
                'terms'             => get_query_var('locations')
            );
        }
        if(get_query_var('property-types') != ''){
            $taxargs[] = array(
                'taxonomy'          => 'property-types',
                'field'             => 'slug',
                'terms'             => get_query_var('property-types')
            );
        }

        if(count($taxargs)){
            $args += array('tax_query' => array(
                'relation' => 'AND',
                $taxargs
            ));
        }

        //query_posts($args);
        $wp_query = new WP_Query();
        $wp_query->query($args);
        $mam_posts_query = ''; // Turn off filter

        if ( have_posts() && $unitsAvailable ) :
            //echo $GLOBALS['wp_query']->request; // will spit out the query
            while ( have_posts() ) : the_post();
                //$post = $query->post;

                $unitId = get_post_meta( $post->ID, '_listing_unit_id', true );
                $bedroomSize = get_post_meta( $post->ID, '_listing_bedrooms', true );

                $link = get_permalink();
                if($checkin && $checkout){
                    $link = add_query_arg( 'checkin', $checkin, get_permalink() );
                    $link = add_query_arg( 'checkout', $checkout, $link );
                }

                $count++; // add 1 to counter on each loop
                $first = ($count == 1) ? 'first' : ''; // if counter is 1 add class of first
                $firstImage = get_post_meta( $post->ID, '_listing_first_image', true );

                $loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', $link, '<img src="https://d2epyxaxvaz7xr.cloudfront.net/305x208/'.get_post_meta( $post->ID, '_listing_first_image', true ).'"></img> ' );

                if($firstImage == '' || $firstImage === null){
                    $loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', $link, '<img src="http://placehold.it/305x208">' );
                }

                if ( wp_listings_get_featured()  ) {
                    // Banner across thumb
                    $loop .= sprintf( '<span class="listing-status %s">Featured</span>', strtolower(str_replace(' ', '-', wp_listings_get_status())), wp_listings_get_status() );
                }

                $loop .= sprintf( '<div class="listing-thumb-meta">' );

                if ( $avgRates[$unitId] > 0 ) {
                    $loop .= sprintf( '<span class="listing-property-type">%s</span>', 'avg. rate' );
                    $loop .= sprintf( '<span class="listing-price">$%s/night</span>', number_format($avgRates[$unitId],0) );
                }else{
                    $loop .= sprintf( '<span class="listing-property-type">%s</span>', 'starting at' );
                    $loop .= sprintf( '<span class="listing-price">$%s/night</span>', number_format(get_post_meta( $post->ID, '_listing_min_rate', true ),0) );
                }

                $loop .= sprintf( '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->' );


                $loop .= sprintf( '<div class="listing-widget-details"><h3 class="listing-title"><a href="%s">%s</a></h3>', $link, get_the_title() );

                $loop .= sprintf( '<p class="listing-information">%s BR / %s BA / %s PPL - %s, %s</p>', get_post_meta( $post->ID, '_listing_bedrooms', true ), get_post_meta( $post->ID, '_listing_bathrooms', true ), get_post_meta( $post->ID, '_listing_occupancy', true ),  get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );

                $loop .= sprintf( '<p class="listing-overview">%s</p>', get_post_meta( $post->ID, '_listing_overview', true ) );

                //$loop .= sprintf( '<span style="margin-left:14px;"><a href="%s" class="button btn-primary">%s</a><span>', $link, __( 'View Property', 'wp_listings' ) );

                $loop .= sprintf('</div><!-- .listing-widget-details -->');



                /** wrap in div with column class, and output **/
                printf( '<article id="post-%s" class="listing entry listing-box %s"><div class="listing-wrap">%s</div><!-- .listing-wrap --></article><!-- article#post-## -->', get_the_id(), $first, apply_filters( 'wp_listings_featured_listings_widget_loop', $loop ) );

                if ( 3 == $count ) { // if counter is 3, reset to 0
                    $count = 0;
                }

            endwhile;

        else:
            echo '<div align="center" style="padding:25px;">No units are available with the selected filters.</div>';
        endif;

        if($unitsAvailable){
            wp_listings_paging_nav();
            //wpbeginner_numeric_posts_nav();
        }
    }
}

get_header();
include(track_connect_view_override('archive-listing', 'style.php'));


include(track_connect_view_override('archive-listing', 'header.php'));


include(track_connect_view_override('archive-listing', 'body.php'));

get_footer();
?>