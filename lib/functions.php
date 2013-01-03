<?php
/**
 * Several easy to use all-around functions.
 * 
 * @author Enisseo
 */
 
/**
 * Generates a clean and application-aware URL.
 *
 * <p>Use this function to build URL in your application. Every parameter is optional.
 * Adding a ':' prefix on the URL adds the full domain name to the URL.</p>
 *
 * <p>You should use this function for <strong>EVERY URL</strong> your application might
 * use or display.</p>
 *
 * <p>Some examples:
 * <ul>
 *	<li><code>url()</code> returns the current URL (only the query, no host there)<li>
 *	<li><code>url(false)</code> returns the current URL without URL parameters (a clean one)<li>
 *	<li><code>url(array('a' => 'b'))</code> returns the current URL with the URL parameter 'a' having value 'b'<li>
 *	<li><code>url('file.php?a=b', array('a' => 'c'))</code> returns 'file.php?a=c'<li>
 *	<li><code>url(':/file.php')</code> returns something like 'http://mydomain.com/file.php'<li>
 * </ul>
 * </p>
 *
 * @param string $url the URL to display in the first place (the current URL by default).
 * @param array $parameters optional parameters to add or overwrite (no more parameters by default).
 * @param boolean $keepParameters whether to keep or not the parameters of the given URL before adding the given parameters (true by default).
 * @return <type> 
 */
function url($url = null, $parameters = null, $keepParameters = null)
{
	// mix parameters
	$keepParameters = is_bool($keepParameters)? $keepParameters: (is_bool($parameters)? $parameters: (is_bool($url)? $url: true));
	$parameters = is_array($parameters)? $parameters: (is_array($url)? $url: (is_array($keepParameters)? $keepParameters: array()));
	$url = is_string($url)? $url: (is_string($parameters)? $parameters: (is_string($keepParameters)? $keepParameters: $_SERVER['REQUEST_URI']));
	
	if (preg_match('/^#/', $url))
	{
		$url = $_SERVER['REQUEST_URI'] . $url;
	}

	if (preg_match('/^:/', $url))
	{
		$url = 'http://' . $_SERVER['HTTP_HOST'] . (strlen($url) == 1? $_SERVER['REQUEST_URI']: ((defined('BASE_URL')? BASE_URL: '') . ((substr($url, 1) == '/')? '': substr($url, 1))));
	}
	else
	{
		if (!preg_match('/^\//', $url) && !preg_match('/:\/\//', $url))
		{
			$url = (defined('BASE_URL')? BASE_URL: '') . $url;
		}
		if ($url == '/')
		{
			$url = (defined('BASE_URL')? BASE_URL: '');
		}
	}
	$info = parse_url($url);
	$url = '';
	if (!empty($info['scheme'])) $url .= $info['scheme'] . '://';
	if (!empty($info['host'])) $url .= $info['host'];
	if (!empty($info['port'])) $url .= ':' . $info['port'];
	$url .= $info['path'];
	if ($keepParameters && !empty($info['query']))
	{
		$urlParams = array();
		parse_str($info['query'], $urlParams);
		$parameters = array_merge($urlParams, $parameters);
	}
	$get = array();
	foreach ($parameters as $key => $val)
	{
		if (is_array($val))
		{
			foreach ($val as $aKey => $aVal)
			{
				$get[] = urlencode($key) . '[' . urlencode($aKey) . ']=' . urlencode($aVal);
			}
		}
		elseif (!is_null($val))
		{
			$get[] = urlencode($key) . '=' . urlencode($val);
		}
	}
	$url = $url . (empty($get)? '': '?' . join('&', $get));
	if (!empty($info['fragment'])) $url .= '#' . $info['fragment'];
	return $url;
}

/**
 * Converts a string into an object.
 *
 * @param string $string
 * @return mixed
 */
function str2obj($string, $default = null)
{
	if (empty($string))
	{
		return $default;
	}
	$object = @unserialize($string);
	return (is_null($default) || gettype($object) == gettype($default))? $object: $default;
}

/**
 * Converts any object/array/... into a string.
 *
 * @param mixed $object
 * @return string
 */
function obj2str($object)
{
	return is_null($object)? null: serialize($object);
}

/**
 * Converts a string into a list.
 *
 * @param string $string
 * @return array
 */
function str2list($string)
{
	if (empty($string))
	{
		return array();
	}
	return preg_split('/\|/', $string);
}

/**
 * Converts a list into a string.
 *
 * @param array $list the list should not contain values with character '|'
 * @return string
 */
function list2str($list)
{
	return is_null($list)? '': join('|', $list);
}

/**
 * Joins an array, optionally with keys.
 * 
 * @param array $array the associative array.
 * @param string $lineJoin the join between values
 * @param string $keyJoin the join between the key and the value.
 * @return string the array joined.
 */
function joinKeys($array, $lineJoin = ',', $keyJoin = ':')
{
	$lines = array();
	foreach ($array as $key => $line)
	{
		$lines[] = (!is_numeric($key)? ($key . $keyJoin): '') . $line;
	}
	return join($lines, $lineJoin);
}

