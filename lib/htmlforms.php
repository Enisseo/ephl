<?php
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'html.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'forms.php');


class DefaultForm extends Form
{
	public $cssClass = null;

	public function render()
	{
		?>
<form method="<?php echo html($this->method)?>" action="<?php echo html($this->action)?>" enctype="<?php echo html($this->enctype)?>"<?php if (!empty($this->cssClass)): ?> class="<?php echo html($this->cssClass)?>"<?php endif; ?>>
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
	public $cssClass = null;

	public function render()
	{
		?>
<fieldset<?php if (!empty($this->cssClass)): ?> class="<?php echo html($this->cssClass)?>"<?php endif; ?>>
	<?php if (!empty($this->label)): ?>
	<legend><?php echo html($this->label)?></legend>
	<?php endif; ?>

	<?php foreach ($this->components as $component): ?>
	<?php $component->render(); ?>
	<?php endforeach; ?>
</fieldset>
		<?php
	}
}

class DefaultFormAction extends FormAction
{
	public $label = null;
	public $disabled = false;
	public $tabindex = null;

	public function getLabel()
	{
		return is_null($this->label)? $this->name: $this->label;
	}

	public function getTabIndex()
	{
		return is_null($this->tabindex)? $this->tabindex = ++DefaultFormField::$currentTabindex: $this->tabindex;
	}

	public function render()
	{
		?>
<input class="submit button<?php if ($this->disabled): ?> disabled<?php endif; ?><?php if (!empty($this->cssClass)): ?> <?php echo html($this->cssClass)?><?php endif; ?>" type="submit"
	tabindex="<?php echo html($this->getTabIndex())?>"<?php if ($this->disabled): ?> disabled="disabled"<?php endif; ?> name="<?php echo html($this->name)?>" value="<?php echo html($this->getLabel())?>" />
		<?php
	}
}

class FormActionLink extends DefaultFormAction
{
	public $href = '';
	public $title = '';
	
	public function render()
	{
		?>
<a class="link <?php if (!empty($this->cssClass)): ?> <?php echo html($this->cssClass)?><?php endif; ?>" tabindex="<?php echo html($this->getTabIndex())?>"
	href="<?php echo html($this->href)?>"<?php if (!empty($this->title)): ?> title="<?php echo html($this->title); ?>"<?php endif; ?>><?php echo html($this->getLabel())?></a>
		<?php
	}
}

class FormFieldHtml extends FormComponent
{
	public $html = '';

	public function __construct($html)
	{
		$this->html = $html;
	}
	
	public function populate(&$data) {}
	
	public function validate(&$data) { return true; }
	
