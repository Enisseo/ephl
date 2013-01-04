<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/htmlforms.php');

$form = new DefaultForm(array('action' => url('')));
$form->addComponents(array(
	new FormFieldText(array(
		'name'		=> 'firstname',
		'label'		=> 'First name',
		'required'	=> true,
	)),
	new FormFieldText(array(
		'name'		=> 'lastname',
		'label'		=> 'Last name',
		'required'	=> true,
	)),
));
$form->addActions(array(
	new DefaultFormAction(array(
		'name'		=> 'valid',
	)),
));

if ($form->isTriggeredWithRequest())
{
	$form->populateWithRequest();
	$validFormData = array();
	if ($form->validate($validFormData))
	{
		print('Your form has been submitted!');
		var_dump($validFormData);
	}
	else
	{
		print('There was an error with your form.');
		$form->render();
	}
}
else
{
	$form->render();
}
