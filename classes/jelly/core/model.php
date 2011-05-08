<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Jelly_Model is the class all models must extend. It handles
 * various CRUD operations and relationships to other models.
 *
 * @package Jelly
 */
abstract class Jelly_Core_Model
{
	/**
	 * @var  array  The original data set on the object
	 */
	protected $_original = array();

	/**
	 * @var  array  Data that's changed since the object was loaded
	 */
	protected $_changed = array();

	/**
	 * @var  array  Data that's already been retrieved is cached
	 */
	protected $_retrieved = array();

	/**
	 * @var  array  Unmapped data that is still accessible
	 */
	protected $_unmapped = array();

	/**
	 * @var  boolean  Whether or not the model is loaded
	 */
	protected $_loaded = FALSE;

	/**
	 * @var  boolean  Whether or not the model is saved
	 */
	protected $_saved = FALSE;

	/**
	 * @var  Jelly_Meta  A copy of this object's meta object
	 */
	protected $_meta = NULL;

	/**
	 * @var  Jelly_Validation  A copy of this object's validation
	 */
	protected $_validation = NULL;

	/**
	 * @var  Boolean  A flag that keeps track of whether or not the model is valid
	 */
	 protected $_valid = FALSE;

	/**
	 * @var  array  With data
	 */
	protected $_with = array();

	/**
	 * Constructor.
	 *
	 * A key can be passed to automatically load a model by its
	 * unique key.
	 *
	 * @param  mixed|null  $key
	 */
	public function __construct($key = NULL)
	{
		// Load the object's meta data for quick access
		$this->_meta = Jelly::meta($this);

		// Copy over the defaults into the original data.
		$this->_original = $this->_meta->defaults();

		// Have an id? Attempt to load it
		if ($key !== NULL)
		{
			$result = Jelly::query($this, $key)
			     ->as_object(FALSE)
			     ->select();

			// Only load if a record is found
			if ($result)
			{
				$this->load_values($result);
			}
		}
	}

	/**
	 * Gets the value of a field.
	 *
	 * Unlike Jelly_Model::get(), values that are returned are cached
	 * until they are changed and relationships are automatically select()ed.
	 *
	 * @see     get()
	 * @param   string  $name The name or alias of the field you're retrieving
	 * @return  mixed
	 */
	public function &__get($name)
	{
		// Alias the field to its actual name. We must do this now
		// so that any aliases will be cached under the real field's
		// name, rather than under its alias name
		if ($field = $this->_meta->field($name))
		{
			$name = $field->name;
		}

		if ( ! array_key_exists($name, $this->_retrieved))
		{
			// Search for with values first
			if ( ! array_key_exists($name, $this->_changed) AND array_key_exists($name, $this->_with))
			{
				$value = Jelly::factory($field->foreign['model'])->load_values($this->_with[$name]);

				// Try and verify that it's actually loaded
				if ( ! $value->id())
				{
					$value->_loaded = FALSE;
					$value->_saved = FALSE;
				}
			}
			else
			{
				$value = $this->get($name);
			}

			// Auto-load relations
			if ($value instanceof Jelly_Builder)
			{
				$value = $value->select($this->_meta->db());
			}

			$this->_retrieved[$name] = $value;
		}

		return $this->_retrieved[$name];
	}

	/**
	 * Sets the value of a field.
	 *
	 * @see     set()
	 * @param   string  $name  The name of the field you're setting
	 * @param   mixed   $value The value you're setting
	 * @return  void
	 */
	public function __set($name, $value)
	{
		// Being set by mysql_fetch_object, store the values for the constructor
		if (empty($this->_original))
		{
			$this->_preload_data[$name] = $value;
			return;
		}

		$this->set($name, $value);
	}

	/**
	 * Passes unknown methods along to the behaviors.
	 *
	 * @param   string  $method
	 * @param   array   $args
	 * @return  mixed
	 **/
	public function __call($method, $args)
	{
		return $this->_meta->events()->trigger('model.call_'.$method, $this, $args);
	}

	/**
	 * Returns true if $name is a field of the model or an unmapped column.
	 *
	 * This does not conform to the standard of returning FALSE if the
	 * property is set but the value is NULL. Rather this acts more like
	 * property_exists.
	 *
	 * @param  string    $name
	 * @return  boolean
	 */
	public function __isset($name)
	{
		return (bool)($this->_meta->field($name) OR array_key_exists($name, $this->_unmapped));
	}

