<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Declares a field that can be joined using Jelly_Model's with()
 *
 * The with() method is expected to complete the join() clause.
 *
 * @package    Jelly
 * @category   Fields/Interfaces
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
interface Jelly_Core_Field_Supports_With {

	/**
	 * This method should add a join() and on() clause
	 * to the builder to finish the query.
	 *
	 * For examples, check out belongsTo and hasOne's implementations.
	 *
	 * @param   Jelly_Builder  $builder
	 * @return  void
	 */
	public function with($builder);

} // End Jelly_Core_Field_Supports_With