<?
declare(strict_types=1);

$game = sql_one('select image from game where name = ?', [$path_resource_id]);
if ($game === null || $game['image'] === null) {
	http_response_code(404);
	exit;}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
echo $game['image'];
