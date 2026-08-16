<?
declare(strict_types=1);

$game_name = $path_resource_id;
$game = sql_one('select name, image, rules, details from game where name = ?', [$game_name]);
if ($game === null) {
	render_page_not_found();
	exit;
}

$current_user = require_game_admin_or_moderator($game_name);
$current_user_is_admin = (bool) $current_user['is_site_admin'] || is_game_admin($game_name, $current_user['name']);

function require_game_admin(): void {
	global $current_user_is_admin;
	if (!$current_user_is_admin) {
		http_response_code(403);
		echo 'only game admins may do that.';
		exit;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? '');

	if ($action === 'add_category') {
		require_game_admin();
		sql('insert into category (game_name, name) values (?, ?)', [$game_name, trim((string) ($_POST['name'] ?? ''))]);
	} elseif ($action === 'remove_category') {
		require_game_admin();
		sql('delete from category where game_name = ? and name = ?', [$game_name, (string) ($_POST['name'] ?? '')]);
	} elseif ($action === 'edit_category_rules') {
		require_game_admin();
		sql(
			'update category set rules = ? where game_name = ? and name = ?',
			[trim((string) ($_POST['rules'] ?? '')), $game_name, (string) ($_POST['name'] ?? '')],
		);
	} elseif ($action === 'add_category_property') {
		require_game_admin();
		sql('insert into property (game_name, category_name, name) values (?, ?, ?)', [$game_name, (string) ($_POST['category_name'] ?? ''), trim((string) ($_POST['property_name'] ?? ''))]);
	} elseif ($action === 'remove_category_property') {
		require_game_admin();
		sql(
			'delete from property where game_name = ? and category_name = ? and name = ?',
			[$game_name, (string) ($_POST['category_name'] ?? ''), (string) ($_POST['property_name'] ?? '')],
		);
	} elseif ($action === 'add_admin_or_moderator') {
		require_game_admin();
		$user_name = (string) ($_POST['user_name'] ?? '');
		$role = (string) ($_POST['role'] ?? '');
		if ($role === 'admin') {
			sql('insert into game_admin (game_name, user_name) values (?, ?)', [$game_name, $user_name]);
		} else {
			sql('insert into game_moderator (game_name, user_name) values (?, ?)', [$game_name, $user_name]);
		}
	} elseif ($action === 'remove_moderator') {
		require_game_admin();
		sql('delete from game_moderator where game_name = ? and user_name = ?', [$game_name, (string) ($_POST['user_name'] ?? '')]);
	} elseif ($action === 'verify_run') {
		$run_user_name = (string) ($_POST['run_user_name'] ?? '');
		if (!$current_user_is_admin && $run_user_name === $current_user['name']) {
			http_response_code(403);
			echo 'moderators may not verify their own runs.';
			exit;
		}
		sql(
			'update run set verified = 1, verified_by = ? where user_name = ? and proof = ? and game_name = ?',
			[$current_user['name'], $run_user_name, (string) ($_POST['run_proof'] ?? ''), $game_name],
		);
	} elseif ($action === 'reject_run') {
		sql(
			'update run set verified = 0 where user_name = ? and proof = ? and game_name = ?',
			[(string) ($_POST['run_user_name'] ?? ''), (string) ($_POST['run_proof'] ?? ''), $game_name],
		);
	} elseif ($action === 'restore_run') {
		sql(
			'update run set verified = null where user_name = ? and proof = ? and game_name = ?',
			[(string) ($_POST['run_user_name'] ?? ''), (string) ($_POST['run_proof'] ?? ''), $game_name],
		);
	} elseif ($action === 'save_game_details') {
		require_game_admin();
		sql(
			'update game set details = ?, rules = ? where name = ?',
			[trim((string) ($_POST['details'] ?? '')), trim((string) ($_POST['rules'] ?? '')), $game_name],
		);
	} elseif ($action === 'upload_game_image') {
		require_game_admin();
		if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
			$scaled_image = scale_image_to_max_dimension((string) file_get_contents($_FILES['image']['tmp_name']), 450);
			write_blob('update game set image = ? where name = ?', $scaled_image, [$game_name]);
		}
	}

	header("Location: /game/$game_name/admin");
	exit;
}

