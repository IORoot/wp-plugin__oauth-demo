<?php

/**
 * Create the button.
 */
function andyp_oauth_button_shortcode_callback($atts){
    return '<button type="button" class="button-secondary" id="andyp__youtube-oauth--button">YouTube OAUTH</button>';
}



add_shortcode( 'andyp_oauth', 'andyp_oauth_button_shortcode_callback' );