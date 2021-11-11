<p>
    <label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Image Size', 'wp_listings' ); ?>:</label>
    <select id="<?php echo $this->get_field_id( 'image_size' ); ?>" class="wp-listings-image-size-selector" name="<?php echo $this->get_field_name( 'image_size' ); ?>">
        <option value="thumbnail">thumbnail (<?php echo absint( get_option( 'thumbnail_size_w' ) ); ?>x<?php echo absint( get_option( 'thumbnail_size_h' ) ); ?>)</option>
        <?php
        $sizes = wp_listings_get_additional_image_sizes();
        foreach ( (array) $sizes as $name => $size )
            echo '<option value="' . esc_attr( $name ) . '" ' . selected( $name, $instance['image_size'], FALSE ) . '>' . esc_html( $name ) . ' (' . absint( $size['width'] ) . 'x' . absint( $size['height'] ) . ')</option>';
        ?>
    </select>
</p>

<?php
printf(
    '<p>%s <input type="text" name="%s" value="%s" size="3" /></p>',
    __( 'How many results should be returned?', 'wp_listings' ),
    $this->get_field_name('posts_per_page'),
    esc_attr( $instance['posts_per_page'] )
);

echo '<p><label for="'. $this->get_field_id( 'posts_term' ) .'">Display by term:</label>

		<select id="'. $this->get_field_id( 'posts_term' ) .'" name="'. $this->get_field_name( 'posts_term' ) .'">
			<option style="padding-right:10px;" value="" '. selected( '', $instance['posts_term'], false ) .'>'. __( 'All Taxonomies and Terms', 'wp_listings' ) .'</option>';

$taxonomies = get_object_taxonomies('listing');

foreach ( $taxonomies as $taxonomy ) {
    $the_tax_object = get_taxonomy($taxonomy);

    echo '<optgroup label="'. esc_attr( $the_tax_object->label ) .'">';

    $terms = get_terms( $taxonomy, 'orderby=name&hide_empty=1' );

    foreach ( $terms as $term )
        echo '<option style="margin-left: 8px; padding-right:10px;" value="'. esc_attr( $the_tax_object->query_var ) . ',' . $term->slug .'" '. selected( esc_attr( $the_tax_object->query_var ) . ',' . $term->slug, $instance['posts_term'], false ) .'>-' . esc_attr( $term->name ) .'</option>';

    echo '</optgroup>';

}

echo '</select></p>';

?>

<p>
    <input class="checkbox" type="checkbox" <?php checked($instance['use_columns'], 1); ?> id="<?php echo $this->get_field_id( 'use_columns' ); ?>" name="<?php echo $this->get_field_name( 'use_columns' ); ?>" value="1" />
    <label for="<?php echo $this->get_field_id( 'use_columns' ); ?>">Split listings into columns?</label>
</p>
<p>
    <label for="<?php echo $this->get_field_id( 'number_columns' ); ?>">Number of columns</label>
    <select class="widefat" id="<?php echo $this->get_field_id( 'number_columns' ); ?>" name="<?php echo $this->get_field_name( 'number_columns' ); ?>">
        <option <?php selected($instance['number_columns'], 2); ?> value="2">2</option>
        <option <?php selected($instance['number_columns'], 3); ?> value="3">3</option>
        <option <?php selected($instance['number_columns'], 4); ?> value="4">4</option>
        <option <?php selected($instance['number_columns'], 5); ?> value="5">5</option>
        <option <?php selected($instance['number_columns'], 6); ?> value="6">6</option>
    </select>
</p>