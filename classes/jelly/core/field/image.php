<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Handles image uploads and optionally refactors the original image and creates thumbnails of different sizes from the
 * uploaded image (as specified by the $thumbnails array).
 *
 * The original image can be refactored, just like the way each thumbnail is specified as an array with the following
 * properties: path, prefix, resize, crop, quality and driver.
 *
 * In addition any other image refactoring method can be set like this:
 * 'method_name' => array('arg1', 'arg2'), for example 'sharpen' => array(20)
 *
 *
 *  path: the only required property. It must point to a valid, writable directory.
 *  prefix: a thumbnail only property. If set the filename of the thumbnail will be prefixed with the value.
 *  resize: the arguments to pass to Image->resize(). See the documentation for that method for more info.
 *  crop: is the arguments to pass to Image->crop(). See the documentation for that method for more info.
 *  quality: the desired quality of the saved image between 0 and 100.
 *
 * For example:
 *
 *     "thumbnails" => array (
 *         // 1st thumbnail
 *         array(
 *             'path'   => 'upload/images/thumbs/',       // where to save the thumbnails, if not set the original image's path will be used
 * 			   'prefix' => 'thumb_',					  // prefix for the thumbnail filename
 *             'resize' => array(500, 500, Image::AUTO),  // width, height, master dimension
 *             'crop'   => array(100, 100, NULL, NULL),   // width, height, offset_x, offset_y
 *             'quality' => 100,        				  // desired quality of the saved image, default 100
 *             'driver' => 'ImageMagick',                 // NULL defaults to Image::$default_driver
 *         ),
 *         // 2nd thumbnail
 *         array(
 *             // ...
 *         ),
 *     )
 *
 * @package    Jelly
 * @author     Jonathan Geiger, Kelvin Luck
 * @copyright  (c) 2010-2011 Jonathan Geiger, Kelvin Luck
 * @license    http://www.opensource.org/licenses/isc-license.txt
 * @see        Image::resize
 * @see        Image::crop
 */
abstract class Jelly_Core_Field_Image extends Jelly_Field_File {

	/**
	 * @var  array  defaults for saving the original image and thumbnails
	 *
	 */
	protected static $defaults = array(
		// The path to save to
		'path'   => NULL,
		// Prefix for thumbnails
		'prefix'   => NULL,
		 // An array to pass to resize(). e.g. array($width, $height, Image::AUTO)
		'resize' => NULL,
		// An array to pass to crop(). e.g. array($width, $height, $offset_x, $offset_y)
		'crop'   => NULL,
		// The quality of the image
		'quality' => 100,
		// The driver to use, defaults to Image::$default_driver
		'driver' => NULL,
	);

	/**
	 * @var  array  specifications for all of the thumbnails that should be automatically generated when a new image is uploaded
	 *
	 */
	public $thumbnails = array();

	/**
	 * @var  array  allowed file types
	 */
	public $types = array('jpg', 'gif', 'png', 'jpeg');

	/**
	 * Ensures there we have validation rules restricting file types to valid image filetypes and
	 * that the paths for any thumbnails exist and are writable.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		// Merge defaults to prevent array access errors down the line
		$options += Jelly_Field_Image::$defaults;

		parent::__construct($options);

		// Check that all thumbnail directories are writable...
		foreach($this->thumbnails as $key => $thumbnail)
		{
			// Merge defaults to prevent array access errors down the line
			$thumbnail += Jelly_Field_Image::$defaults;

			// Ensure the path is normalized and writable if set
			if ($thumbnail['path'])
			{
				$thumbnail['path'] = $this->_check_path($thumbnail['path']);
			}

			// If no prefix is set but the thumbnail path is the same as the original path throw exception
			if ( ! $thumbnail['prefix'] AND ($thumbnail['path'] === $this->path OR ! $thumbnail['path']))
			{
				throw new Kohana_Exception(':class must have a different `path` or a `prefix` property for thumbnails', array(
					':class' => get_class($this),
				));
			}

			// Merge back in
			$this->thumbnails[$key] = $thumbnail;
		}
	}

	/**
	 * Logic to deal with uploading the image file and generating thumbnails according to
	 * what has been specified in the $thumbnails array.
	 *
	 * @param   Validation   $validation
	 * @param   Jelly_Model  $model
	 * @param   string       $field
	 * @return  void
	 */
	public function _upload(Validation $validation, $model, $field)
	{
		// Save the original untouched
		if ( ! parent::_upload($validation, $model, $field))
		{
			return;
		}

		// Set the filename and the source
		$filename = $this->_filename;
		$source   = $this->path.$filename;

		// Resize, crop or change quality of the original if needed
		if ($this->resize OR $this->crop OR $this->quality < 100)
		{
			// Create an empty array for methods
			$methods = array();

			// Add resize to settings if set
			if ($this->resize)
			{
				$methods['resize'] = $this->resize;
			}

			// Add crop to settings if set
			if ($this->crop)
			{
				$methods['crop'] = $this->crop;
			}

			// Add driver to settings is set
			if ($this->driver)
			{
				$driver = $this->driver;
			}
			else
			{
				$driver = NULL;
			}

			// Resize and crop image
			$this->_refactor($source, $driver, $methods, NULL, $this->quality);
		}

		// Has our source file changed?
		if ($model->changed($field))
		{
			foreach ($this->thumbnails as $thumbnail)
			{
				// Set the destination path
				if ($thumbnail['path'])
				{
					// Use thumbnail path if given
					$destination = $thumbnail['path'];
				}
				else
				{
					// Use original image path
					$destination = $this->path;
				}

				// Delete old file if necessary
				$this->_delete_old_file($thumbnail['prefix'].$model->original($field), $destination);

				// Add filename to destination
				$destination .= $thumbnail['prefix'].$filename;

				// Resize and crop images
				$this->_refactor($source, $thumbnail['driver'], $thumbnail, $destination, $thumbnail['quality']);
			}
		}
	}

	/**
	 * Refactors the images.
	 *
	 * @param   string      $source       the source file
	 * @param   string      $driver       image driver to use
	 * @param   array       $methods      image refactoring methods
	 * @param   string|null $destination  the destination file
	 * @param   int|null    $quality      quality of the saved file
	 * @return  void
	 */
	protected function _refactor($source, $driver, $methods, $destination = NULL, $quality = NULL)
	{
		// Let the Image class do its thing
		$image = Image::factory($source, $driver ? $driver : Image::$default_driver);

		// This little bit of craziness allows us to call resize
		// and crop in the order specifed by the config array.
		foreach ($methods as $method => $args)
		{
			if (($method === 'resize' OR $method === 'crop' OR $method === 'rotate' OR $method === 'flip' OR $method === 'sharpen' OR $method === 'reflection' OR $method === 'watermark' OR $method === 'background') AND $args)
			{
				call_user_func_array(array($image, $method), $args);
			}
		}

		// Save
		$image->save($destination, $quality);
	}

} // End Jelly_Core_Field_Image