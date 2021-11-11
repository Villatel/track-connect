<?php add_thickbox();
wp_enqueue_script( 'jquery' );
wp_enqueue_script ('jquery-ui-core');
wp_enqueue_script( 'jquery-ui-progressbar');
wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
global $wpdb;
?>
<div id="icon-options-general" class="icon32"></div>
<div class="wrap">
	<h2>TRACK Connect Settings</h2>
	<hr>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div id="side-info-column" class="inner-sidebar">
		<?php do_meta_boxes('wp-listings-options', 'side', null); ?>
		</div>               
        
        <div id="post-body">
            <div id="post-body-content" class="has-sidebar-content">
                
										
            	<?php $options = get_option('plugin_wp_listings_settings');

            	if ( !isset($options['wp_listings_stylesheet_load']) ) {
					$options['wp_listings_stylesheet_load'] = 0;
				}
				if ( !isset($options['wp_listings_widgets_stylesheet_load']) ) {
					$options['wp_listings_widgets_stylesheet_load'] = 0;
				}
				if ( !isset($options['wp_listings_default_state']) ) {
					$options['wp_listings_default_state'] = '';
				}
				if ( !isset($options['wp_listings_archive_posts_num']) ) {
					$options['wp_listings_archive_posts_num'] = 9;
				}
				if ( !isset($options['wp_listings_slug']) ) {
					$options['wp_listings_slug'] = 'listings';
				}
				if ( !isset($options['wp_listings_domain']) ) {
					$options['wp_listings_domain'] = '';
				}

            	?>
            	
                
            	<h2>Include CSS?</h2>
				<p>Here you can deregister the WP Listings CSS files and move to your theme's css file for ease of customization</p>
				<?php
				if ($options['wp_listings_stylesheet_load'] == 1)
					echo '<p style="color:red; font-weight: bold;">The plugin\'s main stylesheet (wp-listings.css) has been deregistered<p>';
				if ($options['wp_listings_widgets_stylesheet_load'] == 1)
					echo '<p style="color:red; font-weight: bold;">The plugin\'s widget stylesheet (wp-listings-widgets.css) has been deregistered<p>';
				?>
				<form action="options.php" method="post" id="wp-listings-stylesheet-options-form">
					<?php settings_fields('wp_listings_options'); ?>
										                
					<?php echo '<h4><input name="plugin_wp_listings_settings[wp_listings_stylesheet_load]" id="wp_listings_stylesheet_load" type="checkbox" value="1" class="code" ' . checked(1, $options['wp_listings_stylesheet_load'], false ) . ' /> Deregister WP Listings main CSS (wp-listings.css)?</h4>'; ?>

					<?php echo '<h4><input name="plugin_wp_listings_settings[wp_listings_widgets_stylesheet_load]" id="wp_listings_widgets_stylesheet_load" type="checkbox" value="1" class="code" ' . checked(1, $options['wp_listings_widgets_stylesheet_load'], false ) . ' /> Deregister WP Listings widgets CSS (wp-listings-widgets.css)?</h4><hr>'; ?>
                    
                    <h2>Track PM Settings</h2>
                    <?php
    					_e("<p>Domain code used in Track</p>", 'wp_listings' );
    				    echo '<h4>Domain: <input name="plugin_wp_listings_settings[wp_listings_domain]" id="wp_listings_domain" type="text" value="' . $options['wp_listings_domain'] . '" size="15" /></h4>';
                    ?>
                    <?php
    					_e("<p>API token</p>", 'wp_listings' );
    				    echo '<h4>Token: <input name="plugin_wp_listings_settings[wp_listings_token]" id="wp_listings_domain" type="text" value="' . $options['wp_listings_token'] . '" size="15" /></h4>';
                    ?>
                    <?php
                    _e("<p>API Secret</p>", 'wp_listings' );
                    echo '<h4>Secret: <input name="plugin_wp_listings_settings[wp_listings_secret]" id="wp_listings_domain" type="text" value="' . $options['wp_listings_secret'] . '" size="15" /></h4>';
                    ?>

                    <?php
    					_e("<p>Complex Node (for grouping listings)</p>", 'wp_listings' );
    					$nodeTypes = $wpdb->get_results("SELECT type_id as id, name FROM ".$wpdb->prefix."track_node_types;");
    				?> 
    				<h4>Node: 
        				<select name="plugin_wp_listings_settings[wp_listings_complex_node]" id="wp_listings_complex_node">
            				<option value="0">None</option>
        				<?php foreach($nodeTypes as $type): ?>
        				     <option <?=($options['wp_listings_complex_node'] == $type->id)?"SELECTED":""; ?> value="<?=$type->id?>"><?=$type->name?></option>
        				<?php endforeach; ?>
                        </select>
        				
    				</h4>
                    
                    <?php echo '<h4><input name="plugin_wp_listings_settings[wp_listings_force_sidebar]" id="wp_listings_force_sidebar" type="checkbox" value="1" class="code" ' . checked(1, $options['wp_listings_force_sidebar'], false ) . ' /> Override sidebar with custom Track Connect widget area?</h4>'; ?> 
                    
					<?php
					_e("<h2>Default State</h2><p>You can enter a default state that will automatically be output on template pages and widgets that show the state. When you are create a listing and leave the state field empty, the default entered below will be shown. You can override the default on each listing by entering a value into the state field.</p>", 'wp_listings' );
				    echo '<h4>Default State: <input name="plugin_wp_listings_settings[wp_listings_default_state]" id="wp_listings_default_state" type="text" value="' . $options['wp_listings_default_state'] . '" size="1" /></h4><hr>';
					?>

					<?php
					_e("<h2>Default Number of Posts</h2><p>The default number of posts displayed on a listing archive page is 9. Here you can set a custom number. Enter <span style='color: #f00;font-weight: 700;'>-1</span> to display all listing posts.<br /><em>If you have more than 20-30 posts, it's not recommended to show all or your page will load slow.</em></p>", 'wp_listings' );
				    echo '<h4>Number of posts on listing archive page: <input name="plugin_wp_listings_settings[wp_listings_archive_posts_num]" id="wp_listings_archive_posts_num" type="text" value="' . $options['wp_listings_archive_posts_num'] . '" size="1" /></h4>';
					?>
					<br />

					<?php echo '<h4>Listings post type slug (leave as default or change as needed): <input type="text" name="plugin_wp_listings_settings[wp_listings_slug]" value="' . $options['wp_listings_slug'] . '" /></h4>'; ?>
					<p>Don't forget to <a href="../wp-admin/options-permalink.php">reset your permalinks</a> if you change the slug.</p>
					<input name="submit" class="button-primary" type="submit" value="<?php esc_attr_e('Save Settings'); ?>" />
				</form>
				
				
				<h2>Track PM Sync</h2>
				<?php if($options['wp_listings_domain'] != '' && $options['wp_listings_token'] != ''): ?>
                    <a href="#TB_inline?width=400&height=350&inlineId=unit-sync" id="sync-btn" class="thickbox">Sync Units</a>
                    <?php if($options['wp_listings_complex_node'] > 0): ?>
                        <br><br>
                        <a href="#TB_inline?width=400&height=350&inlineId=complex-sync" id="complex-btn" class="thickbox">Sync Complexes</a>
                    <?php endif; ?>
                <?php else: ?>
                    <h4>A domain and token is required to be saved above to sync units.</h4>
                <?php endif; ?>
                
				
				
            </div>    
        </div>
    </div>