	/**
	 * This doesn't unset fields. Rather, it sets them to their original
	 * value. Unmapped, changed, and retrieved values are unset.
	 *
	 * In essence, unsetting a field sets it as if you never made any changes
	 * to it, and clears the cache if the value has been retrieved with those changes.
	 *
	 * @param   string  $name
	 * @return  void
	 */
	public function __unset($name)
	{
		if ($field = $this->_meta->field($name, TRUE))
		{
			// Ensure changed and retrieved data is cleared
			// This effectively clears the cache and any changes
			unset($this->_changed[$name]);
			unset($this->_retrieved[$name]);
		}

		// We can safely delete this no matter what
		unset($this->_unmapped[$name]);
	}

	/**
	 * Returns a string representation of the model in the
	 * form of `Model_Name (id)` or `Model_Name (NULL)` if
	 * the model is not loaded.
	 *
	 * This is designed to be useful for debugging.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) get_class($this).'('.($this->id() ? $this->id() : 'NULL').')';
	}

	/**
	 * Gets the value for a field.
	 *
	 * Relationships that are returned are raw Jelly_Builders, and must be
	 * execute()d before they can be used. This allows you to chain
	 * extra statements on to them.
	 *
	 * @param   string       $name The field's name
	 * @return  array|mixed
	 */
	public function get($name)
	{
		if ($field = $this->_meta->field($name))
		{
			// Alias the name to its actual name
			$name = $field->name;

			if (array_key_exists($name, $this->_changed))
			{
				$value = $field->get($this, $this->_changed[$name]);
			}
			else
			{
				$value = $this->original($name);
			}

			return $value;
		}
		// Return unmapped data from custom queries
		elseif (isset($this->_unmapped[$name]))
		{
			return $this->_unmapped[$name];
		}
	}

	/**
	 * Returns the original value of a field, before it was changed.
	 *
	 * This method—combined with get(), which first searches for changed
	 * values—is useful for comparing changes that occurred on a model.
	 *
	 * @param   string  $field The field's or alias name
	 * @return  mixed
	 */
	public function original($field)
	{
		if ($field = $this->_meta->field($field))
		{
			// Alias the name to its actual name
			return $field->get($this, $this->_original[$field->name]);
		}
	}

	/**
	 * Returns an array of values in the fields.
	 *
	 * You can pass an array of field names to retrieve
	 * only the values for those fields:
	 *
	 *     $model->as_array(array('id', 'name', 'status'));
	 *
	 * @param   array  $fields
	 * @return  array
	 */
	public function as_array(array $fields = NULL)
	{
		$fields = $fields ? $fields : array_keys($this->_meta->fields());
		$result = array();

		foreach($fields as $field)
		{
			$result[$field] = $this->__get($field);
		}

		return $result;
	}

	/**
	 * Sets the value of a field.
	 *
	 * You can pass an array of key => value pairs
	 * to set multiple fields at the same time:
	 *
	 *    $model->set(array(
	 *        'field1' => 'value',
	 *        'field2' => 'value',
	 *         ....
	 *    ));
	 *
	 * @param   array|string  $values
	 * @param   string        $value
	 * @return  Jelly_Model
	 */
	public function set($values, $value = NULL)
	{
		// Accept set('name', 'value');
		if ( ! is_array($values))
		{
			$values = array($values => $value);
		}

		foreach($values as $key => $value)
		{
			$field = $this->_meta->field($key);

			// If this isn't a field, we just throw it in unmapped
			if ( ! $field)
			{
				$this->_unmapped[$key] = $value;
				continue;
			}

			// Compare the new value with the current value
			// If it's not changed, we don't need to continue
			$value = $field->set($value);
			$current_value = array_key_exists($field->name, $this->_changed)
			               ? $this->_changed[$field->name]
			               : $this->_original[$field->name];

			// Set an empty value to NULL for deleting relationships
			if (($field instanceof Jelly_Field_HasMany OR $field instanceof Jelly_Field_ManyToMany) AND empty($value))
			{
				$value = NULL;
			}

			// Ensure data is really changed
			if ($value === $current_value)
			{
				continue;
			}

			// Data has changed
			$this->_changed[$field->name] = $value;

			// Run filters after it's set as changed
			$this->_changed[$field->name] = $this->run_filter($field, $this->_changed[$field->name]);

			// Invalidate the cache
			if (array_key_exists($field->name, $this->_retrieved))
			{
				unset($this->_retrieved[$field->name]);
			}

			// Model is no longer saved or valid
			$this->_saved = $this->_valid = FALSE;
		}

		return $this;
	}

