# Wordpress & Google OAuth

This plugin will create a simple shortcode for a button that will open an OAUTH window to request permissions to use YouTube of the user. It utilises the Google API client library and services composer packages.

This demo is about a simple as I could make it. However, it's still a little convoluted in my opinion. I'm sure there are better ways of doing it.

I've taken lots of concepts and ideas from https://github.com/ohfuchs/acf-oauth so if you want a full ACF Oauth package, then this is a great one to use.

However, in my use-case, I wanted to use the google api client library https://github.com/googleapis/google-api-php-client and it's services. Therefore I had to work out the steps of going about doing all this myself. 



## Installation

You will need to do the following steps:

1.  Clone the repo into your wp-plugins directory.
2.  Activate the plugin.
3.  Create a new google API project with OAuth 2.0 credentials in the google API Console. https://console.developers.google.com/
4.  Add the YouTube Data API v3 API into the project.
5.  You may have to set up consent pages and usage agreements.
6.  You must add https://MYDOMAIN.com/wp-admin/admin-ajax.php as an Authorized redirect URI.
7.  Download the JSON credentials into the root of the plugin folder and call the file `client_secret.json`
8.  Run a `composer install` in the plugin folder to install all dependencies (google-api-php-client and google-api-php-client-services). 
9.  Run a `composer dumpautoload` to autoload all of the classes.
10.  Use the shortcode `[andyp_oauth]` on a page to render the OAUTH button.



## How it works

The OAuth workflow seems to be quite a tricky and complex workflow to follow, but once you break it down into it's component parts, it's much more manageable to understand. Here are the parts:



### Step 1 - Creating an Application

>   Telling google you have an application and you want to give it permissions to use a specific API

