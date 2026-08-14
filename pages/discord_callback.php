<?php
declare(strict_types=1);

$authorization_code = $_GET['code'] ?? null;
if ($authorization_code === null) {
	http_response_code(400);
	exit;
}

$discord_user = discord_fetch_user_for_code($authorization_code);
$user = find_or_create_user_by_discord((string) $discord_user['id'], (string) $discord_user['username']);
log_in_as($user['name']);
header('Location: /');
exit;
