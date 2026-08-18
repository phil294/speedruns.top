<?
declare(strict_types=1);

/*
 * Catch-all solution for errors, taken from some php.net comment
 * https://www.php.net/manual/en/function.set-error-handler.php#112291
 */

function log_error(int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return false; // @-suppressed errors
    }
	log_exception(new ErrorException($errstr, 0, $errno, $errfile, $errline));}
function log_exception(Throwable $e): never {
	$message = "Type: " . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()}";
	log_line('error', "ex: {$message}");
	header('HTTP/1.1 500 Internal Server Error');
	if (DEBUG_PRINT_ERROR_MESSAGES) {
		?><pre><?
		echo get_class($e) . "\n{$e->getMessage()}\nFile: {$e->getFile()}\nLine: {$e->getLine()}\n\n";
		echo $e->getTraceAsString();
		// var_dump($e);
	} else
		echo 'internal server error :( admins have been notified. please try again later.';
	exit;}
function check_for_fatal(): void {
	$error = error_get_last();
	if (($error["type"] ?? null) == E_ERROR)
		log_error($error["type"], $error["message"], $error["file"], $error["line"]);}

register_shutdown_function(check_for_fatal(...));
set_error_handler(log_error(...));
set_exception_handler(log_exception(...));
ini_set("display_errors", "off");
error_reporting(E_ALL);