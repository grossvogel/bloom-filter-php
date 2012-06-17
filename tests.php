<?php
require 'bloomfilter.php';


define ('HASH_COUNT', 14);
define ('VECTOR_SIZE', pow (2, 21));
define ('DICTIONARY_PATH', '/usr/share/dict/words');
define ('DICTIONARY_SIZE', 98569);		  //	for my /usr/share/dict/words (use wc --lines <path>)
define ('CACHE_PATH', '/home/jason/tmp'); //	obviously, this isn't portable

//	get words made of random characters to test against the filter
function get_random_words ($count, $maxlength)
{
	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$words = array ();
	for ($wordCount = 0; $wordCount < $count; $wordCount++)
	{
		$str = '';
		$length = min(($count % $maxlength) + 4, $maxlength);
		for($i = 0; $i < $length; $i++){
			$index = rand(0,strlen($alphabet) - 1);
			$str .= substr($alphabet, $index, 1);
		}
		$words[] = $str;			
	}
	return $words;
}

$testWords = array ('hello','Abby','john', 'there','change','time','date','week','user','hi','this','that');
$notWords = get_random_words (10000, 8);

$cache = new FileCache (CACHE_PATH);
$filter = new BloomFilter (VECTOR_SIZE, HASH_COUNT);
$filter->loadDictionary (DICTIONARY_PATH, $cache);

$failures = 0;
$tests = 0;
foreach ($testWords as $word)
{
	if (!$filter->check ($word))
	{
		echo "The word $word was not found in the dictionary!\n";
		$failures++;
	}
}

foreach ($notWords as $word)
{
	$tests++;
	if ($filter->check ($word))
	{
		if ($failures < 10)
		{
			echo "The non-word $word was found in the dictionary!\n";
		}
		$failures++;
	}
}

$percent = number_format (($failures / $tests) * 100, 2);
echo "\n\nDictionary Size: " . DICTIONARY_SIZE . "\n";
echo "Vector Size: " . VECTOR_SIZE . "\n";
echo "Ratio: " . number_format (VECTOR_SIZE / DICTIONARY_SIZE, 2) . "\n";
echo "Hashes: " . HASH_COUNT . "\n";
echo "\nNumber of failures: {$failures}, or {$percent}%\n\n";
