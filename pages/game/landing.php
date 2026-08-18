<?
declare(strict_types=1);

$game_name = $path_resource_id;
$game = sql_one('select name, rules, details, image from game where name = ?', [$game_name]);
if ($game === null) {
	render_page_not_found();
	exit;}
$categories = sql('select name, rules from category where game_name = ?', [$game_name]);
$selected_category_name = $_GET['category'] ?? $categories[0]['name'] ?? null;
$selected_category = $selected_category_name !== null ? array_filter($categories, fn($c) => $c['name'] === $selected_category_name)[0] ?? null : null;
if ($selected_category === null)
	$selected_category_name = null;

$show_all_runs_per_user = ($_GET['showruns'] ?? '') === 'all';

$runs = [];
$property_names = [];
if ($selected_category !== null) {
	$runs = sql(
		"select r.user_name, r.proof, r.time_milliseconds, r.comment, r.verified, r.created_at, u.profile_picture, json_group_array(rp.value order by p.name asc) as property_values
		from (
			select *, row_number() over (partition by user_name order by time_milliseconds asc) as per_user_rank
			from run
			where game_name = ? and category_name = ? and deleted_at is null and (verified is null or verified = 1)
		) r
		inner join user u on u.name = r.user_name
		left join property p on r.game_name = p.game_name and r.category_name = p.category_name
		left join run__property rp on r.proof = rp.run_proof and r.user_name = rp.run_user_name and p.name = rp.property_name
		" . ($show_all_runs_per_user ? "" : "where r.per_user_rank = 1") . "
		group by r.user_name, r.proof
		order by r.time_milliseconds asc",
		[$game_name, $selected_category_name]);

	$property_names = array_column(sql('select name from property where game_name = ? and category_name = ? order by name asc', [$game_name, $selected_category_name]), 'name');}

$game_admin_user_names = array_column(sql('select user_name from game_admin where game_name = ? order by user_name', [$game_name]), 'user_name');
$game_moderator_user_names = array_column(sql('select user_name from game_moderator where game_name = ? order by user_name', [$game_name]), 'user_name');
$current_user = current_user();
$current_user_manages_game = $current_user !== null && ($current_user['is_site_admin'] || in_array($current_user['name'], $game_admin_user_names, true) || in_array($current_user['name'], $game_moderator_user_names, true));

require __DIR__ . '/../../templates/header.php';
?>
<? if ($current_user_manages_game): ?>
<p><a href="/game/<?= e($game_name) ?>/admin">manage this game</a></p>
<? endif; ?>

<div class="game-header">
	<div class="game-header-content">
		<? if ($game['details']): ?>
		<p><?= nl2br(e($game['details'])) ?></p>
		<? endif; ?>

		<form method="get" class="radio-tabs">
			<? foreach ($categories as $category): ?>
			<label><input type="radio" name="category" required value="<?= e($category['name']) ?>" <?= $category['name'] === $selected_category_name ? 'checked' : '' ?> onchange="this.form.submit()"> <?= e($category['name']) ?></label>
			<? endforeach; ?>
			<button type="submit">view</button>
			<span class="spacer"></span>
			<a href="/game/<?= e($game_name) ?>/submit-run" class="prominent-link">+ submit a run</a>
		</form>

		<details>
			<summary>game rules</summary>
			<?= markdown(e($game['rules'])) ?>
		</details>

		<? if ($selected_category !== null): ?>
		<details>
			<summary>category rules</summary>
			<?= markdown(e($selected_category['rules'])) ?>
		</details>
		<? endif; ?>
	</div>
	<? if ($game['image'] !== null): ?>
	<img src="/game/<?= e($game_name) ?>/image" alt="game logo" class="game-image">
	<? endif; ?>
</div>

<? if ($selected_category !== null): ?>
<p>
	<a href="/game/<?= e($game_name) ?>?category=<?= urlencode($selected_category_name) ?><?= $show_all_runs_per_user ? '' : '&showruns=all' ?>">
		<?= $show_all_runs_per_user ? 'show only best run per player' : 'show all runs per player' ?>
	</a>
</p>
<table class="leaderboard runs">
	<thead>
		<tr>
			<th>#</th>
			<th>time</th>
			<th>player</th>
			<? foreach ($property_names as $property): ?>
			<th><?= e($property) ?></th>
			<? endforeach; ?>
			<th>date</th>
			<th>proof</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($runs as $run):
		$verified_str = $run['verified'] === null ? 'verification-pending' : ($run['verified'] === 1 ? 'verified' : 'rejected'); ?>
		<tr class="<?= $verified_str ?>">
			<td></td>
			<td><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
			<td>
				<? if ($run['profile_picture'] !== null): ?>
				<img class="avatar" src="/user/<?= e($run['user_name']) ?>/picture" alt="profile picture">
				<? endif; ?>
				<a href="/user/<?= e($run['user_name']) ?>"><?= e($run['user_name']) ?></a>
			</td>
			<? foreach (json_decode($run['property_values']) as $property): ?>
			<td><?= e($property ?? '') ?></td>
			<? endforeach; ?>
			<td><?= e(format_date($run['created_at'])) ?></td>
			<td><a href="<?= e($run['proof']) ?>">link</a></td>
			<td><span class="tag <?= $verified_str ?>"><?= $verified_str ?></span></td>
		</tr>
		<? endforeach; ?>
	</tbody>
</table>
<? else: ?>
<p>no run categories configured yet.</p>
<? endif; ?>

<hr>
<p><? help_icon_html('game admins and moderators verify submitted runs. game admins edit the categories and set mandatory run properties.'); ?></p>
<? foreach ($game_admin_user_names as $admin): ?>
<p><?= e($admin) ?> - admin</p>
<? endforeach; ?>
<? foreach ($game_moderator_user_names as $moderator): ?>
<p><?= e($moderator) ?> - moderator</p>
<? endforeach; ?>
<?
require __DIR__ . '/../../templates/footer.php';
