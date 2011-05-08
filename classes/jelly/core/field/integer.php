<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles integer data-types
 *
 * @package    Jelly
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Integer extends Jelly_Field {

	/**
	 * @var  int  default value is 0, per the SQL standard
	 */
	public $default = 0;

	/**
	 * Converts the value to an integer.
	 *
	 * @param   mixed  $value
	 * @return  int
	 */
	public function set($value)
	{
		list($value, $return) = $this->_default($value);

		if ( ! $return)
		{
			$value = (int) $value;
		}

		return $value;
	}

} // End Jelly_Core_Field_Integer