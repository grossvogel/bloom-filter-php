<?php
require_once ('cache.php');
require_once ('bit-util-php/bitarray.php');
/**
 * 	implement a bloom filter
 * 	reference: http://pages.cs.wisc.edu/~cao/papers/summary-cache/node8.html
 */
class BloomFilter
{
	private $vector = array ();
	private $vectorSize = 8;
	private $hashes = 8;
	private $algos = array ();
	private $bytesPerHash = 8;
	
	/**
	 * 	supply a vector size in bytes and a hash count
	 */
	public function __construct ($size, $hashes)
	{
		$this->vectorSize = $size;
		$this->hashes = $hashes;
		$this->bytesPerHash = ceil ((log ($this->vectorSize, 2) - 2) / 3);
		echo $this->bytesPerHash . " bytes per hash\n";
		
		$this->vector = new BitArray ($this->vectorSize);
		$this->algos = hash_algos ();
	}
	
	/**
	*	log the existence of a well-formed word from our dictionary
	*/
	public function store ($word)
	{
		$this->iterate_hashes ($word, 'do_store_action');
	}
	
	/**
	*	Check to see if the given word is in the dictionary
	*/
	public function check ($word)
	{
		return $this->iterate_hashes ($word, 'do_check_action');
	}

	/**
	*	If $cache contains a dicationary, load it, otherwise, load it from the word file at $path
	*/
	public function loadDictionary ($path, Cache $cache = null)
	{
		$foundInCache = false;
		$key = null;
		if ($cache != null)
		{
			$key = sprintf ("%s_%d_%d", $path, $this->vectorSize, $this->hashes);
			$vector = $cache->retrieve ($key);
			if ($vector)
			{
				$foundInCache = true;
				$this->vector = $vector;
				//print_r (unpack ("C*", $this->vector->getData ()));	//	this outputs the values status of all the bytes in the vector
			}
		}
		
		if (!$foundInCache)
		{
			$handle = fopen ($path, 'r');
			while (!feof ($handle))
			{
				$word = fgets ($handle);
				if ($word === false)
				{
					break;
				}
				elseif (!$word)
				{
					continue;
				}
				$word = trim ($word);
				$this->store (trim ($word));
			}
			fclose ($handle);
			
			if ($cache != null)
			{
				$cache->store ($key, $this->vector);
			}
		}
	}
	
	private function do_store_action ($position)
	{
		$this->vector[$position] = 1;
		return null;
	}
	
	private function do_check_action ($position)
	{
		if (!$this->vector[$position])
		{
			return false;
		}
		return null;
	}
	
	private function iterate_hashes ($word, $action)
	{
		$hashCount = 0;
		foreach ($this->algos as $algo)
		{
			$pieces = str_split (hash ($algo, $word, true), $this->bytesPerHash);
			foreach ($pieces as $piece)
			{
				if (strlen ($piece) == $this->bytesPerHash)
				{
					$info = unpack ("C*", $piece);
					$position = 0;
					foreach ($info as $index=>$char)
					{
						$position += $char << ($index - 1) * 8;
					}
					
					$ret = call_user_func (array ($this, $action), $position % $this->vectorSize);
					if ($ret !== null)
					{
						return $ret;
					}

					$hashCount++;
					if ($hashCount == $this->hashes)
					{
						return true;
					}
				}
			}
		}
		trigger_error ("ERROR: We ran out of hashes... we only have $hashCount available. Try reducing the number of hashes or the size of the vector.");
	}
}

