(function($){

    /**
     * The ajax_object.auth_url object is passed in from the 
     * wp_localize_script function in enqueue_js.php file. 
     */
    $('#andyp__youtube-oauth--button').on( 'click', function(){
        var win = window.open( ajax_object.auth_url, "_blank", "width=600,height=600" );
    });

})(jQuery);