/**
 * Tests if the given value belongs to the array values and returns the value, returns the first element of the array otherwise.
 *
 * @param mixed $value the value to test among values.
 * @param array $values the set of possible values.
 * @return mixed the value or the first element of the array of values.
 */
function among($value, $values)
{
	return in_array($value, $values)? $value: (count($values) > 0? $values[0]: null);
}

/**
 * Sort an array with key=>value according to an array of keys in order.
 *
 * Any non matching key will be removed.
 *
 * @param array $arrayWithKeys the array to be sorted.
 * @param array $keysInOrder the list of keys in order.
 * @return array the array sorted.
 */
function sortKeysByArray($arrayWithKeys, $keysInOrder)
{
	$result = array();
	foreach ($keysInOrder as $key)
	{
		if (isset($arrayWithKeys[$key]))
		{
			$result[$key] = $arrayWithKeys[$key];
		}
	}
	return $result;
}

/**
 * Finds a value in the given array according to the given key.
 * The key can have the following format: 'xxx[yyy][zzz]'.
 *
 * @param array $array
 * @param string $key
 * @param mixed $default the default value if the key is not found in the array
 * @return mixed
 * @see setValueToKeyInArray()
 */
function findKeyFromArray(&$array, $key, $default = null)
{
	if (isset($array[$key]))
	{
		return $array[$key];
	}
	else
	{
		$path = preg_split('/(\]\[)|\[|(\]$)/', $key);
		if (count($path) > 1)
		{
			$currentArray = $array;
			while (count($path))
			{
				$element = array_shift($path);
				if (strlen($element))
				{
					if (!isset($currentArray[$element]))
					{
						return $default;
					}
					$currentArray = $currentArray[$element];
				}
			}
			return $currentArray;
		}
	}
	return $default;
}

/**
 * Sets a value in the given array according to the given key.
 * The key can have the following format: 'xxx[yyy][zzz]'.
 *
 * @param array $array
 * @param string $key
 * @param mixed $value
 * @see findKeyFromArray()
 */
function setValueToKeyInArray(&$array, $key, $value)
{
	$path = preg_split('/(\]\[)|\[|(\]$)/', $key);
	$lastKey = array_pop($path);
	if (!strlen($lastKey))
	{
		$lastKey = array_pop($path);
	}
	$currentArray =& $array;
	while (count($path))
	{
		$element = array_shift($path);
		if (strlen($element))
		{
			if (!isset($currentArray[$element]))
			{
				$currentArray[$element] = array();
			}
			$currentArray =& $currentArray[$element];
		}
	}
	$currentArray[$lastKey] = $value;
	$currentArray =& $array;
}


/**
 * Returns a new array with or without the given value.
 *
 * @param array $array
 * @param mixed $value
 * @return if the given array contains the value, a cloned array minus the value, otherwise a cloned array plus the value.
 */
function array_toggle($array, $value)
{
	$newArray = array();
	foreach ($array as $key => $val)
	{
		if ($val != $value)
		{
			$newArray[$key] = $val;
		}
	}
	if (count($newArray) == count($array))
	{
		$newArray[] = $value;
	}
	return $newArray;
}


/**
 * Mix two colors and give the result.
 *
 * @param string $color1 the color as a hex string of 6 characters.
 * @param string $color2 the color to apply, as a hex string of 6 characters.
 * @param float $pc the percentage of color to apply.
 * @return string the hex string of the returned color.
 */
function colorMix($color1, $color2, $pc = 0.5)
{
	$color = '';
	for ($c = 0; $c < 6; $c += 2)
	{
		$comp1Dec = hexdec(substr($color1, $c, 2));
		$comp2Dec = hexdec(substr($color2, $c, 2));
		$color .= sprintf('%02s', dechex(round($comp1Dec * (1 - $pc) + ($comp2Dec * $pc))));
	}
	return $color;
}


/**
 * Formats a date according to a format.
 *
 * @param string $format the format, similar to date().
 * @param mixed $date the date, either a timestamp or a formatted date (Y-m-d)
 * @return string
 */
function formatDate($format, $date)
{
	if (!is_numeric($date))
	{
		if ($date instanceof DateTime)
		{
			$date = $date->format('U');
		}
		else
		{
			$date = strtotime($date);
		}
	}
	if ($date !== false)
	{
		$dateNamesFormats = array('%a', '%A', '%b', '%B');
		$dateNames = preg_split('/,/', strftime(join(',', $dateNamesFormats), $date));
		for ($d = count($dateNamesFormats) - 1; $d >= 0; $d--)
		{
			$format = str_replace($dateNamesFormats[$d], tr($dateNames[$d]), $format);
		}
		return strftime($format, $date);
	}
	return $date;
}


/**
 * Converts a numeric ID into a unique hash code.
 *
 * @param int $id
 * @return string
 */
