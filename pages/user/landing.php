<?
declare(strict_types=1);

$user_name = $path_resource_id;
$user = sql_one('select profile_picture, created_at from user where name = ?', [$user_name]);
if ($user === null) {
	render_page_not_found();
	exit;}

$submissions = sql(
	'select r.verified, r.time_milliseconds, r.created_at, r.category_name, r.proof, g.name as game_name, json_group_array(rp.value order by p.name asc) as property_values
	from run r
	join game g on g.name = r.game_name
	left join property p on r.game_name = p.game_name and r.category_name = p.category_name
	left join run__property rp on r.proof = rp.run_proof and r.user_name = rp.run_user_name and p.name = rp.property_name
	where r.user_name = ? and r.deleted_at is null
	group by r.proof
	order by r.created_at desc',
	[$user_name],);

require __DIR__ . '/../../templates/header.php';
?>
<? if ($user['profile_picture'] !== null): ?>
<img src="/user/<?= e($user_name) ?>/picture" alt="profile picture" style="max-width:100px;max-height:100px;display:block;">
<? endif; ?>
<p>member since <?= e(format_date($user['created_at'])) ?></p>

<h3>submissions (<?= count($submissions) ?>)</h3>
<table class="runs">
	<? foreach ($submissions as $run):
	$verified_str = $run['verified'] === null ? 'verification-pending' : ($run['verified'] === 1 ? 'verified' : 'rejected'); ?>
	<tr class="<?= $verified_str ?>">
		<td><?= e(format_date($run['created_at'])) ?></td>
		<td><a href="/game/<?= e($run['game_name']) ?>"><?= e($run['game_name']) ?></a> / <?= e($run['category_name']) ?></td>
		<td class="run-time"><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
		<? foreach (json_decode($run['property_values']) as $property): ?>
		<td><?= e($property ?? '') ?></td>
		<? endforeach; ?>
		<td><a href="<?= e($run['proof']) ?>">link</a></td>
		<td><span class="tag <?= $verified_str ?>"><?= $verified_str ?></span></td>
	</tr>
	<? endforeach; ?>
</table>
<?
require __DIR__ . '/../../templates/footer.php';
