<?php


class demo_oauth
{
    
    private $client;

    private $service;

    private $results;




    public function run()
    {
        $this->get_tokens();
        $this->create_client();

        /**
         * Already have a refresh_token, so use that.
         */
        
        if ( false !== ( $this->refresh_token ) ) {
            $this->use_refresh_token();
            $this->run_youtube_request();
            return;
        }

        if ( false === ( $this->auth_token ) ) { return; }

        /**
         * have an OAUTH_CODE but no OAUTH_REFRESH_TOKEN.
         */
        $this->get_auth_token();
        $this->run_youtube_request();
        
    }



    /**
     * get_tokens
     * 
     * This will get any tokens stored as transients.
     *
     * @return void
     */
    private function get_tokens()
    {
        $this->auth_token = get_transient( 'YT_OAUTH_CODE' );
        $this->refresh_token = get_transient( 'YT_OAUTH_REFRESH_TOKEN' );
    }


    /**
     * create_client
     *
     * Uses the google client library.
     * 
     * @return void
     */
    private function create_client()
    {
        $this->client = new Google_Client();
        
        $this->client->setAuthConfigFile(DEMO_APPLICATION_CREDENTIALS);

        $this->client->addScope(Google_Service_YouTube::YOUTUBE_FORCE_SSL);

        $this->client->setPrompt('consent');  // Needed to get refresh_token every time.

        $this->client->setAccessType('offline');
    }




    /**
     * get_auth_token 
     * 
     * Not autenticated yet, so do so and set the refresh token.
     * 
     * Refresh token set for 1 year.
     * 
     * @return void
     */
    public function get_auth_token()
    {
        
        $this->client->authenticate($this->auth_token);

        $this->refresh_token = $this->client->getRefreshToken();

        set_transient( 'YT_OAUTH_REFRESH_TOKEN', $this->refresh_token, WEEK_IN_SECONDS );

    }



    /**
     * use_refresh_token 
     * 
     * Already authenticated and have a refresh token.
     *
     * @return void
     */
    public function use_refresh_token()
    {
        $refresh_token = get_transient( 'YT_OAUTH_REFRESH_TOKEN' );

        $this->client->refreshToken($refresh_token);

    }



    /**
     * Get a new YouTube Object.
     * 
     * Services are called through queries to service specific objects. 
     * These are created by constructing the service object, and passing an 
     * instance of Google_Client to it. Google_Client contains the IO, authentication 
     * and other classes required by the service to function, and the service informs 
     * the client which scopes it uses to provide a default when authenticating a user.
     */
    public function run_youtube_request()
    {
        
        $this->service = new \Google_Service_YouTube($this->client);

        /**
         * Each API provides resources and methods, usually in a chain. These can be 
         * accessed from the service object in the form $service->resource->method(args). 
         * Most method require some arguments, then accept a final parameter of an array 
         * containing optional parameters.
         */
        $queryParams = [
            'mine' => true
        ];

        $this->results = $this->service->channels->listChannels( 'snippet,contentDetails,statistics', $queryParams );
    }


}