	/**
	 * Filters a value for a specific column
	 *
	 * @param    string       $field  The column name
	 * @param    string       $value  The value to filter
	 * @return   string
	 * @credits  Kohana Team
	 */
	protected function run_filter($field, $value)
	{
		// Set filters
		$filters = $field->filters;

		// Set the actual field
		$field = $field->name;

		// Bind the field name and model so they can be used in the filter method
		$_bound = array
		(
			':field' => $field,
			':model' => $this,
		);

		foreach ($filters as $array)
		{
			// Value needs to be bound inside the loop so we are always using the
			// version that was modified by the filters that already ran
			$_bound[':value'] = $value;

			// Filters are defined as array($filter, $params)
			$filter = $array[0];
			$params = Arr::get($array, 1, array(':value'));

			foreach ($params as $key => $param)
			{
				if (is_string($param) AND array_key_exists($param, $_bound))
				{
					// Replace with bound value
					$params[$key] = $_bound[$param];
				}
			}

			// Replace bound values for the filter
			if (is_array($filter) AND ($filter[0] == ':model' OR $filter[0] == ':field') AND array_key_exists(':model', $_bound))
			{
				if ($filter[0] == ':model')
				{
					// Replace with bound value
					$filter[0] = $_bound[$filter[0]];
				}
				elseif ($filter[0] == ':field')
				{
					// Set fields
					$_fields = $_bound[':model']->meta()->fields();

					// Replace with bound value
					$filter[0] = $_fields[$field];
				}
			}

			if (is_array($filter) OR ! is_string($filter))
			{
				// This is either a callback as an array or a lambda
				$value = call_user_func_array($filter, $params);
			}
			elseif (strpos($filter, '::') === FALSE)
			{
				// Use a function call
				$function = new ReflectionFunction($filter);

				// Call $function($this[$field], $param, ...) with Reflection
				$value = $function->invokeArgs($params);
			}
			else
			{
				// Split the class and method of the rule
				list($class, $method) = explode('::', $filter, 2);

				// Use a static method call
				$method = new ReflectionMethod($class, $method);

				// Call $Class::$method($this[$field], $param, ...) with Reflection
				$value = $method->invokeArgs(NULL, $params);
			}
		}

		return $value;
	}

	/**
	 * Clears the object and loads an array of values into the object.
	 *
	 * This should only be used for setting from database results
	 * since the model declares itself as saved and loaded after.
	 *
	 * @param   Jelly_Collection|Jelly_Model|array  $values
	 * @return  Jelly_Model
	 */
	public function load_values($values)
	{
		// Clear the object
		$this->clear();

		foreach($values as $key => $value)
		{
			// Key is coming from a with statement
			if (substr($key, 0, 1) === ':')
			{
				// The field comes back as ':model:field',
				// but can have infinite :field parts
				$targets = explode(':', ltrim($key, ':'), 2);

				// Alias as it comes back in, which allows
				// people to use with() with alaised field names
				$relationship = $this->_meta->field(array_shift($targets), TRUE);

				// Find the field we need to set the value as
				$target = implode(':', $targets);

				// If there is no ":" in the target, it is a
				// column, otherwise it's another with()
				if (FALSE !== strpos($target, ':'))
				{
					$target = ':'.$target;
				}

				$this->_with[$relationship][$target] = $value;
			}
			// Standard setting of a field
			elseif ($field = $this->_meta->field($key))
			{
				$this->_original[$field->name] = $field->set($value);
			}
			// Unmapped data
			else
			{
				$this->_unmapped[$key] = $value;
			}
		}

		// Model is now saved and loaded
		$this->_saved = $this->_loaded = TRUE;

		return $this;
	}

	/**
	 * Validates the current model's data
	 *
	 * @throws  Jelly_Validation_Exception
	 * @param   Validation|null   $extra_validation
	 * @return  Jelly_Core_Model
	 */
	public function check($extra_validation = NULL)
	{
		$key = $this->_original[$this->_meta->primary_key()];

		// Determine if any external validation failed
		$extra_errors = ($extra_validation instanceof Validation AND ! $extra_validation->check());

		// For loaded models, we're only checking what's changed, otherwise we check it all
		$data = ($key) ? $this->_changed : $this->_changed + $this->_original;

		// Always build a new validation object
		$this->_validation($data, (bool) $key);

		// Run validation
		if ( ! $this->_valid)
		{
			$array = $this->_validation;

			$this->_meta->events()->trigger('model.before_validate',
				$this, array($this->_validation));

			if (($this->_valid = $array->check()) === FALSE OR $extra_errors)
			{
				$exception = new Jelly_Validation_Exception($this->_meta->model(), $array);

				if ($extra_errors)
				{
					// Merge any possible errors from the external object
					$exception->add_object('_external', $extra_validation);
				}

				throw $exception;
			}

			$this->_meta->events()->trigger('model.after_validate',
				$this, array($this->_validation));
		}
		else
		{
			$this->_valid = TRUE;
		}

		return $this;
	}