$categories = sql('select c.name, c.rules, json_group_array(p.name) as properties from category c left join property p on c.name = p.category_name where c.game_name = ? group by c.name', [$game_name]);

$game_admin_user_names = array_column(sql('select user_name from game_admin where game_name = ? order by user_name', [$game_name]), 'user_name');
$game_moderator_user_names = array_column(sql('select user_name from game_moderator where game_name = ? order by user_name', [$game_name]), 'user_name');

$pending_runs = sql(
	'select user_name, category_name, time_milliseconds, proof from run where game_name = ? and verified is null and deleted_at is null order by created_at',
	[$game_name],
);
$rejected_runs = sql(
	'select user_name, category_name, time_milliseconds, proof from run where game_name = ? and verified = 0 order by created_at desc',
	[$game_name],
);

require __DIR__ . '/../../templates/header.php';
?>
<h3>categories (<?= count($categories) ?> / 20) <? help_icon_html('a game needs between 1 and 20 categories. example categories: any%, 100% glitchless, level 1-10. each category gets its own leaderboard. categories\' properties are optional and meant for informative values such as chosen character class. properties are required to be filled in when submitting a run.'); ?></h3>
<? foreach ($categories as $category): ?>
<fieldset>
	<legend><?= e($category['name']) ?></legend>
	<? if ($current_user_is_admin): ?>
	<form method="post">
		<input type="hidden" name="action" value="edit_category_rules">
		<input type="hidden" name="name" value="<?= e($category['name']) ?>">
		<p><label>rules</label><br><textarea name="rules" maxlength="4000" rows="4"><?= e($category['rules']) ?></textarea> <button type="submit">save rules</button></p>
	</form>
	<div>
		properties:
		<? foreach (json_decode($category['properties']) as $property): ?>
		<span class="tag"><?= e($property) ?>
			<form method="post" style="display:inline;">
				<input type="hidden" name="action" value="remove_category_property">
				<input type="hidden" name="category_name" value="<?= e($category['name']) ?>">
				<input type="hidden" name="property_name" value="<?= e($property) ?>">
				<button type="submit" class="secondary">x</button>
			</form>
		</span>
		<? endforeach; ?>
	</div>
	<form method="post">
		<input type="hidden" name="action" value="add_category_property">
		<input type="hidden" name="category_name" value="<?= e($category['name']) ?>">
		<input type="text" name="property_name" maxlength="50" placeholder="property name" required>
		<button type="submit">+ add property</button>
	</form>
	<form method="post">
		<input type="hidden" name="action" value="remove_category">
		<input type="hidden" name="name" value="<?= e($category['name']) ?>">
		<button type="submit">remove category</button>
	</form>
	<? endif; ?>
</fieldset>
<? endforeach; ?>
<? if ($current_user_is_admin): ?>
<form method="post">
	<input type="hidden" name="action" value="add_category">
	<input type="text" name="name" maxlength="50" placeholder="category name" required>
	<button type="submit">+ add category</button>
</form>
<? endif; ?>

<hr>
<h3>admins &amp; moderators <? help_icon_html('game admins keep their role forever and may add other admins and moderators. moderators can be removed again.'); ?></h3>
<? foreach ($game_admin_user_names as $admin_name): ?>
<p><?= e($admin_name) ?> - admin (permanent)</p>
<? endforeach; ?>
<? foreach ($game_moderator_user_names as $moderator_name): ?>
<p>
	<?= e($moderator_name) ?> - moderator
	<? if ($current_user_is_admin): ?>
	<form method="post" style="display:inline;">
		<input type="hidden" name="action" value="remove_moderator">
		<input type="hidden" name="user_name" value="<?= e($moderator_name) ?>">
		<button type="submit">remove</button>
	</form>
	<? endif; ?>
