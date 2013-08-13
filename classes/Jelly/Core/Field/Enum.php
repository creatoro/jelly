<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles enumerated lists
 *
 * A choices property is required, which is an array of valid options. If you
 * attempt to set a value that isn't a valid choice, the default will be used.
 *
 * @package    Jelly
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Enum extends Jelly_Field_String {

	/**
	 * @var array An array of valid choices
	 */
	public $choices = array();

	/**
	 * Ensures there is a choices array set.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		if (empty($this->choices))
		{
			// Ensure we have choices to gather values from
			throw new Kohana_Exception(':class must have a `choices` property set', array(
				':class' => get_class($this)));
		}

		if (in_array(NULL, $this->choices))
		{
			// Set allow_null to TRUE if we find a NULL value
			$this->allow_null = TRUE;
		}
		elseif ($this->allow_null)
		{
			// We're allowing NULLs but the value isn't set. Create it so validation won't fail.
			array_unshift($this->choices, NULL);
		}

		// Select the first choice
		reset($this->choices);

		if ( ! Arr::is_assoc($this->choices))
		{
			// Convert non-associative values to associative ones
			$this->choices = array_combine($this->choices, $this->choices);
		}

		if ( ! array_key_exists('default', $options))
		{
			// Set the default value from the first choice in the array
			$this->default = key($this->choices);

			if ($this->choices[$this->default] === NULL)
			{
				// Set the default to NULL instead of using the key which is an empty string for NULL values
				$this->default = NULL;
			}
		}

		// Add a rule to validate that the value is proper
		$this->rules[] = array('array_key_exists', array(':value', $this->choices));
	}

	/**
	 * Selects the default value from the choices if NULLs are not allowed, but
	 * the given value is NULL.
	 *
	 * This is intended to mimic the way MySQL selects the default value if
	 * nothing is given.
	 *
	 * @param   mixed  $value
	 * @return  string
	 */
	public function set($value)
	{
		if ($value === NULL AND ! $this->allow_null)
		{
			// Set value to the default value
			$value = $this->default;
		}

		return parent::set($value);
	}

} // End Jelly_Core_Field_Enum