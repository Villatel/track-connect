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
$checkin = ($_REQUEST['checkin'])? $_REQUEST['checkin']:false;
$checkout = ($_REQUEST['checkout'])? $_REQUEST['checkout']:false;
$bedrooms = ($_REQUEST['bedrooms'])? $_REQUEST['bedrooms']:false;
$availableUnits = false;
$checkAvailability = false;
$linkString = '';

if($checkin && $checkout){
    $linkString = "?checkin=$checkin&checkout=$checkout";
    $checkAvailability = true;
    require_once( __DIR__ . '/../api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);
    $availableUnits = $request->getAvailableUnits($checkin,$checkout,false);    
}

function archive_listing_loop() {

		global $post,$wp_query,$wp_the_query,$availableUnits,$checkAvailability,$bedrooms,$checkin,$checkout;

		$count = 0; // start counter at 0
        $unitsAvailable = true;

        if($checkAvailability && !count($availableUnits)){
            echo '<div align="center" style="padding:25px;">No units are available from '. date('m/d/Y', strtotime($checkin)) . ' to ' .  date('m/d/Y', strtotime($checkout)). '.</div>';
            $unitsAvailable = false;
        }

		// Start the Loop.	
		$paged = (get_query_var('paged')) ? intval(get_query_var('paged')) : 1;
		$args = array('post_type'=> 'listings','posts_per_page'=> 9);
		if(get_query_var('paged')){    		
    		$args += array('paged' => $paged);
		}
		if($bedrooms > 0){    		
    		$args += array('meta_key' => '_listing_bedrooms','meta_value' => $bedrooms);
		}
		if($checkAvailability){    		
    		$args += array('post__in' => $availableUnits);
		}  
		if(get_query_var('status') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'status',
        			'field'             => 'slug',
        			'terms'             => get_query_var('status')
        		),
                ),);
		}
		if(get_query_var('features') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'features',
        			'field'             => 'slug',
        			'terms'             => get_query_var('features')
        		),
                ),);
		}
		if(get_query_var('locations') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'locations',
        			'field'             => 'slug',
        			'terms'             => get_query_var('locations')
        		),
                ),);
		}
		if(get_query_var('property-types') != ''){   		
    		$args += array('tax_query' => array(
        		array(
        			'taxonomy'          => 'property-types',
        			'field'             => 'slug',
        			'terms'             => get_query_var('property-types')
        		),
                ),);
		}

		query_posts($args);

		if ( have_posts() && $unitsAvailable ) : 
		    while ( have_posts() ) : the_post();

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

			if ( '' != get_post_meta( $post->ID, '_listing_text', true ) ) {
				$loop .= sprintf( '<span class="listing-text">%s</span>', get_post_meta( $post->ID, '_listing_text', true ) );
			} elseif ( '' != wp_listings_get_property_types() ) {
				$loop .= sprintf( '<span class="listing-property-type">%s</span>', wp_listings_get_property_types() );
			}
            
            if ( '' != get_post_meta( $post->ID, '_listing_min_rate', true ) ) {
                $loop .= sprintf( '<span class="listing-property-type">%s</span>', 'starting at' );
				$loop .= sprintf( '<span class="listing-price">$%s/night</span>', number_format(get_post_meta( $post->ID, '_listing_min_rate', true ),0) );
			}
			
			$loop .= sprintf( '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->' );

			if ( '' != get_post_meta( $post->ID, '_listing_open_house', true ) ) {
				//$loop .= sprintf( '<span class="listing-open-house">Open House: %s</span>', get_post_meta( $post->ID, '_listing_open_house', true ) );
			}
			
			if ( '' != get_post_meta( $post->ID, '_listing_city', true ) && '' != get_post_meta( $post->ID, '_listing_state', true ) ) {
				//$loop .= sprintf( '<span class="listing-open-house">%s, %s</span>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
			}

			$loop .= sprintf( '<div class="listing-widget-details"><h3 class="listing-title"><a href="%s">%s</a></h3>', $link, get_the_title() );
			$loop .= sprintf( '<p><span style="margin-left:12px;">%s, %s</span></p>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );

			if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) || '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) || '' != get_post_meta( $post->ID, '_listing_sqft', true )) {
				$loop .= sprintf( '<ul class="listing-beds-baths-sqft"><li class="beds">%s<span>Beds</span></li> <li class="baths">%s<span>Baths</span></li> <li class="sqft">%s<span>Guests</span></li></ul>', get_post_meta( $post->ID, '_listing_bedrooms', true ), get_post_meta( $post->ID, '_listing_bathrooms', true ), get_post_meta( $post->ID, '_listing_occupancy', true )  );
			}

			$loop .= sprintf('</div><!-- .listing-widget-details -->');

			$loop .= sprintf( '<a href="%s" class="button btn-primary more-link">%s</a>', $link, __( 'View Listing', 'wp_listings' ) );

			/** wrap in div with column class, and output **/
			printf( '<article id="post-%s" class="listing entry one-third %s"><div class="listing-wrap">%s</div><!-- .listing-wrap --></article><!-- article#post-## -->', get_the_id(), $first, apply_filters( 'wp_listings_featured_listings_widget_loop', $loop ) );

			if ( 3 == $count ) { // if counter is 3, reset to 0
				$count = 0;
			}

		endwhile;
		
		else:
		    echo '<div align="center" style="padding:25px;">No units are available with the selected filters.</div>';
		endif;
        
        if($unitsAvailable){
            wp_listings_paging_nav();
        }
}



get_header(); ?>

	<section id="primary" class="content-area container inner">
		<div id="content" class="site-content" role="main">


            <header class="archive-header">
                <?php
                $object = get_queried_object();
                $title = '<h1 class="archive-title">Unit Search</h1>';
                echo $title; ?>

                <small><?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?></small>
            </header><!-- .archive-header -->

			<?php archive_listing_loop(); ?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();