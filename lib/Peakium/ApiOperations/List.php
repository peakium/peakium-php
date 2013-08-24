<?php

namespace Peakium;

class OperationList
{
	public static function all($endpoint_url, $filters = array(), $api_key=null)
	{
		list($response, $api_key) = \Peakium::request('get', $endpoint_url, $api_key, $filters);
		$object = \Peakium\Util::convert_to_peakium_object($response, $api_key);

		// Set the URL for list objects
		if ($object instanceof \Peakium\ListObject)
			$object->set_endpoint_url($endpoint_url, $filters);

		return $object;
	}
}