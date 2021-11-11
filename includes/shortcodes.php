<?php 
/**
 * Adds shortcode to display listings
 */

include_once 'helpers.php';

add_shortcode( 'listings', 'wp_listings_shortcode' );

function wp_listings_shortcode($atts, $content = null) {
    extract(shortcode_atts(array(
        'id'       => '',
        'taxonomy' => '',
        'term'     => '',
        'limit'    => '',
        'columns'  => ''
    ), $atts ) );

    /**
     * if limit is empty set to all
     */
    if(!$limit) {
        $limit = -1;
    }

    /**
     * if columns is empty set to 0
     */
    if(!$columns) {
        $columns = 0;
    }

    /*
     * query args based on parameters
     */
    $query_args = array(
        'post_type'       => 'listing',
        'posts_per_page'  => $limit
    );

    if($id) {
        $query_args = array(
            'post_type'       => 'listing',
            'post__in'        => explode(',', $id)
        );
    }
    
    
            
    if($term && $taxonomy) {
        $query_args = array(
            'post_type'       => 'listing',
            'posts_per_page'  => $limit,
            'tax_query'       => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'     => $term
                )
            )
        );
    }
    
    $query_args += array(
        'post_status'=> 'publish',
    	'orderby'    => 'name',
    	'order'      => 'ASC',
    	'groupby'    => 'group_id'
    );
    
    
    
    /*
     * start loop
     */
    global $post;

    $listings_array = get_posts( $query_args );

    $count = 0;

    $output = '<div class="wp-listings-shortcode">';

    foreach ( $listings_array as $post ) : setup_postdata( $post );
    
        // This will override for complexes
        if(!$post->parent_listing && !$complexId){
            continue;
        }

        include(track_connect_view_override('shortcodes', 'listings.php'));

    endforeach;

    $output .= '</div><!-- .wp-listings-shortcode -->';

    wp_reset_postdata();

    return $output;
    
}
