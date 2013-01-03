<?php
/**
 * Manage configuration file.
 * 
 * <p>Configuration variables are stored in a .php file in the format of a $config array.</p>
 * 
 * @author Enisseo
 */
 
/**
 * The Configuration base class.
 */
class Configuration
{
	protected $config = array();
	
	public function __Configuration($file = null)
	{
		if (!empty($file))
		{
			include($file);
			$this->config = $config;
		}
	}
	
	/**
	 * @see loadConfig($file)
	 */
	public function load($file)
	{
		include($file);
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * @see setConfig($key, $value)
	 */
	public function set($key, $value)
	{
		$this->config[$key] = $value;
	}
	
	/**
	 * @see config($key, $default)
	 */
	public function get($key, $default = null)
	{
		return isset($this->config[$key])? $this->config[$key]: $default;
	}
}

function loadConfig($file)
{
	global $_configuration;
	if (empty($_configuration))
	{
		$_configuration = new Configuration();
	}
	$_configuration->load($file);
}

function setConfig($key, $value)
{
	global $_configuration;
	if (empty($_configuration)) user_error('No configuration file loaded with loadConfig()', E_USER_WARNING);
	$_configuration->set($key, $value);
}

function config($key, $default = null)
{
	global $_configuration;
	if (empty($_configuration)) user_error('No configuration file loaded with loadConfig()', E_USER_WARNING);
	return $_configuration->get($key, $default);
}
