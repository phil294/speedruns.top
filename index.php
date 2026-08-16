<?
declare(strict_types=1);

const DEBUG = true;
require __DIR__ . '/src/error-handler.php';
require __DIR__ . '/src/bootstrap.php';

$breadcrumbs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	rate_limit_action();
}

$request_path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($request_path !== '/') {
	$request_path = rtrim($request_path, '/');
}

/** @var string|null $path_resource_id */
$path_resource_id = null;
if (preg_match('#^/([^/]+)/([^/]+)(/([^/]+))?$#', $request_path, $match)) {
	$path_resource_id = $match[2];
	$breadcrumbs = [
		["name" => $match[1], "url" => "/{$match[1]}"],
		["name" => $match[2], "url" => "/{$match[1]}/{$match[2]}"]];
	if($match[3] ?? null) {
		$breadcrumbs[] =["name" => $match[4], "url" => "/{$match[1]}/{$match[2]}{$match[3]}"];
	}
	$request_path = "/{$match[1]}" . ($match[3] ?? '/landing');
} elseif ($request_path === '/') {
	$request_path = '/landing';
} else {
	$breadcrumbs = [["name" => ltrim($request_path, '/')]];
}
// die($request_path);
$pages_directory = realpath(__DIR__ . '/pages');
$page_path = realpath(__DIR__ . "/pages{$request_path}.php");
if ($page_path !== false && str_starts_with($page_path, $pages_directory . DIRECTORY_SEPARATOR)) { // avoid `..` attacks
	require $page_path;
} else {
	render_page_not_found();
}
