<?php
class Geonames
{
	protected static function _queryURL($url, $mcKey=FALSE)
	{
		if(MEMCACHE_ENABLED && $mcKey && ($data=mc()->get($mcKey)) != FALSE)
			$json = $data;
		else
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			$json = curl_exec($ch);

			if(!$json)
				return FALSE;

			$json = json_decode($json);

			if(MEMCACHE_ENABLED && $mcKey)
				mc()->set($mcKey, $json, 0, 86400 * 30);
		}

		return $json;
	} 

	public static function getNearestIntersection($lat, $lng)
	{
		$url = 'http://ws.geonames.org/findNearestIntersectionJSON?formatted=true&lat=' . $lat . '&lng=' . $lng . '&style=full';
		$mcKey = self::_mcKey('intersection', $lat . ',' . $lng);

		$json = self::_queryURL($url, $mcKey);

		if(k($json, 'intersection') == FALSE)
			return FALSE;

		$intersection = $json->intersection->street1 . ' & ' . preg_replace('/^(SE|SW|NE|NW|N|E|S|W) /', '', $json->intersection->street2);
		
		return $intersection;
	}

	public static function getNearestIntersectionWithCity($lat, $lng)
	{
		$url = 'http://ws.geonames.org/findNearestIntersectionJSON?formatted=true&lat=' . $lat . '&lng=' . $lng . '&style=full';
		$mcKey = self::_mcKey('intersection', $lat . ',' . $lng);

		$json = self::_queryURL($url, $mcKey);

		if(k($json, 'intersection') == FALSE)
			return FALSE;

		$intersection = k($json->intersection, 'street1') . ' & ' 
			. preg_replace('/^(SE|SW|NE|NW|N|E|S|W) /', '', k($json->intersection, 'street2'))
			. ', ' . k($json->intersection, 'placename') . ', ' . k($json->intersection, 'adminName1');
				
		return $intersection;
	}
	
	private static function _mcKey($method, $data)
	{
		return 'Geonames::' . $method . ':' . $data;
	}
}
?>