</div>

<style>
#progressbar{
	margin-bottom:20px;
}
#progressyears {
	float:left;	
	font-weight:bold;
	font-size:14px;
	padding-top:5px;
	width:20%;
}
.ui-progressbar {
	position: relative;
}
.progress-label {
	color:#000;
	font-weight: bold;
	left: 50%;
	position: absolute;
	top: 4px;
}
</style>

<div id="unit-sync" style="display:none;">
    <div>
        <div id="sync-msg" style="overflow: auto; height: 250px; padding-bottom: 15px;"></div>
        <div id="progressbar" name="progressbar"></div>
    </div>
</div>

<div id="complex-sync" style="display:none;">
    <div>
        <div id="complex-msg" style="overflow: auto; height: 250px; padding-bottom: 15px;"></div>
    </div>
</div>

<input type="hidden" id="total-units" value="0">  
<script>
$j = jQuery.noConflict();
var x = 0;
$j('#sync-btn').click(function () {
    $j('#sync-msg').html("<b>Syncing now, do not close this window until the operation is complete!</b><br>");
    $j.ajax('/wp-admin/admin-ajax.php', {
        type: "POST",
        dataType: 'json',
        data: {
            action: 'get_unit_count'
        },
        success: function (d) {
            x = 0;
            var units = d.response;
            var num = (units / 50) + 1;
            var pages = [];
            for(i = 1;i <= num;i++){
                pages.push(i);
            }
            $j('#sync-msg').append(units+" units have been found.");
            $j('#total-units').val(units);
            loopArray(pages);             
        },
        error: function (d) {
             x = 0;
            var units = d.response;
            var num = (units / 50) + 1;
            var pages = [];
            for(i = 1;i <= num;i++){
                pages.push(i);
            }
            $j('#sync-msg').append(units+" units have been found.");
            $j('#total-units').val(units);
            loopArray(pages); 
        }
        
    });  
});

