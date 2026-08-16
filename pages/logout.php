<?
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	log_out();
}
header('Location: /');
exit;
