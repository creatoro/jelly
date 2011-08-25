<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests ManyToMany fields.
 *
 * @package Jelly
 * @group   jelly
 * @group   jelly.field
 * @group   jelly.field.many_to_many
 */
class Jelly_Field_ManyToManyTest extends Unittest_Jelly_TestCase {

	/**
	 * Provider for test_get
	 */
	public function provider_get()
	{
		return array(
			array(Jelly::factory('test_post', 1)->get('test_categories'), 3),
			array(Jelly::factory('test_post', 2)->get('test_categories'), 1),
			array(Jelly::factory('test_post', 555)->get('test_categories'), 0),
		);
	}
	
	/**
	 * Tests Jelly_Field_ManyToMany::get()
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

