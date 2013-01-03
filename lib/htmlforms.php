<?php
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'html.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'forms.php');


class DefaultForm extends Form
{
	public $structure = null;

	public function __construct($data = array())
	{
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	public function populateWithRequest($request)
	{
		return $this->populate($request[$this->method]);
	}
	
	public function isTriggered($request)
	{
		foreach (array_keys($this->actions) as $actionName)
		{
			if (isset($request[$this->method][$actionName]))
			{
				return $actionName;
			}
		}
		return false;
	}


	public function addComponent($component)
	{
		$this->structure[] = $component;
		return $this;
	}

	public function getStructure()
	{
		return (is_null($this->structure)? $this->fields: $this->structure);
	}
	
	public function renderFields()
	{
		$fields = func_get_args();
		if (func_num_args() == 1 && is_array(func_get_arg(0)))
		{
			$fields = func_get_arg(0);
		}
		foreach ($this->fields as $field)
		{
			if (empty($fields) || in_array($field->name, $fields))
			{
				$field->render();
			}
		}
	}
	
	public function renderActions()
	{
		if (!empty($this->actions)): ?>
	<ul class="actions">
		<?php foreach ($this->actions as $actionName => $action):
			$cssClass = isset($action->cssClass)? $action->cssClass: preg_replace('/[^a-zA-Z]/', '', preg_replace('/([^a-zA-Z]|^)([a-zA-Z])/e', '\'$1\'.strtoupper(\'$2\')', $action->name)); ?>
		<li class="<?=html($cssClass)?>"><?php $action->render(); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif;
	}

	public function render()
	{
		?>
<form method="<?=html($this->method)?>" action="<?=html($this->action)?>">
	<?php foreach ($this->getStructure() as $component):
		$component->form = $this; ?>
	<?php $component->render(); ?>
	<?php endforeach; ?>
	<?php $this->renderActions(); ?>
</form>
		<?php
	}
}

class FormFieldset extends FormComponent
{
	public $label = null;
	public $fields = array();

	public function __construct($label = '', $fields = array())
	{
		$this->label = $label;
		$this->fields = $fields;
	}

	public function getFields()
	{
		$fields = array();
		foreach ($this->fields as $field)
		{
			if ($field instanceof FormField)
			{
				$fields[$field->name] = $field;
			}
			else
			{
				$field = $this->form->fields[$field];
				$fields[$field->name] = $field;
			}
		}
		return $fields;
	}

	public function render()
	{
		?>
<fieldset>
	<legend><?=html($this->label)?></legend>

	<?php foreach ($this->getFields() as $field): ?>
	<?php $field->render(); ?>
	<?php endforeach; ?>
</fieldset>
		<?php
	}
}

class DefaultFormAction extends FormAction
{
	public $label = null;
	public $cssClass = null;

	public function __construct($name, $label = null, $cssClass = null)
	{
		$this->name = $name;
		$this->label = $label;
		$this->cssClass = $cssClass;
	}

	public function getLabel()
	{
		return is_null($this->label)? $this->name: $this->label;
	}

	public function render()
	{
		?>
<input class="submit button<?php if (!empty($this->cssClass)): ?> <?=html($this->cssClass)?><?php endif; ?>" type="submit" name="<?=html($this->name)?>" value="<?=html($this->getLabel())?>" />
		<?php
	}
}

class LinkFormAction extends DefaultFormAction
{
	public function render()
	{
		?>
<a class="link button<?php if (!empty($this->cssClass)): ?> <?=html($this->cssClass)?><?php endif; ?>" href="<?=html($this->name)?>"><?=html($this->getLabel())?></a>
		<?php
	}
}

abstract class DefaultFormField extends FormField
{
	static private $fields = array();
	static public $errors = array(
		'required'		=> 'This field is required.',
		'format'		=> 'The values are not in the correct format.',
	);
	static public $labels = array(
		'required'		=> 'mandatory',
	);
	static public $currentTabindex = 0;
	public $id = null;
	public $label = null;
	public $explain = null;
	public $error = null;
	public $required = false;
	public $cssClass = null;
	public $tabindex = null;

	public function __construct($data = array())
	{
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}

	public function getCssClass()
	{
		return !is_null($this->cssClass)? $this->cssClass:
			preg_replace('/[^a-zA-Z]/', '', preg_replace('/([^a-zA-Z]|^)([a-zA-Z])/e', '\'$1\'.strtoupper(\'$2\')', $this->name));
	}

