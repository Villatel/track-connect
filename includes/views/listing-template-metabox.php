<input type="hidden" name="wplistings_single_noncename" id="wplistings_single_noncename" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />

<label class="hidden" for="listing_template"><?php  _e( 'Listing Template', 'wp_listings' ); ?></label><br />
<select name="_wp_post_template" id="listing_template" class="dropdown">
    <option value=""><?php _e( 'Default', 'wp_listings' ); ?></option>
    <?php $this->listing_templates_dropdown(); ?>
</select><br /><br />
<p><?php _e( 'You can use custom templates for single listings that might have additional features or custom layouts by adding them to your theme directory. If so, you will see them above.', 'wp_listings' ); ?></p>
