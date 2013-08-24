<?php

namespace Peakium;

class SubmissionForm extends \Peakium\ApiResource
{

	public static function build($type, $params, $api_key=null)
	{
		list($response, $api_key) = \Peakium::request('post', self::build_url($type), $api_key, $params);
		return \Peakium\Util::convert_to_peakium_object($response, $api_key);
	}

	private static function build_url($type)
	{
		return self::endpoint_url() . '/' . $type;
	}
}