	public function getLabel()
	{
		return is_null($this->label)? $this->name: $this->label;
	}
	
	public function getTabIndex()
	{
		return is_null($this->tabindex)? $this->tabindex = ++DefaultFormField::$currentTabindex: $this->tabindex;
	}

	public function getId()
	{
		if (is_null($this->id))
		{
			do
			{
				$index = 1;
				$this->id = 'form_' . $this->name . ($index == 1? '': '_' . $index);
				$index++;
			} while (isset(DefaultFormField::$fields[$this->id]));
			DefaultFormField::$fields[$this->id] =& $this;
		}
		return $this->id;
	}

	public function validate()
	{
		if ($this->required && empty($this->value))
		{
			$this->error = DefaultFormField::$errors['required'];
		}
		return empty($this->error);
	}

	public function render()
	{
		?>
<dl class="property <?=html($this->getCssClass())?><?php if (!empty($this->error)): ?> Error<?php endif; ?>">
	<dt class="label"><label for="<?=html($this->getId())?>"><?=html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?=html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label></dt>
	<dd class="field"><?php $this->renderInput(); ?></dd>
	<?php if (!empty($this->error)): ?>
	<dd class="error"><?=html($this->error)?></dd>
	<?php elseif (!empty($this->explain)): ?>
	<dd class="explain"><?=html($this->explain)?></dd>
	<?php endif; ?>
</dl>
		<?php
	}

	public function renderInput()
	{
	}
}

class FormFieldText extends DefaultFormField
{
	public $maxlength = null;
	
	public function renderInput()
	{
		?>
		<input type="text" class="text" tabindex="<?=html($this->getTabIndex())?>"
			<?php if (!empty($this->autocomplete)): ?> autocomplete="<?=html($this->autocomplete)?>"<?php endif; ?>
			<?php if (!empty($this->maxlength)): ?> maxlength="<?=html($this->maxlength)?>"<?php endif; ?>
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html($this->value)?>" />
		<?php
	}
}

class FormFieldEmail extends DefaultFormField
{
	public function validate()
	{
		$valid = parent::validate();
		
		if ($valid && (!preg_match('/^[^@]+\@[^\.]+(\.[^\.]+)+$/', $this->value)))
		{
			$this->error = DefaultFormField::$errors['format'];
			$valid = false;
		}
		
		return $valid;
	}
	
	public function renderInput()
	{
		?>
		<input type="email" class="email text" tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html($this->value)?>" />
		<?php
	}
}

class FormFieldDate extends DefaultFormField
{
	public $format = 'mm/dd/yy';

	public function validate()
	{
		$valid = parent::validate();
		
		if ($valid && (!preg_match('/\d{4}\-\d{2}\-\d{2}/', $this->value)))
		{
			$this->error = DefaultFormField::$errors['format'];
			$valid = false;
		}
		
		return $valid;
	}
	
	public function renderInput()
	{
		?>
		<input type="text" title="<?=html($this->format)?>" class="date text" tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html($this->value)?>" />
		<?php
	}
}

class FormFieldTime extends DefaultFormField
{
	public function validate()
	{
		$valid = parent::validate();
		
		if ($valid && (!preg_match('/[0?\d]\:[0?\d]/', $this->value)))
		{
			$this->error = DefaultFormField::$errors['format'];
			$valid = false;
		}
		
		return $valid;
	}
	
	public function renderInput()
	{
		?>
		<input type="text" class="time text" tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html($this->value)?>" />
		<?php
	}
}

class FormFieldNumeric extends DefaultFormField
{
	public function validate()
	{
		$valid = parent::validate();
		
		if ($valid && (!is_numeric($this->value)))
		{
			$this->error = DefaultFormField::$errors['format'];
			$valid = false;
		}
		
		return $valid;
	}

	public function renderInput()
	{
		?>
		<input type="text" class="numeric text" tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html($this->value)?>" />
		<?php
	}
}

class FormFieldCheckbox extends DefaultFormField
{
	public $value = 1;
	public $checked = false;
	protected $defaultValue = null;
	
	public function populate($value)
	{
		$this->checked = ($this->value == $value);
		$this->defaultValue = $this->value;
		return parent::populate($value);
	}

