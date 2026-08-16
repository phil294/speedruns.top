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

// TODO: is this stuff really necessary?
// foreach (['/data/', '/src/', '/templates/', '/schema.sql'] as $forbidden_path_prefix) {
// 	if (str_starts_with($request_path, $forbidden_path_prefix)) {
// 		http_response_code(404);
// 		exit;
// 	}
// }
// if (PHP_SAPI === 'cli-server' && $request_path !== '/' && $request_path !== '/index.php' && is_file(__DIR__ . $request_path)) {
// 	return false;
// }

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
$page_path = __DIR__ . "/pages{$request_path}.php"; // TODO: safe against ../../.. attacks?
if(file_exists($page_path)) {
	require $page_path;
} else {
	render_page_not_found();
}
