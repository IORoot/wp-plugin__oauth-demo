<?php

/**
 * NOTE - Make sure that :
 * 
 * 1. You have downloaded the crendials json file from the google api console.
 * 2. Saved it in the root of the project called client_secret.json
 * 3. The .gitignore is listing that file (so you don't add it to git!)
 * 4. define('DEMO_APPLICATION_CREDENTIALS', __DIR__.'/client_secret.json');
 * 
 * This action will add the youtube_oauth.js file into the footer of the admin
 * page.
 * 
 * It requires The google client library from https://github.com/googleapis/google-api-php-client.
 */
// add_action( 'admin_enqueue_scripts', 'enqueue_demo_oauth' );
add_action( 'wp_enqueue_scripts', 'enqueue_demo_oauth' );



function enqueue_demo_oauth() {
    

    /**
     * Add jQuery and JS script to footer.
     */
	wp_enqueue_script('demo-oauth-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js', array(), null );
    wp_enqueue_script('demo-oauth-script', plugins_url( 'demo_oauth.js', __FILE__ ), array( 'demo-oauth-jquery' ), null  );
    

    /**
     * Generate AUTH URL with Google Client Library.
     */
    $client = new Google_Client();
    $client->setAuthConfig(DEMO_APPLICATION_CREDENTIALS);
    $client->addScope(Google_Service_YouTube::YOUTUBE_FORCE_SSL);
    $client->setPrompt('consent');  // Needed to get refresh_token every time.
    $client->setAccessType('offline');
    


    /**
     * The "action" parameter tells the admin-ajax.php system which 
     * Action to run.
     * In this case, the action is "demo_oauth" which is defined
     * as an AJAX endpoint in the /actions/oauth_callback.php file.
     */
    $demo_state_args = array(
        'action' 		=> 'demo_oauth_callback'
    );

    $state = base64_encode( json_encode( $demo_state_args ) );

    $client->setState($state);


    /**
     * Create the Authentication URL based off the state and client
     * details.
     */
    $auth_url = $client->createAuthUrl();



    /**
     * Make these values accessible in the Javascript file.
     * 
     * In JavaScript, these object properties are accessed as 
     * ajax_object.ajax_url
     * ajax_object.auth_url
     */
    wp_localize_script( 'demo-oauth-script', 'ajax_object', 
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ), 
            'auth_url' => $auth_url
        ] 
    );




}