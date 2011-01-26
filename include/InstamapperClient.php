<?php 
class InstamapperClient
{
	public function add_device($username)
	{
		// Add a device
		$result = $this->_curl('https://www.instamapper.com/fe', array('action'=>'addDevice', 'label'=>'geoloqi_' . $username));
		
		// Find the device key
		if(preg_match('/device_key=([0-9]+)/', $result, $match))
			$device_key = $match[1];
		else
			return FALSE;
			
		// Configure API access for the device
		// http://www.instamapper.com/fe?action=shareDevice&device_key=12345678901234
		$result = $this->_curl('http://www.instamapper.com/fe?action=enableAPI&device_key=' . $device_key);
		
		if(preg_match('/' . $device_key . '\s*<td>([0-9]+)/s', $result, $match))
			$api_key = $match[1];
		else
			return FALSE;
		
		return array('device_key' => $device_key, 'api_key' => $api_key);
	}

	public function watch($watch_key)
	{
		$result = $this->_curl('http://www.instamapper.com/rtt?d=' . $watch_key . '&n=1');
		// TODO: Detect if we got logged out and log back in
		return trim($result);
	}
	
	public function get_watch_key($device_key)
	{
		$result = $this->_curl('http://www.instamapper.com/fe?page=track&device_key=' . $device_key);
		if(preg_match('/input.+name="?key"?.+value="?([0-9]+)/', $result, $match))
			return $match[1];
		else
			return FALSE;
	}
	
	private function _curl($url, $params=array(), $referer=FALSE)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if(count($params) > 0)
		{
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
		}
		touch(INSTAMAPPER_COOKIEFILE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_COOKIEFILE, INSTAMAPPER_COOKIEFILE);
		curl_setopt($ch, CURLOPT_COOKIEJAR, INSTAMAPPER_COOKIEFILE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		if($referer)
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.8) Gecko/20100202 Firefox/3.5.8 (.NET CLR 3.5.30729)');
		$result = curl_exec($ch);
		
		if($result === FALSE)
			echo curl_error($ch);
		
		return $result;
	}

	public function login()
	{
		$this->_curl('https://www.instamapper.com/fe', array('action'=>'login', 'username'=>INSTAMAPPER_USERNAME, 'password'=>INSTAMAPPER_PASSWORD));
	}
}
?>