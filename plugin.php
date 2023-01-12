<?php
/*
	Plugin Name: Track Connect
	Plugin URI: http://wordpress.org/plugins/track-connect/
	Description: Designed to work with Track PM: Syncs units and availability to Wordpress.
	Author: Track HS
	Author URI: http://www.trackhs.com

	Version: 4.0.5

	License: GNU General Public License v2.0 (or later)
	License URI: http://www.opensource.org/licenses/gpl-license.php
*/
require 'class.migrator.php';


/** Global Constants */
define( 'WP_LISTINGS_VIEWS_DIR', get_template_directory().'/trackconnect/views/');
define( 'WP_TRACK_DB_VERSION', '1');
define( 'WP_LISTINGS_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_LISTINGS_VERSION', '4.0.5' );

register_activation_hook( __FILE__, 'wp_listings_activation' );
/**
 * This function runs on plugin activation. It flushes the rewrite rules to prevent 404's
 *
 * @since 1.0
 */
function wp_listings_activation() {
    global $wpdb;

    /** Flush rewrite rules */
    if ( ! post_type_exists( 'listing' ) ) {
        wp_listings_init();
        global $_wp_listings, $_wp_listings_taxonomies, $_wp_listings_templates;
        $_wp_listings->create_post_type();
        $_wp_listings_taxonomies->register_taxonomies();
    }
    /** Possibly needed: Error handling - registration fail if folder not created */
    if (!file_exists(WP_LISTINGS_VIEWS_DIR)) {
        wp_mkdir_p(WP_LISTINGS_VIEWS_DIR);
    }

    $options_table = $wpdb->prefix."options";
    $track_db_version = $wpdb->get_var("SELECT option_value FROM $options_table WHERE option_name = 'track_db_version'");
    if (empty($track_db_version)) {
        $wpdb->insert($options_table ,
            array(
                'option_name' => "track_db_version" ,
                'option_value' => WP_TRACK_DB_VERSION)
        );

        $track_db_version = WP_TRACK_DB_VERSION;
    }
    $migrator = new Migrator($track_db_version, $wpdb);
    $migrator->run();

    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'wp_listings_deactivation' );
/**
 * This function runs on plugin deactivation. It flushes the rewrite rules to get rid of remnants
 *
 * @since 1.0
 */
function wp_listings_deactivation() {

    flush_rewrite_rules();
}

add_action( 'after_setup_theme', 'wp_listings_init' );
/**
 * Initialize Sync.
 *
 * Include the libraries, define global variables, instantiate the classes.
 *
 * @since 1.0
 */
function wp_listings_init() {

    global $_wp_listings, $_wp_listings_taxonomies, $_wp_listings_templates;



    /** Load textdomain for translation */
    load_plugin_textdomain( 'wp_listings', false, basename( dirname( __FILE__ ) ) . '/languages/' );

    /** Includes */
    require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
    require_once( dirname( __FILE__ ) . '/includes/functions.php' );
    require_once( dirname( __FILE__ ) . '/includes/shortcodes.php' );
    require_once( dirname( __FILE__ ) . '/includes/class-listings.php' );
    require_once( dirname( __FILE__ ) . '/includes/class-taxonomies.php' );
    require_once( dirname( __FILE__ ) . '/includes/class-listing-template.php' );
    require_once( dirname( __FILE__ ) . '/includes/class-listings-search-widget.php' );
    require_once( dirname( __FILE__ ) . '/includes/class-featured-listings-widget.php' );

    /** Add theme support for post thumbnails if it does not exist */
    if(!current_theme_supports('post-thumbnails')) {
        add_theme_support( 'post-thumbnails' );
    }

    /** Registers and enqueues scripts for single listings */
    add_action('wp_enqueue_scripts', 'add_wp_listings_scripts');
    function add_wp_listings_scripts() {

        wp_register_style('properticons', '//s3.amazonaws.com/properticons/css/properticons.css', '', null, 'all');
        wp_register_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', '', null, 'all');
        wp_register_style('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css', '', null, 'all');

        wp_register_style('slideshow', WP_LISTINGS_URL . '/includes/css/slippry.css', '', null, 'all');
        wp_register_style('bootstrap-datepicker', WP_LISTINGS_URL . '/includes/css/bootstrap-datepicker.min.css', '', null, 'all');
        wp_register_style('bootstrap-datepicker3', WP_LISTINGS_URL . '/includes/css/bootstrap-datepicker3.min.css', '', null, 'all');
        wp_register_style('daterangepicker', WP_LISTINGS_URL . '/includes/css/daterangepicker.css', '', null, 'all');
        wp_register_style('royalslider', WP_LISTINGS_URL . 'includes/css/royalslider.css', '', null, 'all');
        wp_register_style('slippry', WP_LISTINGS_URL . 'includes/css/slippry.css', '', null, 'all');
        wp_register_style('wp_listings', WP_LISTINGS_URL . 'includes/css/wp-listings.css', '', null, 'all');
        wp_register_style('wp-listings-single', WP_LISTINGS_URL . '/includes/css/wp-listings-single.css', '', null, 'all');
        wp_register_style('wp_listings_widgets', WP_LISTINGS_URL . 'includes/css/wp-listings-widgets.css', '', null, 'all');

        wp_register_script( 'jquery', '//cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js', array('jquery'), null, true );
        wp_register_script( 'fitvids', '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js', array('jquery'), null, true ); // enqueued only on single listings

        wp_register_script( 'bootstrap-datepicker', WP_LISTINGS_URL . 'includes/js/bootstrap-datepicker.min.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-daterangepicker', WP_LISTINGS_URL . 'includes/js/jquery.daterangepicker.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-easing', WP_LISTINGS_URL . 'includes/js/jquery.easing-1.3.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-royalslider', WP_LISTINGS_URL . 'includes/js/jquery.royalslider.min.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-validate', WP_LISTINGS_URL . 'includes/js/jquery.validate.min.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jssor-slider', WP_LISTINGS_URL . 'includes/js/jssor.slider.mini.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'wp-listings-single', WP_LISTINGS_URL . 'includes/js/single-listing.min.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-slideshow-settings', WP_LISTINGS_URL . 'includes/js/slideshow.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'jquery-slideshow', WP_LISTINGS_URL . 'includes/js/slippry.min.js', array('jquery'), null, true ); // enqueued only on single listings
        wp_register_script( 'momentjs', '//cdn.jsdelivr.net/momentjs/latest/moment.min.js', array('jquery'), null, true ); // enqueued only on single listings


        wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');

        wp_enqueue_style('properticons');
        wp_enqueue_style('font-awesome');
        wp_enqueue_style('slideshow');
        wp_enqueue_style('bootstrap-datepicker');
        wp_enqueue_style('bootstrap-datepicker3');
        wp_enqueue_style('daterangepicker');
        wp_enqueue_style('royalslider');
        wp_enqueue_style('slippry');
        wp_enqueue_style('wp_listings');
        wp_enqueue_style('wp-listings-single');
        wp_enqueue_style('wp_listings_widgets');

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'fitvids' );
        wp_enqueue_script( 'bootstrap-datepicker' );
        wp_enqueue_script( 'jquery-daterangepicker' );
        wp_enqueue_script( 'jquery-easing' );
        wp_enqueue_script( 'jquery-royalslider' );
        wp_enqueue_script( 'jquery-validate' );
        wp_enqueue_script( 'jssor-slider' );
        wp_enqueue_script( 'wp-listings-single' );
        wp_enqueue_script( 'jquery-slideshow-settings' );
        wp_enqueue_script( 'jquery-slideshow' );
        wp_enqueue_script( 'momentjs' );
        wp_enqueue_script ('jquery-ui-core');
        wp_enqueue_script( 'jquery-ui-progressbar');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-slider');
    }


    /** Instantiate */
    $_wp_listings = new WP_Listings;
    $_wp_listings_taxonomies = new WP_Listings_Taxonomies;
    $_wp_listings_templates = new Single_Listing_Template;

    add_action( 'widgets_init', 'wp_listings_register_widgets' );

}

