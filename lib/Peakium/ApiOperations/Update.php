<?php

namespace Peakium;

class OperationUpdate
{
	public static function save(\Peakium\PeakiumObject $object)
	{
		if (!empty($object->_unsaved_values))
		{
			$values = $object->_unsaved_values; 
			foreach ($values as $h => $k)
			{
				$values[$h]->update($object->values[$k] == null ? '' : $object->values[$k]);
			}
			$values.delete('id');

			list($response, $api_key) = \Peakium::request('post', $endpoint_url, $object->_api_key, $values);
			$object->refresh_from($response, $api_key);
		}

		return $object;
	}
}