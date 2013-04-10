<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/controllers.php');

class HelloWorldController extends Controller
{
	public function render()
	{
		print('Hello, world!');
	}
}

controller_runlast();
