<?php

namespace Peakium;

class Gateway extends \Peakium\ApiResource
{
	public static $include = array(
								'Create',
								'List',
								'Update'
	);

	public function set_default()
	{
		list($response, $api_key) = \Peakium::request('post', $this->set_default_url(), $this->_api_key);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	private function set_default_url()
	{
		return $this->object_endpoint_url() . '/default';
	}
}
