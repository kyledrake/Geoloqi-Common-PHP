<?php

class GoogleAuth
{
	public $accessToken;
	public $refreshToken;
	private $_clientID;
	private $_clientSecret;
	private $_baseRedirect;

	public function __construct($clientID, $clientSecret, $baseRedirect=FALSE)
	{
		$this->_clientID = $clientID;
		$this->_clientSecret = $clientSecret;
		$this->_baseRedirect = $baseRedirect;
	}

	public function authorizeURL($scope='')
	{
		$params = array(
			'client_id' => $this->_clientID,
			'response_type' => 'code',
			'scope' => $scope,
			'redirect_uri' => $this->_buildRedirectURI()
		);
			
		return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
	}

	public function isCallback()
	{
		// oauth_callback is set in the redirect URI
		return array_key_exists('oauth_callback', $_GET);
	}

	public function callback()
	{
		$params = array(
			'client_id' => $this->_clientID,
			'redirect_uri' => $this->_buildRedirectURI(),
			'grant_type' => 'authorization_code',
			'client_secret' => $this->_clientSecret,
			'code' => $_GET['code']
		);
	
		$token = $this->_request('https://accounts.google.com/o/oauth2/token', $params);
		$token = json_decode($token);

		if(is_object($token) && k($token, 'access_token'))
		{
			$this->accessToken = $token->access_token;
			$this->refreshToken = $token->refresh_token;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	public function _request($url, $post=FALSE)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if($post)
		{
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}

		return curl_exec($ch);
	}

	public function request($url, $params=array(), $post=FALSE)
	{
		if($this->accessToken) {
			// If there is an access token present, use it
			$params['oauth_token'] = $this->accessToken;
		} elseif($this->refreshToken) {
			// If there is no access token, then use the refresh token if present to get a new access token
			if($this->_refreshToken()) {
				$params['oauth_token'] = $this->accessToken;
			} else {
				// Failed to get an access token using the refresh token
				return FALSE;
			}
		} else {
			// If there is no access token or refresh token, can't do anything
			return FALSE;
		}

		$params['alt'] = 'jsonc';
			
		if($post)
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		else
		{
			$url .= '?' . http_build_query($params);
			$ch = curl_init($url);
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$json = curl_exec($ch);

		if($json)
			return json_decode($json);
		else
			return FALSE;
	}
	
	private function _refreshToken()
	{
		if(!$this->refreshToken)
			return FALSE;
	
		$params = array(
			'client_id' => $this->_clientID,
			'grant_type' => 'refresh_token',
			'client_secret' => $this->_clientSecret,
			'refresh_token' => $this->refreshToken
		);
	
		$token = $this->_request('https://accounts.google.com/o/oauth2/token', $params);
		$token = json_decode($token);

		if(is_object($token) && k($token, 'access_token'))
		{
			$this->accessToken = $token->access_token;
			if(property_exists($token, 'refresh_token'))
				$this->refreshToken = $token->refresh_token;
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	private function _buildRedirectURI()
	{
		if(strpos($this->_baseRedirect, '?'))
			return $this->_baseRedirect . '&oauth_callback';
		else
			return $this->_baseRedirect . '?oauth_callback';
	}

}