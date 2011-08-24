<?php defined('SYSPATH') or die('No direct script access.');

class Database_PDO extends Kohana_Database_PDO
{
	public function connection()
	{
		return $this->_connection;
	}
}
