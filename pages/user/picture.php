<?
declare(strict_types=1);

$user = sql_one('select profile_picture from user where name = ?', [$path_resource_id]);
if ($user === null || $user['profile_picture'] === null) {
	http_response_code(404);
	exit;}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=31536000, immutable');
echo $user['profile_picture'];
