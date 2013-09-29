<?php

namespace Peakium;

abstract class Util
{
	public static function objects_to_ids($object)
	{
		switch(gettype($object)):
			case 'array':
				$res = array();
				foreach($object as $k => $v)
				{
					if ($v !== null)
					{
						$res[$k] = self::objects_to_ids($v);
					}
				}
				return $res;
			case 'object':
				if (get_class($object) == 'APIResource')
					return $object->id;
			default:
				return $object;
		endswitch;
	}

	protected static $object_classes = array(
				'customer' => 'Customer',
				'event' => 'Event',
				'event_webhook' => 'EventWebhook',
				'gateway' => 'Gateway',
				'invoice' => 'Invoice',
				'payment_session' => 'PaymentSession',
				'subscription' => 'Subscription',
				'webhook' => 'Webhook',
				'list' => 'ListObject',
	);

	public static function convert_to_peakium_object($resp, $api_key)
	{
		switch(gettype($resp)):
			case 'array':
				$func = function($v) use (&$api_key) { return self::convert_to_peakium_object($v, $api_key); };
				return array_map($func, $resp);
			case 'object':
				// Just an array there is no "object" attribute
				if (!isset($resp->object) || !is_string($resp->object))
					return get_object_vars($resp);

				$class = '\\' . __NAMESPACE__ . '\\' . (isset($resp->object) && isset(self::$object_classes[$resp->object]) ? self::$object_classes[$resp->object] : 'PeakiumObject');
				return $class::construct_from(get_object_vars($resp), $api_key);
			default:
				return $resp;
		endswitch;
	}

	public static function file_readable($file)
	{
		return is_readable($file);
	}

	public static function symbolize_names($object)
	{
		switch(gettype($object)):
			case 'object':
				// No need to symbolize object in PHP
				if (isset($object->debug_info)) unset($object->debug_info);
				return $object;
			case 'array':
				$func = function($value) { return self::symbolize_names($value); };
				return array_map($func, $object);
			default:
				return $object;
		endswitch;
	}

	public static function url_encode($key)
	{
		return urlencode($key);
	}

	public static function flatten_params($params, $parent_key=null)
	{
		$result = array();
		foreach ($params as $key => $value)
		{
			$calculated_key = $parent_key ? $parent_key . self::url_encode($key) : self::url_encode($key);
			if (gettype($value) == 'object')
				array_merge($result, self::flatten_params($value, $calculated_key));
			elseif(gettype($value) == 'array')
				array_merge($result, self::flatten_params_array($value, $calculated_key));
			else
				array_push($result, $value);
		}
		return $result;
	}

	public static function flatten_params_array($value, $calculated_key)
	{
		$result = array();
		foreach ($value as $elem)
		{
			if (gettype($value) == 'object')
				array_merge($result, self::flatten_params($elem, $calculated_key));
			elseif(gettype($value) == 'array')
				array_merge($result, self::flatten_params_array($elem, $calculated_key));
			else
				array_push($result, array($calculated_key => array(), $elem));
		}
		return $result;
	}

	public static function camel_to_snake_case($string)
	{
		return preg_replace(
			'/(^|[a-z])([A-Z])/e', 
			'strtolower(strlen("\\1") ? "\\1_\\2" : "\\2")',
			$string 
		); 
	}


	public static function convert_peakium_object_to_array($values)
	{
		$results = array();
		foreach ($values as $k => $v) {
			// FIXME: this is an encapsulation violation
			if ($k[0] == '_') {
				continue;
			}

			if ($v instanceof PeakiumObject) {
				$results[$k] = $v->__toArray(true);
			}
			else if (is_array($v)) {
				$results[$k] = self::convert_peakium_object_to_array($v);
			}
			else
			{
				$results[$k] = $v;
			}
		}
		return $results;
	}
}
