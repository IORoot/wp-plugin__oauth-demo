<?php
/*
 * 
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Demo of Google OAUTH
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>🔌PLUGIN</strong> | Demo using google's API client https://github.com/googleapis/google-api-php-client
 * Version:           1.0.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 * Domain Path:       /languages
 */

define('DEMO_APPLICATION_CREDENTIALS', __DIR__.'/client_secret.json');


// ┌─────────────────────────────────────────────────────────────────────────┐
// │                         Use composer autoloader                         │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/vendor/autoload.php';

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                    Create the ANDYP_OAUTH shortcode                     │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/src/shortcode/button.php'; 

// ┌─────────────────────────────────────────────────────────────────────────┐
// │            Load the Javascript into the Admin page footer.              │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/src/js/enqueue_js.php';

// ┌─────────────────────────────────────────────────────────────────────────┐
// │          Create the AJAX callback action for the redirectURI            │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/src/callback/ajax_callback.php';

// ┌─────────────────────────────────────────────────────────────────────────┐
// │         Decode the response 'state' parameter into 'action'             │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/src/callback/decode_state.php';
