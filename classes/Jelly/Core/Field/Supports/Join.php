<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Declares a field that can be joined using Jelly_Builder's auto_join()
 *
 * The join() method is expected to add a join() and on() clause to the
 * builder to complete the join.
 *
 * @package    Jelly
 * @category   Fields/Interfaces
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
interface Jelly_Core_Field_Supports_Join {

	/**
	 * This method should add a join() and on() clause
	 * to the builder to finish the query.
	 *
	 * For examples, check out belongsTo and HasOne's implementations.
	 *
	 * @param   Jelly_Builder  $builder
	 * @return  void
	 */
	public function join(Jelly_Builder $builder);

} // End Jelly_Core_Field_Supports_Join