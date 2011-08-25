<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Represents a post in the database.
 *
 * @package  Jelly
 */
class Model_Test_Post extends Jelly_Model {

	public static function initialize(Jelly_Meta $meta)
	{
		$meta->db(Kohana::config('unittest')->db_connection);

		// Posts always load_with an author
		$meta->load_with(array('test_author'));

		// Set fields
		$meta->fields(array(
			'id'              => Jelly::field('primary'),
			'name'            => Jelly::field('string'),
			'slug'            => Jelly::field('slug', array(
				'unique' => TRUE
			)),
			'status'          => Jelly::field('enum', array(
				'choices' => array('draft', 'published', 'review'),
			)),
			'created'         => Jelly::field('timestamp', array(
				'auto_now_create' => TRUE
			)),	
			'updated'         => Jelly::field('timestamp', array(
				'auto_now_update' => TRUE
			)),	

			// Relationships
			'test_author'     => Jelly::field('belongsto'),
			'test_categories' => Jelly::field('manytomany'),

			// Relationship with non-standard naming
			'approved_by'     => Jelly::field('belongsto', array(
				'foreign' => 'test_author.id',
				'column'  => 'approved_by',
			)),

			// Alias columns, for testing
			'_id'             => 'id',
			'_slug'           => 'slug',
		));
	}

} // End Model_Test_Post