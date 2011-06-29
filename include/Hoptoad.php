<?php
// https://github.com/mattfawcett/php-hoptoad-notifier/blob/master/hoptoad.php

/**
License

Copyright (c) 2008, Rich Cavanaugh All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided 
that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions 
	and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions 
	and the following disclaimer in the documentation and/or other materials provided with the distribution.
* The name of the author may not be used to endorse or promote products derived from this software 
	without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR 
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE 
USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
**/

set_error_handler(array("Hoptoad", "errorHandler"));
set_exception_handler(array("Hoptoad", "exceptionHandler"));

class Hoptoad
{
	public static function errorHandler($code, $message)
	{
		if ($code == E_STRICT) return;
		Hoptoad::notifyHoptoad(HOPTOAD_API_KEY, $message, null, 2);
	}
	
	public static function exceptionHandler($exception)
	{
		Hoptoad::notifyHoptoad(HOPTOAD_API_KEY, $exception->getMessage(), null, 2);
	}
	
	public static function notifyHoptoad($api_key, $message, $error_class=null, $offset=1)
	{
		$lines = array_slice(Hoptoad::tracer(), $offset);
		
		if (isset($_SESSION)) {
			$session = array('key' => session_id(), 'data' => $_SESSION);
		} else {
			$session = array();
		}
		
		$body = array(
			'api_key' => $api_key,
			'error_class' => $error_class,
			'error_message' => $message,
			'backtrace' => $lines,
			'request' => array("params" => $_REQUEST, "url" => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
			'session' => $session,
			'environment' => $_SERVER
		);
		require_once(dirname(__FILE__) . "/Spyc.php");
		$yaml = Spyc::YAMLDump(array("notice" => $body),4,60);
		
		$curlHandle = curl_init(); // init curl
		
		// cURL options
		curl_setopt($curlHandle, CURLOPT_URL, 'http://hoptoadapp.com/notices/'); // set the url to fetch
		curl_setopt($curlHandle, CURLOPT_POST, 1);
		curl_setopt($curlHandle, CURLOPT_HEADER, 0);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10); // time to wait in seconds
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $yaml);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array("Accept: text/xml, application/xml", "Content-type: application/x-yaml"));
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		
		curl_exec($curlHandle); // Make the call for sending the message
		curl_close($curlHandle); // Close the connection
	}
  
	public static function tracer()
	{
		$lines = Array();
		
		$trace = debug_backtrace();
		
		$indent = '';
		$func = '';
		
		foreach($trace as $val) {
			if (!isset($val['file']) || !isset($val['line'])) continue;
			
			$line = $val['file'] . ' on line ' . $val['line'];
			
			if ($func) $line .= ' in function ' . $func;
			$func = $val['function'];
			$lines[] = $line;
		}
		return $lines;
	}
}
