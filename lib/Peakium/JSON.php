<?php

namespace Peakium;

if (!function_exists('json_decode')) {
	throw new \Exception('Peakium needs the JSON PHP extension.');
}

class JSON
{
	public static function dump($array, $pretty = false)
	{
		if ($pretty && defined('JSON_PRETTY_PRINT'))
		  return json_encode($array, JSON_PRETTY_PRINT);
		else
		  return json_encode($array);
	}

	public static function load($string)
	{
		return json_decode($string);
	}
}
