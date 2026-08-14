<?php
/** @var string $page_title */
$current_user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= e($page_title) ?> - speedruns.top</title>
	<link rel="stylesheet" href="/style.css">
</head>
<body>
	<header>
		<nav>
			<a href="/"><strong>speedruns.top</strong></a>
			<a href="/">Games</a>
			<span class="spacer"></span>
			<?php if ($current_user !== null): ?>
			<span><?= e($current_user['name']) ?></span>
			<?php if ($current_user['is_site_admin']): ?>
			<a href="/site-admin">Site admin</a>
			<?php endif; ?>
			<form method="post" action="/logout"><button type="submit" class="secondary">Logout</button></form>
			<?php else: ?>
			<a href="/login">Login/Register</a>
			<form method="get" action="/discord-login"><button type="submit" class="secondary">Discord login</button></form>
			<?php endif; ?>
		</nav>
	</header>
	<main>
