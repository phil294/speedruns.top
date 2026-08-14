<?php
declare(strict_types=1);

/** @var string $game_url_shorthand */

$game = sql_one('select * from game where url_shorthand = ?', [$game_url_shorthand]);
if ($game === null) {
	http_response_code(404);
	$page_title = 'not found';
	require __DIR__ . '/../templates/header.php';
	echo '<p>no such game.</p>';
	require __DIR__ . '/../templates/footer.php';
	exit;
}

$page_title = $game['name'];

$categories = sql('select * from category where game_url_shorthand = ? order by sort_order', [$game_url_shorthand]);
$selected_category_name = $_GET['category'] ?? ($categories[0]['name'] ?? null);
$selected_category = null;
foreach ($categories as $category) {
	if ($category['name'] === $selected_category_name) {
		$selected_category = $category;
	}
}

$properties = $selected_category !== null ? sql(
	'select property.name from property
	join category__property on category__property.property_name = property.name and category__property.game_url_shorthand = property.game_url_shorthand
	where category__property.game_url_shorthand = ? and category__property.category_name = ?
	order by property.name',
	[$game_url_shorthand, $selected_category_name],
) : [];

$runs = $selected_category !== null ? sql(
	"select run.*, user.profile_picture from run
	join user on user.name = run.user_name
	where run.game_url_shorthand = ? and run.category_name = ? and run.status in ('pending', 'verified')
	order by run.time_milliseconds asc",
	[$game_url_shorthand, $selected_category_name],
) : [];

$property_value_map = [];
if ($runs !== []) {
	$run_ids = array_column($runs, 'id');
	$placeholders = implode(',', array_fill(0, count($run_ids), '?'));
	foreach (sql("select run_id, property_name, value from run__property where run_id in ($placeholders)", $run_ids) as $property_value) {
		$property_value_map[$property_value['run_id']][$property_value['property_name']] = $property_value['value'];
	}
}

$verified_rank = 0;
$game_admins = sql('select user_name from game_admin where game_url_shorthand = ? order by user_name', [$game_url_shorthand]);
$game_moderators = sql('select user_name from game_moderator where game_url_shorthand = ? order by user_name', [$game_url_shorthand]);
$current_user = current_user();
$current_user_manages_game = $current_user !== null
	&& ($current_user['is_site_admin'] || is_game_admin($game_url_shorthand, $current_user['name']) || is_game_moderator($game_url_shorthand, $current_user['name']));

require __DIR__ . '/../templates/header.php';
?>
<h2>&lt; <?= e($game['name']) ?> &gt; <?php help_icon('this is a speedrun leaderboard. pick a category to see its runs and rules.'); ?></h2>

<?php if ($game['image'] !== null): ?>
<img src="/game/<?= e($game_url_shorthand) ?>/image" alt="" style="max-width:200px;display:block;">
<?php endif; ?>
<?php if ($game['details'] !== ''): ?>
<p><?= nl2br(e($game['details'])) ?></p>
<?php endif; ?>

<nav>
	<?php foreach ($categories as $category): ?>
	<a href="/game/<?= e($game_url_shorthand) ?>?category=<?= urlencode($category['name']) ?>"><?= $category['name'] === $selected_category_name ? '[ ' . e($category['name']) . ' ]' : e($category['name']) ?></a>
	<?php endforeach; ?>
	<span class="spacer"></span>
	<a href="/game/<?= e($game_url_shorthand) ?>/submit">+ submit a run</a>
</nav>

<?php render_rules($game['rules']); ?>

<?php if ($selected_category !== null): ?>
<?php render_rules($selected_category['rules']); ?>
<table>
	<thead>
		<tr>
			<th>#</th>
			<th>time</th>
			<th>player</th>
			<?php foreach ($properties as $property): ?>
			<th><?= e($property['name']) ?></th>
			<?php endforeach; ?>
			<th>date</th>
			<th>proof</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($runs as $run): ?>
		<?php if ($run['status'] === 'verified') { $verified_rank++; } ?>
		<tr>
			<td><?= $run['status'] === 'verified' ? $verified_rank : '-' ?></td>
			<td><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
			<td>
				<?php if ($run['profile_picture'] !== null): ?>
				<img class="avatar" src="data:image/png;base64,<?= base64_encode($run['profile_picture']) ?>" alt="">
				<?php endif; ?>
				<?= e($run['user_name']) ?>
			</td>
			<?php foreach ($properties as $property): ?>
			<td><?= e($property_value_map[$run['id']][$property['name']] ?? '') ?></td>
			<?php endforeach; ?>
			<td><?= e(format_date($run['created_at'])) ?></td>
			<td><a href="<?= e($run['proof']) ?>">link</a></td>
			<td><?= $run['status'] === 'pending' ? '<span class="tag">unverified</span>' : '' ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else: ?>
<p>this game has no categories yet.</p>
<?php endif; ?>

<hr>
<p>
	game admins: <?= e(implode(', ', array_column($game_admins, 'user_name'))) ?>
	&nbsp; moderators: <?= e(implode(', ', array_column($game_moderators, 'user_name'))) ?>
	<?php help_icon('game admins and moderators verify submitted runs and manage the leaderboard.'); ?>
</p>
<?php if ($current_user_manages_game): ?>
<p><a href="/game/<?= e($game_url_shorthand) ?>/admin">manage this game</a></p>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
