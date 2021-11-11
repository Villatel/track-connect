<?php

$loop = sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', get_permalink(), '<img src="https://d2epyxaxvaz7xr.cloudfront.net/305x208/'.get_post_meta( $post->ID, '_listing_first_image', true ).'"></img> ' );

if ( wp_listings_get_featured()  ) {
    $loop .= sprintf( '<span class="listing-status %s">Featured</span>', strtolower(str_replace(' ', '-', wp_listings_get_status())), wp_listings_get_status() );
}

$loop .= sprintf( '<div class="listing-thumb-meta">' );

if ( '' != get_post_meta( $post->ID, '_listing_text', true ) ) {
    $loop .= sprintf( '<span class="listing-text">%s</span>', get_post_meta( $post->ID, '_listing_text', true ) );
} elseif ( '' != wp_listings_get_property_types() ) {
    $loop .= sprintf( '<span class="listing-property-type">%s</span>', wp_listings_get_property_types() );
}

if ( '' != get_post_meta( $post->ID, '_listing_price', true ) ) {
    $loop .= sprintf( '<span class="listing-price">%s</span>', get_post_meta( $post->ID, '_listing_price', true ) );
}

$loop .= sprintf( '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->' );

if ( '' != get_post_meta( $post->ID, '_listing_open_house', true ) ) {
    $loop .= sprintf( '<span class="listing-open-house">Open House: %s</span>', get_post_meta( $post->ID, '_listing_open_house', true ) );
}

$loop .= sprintf( '<div class="listing-widget-details"><h3 class="listing-title"><a href="%s">%s</a></h3>', get_permalink(), get_the_title() );
$loop .= sprintf( '<p class="listing-address"><span class="listing-city-state-zip">%s, %s </span></p>', wp_listings_get_city(), wp_listings_get_state() );

if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) || '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) || '' != get_post_meta( $post->ID, '_listing_sqft', true )) {
    $loop .= sprintf( '<ul class="listing-beds-baths-sqft"><li class="beds">%s<span>Beds</span></li> <li class="baths">%s<span>Baths</span></li> <li class="sqft">%s<span>Guests</span></li></ul>', get_post_meta( $post->ID, '_listing_bedrooms', true ), get_post_meta( $post->ID, '_listing_bathrooms', true ), get_post_meta( $post->ID, '_listing_occupancy', true )  );
}

$loop .= sprintf('</div><!-- .listing-widget-details -->');

$loop .= sprintf( '<a href="%s" class="button btn-primary more-link">%s</a>', get_permalink(), __( 'View Listing', 'wp_listings' ) );