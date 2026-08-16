<?
declare(strict_types=1);

$current_user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$name = trim((string) ($_POST['name'] ?? ''));
	$website = trim((string) ($_POST['website'] ?? ''));
	$description = trim((string) ($_POST['description'] ?? ''));

	sql(
		'insert into game_request (name, website, description, requested_by) values (?, ?, ?, ?)',
		[$name, $website !== '' ? $website : null, $description, $current_user['name']],
	);
	notify_site_admin('new game request on speedruns.top', "{$current_user['name']} requested \"$name\".\n\n$description");
	$confirmation_message = 'thanks, your request has been sent to the site admins.';
}

require __DIR__ . '/../templates/header.php';

help_icon_html('accepted requests make you the permanent game admin for that game.'); ?>
<? if (isset($confirmation_message)): ?>
<p><?= e($confirmation_message) ?></p>
<? endif; ?>
<form method="post" action="/game-add">
	<p><label for="name">game name</label> <input type="text" id="name" name="name" maxlength="100" required value="<?= e($_POST['name'] ?? '') ?>"></p>
	<p><label for="website">website/wiki</label> <input type="url" id="website" name="website" maxlength="300" required value="<?= e($_POST['website'] ?? '') ?>"></p>
	<p><label for="description">tell us why this game should be added, and why you're the right person to be its admin on speedruns.top</label><br><textarea id="description" name="description" maxlength="4000" rows="4" required><?= e($_POST['description'] ?? '') ?></textarea></p>
	<button type="submit">request game</button>
</form>
<p>accepted requests make you the permanent game admin for that game.</p>
<?
require __DIR__ . '/../templates/footer.php';
