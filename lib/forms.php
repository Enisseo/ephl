<?php
/**
 * Provide an abstraction for form generation.
 * 
 * @author Enisseo
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php');
 
/**
 * A form, with components and actions.
 */
abstract class Form
{
	protected $method = 'post';
	protected $action = '';
	protected $components = array();
	protected $actions = array();

	/**
	 * Initializes a form with a set of params.
	 */
	public function __construct($params = array())
	{
		foreach ($params as $key => $value)
		{
			$this->$key = $value;
		}
	}

	public function addComponent(FormComponent $component)
	{
		$this->components[] = $component;
		$component->setForm($this);
		return $this;
	}

	public function addComponents($components)
	{
		foreach ($components as $component)
		{
			$this->addComponent($component);
		}
		return $this;
	}

	public function addAction(FormAction $action)
	{
		$this->actions[$action->getName()] = $action;
		return $this;
	}

	public function addActions($actions)
	{
		$this->actions = array_merge($this->actions, $actions);
		return $this;
	}

	/**
	 * Indicates if a form action has been triggered.
	 * 
	 * @return string the name of the action triggered, false otherwise.
	 */
	public function isTriggered(&$data)
	{
		foreach ($this->actions as $action)
		{
			if ($action->isTriggered($data))
			{
				return $action->getName();
			}
		}
		return false;
	}
	
	public function isTriggeredWithRequest()
	{
		if (strtolower($this->method) == 'post')
		{
			return $this->isTriggered($_POST);
		}
		else
		{
			return $this->isTriggered($_GET);
		}
	}

	/**
	 * Populates the fields/components with the given data.
	 * 
	 * @param $data array
	 */
	public function populate(&$data)
	{
		foreach ($this->components as $component)
		{
			$component->populate($data);
		}
	}
	
	public function populateWithRequest()
	{
		if (strtolower($this->method) == 'post')
		{
			$this->populate($_POST);
		}
		else
		{
			$this->populate($_GET);
		}
	}

	/**
	 * Validates a form with its fields.
	 * 
	 * @param array $data the array to populate with validated data.
	 */
	public function validate(&$data)
	{
		$validate = true;
		foreach ($this->components as $component)
		{
			$validate &= $component->validate($data);
		}
		return $validate;
	}
	
	abstract public function render();
}

/**
 * A form component, could be anything in the form (field, fieldset, text...).
 */
abstract class FormComponent
{
	protected $form = null;

	/**
	 * Initializes a component with a set of params.
	 */
	public function __construct($params = array())
	{
		foreach ($params as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	/**
	 * Sets the form.
	 */
	public function setForm(&$form)
	{
		$this->form =& $form;
	}
	
	/**
	 * Populates the component/inner components.
	 */
	abstract public function populate(&$data);
	
	/**
	 * Validates the component/inner components in their current state.
	 * 
	 * @return boolean
	 */
	abstract public function validate(&$data);
	
	/**
	 * Renders the component.
	 */
	abstract public function render();
}


/**
 * A field within a form.
 */
abstract class FormField extends FormComponent
{
	protected $name = null;
	protected $value = null;

	public function populate(&$data)
	{
		$this->value = $data[$this->name];
	}

	public function validate(&$data)
	{
		$data[$this->name] = $this->value;
		return true;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getValue()
	{
		return $this->value;
	}
}

abstract class FormGroup extends FormComponent
{
	protected $components = array();
	
	public function addComponent(FormComponent $component)
	{
		$this->components[] = $component;
		$component->setForm($this);
		return $this;
	}

	public function addComponents($components)
	{
		foreach ($components as $components)
		{
			$this->addComponent($component);
		}
		return $this;
	}

	public function populate(&$data)
	{
		foreach ($this->components as $component)
		{
			$component->populate($data);
		}
	}

	public function validate(&$data)
	{
		$validated = true;
		foreach ($this->components as $component)
		{
			$validated &= $component->validate($data);
		}
		return $validated;
	}
}

/**
 * An action the user can execute on the form (submit button, cancel...).
 */
abstract class FormAction
{
	protected $name = null;
	
	public function __construct($params = array())
	{
		foreach ($params as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	/**
	 * Indicates if the action has been triggered.
	 * 
	 * @param array $data the posted data from the formular.
	 */
	public function isTriggered($data)
	{
		return isset($data[$this->name]);
	}
	
	public function getName()
	{
		return $this->name;
	}
}
