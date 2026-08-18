<?
declare(strict_types=1);

$theme = $_GET['value'] ?? '';
if ($theme !== 'light' && $theme !== 'dark') {
	http_response_code(400);
	exit;}

setcookie('theme', $theme, [
	'expires' => time() + 60 * 60 * 24 * 365 * 10,
	'path' => '/',
	'secure' => COOKIE_SECURE,
	'httponly' => false,
	'samesite' => 'Lax',
]);

$redirect_to = '/';
if (isset($_SERVER['HTTP_REFERER'])) {
	$referer_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
	if ($referer_path !== null && $referer_path !== false)
		$redirect_to = $referer_path . (($referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY)) !== null ? '?' . $referer_query : '');}
header('Location: ' . $redirect_to);
exit;
