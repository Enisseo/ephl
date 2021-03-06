# ePHL #

A minimalist PHP framework.


## Concepts ##

ePHL was born from my experience of **EVERY SINGLE PHP FRAMEWORK** that I ever tried. When I start a project, I
want to code the minimum but keep a total control of what I do because every new project has - by definition -
never been done.

So the point of this framework is to provide a really simple base so that you do get useful shortcuts and functions
but are not forced to do some extra work not required, especially if you are multi-task (developer and designer).

ePHL is pronounced like *Eiffel* (/ɛ.fɛl/), and could mean **e**nisseo's **PH**P **L**ibrary or anything you want as this is a DIY acronym.


## MVC (is bullsh*t) ##

First, I want to come clear. By reading this title, you could think I have hard feelings about the 
MVC (Mad Vulcan Cyborg) model: you would be totally right. But what I hate mostly, like every design pattern, is
the overuse in contexts that are often non pertinents.

In ePHL, *you* decide if you want to separate the M from the V, from the C, or even use any of this, or add
some new fancy letters to your model.

ePHL provides a freedom of choice in your implementation. Here is the classic "Hello, world!" in ePHL:

**hello1.php**
```php
<?php print('Hello, world!'); ?>
```

What did you expect, you ZendFramework-lover? Some 100-lines code example you don't even understand?
You want to write "Hello, world!", *it should be as simple as that!*

Now you are a bit frustrated that I didn't give you an example with the ePHL framework. Ok, then you could also write:

**hello2.php**
```php
<?php
require_once('/lib/controllers.php');

class HelloWorldController extends Controller
{
	public function render()
	{
		print('Hello, world!');
	}
}

runLastController();
?>
```

Happy? Now that we've added a Controller, why not a View?

**hello3.php**
```php
<?php
require_once('/lib/controllers.php');

class HelloWorldController extends Controller
{
	public function render()
	{
		include('views/helloworld.tpl');
	}
}

runLastController();
?>
```

**views/helloworld.tpl**
```
Hello, world!
```

See? You do what you need, not what a horrible piece of software told you to. If you are like me often
in charge of the PHP code as well as the HTML/CSS/Javascript code, you do not have to create 3 different
files for each new piece of code you write *if you do not want to*.


## Templates (the nice way) ##

After reading the previous sections, you still don't know what I think of template engines? They are made
by Satan (aka CTO) and the Four Horsemen of the Apocalypse (aka developers) and their only goal is to
upset every existing guy, from the developer - who'd like to write some good ol' PHP code, not learn
a new language - to the designer - who'd need the developer for every 20% task the engine is not able to
manage and who'd have to learn a new language anyway.

### The good ###

In ePHL, the template language is PHP. Plain PHP. But as I'm a very kind person (when I'm not eating
kittens), I've written some functions to help you:

```php
html($text); // returns the HTML-safe text
javascript($text); // returns a plain Javascript string
```

So you can write beautiful (and syntax-highlighted) templates like this:

**html.php**
```php
<div>
	<?php if ($eggsCount > 1): ?>
	<p>There are <?=html($eggsCount)?> eggs in my basket.</p>
	<?php elseif ($eggsCount > 0): ?>
	<p>There is only one egg in my basket.</p>
	<?php else: ?>
	<p>Holy chicken! There is no egg in my basket!</p>
	<?php endif; ?>
	
	<?php if (count($movies)): ?>
	<ul>
		<?php foreach ($movies as $movie): ?>
		<li><?=html(<?=html($movie['title'])?>)?> (<?=html($movie['year'])?>)</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</div>
```

### The also good ###

But that's not all! Inspired by http://www.phpti.com, I've written similar template functions so you can
add extra functions to your templates: inheritance, block replacement, easy inclusion, template overwriting.

**template.php**
```php
<?php
require_once('/lib/templates.php');
template_folder('/views');

template_inherits('structure');

block_start('main');

switch (@$_GET['content']):
	case 'a':
?><p>You are on the content of page A, mister!</p><?php
		break;
	case 'b':
?><p>Yep, B panel!</p><?php
		break;
	case 'c':
?><p>Weird... This is the C section.</p><?php
		break;
	default:
?><p>This is the default content! Click on a link above, dummy!</p><?php
		break;
endswitch;

block_end();

block_start('title'); ?>
<h1>Template demo</h1>
<?php block_end(); ?>
```

And your templates:

**views/structure.php**
```php
<!DOCTYPE html>
<html class="no-js">
    <head>
        <meta charset="utf-8">
        <title></title>
    </head>
    <body>
    	<?php block('title'); ?>
    	
    	<div id="menu">
    		<?php template_include('menu'); ?>
    	</div>
    	
    	<div id="main">
    		<?php block('main'); ?>
    	</div>
    </body>
</html>
```

