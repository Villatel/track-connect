<header class="archive-header">
    <?php
    $object = get_queried_object();

    $title = '<h1 class="archive-title">Unit Search</h1>';
    echo $title; ?>

    <small><?php if ( function_exists('yoast_breadcrumb') ) { yoast_breadcrumb('<p id="breadcrumbs">','</p>'); } ?></small>
</header><!-- .archive-header -->