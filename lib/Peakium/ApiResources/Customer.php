<?php

namespace Peakium;

class Customer extends \Peakium\ApiResource
{
	public static $include = array('List');

	public function subscriptions()
	{
		return Subscription::all(array('customer' => $this->id, $this->_api_key));
	}

	public function cancel_subscription($token)
	{
		list($response, $api_key) = \Peakium::request('delete', $this->subscription_url($token), $this->_api_key);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	private function subscription_url($token)
	{
		return $this->subscriptions_url() . '/' . urlencode($token);
	}

	private function subscriptions_url()
	{
		return $this->object_endpoint_url() . '/subscriptions';
	}
}
