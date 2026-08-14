<?php
declare(strict_types=1);

$current_user = require_login();
if (!$current_user['is_site_admin']) {
	http_response_code(403);
	echo 'site admins only.';
	exit;
}

$page_title = 'site admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require_action_allowed($current_user);
	$action = (string) ($_POST['action'] ?? '');
	$url_shorthand = (string) ($_POST['url_shorthand'] ?? '');

	if ($action === 'accept_game_request') {
		$game_request = sql_one('select * from game_request where url_shorthand = ?', [$url_shorthand]);
		if ($game_request !== null) {
			write('insert into game (url_shorthand, name, website) values (?, ?, ?)', [$game_request['url_shorthand'], $game_request['name'], $game_request['website']]);
			write('insert into category (game_url_shorthand, name, sort_order) values (?, ?, 0)', [$url_shorthand, 'any%']);
			write('insert into game_admin (game_url_shorthand, user_name) values (?, ?)', [$url_shorthand, $game_request['requested_by']]);
			write('delete from game_request where url_shorthand = ?', [$url_shorthand]);
			$requester_email = sql_one('select email from user where name = ?', [$game_request['requested_by']])['email'] ?? null;
			if ($requester_email !== null) {
				send_mail(
					$requester_email,
					'your game request was accepted',
					"\"{$game_request['name']}\" is now live on speedruns.top, and you are its game admin.",
				);
			}
		}
	} elseif ($action === 'reject_game_request') {
		write('delete from game_request where url_shorthand = ?', [$url_shorthand]);
	}

	header('Location: /site-admin');
	exit;
}

$game_requests = sql('select * from game_request order by created_at');

require __DIR__ . '/../templates/header.php';
?>
<h2>&lt; site admin &gt;</h2>
<h3>pending game requests (<?= count($game_requests) ?>)</h3>
<?php foreach ($game_requests as $game_request): ?>
<p>
	<strong><?= e($game_request['name']) ?></strong> (<?= e($game_request['url_shorthand']) ?>) requested by <?= e($game_request['requested_by']) ?><br>
	<?= nl2br(e($game_request['description'])) ?>
	<form method="post" action="/site-admin" style="display:inline;">
		<input type="hidden" name="action" value="accept_game_request">
		<input type="hidden" name="url_shorthand" value="<?= e($game_request['url_shorthand']) ?>">
		<button type="submit">accept</button>
	</form>
	<form method="post" action="/site-admin" style="display:inline;">
		<input type="hidden" name="action" value="reject_game_request">
		<input type="hidden" name="url_shorthand" value="<?= e($game_request['url_shorthand']) ?>">
		<button type="submit">reject</button>
	</form>
</p>
<?php endforeach; ?>
<?php
require __DIR__ . '/../templates/footer.php';
