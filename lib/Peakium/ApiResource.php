<?php

namespace Peakium;

if (!function_exists('curl_init')) {
	throw new \Exception('Peakium needs the CURL PHP extension.');
}

abstract class ApiResource extends PeakiumObject
{
	public static function class_name()
	{
		$class = get_called_class();
		if (!false == ($pos = strrpos($class, '\\')))
			$class = substr($class, $pos + 1);

		return $class;
	}

	public static function endpoint_url()
	{
		if (get_called_class() == __CLASS__)
			throw new NotImplementedError('APIResource is an abstract class. You should perform actions on its subclasses');

		return '/v1/' . \Peakium\Util::camel_to_snake_case(self::class_name()) . 's';
	}

	public function object_endpoint_url()
	{
		if (!($id = $this->id))
			throw new InvalidRequestError(sprintf('Could not determine which endpoint URL to request: %s instance has invalid ID: %s', self::class_name(), $id), 'id');

		return self::endpoint_url() . '/' . $id;
	}

	public function refresh()
	{
		list($response, $api_key) = \Peakium::request('get', $this->object_endpoint_url(), $this->_api_key, $this->_retrieve_options);
		$this->refresh_from($response, $api_key);
		return $this;
	}

	public static function retrieve($id, $api_key = null)
	{
		$class = get_called_class();
		$instance = new $class($id, $api_key);
		$instance->refresh();
		return $instance;
	}

	public static $include = array();

	public static function __callStatic($name, array $args)
	{
		$class = self::_find_operation_class($name);

		// Add the endpoint URL to use
		array_unshift($args, self::endpoint_url());

		return call_user_func_array(array($class, $name), $args);
	}

	public function __call($name, array $args)
	{
		$class = self::_find_operation_class($name);

		// Add the class itself
		array_unshift($args, $this);

		return call_user_func_array(array($class, $name), $args);
	}

	public static function _find_operation_class($method_name)
	{
		foreach (static::$include as $operation)
		{
			$class = '\Peakium\Operation' . $operation;
			if (method_exists($class, $method_name))
			{
				return $class;
			}
		}
		throw new NoMethodError(sprintf('Undefined method \'%s\' for %s', $method_name, self::class_name()));
	}
}
