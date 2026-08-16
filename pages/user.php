<?
declare(strict_types=1);

$users = sql('select u.profile_picture, u.name, u.created_at, count(r.proof) as run_count from user u left join run r on u.name = r.user_name and r.verified = 1 and r.deleted_at is null group by u.name order by r.created_at asc');

require __DIR__ . '/../templates/header.php';
?>
<table>
	<? foreach ($users as $user): ?>
	<tr>
		<td>
			<? if ($user['profile_picture'] !== null): ?>
			<img src="/user/<?= e($user['name']) ?>/picture" alt="" style="max-width:100px;max-height:100px;display:block;">
			<? endif; ?>
		</td>
		<td><a href="/user/<?= e($user['name']) ?>"><?= e($user['name']) ?></a></td>
		<td><?= e((int) $user['run_count']) ?> verified runs</td>
	</tr>
	<? endforeach; ?>
</table>
<?
require __DIR__ . '/../templates/footer.php';
