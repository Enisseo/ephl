<?php
require_once('lib/html.php');
require_once('lib/templates.php');
require_once('lib/responsive.php');

template_folder(realpath('tpl'));

if (responsive('state', null))
{
	if (responsive('id') == '__css__')
	{
		print(json_encode(array(responsive('state') . '.css')));
	}
	elseif (responsive('id') == '__js__')
	{
		print(json_encode(array(responsive('state') . '.js')));
	}
	else
	{
		var_dump(responsive('state', null));
		var_dump(responsive('height', 768));
		var_dump(responsive('width', 1024));
		var_dump(responsive('id', '-'));
		var_dump(responsive('pxRatio', 1));
	}
	exit();
}

template_inherits('main');

block_start('head'); ?>
<?php block_end();

block_start('header'); ?>
<h1 class="title">h1.title</h1>
<nav>
    <ul>
        <li><a href="#">nav ul li a</a></li>
        <li><a href="#">nav ul li a</a></li>
        <li><a href="#">nav ul li a</a></li>
    </ul>
</nav>
<?php block_end();

block_start('container'); ?>
<div class="Responsive" id="container">
	Test
</div>
<?php block_end(); 