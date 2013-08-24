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
}