	public function render()
	{
		?><?php echo $this->html; ?><?php
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
	public $placeholder = null;
	public $explain = null;
	public $error = null;
	public $required = false;
	public $cssClass = null;
	public $tabindex = null;

	public function getCssClass()
	{
		return !is_null($this->cssClass)? $this->cssClass:
			preg_replace('/[^a-zA-Z0-9]/', '', preg_replace('/([^a-zA-Z]|^)([a-zA-Z0-9])/e', '\'$1\'.strtoupper(\'$2\')', $this->name));
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
			$index = 1;
			do
			{
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
	<dt class="label"><?php $this->renderLabel(); ?></dt>
	<dd class="field"><?php $this->renderInput(); ?></dd>
	<?php if (!empty($this->error)): ?>
	<dd class="error"><p><?php echo html($this->error)?></p></dd>
	<?php elseif (!empty($this->explain)): ?>
	<dd class="explain"><p><?php echo html($this->explain)?></p></dd>
	<?php endif; ?>
</dl>
		<?php
	}
	
	public function renderLabel()
	{
		?><label for="<?php echo html($this->getId())?>"><?php echo html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?php echo html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label><?php
	}

	public function renderInput() {}
}

class FormFieldHidden extends FormField
{
	public function render()
	{
		?>
		<input type="hidden" name="<?php echo html($this->getName())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldText extends DefaultFormField
{
	public $maxlength = null;

	public function renderInput()
	{
		?>
		<input type="text" class="text" tabindex="<?php echo html($this->getTabIndex())?>"
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			<?php if (!empty($this->autocomplete)): ?> autocomplete="<?php echo html($this->autocomplete)?>"<?php endif; ?>
			<?php if (!empty($this->maxlength)): ?> maxlength="<?php echo html($this->maxlength)?>"<?php endif; ?>
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldPassword extends DefaultFormField
{
	public function renderInput()
	{
		?>
		<input type="password" class="text password" tabindex="<?php echo html($this->getTabIndex())?>"
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			<?php if (!empty($this->autocomplete)): ?> autocomplete="<?php echo html($this->autocomplete)?>"<?php endif; ?>
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
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldDate extends DefaultFormField
{
	static public $errors = array(
		'range'			=> 'This value is out of the valid range.',
	);
	
	public $min = null;
	public $max = null;
	public $format = 'mm/dd/yy';

	public function validate(&$data)
	{
		$valid = parent::validate($data);

		if ($valid)
		{
			if (!empty($this->value))
			{
				if (!preg_match('/\d{4}\-\d{2}\-\d{2}/', $this->value))
				{
					$this->error = DefaultFormField::$errors['format'];
				}
			}
			elseif ($this->required)
			{
				$this->error = DefaultFormField::$errors['required'];
			}
			
			if (empty($this->error))
			{
				if (!empty($this->value))
				{
					if ((!is_null($this->min) && $this->value < $this->min)
						|| (!is_null($this->max) && $this->value > $this->max))
					{
						$this->error = FormFieldDate::$errors['range'];
					}
				}
			}
		}

		return empty($this->error);
	}

	public function renderInput()
	{
		?>
		<input type="date" title="<?php echo html($this->format)?>" class="date text" tabindex="<?php echo html($this->getTabIndex())?>"
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			<?php if (!is_null($this->min)): ?>min="<?php echo html($this->min)?>"<?php endif; ?> <?php if (!is_null($this->max)): ?>max="<?php echo html($this->max)?>"<?php endif; ?>
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
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldNumeric extends DefaultFormField
{
	static public $errors = array(
		'range'			=> 'This value is out of the valid range.',
	);
	
	public $min = null;
	public $max = null;
	public $maxlength = null;

	public function validate(&$data)
	{
		$valid = parent::validate($data);

		if ($valid)
		{
			if (!empty($this->value))
			{
				if (!is_numeric($this->value))
				{
					$this->error = DefaultFormField::$errors['format'];
				}
			}
			elseif ($this->required)
			{
				$this->error = DefaultFormField::$errors['required'];
			}
			
			if (empty($this->error))
			{
				if (!empty($this->value))
				{
					if ((!is_null($this->min) && $this->value < $this->min)
						|| (!is_null($this->max) && $this->value > $this->max))
					{
						$this->error = FormFieldNumeric::$errors['range'];
					}
				}
			}
		}

		return empty($this->error);
	}

	public function renderInput()
	{
		?>
		<input type="number" class="numeric text" tabindex="<?php echo html($this->getTabIndex())?>" pattern="[0-9]*"
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
			<?php if (!empty($this->maxlength)): ?> maxlength="<?php echo html($this->maxlength)?>"<?php endif; ?>
			<?php if (!is_null($this->min)): ?>min="<?php echo html($this->min)?>"<?php endif; ?> <?php if (!is_null($this->max)): ?>max="<?php echo html($this->max)?>"<?php endif; ?>
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html($this->value)?>" />
		<?php
	}
}

class FormFieldCheckbox extends DefaultFormField
{
	public $value = 1;
	public $checked = false;
	public $disabled = false;
	protected $defaultValue = null;

	public function populate(&$data)
	{
		if (isset($data[$this->name]))
		{
			$this->checked = ($this->value == $data[$this->name]);
		}
		$this->defaultValue = $this->value;
	}
	
	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
		if ($this->required && !$this->checked)
		{
			$this->error = DefaultFormField::$errors['required'];
			return false;
		}
		
		$data[$this->name] = $this->checked? (is_null($this->defaultValue)? $this->value: $this->defaultValue): '';
		
		return $valid;
	}

	public function renderLabel()
	{
		?>&nbsp;<?php
	}

	public function renderInput()
	{
		?>
		<input type="hidden" name="<?php echo html($this->name)?>" value="" />
		<input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>" value="<?php echo html(is_null($this->defaultValue)? $this->value: $this->defaultValue)?>"
			<?php if ($this->disabled): ?>disabled="disabled" <?php endif; ?>
			<?php if ($this->checked): ?>checked="checked" <?php endif; ?> />
		<label<?php if ($this->disabled): ?> class="disabled"<?php endif; ?> for="<?php echo html($this->getId())?>"><?php echo html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?php echo html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label>
		<?php
	}
}

class FormFieldTextarea extends DefaultFormField
{
	public function renderInput()
	{
		?>
		<textarea tabindex="<?php echo html($this->getTabIndex())?>"
			<?php if (!empty($this->placeholder)): ?> placeholder="<?php echo html($this->placeholder === true? $this->label: $this->placeholder)?>"<?php endif; ?>
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

	public function populate(&$data)
	{
		if (array_key_exists($this->name, $data))
		{
			$this->value = $data[$this->name];
		}
		else
		{
			$this->value = $this->multiple? array(): null;
		}
	}

	public function isSelected($key)
	{
		return ($this->multiple && is_array($this->value))? in_array($key, $this->value): $key == $this->value;
	}
}

class FormFieldSelect extends FormFieldMultiple
{
	public $emptyItem = true;
	public $emptyLabel = '-';

	public function renderInput()
	{
		?>
		<select tabindex="<?php echo html($this->getTabIndex())?>" id="<?php echo html($this->getId())?>"
			name="<?php echo html($this->getName())?>"<?php if ($this->multiple): ?> multiple="multiple"<?php endif; ?>>
			<?php if ($this->emptyItem && !$this->required && !$this->multiple): ?>
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
	
	public function renderLabel()
	{
		?><label><?php echo html($this->getLabel())?>
		<?php if ($this->required): ?><a class="mandatory" href="#" title="<?php echo html(DefaultFormField::$labels['required'])?>">*</a><?php endif; ?></label><?php
	}

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
	public $emptyLabel = 'No tags';

	public function renderInput()
	{
		?>
		<?php if (empty($this->value)): ?>
		<p class="Empty"><?php echo html($this->emptyLabel)?></p>
		<?php else: ?>
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
		<?php endif; ?>
		<?php
	}
}

class FormFieldFile extends DefaultFormField
{
	static public $errors = array(
		'disallow'		=> 'This file path is not allowed.',
		'maxsize'		=> 'The size of the file is higher than the limit.',
		'extension'		=> 'This file extension is not allowed.',
	);
	
	public $folder = '';
	public $multiple = false;
	public $extensions = array();
	public $maxSize = 2000000;
	public $emptyLabel = 'No file yet';

	public function setForm(&$form)
	{
		parent::setForm($form);
		
		if (!empty($this->form))
		{
			$this->form->enctype = 'multipart/form-data';
		}
	}
	
	public function populate(&$data)
	{
		if (array_key_exists($this->name, $data))
		{
			$this->value = $data[$this->name];
		}
		else
		{
			$this->value = array();
		}
		
		$cleanValues = array();
		foreach ((array) $this->value as $value)
		{
			if (!empty($value))
			{
				$cleanValues[] = $value;
			}
		}
		$this->value = $this->multiple? $cleanValues: (empty($cleanValues)? null: array_pop($cleanValues));

		if (isset($data['MAX_FILE_SIZE']))
		{
			if (!empty($_FILES[$this->name]) && !empty($_FILES[$this->name]['tmp_name']))
			{
				$fileInfo = $_FILES[$this->name];
				
				if (is_array($fileInfo['name']))
				{
					$l = count($fileInfo['name']);
					for ($f = 0; $f < $l; $f++)
					{
						if (empty($fileInfo['name'][$f])) continue;
						
						if (!$this->hasValidFileSize($fileInfo['size'][$f]))
						{
							@unlink($fileInfo['tmp_name'][$f]);
							return false;
						}
			
						// Settings
						$fileName = $fileInfo['name'][$f];
			
						if (!$this->hasValidExtension($fileName))
						{
							@unlink($fileInfo['tmp_name'][$f]);
							return false;
						}
						
						$this->createFolder();
			
						$fileName = $this->cleanFileName($fileName);
			
						$fileName = $this->getUniqueFileName($fileName);
			
						$filePath = $this->folder . $fileName;
						move_uploaded_file($fileInfo['tmp_name'][$f], $filePath);
						imageautorotate($filePath);
			
						$this->value[] = $fileName;
					}
				}
				else
				{
					if (!$this->hasValidFileSize($fileInfo['size']))
					{
						@unlink($fileInfo['tmp_name']);
						return false;
					}
		
					// Settings
					$fileName = $fileInfo['name'];
		
					if (!$this->hasValidExtension($fileName))
					{
						@unlink($fileInfo['tmp_name']);
						return false;
					}
					
					$this->createFolder();
		
					$fileName = $this->cleanFileName($fileName);
		
					$fileName = $this->getUniqueFileName($fileName);
		
					$filePath = $this->folder . $fileName;
					move_uploaded_file($fileInfo['tmp_name'], $filePath);
					imageautorotate($filePath);
		
					$this->value = $fileName;
				}
				unset($_FILES[$this->name]);
			}
		}
	}

	public function getName()
	{
		return $this->name . ($this->multiple? '[]': '');
	}
	
	protected function createFolder()
	{
		if (!file_exists($this->folder) || !is_dir($this->folder))
		{
			@mkdir($this->folder, 0777, true);
		}
	}

	protected function cleanFileName($fileName)
	{
		return standardize(preg_replace('/[^\w\._]+/', '_', $fileName));
	}
	
	protected function hasValidFileSize($fileSize)
	{
		if ($this->maxSize && $fileSize > $this->maxSize)
		{
			$this->error = FormFieldFile::$errors['maxsize'];
			return false;
		}
		return true;
	}

	protected function hasValidExtension($fileName)
	{
		if (!empty($this->extensions))
		{
			$fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
			if (!in_array($fileType, $this->extensions))
			{
				$this->error = FormFieldFile::$errors['extension'];
				return false;
			}
		}
		return true;
	}
	
	protected function getUniqueFileName($fileName)
	{
		if (file_exists($this->folder . $fileName))
		{
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);
				
			$count = 1;
			while (file_exists($this->folder . $fileName_a . '_' . $count . $fileName_b))
			{
				$count++;
			}
				
			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}
		return $fileName;
	}

	public function validate(&$data)
	{
		$valid = parent::validate($data);
		
		if ($valid)
		{
			if ($this->required)
			{
				if (empty($this->value))
				{
					$this->error = DefaultFormField::$errors['required'];
				}
			}
			
			if (empty($this->error))
			{
				foreach ((array) $this->value as $value)
				{
					if (startsWith($value, $this->folder))
					{
						$this->error = FormFieldFile::$errors['disallow'];
						break;
					}
				}
			}
		}

		return empty($this->error);
	}

	public function renderInput()
	{
		?>
		<?php if (!empty($this->value)): ?>
			<?php foreach ((array) $this->value as $value): ?>
			<div class="file">
				<?php $this->renderFile($value); ?>
			</div>
			<?php endforeach; ?>
		<?php else: ?>
		
		<input type="hidden" name="<?php echo html($this->name)?>" value="" id="<?php echo html($this->getId())?>-hidden" />
		<p class="file empty"><?php echo html($this->emptyLabel)?></p>
		<?php endif; ?>
		
		<div class="file-upload" id="<?php echo html($this->getId())?>">
			<?php $this->renderFileUpload(); ?>
		</div>
		<?php
	}

	protected function renderFile($value)
	{
		static $index = 0;
		?>
		<input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-file-<?php echo html($index)?>" value="<?php echo html($value)?>" checked="checked" />
		<label for="<?php echo html($this->getId())?>-file-<?php echo html($index++)?>"><?php echo html($value)?></label>
		<?php
	}

	protected function renderFileUpload()
	{
		?>
		<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo html($this->maxSize)?>" />
		<input type="file" class="file" tabindex="<?php echo html($this->getTabIndex())?>" 
			id="<?php echo html($this->getId())?>" name="<?php echo html($this->getName())?>" />
		<?php
	}
}

class FormFieldImage extends FormFieldFile
{
	public $extensions = array('jpg', 'jpeg', 'gif', 'png');
	public $emptyLabel = 'No image yet';
	
	public $imageBaseUrl = '';
	public $alt = '';

	protected function renderFile($value)
	{
		static $index = 0;
		?>
		<input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->name)?>" id="<?php echo html($this->getId())?>-image-<?php echo html($index)?>" value="<?php echo html($value)?>" checked="checked" />
		<label for="<?php echo html($this->getId())?>-image-<?php echo html($index++)?>"><img src="<?php echo html($this->imageBaseUrl . $value)?>" alt="<?php echo html($this->alt); ?>" /></label>
		<?php
	}
}

class FormFieldPlupload extends FormFieldFile
{
	static protected $hasLoadedPlupload = false;
	
	public $pluploadUrl = '';
	public $ajaxUploadUrl = false;
	public $uploadLabel = 'Upload file';
	public $orLabel = 'or';
	public $dropLabel = 'Drop file here';
	public $removeLabel = 'Remove uploaded file';
	public $filterLabel = 'Files';
	
	public function __construct($data = array())
	{
		parent::__construct($data);
		
		if (!empty($_GET['ajaxupload']) && $_GET['ajaxupload'] == $this->name)
		{
			// HTTP headers for no cache etc
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");

			// Get parameters
			$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
			$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
			$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
			
			if (!$this->hasValidExtension($fileName))
			{
				die(json_encode(array('jsonrpc' => '2.0', 'error' => array('code' => 104, 'message' => $this->error))));
			}

			$this->createFolder();

			$fileName = $this->cleanFileName($fileName);

			$fileName = $this->getUniqueFileName($fileName);

			$filePath = $this->folder . $fileName;

			// Look for the content type header
			if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			{
				$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
			}

			if (isset($_SERVER["CONTENT_TYPE"]))
			{
				$contentType = $_SERVER["CONTENT_TYPE"];
			}

			// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
			if (strpos($contentType, "multipart") !== false)
			{
				if (isset($_FILES[$this->name]['tmp_name']) && is_uploaded_file($_FILES[$this->name]['tmp_name']))
				{
					// Open temp file
					$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
					if ($out)
					{
						// Read binary input stream and append it to temp file
						$in = fopen($_FILES[$this->name]['tmp_name'], "rb");

						if ($in)
						{
							while ($buff = fread($in, 4096))
							{
								fwrite($out, $buff);
							}
						}
						else
						{
							die('error:Failed to open input stream.');
						}
						fclose($in);
						fclose($out);
						
						if (!$this->hasValidFileSize(filesize("{$filePath}.part")))
						{
							@unlink("{$filePath}.part");
							die('error:' . $this->error);
						}
						
						@unlink($_FILES[$this->name]['tmp_name']);
					}
					else
					{
						die('error:Failed to open output stream.');
					}
				}
				else
				{
					die('error:Failed to move uploaded file.');
				}
			}
			else
			{
				// Open temp file
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out)
				{
					// Read binary input stream and append it to temp file
					$in = fopen("php://input", "rb");

					if ($in)
					{
						while ($buff = fread($in, 4096))
						{
							fwrite($out, $buff);
						}
					}
					else
					{
						die('error:Failed to open input stream.');
					}

					fclose($in);
					fclose($out);
						
					if (!$this->hasValidFileSize(filesize("{$filePath}.part")))
					{
						@unlink("{$filePath}.part");
						die('error:' . $this->error);
					}
				}
				else
				{
					die('error:Failed to open output stream.');
				}
			}

			// Check if file has been uploaded
			if (!$chunks || $chunk == $chunks - 1)
			{
				if (!$this->hasValidFileSize(filesize("{$filePath}.part")))
				{
					@unlink("{$filePath}.part");
					die(json_encode(array('jsonrpc' => '2.0', 'error' => array('code' => 105, 'message' => $this->error))));
				}
					
				// Strip the temp .part suffix off 
				rename("{$filePath}.part", $filePath);
				imageautorotate($filePath);

				// Return JSON-RPC response
				die($fileName);
			}
		}
	}
	
	public function renderInput()
	{
		if (!FormFieldPlupload::$hasLoadedPlupload): FormFieldPlupload::$hasLoadedPlupload = true; ?>
		<script type="text/javascript" src="<?php echo html($this->pluploadUrl); ?>plupload.full.min.js"></script>
		<?php endif; ?>
		
		<input type="hidden" name="<?php echo html($this->getName())?>" value="" id="<?php echo html($this->getId())?>-hidden" />
		
		<div id="<?php echo html($this->getId())?>-container">
			<p class="file empty" id="<?php echo html($this->getId())?>-emptyLabel"<?php if (!empty($this->value)): ?> style="display:none"<?php endif; ?>><?php echo html($this->emptyLabel); ?></p>
			<ol class="files" id="<?php echo html($this->getId())?>-files">
			<?php if (!empty($this->value)): ?>
				<?php foreach ((array) $this->value as $value): ?>
				<li class="file">
				<?php $this->renderFile($value); ?>
				</li>
				<?php endforeach; ?>
			<?php endif; ?>
			</ol>
		</div>
		
		<div class="file-upload" id="<?php echo html($this->getId())?>-drop">
			<p class="file drop" id="<?php echo html($this->getId())?>-dropLabel" style="display:none"><?php echo html($this->dropLabel); ?>
				<span class="or"><?php echo html($this->orLabel)?></span></p>
			
			<?php $this->renderFileUpload(); ?>
			
			<button class="button label" id="<?php echo html($this->getId())?>-browseButton"><?php echo html($this->uploadLabel)?></button>
			<?php if (!$this->required): ?>
			<button class="button remove" style="display:none" id="<?php echo html($this->getId())?>-removeButton"><?php echo html($this->removeLabel)?></button>
			<?php endif; ?>
		</div>
		
		<?php $this->renderPluploadInit(); ?>
		<?php
	}
	
	protected function getPluploadOptions()
	{
		$pluploadOptions = array(
			'runtimes'			=> 'html5,gears,flash,silverlight',
			'flash_swf_url'		=> $this->pluploadUrl . 'Moxie.swf',
			'silverlight_xap_url' => $this->pluploadUrl . 'Moxie.xap',
			'browse_button'		=> $this->getId() . '-browseButton',
			'container'			=> $this->getId() . '-drop',
			'drop_element'		=> $this->getId() . '-drop',
			'file_data_name'	=> $this->name,
			'max_file_size'		=> $this->maxSize . 'b',
			'url'				=> url($this->ajaxUploadUrl, array('ajaxupload' => $this->name)),
			'chunk_size'		=> 512 * 1024,
			'multi_selection'	=> $this->multiple? 1: 0,
		);
		if (!empty($this->extensions))
		{
			$pluploadOptions['filters'] = array(
				array('title' => $this->filterLabel, 'extensions' => join(',', $this->extensions)),
			);
		}
		return $pluploadOptions;
	}

	protected function renderPluploadInit()
	{
		?>
		<script type="text/javascript">
		(function() {
			var fileInput = document.getElementById('<?php echo html($this->getId()); ?>')
			var inputHidden = document.getElementById('<?php echo html($this->getId() . '-hidden'); ?>')
			var initValue = inputHidden.value;
			var browseButton = document.getElementById('<?php echo html($this->getId() . '-browseButton'); ?>');
			<?php if (!$this->required): ?>
			var removeButton = document.getElementById('<?php echo html($this->getId() . '-removeButton'); ?>');
			<?php endif; ?>
			var container = document.getElementById('<?php echo html($this->getId() . '-container'); ?>');
			var files = document.getElementById('<?php echo html($this->getId() . '-files'); ?>');
			var drop = document.getElementById('<?php echo html($this->getId() . '-drop'); ?>');
			var dropLabel = document.getElementById('<?php echo html($this->getId() . '-dropLabel'); ?>')
			var emptyLabel = document.getElementById('<?php echo html($this->getId() . '-emptyLabel'); ?>')
			var uploader = new plupload.Uploader(<?php echo json_encode($this->getPluploadOptions()); ?>);
			<?php $this->renderPluploadBindings(); ?>
			document.addEventListener('DOMContentLoaded', function() {
				uploader.init();
			});
		})();
		</script>
		<?php
	}

	protected function renderPluploadBindings()
	{
		?>
		uploader.bind('Init', function(up) { <?php $this->renderPluploadInitListener(); ?> });
		uploader.bind('PostInit', function(up) { <?php $this->renderPluploadPostInitListener(); ?> });
		uploader.bind('Error', function(up, error) { <?php $this->renderPluploadErrorListener(); ?> });
		uploader.bind('FilesAdded', function(up, filesAdded) { <?php $this->renderPluploadFilesAddedListener(); ?> });
		uploader.bind('QueueChanged', function(up) { <?php $this->renderPluploadQueueChangedListener(); ?> });
		uploader.bind('UploadProgress', function(up, file) { <?php $this->renderPluploadUploadProgressListener(); ?> });
		uploader.bind('UploadComplete', function(up, filesCompleted) { <?php $this->renderPluploadUploadCompleteListener(); ?> });
		uploader.bind('FileUploaded', function(up, file, response) { <?php $this->renderPluploadFileUploadedListener(); ?> });
		<?php if (!$this->required): ?>
		removeButton.onclick = function(evt) { evt.preventDefault(); var up = uploader; <?php $this->renderPluploadRemoveFileListener(); ?> return false; };
		<?php endif; ?>
		<?php
	}
	
	protected function renderPluploadInitListener()
	{
		?>
		fileInput.style.display = 'none';
		<?php
	}
	
	protected function renderPluploadPostInitListener()
	{
		?>
		if (up.features.dragdrop) {
			dropLabel.style.display = '';
			drop.className += ' dropable';
			up.refresh();
		}
		<?php
	}
	
	protected function renderPluploadFileItemCreation()
	{
		?>
		fileDiv = document.createElement('li');
		fileDiv.className = 'file';
		fileDiv.id = 'file-' + file.id;
		fileDiv.innerHTML = '<span class="name">' + file.name + '</span> <span class="size">(' + plupload.formatSize(file.size) + ')</span><span class="message" id="file-' + file.id + '-message"></span>';
		files.appendChild(fileDiv);
		<?php
	}
	
	protected function renderPluploadErrorListener()
	{
		?>
		if (error.code == plupload.INIT_ERROR) {
			browseButton.style.display = 'none';
		}
		if (error.file) {
			var fileDiv = document.getElementById('file-' + error.file.id);
			if (!fileDiv) {
				var file = error.file;
				<?php $this->renderPluploadFileItemCreation(); ?>
				up.files.push(file);
			}
			var message = document.getElementById('file-' + error.file.id + '-message');
			if (message) {
				message.className += ' error';
				message.innerHTML = error.message;
			}
		}
		<?php
	}
	
	protected function renderPluploadFilesAddedListener()
	{
		?>
		var fileDiv = null;
		<?php if (!$this->multiple): ?>
		files.innerHTML = '';
		if (up.files.length) {
			up.stop();
			for (var f = 0; f < up.files.length; f++) {
				file = up.files[f];
				fileDiv = document.getElementById('file-' + file.id);
				up.removeFile(file);
			}
		}
		<?php endif; ?>
		for (var f = 0; f < filesAdded.length; f++) {
			file = filesAdded[f];
			<?php $this->renderPluploadFileItemCreation(); ?>
		}
		up.refresh();
		<?php
	}
	
	protected function renderPluploadQueueChangedListener()
	{
		?>
		emptyLabel.style.display = up.files.length? 'none': '';
		up.start();
		<?php if (!$this->required): ?>
		removeButton.style.display = '';
		<?php endif; ?>
		up.refresh();
		<?php
	}
	
	protected function renderPluploadUploadProgressListener()
	{
		?>
		var fileDiv = document.getElementById('file-' + file.id);
		var message = document.getElementById('file-' + file.id + '-message');
		if (fileDiv) {
			fileDiv.className = fileDiv.className.replace(/\s*progress/, '') + ' progress';
		}
		if (message) {
			message.innerHTML = file.percent + '%';
		}
		<?php
	}
	
	protected function renderPluploadUploadCompleteListener()
	{
		?>
		var fileDiv = null;
		var message = null;
		for (var f = up.files.length - 1; f >= 0; f--) {
			file = up.files[f];
			fileDiv = document.getElementById('file-' + file.id);
			if (fileDiv) {
				fileDiv.className = fileDiv.className.replace(/\s*progress/, '') + ' complete';
			}
		}
		<?php
	}
	
	protected function renderPluploadFileUploadedListener()
	{
		?>
		var error = null;
		response = response.response;
		if (response.match(/^error:/)) {
			error = response.substr(6);
		}
		var fileDiv = document.getElementById('file-' + file.id);
		var message = document.getElementById('file-' + file.id + '-message');
		if (fileDiv) {
			fileDiv.className = fileDiv.className.replace(/\s*progress/, '');
			if (error) {
				fileDiv.className += ' error';
			} else {
				fileDiv.className += ' uploaded';
				<?php if ($this->multiple): ?>
				var newInputHidden = inputHidden.cloneNode(true);
				newInputHidden.value = response;
				fileDiv.appendChild(newInputHidden);
				<?php else: ?>
				inputHidden.value = response;
				container.appendChild(inputHidden);
				<?php endif; ?>
			}
		}
		if (message) {
			if (error) {
				message.innerHTML = error;
			} else {
				message.innerHTML = '100%';
			}
		}
		<?php
	}
	
	protected function renderPluploadRemoveFileListener()
	{
		?>
		up.stop();
		var fileDiv = null;
		for (var f = up.files.length - 1; f >= 0; f--) {
			file = up.files[f];
			fileDiv = document.getElementById('file-' + file.id);
			if (fileDiv) {
				files.removeChild(fileDiv);
			}
		}
		inputHidden.value = initValue;
		files.insertBefore(inputHidden, files.firstChild);
		<?php if (!$this->required): ?>
		removeButton.style.display = 'none';
		<?php endif; ?>
		<?php
	}
}



class FormFieldPluploadImage extends FormFieldPlupload
{
	public $extensions = array('jpg', 'jpeg', 'gif', 'png');
	public $emptyLabel = 'No image yet';
	public $uploadLabel = 'Upload image';
	public $dropLabel = 'Drop image here';
	public $filterLabel = 'Images';
	
	public $imageBaseUrl = '';
	public $alt = '';
	public $resize = null;

	protected function renderFile($value)
	{
		static $index = 0;
		?>
		<input type="checkbox" class="checkbox" tabindex="<?php echo html($this->getTabIndex())?>"
			name="<?php echo html($this->getName())?>" id="<?php echo html($this->getId())?>-image-<?php echo html($index)?>" value="<?php echo html($value)?>" checked="checked" />
		<label for="<?php echo html($this->getId())?>-image-<?php echo html($index++)?>"><img src="<?php echo html($this->imageBaseUrl . $value)?>" alt="<?php echo html($this->alt); ?>" /></label>
		<?php
	}
	
	protected function getPluploadOptions()
	{
		$pluploadOptions = parent::getPluploadOptions();
		if (!empty($this->resize))
		{
			$pluploadOptions['resize'] = $this->resize;
		}
		return $pluploadOptions;
	}
}