You can use the Google API Console (https://console.developers.google.com/) service to create a new project that tells google you are creating a new web application. Once you go to the website you'll want to do the following things:

1.  **New Project**. A project is all the settings for this application you are creating. You can create multiple projects for different purposes. Each project has quotas on how much it can use each API. Give the project a name and create it.

2.  **Consent Screen.** Here you can select the different parts of the project that the user will see when authorising your application.

    -   Application type = public
    -   Application Name = Name of app.
    -   Support Email = Your email.
    -   Scopes = email, profile, openid.
    -   Authorized domains = yourdomain.com
    -   Homepage link = https://yourdomain.com
    -   Privacy Policy = https://yourdomain.com/privacy-policy-page
    -   Terms of Service = https://yourdomain.com/tos
    -   Save.

3.  **Library**. Under the library sidemenu option you can select the specific APIs you want to use. In this demo I'm using the`YouTube Data API v3` Select that and enable it.

4.  **Credentials**. Now you have to setup a way to use this new project. There are three methods available, each with a different use-case. API Keys, OAuth 2.0 Client IDs, and Service Accounts. Click the button at the top of the page **+ CREATE CREDENTIALS** and select "OAuth client ID".

5.  **Application Type.** This is a "web application". This dictates the way the OAuth process works.

6.  **Name**. Give your OAuth client ID an appropriate name.

7.  **Authoized redirect URIs**. These MUST be exactly right. slashes on the end make a difference, as well as the protocol of http or https.

    -   https://yourdomain.com/wp-admin/admin-ajax.php
    -   https://yourdomain.com
    -   https://yourdomain.com/

8.  **Download JSON**. Once all of the details are filled in, download the JSON file with all of the credentials to the root of the demo plugin folder and call the file `client_secret.json`. It must be called this because a constant called `DEMO_APPLICATION_CREDENTIALS` looks for this file.

    

Now you have setup a way to communicate with google. They now know you have an application that needs access to different people's YouTube accounts depending on who authorises it. 

They also know a user will be using the OAuth 2.0 workflow to tell them to allow your application to have permission to use their YouTube account.

Lastly, they know that once the user has completed granting access to their account for your application, google will redirect them back to `https://yourdomain.com/wp-admin/admin-ajax.php` 





### Step 2 - Composer 

Composer is a package manager that automatically installs any php packages you want. In our case, we want to install the Google API client and the YouTube service that comes with it. The installation method is described on their github page here: https://github.com/googleapis/google-api-php-client

My `composer.json` file tells composer what to install. So, by running `composer install` you'll install everything you need.





### Step 3 - Shortcodes

>   `/src/shortcode/button.php`

This uses the wordpress `add_shortcode` function to declare the word `andyp_oauth` as the name of the shortcode and a function to run.

This function then does two things:

1.  It renders a `<button>` with a specific ID that will be picked up by our javascript later.

    `id="andyp__youtube-oauth--button"`

2.  It runs our `demo_youtube` class and returns any result in JSON. On initial installation, this will be nothing because we haven't authenticated yet.

Add the shortcode `[andyp_oauth]` onto any page and it'll render the button and any result as a JSON object.



### Step 4 - Enqueue Javascript

>    `/src/js/enqueue_js.php`

This is where we start getting into the nitty-gritty. Before we start, you'll notice there are comments all over and some code commented out, this is because this demo app is meant for the frontend. However, the commented out bits allow you to use the code in the backend too. For instance, the `add_action` at the top of the page has a commented out second declaration for `admin_enqueue_scripts` for backend usage too.

This file will run the `wp_enqueue_scripts` wordpress action on the function we define in this file. This then does the following things:

1.  Adds jQuery and our custom `demo_oauth.js` script.

2.  Create a new Google_Client object from the google-api-client-php package.

    ```php
    /*     
    * Generate AUTH URL with Google Client Library.
    */
    $client = new Google_Client();
    $client->setAuthConfig(DEMO_APPLICATION_CREDENTIALS);
    $client->addScope( Google_Service_YouTube::YOUTUBE_FORCE_SSL );
    $client->setPrompt('consent');
    $client->setAccessType('offline');
    ```

    This will build up a new Google Client object.

    set the Authentication config (using our client_secrets.json file - assigned to that constant).

    Add a [YouTube Scopes](https://developers.google.com/youtube/v3/guides/auth/installed-apps#identify-access-scopes) . Think of a scope as a specific permission level - YOUTUBE_FORCE_SSL is a full access permission level.

    Finally, Set a consent screen to show and that we want offline access.

    
    
3.  The next part is to utilise the `state` parameter that we can send to the google OAuth server, but it's essentially not used by them, it's for us on the return back once the user has been authentiated. 

    What we're going to do is use wordpress's AJAX functionality to read any returned values and do something with them. The way we have to set this functionality up is by redirecting back to the `admin-ajax.php` file (remember we specified that in the Google console as the return URI).

    However, this file expects at least one parameter called 'action' to indicate which function you want it to run.

    The head-scratcher problem is that the google API does not have an 'action' parameter and won't allow any extra ones to be added. This is where the `state` parameter comes in. We're going to send a json_encoded array of `'action' = 'demo_oauth_callback'` within the state parameter and setup (later - see below) a catcher to json_decode the state parameter and append it's contents (this key-value pair) as an extra parameter BEFORE it gets sent to the `admin-ajax.php` file. Cool, huh?

    Alright, well, to setup this `state` parameter, we do this:

    ```php
    /**
    * The "action" parameter tells the admin-ajax.php system 
    * which Action to run.
    * In this case, the action is "demo_oauth" which is 
    * definedas an AJAX endpoint in the 
    * /actions/oauth_callback.php file.
    */
    $demo_state_args = array(
    	'action' => 'demo_oauth_callback'
    );
    
    $state = base64_encode( json_encode( $demo_state_args ) );
    
    $client->setState($state);
    ```

    

4.  The google client library allows us to generate an authentication URL based off all the settings we specified above. To do this is a one-liner:

    ```php
    $auth_url = $client->createAuthUrl();
    ```

    This will return with a long URL that we can visit to open up the start of the OAuth process. However, we want to send it to our Javascript to open up a new tab window instead.

    

5.  The last part is the take the generated Authentication URL and make it available to our Javascript on the front-end. To do this, we can utilise the wordpress `wp_localize_script` function to send any values to the frontend. We want two values: 

    -   The admin-ajax.php file url.
    -   The authentication url we just generated.

    The `wp_localize_script` needs to know which javascript file to tie the values to and the name of the data object to nest these values under.

    ```php
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
    ```

    This then will link to the `demo-oauth-script` which we enqueued at the top of the function. And the object with all the data is called `ajax_object`.



Ok, so we've now loaded our javascript into our footer of the page, our authentication URL has been generated and we've made that available to the javascript.



### Step 5 - Javascript

>   `/src/js/demo_oauth.js`

When you open this file up you can see it's pretty damn basic. 

```javascript
(function($){
/**
* The ajax_object.auth_url object is passed in from the 
* wp_localize_script function in enqueue_js.php file. 
*/
$('#andyp__youtube-oauth--button').on( 'click', function(){
		var win = window.open( 
    		ajax_object.auth_url, 
    		"_blank", 
    		"width=600,height=600" 
  	);
});

})(jQuery);
```



All this does is the following:

1.  Make the `jQuery` available as `$`
2.  Search for our button ID `'#andyp__youtube-oauth--button'`
3.  Attach a `click` event onto it which will run a function.
4.  The function will create a new blank window that point to the authentication URL we created in the enqueuing process above.
5.  Success!



### Step 6 - OAuth

At this point, the user will click the button and open the new window with the authentication URL as the target. They will be presented with the OAuth steps  from Google to select a user / account from YouTube and to allow access to the project.

Note - You WILL get a warning saying "**This App isn't Verified**". You'll need to click on the 'Advanced' link and then the "Go to yourdomain.com (unsafe)" to proceed.

This will disappear once your app has gone through the google verification process. However, for this demo purposes, there's no need.



### Step 7 - The Callback

>   `/src/callback/ajax_callback.php`

Once the user has gone through the entire process of the OAuth steps, the google OAuth server will redirect the user to our redirect URI of `https://yourdomain.com/wp-admin/admin-ajax.php` with all of the bits we need to run an authenticated API call.

So now we need to setup that AJAX function to listen for that returning response from google. To do that we use the wordpress `wp_ajax_` actions.

Wordpress allows us to set this up by specifying what 'action' parameter to listen for (remember we put the `action = demo_oauth_callback` into the `state` of the request - see Step 4 above ) and also what function to run when it sees that specific 'action' parameter.

So we need to set up an AJAX listener for the action `demo_oauth_callback` and then run a function when it sees it. 

```php
add_action( 'wp_ajax_demo_oauth_callback', 'demo_oauth_callback', 8);
```

This line will do exactly that. 

Note that if you want to specifically setup and AJAX listener, or endpoint, or whatever you want to call it, you have to prefix the word with `wp_ajax_` on the action. 

Now, when it sees an `action=demo_oauth_callback` in a request to the page `admin-ajax.php`, the function (called the same thing - standard practice in wordpress) `'demo_oauth_callback'` will run.

You'll notice that there is a very similar action called `wp_ajax_nopriv_demo_oauth_callback` too. This is for frontend no-logged-in users to use the button too. If you're only using this on the backend, no need for that action.

The function that runs, `demo_oauth_callback` really only does two things:

1.  Sets a transient of 300 seconds (this is a database entry with a timelimit on when it's removed). 

    ```php
    set_transient( 'DEMO_OAUTH_CODE', $_REQUEST['code'], 300 );
    ```

    The google OAuth server returns all the data we need in the global `$_REQUEST` object. The `['code']` is the access code we can use to run API Calls! Whoo!

    The issue is that it's very short-lived and we don't want to keep re-authenticating every 5 minutes. But we'll deal with that later. For now, lets just store the value in the database.

    

2.  Output some text and then run the `wp_die()` function. You need to do this to properly run the request.



There we have it, we have the Access code in our database to use!



### Step 8 - Decode state.

>   `/src/callback/decode_state.php`

Not so fast... remember we have that little issue of the `action` parameter being inside the `state` parameter. Well we need to extract that value and add it as an action parameter itself.

In example, when the OAuth server sends the user back to admin-ajax.php, the request will look a little like this:

```http
https://yourdomain.com/wp-admin/admin-ajax.php
?state=eyJhY3Rpb24iOiJkZW1vX29hdXRoXANhbGxiYWNrIn0%3D
&code=4/4AElLX-I-BWrqG1t-gVJLi03lYYSPXysL70-w4yMI2Kt2if8CIAT2wL3PSPGTatNJ6B_tyQH5WczRr5At6firAI
&scope=https://www.googleapis.com/auth/youtube.force-ssl
```



Notice there's no `&action= ` there. That's because it's inside the part:

```http
?state=eyJhY3Rpb24iOiJkZW1vX29hdXRoX3NhbGxiYWNrIn0%3D
```

 So we need to grab that string, base64_decode and json_decode it, then add any of the key-value pairs as real request parameters.

I'll say upfront that this file is basically stolen from https://github.com/ohfuchs/acf-oauth , so go give him a star and have a look at his repo for a deeper analysis.

We're going to add a new action on `init` that will run wil a priority of `9` that will do some checks and intercept the `$_REQUEST['state']` parameter.

```php
add_action( 'init',	'_decode_repsonse_state', 9 );
```

And the function `_decode_repsonse_state` does the following:

1.  Checks for admin permission
2.  Checks for looged in user
3.  Checks to see if AJAX is running
4.  Checks if there is a `$_REQUEST['state']` parameter
5.  If all passes, `base64_decode()` the value
6.  Run a `json_decode()` on the value
7.  Merge the array key and value onto the existing` $_REQUEST` array.



Now we have an `action=demo_oauth_callback` in the `$_REQUEST` array that the `admin-ajax.php` file will pick up and run.



### Step 9 - Refresh Tokens

>   `/src/youtube.php`

The access token is nice a safe within the database for the next 5 minutes, but as mentioned before, we can't keep authenticating like that. This is where we now generate refresh tokens that won't run out and allow us to generate more access tokens without going through the whole process again.

The `demo_youtube` class has a `run()` method that kicks everything off and initially do these steps:

1.  Get any existing tokens from the transient DB.

2.  Create a new google client from the google-api-client-php package.

3.  Check to see if we have a `refresh_token` which we don't.

4.  Check to see if the user actually ran the OAuth flow.

5.  Create a new refresh token by running the `get_auth_token()` method.

    ```php
    /**
    * get_auth_token 
    * 
    * Not autenticated yet, so do so and set refresh token.
    * Refresh token set for 1 week.
    * 
    * @return void
    */
    public function get_auth_token()
    {
    
    	$this->client->authenticate($this->auth_token);
    
    	$this->refresh_token = $this->client->getRefreshToken();
    
    	set_transient( 
    		'DEMO_OAUTH_REFRESH_TOKEN', 
    		$this->refresh_token, 
    		WEEK_IN_SECONDS 
      );
    
    }
    ```

    This will do the following three things:

    -   Use the `auth_token` we got back from google to authenticate the google client. This is done simply by running it's own `authenticate` method with the token as a parameter.
    -   Once the client is authenticated, grab a new `refresh_token` by using the `getRefreshToken()` method.
    -   Save the refresh_token into another transient within the database for 1 week. (sidenote - I know this is insecure, it's for demo purposes only)

    Now we have the refresh_token we can continue onto running the youtube request.

    

### Step 10 - YouTube Request

>    `/src/youtube.php`

Now we have a refresh_token and an auth_token (for the next 5 minutes at least), we can run the `run_youtube_request()` method.

This uses the second composer installed package, the `google-api-php-client-services` that includes the YouTube service. 

https://github.com/googleapis/google-api-php-client-services

Now we can create a new instance of the google YouTube service by running the line:

```php
$this->service = new \Google_Service_YouTube($this->client);
```

You can see it requires an instance of an authenticated client to work. 

Once this is done, we can now call the YouTube API quite easily. Our `service` object can do a whole host of API requests. See the YouTube API documentation for more information:

https://developers.google.com/youtube/v3/docs

Our simple call will just list the channels the authenticated user has access to. So we can run the `listChannels()` method on the service object to do this. However, we need to pass any query parameters too, so just provide an array with those in it.

```php
$queryParams = [
  'mine' => true
];
$this->results = $this->service->channels->listChannels( 'snippet,contentDetails,statistics', $queryParams );
```

This will set the `$this->results` parameter to the result of the query.



### Step 11 - Using a Refresh Token

>`/src/youtube.php`

Lets return back to the `run()` method of the `demo_youtube` class.

The first check is whether there is a `refresh_token` or not. The second time around, there will be, so now we can use that token to authenticate the client instead. Thats done in the `use_refresh_token()` method.

Simply pass the token to the client's refreshToken method and it'll reauthenticate and allow you to continue using the API calls.

```php
public function use_refresh_token()
{
	$refresh_token = get_transient('DEMO_OAUTH_REFRESH_TOKEN');
	$this->client->refreshToken($refresh_token);
}
```

 

### Step 12 - Get Results

>   `/src/shortcode/button.php`

If we go back to the button code, you can see that it uses the `run()` method to do all the bits to authenticate and run the YouTube API call and the store it in the `$this->results` parameter. 

It the runs the `$this->get_results()` method to return those values, does a `json_encode` (to make it a little more readable) and echos it out to the screen.



## Summary

I'm actually using this as an ACF button (within a message field type) and it works quite well. I've stripped away as much as I could to make it easier to understand, but the core is there.

Closing notes:

1.  Security. Storing the tokens in cleartext in the DB as transients has to be insecure and I'm sure there must be better methods.
2.  Make sure that the `.gitignore` file is pointing at your `client_secret.json` file. You don't want to commit that into your repository or github.
3.  There's probably a better way with REST APIs instead of AJAX methods. This might be another project.



Pull requests welcome. Good luck and let the force be with you.


