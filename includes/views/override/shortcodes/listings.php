<?php

$firstImage = get_post_meta( $post->ID, '_listing_first_image', true );
$count = ( $count == $columns ) ? 1 : $count + 1;

$first_class = ( 1 == $count ) ? 'first' : '';

$output .= '<div class="listing-wrap ' . get_column_class($columns) . ' ' . $first_class . '">';

$output .= sprintf( '<div class="listing-widget-thumb"><a href="%s" class="listing-image-link">%s</a>', get_permalink(), '<img src="https://d2epyxaxvaz7xr.cloudfront.net/278x208/'.get_post_meta( $post->ID, '_listing_first_image', true ).'"></img> ' );

//$output .= '<div class="listing-widget-thumb"><a href="' . get_permalink() . '" class="listing-image-link">' . get_the_post_thumbnail( $post->ID, 'listings' ) . '</a>';


$output .= '<div class="listing-thumb-meta">';

if ( '' != get_post_meta( $post->ID, '_listing_text', true ) ) {
    $output .= '<span class="listing-text">' . get_post_meta( $post->ID, '_listing_text', true ) . '</span>';
} elseif ( '' != wp_listings_get_property_types() ) {
    //$output .= '<span class="listing-property-type">' . wp_listings_get_property_types() . '</span>';
}

if ( '' != get_post_meta( $post->ID, '_listing_price', true ) ) {
    $output .= '<span class="listing-price">' . get_post_meta( $post->ID, '_listing_price', true ) . '</span>';
}

$output .= '</div><!-- .listing-thumb-meta --></div><!-- .listing-widget-thumb -->';

if ( '' != get_post_meta( $post->ID, '_listing_open_house', true ) ) {
    $output .= '<span class="listing-open-house">' . __( "Open House", 'wp_listings' ) . ': ' . get_post_meta( $post->ID, '_listing_open_house', true ) . '</span>';
}

$output .= '<div class="listing-widget-details"><h3 class="listing-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
$output .= '<p class="listing-address"><span class="listing-address">' . get_post_meta( $post->ID, '_listing_city', true ) . ', ' .  get_post_meta( $post->ID, '_listing_state', true ) . '</span><br />';
//$output .= '<span class="listing-city-state-zip">' .get_post_meta( $post->ID, '_listing_overview', true ) . '</span></p>';

if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) || '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) || '' != get_post_meta( $post->ID, '_listing_occupancy', true )) {
    $output .= '<ul class="listing-beds-baths-sqft"><li class="beds">' . get_post_meta( $post->ID, '_listing_bedrooms', true ) . '<span>' . __( "Beds", 'wp_listings' ) . '</span></li> <li class="baths">' . get_post_meta( $post->ID, '_listing_bathrooms', true ) . '<span>' . __( "Baths", 'wp_listings' ) . '</span></li> <li class="sqft">' . get_post_meta( $post->ID, '_listing_occupancy', true ) . '<span>' . __( "Persons", 'wp_listings' ) . '</span></li></ul>';
}

$output .= '</div><!-- .listing-widget-details --></div><!-- .listing-wrap -->';