<?php

/**
 * Class for authenticating with gmail account
 */
namespace GmailWrapper;
use Google_Client;
use Google_Service_Gmail;

class Authenticate
{
    private static $instance;
    private $client;
    private $gmail;
    private $tokens;
    private $client_id;
    private $user_id;
    private $is_authenticated;

    private function __construct($clientID, $clientSecret, $applicationName, $developerKey)
    {
        $this->is_authenticated = false;
        if (is_null($this->client)) {
            $this->client = new Google_Client();
            $this->client_id = $clientID;
            $this->client->setClientId($clientID);
            $this->client->setClientSecret($clientSecret);
            $this->client->setApplicationName($applicationName);
            $this->client->setDeveloperKey($developerKey);
        }
    }

    /**
     * Returns the current instance of the Authenticate class if it already exists or creates a new one if not.
     * @param  string $clientID        The client id of the app
     * @param  string $clientSecret    The client secret key of the app
     * @param  string $applicationName The application name
     * @param  string $developerKey    The developer key
     * @return Authenticate            The instance of the Authenticate class
     */
    public static function getInstance($clientID, $clientSecret, $applicationName, $developerKey)
    {
        if (null === static::$instance) {
            static::$instance = new Authenticate($clientID, $clientSecret, $applicationName, $developerKey);
        }
        return static::$instance;
    }

    /**
     * Returns the login url needed for user authentication
     * @param  string $redirect_url The redirect url where the app should return after authentication
     * @param  array  $scopes       The login scope - permissions requested from the user
     * @return string               The login url 
     */
    public function getLogInURL($redirect_url, $scopes = array('openid'))
    {
        $this->client->setRedirectUri($redirect_url);
        $this->client->setScopes($scopes);

        $loginUrl = $this->client->createAuthUrl();
        return $loginUrl;
    }

    /**
     * Login logic
     * @return boolean The status of the login operation
     */
    public function logIn()
    {
        try {
            $this->gmail = new Google_Service_Gmail($this->client);
            if (isset($_GET['code'])) {
                $this->client->authenticate($_GET['code']);
                $tokens = $this->client->getAccessToken();
                $this->tokens = json_decode($tokens);
                $attributes = $this->client->verifyIdToken($this->tokens->id_token, $this->client_id)->getAttributes();
                if ($attributes['payload']['sub']) {
                    $this->is_authenticated = true;
                    $this->user_id = $attributes['payload']['sub'];
                    return true;
                }
                return false;
            }
            return false;
        } catch (Google_Auth_Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    }

    /**
     * Check whether the current token is still valid
     * @param  object  $tokens The tokens as returned by Google's API
     * @return boolean         The status of token validity
     */
    public function isTokenValid($tokens)
    {
        if (isset($tokens->id_token) && isset($tokens->access_token)) {
            $this->client->setAccessToken(json_encode($tokens));
        }
        if (!$this->client->isAccessTokenExpired()) {
            $this->tokens = $tokens;
            $attributes = $this->client->verifyIdToken($this->tokens->id_token, $this->client_id)->getAttributes();
            if ($attributes['payload']['sub']) {
                $this->is_authenticated = true;
                $this->user_id = $attributes['payload']['sub'];
            }
            return true;
        }
        return false;
    }

    /**
     * Get the details of the logged in user
     * @return array|boolean User details if the user is authenticated; false if not
     */
    public function getUserDetails()
    {
        if ($this->is_authenticated) {
            $me = $this->gmail->users->getProfile('me');
            return [
                        'user_id' => $this->user_id,
                        'email' => $me['emailAddress']
                    ];
        }
        return false;
    }
    
    /**
     * Returns the id of the logged in user
     * @return string The id of the logged in user
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Returns the google client
     * @return Google_Client The google client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the saved tokens
     * @return object Tokens as returned by Google
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Returns the status of the user authentication
     * @return boolean Status variable that shows whether the user is authenticated
     */
    public function isAuthenticated()
    {
        return $this->is_authenticated;
    }
}