function id2hash($id)
{
	static $pivots = array(0x4247, 0x4E74, 0x0BB9, 0x8168);
	$hashHex = '';
	$offset = (2 + $id) % 4;
	$hexId = str_pad(dechex($id), 8, '0', STR_PAD_LEFT);
	for ($p = count($pivots) - 1; $p >= 0; $p--)
	{
		$hexIdPart = substr($hexId, ($p + $offset) % 4 * 2, 2);
		$binIdPart = str_pad(base_convert($hexIdPart, 16, 2), 8, '0', STR_PAD_LEFT);
		$extendedBinIdPart = '';
		$binIdPartArray = str_split($binIdPart);
		for ($b = 0; $b < 8; $b++)
		{
			$extendedBinIdPart .= $binIdPartArray[$b] . ((($b % 4) == $offset)? '1': '0');
		}
		$hashDecPart = ($pivots[$p] + bindec($extendedBinIdPart)) % 0xFFFF;
		$hashHex .= str_pad(dechex($hashDecPart), 4, '0', STR_PAD_LEFT);
	}
	
	$hash32 = '';
	for ($hashHexTruncated = $hashHex; strlen($hashHexTruncated); $hashHexTruncated = substr($hashHexTruncated, 0, strlen($hashHexTruncated) - 5))
	{
		$hash32Part = base_convert(substr($hashHexTruncated, -5), 16, 32);
		if (strlen($hashHexTruncated) >= 5)
		{
			$hash32Part = str_pad($hash32Part, 4, '0', STR_PAD_LEFT);
		}
		$hash32 = $hash32Part . $hash32;
	}
	
	return $hash32;
}


/**
 * Converts a hash code into a numeric ID.
 *
 * @param string $hashCode the hash code in base 32.
 * @return int
 */
function hash2id($hash32)
{
	static $pivots = array(0x4247, 0x4E74, 0x0BB9, 0x8168);
	$hexId = '';
	$hexIdArray = array('', '', '', '');
	
	$hashHex = '';
	for ($hash32Truncated = $hash32; strlen($hash32Truncated); $hash32Truncated = substr($hash32Truncated, 0, strlen($hash32Truncated) - 4))
	{
		$hashHexPart = str_pad(base_convert(substr($hash32Truncated, -4), 32, 16), 5, '0', STR_PAD_LEFT);
		$hashHex = $hashHexPart . $hashHex;
	}
	$hashHex = substr(str_pad($hashHex, 16, '0', STR_PAD_LEFT), -16);
	
	$offset = null;
	for ($p = count($pivots) - 1; $p >= 0; $p--)
	{
		$hashDecPart = hexdec(substr($hashHex, $p * 4, 4));
		$extendedBinIdPart = substr(str_pad(decbin((0xFFFF + $hashDecPart - $pivots[3 - $p]) % 0xFFFF), 16, '0', STR_PAD_LEFT), -16);
		$binIdPart = '';
		$extendedBinIdPartArray = str_split($extendedBinIdPart);
		for ($b = 0; $b < 16; $b += 2)
		{
			$binIdPart .= $extendedBinIdPartArray[$b];
			if ($extendedBinIdPartArray[$b + 1])
			{
				if (is_null($offset))
				{
					$offset = ($b / 2) % 4;
				}
				else
				{
					if ($offset != ($b / 2) % 4)
					{
						trigger_error('Invalid hash code!', E_USER_WARNING);
					}
				}
			}
		}
		$hexIdPart = str_pad(base_convert($binIdPart, 2, 16), 2, '0', STR_PAD_LEFT);
		$hexIdArray[(7 + $offset - $p) % 4] = $hexIdPart;
	}
	$hexId = join('', $hexIdArray);
	
	$id = hexdec($hexId);
	
	if ((($id + 2) % 4) != $offset)
	{
		trigger_error('Invalid hash code!', E_USER_WARNING);
	}
	
	return $id;
}


/**
 * Sets or gets the variant part of the URL, i.e. the part between the two last dots.
 *
 * @param string $url
 * @param string $variant the variant part to add to the URL.
 * @return string the URL with variant part if provided, the variant part of the URL otherwise.
 */
function urlVariant($url, $variant = null)
{
	$lastDot = strrpos($url, '.');
	if ($lastDot !== false)
	{
		if (is_null($variant))
		{
			$firstPart = substr($url, 0, $lastDot);
			$previousDot = strrpos($firstPart, '.');
			if ($previousDot !== false)
			{
				return substr($firstPart, $previousDot + 1);
			}
			return '';
		}
		else
		{
			return substr($url, 0, $lastDot) . ($variant? ('.' . $variant): '') . substr($url, $lastDot);
		}
	}
	return false;
}


/**
 * Remove accents and special characters from a string.
 */
function standardize($text)
{
	setlocale(LC_CTYPE, 'utf-8');
	$text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
	return $text;
}

/**
 * Indicates if the string starts with a certain pattern.
 */
function startsWith($str, $start)
{
	return strlen($str) >= strlen($start)? (substr($str, 0, strlen($start)) == $start): false;
}
