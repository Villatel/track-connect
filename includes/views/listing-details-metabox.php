<?php
wp_nonce_field( 'wp_listings_metabox_save', 'wp_listings_metabox_nonce' );

global $post,$wpdb;
$options = get_option('plugin_wp_listings_settings');

$pattern = '<p><label>%s<br /><input type="text" name="wp_listings[%s]" value="%s" /></label></p>';

echo '<div style="width: 45%; float: left">';

	foreach ( (array) $this->property_details['col1'] as $label => $key ) {
		printf( $pattern, esc_html( $label ), $key, esc_attr( get_post_meta( $post->ID, $key, true ) ) );
	}

echo '</div>';

echo '<div style="width: 45%; float: left;">';

	foreach ( (array) $this->property_details['col2'] as $label => $key ) {
		printf( $pattern, esc_html( $label ), $key, esc_attr( get_post_meta( $post->ID, $key, true ) ) );
	}

echo '</div><br style="clear: both;" /><br /><br />';

echo '<div style="width: 90%; float: left;">';

	_e('<p><label>Custom Listing Text (custom text to display as overlay on featured listing widget)<br />', 'wp_listings');
	printf( __( '<input type="text" name="wp_listings[_listing_text]" value="%s" /></label></p>', 'wp_listings' ), htmlentities( get_post_meta( $post->ID, '_listing_text', true) ) );

echo '</div><br style="clear: both;" /><br /><br />';

echo '<input name="wp_listings[_listing_disable_sync_description]" id="_listing_disable_sync_description" type="hidden" value="0"   />';
echo '<input name="wp_listings[_listing_disable_sync_description]" id="_listing_disable_sync_description" type="checkbox" value="1" class="code" ' . checked(1, get_post_meta( $post->ID, '_listing_disable_sync_description', true), false ) . ' /> Disable syncing of post Description (so you can use HTML)?';


$nodes = $wpdb->get_results("SELECT term_id, name FROM ".$wpdb->prefix."terms WHERE node_type_id = ".$options['wp_listings_complex_node']." ORDER BY name");
?> 
<h4>Is this listing a complex? 
	<select name="wp_listings[_listing_complex_id]">
		<option value="0">No</option>
	<?php foreach($nodes as $type): ?>
	     <option <?=(get_post_meta( $post->ID, '_listing_complex_id', true) == $type->term_id)?"SELECTED":""; ?> value="<?=$type->term_id?>"><?=$type->name?></option>
	<?php endforeach; ?>
    </select>
	
</h4>