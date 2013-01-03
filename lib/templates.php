<?php
/**
 * Provides simple template management.
 * 
 * @author Enisseo
 */

/**
 * The base class for templates.
 */
class Template
{
	protected $file = null;
	protected $blocks = array();
	protected $folders = array();
	protected $inherits = null;
	protected $inheritsVars = array();
	protected $lastBlock = array();
	
	/**
	 * Initialize the template with the file and folder(s).
	 * 
	 * @param string $file
	 * @param string... $folders
	 */
	public function __Template()
	{
		$args = func_get_args();
		if (count($args))
		{
			$this->file = array_shift($args);
			$this->folders = $args;
		}
	}
	
	protected function findFile($name)
	{
		foreach ($this->folders as $folder)
		{
			$file = $folder . DIRECTORY_SEPARATOR . $name . (strpos($name, '.') !== false? '': '.php');
			if (file_exists($file))
			{
				return $file;
			}
		}
		user_error('No template found for "' . $name . '"', E_USER_WARNING);
		return false;
	}
	
	public function setTemplate($file)
	{
		$this->file = $file;
	}
	
	/**
	 * Adds folders to look for templates.
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
	 * Adds one folder to look for templates.
	 * 
	 * <p>Folders are looked from the last one to the first one, in order to implements "overwriting".</p>
	 */
	public function addFolder($folder)
	{
		array_unshift($this->folders, $folder);
	}
	
	/**
	 * Indicates that the template inherits a bigger one and defines blocks.
	 * 
	 * @param string $file the name of the file, with or without the .php extension.
	 * @param array $vars additional vars to the inherited template.
	 */
	public function inherits($file, $vars = array())
	{
		$this->inherits = $file;
		$this->inheritsVars = $vars;
	}
	
	/**
	 * Renders the template.
	 */
	public function render($_vars = array())
	{
		if (!empty($this->file))
		{
			foreach ($_vars as $key => $value)
			{
				$$key = $value;
			}
			include($this->findFile($this->file));
		}
		
		if (!empty($this->inherits))
		{
			foreach ($this->inheritsVars as $key => $value)
			{
				$$key = $value;
			}
			include($this->findFile($this->inherits));
		}
	}
	
	/**
	 * Inserts a template file with parameters.
	 */
	public function insert($_file, $_vars = array())
	{
		foreach ($_vars as $key => $value)
		{
			$$key = $value;
		}
		include($this->findFile($_file));
	}
	
	/**
	 * Defines the beginning of a block.
	 */
	public function blockStart($name)
	{
		ob_start();
		array_push($this->lastBlock, $name);
	}
	
	/**
	 * Defines the end of a block.
	 */
	public function blockEnd($name = null)
	{
		$blockContents = ob_get_clean();
		if (empty($name))
		{
			$name = array_pop($this->lastBlock);
		}
		$this->blocks[$name] = $blockContents;
	}
	
	/**
	 * Pritns a defined block.
	 */
	public function block($name)
	{
		print($this->blocks[$name]);
	}
}


function template_folders()
{
	global $_template;
	if (empty($_template))
	{
		$_template = new Template();
	}
	$_template->addFolders(func_get_args());
}

function template_folder($folder)
{
	global $_template;
	if (empty($_template))
	{
		$_template = new Template();
	}
	$_template->addFolder($folder);
}

function template_inherits($file, $vars = array())
{
	global $_template;
	if (empty($_template)) user_error('No template folder specified with template_folder[s]()', E_USER_WARNING);
	$_template->inherits($file, $vars);
	register_shutdown_function('template_render');
}

function template_include($fileName, $vars = array())
{
	global $_template;
	if (empty($_template)) user_error('No template folder specified with template_folder[s]()', E_USER_WARNING);
	$_template->insert($fileName, $vars);
}

function block($blockName)
{
	global $_template;
	if (empty($_template)) user_error('No template folder specified with template_folder[s]()', E_USER_WARNING);
	$_template->block($blockName);
}

function block_start($blockName)
{
	global $_template;
	if (empty($_template)) user_error('No template folder specified with template_folder[s]()', E_USER_WARNING);
	$_template->blockStart($blockName);
}

function block_end($blockName = null)
{
	global $_template;
	if (empty($_template)) user_error('No template folder specified with template_folder[s]()', E_USER_WARNING);
	$_template->blockEnd($blockName);
}

function template_render()
{
	global $_template;
	$_template->render();
}