/**
 * Quote AJAX from unit page
 *
 * @since 1.0
 */
add_action( 'wp_ajax_quote_request', 'get_quote' );
add_action( 'wp_ajax_nopriv_quote_request', 'get_quote' );

function get_quote(){
    $options = get_option('plugin_wp_listings_settings');
    $unitId = $_POST['cid'];
    $checkin = $_POST['checkin'];
    $checkout = $_POST['checkout'];
    $persons = isset($_POST['persons'])?$_POST['persons']:2;

    require_once( __DIR__ . '/includes/api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);
    $quote =  $request->getQuote($unitId,$checkin,$checkout,$persons);

    wp_send_json( $quote );
}

add_action( 'wp_ajax_sync_units', 'sync_units' );
function sync_units(){
    $options = get_option('plugin_wp_listings_settings');
    $page = null;
    $size = null;
    $page = ($_POST['page'])?$_POST['page']:1;
    $size = ($_POST['size'])?$_POST['size']:25;

    require_once( __DIR__ . '/includes/api/request.php' );
    $request = new plugins\api\pluginApi();
    $sync =  $request->getUnits($page,$size,$options['wp_listings_complex_node']);

    wp_send_json( $sync );
}

add_action( 'wp_ajax_get_unit_count', 'get_unit_count' );
function get_unit_count(){
    $options = get_option('plugin_wp_listings_settings');
    require_once( __DIR__ . '/includes/api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);
    $count =  $request->getUnitCount();
    $request->removeActive();

    wp_send_json( $count );
}

add_action( 'wp_ajax_sync_other', 'sync_other' );
function sync_other(){
    $options = get_option('plugin_wp_listings_settings');
    require_once( __DIR__ . '/includes/api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);
    $request->getUnitNodes();
    $request->rebuildTaxonomies();
    if(isset($options['wp_listings_complex_node']) && $options['wp_listings_complex_node'] > 0){
        $request->getComplexes($options['wp_listings_complex_node']);
    }
}

add_action( 'wp_ajax_sync_complexes', 'sync_complexes' );
function sync_complexes(){
    $options = get_option('plugin_wp_listings_settings');
    require_once( __DIR__ . '/includes/api/request.php' );
    $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);

    if(isset($options['wp_listings_complex_node']) && $options['wp_listings_complex_node'] > 0){
        $request->getComplexes($options['wp_listings_complex_node']);
    }
}

/**
 * Register Widgets that will be used in the plugin
 *
 * @since 1.0
 */
function wp_listings_register_widgets() {

    $widgets = array( 'WP_Listings_Featured_Listings_Widget', 'WP_Listings_Search_Widget' );

    foreach ( (array) $widgets as $widget ) {
        register_widget( $widget );
    }

}
