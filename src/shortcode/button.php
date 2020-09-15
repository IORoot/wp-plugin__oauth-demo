<?php

/**
 * Create the button.
 */
function andyp_oauth_button_shortcode_callback($atts){

    $output =  '<button type="button" class="button-secondary" id="andyp__youtube-oauth--button">YouTube OAUTH</button></br></br>';


    $youtube = new demo_youtube();
    $youtube->run();
    $result = json_encode($youtube->get_results());
    $output .= "<H3>JSON RESULT</H3>";
    $output .= $result;

    return $output;
}



add_shortcode( 'andyp_oauth', 'andyp_oauth_button_shortcode_callback' );