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
	 * Hash password before saving if the field is $in_db, and just after if it's not.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @param   bool         $loaded
	 * @return  mixed
	 */
	public function save($model, $value, $loaded)
	{
		return $this->hash($value, $model);
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

		// Return plain password if no hashing is done
		return $password;
	}

} // End Jelly_Core_Field_Password