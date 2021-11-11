<?php
include_once 'helpers.php';
/**
 * This widget displays listings, based on user input, in any widget area.
 *
 * @package WP Listings
 * @since 0.1.0
 */
class WP_Listings_Featured_Listings_Widget extends WP_Widget {
	
	// used to be WP_Listings_Featured_Listings_Widget
	function __construct() {
		$widget_ops  = array( 'classname' => 'wplistings-featured-listings clearfix', 'description' => __( 'Display grid-style featured listings', 'wp_listings' ) );
		$control_ops = array( 'width' => 300, 'height' => 350 );
		parent::__construct( 'wplistings-featured-listings', __( 'WP Listings - Featured Listings', 'wp_listings' ), $widget_ops, $control_ops );
	}

	/**
	 * Returns the column class
	 *
	 * @param int $number_columns
	 * @param int $number_items
	 */
	function get_column_class($number_columns) {

		$column_class = '';

		// Max of six columns
		$number_columns = ( $number_columns > 6 ) ? 6 : (int)$number_columns;

		// column class
		switch ($number_columns) {
			case 0:
			case 1:
				$column_class = '';
				break;
			case 2:
				$column_class = 'one-half';
				break;
			case 3:
				$column_class = 'one-third';
				break;
			case 4:
				$column_class = 'one-fourth';
				break;
			case 5:
				$column_class = 'one-fifth';
				break;
			case 6:
				$column_class = 'one-sixth';
				break;
		}

		return $column_class;
	}

	function widget( $args, $instance ) {

		extract( $args );
		$column_class = $instance['use_columns'] ? $this->get_column_class($instance['number_columns']) : '';

		echo $before_widget;

		if ( !empty( $instance['posts_term'] ) ) {
			$posts_term = explode( ',', $instance['posts_term'] );
		}

		$query_args = array(
			'post_type'			=> 'listing',
			'posts_per_page'	=> $instance['posts_per_page'],
			'paged'				=> get_query_var('paged') ? get_query_var('paged') : 1
		);

		if ( !empty( $instance['posts_term'] ) && count($posts_term) == 2 ) {
			$query_args[$posts_term['0']] = $posts_term['1'];
		}

		$wp_listings_widget_query = new WP_Query( $query_args );

		$count = 0;

		global $post;
		$template_name = 'featured-listings-widget.php';
		if (file_exists(WP_LISTINGS_VIEWS_DIR."custom_$template_name")) {
			include(WP_LISTINGS_VIEWS_DIR."custom_$template_name");
		} else {
			include("views/$template_name");
		}
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['posts_per_page'] = (int) $new_instance['posts_per_page'];
		$instance['image_size'] 	= strip_tags($new_instance['image_size'] );	
		$instance['use_columns']    = (int) $new_instance['use_columns'];
		$instance['number_columns'] = (int) $new_instance['number_columns'];
		$instance['posts_term']     = strip_tags( $new_instance['posts_term'] );

		return $instance;
	}

	function form( $instance ) {

		$instance = wp_parse_args( $instance, array(
			'title'				=> '',
			'posts_per_page'	=> 3,
			'image_size'		=> 'listings',
			'use_columns'       => 0,
			'number_columns'    => 3,
			'posts_term'        => ''
		) );

		printf(
			'<p><label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" style="%s" /></p>',
			$this->get_field_id('title'),
			__( 'Title:', 'wp_listings' ),
			$this->get_field_id('title'),
			$this->get_field_name('title'),
			esc_attr( $instance['title'] ),
			'width: 95%;'
		);

		//TODO: add check for custom view
		include('views/featured-listings-widget-form.php');
	}
} // EOF