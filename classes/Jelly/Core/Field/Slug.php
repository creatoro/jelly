<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles "slugs"
 *
 * Slugs are automatically converted.
 *
 * A valid slug consists of lowercase alphanumeric characters, plus
 * underscores, dashes, and forward slashes.
 *
 * @package    Jelly
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_Slug extends Jelly_Field_String {

	/**
	 * @var  boolean  transliterate to ASCII
	 */
	public $ascii_only = TRUE;

	/**
	 * @var  string  separator in slug
	 */
	public $separator = '-';

	/**
	 * @var  string  hierarchy separator in slug, if set you
	 *               can have slugs like my-category/my-subcategory,
	 *               where the hierarchy separator is set to '/'
	 */
	public $hierarchy_separator;

	/**
	 * Converts a slug to value valid for a URL.
	 *
	 * @param    mixed  $value
	 * @return   mixed
	 * @uses     UTF8::transliterate_to_ascii
	 * @credits  Kohana-Team
	 */
	public function set($value)
	{
		list($value, $return) = $this->_default($value);

		if ( ! $return)
		{
			if ($this->ascii_only === TRUE)
			{
				// Transliterate value to ASCII
				$value = UTF8::transliterate_to_ascii($value);
			}

			// Set preserved characters
			$preserved_characters = preg_quote($this->separator);

			// Add hierarchy separator to preserved characters if set
			if ($this->hierarchy_separator)
			{
				$preserved_characters .= preg_quote($this->hierarchy_separator);
			}

			// Remove all characters that are not in preserved characters, a-z, 0-9, or whitespace
			$value = preg_replace('![^'.$preserved_characters.'a-z0-9\s]+!', '', strtolower($value));

			// Remove whitespace around hierarchy separators if hierarchy separator is set
			if ($this->hierarchy_separator)
			{
				$value = preg_replace('/\s*(['.preg_quote($this->hierarchy_separator, '/').'])\s*/', '$1', $value);
			}

			// Replace all separator characters and whitespace by a single separator
			$value = preg_replace('!['.preg_quote($this->separator).'\s]+!u', $this->separator, $value);

			// Trim separators from the beginning and end
			$value = trim($value, $this->separator);

			// Check if hierarchy separator is set
			if ($this->hierarchy_separator)
			{
				// Replace all hierarchy separators by a single hierarchy separator
				$value = preg_replace('!['.preg_quote($this->hierarchy_separator).']+!u', $this->hierarchy_separator, $value);

				// Trim hierarchy separators from the beginning and end
				$value = trim($value, $this->hierarchy_separator);

				// Look for separators again at the beginning and end just in case
				$value = trim($value, $this->separator);
			}
		}

		return $value;
	}

} // End Jelly_Core_Field_Slug