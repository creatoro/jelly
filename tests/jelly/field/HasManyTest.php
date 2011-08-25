<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests HasMany fields.
 *
 * @package Jelly
 * @group   jelly
 * @group   jelly.field
 * @group   jelly.field.has_many
 */
class Jelly_Field_HasManyTest extends Unittest_Jelly_TestCase {

	/**
	 * Provider for test_get
	 */
	public function provider_get()
	{
		return array(
			array(Jelly::factory('test_author', 1)->get('test_posts'), 2),
			array(Jelly::factory('test_author', 555)->get('test_posts'), 0),
		);
	}
	
	/**
	 * Tests Jelly_Field_HasMany::get()
	 * 
	 * @dataProvider  provider_get
	 */
	public function test_get($builder, $count)
	{
		$this->assertTrue($builder instanceof Jelly_Builder);
		
		// Select the result
		$result = $builder->select();
		
		// Should now be a collection
		$this->assertEquals(TRUE, $result instanceof Jelly_Collection);
		$this->assertEquals($count, $result->count());
		
		foreach ($result as $row)
		{
			$this->assertGreaterThan(0, $row->id());
			$this->assertTrue($row->loaded());
		}
	}
}

