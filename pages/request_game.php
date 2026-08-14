<?php
declare(strict_types=1);

$page_title = 'request a game';
$current_user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require_action_allowed($current_user, '/games/request', $_POST);

	$name = trim((string) ($_POST['name'] ?? ''));
	$url_shorthand = strtolower(trim((string) ($_POST['url_shorthand'] ?? '')));
	$website = trim((string) ($_POST['website'] ?? ''));
	$description = trim((string) ($_POST['description'] ?? ''));

	if ($name === '' || $description === '' || preg_match('/^[a-z0-9-]+$/', $url_shorthand) !== 1) {
		$error_message = 'please fill in a game name, a valid url shorthand (lowercase letters, numbers, hyphens) and tell us a bit about the game.';
	} elseif (sql_one('select 1 from game where url_shorthand = ?', [$url_shorthand]) !== null || sql_one('select 1 from game_request where url_shorthand = ?', [$url_shorthand]) !== null) {
		$error_message = 'that url shorthand is already taken, please choose another one.';
	} else {
		write(
			'insert into game_request (url_shorthand, name, website, description, requested_by) values (?, ?, ?, ?, ?)',
			[$url_shorthand, $name, $website !== '' ? $website : null, $description, $current_user['name']],
		);
		notify_site_admin('new game request on speedruns.top', "{$current_user['name']} requested \"$name\" ($url_shorthand).\n\n$description");
		$confirmation_message = 'thanks, your request has been sent to the site admins.';
	}
}

require __DIR__ . '/../templates/header.php';
?>
<h2>request a game <?php help_icon('accepted requests make you the permanent game admin for that game.'); ?></h2>
<?php if (isset($confirmation_message)): ?>
<p><?= e($confirmation_message) ?></p>
<?php else: ?>
<?php if (isset($error_message)): ?>
<p><?= e($error_message) ?></p>
<?php endif; ?>
<form method="post" action="/games/request">
	<p><label for="name">game name</label> <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>"></p>
	<p><label for="url_shorthand">url shorthand</label> <input type="text" id="url_shorthand" name="url_shorthand" pattern="[a-z0-9-]+" required value="<?= e($_POST['url_shorthand'] ?? '') ?>"> <?php help_icon("this is what shows up in the game's url, e.g. speedruns.top/game/your-shorthand. lowercase letters, numbers and hyphens only."); ?></p>
	<p><label for="website">website/wiki</label> <input type="url" id="website" name="website" value="<?= e($_POST['website'] ?? '') ?>"></p>
	<p><label for="description">tell us about it</label><br><textarea id="description" name="description" rows="4" required><?= e($_POST['description'] ?? '') ?></textarea></p>
	<button type="submit">request game</button>
</form>
<p>accepted requests make you the permanent game admin for that game.</p>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
