<?php

namespace Peakium;

class OperationCreate
{
	public static function create($endpoint_url, $params=array(), $api_key=null)
	{
		list($response, $api_key) = \Peakium::request('post', $endpoint_url, $api_key, $params);
		return \Peakium\Util::convert_to_peakium_object($response, $api_key);
	}
}