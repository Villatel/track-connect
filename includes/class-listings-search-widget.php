<?php

/**
 * This widget creates a search form which uses listings' taxonomy for search fields.
 *
 * @package WP Listings
 * @since 0.1.0
 */
class WP_Listings_Search_Widget extends WP_Widget {

	function __construct() {
		$widget_ops  = array(
			'classname'   => 'listings-search wp-listings-search wp-listings-search-sidebar',
			'description' => __( 'Display listings search dropdown', 'wp_listings' )
		);

		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'listings-search' );

		parent::__construct( 'listings-search', __( 'WP Listings - Search', 'wp_listings' ), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		global $wpdb, $_wp_listings_taxonomies, $wp_query, $wp_the_query;

		$checkin     = ( isset( $_REQUEST['checkin'] ) && $_REQUEST['checkin'] != '' ) ? date( 'm/d/Y',
			strtotime( $_REQUEST['checkin'] ) ) : null;
		$checkout    = ( isset( $_REQUEST['checkout'] ) && $_REQUEST['checkout'] != '' ) ? date( 'm/d/Y',
			strtotime( $_REQUEST['checkout'] ) ) : null;
		$rooms       = ( isset( $_REQUEST['bedrooms'] ) ) ? $_REQUEST['bedrooms'] : '';
		$features    = ( isset( $_REQUEST['features'] ) ) ? $_REQUEST['features'] : array();
		$locations   = ( isset( $_REQUEST['locations'] ) ) ? $_REQUEST['locations'] : null;
		$lowRate     = ( isset( $_REQUEST['low'] ) ) ? $_REQUEST['low'] : false;
		$highRate    = ( isset( $_REQUEST['high'] ) ) ? $_REQUEST['high'] : false;
		$lowBed      = ( isset( $_REQUEST['lowbed'] ) ) ? $_REQUEST['lowbed'] : false;
		$highBed     = ( isset( $_REQUEST['highbed'] ) ) ? $_REQUEST['highbed'] : false;
		$sleeps      = ( isset( $_REQUEST['sleeps'] ) ) ? $_REQUEST['sleeps'] : '';
		$lodgingType = ( isset( $_REQUEST['lodging'] ) ) ? $_REQUEST['lodging'] : '';

		$ratesMin = $wpdb->get_row( "SELECT meta_value as rate FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_listing_min_rate' AND meta_value > 0 ORDER BY ABS(meta_value) ASC;" );
		$ratesMax = $wpdb->get_row( "SELECT meta_value as rate FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_listing_max_rate' ORDER BY ABS(meta_value) DESC;" );

		$bedsMin = $wpdb->get_row( "SELECT meta_value as bed FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_listing_bedrooms' ORDER BY ABS(meta_value) ASC;" );
		$bedsMax = $wpdb->get_row( "SELECT meta_value as bed FROM " . $wpdb->prefix . "postmeta WHERE meta_key = '_listing_bedrooms' ORDER BY ABS(meta_value) DESC;" );

		$amenities       = $wpdb->get_results( "SELECT name FROM " . $wpdb->prefix . "track_amenities WHERE active = 1" );
		$activeAmenities = array();
		foreach ( $amenities as $a ) {
			$activeAmenities[] = $a->name;
		}

		$instance = wp_parse_args( (array) $instance, array(
			'title'       => '',
			'button_text' => __( 'Search Listings', 'wp_listings' )
		) );

		$listings_taxonomies = $_wp_listings_taxonomies->get_taxonomies();

		extract( $args );

		$template_name = 'search-widget.php';
		if ( file_exists( WP_LISTINGS_VIEWS_DIR . "custom_$template_name" ) ) {
			include( WP_LISTINGS_VIEWS_DIR . "custom_$template_name" );
		} else {
			include( "views/$template_name" );
		}
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		global $_wp_listings_taxonomies;

		$instance = wp_parse_args( (array) $instance, array(
			'title'       => '',
			'button_text' => __( 'Search Listings', 'wp_listings' )
		) );

		$listings_taxonomies = $_wp_listings_taxonomies->get_taxonomies();
		$new_widget          = empty( $instance );

		$template_name = 'search-form.php';
		if ( file_exists( WP_LISTINGS_VIEWS_DIR . "custom_$template_name" ) ) {
			include( WP_LISTINGS_VIEWS_DIR . "custom_$template_name" );
		} else {
			include( "views/$template_name" );
		}
	}

	protected function getNodeTypes() {
		global $wpdb;
		return $wpdb->get_results("SELECT id, name, type_id FROM ".$wpdb->prefix."track_node_types WHERE active = 1 ORDER BY name;");
	}

	protected function getNodes($type) {
		global $wpdb;
		return $wpdb->get_results("SELECT name, slug, t.term_id, count FROM ".$wpdb->prefix."terms as t JOIN ".$wpdb->prefix."term_taxonomy tt on tt.term_id = t.term_id and taxonomy = 'locations'  WHERE node_type_id = ".$type->type_id." ORDER BY name");
	}

	protected function getLodgingTypes() {
		global $wpdb;
		return $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_listing_lodging_type_name' AND meta_value != '' GROUP BY meta_value ORDER BY meta_value ASC");
	}
}