<?php

function demo_oauth_callback() {

    // set_transient( 'DEMO_OAUTH_CODE', $_REQUEST['code'], 300 );

    
    echo "DEMO_CODE = ".$_REQUEST['code']."<br/>";
    echo "Transient set for 5 minutes.<br/>";
    echo "DEMO_REFRESH_CODE Transient will be set for one hour.<br/>";
    echo "Please now close this window.";

    wp_die(); // this is required to return a proper response
    
}

add_action( 'wp_ajax_nopriv_demo_oauth_callback', 'demo_oauth_callback', 8);
add_action( 'wp_ajax_demo_oauth_callback', 'demo_oauth_callback', 8);