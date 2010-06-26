<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles belongs to relationships.
 *
 * @package  Jelly
 */
abstract class Jelly_Core_Field_BelongsTo extends Jelly_Field implements Jelly_Field_Supports_With
{
	/**
	 * @var  boolean  Defaults belongs_to's to in the database
	 */
	public $in_db = TRUE;
	
	/**
	 * @var  int  Default to 0 for no relationship
	 */
	public $default = 0;
	
	/**
	 * @var  boolean  Null values are not allowed, 0 represents no record
	 */
	public $allow_null = FALSE;
	
	/**
	 * @var  boolean  Empty values are converted to the default
	 */
	public $convert_empty = TRUE;
	
	/**
	 * @var  int  Empty values are converted to 0, not NULL
	 */
	public $empty_value = 0;

	/**
	 * @var  string  A string pointing to the foreign model and (optionally, a
	 *               field, column, or meta-alias).
	 */
	public $foreign = '';
	
	/**
	 * Constructor
	 *
	 * @param  array  $options 
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);
		
		// Default the empty value to NULL when allow_null is TRUE, but be careful not
		// to override a programmer-configured empty_value
		if ( ! empty($options['allow_null']) AND ! array_key_exists('empty_value', $options))
		{
			$this->empty_value = NULL;
		}
	}

	/**
	 * Automatically sets foreign to sensible defaults
	 *
	 * @param   string  $model
	 * @param   string  $column
	 * @return  void
	 */
	public function initialize($model, $column)
	{
		// Default to the name of the column
		if (empty($this->foreign))
		{
			$this->foreign = $column.'.:primary_key';
		}
		// Is it model.field?
		elseif (FALSE === strpos($this->foreign, '.'))
		{
			$this->foreign = $this->foreign.'.:primary_key';
		}

		// Create an array from them
		$this->foreign = array_combine(array('model', 'field'), explode('.', $this->foreign));

		// Default to the foreign model's primary key
		if (empty($this->column))
		{
			$this->column = $this->foreign['model'].'_id';
		}

		// Column is set and won't be overridden
		parent::initialize($model, $column);
	}

	/**
	 * Returns the primary key of the model passed.
	 *
	 * @param   mixed  $value
	 * @return  mixed
	 */
	public function set($value)
	{
		if (is_object($value))
		{
			$value = $value->id();
		}
		
		list($value, $return) = $this->_default($value);
		
		if ( ! $return)
		{
			$value = ( ! $value OR is_numeric($value)) ? (int) $value : (string) $value;
		}
		
		return $value;
	}

	/**
	 * Returns the jelly model that this model belongs to
	 *
	 * @param   Jelly_Model  $model
	 * @param   mixed        $value
	 * @return  Jelly_Builder
	 */
	public function get($model, $value)
	{
		return Jelly::query($this->foreign['model'])
		            ->where($this->foreign['field'], '=', $value)
		            ->limit(1);
	}

	/**
	 * Implementation of Jelly_Field_Behavior_Joinable
	 *
	 * @param   Jelly_Builder  $builder
	 * @return  void
	 */
	public function with($builder)
	{
		$join_col1 = $this->model.'.'.$this->column;
		// We use this field's alias rather than the foreign model so the join alias can be resolved
		$join_col2 = $this->name.'.'.$this->foreign['field'];

		$builder
			->join(array($this->foreign['model'], Jelly::join_alias($this)), 'LEFT')
			->on($join_col1, '=', $join_col2);
	}
}
