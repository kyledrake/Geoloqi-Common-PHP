<?php 

/**
 * Check for the required config.php file 
 */
if(!file_exists(dirname(__FILE__) . '/config.php'))
{
	die('Setup not complete: Copy config.template.php to config.php and modify the configuration settings to match your environment.');
}

/**
 * Helper functions for fetching data from GET/POST/REQUEST
 */
function get($key)
{
	if(array_key_exists($key, $_GET))
		return $_GET[$key];
	else
		return FALSE;
}
/**
 * Returns the value from POST. When the content-type header is set to application/json, the API controller
 * has already read in the raw post data and set $_POST to an object after decoding the post data from JSON.
 */
function post($key, $val=NULL)
{
	if(is_object($_POST))
	{
		if($val == NULL)
			if(property_exists($_POST, $key))
				return $_POST->{$key};
			else
				return FALSE;
		else
			$_POST->{$key} = $val;
	}
	else
	{
		if($val == NULL)
			if(array_key_exists($key, $_POST))
				return $_POST[$key];
			else
				return FALSE;
		else
			$_POST[$key] = $val;
	}
}
function request($key)
{
	if(array_key_exists($key, $_REQUEST))
		return $_REQUEST[$key];
	else
		return FALSE;
}
function session($key)
{
	if(array_key_exists($key, $_SESSION))
		return $_SESSION[$key];
	else
		return FALSE;
}

// Retrieves $key from $object without throwing a notice, and works whether $object is an array or object
function k($object, $key, $notfound=NULL)
{
	if(is_object($object))
	{
		if(property_exists($object, $key))
			return $object->{$key};
		else
			return $notfound;
	}
	elseif(is_array($object))
	{
		if(array_key_exists($key, $object))
			return $object[$key];
		else
			return $notfound;
	}
	else
		return $notfound;
}

/**
 * Returns a handle to the DB object
 */
function db()
{
	static $db;
	if(!isset($db))
	{
		try {
			$db = new PDO(PDO_DSN, PDO_USER, PDO_PASS);
		} catch (PDOException $e) {
			header('HTTP/1.1 500 Server Error');
			die(json_encode(array('error'=>'database_error', 'error_description'=>'Connection failed: ' . $e->getMessage())));
		}
	}
	return $db;
}

/**
 * Returns a handle to the Memcache object
 */
function mc()
{
	/*
	 * If the constant was not defined in the config class, then PHP will treat it as a string which will
	 * be evaluated as 'true' causing this mc() function to be run. Since we don't want to use Memcache
	 * unless it is explicitly enabled in the config file, return a "NullMemcache" object which will throw
	 * an error no matter what method is called on it.
	 */
	if(!defined('MEMCACHE_ENABLED') || !MEMCACHE_ENABLED)
		return new NullMemcache();
	
	static $memcache;
	if(!isset($memcache))
	{
		if(!class_exists('Memcache'))
			die('Class Memcache was not found. Disable Memcache in the config file or install Memcache.');
		
		$memcache = new Memcache;
		foreach($GLOBALS['MEMCACHE_SERVERS'] as $m)
			$memcache->addServer($m['host'], $m['port']);
	}
	
	return $memcache;
}

class NullMemcache
{
	public function __call($method, $params)
	{
		die('Attempted to use Memcache but it has not been configured properly.');
	}
	
	public function set($method, $params)
	{
		return null;
	}
}


/**
 * For HTML formatting arrays for debugging
 */
function pa($a)
{
	echo '<pre>';
	print_r($a);
	echo '</pre>';
}

/**
 * Log to a MediaWiki RecentChanges bot.
 * http://www.mediawiki.org/wiki/Manual:MediaWiki-Recent_Changes-IRCBot
 */
function irc_debug($msg)
{
	if(!defined('MW_IRC_HOST') || MW_IRC_HOST == FALSE)
		return FALSE;
	
	static $sock = FALSE;
	if($sock == FALSE)
		$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

	$msg = '[' . LOG_PREFIX . '] ' . $msg;
	
	socket_sendto($sock, $msg, strlen($msg), 0, MW_IRC_HOST, MW_IRC_PORT);
}

?>
