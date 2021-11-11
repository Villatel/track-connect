<?php
/**
 * The Template for displaying all single listing posts
 *
 * @package Track Connect
 * @since 0.1.0
 */


if(get_post_status( $post->ID ) != 'publish' ){
	status_header(404);
	nocache_headers();
	include( get_404_template() );
	exit;
}


/** Set DNS Prefetch to improve performance on single listings templates */
add_filter('wp_head','wp_listings_dnsprefetch', 0);
function wp_listings_dnsprefetch() {
    echo "\n<link rel='dns-prefetch' href='//maxcdn.bootstrapcdn.com' />\n"; // Loads FontAwesome
    echo "<link rel='dns-prefetch' href='//cdnjs.cloudflare.com' />\n"; // Loads FitVids
}

$options = get_option('plugin_wp_listings_settings');
$checkin = isset($_REQUEST['checkin'])? $_REQUEST['checkin']:null;
$checkout = isset($_REQUEST['checkout'])? $_REQUEST['checkout']:null;

if(!function_exists('single_listing_post_content')){
    function single_listing_post_content() {

        global $post, $wbdb, $options, $checkin, $checkout;

        $trackServer = (strtoupper($options['wp_listings_domain']) == 'HSR')?"trackstaging.info":"trackhs.com";
        $imagesArray = json_decode(get_post_meta( $post->ID, '_listing_images')[0]);
        $amenitiesArray = json_decode(get_post_meta( $post->ID, '_listing_amenities')[0]);
        $amenityTypes = [];
        foreach($amenitiesArray as $amen){

            $amenityTypes[$amen->type][] = $amen->name;

        }
        //$amenitiesArray = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'wp_terms' AND column_name = 'amenity_id'"  );
        $unit_id = get_post_meta( $post->ID, '_listing_unit_id', true );

        require_once( __DIR__ . '/../api/request.php' );
        $request = new plugins\api\pluginApi($options['wp_listings_domain'],$options['wp_listings_token'], $options['wp_listings_secret']);
        $unavailableDates = $request->getReservedDates($unit_id);
        $endpoint = $request->getEndPoint();

        $dateRange = '';
        if($checkin && $checkout){
            $dateRange = date('m/d/Y', strtotime($checkin)) . ' to ' . date('m/d/Y', strtotime($checkout));
        }
        ?>

        <!-- Include Required Prerequisites -->
        <script type="text/javascript" src="//cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

        <style>
            .extra-persons-box { white-space: nowrap; }
            .extra-person-box { display: inline-block; width: 50%; white-space: normal; }

            .amenities {
                -moz-column-count: 2;
                -moz-column-gap: 20px;
                -webkit-column-count: 2;
                -webkit-column-gap: 20px;
                column-count: 2;
                column-gap: 20px;
            }

            @media only screen and (max-device-width: 800px), screen and (max-width: 800px) {
                .slide_wrapper {
                    width: 100%;
                    margin: 75px;
                }
                .amenities {
                    -moz-column-count: 1;
                    -moz-column-gap: 0px;
                    -webkit-column-count: 1;
                    -webkit-column-gap: 0px;
                    column-count: 1;
                    column-gap: 0px;
                }

            }
            .slide_block {
                width: 100%;
            }
            .listing-wrapper {
                margin: 0px 75px 10px 75px;

            }
            .date-picker-wrapper {
                z-index: 100000 !important;
            }
            .daterange {
                width: 100% !important;

            }
            .quote-table{
                width: 100%;
            }
            .alnright {
                text-align: right;
            }
            #grand-total-row {
                font-weight: 600;
                text-decoration:underline;
                border-bottom: 1px solid #000;
            }
            .alert-danger {
                margin-top: -20px;
                background-color: #d65f5f;
                font-weight: 300;
                padding: 15px;
                color: white;
            }

            body.has-sidebar #main .sidebar {
                width: 100% !important;
            }
        </style>

        <?php
        $listing_meta = sprintf( '<ul class="listing-meta">');

        if ( '' != get_post_meta( $post->ID, '_listing_min_rate', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-price">$%s to $%s / night</li>', number_format(get_post_meta( $post->ID, '_listing_min_rate', true ),0), number_format(get_post_meta( $post->ID, '_listing_max_rate', true ),0) );
        }

        if ( '' != wp_listings_get_property_types() ) {
            $listing_meta .= sprintf( '<li class="listing-property-type"><span class="label">Property Type: </span>%s</li>', get_the_term_list( get_the_ID(), 'property-types', '', ', ', '' ) );
        }

        if ( '' != get_post_meta( $post->ID, '_listing_city', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-location"><span class="label">Location: </span>%s, %s</li>', get_post_meta( $post->ID, '_listing_city', true ), get_post_meta( $post->ID, '_listing_state', true ) );
        }

        if ( '' != get_post_meta( $post->ID, '_listing_bedrooms', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-bedrooms"><span class="label">Beds: </span>%s</li>', get_post_meta( $post->ID, '_listing_bedrooms', true ) );
        }

        if ( '' != get_post_meta( $post->ID, '_listing_bathrooms', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-bathrooms"><span class="label">Baths: </span>%s</li>', get_post_meta( $post->ID, '_listing_bathrooms', true ) );
        }

        if ( '' != get_post_meta( $post->ID, '_listing_sqft', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-sqft"><span class="label">Sq Ft: </span>%s</li>', get_post_meta( $post->ID, '_listing_sqft', true ) );
        }

        if ( '' != get_post_meta( $post->ID, '_listing_lot_sqft', true ) ) {
            $listing_meta .= sprintf( '<li class="listing-lot-sqft"><span class="label">Lot Sq Ft: </span>%s</li>', get_post_meta( $post->ID, '_listing_lot_sqft', true ) );
        }

        $listing_meta .= sprintf( '</ul>');



        ?>

        <div class="listing-wrapper">
            <div itemscope itemtype="http://schema.org/SingleFamilyResidence" class="entry-content wplistings-single-listing">

                <section class="slide_wrapper">
                    <article class="slide_block">
                        <ul id="thumbnails">
                            <?php $i = 0;
                            foreach($imagesArray as $image): $i++;?>
                                <li><a href="#slide<?=$i?>"><img alt="<?=$image->text?>" src="https://d2epyxaxvaz7xr.cloudfront.net/620x475/<?=$image->url?>"></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?=$listing_meta;?>
                        <div class="thumb-box">
                            <ul class="thumbs">
                                <?php $i = 0;
                                foreach($imagesArray as $image): $i++;?>
                                    <li><a href="#<?=$i?>" data-slide="<?=$i?>"><img src="https://d2epyxaxvaz7xr.cloudfront.net/125x85/<?=$image->url?>"></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </article>
                </section>


                <div class="quote_wrapper clearfix" id="quote_wrapper">
                    <h3 class="widget-title">Reservation Quote</h3>
                    <form target="_blank" action="<?=$endpoint?>/irm/checkout/">
                        <input type="hidden" id="checkin_date" name="checkin" value="<?=date('Y-m-d', strtotime($checkin))?>" >
                        <input type="hidden" id="checkout_date" name="checkout" value="<?=date('Y-m-d', strtotime($checkout))?>" >
                        <input type="hidden" id="cid" name="cid" value="<?=$unit_id?>">
                        <input type="text" name="daterange" id="daterange" placeholder="Select dates..." size="48" value="<?=$dateRange?>"><br>
                        <div class="extra-persons-box">
                            <div class="extra-person-box">
                                <label>Adults</label>
                                <select class="persons" data-id="1" name="person[1]" >
                                    <option value="1">1</option>
                                    <option selected="" value="2">2</option>
                                    <?php for($i =3;$i <= 30; $i++): ?>
                                        <option value="<?=$i?>"><?=$i?></option>
                                    <?php endfor; ?>
                                </select> &nbsp;
                            </div><div class="extra-person-box">
                                <label>Children</label>
                                <select class="persons" data-id="2" name="person[2]" >
                                    <?php for($i =0;$i <= 30; $i++): ?>
                                        <option value="<?=$i?>"><?=$i?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div id="quote-messages">

                        </div>
                        <div id="loading-img" align="center" style="display: none;">
                            <img src="/wp-content/plugins/track-connect/images/ajax-loader.gif">
                        </div>
                        <div id="breakdown-summary" style="display: none; width: 100%;">
                            <table class="quote-table">
                                <tbody>
                                <tr id="nightly-charges-row">
                                    <td>Total Rent</td>
                                    <td class='alnright' id="nightly-charges">
                                    </td>
                                </tr>

                                <tr>
                                    <td>Service Fees</td>
                                    <td class='alnright' id="reservation-charges">
                                    </td>
                                </tr>

                                <tr>
                                    <td>Taxes</td>
                                    <td class='alnright'><span id="taxes"></span></td>
                                </tr>

                                <tr id="grand-total-row">
                                    <td>Grand Total</td>
                                    <td class='alnright'>
                                        <strong id="grand-total"></strong>
                                    </td>
                                </tr>

                                <tr id="deposit-policy">
                                    <td>Deposit Due</td>
                                    <td class='alnright'>
                                        <strong id="deposit-total"></strong>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <div align="center" style="margin-top: 10px;">
                                <button type="submit" class="btn btn-booking">Book Now</button>
                            </div>
                        </div>
                    </form>
                </div>

                <script>
                    jQuery(function($) {
                        var thumbs = jQuery('#thumbnails').slippry({
                            // general elements & wrapper
                            slippryWrapper: '<div class="slippry_box thumbnails" />',
                            // options
                            transition: 'horizontal',
                            pager: false,
                            auto: false,
                            onSlideBefore: function (el, index_old, index_new) {
                                jQuery('.thumbs a img').removeClass('active');
                                jQuery('img', jQuery('.thumbs a')[index_new]).addClass('active');
                            }
                        });
                        jQuery('.thumbs a').click(function () {
                            thumbs.goToSlide($(this).data('slide'));
                            return false;
                        });

                        $("#quote_wrapper").prependTo("#track-widget");

                        function stringifyTomorrow() {
                            var today = moment();
                            var tomorrow = today.add('days', 1);
                            return moment(today).format("YYYY-MM-DD");
                        }
                        $('#daterange').dateRangePicker(
                            {
                                startOfWeek: 'sunday',
                                separator : ' to ',
                                format: 'MM/DD/YYYY',
                                customTopBar: '<b>Please select a Check In and Check Out date...</b>',
                                autoClose: true,
                                selectForward: true,
                                minDays: 2,
                                stickyMonths: true,
                                startDate: moment().format('MM/DD/YYYY'),
                                beforeShowDay: function(t)
                                {
                                    var theDate = moment(t).format("YYYY-MM-DD");
                                    var valid = !(theDate < stringifyTomorrow() <?php
                                        if(count($unavailableDates)){
                                            foreach($unavailableDates as $date){
                                                echo ' || theDate  == "'.$date.'" ';
                                            }
                                        }?>);
                                    var _class = '';
                                    var _tooltip = valid ? '' : 'Unavailable';
                                    return [valid,_class,_tooltip];
                                }

                            }).bind('datepicker-change',function(event,obj)
                        {
                            /* This event will be triggered when second date is selected */
                            $('#checkin_date').val(moment(obj.date1).format("YYYY-MM-DD"));
                            $('#checkout_date').val(moment(obj.date2).format("YYYY-MM-DD"));

                            quoteReservation();
                        });

                        // Quote Method
                        <?php if($checkin > 0 && $checkout > $checkin){ ?>
                        quoteReservation();
                        <?php } ?>

                        // Update on change
                        $('.persons').change(function () {
                            quoteReservation();
                        });

                        function quoteReservation() {
                            $('#quote-messages').hide();
                            $('#quote-messages').empty();
                            $('#breakdown-summary').hide();
                            $('#loading-img').show();
                            // Encode Persons
                            var persons = {};
                            $('.persons').each(function () {
                                persons[$(this).data('id')] = $(this).val();
                            });

                            $.ajax('/wp-admin/admin-ajax.php', {
                                type: "POST",
                                dataType: 'json',
                                data: {
                                    action: 'quote_request',
                                    cid: '<?=$unit_id?>',
                                    checkin: $('#checkin_date').val(),
                                    checkout: $('#checkout_date').val(),
                                    persons: persons
                                },
                                success: function (d) {
                                    $('#loading-img').hide();
                                    if(!d.success) {
                                        $('#quote-messages').show();
                                        if(d.errors && d.errors.length) {
                                            for(var i = 0; i < d.errors.length; i++) {
                                                $('#quote-messages').append('<p class="alert alert-danger">'+ d.errors[i]+'</p>');
                                            }
                                        }
                                        else {
                                            $('#quote-messages').append('<p class="alert alert-danger">'+ d.message+'</p>');
                                        }
                                        return;

                                    }else{
                                        $('#breakdown-summary').show();
                                        $('#nightly-charges').html('$'+d.data.nightlyRates);
                                        $('#reservation-charges').html('$'+d.data.reservationCharges);
                                        $('#taxes').html('$'+d.data.taxes);
                                        $('#grand-total').html('$'+d.data.grandTotal);

                                        $('#deposit-policy').hide();
                                        if ((d.data.depositType != 'Guarantee')) {
                                            $('#deposit-policy').show();
                                            $('#deposit-total').html('$'+d.data.depositTotal);
                                        }
                                    }
                                }
                            });
                        }
                    });
                </script>


                <div id="listing-tabs" class="listing-data">

                    <ul>
                        <!--<li><a href="#listing-availability">Availability</a></li>-->

                        <li><a href="#listing-description">Description</a></li>

                        <li><a href="#listing-details">Details</a></li>

                        <?php if(count($amenitiesArray)): ?>
                            <li><a href="#listing-amenities">Amenities</a></li>
                        <?php endif; ?>

                        <?php if (get_post_meta( $post->ID, '_listing_gallery', true) != '') { ?>
                            <li><a href="#listing-gallery">Photos</a></li>
                        <?php } ?>

                        <?php if (get_post_meta( $post->ID, '_listing_youtube_id', true) != '') { ?>
                            <li><a href="#listing-video">Video</a></li>
                        <?php } ?>

                        <!--
    				<?php if (get_post_meta( $post->ID, '_listing_school_neighborhood', true) != '') { ?>
    				<li><a href="#listing-school-neighborhood">Schools &amp; Neighborhood</a></li>
    				<?php } ?>
    				-->
                    </ul>

                    <!--
                <div id="listing-availability" itemprop="availability">
                    <iframe frameborder="0" width="100%" height="550px" src="http://<?=$options['wp_listings_domain']?>.<?=$trackServer?>/api/vacation_rentals/index.php?cid=<?=get_post_meta( $post->ID, '_listing_unit_id', true )?>&domainweb=<?=$options['wp_listings_domain']?>&online_res=0"></iframe>
                </div>
                -->

                    <div id="listing-description" itemprop="description">
                        <?php if (get_post_meta( $post->ID, '_listing_home_sum', true) != '') {
                            echo get_post_meta( $post->ID, '_listing_home_sum', true);
                        }else{
                            the_content( __( 'View more <span class="meta-nav">&rarr;</span>', 'wp_listings' ) ); ?>
                        <?php } ?>
                    </div><!-- #listing-description -->

                    <div id="listing-details">
                        <?php
                        $details_instance = new WP_Listings();

                        $pattern = '<tr class="wp_listings%s"><td class="label">%s</td><td>%s</td></tr>';

                        echo '<table class="listing-details">';

                        echo '<tbody class="left">';
                        echo '<tr class="wp_listings_listing_price"><td class="label">Rates</td><td>$'.number_format(get_post_meta( $post->ID, '_listing_min_rate', true ),0) . ' to $' . number_format(get_post_meta( $post->ID, '_listing_max_rate', true ),0) .'</td></tr>';
                        echo '<div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">';
                        //echo '<tr class="wp_listings_listing_address"><td class="label">Address</td><td itemprop="streetAddress">'.get_post_meta( $post->ID, '_listing_address', true) .'</td></tr>';
                        echo '<tr class="wp_listings_listing_city"><td class="label">City</td><td itemprop="addressLocality">'.get_post_meta( $post->ID, '_listing_city', true) .'</td></tr>';
                        echo '<tr class="wp_listings_listing_state"><td class="label">State</td><td itemprop="addressRegion">'.get_post_meta( $post->ID, '_listing_state', true) .'</td></tr>';
                        //echo '<tr class="wp_listings_listing_zip"><td class="label">Zip</td><td itemprop="postalCode">'.get_post_meta( $post->ID, '_listing_zip', true) .'</td></tr>';
                        echo '</div>';
                        echo '<tr class="wp_listings_listing_mls"><td class="label">Max Occupancy</td><td>'.get_post_meta( $post->ID, '_listing_occupancy', true) .'</td></tr>';
                        echo '</tbody>';

                        echo '<tbody class="right">';
                        foreach ( (array) $details_instance->property_details['col2'] as $label => $key ) {
                            $detail_value = esc_html( get_post_meta($post->ID, $key, true) );
                            if (! empty( $detail_value ) ) :
                                if($label == 'Youtube ID:'){
                                    continue;
                                }
                                printf( $pattern, $key, esc_html( str_replace(":", "", $label)  ), $detail_value );
                            endif;
                        }
                        echo '</tbody>';

                        echo '</table>';

                        ?>
                    </div><!-- #listing-details -->

                    <?php if(count($amenitiesArray)): ?>
                        <div id="listing-amenities">

                            <?php foreach($amenityTypes as $type=>$types){
                                echo "<h4>$type</h4> <ul>";
                                foreach($types as $amenity){
                                    echo '<li style="float:left;padding-right: 15px;"><i class="dashicons dashicons-yes"></i> '.$amenity.'</li>';
                                }
                                echo '</ul><hr style="clear: both;">';
                            } ?>

                        </div>
                    <?php endif; ?>

                    <?php if (get_post_meta( $post->ID, '_listing_gallery', true) != '') { ?>
                        <div id="listing-gallery">
                            <?php echo do_shortcode(get_post_meta( $post->ID, '_listing_gallery', true)); ?>
                        </div><!-- #listing-gallery -->
                    <?php } ?>

                    <?php if (get_post_meta( $post->ID, '_listing_youtube_id', true) != '') { ?>
                        <div id="listing-video">

                            <iframe class="listing-youtube-video" width="100%" height="400" src="https://www.youtube.com/embed/<?=get_post_meta( $post->ID, '_listing_youtube_id', true);?>?rel=0" frameborder="0" allowfullscreen></iframe>

                        </div><!-- #listing-video -->
                    <?php } ?>

                    <?php if (get_post_meta( $post->ID, '_listing_school_neighborhood', true) != '') { ?>
                        <div id="listing-school-neighborhood">
                            <p>
                                <?php echo do_shortcode(get_post_meta( $post->ID, '_listing_school_neighborhood', true)); ?>
                            </p>
                        </div><!-- #listing-school-neighborhood -->
                    <?php } ?>

                </div><!-- #listing-tabs.listing-data -->

                <?php
                if (get_post_meta( $post->ID, '_listing_map', true) != '') {
                    echo '<div id="listing-map"><h3>Location Map</h3>';
                    echo do_shortcode(get_post_meta( $post->ID, '_listing_map', true) );
                    echo '</div><!-- .listing-map -->';
                }
                ?>

                <?php
                if (function_exists('_p2p_init') && function_exists('agent_profiles_init') ) {
                    echo'<div id="listing-agent">
    				<div class="connected-agents">';
                    aeprofiles_connected_agents_markup();
                    echo '</div></div><!-- .listing-agent -->';
                }
                ?>

            </div><!-- .entry-content -->
        </div><!-- .listing-wrapper -->
        <?php
    }
}

if (function_exists('equity')) {

	remove_action( 'equity_entry_header', 'equity_post_info', 12 );
	remove_action( 'equity_entry_footer', 'equity_post_meta' );

	remove_action( 'equity_entry_content', 'equity_do_post_content' );
	add_action( 'equity_entry_content', 'single_listing_post_content' );

	equity();

} elseif (function_exists('genesis_init')) {

	remove_action( 'genesis_before_loop', 'genesis_do_breadcrumbs' );
	remove_action( 'genesis_entry_header', 'genesis_post_info', 12 ); // HTML5
	remove_action( 'genesis_before_post_content', 'genesis_post_info' ); // XHTML
	remove_action( 'genesis_entry_footer', 'genesis_post_meta' ); // HTML5
	remove_action( 'genesis_after_post_content', 'genesis_post_meta' ); // XHTML
	remove_action( 'genesis_after_entry', 'genesis_do_author_box_single', 8 ); // HTML5
	remove_action( 'genesis_after_post', 'genesis_do_author_box_single' ); // XHTML

	remove_action( 'genesis_entry_content', 'genesis_do_post_content' ); // HTML5
	remove_action( 'genesis_post_content', 'genesis_do_post_content' ); // XHTML
	add_action( 'genesis_entry_content', 'single_listing_post_content' ); // HTML5
	add_action( 'genesis_post_content', 'single_listing_post_content' ); // XHTML

	genesis();

} else {

get_header();

    include(track_connect_view_override('single-listing', 'body.php'));

get_footer();
}
?>