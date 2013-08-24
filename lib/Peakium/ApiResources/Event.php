<?php

namespace Peakium;

class Event extends \Peakium\ApiResource
{
	public static $include = array('List');

	public function validate($data)
	{
		list($response, $api_key) = \Peakium::request('post', $this->validate_url(), $this->api_key, $data);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	public function send($params=array())
	{
		list($response, $api_key) = \Peakium::request('post', $this->send_url(), $this->api_key, $params);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	private function validate_url()
	{
		return $this->object_endpoint_url() . '/validate';
	}

	private function send_url()
	{
		return $this->object_endpoint_url() . '/send';
	}
}
