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
        $this->client = new Google_Client();
        $this->client_id = $clientID;
        $this->client->setClientId($clientID);
        $this->client->setClientSecret($clientSecret);
        $this->client->setApplicationName($applicationName);
        $this->client->setDeveloperKey($developerKey);
        $this->gmail = new Google_Service_Gmail($this->client);
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
     * @param  string/boolean $accessType     The access type (online or offline), online is default
     * @param  string/boolean $approvalPrompt Whether to force approval on every login 
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getLogInURL($redirect_url, $scopes = array('openid'), $accessType = false, $approvalPrompt = false)
    {
        try {
            $this->client->setRedirectUri($redirect_url);
            $this->client->setScopes($scopes);
            if ($accessType) {
                $this->client->setAccessType($accessType);
            }
            if($approvalPrompt) {
                $this->client->setApprovalPrompt($approvalPrompt);
            }
            $loginUrl = $this->client->createAuthUrl();
            return ['status' => true, 'data' => $loginUrl];
        } catch (\Google_Auth_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Login logic
     * @return array       Status and message depending on the success of the operation
     */
    public function logIn($code)
    {
        try {
            $this->client->authenticate($code);
            $tokens = $this->client->getAccessToken();
            $this->tokens = json_decode($tokens);
            $attributes = $this->client->verifyIdToken($this->tokens->id_token, $this->client_id)->getAttributes();
            if ($attributes) {
                $this->is_authenticated = true;
                $this->user_id = $attributes['payload']['sub'];
                return ['status' => true, 'message' => 'Successfully authenticated.'];
            }
            return ['status' => false, 'message' => 'Error. Please try again.'];
        } catch (\Google_Auth_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check whether the current token is still valid
     * @param  object  $tokens The tokens as returned by Google's API
     * @return array       Status and message depending on the token validity
     */
    public function isTokenValid($tokens)
    {
        try {
            $this->client->setAccessToken(json_encode($tokens));
            if (!$this->client->isAccessTokenExpired()) {
                $this->tokens = $tokens;
                $attributes = $this->client->verifyIdToken($this->tokens->id_token, $this->client_id)->getAttributes();
                if ($attributes) {
                    $this->is_authenticated = true;
                    $this->user_id = $attributes['payload']['sub'];
                    return ['status' => true, 'message' => 'Token is valid.'];
                }
                return ['status' => false, 'message' => 'Error. Please try again.'];
            }
        } catch (\Google_Auth_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get the details of the logged in user
     * @return array       Status and data/error message depending on the success of the operation
     */
    public function getUserDetails()
    {
        if ($this->is_authenticated) {
            try {
                $me = $this->gmail->users->getProfile('me');
                return [
                            'user_id' => $this->user_id,
                            'email' => $me['emailAddress']
                        ];
            } catch (\Google_Service_Exception $e) {
                return ['status' => false, 'message' => $e->getMessage()];
            }
        }
        return ['status' => false, 'message' => 'User is not authenticated.'];
    }

    /**
     * Refreshes the access token
     * @param  string $refreshToken Refresh token returned by Google's API
     * @return array       Status and error message if the operation is unsuccessful
     */
    public function refreshToken($refreshToken)
    {
        try {
            $this->client->refreshToken($refreshToken);
            $this->tokens = json_decode($this->client->getAccessToken());
            $this->tokens->refresh_token = $refreshToken;
            $attributes = $this->client->verifyIdToken($this->tokens->id_token, $this->client_id)->getAttributes();
            if ($attributes) {
                $this->is_authenticated = true;
                $this->user_id = $attributes['payload']['sub'];
                return ['status' => true];
            }
            return ['status' => false, 'message' => 'Token is invalid.'];
        } catch (\Google_Auth_Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Returns the id of the logged in user
     * @return string The id of the logged in user
     */
    public function getUserId()
    {
        if(!$this->user_id) {
            throw new \Exception("User is not authenticated", 1);
        }
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
