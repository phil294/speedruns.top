<?php
declare(strict_types=1);

/** @var string $game_url_shorthand */

$game = sql_one('select image from game where url_shorthand = ?', [$game_url_shorthand]);
if ($game === null || $game['image'] === null) {
	http_response_code(404);
	exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
echo $game['image'];
