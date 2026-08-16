<?
declare(strict_types=1);

$authorization_code = $_GET['code'] ?? null;
if ($authorization_code === null) {
	http_response_code(400);
	exit;
}

$discord_user = discord_fetch_user_for_code($authorization_code);
if (!isset($discord_user['id'])) {
	http_response_code(400);
	echo 'discord callback failed. please try again??';
	exit;
}
$username = find_or_create_user_by_discord((string) $discord_user['id']);
log_in_as($username);
header('Location: /');
exit;
