<?php

namespace Peakium;

class Subscription extends \Peakium\ApiResource
{
	public static $include = array(
								'List',
	);

	public static function retrieve($id, $api_key = null)
	{
		throw new InvalidRequestError(sprintf('You need to access individual %s through a %s', self::class_name(), Customer::class_name()), 'customer');
	}

	public function object_endpoint_url()
	{
		if (!($token = $this->token))
			throw new InvalidRequestError(sprintf('Could not determine which endpoint URL to request: %s instance has invalid token: %s', self::class_name(), $token), 'token');

		if (!($customer = $this->customer))
			throw new InvalidRequestError(sprintf('Could not determine which endpoint URL to request: %s instance has invalid customer: %s', self::class_name(), $customer), 'customer');

		return $customer->object_endpoint_url() . '/subscriptions/' . urlencode($token);
	}
}