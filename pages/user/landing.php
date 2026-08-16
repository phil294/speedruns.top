<?
declare(strict_types=1);

$user_name = $path_resource_id;
$user = sql_one('select profile_picture, created_at from user where name = ?', [$user_name]);
if ($user === null) {
	render_page_not_found();
	exit;
}

$submissions = sql(
	'select r.verified, r.time_milliseconds, r.created_at, r.category_name, g.name as game_name from run r
	join game g on g.name = r.game_name
	where r.user_name = ? and r.deleted_at is null
	order by r.created_at desc',
	[$user_name],
);

require __DIR__ . '/../../templates/header.php';
?>
<? if ($user['profile_picture'] !== null): ?>
<img src="/user/<?= e($user_name) ?>/picture" alt="" style="max-width:100px;max-height:100px;display:block;">
<? endif; ?>
<p>member since <?= e(format_date($user['created_at'])) ?></p>

<h3>submissions (<?= count($submissions) ?>)</h3>
<table>
	<? foreach ($submissions as $run): ?>
	<tr>
		<td><?= e(format_date($run['created_at'])) ?></td>
		<td><a href="/game/<?= e($run['game_name']) ?>"><?= e($run['game_name']) ?></a> / <?= e($run['category_name']) ?></td>
		<td><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
		<td><span class="tag"><?= $run['verified'] === null ? 'pending' : ($run['verified'] === 1 ? 'verified' : 'rejected') ?></span></td>
	</tr>
	<? endforeach; ?>
</table>
<?
require __DIR__ . '/../../templates/footer.php';
