<?php

namespace Peakium;

class Webhook extends \Peakium\ApiResource
{
	public static $include = array(
								'Create',
								'Delete',
								'List',
	);

	public function __construct($id=null, $api_key=null)
	{
		if (is_array($id))
			$id = $id['url'];

		return parent::__construct($id, $api_key);
	}

	public function object_endpoint_url()
	{
		if (!($url = $this->url))
			throw new InvalidRequestError(sprintf('Could not determine which endpoint URL to request: %s instance has invalid url: %s', self::class_name(), $url), 'url');

		return self::endpoint_url() . '/' . urlencode($url);
	}
}
