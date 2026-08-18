<?
declare(strict_types=1);

$_uniq_req_id = uniqid();
$_log_out = fopen(__DIR__ . '/data/log.log', 'a');
function log_line(string $severity, string $message): void {
	global $_log_out, $_uniq_req_id;
	try {
		$user_name = current_user()['name'] ?? '-';
	} catch (Throwable) {
		$user_name = '??';
	}
	$line = sprintf("[%s] %s %s[%s] %s\n", date('Y-m-d H:i:s'), strtoupper($severity), $_uniq_req_id, $user_name, $message);
	fwrite($_log_out, $line);
	if ($severity === 'error')
		notify_site_admin('error on '.BASE_URL, $message);
}
require __DIR__ . '/src/error-handler.php';
require __DIR__ . '/src/bootstrap.php';

$breadcrumbs = [];

$postdata = file_get_contents('php://input'); // this site doesn't use passwords, so it's reasonably fine to log
log_line('info', sprintf("req: %s %s; %s/%s; %s - %s", @$_SERVER['REQUEST_METHOD'], @$_SERVER['REQUEST_URI'], @$_SERVER['REMOTE_ADDR'], @$_SERVER['HTTP_X_FORWARDED_FOR'], @$_SERVER['HTTP_USER_AGENT'], $postdata));

$request_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($request_path !== '/')
	$request_path = rtrim($request_path, '/');

if ($_SERVER['REQUEST_METHOD'] === 'POST')
	rate_limit_action($request_path);

/** @var string|null $path_resource_id */
$path_resource_id = null;
if (preg_match('#^/([^/]+)/([^/]+)(/([^/]+))?$#', $request_path, $match)) {
	$path_resource_id = urldecode($match[2]);
	$breadcrumbs = [
		["name" => $match[1], "url" => "/{$match[1]}"],
		["name" => $path_resource_id, "url" => "/{$match[1]}/{$match[2]}"]];
	if ($match[3] ?? null)
		$breadcrumbs[] =["name" => $match[4], "url" => "/{$match[1]}/{$match[2]}{$match[3]}"];
	$request_path = "/{$match[1]}" . ($match[3] ?? '/landing');
} elseif ($request_path === '/') {
	$request_path = '/landing';
} else {
	$breadcrumbs = [["name" => ltrim($request_path, '/')]];
}
// die($request_path);
$pages_directory = realpath(__DIR__ . '/pages');
$page_path = realpath(__DIR__ . "/pages{$request_path}.php");
if ($page_path !== false && str_starts_with($page_path, $pages_directory . DIRECTORY_SEPARATOR)) // avoid `..` attacks
	require $page_path;
else
	render_page_not_found();
