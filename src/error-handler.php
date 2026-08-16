<?
/*
 * Catch-all solution for errors, taken from some php.net comment
 * https://www.php.net/manual/en/function.set-error-handler.php#112291
 */

function log_error($num, $str, $file, $line, $context = null) {
	log_exception(new ErrorException($str, 0, $num, $file, $line));
}
function log_exception(Throwable $e) {
	if (DEBUG) {
		?><pre><?
		echo get_class($e) . "\n{$e->getMessage()}\nFile: {$e->getFile()}\nLine: {$e->getLine()}\n\n";
		echo $e->getTraceAsString();
		// var_dump($e);
	} else {
		$logfile = __DIR__ . '/../data/error.log';
		// TODO: does the current_user invocation mean that errors while bootstrapping get stuck here?
		$message = "Type: " . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()}; Request: {$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}; User: " . (current_user()['name'] ?? 'not logged in');
		file_put_contents($logfile, $message . PHP_EOL, FILE_APPEND);
		notify_site_admin('error on speedruns.top', $message);
		header('HTTP/1.1 500 Internal Server Error');
		echo 'Internal server error :( Admins have been notified. Please try again later.';
	}
	exit;
}
function check_for_fatal() {
	$error = error_get_last();
	if (($error["type"] ?? null) == E_ERROR)
		log_error($error["type"], $error["message"], $error["file"], $error["line"]);
}

register_shutdown_function("check_for_fatal");
set_error_handler("log_error");
set_exception_handler("log_exception");
ini_set("display_errors", "off");
error_reporting(E_ALL);