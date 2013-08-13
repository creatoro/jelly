<?php defined('SYSPATH') or die('No direct script access.');


class Jelly_Core_Field_DateTime extends Jelly_Field
{
	/**
	 * @var boolean convert empty values to null
	 */

	public $convert_empty = true;
	/**
	 * @var  boolean  default to converting empty values to NULL
	 */
	public $allow_null = true;

	/**
	 * @var  int  default is NULL
	 */
	public $default = null;

	public function __construct($options = array())
	{
		parent::__construct($options);
	}

	public function get($model, $value)
	{
		if ($value === null) {
			return null;
		}
		if ($value instanceof DateTime) {
			return $value;
		}
		return new DateTime($value);
	}

	public function save($model, $value, $loaded)
	{
		if ($value instanceof DateTime) {
			return $value->format('Y-m-d H:i:s');
		}
		return $value;
	}
}
