<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Represents an author in the database.
 *
 * @package  Jelly
 */
class Model_Test_Author extends Jelly_Model {

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->db(Kohana::config('unittest')->db_connection);

		$meta->fields(array(
			'id'         => Jelly::field('primary'),
			'name'       => Jelly::field('string'),
			'password'   => Jelly::field('password'),
			'email'      => Jelly::field('email'),

			// Relationships
			'test_posts' => Jelly::field('hasmany'),
			'test_post'  => Jelly::field('hasone'),

			// Relationship with non-standard naming
			'permission' => Jelly::field('belongsto', array(
				'foreign' => 'test_role',
				'column'  => 'test_role_id',
			)),
			
			// Aliases for testing
			'_id'        => 'id',
		 ));
	}

} // End Model_Test_Author