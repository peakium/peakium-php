<?php

namespace Peakium;

class Invoice extends \Peakium\ApiResource
{
	public static $include = array(
								'List',
	);

	public static function overdue($params=array(), $api_key=null)
	{
		$params = array_merge($params, array('overdue' => true));
		return self::all($params, $api_key);
	}

	public function pay()
	{
		list($response, $api_key) = \Peakium::request('post', $this->pay_url(), $this->api_key);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	private static function overdue_url()
	{
		return self::endpoint_url() . '/overdue';
	}

	private function pay_url()
	{
		return $this->object_endpoint_url() . '/pay';
	}

}
