<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests for Jelly_Collection.
 *
 * @package Jelly
 * @group   jelly
 * @group   jelly.collection
 */
class Jelly_CollectionTest extends Unittest_Jelly_TestCase {

	/**
	 * Provider for test type
	 */
	public function provider_construction()
	{
		$db = Kohana::config('unittest')->db_connection;
		$result = DB::select()->from('test_posts');
		
		return array(
			array(new Jelly_Collection($result->execute($db), 'Model_Test_Post'), 'Model_Test_Post'),
			array(new Jelly_Collection($result->execute($db), Jelly::factory('test_post')), 'Model_Test_Post'),
			array(new Jelly_Collection($result->execute($db)), FALSE),
			array(new Jelly_Collection($result->as_object()->execute($db)), 'stdClass'),
			array(new Jelly_Collection($result->execute($db), 'Model_Test_Post'), 'Model_Test_Post'),
		);
	}
	
	/**
	 * Tests Jelly_Collections properly handle database results and 
	 * different types of return values.
	 *
	 * @dataProvider  provider_construction
	 */
	public function test_construction($result, $class)
	{
		if (is_string($class))
		{
			$this->assertTrue($result->current() instanceof $class);
		}
		else
		{
			$this->assertTrue(is_array($result->current()));
		}
	}
}