</p>
<? endforeach; ?>
<? if ($current_user_is_admin): ?>
<form method="post">
	<input type="hidden" name="action" value="add_admin_or_moderator">
	<label for="user_name">add user</label> <input type="text" id="user_name" name="user_name" maxlength="32" required>
	role
	<label><input type="radio" name="role" value="moderator" checked> moderator</label>
	<label><input type="radio" name="role" value="admin"> admin</label>
	<button type="submit">add</button>
</form>
<? endif; ?>

<hr>
<h3>pending runs (<?= count($pending_runs) ?>) <? help_icon_html('game admins and moderators can verify runs. game admins may even verify their own runs, but moderators may not.'); ?></h3>
<? foreach ($pending_runs as $run): ?>
<p>
	<?= e($run['user_name']) ?> &nbsp; <?= e($run['category_name']) ?> &nbsp; <?= e(format_run_time((int) $run['time_milliseconds'])) ?> &nbsp;
	<a href="<?= e($run['proof']) ?>">link</a>
	<form method="post" style="display:inline;">
		<input type="hidden" name="action" value="verify_run">
		<input type="hidden" name="run_user_name" value="<?= e($run['user_name']) ?>">
		<input type="hidden" name="run_proof" value="<?= e($run['proof']) ?>">
		<button type="submit">verify</button>
	</form>
	<form method="post" style="display:inline;">
		<input type="hidden" name="action" value="reject_run">
		<input type="hidden" name="run_user_name" value="<?= e($run['user_name']) ?>">
		<input type="hidden" name="run_proof" value="<?= e($run['proof']) ?>">
		<button type="submit">reject</button>
	</form>
</p>
<? endforeach; ?>

<hr>
<h3>rejected runs (<?= count($rejected_runs) ?>) <? help_icon_html('these can be restored back to pending.'); ?></h3>
<? foreach ($rejected_runs as $run): ?>
<p style="text-decoration:line-through;">
	<?= e($run['user_name']) ?> &nbsp; <?= e($run['category_name']) ?> &nbsp; <?= e(format_run_time((int) $run['time_milliseconds'])) ?>
	<form method="post" style="display:inline; text-decoration:none;">
		<input type="hidden" name="action" value="restore_run">
		<input type="hidden" name="run_user_name" value="<?= e($run['user_name']) ?>">
		<input type="hidden" name="run_proof" value="<?= e($run['proof']) ?>">
		<button type="submit">restore</button>
	</form>
</p>
<? endforeach; ?>

<? if ($current_user_is_admin): ?>
<hr>
<h3>game details</h3>
<? if ($game['image'] !== null): ?>
<img src="/game/<?= e($game_name) ?>/image" alt="" style="max-width:100px;max-height:100px;display:block;">
<? endif; ?>
<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="action" value="upload_game_image">
	<p>game image <?= $game['image'] !== null ? '(current image set)' : '(no image set)' ?> <input type="file" name="image" accept="image/*" required> <button type="submit">upload</button></p>
</form>
<form method="post">
	<input type="hidden" name="action" value="save_game_details">
	<p><label>game-wide rules</label><br><textarea name="rules" maxlength="4000" rows="4"><?= e($game['rules']) ?></textarea></p>
	<p><label>game details</label><br><textarea name="details" maxlength="4000" rows="8"><?= e($game['details']) ?></textarea></p>
	<button type="submit">save details</button>
</form>

<p>if you're missing a feature or have any other problems, please contact us via <a href="mailto:<?= SITE_ADMIN_EMAIL ?>"><?= SITE_ADMIN_EMAIL ?></a></p>
<? endif; ?>
<?
require __DIR__ . '/../../templates/footer.php';
