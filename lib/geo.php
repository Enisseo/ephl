<?php
/**
 * Returns the geohash for the latitude & longitude.
 *
 * @param float $latitude
 * @param float $longitude
 * @param int $precision number of figures after the floating point.
 * @return string
 */
function geohash($latitude, $longitude, $precision = 6)
{
	//TODO better hash
	// abcdefgh with
	// a = ~Europe size / continent (10000km)
	// b = ~France (1500km)
	// c = ~Region (500km)
	// d = ~Department (200km)
	// e = ~Paris / Rennes w/ large neighbors (50km)
	// f = ~Rennes (5km)
	// g = ~Quartier (500m)
	// h = ~Street/big building (50m)
	// a,b,c,d,e,f,g,h in [0-9a-z]?
	$mult = pow(10, $precision);

	$binaryLatitude = 0x0;
	$minLat = -90;
	$maxLat = 90;
	$offset = 0;
	do
	{
		$medLat = ($maxLat + $minLat) / 2;
		$binaryLatitude = $binaryLatitude | ((($latitude >= $medLat)? 1: 0) << $offset++);
		if ($latitude >= $medLat)
		{
			$minLat = ceil($medLat * $mult) / $mult;
		}
		else
		{
			$maxLat = floor($medLat * $mult) / $mult;
		}
	} while ($minLat != $maxLat);

	$binaryLongitude = 0x0;
	$minLon = -180;
	$maxLon = 180;
	$offset = 0;
	do
	{
		$medLon = ($maxLon + $minLon) / 2;
		$binaryLongitude = $binaryLongitude | ((($longitude >= $medLon)? 1: 0) << $offset++);
		if ($longitude >= $medLon)
		{
			$minLon = ceil($medLon * $mult) / $mult;
		}
		else
		{
			$maxLon = floor($medLon * $mult) / $mult;
		}
	} while ($minLon != $maxLon);
	
	$geohash = '';
	$interlace1 = $binaryLongitude;
	$interlace2 = $binaryLatitude;
	do {
		$code = 						(($interlace1 & 1) << 4) |
			(($interlace2 & 1) << 3) |	(($interlace1 & 2) << 1) | 
			(($interlace2 & 2) << 0) |	(($interlace1 & 4) >> 2);
		$tmp = $interlace1 >> 3;
		$interlace1 = $interlace2 >> 2;
		$interlace2 = $tmp;
		$geohash .= base_convert(intval($code), 10, 32);
	} while ($interlace1 > 0 && $interlace2 > 0);
	
	$trueGeohash = strtr($geohash, '0123456789abcdefghijklmnopqrstuv', '0123456789bcdefghjkmnpqrstuvwxyz');
	
	return $trueGeohash;
}

define('GEO_UNIT_KILOMETERS', 'kilometers');
define('GEO_UNIT_MILES', 'miles');
function distance($lat1, $lng1, $lat2, $lng2, $unit = GEO_UNIT_KILOMETERS)
{
	$pi80 = M_PI / 180;
	$lat1 *= $pi80;
	$lng1 *= $pi80;
	$lat2 *= $pi80;
	$lng2 *= $pi80;

	$r = 6372.797; // mean radius of Earth in km
	$dlat = $lat2 - $lat1;
	$dlng = $lng2 - $lng1;
	$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$km = $r * $c;

	return ($unit == GEO_UNIT_MILES? ($km * 0.621371192) : $km);
}