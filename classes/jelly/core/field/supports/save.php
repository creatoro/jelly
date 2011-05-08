<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Declares a field will handle its own saving queries. Nearly all
 * relationship types implement this interface.
 *
 * @package    Jelly
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
interface Jelly_Core_Field_Supports_Save {

	/**
	 * This method is called after an insert or update is finished on
	 * changed in_db fields. The field is expected to handle all database
	 * interaction and save whatever value is passed to it.
	 *
	 * This will only be called if the field has changed somehow.
	 *
	 * Although the model is technically loaded by this time and the
	 * primary key is set and available, $loaded is useful for determining
	 * whether the save() was an INSERT or an UPDATE.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  void
	 */
	public function save($model, $value, $loaded);

} // End Jelly_Core_Field_Supports_Save