<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Default auth user toke
 *
 * @package	   Kohana/Auth
 * @author	   creatoro
 * @copyright  (c) 2011 creatoro
 * @license	   http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Model_Auth_User_Token extends Jelly_Model {

	public static function initialize(Jelly_Meta $meta)
	{
		// The table the model is attached to
		$meta->table('user_tokens');

		// Fields defined by the model
		$meta->fields(array(
			'id' => Jelly::field('primary'),
			'user_agent' => Jelly::field('string'),
			'token' => Jelly::field('string', array(
				'unique' => TRUE,
			)),
			'type' => Jelly::field('string'),
			'created' => Jelly::field('timestamp', array(
				'auto_now_create' => TRUE,
			)),
			'expires' => Jelly::field('timestamp'),

			// Relationships to other models
			'user' => Jelly::field('belongsto'),
		));
	}

	/**
	 * Handles garbage collection and deleting of expired objects.
	 *
	 * @return	void
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (mt_rand(1, 100) === 1)
		{
			// Do garbage collection
			$this->delete_expired();
		}

		if ($this->expires < time() AND $this->loaded())
		{
			// This object has expired
			$this->delete();
		}
	}

	/**
	 * Deletes all expired tokens.
	 *
	 * @return	Jelly
	 */
	public function delete_expired()
	{
		// Delete all expired tokens
		DB::delete($this->meta()->table())
			->where('expires', '<', time())
			->execute($this->meta()->db());

		return $this;
	}

	/**
	 * Creates a new token.
	 *
	 * @param	array  $data
	 * @return	Jelly_Model
	 */
	public function create_token(array $data)
	{
		// Create the token
		do
		{
			$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
		}
		while ($this->get_token($token)->loaded());

		// Store token in database
		return $this->set(array(
			'user'		 => $data['user_id'],
			'expires'	 => $data['expires'],
			'user_agent' => $data['user_agent'],
			'token'		 => $token,
		))->save();
	}

	/**
	 * Loads a token.
	 *
	 * @param	string	$token
	 * @return	Jelly_Model
	 */
	public function get_token($token)
	{
		return Jelly::query('user_token')->where('token', '=', $token)->limit(1)->select();
	}

} // End Auth User Token Model