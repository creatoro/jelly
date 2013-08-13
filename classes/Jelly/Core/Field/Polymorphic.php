<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles polymorphic columns
 *
 * A polymorphic column is typically a string that specifies
 * the model to use for the row.
 *
 * @package    Jelly
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Polymorphic extends Jelly_Field_String {

	/**
	 * @var  boolean  this is a polymorphic field
	 */
	public $polymorphic = TRUE;

	/**
	 * Sets the default for the field to the model.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 */
	public function initialize($model, $column)
	{
		$this->default = $model;
	}

	/**
	 * Casts to a string, preserving NULLs along the way.
	 *
	 * @param   mixed   $value
	 * @return  string
	 */
	public function set($value)
	{
		list($value, $return) = $this->_default($value);

		if ( ! $return)
		{
			$value = (string) $value;
		}

		return $value;
	}

} // End Jelly_Core_Field_Polymorphic