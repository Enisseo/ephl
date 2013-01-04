<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/html.php');

$eggsCount = rand(0, 6);
$movies = array(
	array('title' => 'The Matrix', 'year' => 1999),
	array('title' => 'Inception', 'year' => 2010),
	array('title' => 'Cloud Atlas', 'year' => 2012),
);
?>
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
		<li><?=html($movie['title'])?> (<?=html($movie['year'])?>)</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</div>