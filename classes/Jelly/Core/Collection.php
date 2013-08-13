<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Jelly Collection
 *
 * Jelly_Collection encapsulates a Database_Result object. It has the exact same API.
 * It offers a few special features that make it useful:
 *
 *  - Only one model is instantiated for the whole result set, which
 *    is significantly faster in terms of performance.
 *  - It is easily extensible, so things like polymorphism and
 *    recursive result sets can be easily implemented.
 *
 * Jelly_Collection likes to know what model its result set is related to,
 * though it's not required. Some features may disappear, however, if
 * it doesn't know the model it's working with.
 *
 * @package    Jelly
 * @category   Query/Result
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Jelly_Core_Collection implements Iterator, Countable, SeekableIterator, ArrayAccess {

	/**
	 * @var  Jelly_Meta  The current meta object, based on the model we're returning
	 */
	protected $_meta = NULL;

	/**
	 * @var  Jelly_Model  The current class we're placing results into
	 */
	protected $_model = NULL;

	/**
	 * @var  Jelly_Collection|array|mixed  The current result set
	 */
	protected $_result = NULL;

	/**
	 * Tracks a database result
	 *
	 * @param  mixed  $model
	 * @param  mixed  $result
	 */
	public function __construct($result, $model = NULL)
	{
		$this->_result = $result;

		// Load our default model
		if ($model AND Jelly::meta($model))
		{
			$this->_model = ($model instanceof Jelly_Model) ? $model : new $model;
			$this->_meta  = $this->_model->meta();
		}
	}

	/**
	 * Converts MySQL Results to Cached Results, since MySQL resources are not serializable.
	 *
	 * @return  array
	 */
	public function __sleep()
	{
		if ( ! $this->_result instanceof Database_Result_Cached)
		{
			$this->_result = new Database_Result_Cached($this->_result->as_array(), '');
		}

		return array_keys(get_object_vars($this));
	}

	/**
	 * Returns a string representation of the collection.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return get_class($this).': '.Jelly::model_name($this->_model).' ('.$this->count().')';
	}

	/**
	 * Returns the collection's meta object, if it exists.
	 *
	 * @return  Jelly_Meta
	 */
	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Return all of the models in array.
	 *
	 * @param   string  $key   column for associative keys
	 * @param   string  $value column for values
	 * @return  array
	 */
	public function as_array($key = NULL, $value = NULL)
	{
		if ($value === NULL)
		{
			$result = array();

			if ($key === NULL)
			{
				foreach ($this as $obj)
				{
					$result[] = $obj;
				}
			}
			else
			{
				foreach ($this as $obj)
				{
					$result[$obj->$key] = $obj;
				}
			}

			return $result;
		}
		else
		{
			return $this->_result->as_array($key, $value);
		}
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  Jelly_Collection
	 */
	public function rewind()
	{
		$this->_result->rewind();
		return $this;
	}

	/**
	 * Implementation of the Iterator interface
	 *
	 * @return  Jelly_Model|array
	 */
	public function current()
	{
		// Database_Result causes errors if you call current()
		// on an object with no results, so we check first.
		if ($this->_result->count())
		{
			$result = $this->_result->current();
		}
		else
		{
			$result = array();
		}

		return $this->_load($result);
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  int
	 */
	public function key()
	{
		return $this->_result->key();
	}

	/**
	 * Implementation of the Iterator interface
	 * @return  Jelly_Collection
	 */
	public function next()
	{
		$this->_result->next();
		return $this;
	}

	/**
	 * Implementation of the Iterator interface
	 *
	 * @return  boolean
	 */
	public function valid()
	{
		return $this->_result->valid();
	}

	/**
	 * Implementation of the Countable interface
	 *
	 * @return  int
	 */
	public function count()
	{
		return $this->_result->count();
	}

	/**
	 * Implementation of SeekableIterator
	 *
	 * @param   mixed  $offset
	 * @return  boolean
	 */
	public function seek($offset)
	{
		return $this->_result->seek($offset);
	}

	/**
	 * ArrayAccess: offsetExists
	 *
	 * @param   mixed  $offset
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return $this->_result->offsetExists($offset);
	}

	/**
	 * ArrayAccess: offsetGet
	 *
	 * @param   mixed  $offset
	 * @param   bool   $object
	 * @return  array|Jelly_Model
	 */
	public function offsetGet($offset, $object = TRUE)
	{
		return $this->_load($this->_result->offsetGet($offset), $object);
	}

	/**
	 * ArrayAccess: offsetSet
	 *
	 * @throws  Kohana_Exception
	 * @param   mixed  $offset
	 * @param   mixed  $value
	 * @return  void
	 */
	final public function offsetSet($offset, $value)
	{
		throw new Kohana_Exception('Jelly results are read-only');
	}

	/**
	 * ArrayAccess: offsetUnset
	 *
	 * @throws  Kohana_Exception
	 * @param   mixed  $offset
	 * @return  void
	 */
	final public function offsetUnset($offset)
	{
		throw new Kohana_Exception('Jelly results are read-only');
	}

	/**
	 * Loads values into the model.
	 *
	 * @param   array  $values
	 * @return  Jelly_Model|array
	 */
	protected function _load($values)
	{
		if ($this->_model)
		{
			$model = clone $this->_model;

			// Don't return models when we don't have one
			return $values
			        ? $model->load_values($values)
			        : $model->clear();
		}

		return $values;
	}

} // End Jelly_Core_Collection