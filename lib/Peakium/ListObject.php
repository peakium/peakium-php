<?php

namespace Peakium;

class ListObject extends PeakiumObject implements \IteratorAggregate, \Countable
{
	public function offsetGet($k)
	{
		switch(gettype($k)):
			case 'string':
				return parent::offsetGet($k);
			default:
				throw new ArgumentError(sprintf("You tried to access the %s index, but ListObject types only support String keys. (HINT: List calls return an object with a 'data' (which is the data array). You likely want to call \$object['data'][%s])", $k, $k));
		endswitch;
	}

	// So we can do foreach, etc
	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

	public function all($params=array(), $api_key=null)
	{
		$args = func_get_args();

		// Add the endpoint URL to use
		array_unshift($args, $this->_endpoint_url);

		return call_user_func_array(array('\Peakium\OperationList', 'all'), $args);
	}

	public function retrieve($id, $api_key=null)
	{
		list($response, $api_key) = \Peakium::request('get', $this->_endpoint_url . '/' . $id, $api_key);
		return \Peakium\Util::convert_to_peakium_object($response, $api_key);
	}

	public function set_endpoint_url($endpoint_url, $params)
	{
		//Set the URL for list objects
		$url = $endpoint_url;
		if (!empty($params))
			$url .= (strpos($endpoint_url, '?') ? '&' : '?') . \Peakium::uri_encode($params);
		$this->_endpoint_url = $url;
		return $this;
	}

	// So we can count the objects
	public function count()
	{
		return count($this->data);
	}
}