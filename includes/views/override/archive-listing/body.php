<?php if ($options['wp_listings_force_sidebar'] == 1): ?>
            <div class="col-sm-12 col-md-3 col-xs-12">
                <?php if (!function_exists('dynamic_sidebar') || !dynamic_sidebar('track_connect')) :
                endif; ?>
            </div>
            <div class="col-sm-12 col-md-9 col-xs-12">
                <?php else: ?>
                <div>
                    <?php endif; ?>
                    <?php

                    archive_listing_loop();

                    ?>
                </div>