jQuery(document).ready(function(){      
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
});