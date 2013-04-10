<?php
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'html.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'forms.php');


class DefaultForm extends Form
{
	public function render()
	{
		?>
<form method="<?php echo html($this->method)?>" action="<?php echo html($this->action)?>">
	<?php foreach ($this->components as $component): ?>
	<?php $component->render(); ?>
	<?php endforeach; ?>
	<?php if (!empty($this->actions)): ?>
	<ul class="actions">
		<?php foreach ($this->actions as $action):
			$cssClass = isset($action->cssClass)? $action->cssClass: preg_replace('/[^a-zA-Z]/', '', preg_replace('/([^a-zA-Z]|^)([a-zA-Z])/e', '\'$1\'.strtoupper(\'$2\')', $action->getName())); ?>
		<li class="<?php echo html($cssClass)?>"><?php $action->render(); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</form>
		<?php
	}
}

class FormFieldset extends FormGroup
{
	public $label = null;

	public function render()
	{
		?>
<fieldset>
	<legend><?php echo html($this->label)?></legend>

	<?php foreach ($this->components as $component): ?>
	<?php $component->render(); ?>
	<?php endforeach; ?>
</fieldset>
		<?php
	}
}

class DefaultFormAction extends FormAction
{
	protected $label = null;
	
	public function getLabel()
	{
		return is_null($this->label)? $this->name: $this->label;
	}

	public function render()
	{
		?>
<input class="submit button<?php if (!empty($this->cssClass)): ?> <?php echo html($this->cssClass)?><?php endif; ?>" type="submit" name="<?php echo html($this->name)?>" value="<?php echo html($this->getLabel())?>" />
		<?php
	}
}

class LinkFormAction extends DefaultFormAction
{
	public function render()
	{
		?>
<a class="link button<?php if (!empty($this->cssClass)): ?> <?php echo html($this->cssClass)?><?php endif; ?>" href="<?php echo html($this->name)?>"><?php echo html($this->getLabel())?></a>
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

	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
		if ($valid && $this->required && empty($this->value))
		{
			$this->error = DefaultFormField::$errors['required'];
		}
		return empty($this->error);
	}

	public function render()
	{
		?>
<dl class="property <?php echo html($this->getCssClass())?><?php if (!empty($this->error)): ?> Error<?php endif; ?>">
	<dt class="label"><label for="<?php echo html($this->getId())?>"><?php echo html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?php echo html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label></dt>
	<dd class="field"><?php $this->renderInput(); ?></dd>
	<?php if (!empty($this->error)): ?>
	<dd class="error"><?php echo html($this->error)?></dd>
	<?php elseif (!empty($this->explain)): ?>
	<dd class="explain"><?php echo html($this->explain)?></dd>
	<?php endif; ?>
</dl>
		<?php
	}

	public function renderInput() {}
}

class FormFieldText extends DefaultFormField
{
	public $maxlength = null;
	
	public function renderInput()
	{
		?>
		<input type="text" class="text" tabindex="<?php echo html($this->getTabIndex())?>"
			<?php if (!empty($this->autocomplete)): ?> autocomplete="<?php echo html($this->autocomplete)?>"<?php endif; ?>
			<?php if (!empty($this->maxlength)): ?> maxlength="<?php echo html($this->maxlength)?>"<?php endif; ?>
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldEmail extends DefaultFormField
{
	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
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
		<input type="email" class="email text" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldDate extends DefaultFormField
{
	public $format = 'mm/dd/yy';

	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
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
		<input type="text" title="<?php echo html($this->format)?>" class="date text" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldTime extends DefaultFormField
{
	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
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
		<input type="text" class="time text" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldNumeric extends DefaultFormField
{
	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
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
		<input type="text" class="numeric text" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldCheckbox extends DefaultFormField
{
	public $value = 1;
	public $checked = false;
	protected $defaultValue = null;
	
	public function populate(&$data)
	{
		$this->checked = ($this->value == $data[$this->name]);
		$this->defaultValue = $this->value;
		return parent::populate($data);
	}

	public function render()
	{
		?>
<dl class="property <?php echo html($this->getCssClass())?>">
	<dt class="label">&nbsp;</dt>
	<dd class="field">
		<input type="hidden" name="<?php echo html($this->name)?>" value="" />
		<input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html(is_null($this->defaultValue)? $this->value: $this->defaultValue)?>"
			<?php if ($this->checked): ?>checked="checked" <?php endif; ?>/>
		<label for="<?php echo html($this->getId())?>"><?php echo html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?php echo html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label>
	</dd>
	<?php if (!empty($this->error)): ?>
	<dd class="error"><?php echo html($this->error)?></dd>
	<?php elseif (!empty($this->explain)): ?>
	<dd class="explain"><?php echo $this->explain?></dd>
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
		<textarea tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>"><?php echo html($this->value)?></textarea>
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
		<select tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->getName())?>"<?php if ($this->multiple): ?> multiple="multiple"<?php endif; ?>>
			<?php if (!$this->required && !$this->multiple): ?>
			<option value=""><?php echo html($this->emptyLabel)?></option>
			<?php endif; ?>
			<?php foreach ($this->options as $key => $value): ?>
			<?php if (is_array($value)): ?>
			<optgroup label="<?php echo html($key)?>">
				<?php foreach ($value as $key => $label): ?>
				<option value="<?php echo html($key)?>"<?php if ($this->isSelected($key)):
					?> selected="selected"<?php endif; ?>><?php echo html($label)?></option>
				<?php endforeach; ?>
			</optgroup>
			<?php else: ?>
			<option value="<?php echo html($key)?>"<?php if ($this->isSelected($key)):
				?> selected="selected"<?php endif; ?>><?php echo html($value)?></option>
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
			<li class="group"><span class="label"><?php echo html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
						name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
				name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($value)?></label></li>
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
			<li class="group"><span class="label"><?php echo html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="radio" class="radio" tabindex="<?php echo html($this->getTabIndex())?>"
						name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="radio" class="radio" tabindex="<?php echo html($this->getTabIndex())?>"
				name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($value)?></label></li>
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
			<li class="group"><span class="label"><?php echo html($key)?></span>
				<ul>
				<?php foreach ($value as $key => $label): ?>
					<li><input type="checkbox" class="tag checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
						name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
						<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
						<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($label)?></label></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php else: ?>
			<li><input type="checkbox" class="tag checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
				name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-<?php echo html($key)?>" value="<?php echo html($key)?>"
				<?php if ($this->isSelected($key)): ?> checked="checked"<?php endif; ?> />
				<label for="<?php echo html($this->getId())?>-<?php echo html($key)?>"><?php echo html($value)?></label></li>
			<?php endif; ?>
			<?php endforeach; ?>
		</ul>
		<?php
	}
}