	public function render()
	{
		?>
<dl class="property <?=html($this->getCssClass())?>">
	<dt class="label">&nbsp;</dt>
	<dd class="field">
		<input type="hidden" name="<?=html($this->name)?>" value="" />
		<input type="checkbox" class="checkbox" tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>" value="<?=html(is_null($this->defaultValue)? $this->value: $this->defaultValue)?>"
			<?php if ($this->checked): ?>checked="checked" <?php endif; ?>/>
		<label for="<?=html($this->getId())?>"><?=html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?=html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label>
	</dd>
	<?php if (!empty($this->error)): ?>
	<dd class="error"><?=html($this->error)?></dd>
	<?php elseif (!empty($this->explain)): ?>
	<dd class="explain"><?=$this->explain?></dd>
	<?php endif; ?>
</dl>
		<?php
	}
}

class FormFieldTextarea extends DefaultFormField
{
	public function renderInput()
	{
		?>
		<textarea tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->name)?>" id="<?=html($this->getId())?>"><?=html($this->value)?></textarea>
		<?php
	}
}

abstract class FormFieldMultiple extends DefaultFormField
{
	public $options = array();
	public $multiple = false;

	public function getName()
	{
		return $this->name . ($this->multiple? '[]': '');
	}

	public function isSelected($key)
	{
		return ($this->multiple && is_array($this->value))? in_array($key, $this->value): $key == $this->value;
	}
}

class FormFieldSelect extends FormFieldMultiple
{
	public $emptyLabel = '-';

	public function renderInput()
	{
		?>
		<select tabindex="<?=html($this->getTabIndex())?>"
			name="<?=html($this->getName())?>"<?php if ($this->multiple): ?> multiple="multiple"<?php endif; ?>>
			<?php if (!$this->required && !$this->multiple): ?>
			<option value=""><?=html($this->emptyLabel)?></option>
			<?php endif; ?>
			<?php foreach ($this->options as $key => $value): ?>
			<?php if (is_array($value)): ?>
			<optgroup label="<?=html($key)?>">
				<?php foreach ($value as $key => $label): ?>
				<option value="<?=html($key)?>"<?php if ($this->isSelected($key)):
					?> selected="selected"<?php endif; ?>><?=html($label)?></option>
				<?php endforeach; ?>
			</optgroup>
			<?php else: ?>
			<option value="<?=html($key)?>"<?php if ($this->isSelected($key)):
				?> selected="selected"<?php endif; ?>><?=html($value)?></option>
			<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<?php
	}
}

class FormFieldCheckboxes extends FormFieldMultiple
{
	public $multiple = true;

	public function renderInput()
	{
		?>
		<ul class="checkboxes">
			<?php foreach ($this->options as $key => $value): ?>
			<?php if (is_array($value)): ?>
			<li class="group"><span class="label"><?=html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="checkbox" class="checkbox" tabindex="<?=html($this->getTabIndex())?>"
						name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="checkbox" class="checkbox" tabindex="<?=html($this->getTabIndex())?>"
				name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($value)?></label></li>
			<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}

class FormFieldRadioButtons extends FormFieldMultiple
{
	public $multiple = false;

	public function renderInput()
	{
		?>
		<ul class="radiobuttons">
			<?php foreach ($this->options as $key => $value): ?>
			<?php if (is_array($value)): ?>
			<li class="group"><span class="label"><?=html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="radio" class="radio" tabindex="<?=html($this->getTabIndex())?>"
						name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="radio" class="radio" tabindex="<?=html($this->getTabIndex())?>"
				name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($value)?></label></li>
			<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}

class FormFieldTags extends FormFieldMultiple
{
	public $multiple = true;
	public $value = array();

	public function renderInput()
	{
		?>
		<ul class="tags">
			<?php foreach ($this->options as $key => $value): ?>
			<?php if (is_array($value)): ?>
			<li class="group"><span class="label"><?=html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="checkbox" class="tag checkbox" tabindex="<?=html($this->getTabIndex())?>"
						name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="checkbox" class="tag checkbox" tabindex="<?=html($this->getTabIndex())?>"
				name="<?=html($this->getName())?>" id="<?=html($this->getId())?>-<?=html($key)?>" value="<?=html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?=html($this->getId())?>-<?=html($key)?>"><?=html($value)?></label></li>
			<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}