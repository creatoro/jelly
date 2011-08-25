<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests timestamp fields.
 *
 * @package Jelly
 * @group   jelly
 * @group   jelly.field
 * @group   jelly.field.timestamp
 */
class Jelly_Field_TimestampTest extends Unittest_TestCase {

	/**
	 * Provider for test_format
	 */
	public function provider_format()
	{
		$field = new Jelly_Field_Timestamp(array('format' => 'Y-m-d H:i:s'));
		
		return array(
			array($field, "2010-03-15 05:45:00", "2010-03-15 05:45:00"),
			array($field, 1268649900, "2010-03-15 05:45:00"),
		);
	}
	
	/**
	 * Tests for issue #113 that ensures timestamps specified 
	 * with a format are converted properly.
	 * 
	 * @dataProvider  provider_format
	 * @link  http://github.com/jonathangeiger/kohana-jelly/issues/113
	 */
	public function test_format($field, $value, $expected)
	{
		$this->assertSame($field->save(NULL, $value, FALSE), $expected);
	}
	
	/**
	 * Tests that timestamp auto create and auto update work as expected.
	 */
	public function test_auto_create_and_update()
	{
		$post = Jelly::factory('test_post')
			->set(array(
				'name' => 'test post',
				'slug' => 'test-post',
			));

		// Save time so we can sanity check the created timestamp
		$time = time();
		$post->save();

		// Test timestamp has been set on create
		$this->assertInternalType('integer', $post->created);
		$this->assertGreaterThanOrEqual($time, $post->created);

		// Store created so we can prove it doesn't change on update
		$created = $post->created;

		sleep(1); // Wait one second to ensure the next tests are valid
		$post->save();

		$this->assertInternalType('integer', $post->updated);
		$this->assertGreaterThan($post->created, $post->updated);
		$this->assertEquals($created, $post->created);

		// Clean up to ensure other tests don't fail
		 $post->delete();
	}
}

