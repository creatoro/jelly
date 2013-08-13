<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles email addresses
 *
 * No special processing is added for this field other
 * than a validation rule that ensures the email address is valid.
 *
 * @package    Jelly
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Email extends Jelly_Field_String {

	/**
	 * Adds an email validation rule if it doesn't already exist.
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 **/
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		if (count($this->rules) > 0)
		{
			// If rules can be found check if the rule for e-mail is set
			foreach ($this->rules as $rule)
			{
				if (is_string($rule[0]) AND $rule[0] === 'email')
				{
					// E-mail rule is set no need to continue
					return;
				}
			}
		}

		// Add the rule for e-mail
		$this->rules[] = array('email');
	}

} // End Jelly_Core_Field_Email