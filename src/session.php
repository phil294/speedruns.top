<?php
declare(strict_types=1);

function current_user(): array|null {
	static $user = null;
	static $looked_up = false;
	if ($looked_up) {
		return $user;
	}
	$looked_up = true;

	$token = $_COOKIE['session'] ?? null;
	if ($token === null) {
		return null;
	}

	$user = sql_one(
		'select user.* from login_session join user on user.name = login_session.user_name where login_session.token = ?',
		[$token],
	);
	return $user;
}

function require_login(): array {
	$user = current_user();
	if ($user === null) {
		header('Location: /login');
		exit;
	}
	return $user;
}

function log_in_as(string $user_name): void {
	$token = bin2hex(random_bytes(32));
	write('insert into login_session (token, user_name) values (?, ?)', [$token, $user_name]);
	setcookie('session', $token, [
		'expires' => time() + 60 * 60 * 24 * 365 * 10,
		'path' => '/',
		'secure' => COOKIE_SECURE,
		'httponly' => true,
		'samesite' => 'Strict',
	]);
}

function log_out(): void {
	$token = $_COOKIE['session'] ?? null;
	if ($token !== null) {
		write('delete from login_session where token = ?', [$token]);
	}
	setcookie('session', '', ['expires' => time() - 3600, 'path' => '/']);
}

function is_game_admin(string $game_url_shorthand, string $user_name): bool {
	return sql_one(
		'select 1 from game_admin where game_url_shorthand = ? and user_name = ?',
		[$game_url_shorthand, $user_name],
	) !== null;
}

function is_game_moderator(string $game_url_shorthand, string $user_name): bool {
	return sql_one(
		'select 1 from game_moderator where game_url_shorthand = ? and user_name = ?',
		[$game_url_shorthand, $user_name],
	) !== null;
}

function require_game_admin_or_moderator(string $game_url_shorthand): array {
	$user = require_login();
	if (!$user['is_site_admin'] && !is_game_admin($game_url_shorthand, $user['name']) && !is_game_moderator($game_url_shorthand, $user['name'])) {
		http_response_code(403);
		echo 'you are not an admin or moderator for this game.';
		exit;
	}
	return $user;
}
