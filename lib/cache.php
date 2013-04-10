<?php
/**
 * Provides simple cache management.
 * 
 * @author Enisseo
 */

/**
 * The base class for caching output.
 */
class Cache
{
	protected $folders = array();
	protected $lastBlock = array();
	
	/**
	 * Initialize the cache with the name and folder(s).
	 * 
	 * @param string $name
	 * @param string... $folders
	 */
	public function __construct()
	{
		$args = func_get_args();
		if (count($args))
		{
			$this->folders = $args;
		}
	}
	
	protected function findFile($name)
	{
		foreach ($this->folders as $folder)
		{
			$file = $folder . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name)
				. (strpos($name, '.') !== false? '': '.cache');
			if (file_exists($file))
			{
				return $file;
			}
		}
		return false;
	}
	
	protected function saveFile($name, $cacheData)
	{
		$file = $this->folders[count($this->folders) - 1] . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name) . (strpos($name, '.') !== false? '': '.cache');
		@mkdir(dirname($file), 0777, true);
		file_put_contents($file, $cacheData);
	}
	
	/**
	 * Adds folders to look for cache files.
	 * 
	 * <p>Folders are looked from the last one to the first one, in order to implements "overwriting".</p>
	 * 
	 * @param string... $folders
	 */
	public function addFolders()
	{
		$args = func_get_args();
		foreach ($args as $folder)
		{
			$this->addFolder($folder);
		}
	}
	
	/**
	 * Adds one folder to look for cache files.
	 * 
	 * <p>Folders are looked from the last one to the first one, in order to implements "overwriting".</p>
	 */
	public function addFolder($folder)
	{
		array_unshift($this->folders, $folder);
	}
	
	/**
	 * Defines the beginning of a cache block.
	 */
	public function blockStart($name)
	{
		ob_start();
		array_push($this->lastBlock, $name);
	}
	
	/**
	 * Defines the end of a cache block.
	 */
	public function blockEnd($name = null)
	{
		$blockContents = ob_get_clean();
		if (empty($name))
		{
			$name = array_pop($this->lastBlock);
		}
		$this->saveFile($name, $blockContents);
		print($blockContents);
	}
	
	/**
	 * Loads the cache file with the given name.
	 */
	public function load($name, $ttl = 0)
	{
		if ($file = $this->findFile($name))
		{
			$mtime = filemtime($file);
			if ((time() - $mtime) <= $ttl)
			{
				include($file);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Makes a "load or start" behavior, returning true if not cached.
	 */
	public function notCached($name, $ttl = 0)
	{
		if (!$this->load($name, $ttl))
		{
			$this->blockStart($name);
			return true;
		}
		return false;
	}
}


function cache_folders()
{
	global $_cache;
	if (empty($_cache))
	{
		$_cache = new Cache();
	}
	$_cache->addFolders(func_get_args());
}

function cache_folder($folder)
{
	global $_cache;
	if (empty($_cache))
	{
		$_cache = new Cache();
	}
	$_cache->addFolder($folder);
}

function cache($name, $ttl = 0)
{
	if (!cache_load($name, $ttl))
	{
		cache_start($name);
		return false;
	}
	return true;
}

function cache_load($name, $ttl = 0)
{
	global $_cache;
	if (empty($_cache)) user_error('No cache folder specified with cache_folder[s]()', E_USER_WARNING);
	return $_cache->load($name, $ttl);
}

function cache_start($name)
{
	global $_cache;
	if (empty($_cache)) user_error('No cache folder specified with cache_folder[s]()', E_USER_WARNING);
	$_cache->blockStart($name);
}

function cache_end($name = null)
{
	global $_cache;
	if (empty($_cache)) user_error('No cache folder specified with cache_folder[s]()', E_USER_WARNING);
	$_cache->blockEnd($name);
}