**views/menu.php**
```php
<nav>
	<a href="template.php?content=a">Content A</a>
	<a href="template.php?content=b">Content B</a>
	<a href="template.php?content=c">Content C</a>
</nav>
```

Do you really need more (or less) than that? You can even write or use a lot of PHP functions to ease
the most common tasks in templates, if any ("trim", "str_replace" and so on). And if you use the Controllers,
you can replace blocks by method calls and inheritance.

### The ugly ###

Now you'll tell me: "And what about code injection, mor*n?!". OK. Code injections are possible 
when a/ the code in your database is not safe or b/ you do not properly escape special chars when displaying 
a value to HTML.

This is why I've come with these two functions, "html()" and "javascript()" and I use the shorttag "<?=VAL?>":
it allows you to easily add clean text, but more, it **makes your errors obvious to everyone**! When you are
reading a template, especially with syntax highlighted, you can see if there are mistakes: if you or your
coworker has written "<p><?=$description?></p>", according to the expected content of "$description"
you'll be quickly able to tell if it's a security flaw or not. And you quickly add the "<?=html()?>" portion of
code every time you want to display a variable.

One last thing: never - ever - escape a string before passing it to the template. You could go to Hell, which is
a very hot place, especially in summer.


## ORM (is for losers) ##

The SQL language is already an abstraction! If you add another layer, it will only make the 20% non-common 
tasks 80% harder.

In ePHL, you are provided with a clean and simple classy (I mean, with style AND php classes) version of the
SQL language, so you can either use classes and methods to help you avoid common typos and errors 
(like SEELCT or UPDATE table VALUES...) or write plain SQL queries, or use a clever mixture of both.

A little portion of code will help you understand:

**my-mysql-sql.php**
```php
<?php
require_once('/lib/mysql.php');

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
```

And so on...

Did you like it? Didn't it feel obvious and natural? The more you'll use it, the more you'll like it!


## Form generation (not for the lazy) ##

ePHL does provide form generation, but it has two layers: an abstract layer, with only abstract classes definitions,
and an implementation with ePHL own generated HTML. Like it or implement your own rendering, but the abstraction
is only here to provide a classic flow for processing forms:

**form-flow.php**
```php
// todo
```


## URL mapping (if you do it yourself) ##

I personnally prefer having the file "a/b/c.php" behind of the URL "a/b/c", not loaded by a series
of PHP scripts that I do not understand. Anyway, ePHL lets you do what you want, so if you love URL 
mapping (I don't, but hey! everyone gets to have its little fantasies) you sure can implement that by yourself.

For example, you can add this to your .htaccess

**.htaccess**
```
//htaccess RewriteRule
```

And create a rule to load the right file in your index:

**index.php**
```php
//switch(true):
//	case query is ...
```

By the way, do you like the "switch(true)" syntax? Better to read than a 30 lines "if/elseif", isn't it?



## Useful functions (I wrote them for you) ##



## Translations (mon ami) ##



## Security ##

ePHL can't and won't prevent the major security issue: you, the not-that-awesome developer. But it can
help you with some tools and tips.

//html(), javascript()
//SQL statements
//CSRF with post() and referer check if done right


## Miscellaneous ##


## Use with other frameworks ##

What?! Did you even read what I'd just explained to you?! Well, there are certainly two cases when you
have this issue:

### You want a stable (as in smelly and obese) framework but you like some ePHL features ###

First: the main thing about ePHL is the simplicity and small amount of code and included files. If you use a
monster framework you will annihilate any advantages ePHL has.

The big problem is that other frameworks often use class autoloader, URL mapping, MVC and sometimes template engines.
So, you will certainly have some troube mixing ePHL with them. Your best chance might be to use the other 
framework as the main framework and add some of the functions or classes of ephl you extracted manually from
the source code.

I know, it's hard and dirty but you started it.

### You want some feature(s) another big framework has and ePHL has not ###

Nice! You start to like the ePHL framework, young padawan. However, your mind is filled with envy, your 
soul is corrupted by desire and laziness has invaded your spirit: someone coded something (let me guess, 
Zend Framework?) and you'd like to get it for free.

Well, ePHL do not constraint you so you can add your own classes, files and libraries pretty easily. You 
should not encounter any issue, except for name conflicts.

However, if you need a feature that ephl has not, maybe it can be of any interest for other users. So you
may want to share it so we can all enjoy the library of re-write one in the ePHL spirit (one file, classes
only if needed...).


## Contact & development ##

ePHL is still in development (and will always be in constant evolution due to the evil nature of the web).
If you want to add some features, well, discuss it with me first so we can see if it has a chance to be
in the core. If you want some features added (by someone else), you'll have to talk to me about it, then 
wait, and maybe one day you'll see it!
