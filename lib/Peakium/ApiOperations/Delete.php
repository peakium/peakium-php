<?php

namespace Peakium;

class OperationDelete
{
	public static function delete(\Peakium\PeakiumObject $object)
	{
		list($response, $api_key) = \Peakium::request('delete', $object->object_endpoint_url(), $object::$api_key);
		$object->refresh_from($response, $api_key);
	}
}