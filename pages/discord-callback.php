<?php
declare(strict_types=1);

$authorization_code = $_GET['code'] ?? null;
if ($authorization_code === null) {
	http_response_code(400);
	exit;
}

// TODO: what if this returns invalid data or is invoked maliciously
$discord_user = discord_fetch_user_for_code($authorization_code);
$username = find_or_create_user_by_discord((string) $discord_user['id']);
log_in_as($username);
header('Location: /');
exit;
