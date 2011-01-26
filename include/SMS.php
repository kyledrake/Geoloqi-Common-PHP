<?php
class SMS
{
	public function send($number, $message)
	{
		$params = array();
		$params['action'] = 'create';
		$params['token'] = TROPO_SMS_TOKEN;
		$params['geoloqi_method'] = 'message';
		$params['geoloqi_to'] = $number;
		$params['geoloqi_text'] = $message;
		$params['geoloqi_network'] = 'SMS';
		echo file_get_contents('http://api.tropo.com/1.0/sessions?' . http_build_query($params, '', '&')) . "\n";
	}
}
?>