<?php
// Start the Loop.
while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title" itemprop="name">', '</h1>' ); ?>
            <small><?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?></small>
            <div class="entry-meta">
                <?php
                if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) :
                    ?>
                    <span class="comments-link"><?php comments_popup_link( __( 'Leave a comment', 'wp_listings' ), __( '1 Comment', 'wp_listings' ), __( '% Comments', 'wp_listings' ) ); ?></span>
                    <?php
                endif;

                edit_post_link( __( 'Edit', 'wp_listings' ), '<span class="edit-link">', '</span>' );
                ?>
            </div><!-- .entry-meta -->
        </header><!-- .entry-header -->


        <?php if($options['wp_listings_force_sidebar'] == 1): ?>
        <div class="col-sm-12 col-md-3 col-xs-12">
            <?php if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar('track_connect') ) :
            endif; ?>
        </div>
        <div class="col-sm-12 col-md-9 col-xs-12">
            <?php else: ?>
            <div>
                <?php endif; ?>

                <?php single_listing_post_content(); ?>



    </article><!-- #post-ID -->

    <?php
    // Previous/next post navigation.
    wp_listings_post_nav();

    // If comments are open or we have at least one comment, load up the comment template.
    if ( comments_open() || get_comments_number() ) {
        comments_template();
    }
endwhile;
?>




