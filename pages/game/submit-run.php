<?
declare(strict_types=1);

$game_name = $path_resource_id;
$game = sql_one('select 1 from game where name = ?', [$game_name]);
if ($game === null) {
	render_page_not_found();
	exit;}

$current_user = require_login();

$category_names = array_column(sql('select name from category where game_name = ?', [$game_name]), 'name');
if ($category_names === []) {
	http_response_code(400);
	$error_message = 'this game has no categories to submit runs to yet.';}
$selected_category_name = in_array($_POST['category'] ?? '', $category_names, true)
	? $_POST['category']
	: ($_GET['category'] ?? $category_names[0]['name'] ?? null);

$properties = sql(
	'select name from property where game_name = ? and category_name = ? order by name',
	[$game_name, $selected_category_name],);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_run') {
	$hours = (int) ($_POST['hours'] ?? 0);
	$minutes = (int) ($_POST['minutes'] ?? 0);
	$seconds = (int) ($_POST['seconds'] ?? 0);
	$milliseconds = (int) ($_POST['milliseconds'] ?? 0);
	$time_milliseconds = (($hours * 60 + $minutes) * 60 + $seconds) * 1000 + $milliseconds;
	$proof = trim((string) ($_POST['proof'] ?? ''));
	$comment = trim((string) ($_POST['comment'] ?? ''));

	$property_values = [];
	foreach ($properties as $property) {
		$property_values[$property['name']] = trim((string) ($_POST['property'][$property['name']] ?? ''));}

	transaction(function () use ($current_user, $game_name, $selected_category_name, $time_milliseconds, $proof, $comment, $property_values) {
		sql(
			'insert into run (user_name, proof, game_name, category_name, time_milliseconds, comment) values (?, ?, ?, ?, ?, ?)',
			[$current_user['name'], $proof, $game_name, $selected_category_name, $time_milliseconds, $comment],);
		foreach ($property_values as $property_name => $property_value) {
			sql(
				'insert into run__property (run_user_name, run_proof, game_name, category_name, property_name, value) values (?, ?, ?, ?, ?, ?)',
				[$current_user['name'], $proof, $game_name, $selected_category_name, $property_name, $property_value],
			);}
	});
	notify_game_admins_and_moderators(
		$game_name,
		'new run submitted for ' . $game_name,
		"{$current_user['name']} submitted a run in $selected_category_name: " . format_run_time($time_milliseconds) . "\nproof: $proof\nplease review and verify it: " . BASE_URL . '/game/' . urlencode($game_name) . '/admin',);
	header("Location: /game/$game_name?category=" . urlencode($selected_category_name) . '&submitted=1');
	exit;}

require __DIR__ . '/../../templates/header.php';
?>
<form method="post">
	<!-- invisible default submit button so pressing enter in any text field submits the run instead of the (first, but visually last) change-category button -->
	<button type="submit" name="action" value="submit_run" tabindex="-1" aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;" <?= $category_names === [] ? 'disabled' : '' ?>></button>
	<p class="radio-tabs">
		category
		<? foreach ($category_names as $category): ?>
		<label><input type="radio" name="category" required value="<?= e($category) ?>" <?= $category === $selected_category_name ? 'checked' : '' ?> onchange="this.form.submit()"> <?= e($category) ?></label>
		<? endforeach; ?>
		<button type="submit" name="action" value="change_category" formnovalidate>change category</button>
	</p>
	<p>
		<label for="hours">hours</label>
		<input type="number" id="hours" name="hours" min="0" style="width:4em;" value="<?= e($_POST['hours'] ?? '0') ?>">
		<br>
		<label for="minutes">minutes</label>
		<input type="number" id="minutes" name="minutes" min="0" max="59" style="width:4em;" value="<?= e($_POST['minutes'] ?? '0') ?>">
		<br>
		<label for="seconds">seconds</label>
		<input type="number" id="seconds" name="seconds" min="0" max="59" style="width:4em;" value="<?= e($_POST['seconds'] ?? '0') ?>">
		<br>
		<label for="milliseconds">milliseconds</label>
		<input type="number" id="milliseconds" name="milliseconds" min="0" max="999" style="width:5em;" value="<?= e($_POST['milliseconds'] ?? '0') ?>">
	</p>
	<? foreach ($properties as $property): ?>
	<p><label><?= e($property['name']) ?></label> <input type="text" name="property[<?= e($property['name']) ?>]" maxlength="200" required value="<?= e($_POST['property'][$property['name']] ?? '') ?>"></p>
	<? endforeach; ?>
	<p><label for="proof">proof</label> <input type="url" id="proof" name="proof" maxlength="500" required value="<?= e($_POST['proof'] ?? '') ?>"> <? help_icon_html('link to a youtube video, twitch stream, or however else your run can be verified.'); ?></p>
	<p><label for="comment">comment</label> <input type="text" id="comment" name="comment" maxlength="300" value="<?= e($_POST['comment'] ?? '') ?>"></p>
	<button type="submit" name="action" value="submit_run" <?= $category_names === [] ? 'disabled' : '' ?>>submit run</button>
</form>
<?
require __DIR__ . '/../../templates/footer.php';
