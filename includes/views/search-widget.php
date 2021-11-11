<?= $before_widget ?>
	<div id="search-widget">
		<?php if ( $instance['title'] ) {
			echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance,
					$this->id_base ) . $after_title;
		} ?>

        <?php include(track_connect_view_override('search-widget', 'form.php')); ?>

    </div>
<?= $after_widget; ?>