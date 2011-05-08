<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles passwords by automatically hashing them before they're
 * saved to the database.
 *
 * It is important to note that a new password is hashed in a validation
 * callback. This gives you a chance to validate the password, and have it
 * be hashed after validation.
 *
 * @package    Jelly
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Password extends Jelly_Field_String {

	/**
	 * @var  callback  a valid callback to use for hashing the password or FALSE to not hash
	 */
	public $hash_with = 'sha1';

	/**
	 * Adds a filter that hashes the password.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $column
	 * @return  void
	 */
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		// Add a filter that hashes the password when validating
		$this->filters[] = array(array(':field', 'hash'), array(':value', ':model'));
	}

	/**
	 * Hashes the password only if it's changed.
	 *
	 * @param   string       $password
	 * @param   Jelly_Model  $model
	 * @return  void
	 */
	public function hash($password, Jelly_Model $model)
	{
		// Do we need to hash the password?
		if ( ! empty($password) AND $this->hash_with AND $model->changed($this->name))
		{
			// Hash the password
			return call_user_func($this->hash_with, $password);
		}
	}

} // End Jelly_Core_Field_Password