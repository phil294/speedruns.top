<?
$title = $breadcrumbs[array_key_last($breadcrumbs) ?? '']['name'] ?? null;
$current_user = current_user();
$theme_preference = $_COOKIE['theme'] ?? null;
?>
<!doctype html>
<html lang="en"<?= $theme_preference !== null ? ' data-theme="' . e($theme_preference) . '"' : '' ?>>
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
				<a href="/" style="text-decoration: none;"><strong>speedruns.top</strong></a>
				<? foreach ($breadcrumbs as $breadcrumb): ?>
				<span>&gt;</span>
				<? if (($breadcrumb['url'] ?? null) === null): ?>
				<span><?= e($breadcrumb['name']) ?></span>
				<? else: ?>
				<a href="<?= e($breadcrumb['url']) ?>"><?= e($breadcrumb['name']) ?></a>
				<? endif; ?>
				<? endforeach; ?>
			</div>
			<span class="spacer"></span>
			<a href="/set-theme?value=light" class="theme-link<?= $theme_preference === 'light' ? ' active' : '' ?>" title="light theme">☀</a>
			<a href="/set-theme?value=dark" class="theme-link<?= $theme_preference === 'dark' ? ' active' : '' ?>" title="dark theme">🌙</a>
			<? if ($current_user !== null): ?>
			<a href="/account"><?= e($current_user['name']) ?></a>
			<? if ($current_user['is_site_admin']): ?>
			<a href="/site-admin">site admin</a>
			<? endif; ?>
			<form method="post" action="/logout"><button type="submit" class="secondary">logout</button></form>
			<? else: ?>
			<a href="/login">login/register</a>
			<form method="get" action="/discord-login"><button type="submit" class="secondary">discord login</button></form>
			<? endif; ?>
		</nav>
	</header>
	<main>
		<? if($title): ?>
		<h2><?= e($title) ?></h2>
		<? endif; ?>
		<? if (isset($error_message)): ?>
		<p class="error"><?= e($error_message) ?></p>
		<? endif; ?>