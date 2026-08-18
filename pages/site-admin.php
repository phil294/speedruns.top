<?
declare(strict_types=1);

$current_user = require_login();
if (!$current_user['is_site_admin']) {
	http_response_code(403);
	echo 'site admins only';
	exit;}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? '');
	$name = (string) ($_POST['name'] ?? '');

	$game_request = sql_one('select r.requested_by as requester_user_name, u.email as requester_email, r.website as request_website, r.name as requested_game_name from game_request r inner join user u on r.requested_by = u.name where r.name = ?', [$name]);
	if ($action === 'accept_game_request') {
		if($game_request === null) {
			// TODO: helper
			http_response_code(400);
			echo 'no such game request';
			exit;}
		transaction(function () use ($game_request) {
			sql('insert into game (name, website) values (?, ?)', [$game_request['requested_game_name'], $game_request['request_website']]);
			sql('insert into game_admin (game_name, user_name) values (?, ?)', [$game_request['requested_game_name'], $game_request['requester_user_name']]);
			sql('delete from game_request where name = ?', [$game_request['requested_game_name']]);
		});
		send_mail($game_request['requester_email'], 'your game request was accepted', "\"{$game_request['requested_game_name']}\" is now live on speedruns.top, and you are its game admin.");
	} elseif ($action === 'reject_game_request') {
		if($game_request === null) {
			http_response_code(400);
			echo 'no such game request';
			exit;}
		sql('delete from game_request where name = ?', [$name]);
		send_mail($game_request['requester_email'], 'your game request was rejected', "\"{$game_request['requested_game_name']}\" was rejected. if you think this was a mistake, please contact " . SITE_ADMIN_EMAIL . ".");}
	header('Location: /site-admin');
	exit;}

$game_requests = sql('select name, description, requested_by from game_request order by created_at');

require __DIR__ . '/../templates/header.php';
?>
<h3>pending game requests (<?= count($game_requests) ?>)</h3>
<? foreach ($game_requests as $game_request): ?>
<p>
	<strong><?= e($game_request['name']) ?></strong> requested by <?= e($game_request['requested_by']) ?><br>
	<?= nl2br(e($game_request['description'])) ?>
	<form method="post" style="display:inline;">
		<input type="hidden" name="action" value="accept_game_request">
		<input type="hidden" name="name" value="<?= e($game_request['name']) ?>">
		<button type="submit">accept</button>
	</form>
	<form method="post" style="display:inline;">
		<input type="hidden" name="action" value="reject_game_request">
		<input type="hidden" name="name" value="<?= e($game_request['name']) ?>">
		<button type="submit">reject</button>
	</form>
</p>
<? endforeach; ?>
<?
require __DIR__ . '/../templates/footer.php';