var loopArray = function(pages) {
    customAlert(pages[x],function(){
        // set x to next item
        x++;

        // any more items in array? continue loop
        if(x < pages.length) {
            loopArray(pages); 
        }else{
            finalizeSync();        
        }
    }); 
}

function customAlert(page,callback) {
    var units = $j('#total-units').val();
    var size = 50;
    $j.ajax('/wp-admin/admin-ajax.php', {
        type: "POST",
        dataType: 'json',
        data: {
            action: 'sync_units',
            page: page,
            size: size
        },
        success: function (d) {
            var bar = ((d.updated * page) / units) * 100;
            if(d.updated < size){
                bar = 100;
            }
            $j('#progressbar').progressbar({
                value: bar
            });
            
            //$j('#sync-msg').append('<br>'+d.updated+' units updated.');
            callback();
        },
        error: function (d) {
            var bar = ((d.updated * page) / units) * 100;
            if(d.updated < size){
                bar = 100;
            }
            $j('#progressbar').progressbar({
                value: bar
            });
            
            //$j('#sync-msg').append('<br>'+d.updated+' units updated.');
            callback();
        }
    });
}

function finalizeSync() {
    $j.ajax('/wp-admin/admin-ajax.php', {
        type: "POST",
        dataType: 'json',
        data: {
            action: 'sync_other'
        },
        success: function (d) {
             $j('#sync-msg').append('<br><br><b>All units and locations have been synced. You can now close this window. </b>');
        },
        error: function (d) {
             $j('#sync-msg').append('<br><br><b>All units and locations have been synced. You can now close this window. </b>');
        },
    });  
}

$j(function() {
    $j('#progressbar' ).progressbar({
        value: 0
    });
});

$j('#complex-btn').click(function () {
    $j('#complex-msg').html("<b>Syncing now, do not close this window until the operation is complete!</b><br>");
    $j.ajax('/wp-admin/admin-ajax.php', {
        type: "POST",
        dataType: 'json',
        data: {
            action: 'sync_complexes'
        },
        success: function (d) {
            //$j('#complex-msg').append('Units created: ' +d.created + '. Units updated: ' + d.updated);
            $j('#complex-msg').append("Sync complete, you can now close this window.");
             
        }
    });  
});
</script>