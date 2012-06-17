<?php
/**
*	a simple interface for a caching mechanism
*/
interface Cache
{
	/**
	 * 	store an item in the cache
	 * 	@param	string	$key
	 * 	@param	mixed	$object
	 */
	public function store ($key, $object);
	
	/**
	 * 	retrieve an item from the cache
	 * 	@param	string	$key
	 * 	@return mixed	the item from the cache, or null if not found
	 */
	public function retrieve ($key);
}

/**
*	a file-based cache
*/
class FileCache implements Cache
{
	private $basePath = '/tmp';
	
	public function __construct ($basePath)
	{
		$this->basePath = rtrim ($basePath, '/');
	}
	
	public function store ($key, $object)
	{
		file_put_contents ($this->getFilePath ($key), serialize ($object));
	}
	
	public function retrieve ($key)
	{
		$path = $this->getFilePath ($key);
		if (file_exists ($path))
		{
			$sValue = file_get_contents ($path);
			return unserialize ($sValue);
		}
		return null;
	}
	
	private function getFilePath ($key)
	{
		$key = ltrim (str_replace (array ('/','\\'), '-', trim($key)), '-');
		return sprintf ("%s/%s", $this->basePath, "$key.cache");
	}
}
