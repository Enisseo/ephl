<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/mysql.php');

$mysql = new MySQL('host', 'user', 'pass', 'db');

$mysql->select()->from('mytable')->fetchArray(); // gets you all values from table "mytable" to an array...

$mysql
	->select('field_a', 'field_b as name')
	->from('mytable')
	->join('myothertable o', 'mytable.x = o.y')
	->where('mytable.z > :value')->with(':value', $value)
	->orderBy('o.y', 'DESC')
	->limit(10)
	->fetchBy('field_a'); // do I really need to explain?
	
$mysql
	->update('mytable')
	->set(array(
		'field_a' => $valueA,
		'field_b' => $valueB,
		))
	->execute();

$mysql
	->insert()
	->into('mytable')
	->set(array(
		'field_a' => $valueA,
		'field_b' => $valueB,
		))
	->executeAndGetInsertedId();

$mysql->query('SHOW COLUMNS FROM mytable')->execute(); // gets you a resource id so you can use mysql_fetch_assoc and similar functions
