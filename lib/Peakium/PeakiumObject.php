<?php

namespace Peakium;

class PeakiumObject implements \ArrayAccess
{
	public static $_permanent_attributes;

	protected $_api_key;
	protected $_values;
	protected $_unsaved_values;
	protected $_transient_values;
	protected $_retrieve_options;

	public static function init()
	{
		self::$_permanent_attributes = new Set(array('_api_key', 'id'));
	}

	public function __construct($id=null, $api_key=null)
	{
		// Parameter overloading
		if (is_array($id)) {
			$this->_retrieve_options = $id;
			unset($this->_retrieve_options['id']);
			$id = $id['id'];
		}
		else
		{
			$this->_retrieve_options = array();
		}

		$this->_api_key = $api_key;
		$this->_values = array();
		$this->_unsaved_values = new Set();
		$this->_transient_values = new Set();
		if ($id)
			$this->id = $id;
	}

	public static function construct_from($values, $api_key=null)
	{
		$class = get_called_class();
		$obj = new $class(isset($values['id']) ? $values['id'] : null, $api_key);
		$obj->refresh_from($values, $api_key);
		return $obj;
	}

	public function __toString()
	{
		return $this->__toJSON();
	}

	public function refresh_from($values, $api_key, $partial=false)
	{
		$this->_api_key = $api_key;

		if (gettype($values) == 'object') $values = get_object_vars($values);

		$removed = ($partial ? new Set() : new Set(array_diff(array_keys($this->_values), array_keys($values))));
		$added = new Set(array_diff(array_keys($values), array_keys($this->_values)));
		// Wipe old state before setting new.  This is useful for e.g. updating a
		// customer, where there is no persistent card parameter.  Mark those values
		// which don't persist as transient

		foreach ($removed as $k) {
//			if (self::$_permanentAttributes->includes($k))
//			continue;
			unset($this->_values[$k]);
			$this->_transient_values->add($k);
			$this->_unsaved_values->delete($k);
		}

		foreach ($values as $k => $v) {
//			if (self::$_permanentAttributes->includes($k))
//			continue;
			$this->_values[$k] = Util::convert_to_peakium_object($v, $api_key);
			$this->_transient_values->delete($k);
			$this->_unsaved_values->delete($k);
		}
	}

	public function __toJSON()
	{
		return \Peakium\JSON::dump($this->__toArray(true), $pretty = true);
	}

	public function __toArray($recursive=false)
	{
	    if ($recursive)
			return \Peakium\Util::convert_peakium_object_to_array($this->_values);
		else
			return $this->_values;
	}

	// Standard accessor magic methods
	public function __set($k, $v)
	{
		if ($v === ""){
			throw new InvalidArgumentException(
				'You cannot set \''.$k.'\'to an empty string. '
				.'We interpret empty strings as NULL in requests. '
				.'You may set obj->'.$k.' = NULL to delete the property');
		}
		$this->_values[$k] = $v;
		$this->_unsaved_values->add($k);
	}

	public function __isset($k)
	{
		return isset($this->_values[$k]);
	}

	public function __unset($k)
	{
		unset($this->_values[$k]);
		$this->_transient_values->add($k);
		$this->_unsaved_values->delete($k);
	}

	public function __get($k)
	{
		if (array_key_exists($k, $this->_values)) {
			return $this->_values[$k];
		} else if ($this->_transient_values->includes($k)) {
			$class = get_class($this);
			$attrs = join(', ', array_keys($this->_values));
			error_log("Peakium Notice: Undefined property of $class instance: $k.  HINT: The $k attribute was set in the past, however.  It was then wiped when refreshing the object with the result returned by Peakium's API, probably as a result of a save().  The attributes currently available on this object are: $attrs");
			return null;
		} else {
			$class = get_class($this);
			error_log("Peakium Notice: Undefined property of $class instance: $k");
			return null;
		}
	}

	// ArrayAccess methods
	public function offsetSet($k, $v)
	{
		$this->$k = $v;
	}

	public function offsetExists($k)
	{
		return array_key_exists($k, $this->_values);
	}

	public function offsetUnset($k)
	{
		unset($this->$k);
	}

	public function offsetGet($k)
	{
		return array_key_exists($k, $this->_values) ? $this->_values[$k] : null;
	}

	public function keys()
	{
		return array_keys($this->_values);
	}
}

