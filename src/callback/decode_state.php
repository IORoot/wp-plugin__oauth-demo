<?php

/**
 * Does a base64_decode on the $_REQUEST['state'] data.
 */
add_action( 'init',	'_decode_repsonse_state', 9 );

/*
* Decodes Data from $state query arg and adds them to $_REQUEST. This must happen
* before admin-ajax.php checks for the 'action' value
*
*/

function _decode_repsonse_state(){

    if( !is_admin()  || !defined('DOING_AJAX') || !is_user_logged_in())
        return;

    if( !isset( $_REQUEST['state'] ) || !is_string( $_REQUEST['state'] ) )
        return;

    $data = base64_decode( $_REQUEST['state'] );

    if( false === $data )
        return;

    $data = json_decode( $data, true );

    if( !is_array( $data ) )
        return;

    $_REQUEST = array_merge( $_REQUEST, $data );

}