	/**
	 * Initializes validation rules, and labels
	 *
	 * @param   array  $data
	 * @param   bool   $update
	 * @return  void
	 */
	protected function _validation($data, $update = FALSE)
	{
		// Build the validation object with its rules
		$this->_validation = Jelly_Validation::factory($data)
			->bind(':model', $this);

		// Add rules and labels
		$this->_validation = $this->_meta->validation_options($this->_validation, $update);
	}

	/**
	 * Creates or updates the current record.
	 *
	 * @param   bool|null        $validation
	 * @return  Jelly_Core_Model
	 */
	public function save($validation = NULL)
	{
		$key = $this->_original[$this->_meta->primary_key()];

		// Run validation
		if ($validation !== FALSE)
		{
			$this->check($validation);
		}

		// These will be processed later
		$values = $saveable = array();

		// Trigger callbacks and ensure we should proceed
		if (FALSE === $this->_meta->events()->trigger('model.before_save', $this))
		{
			return $this;
		}

		// Iterate through all fields in original in case any unchanged fields
		// have save() behavior like timestamp updating...
		foreach ($this->_changed + $this->_original as $column => $value)
		{
			$field = $this->_meta->field($column);

			// Only save in_db values
			if ($field->in_db)
			{
				// See if field wants to alter the value on save()
				$value = $field->save($this, $value, $key);

				// Only set the value to be saved if it's changed from the original
				if ($value !== $this->_original[$column])
				{
					$values[$field->name] = $value;
				}
				// Or if we're INSERTing and we need to set the defaults for the first time
				else if ( ! $key AND ! $this->changed($field->name) AND ! $field->primary)
				{
					$values[$field->name] = $field->default;
				}
			}
			// Field can save itself,
			elseif ($field->supports(Jelly_Field::SAVE) AND $this->changed($column))
			{
				$saveable[$column] = $value;
			}
		}

		// If we have a key, we're updating
		if ($key)
		{
			// Do we even have to update anything in the row?
			if ($values)
			{
				Jelly::query($this, $key)
					 ->set($values)
					 ->update();
			}
		}
		else
		{
			list($id) = Jelly::query($this)
							 ->columns(array_keys($values))
							 ->values(array_values($values))
							 ->insert();

			// Gotta make sure to set this
			$this->_changed[$this->_meta->primary_key()] = $id;
		}

		// Re-set any saved values; they may have changed
		foreach ($values as $column => $value)
		{
			$this->set($column, $value);
		}

		// Set the changed data back as original
		$this->_original = array_merge($this->_original, $this->_changed);

		// We're good!
		$this->_loaded = $this->_saved = TRUE;
		$this->_retrieved = $this->_changed = array();

		// Save the relations
		foreach ($saveable as $field => $value)
		{
			$this->_meta->field($field)->save($this, $value, (bool) $key);
		}

		// Trigger post-save callback
		$this->_meta->events()->trigger('model.after_save', $this);

		return $this;
	}

	/**
	 * Deletes a single record.
	 *
	 * @return  boolean
	 **/
	public function delete()
	{
		$result = FALSE;

		// Are we loaded? Then we're just deleting this record
		if ($this->_loaded)
		{
			$key = $this->_original[$this->_meta->primary_key()];

			// Trigger callbacks to ensure we proceed
			$result = $this->_meta->events()->trigger('model.before_delete', $this);

			if ($result === NULL)
			{
				// Trigger field callbacks
				foreach ($this->_meta->fields() as $field)
				{
					$field->delete($this, $key);
				}

				$result = Jelly::query($this, $key)->delete();
			}
		}

		// Trigger the after-delete
		$this->_meta->events()->trigger('model.after_delete', $this);

		// Clear the object so it appears deleted anyway
		$this->clear();

		return (boolean) $result;
	}

	/**
	 * Removes any changes made to a model.
	 *
	 * This method only works on loaded models.
	 *
	 * @return  Jelly_Model
	 */
	public function revert()
	{
		if ($this->_loaded)
		{
			$this->_loaded =
			$this->_saved  = TRUE;

			$this->_changed   =
			$this->_retrieved = array();
		}

		return $this;
	}

	/**
	 * Sets a model to its original state, as if freshly instantiated
	 *
	 * @return  Jelly_Model
	 */
	public function clear()
	{
		$this->_valid  =
		$this->_loaded =
		$this->_saved  = FALSE;

		$this->_with      =
		$this->_changed   =
		$this->_retrieved =
		$this->_unmapped  = array();

		$this->_original = $this->_meta->defaults();

		return $this;
	}

