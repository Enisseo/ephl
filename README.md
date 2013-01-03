ePHL
====

A minimalist PHP framework.


Concepts
===

ePHL was born from my experience of EVERY SINGLE PHP FRAMEWORK that I tried. When I start a project, I
want to code the minimum but keep a total control of what I do because every new project has - by definition -
never been done.

So the point of this framework is to provide a really simple base so that you do get useful shortcuts and functions
but are not forced to do some extra work not required, especially if you are multi-task (developer & designer).



MVC (is bullsh*t)
===

Firstly, I want to come clear. By reading this title, you could think I have hard feelings about the 
MVC (Mad Vulcan Cyborg) model: you would be right. But what I hate mostly, like every design pattern, is
the overuse in contexts that are often non pertinents.

In ePHL, YOU decide if you want to separate the M from the V, from the C, or even use any of this, or add
some new letters to your model.

ePHL provides a freedom of choice in your implementation. Here is a "Hello, world!" in ephl:

**hello1.php**
```php
<?php print('Hello, world!'); ?>
```

What did you expect you damn Zend Framework lover?! Some 100-lines code example you don't even understand?!
You want to write a f*cking "Hello, world!", *it should be as simple as that!*

Now your are a bit frustrated that I didn't give you a big fat example. Ok, then you could also write:

**hello2.php**
```php
<?php
include('initialize-my-app.php');

HelloWorldController extends DefaultController
{
	public function render()
	{
		print('Hello, world!');
	}
}
?>
```

Happy? We added a Controller, why not a View?

**hello3.php**
```php
<?php
include('initialize-my-app.php');

HelloWorldController extends DefaultController
{
	public function render()
	{
		include('views/helloworld.tpl');
	}
}
?>
```

**views/helloworld.tpl**
```
Hello, world!
```

See! You do what you need, not what a horrible piece of software told you to. If you are like me often
in charge of the PHP code as well as the HTML/CSS/Javascript code, you do not have to create 3 different
files for each new piece of code you write if you do not want to.



ORM (is for losers)
===

The SQL language is already an abstraction, d*ckhead! If you add another layer, it will only make the 20%
non common tasks 80% harder.

In ePHL, you are provided with a clean and simple classy (I mean, with style AND php classes) version of the
SQL language, so you can either use classes and methods to help you avoid common typos and errors 
(like SEELCT or UPDATE table VALUES...) or write plain SQL queries, or use a mix between both universes.

A little portion of code will help you understand:

**my-mysql-sql.php**
```php
<?php
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
	
$mysql->update('mytable')->set(array('field_a' => $valueA, 'field_b' => $valueB))->execute();
?>
```

And so on...

Did you like it? Didn't it feel obvious and natural? The more you'll use it, the more you'll like it!


Form generation (not for the lazy-a**)
===

ePHL does provide form generation, but it has two layers: an abstract layer, with only abstract classes definitions,
and an implementation with ePHL own generated HTML. Like it or implement your own rendering, but the abstraction
is only here to provide a classic flow for processing forms:

**form-flow.php**
```php
// todo
```


URL mapping (if you do it yourself)
===

I personnally prefer having the file "a/b/c.php" behind of the URL "a/b/c", and not loaded by a series
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



Useful functions (I wrote them for you)
===



Translations (mon ami)
===



Security
===

ePHL can't and won't prevent the major security issue: you, the not-that-awesome developer. But it can
help you with some tools and tips.

//html(), javascript()
//SQL statements
//CSRF with post() and referer check if done right



Use with other frameworks
===

What?! Did you even read what I'd just explained to you?! Well, there are certainly two cases when you
have this issue:

You want a stable (as in smelly and obese) framework but you like some ephl features
==

First: the main thing about ePHL is the simplicity and small amount of code and included files. If you use a
monster framework you will annihilate any advantages ePHL has.

The big problem is that other frameworks often use a dirty URL mapping, MVC and sometimes template engines.
So, you will certainly have some troube mixing ePHL with them. Your best chance might be to use the other 
framework as the main framework and add some of the functions or classes of ephl you extracted manually from
the source code.

I know, it's hard and dirty but you started it.

You want some feature(s) another big framework has and ePHL has not
==

Nice! You start to like the ePHL framework, yound padawan. However, your mind is filled with envy, your 
soul is corrupted by desire and laziness has invaded your spirit: someone coded something (let me guess, 
Zend Framework?) and you'd like to get it for free.

Well, ePHL do not constraint you so you can add your own classes, files and libraries pretty easily. You 
should not encounter any issue, except for name conflicts.

However, if you need a feature that ephl has not, maybe it can be of any interest for other users, so you
may want to share it so we can all enjoy the library of re-write one in the ephl spirit (one file, classes
only if needed...).


Contact & development
===

ePHL is still in development (and will always be in constant evolution due to the evil nature of the web).
If you want to add some features, well, discuss it with me first so we can see if it has a chance to be
in the core. If you want some features added (by someone else), you'll have to talk to me about it, then being
called a d*ck, then get the f*ck off, then wait and maybe one day you'll see it!
