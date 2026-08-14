<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$request_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

foreach (['/data/', '/src/', '/templates/', '/schema.sql'] as $forbidden_path_prefix) {
	if (str_starts_with($request_path, $forbidden_path_prefix)) {
		http_response_code(404);
		exit;
	}
}

if (PHP_SAPI === 'cli-server' && $request_path !== '/' && $request_path !== '/index.php' && is_file(__DIR__ . $request_path)) {
	return false;
}

function dispatch_game_page(string $game_url_shorthand, string $page_file_path): void {
	require $page_file_path;
}

function render_not_found(): void {
	$page_title = 'not found';
	http_response_code(404);
	require __DIR__ . '/templates/header.php';
	echo '<p>page not found.</p>';
	require __DIR__ . '/templates/footer.php';
}

match (true) {
	$request_path === '/' => require __DIR__ . '/pages/landing.php',
	$request_path === '/login' => require __DIR__ . '/pages/login.php',
	$request_path === '/discord-login' => require __DIR__ . '/pages/discord_login.php',
	$request_path === '/discord-callback' => require __DIR__ . '/pages/discord_callback.php',
	$request_path === '/logout' => require __DIR__ . '/pages/logout.php',
	$request_path === '/games/request' => require __DIR__ . '/pages/request_game.php',
	$request_path === '/site-admin' => require __DIR__ . '/pages/site_admin.php',
	preg_match('#^/game/([a-z0-9-]+)/admin$#', $request_path, $path_matches) === 1 => dispatch_game_page($path_matches[1], __DIR__ . '/pages/game_admin.php'),
	preg_match('#^/game/([a-z0-9-]+)/submit$#', $request_path, $path_matches) === 1 => dispatch_game_page($path_matches[1], __DIR__ . '/pages/submit_run.php'),
	preg_match('#^/game/([a-z0-9-]+)/image$#', $request_path, $path_matches) === 1 => dispatch_game_page($path_matches[1], __DIR__ . '/pages/game_image.php'),
	preg_match('#^/game/([a-z0-9-]+)$#', $request_path, $path_matches) === 1 => dispatch_game_page($path_matches[1], __DIR__ . '/pages/game_leaderboard.php'),
	default => render_not_found(),
};