	/**
	 * Returns whether or not that model is related to the
	 * $model specified. This only works with relationships
	 * where the model "has" other models:
	 *
	 * has_many, many_to_many
	 *
	 * Pretty much anything can be passed for $models, including:
	 *
	 *  * A primary key
	 *  * Another model
	 *  * A Jelly_Collection
	 *  * An array of primary keys or models
	 *
	 * @param   string   $name
	 * @param   mixed    $models
	 * @return  boolean
	 */
	public function has($name, $models)
	{
		$field = $this->_meta->field($name);

		// Don't continue without knowing we have something to work with
		if ($field AND $field->supports(Jelly_Field::HAS))
		{
			return $field->has($this, $models);
		}

		return FALSE;
	}

	/**
	 * Adds a specific model or models to the relationship.
	 *
	 * @param   string      $name
	 * @param   mixed       $models
	 * @return  Jelly_Model
	 */
	public function add($name, $models)
	{
		return $this->_change($name, $models, TRUE);
	}

	/**
	 * Removes a specific model or models to the relationship.
	 *
	 * @param   string       $name
	 * @param   mixed        $models
	 * @return  Jelly_Model
	 */
	public function remove($name, $models)
	{
		return $this->_change($name, $models, FALSE);
	}

	/**
	 * Returns whether or not the model is loaded
	 *
	 * @return  boolean
	 */
	public function loaded()
	{
		return $this->_loaded;
	}

	/**
	 * Whether or not the model is saved
	 *
	 * @return  boolean
	 */
	public function saved()
	{
		return $this->_saved;
	}

	/**
	 * Returns whether or not the particular $field has changed.
	 *
	 * If $field is NULL, the method returns whether or not any
	 * data whatsoever was changed on the model.
	 *
	 * @param   string   $field
	 * @return  boolean
	 */
	public function changed($field = NULL)
	{
		if ($field)
		{
			return array_key_exists($this->_meta->field($field, TRUE), $this->_changed);
		}
		else
		{
			return (bool) $this->_changed;
		}
	}

	/**
	 * Returns the value of the model's primary key
	 *
	 * @return  mixed
	 */
	public function id()
	{
		return $this->get($this->_meta->primary_key());
	}

	/**
	 * Returns the value of the model's name key
	 *
	 * @return  mixed
	 */
	public function name()
	{
		return $this->get($this->_meta->name_key());
	}

	/**
	 * Returns the model's meta object
	 *
	 * @return  Jelly_Meta
	 */
	public function meta()
	{
		return $this->_meta;
	}

	/**
	 * Changes a relation by adding or removing specific records from the relation.
	 *
	 * @param   string      $name    The name of the field
	 * @param   mixed       $models  Models or primary keys to add or remove
	 * @param   string      $add     True to add, False to remove
	 * @return  Jelly_Model
	 */
	protected function _change($name, $models, $add)
	{
		$field = $this->_meta->field($name);

		if ($field AND $field->supports(Jelly_Field::ADD_REMOVE))
		{
			$name = $field->name;
		}
		else
		{
			return $this;
		}

		$current = array();

		// If this is set, we don't need to re-retrieve the values
		if ( ! array_key_exists($name, $this->_changed))
		{
			$current = $this->_ids($this->__get($name));
		}
		else
		{
			$current = $this->_changed[$name];
		}
		$changes = $this->_ids($models);

		// Are we adding or removing?
		if ($add)
		{
			$changes = array_unique(array_merge($current, $changes));
		}
		else
		{
			$changes = array_diff($current, $changes);
		}

		// Set it
		$this->set($name, $changes);

		// Chainable
		return $this;
	}

	/**
	 * Converts different model types to an array of primary keys
	 *
	 * @param   array|mixed  $models
	 * @return  array
	 */
	protected function _ids($models)
	{
		$ids = array();

		// Handle Database Results
		if ($models instanceof Iterator OR is_array($models))
		{
			foreach($models as $row)
			{
				if (is_object($row))
				{
					// Ignore unloaded relations
					if ($row->loaded())
					{
						$ids[] = $row->id();
					}
				}
				else
				{
					$ids[] = $row;
				}
			}
		}
		// And individual models
		elseif (is_object($models))
		{
			// Ignore unloaded relations
			if ($models->loaded())
			{
				$ids[] = $models->id();
			}
		}
		// And everything else
		else
		{
			$ids[] = $models;
		}

		return $ids;
	}
}  // End Jelly_Core_Model