<?php

/**
 * Tests the current query URL to the given url model regexp.
 * 
 * @param string the regexp to test the url to.
 * @param array the name of the GET variable to set according to the capturing parenthesis of the url model.
 * @return false|array
 */
function route($urlModel, $params = null)
{
	static $request = null;
	
	if (is_null($request))
	{
		$request = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
		$currentDir = preg_replace('#[/\\\]#', '/', dirname($_SERVER['SCRIPT_NAME']) . '/');
		
		$l = strlen($currentDir);
		$lr = strlen($request);
		$lastPos = strrpos($request, '/');
		while ($lastPos)
		{
			if (substr($currentDir, $l - $lastPos - 1, $lastPos) == substr($request, 0, $lastPos))
			{
				$request = substr($request, $lastPos);
				break;
			}
			$lastPos = strrpos($request, '/', $lastPos - $lr - 1);
		}
	}
	
	$regexp = '@^' . $urlModel . '$@i';
	$result = array();
	if (preg_match($regexp, $request, $result))
	{
		if (is_null($params))
		{
			$params = array();
			for ($i = 1; $i < count($result); $i++)
			{
				$params[] = $i - 1;
			}
		}
		$vars = array();
		$l = count($result) - 1;
		$lp = count($params);
		for ($i = 0; $i < $l && $i < $lp; $i++)
		{
			$vars[$params[$i]] = $result[$i + 1];
		}
		$_GET = array_merge($_GET, $vars);
		return $vars? $vars: true;
	}
	return false;
}
