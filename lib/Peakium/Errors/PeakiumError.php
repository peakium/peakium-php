<?php

namespace Peakium;

class PeakiumError extends \Exception
{
	public $http_code;
	public $http_body;
	public $json_body;
	
	public function __construct($message=null, $http_code=null, $http_body=null, $json_body=null)
	{
		parent::__construct($message);
		$this->http_code = $http_code;
		$this->http_body = $http_body;
		$this->json_body = $json_body;
	}
}
