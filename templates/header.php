<?php
$title = array_last($breadcrumbs)['name'] ?? null;
$current_user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $title ? (e($title) . ' - ') : 'speedruns.top' ?></title>
	<link rel="stylesheet" href="/style.css">
</head>
<body>
	<header>
		<nav>
			<div class="breadcrumbs">
				<a href="/"><strong>speedruns.top</strong></a>
				<?php foreach ($breadcrumbs as $breadcrumb): ?>
				<span>&gt;</span>
				<?php if (($breadcrumb['url'] ?? null) === null): ?>
				<span><?= e($breadcrumb['name']) ?></span>
				<?php else: ?>
				<a href="<?= e($breadcrumb['url']) ?>"><?= e($breadcrumb['name']) ?></a>
				<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<span class="spacer"></span>
			<?php if ($current_user !== null): ?>
			<span><?= e($current_user['name']) ?></span>
			<a href="/account">account</a>
			<?php if ($current_user['is_site_admin']): ?>
			<a href="/site-admin">site admin</a>
			<?php endif; ?>
			<form method="post" action="/logout"><button type="submit" class="secondary">logout</button></form>
			<?php else: ?>
			<a href="/login">login/register</a>
			<form method="get" action="/discord-login"><button type="submit" class="secondary">discord login</button></form>
			<?php endif; ?>
		</nav>
	</header>
	<main>
		<?php if($title): ?>
		<h2><?= e($title) ?></h2>
		<?php endif; ?>
		<?php if (isset($error_message)): ?>
		<p style="color:darkred; font-weight:bold;"><?= e($error_message) ?></p>
		<?php endif; ?>