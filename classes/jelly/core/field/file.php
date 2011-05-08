<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles file uploads.
 *
 * Since this field is ultimately just a varchar in the database, it
 * doesn't really make sense to put rules like Upload::valid or Upload::type
 * on the validation object; if you ever want to NULL out the field, the validation
 * will fail!
 *
 * As such, these
 *
 * @package    Jelly
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Field_File extends Jelly_Field implements Jelly_Field_Supports_Save {

	/**
	 * @var  boolean  whether or not to delete the old file when a new file is added
	 */
	public $delete_old_file = TRUE;

	/**
	 * @var  string  the path to save the file in
	 */
	public $path = NULL;

	/**
	 * @var  array  valid types for the file
	 */
	public $types = array();

	/**
	 * @var  string  the filename that will be saved
	 */
	protected $_filename;

	/**
	 * Ensures there is a path for saving set.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		// Set the path
		$this->path = $this->_check_path($this->path);
	}

	/**
	 * Adds a rule that uploads the file.
	 *
	 * @param   Jelly_Model  $model
	 * @param   string       $column
	 * @return void
	 */
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		// Add a rule to save the file when validating
		$this->rules[] = array(array(':field', '_upload'), array(':validation', ':model', ':field'));
	}

	/**
	 * Implementation for Jelly_Field_Supports_Save.
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  void
	 */
	public function save($model, $value, $loaded)
	{
		return $this->_filename ? $this->_filename : $value;
	}

	/**
	 * Logic to deal with uploading the image file and generating thumbnails according to
	 * what has been specified in the $thumbnails array.
	 *
	 * @param   Validation   $validation
	 * @param   Jelly_Model  $model
	 * @param   Jelly_Field  $field
	 * @return  bool
	 */
	public function _upload(Validation $validation, $model, $field)
	{
		if ($validation->errors())
		{
			// Don't bother uploading
			return FALSE;
		}

		// Get the image from the validation object
		$file = $validation[$field];

		// Check if it's a valid file
		if ( ! is_array($file) OR ! Upload::valid($file) OR ! Upload::not_empty($file))
		{
			// Add error
			$validation->error($field, 'invalid_file');

			return FALSE;
		}

		// Check to see if it's a valid type
		if ($this->types AND ! Upload::type($file, $this->types))
		{
			// Add error
			$validation->error($field, 'invalid_type');

			return FALSE;
		}

		// Sanitize the filename
		$file['name'] = preg_replace('/[^a-z0-9-\.]/', '-', strtolower($file['name']));

		// Strip multiple dashes
		$file['name'] = preg_replace('/-{2,}/', '-', $file['name']);

		// Upload a file?
		if (($filename = Upload::save($file, NULL, $this->path)) !== FALSE)
		{
			// Standardise slashes
			$filename = str_replace('\\', '/', $filename);

			// Chop off the original path
			$value = str_replace($this->path, '', $filename);

			// Ensure we have no leading slash
			if (is_string($value))
			{
				$value = trim($value, '/');
			}

			// Garbage collect
			$this->_delete_old_file($model->original($this->name), $this->path);

			// Set the saved filename
			$this->_filename = $value;
		}
		else
		{
			// Add error
			$validation->error($field, 'invalid_file');

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Checks that a given path exists and is writable and that it has a trailing slash.
	 *
	 * (pulled out into a method so that it can be reused easily by image subclass)
	 *
	 * @param   string  $path
	 * @return  string  the path - making sure it has a trailing slash
	 */
	protected function _check_path($path)
	{
		// Normalize the path
		$path = str_replace('\\', '/', realpath($path));

		// Ensure we have a trailing slash
		if ( ! empty($path) AND is_writable($path))
		{
			$path = rtrim($path, '/').'/';
		}
		else
		{
			throw new Kohana_Exception(get_class($this).' must have a `path` property set that points to a writable directory');
		}

		return $path;
	}

	/**
	 * Deletes the previously used file if necessary.
	 *
	 * @param   string  $filename
	 * @param   string  $path
	 * @return  void
	 */
	protected function _delete_old_file($filename, $path)
	{
		 // Delete the old file if we need to
		if ($this->delete_old_file AND $filename != $this->default)
		{
			// Set the file path
			$path = $path.$filename;

			// Check if file exists
			if (file_exists($path))
			{
				// Delete file
				unlink($path);
			}
		}
	}

} // End Jelly_Core_Field_File