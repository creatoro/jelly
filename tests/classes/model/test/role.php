<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Represents a specific role in the database.
 *
 * @package  Jelly
 */
class Model_Test_Role extends Jelly_Model {

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->db(Kohana::config('unittest')->db_connection);

		$meta->fields(array(
			'id'   => Jelly::field('primary'),
			'name' => Jelly::field('string'),
		));
	}

} // End Model_Test_Role