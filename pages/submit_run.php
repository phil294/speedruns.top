<?php
declare(strict_types=1);

/** @var string $game_url_shorthand */

$game = sql_one('select * from game where url_shorthand = ?', [$game_url_shorthand]);
if ($game === null) {
	http_response_code(404);
	exit;
}

$current_user = require_login();
$page_title = 'submit a run - ' . $game['name'];

$categories = sql('select * from category where game_url_shorthand = ? order by sort_order', [$game_url_shorthand]);
if ($categories === []) {
	http_response_code(400);
	echo 'this game has no categories to submit runs to yet.';
	exit;
}

$category_names = array_column($categories, 'name');
$selected_category_name = in_array($_POST['category'] ?? '', $category_names, true)
	? $_POST['category']
	: ($_GET['category'] ?? $categories[0]['name']);

$properties = sql(
	'select property.name from property
	join category__property on category__property.property_name = property.name and category__property.game_url_shorthand = property.game_url_shorthand
	where category__property.game_url_shorthand = ? and category__property.category_name = ?
	order by property.name',
	[$game_url_shorthand, $selected_category_name],
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_run') {
	require_action_allowed($current_user, "/game/$game_url_shorthand/submit", $_POST);

	$minutes = (int) ($_POST['minutes'] ?? 0);
	$seconds = (int) ($_POST['seconds'] ?? 0);
	$milliseconds = (int) ($_POST['milliseconds'] ?? 0);
	$time_milliseconds = ($minutes * 60 + $seconds) * 1000 + $milliseconds;
	$proof = trim((string) ($_POST['proof'] ?? ''));
	$comment = trim((string) ($_POST['comment'] ?? ''));

	$property_values = [];
	foreach ($properties as $property) {
		$property_values[$property['name']] = trim((string) ($_POST['property'][$property['name']] ?? ''));
	}

	if ($time_milliseconds <= 0 || $proof === '' || in_array('', $property_values, true)) {
		$error_message = 'please enter a time greater than zero, provide proof and fill in all required properties.';
	} else {
		$run_id = bin2hex(random_bytes(16));
		write(
			'insert into run (id, game_url_shorthand, category_name, user_name, time_milliseconds, proof, comment) values (?, ?, ?, ?, ?, ?, ?)',
			[$run_id, $game_url_shorthand, $selected_category_name, $current_user['name'], $time_milliseconds, $proof, $comment],
		);
		foreach ($property_values as $property_name => $property_value) {
			write('insert into run__property (run_id, property_name, value) values (?, ?, ?)', [$run_id, $property_name, $property_value]);
		}
		notify_game_admins_and_moderators(
			$game_url_shorthand,
			'new run submitted for ' . $game['name'],
			"{$current_user['name']} submitted a run in $selected_category_name: " . format_run_time($time_milliseconds) . "\nproof: $proof",
		);
		header("Location: /game/$game_url_shorthand?category=" . urlencode($selected_category_name) . '&submitted=1');
		exit;
	}
}

require __DIR__ . '/../templates/header.php';
?>
<h2>&lt; <?= e($game['name']) ?> / submit a run &gt;</h2>
<?php if (isset($error_message)): ?>
<p><?= e($error_message) ?></p>
<?php endif; ?>
<form method="post" action="/game/<?= e($game_url_shorthand) ?>/submit">
	<p>
		category
		<?php foreach ($categories as $category): ?>
		<label><input type="radio" name="category" value="<?= e($category['name']) ?>" <?= $category['name'] === $selected_category_name ? 'checked' : '' ?>> <?= e($category['name']) ?></label>
		<?php endforeach; ?>
		<button type="submit" name="action" value="change_category">change category</button>
	</p>
	<p>
		time
		<input type="number" name="minutes" min="0" style="width:4em;" value="<?= e($_POST['minutes'] ?? '0') ?>"> :
		<input type="number" name="seconds" min="0" max="59" style="width:4em;" value="<?= e($_POST['seconds'] ?? '0') ?>"> .
		<input type="number" name="milliseconds" min="0" max="999" style="width:5em;" value="<?= e($_POST['milliseconds'] ?? '0') ?>">
	</p>
	<?php foreach ($properties as $property): ?>
	<p><label><?= e($property['name']) ?></label> <input type="text" name="property[<?= e($property['name']) ?>]" required value="<?= e($_POST['property'][$property['name']] ?? '') ?>"></p>
	<?php endforeach; ?>
	<p><label for="proof">proof</label> <input type="url" id="proof" name="proof" required value="<?= e($_POST['proof'] ?? '') ?>"> <?php help_icon('link to a youtube video, twitch stream, or however else your run can be verified.'); ?></p>
	<p><label for="comment">comment</label> <input type="text" id="comment" name="comment" value="<?= e($_POST['comment'] ?? '') ?>"></p>
	<button type="submit" name="action" value="submit_run">submit run</button>
</form>
<?php
require __DIR__ . '/../templates/footer.php';
