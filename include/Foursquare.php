<?php

class Foursquare
{
	public $accessToken;
	private $_clientID;
	private $_clientSecret;
	private $_baseRedirect;
		
	public function __construct($clientID, $clientSecret, $baseRedirect=FALSE)
	{
		$this->_clientID = $clientID;
		$this->_clientSecret = $clientSecret;
		$this->_baseRedirect = $baseRedirect;
	}

	public function authorizeURL($display='')
	{
		$params = array(
			'client_id' => $this->_clientID,
			'response_type' => 'code',
			'redirect_uri' => $this->_buildRedirectURI()
		);
		if($display)
			$params['display'] = $display;
			
		return 'https://foursquare.com/oauth2/authenticate?' . http_build_query($params);
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
	
		$token = $this->_request('https://api.foursquare.com/oauth2/access_token?' . http_build_query($params));
		$token = json_decode($token);

		if(is_object($token) && k($token, 'access_token'))
		{
			$this->accessToken = $token->access_token;
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
		return curl_exec($ch);
	}

	public function request($method, $params=array(), $post=FALSE)
	{
		if($this->accessToken)
			$params['oauth_token'] = $this->accessToken;
			
		$url = 'https://api.foursquare.com/v2/' . $method;
		
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
		$json = curl_exec($ch);
		
		if($json)
			return json_decode($json);
		else
			return FALSE;
	}
	
	private function _buildRedirectURI()
	{
		if(strpos($this->_baseRedirect, '?'))
			return $this->_baseRedirect . '&oauth_callback';
		else
			return $this->_baseRedirect . '?oauth_callback';
	}

}