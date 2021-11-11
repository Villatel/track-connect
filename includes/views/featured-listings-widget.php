<?php
if ( ! empty( $instance['title'] ) ) {
    echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $after_title;
}

if ( $wp_listings_widget_query->have_posts() ) : while ( $wp_listings_widget_query->have_posts() ) : $wp_listings_widget_query->the_post();

    $count = ( $count == $instance['number_columns'] ) ? 1 : $count + 1;

    $first_class = ( 1 == $count && 1 == $instance['use_columns'] ) ? ' first' : '';

    include(track_connect_view_override('featured-listings-widget','index.php'));

    /** wrap in div with possible column class, and output **/
    printf( '<div class="listing %s post-%s"><div class="listing-wrap">%s</div></div>', $column_class . $first_class, $post->ID, apply_filters( 'wp_listings_featured_listings_widget_loop', $loop ) );

endwhile; endif;
wp_reset_postdata();

echo $after_widget;

?>