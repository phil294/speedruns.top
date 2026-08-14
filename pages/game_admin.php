<?php
declare(strict_types=1);

/** @var string $game_url_shorthand */

$game = sql_one('select * from game where url_shorthand = ?', [$game_url_shorthand]);
if ($game === null) {
	http_response_code(404);
	exit;
}

$current_user = require_game_admin_or_moderator($game_url_shorthand);
$current_user_is_admin = (bool) $current_user['is_site_admin'] || is_game_admin($game_url_shorthand, $current_user['name']);
$page_title = 'admin - ' . $game['name'];

function forbidden_unless(bool $condition): void {
	if (!$condition) {
		http_response_code(403);
		echo 'only game admins may do that.';
		exit;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require_action_allowed($current_user);
	$action = (string) ($_POST['action'] ?? '');

	if ($action === 'add_category') {
		forbidden_unless($current_user_is_admin);
		$name = trim((string) ($_POST['name'] ?? ''));
		$category_count = (int) sql_one('select count(*) as count from category where game_url_shorthand = ?', [$game_url_shorthand])['count'];
		if ($name !== '' && $category_count < 20) {
			write('insert into category (game_url_shorthand, name, sort_order) values (?, ?, ?)', [$game_url_shorthand, $name, $category_count]);
		}
	} elseif ($action === 'remove_category') {
		forbidden_unless($current_user_is_admin);
		$category_count = (int) sql_one('select count(*) as count from category where game_url_shorthand = ?', [$game_url_shorthand])['count'];
		if ($category_count > 1) {
			write('delete from category where game_url_shorthand = ? and name = ?', [$game_url_shorthand, (string) ($_POST['name'] ?? '')]);
		}
	} elseif ($action === 'edit_category_rules') {
		forbidden_unless($current_user_is_admin);
		write(
			'update category set rules = ? where game_url_shorthand = ? and name = ?',
			[trim((string) ($_POST['rules'] ?? '')), $game_url_shorthand, (string) ($_POST['name'] ?? '')],
		);
	} elseif ($action === 'edit_category_properties') {
		forbidden_unless($current_user_is_admin);
		$category_name = (string) ($_POST['name'] ?? '');
		$property_names = array_filter(array_map('trim', explode(',', (string) ($_POST['properties'] ?? ''))));
		write('delete from category__property where game_url_shorthand = ? and category_name = ?', [$game_url_shorthand, $category_name]);
		foreach ($property_names as $property_name) {
			write('insert or ignore into property (game_url_shorthand, name) values (?, ?)', [$game_url_shorthand, $property_name]);
			write(
				'insert into category__property (game_url_shorthand, category_name, property_name) values (?, ?, ?)',
				[$game_url_shorthand, $category_name, $property_name],
			);
		}
	} elseif ($action === 'add_admin_or_moderator') {
		forbidden_unless($current_user_is_admin);
		$user_name = (string) ($_POST['user_name'] ?? '');
		$role = (string) ($_POST['role'] ?? '');
		if (sql_one('select 1 from user where name = ?', [$user_name]) !== null) {
			if ($role === 'admin') {
				write('insert or ignore into game_admin (game_url_shorthand, user_name) values (?, ?)', [$game_url_shorthand, $user_name]);
			} elseif ($role === 'moderator') {
				write('insert or ignore into game_moderator (game_url_shorthand, user_name) values (?, ?)', [$game_url_shorthand, $user_name]);
			}
		}
	} elseif ($action === 'remove_moderator') {
		forbidden_unless($current_user_is_admin);
		write('delete from game_moderator where game_url_shorthand = ? and user_name = ?', [$game_url_shorthand, (string) ($_POST['user_name'] ?? '')]);
	} elseif ($action === 'verify_run') {
		write(
			"update run set status = 'verified', verified_by = ? where id = ? and game_url_shorthand = ? and status = 'pending'",
			[$current_user['name'], (string) ($_POST['run_id'] ?? ''), $game_url_shorthand],
		);
	} elseif ($action === 'reject_run') {
		write(
			"update run set status = 'rejected' where id = ? and game_url_shorthand = ? and status = 'pending'",
			[(string) ($_POST['run_id'] ?? ''), $game_url_shorthand],
		);
	} elseif ($action === 'restore_run') {
		write(
			"update run set status = 'pending' where id = ? and game_url_shorthand = ? and status in ('rejected', 'deleted')",
			[(string) ($_POST['run_id'] ?? ''), $game_url_shorthand],
		);
	} elseif ($action === 'save_game_details') {
		forbidden_unless($current_user_is_admin);
		write(
			'update game set details = ?, rules = ? where url_shorthand = ?',
			[trim((string) ($_POST['details'] ?? '')), trim((string) ($_POST['rules'] ?? '')), $game_url_shorthand],
		);
	} elseif ($action === 'upload_game_image' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
		forbidden_unless($current_user_is_admin);
		$scaled_image = scale_image_to_max_dimension((string) file_get_contents($_FILES['image']['tmp_name']), 450);
		write('update game set image = ? where url_shorthand = ?', [$scaled_image, $game_url_shorthand]);
	}

	header("Location: /game/$game_url_shorthand/admin");
	exit;
}

$categories = sql('select * from category where game_url_shorthand = ? order by sort_order', [$game_url_shorthand]);
foreach ($categories as &$category) {
	$category['properties'] = array_column(
		sql(
			'select property.name from property
			join category__property on category__property.property_name = property.name and category__property.game_url_shorthand = property.game_url_shorthand
			where category__property.game_url_shorthand = ? and category__property.category_name = ?
			order by property.name',
			[$game_url_shorthand, $category['name']],
		),
		'name',
	);
}
unset($category);

$game_admins = array_column(sql('select user_name from game_admin where game_url_shorthand = ? order by user_name', [$game_url_shorthand]), 'user_name');
$game_moderators = array_column(sql('select user_name from game_moderator where game_url_shorthand = ? order by user_name', [$game_url_shorthand]), 'user_name');

$pending_runs = sql(
	"select * from run where game_url_shorthand = ? and status = 'pending' order by created_at",
	[$game_url_shorthand],
);
$rejected_and_deleted_runs = sql(
	"select * from run where game_url_shorthand = ? and status in ('rejected', 'deleted') order by created_at desc",
	[$game_url_shorthand],
);

require __DIR__ . '/../templates/header.php';
?>
<h2>&lt; <?= e($game['name']) ?> / admin &gt;</h2>

<h3>categories (<?= count($categories) ?> / 20) <?php help_icon('a game needs between 1 and 20 categories. properties are comma-separated and required when submitting a run.'); ?></h3>
<?php foreach ($categories as $category): ?>
<p>
	<strong><?= e($category['name']) ?></strong>
	<?php if ($category['properties'] !== []): ?> (<?= e(implode(', ', $category['properties'])) ?>)<?php endif; ?>
	<?php if ($current_user_is_admin): ?>
	<details style="display:inline-block;"><summary>edit</summary>
		<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
			<input type="hidden" name="action" value="edit_category_rules">
			<input type="hidden" name="name" value="<?= e($category['name']) ?>">
			<p><label>rules</label><br><textarea name="rules" rows="2"><?= e($category['rules']) ?></textarea> <button type="submit">save rules</button></p>
		</form>
		<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
			<input type="hidden" name="action" value="edit_category_properties">
			<input type="hidden" name="name" value="<?= e($category['name']) ?>">
			<p><label>properties</label> <input type="text" name="properties" value="<?= e(implode(', ', $category['properties'])) ?>"> <button type="submit">save properties</button></p>
		</form>
		<?php if (count($categories) > 1): ?>
		<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
			<input type="hidden" name="action" value="remove_category">
			<input type="hidden" name="name" value="<?= e($category['name']) ?>">
			<button type="submit">remove category</button>
		</form>
		<?php endif; ?>
	</details>
	<?php endif; ?>
</p>
<?php endforeach; ?>
<?php if ($current_user_is_admin): ?>
<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
	<input type="hidden" name="action" value="add_category">
	<input type="text" name="name" placeholder="category name" required>
	<button type="submit">+ add category</button>
</form>
<?php endif; ?>

<hr>
<h3>admins &amp; moderators <?php help_icon('game admins keep their role forever and may add other admins and moderators. moderators can be removed again.'); ?></h3>
<?php foreach ($game_admins as $admin_name): ?>
<p><?= e($admin_name) ?> - admin (permanent)</p>
<?php endforeach; ?>
<?php foreach ($game_moderators as $moderator_name): ?>
<p>
	<?= e($moderator_name) ?> - moderator
	<?php if ($current_user_is_admin): ?>
	<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin" style="display:inline;">
		<input type="hidden" name="action" value="remove_moderator">
		<input type="hidden" name="user_name" value="<?= e($moderator_name) ?>">
		<button type="submit">remove</button>
	</form>
	<?php endif; ?>
</p>
<?php endforeach; ?>
<?php if ($current_user_is_admin): ?>
<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
	<input type="hidden" name="action" value="add_admin_or_moderator">
	<label for="user_name">add user</label> <input type="text" id="user_name" name="user_name" required>
	role
	<label><input type="radio" name="role" value="moderator" checked> moderator</label>
	<label><input type="radio" name="role" value="admin"> admin</label>
	<button type="submit">add</button>
</form>
<?php endif; ?>

<hr>
<h3>pending runs (<?= count($pending_runs) ?>) <?php help_icon('game admins may verify their own runs.'); ?></h3>
<?php foreach ($pending_runs as $run): ?>
<p>
	<?= e($run['user_name']) ?> &nbsp; <?= e($run['category_name']) ?> &nbsp; <?= e(format_run_time((int) $run['time_milliseconds'])) ?> &nbsp;
	<a href="<?= e($run['proof']) ?>">link</a>
	<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin" style="display:inline;">
		<input type="hidden" name="action" value="verify_run">
		<input type="hidden" name="run_id" value="<?= e($run['id']) ?>">
		<button type="submit">verify</button>
	</form>
	<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin" style="display:inline;">
		<input type="hidden" name="action" value="reject_run">
		<input type="hidden" name="run_id" value="<?= e($run['id']) ?>">
		<button type="submit">reject</button>
	</form>
</p>
<?php endforeach; ?>

<hr>
<h3>rejected / deleted runs (<?= count($rejected_and_deleted_runs) ?>) <?php help_icon('these can be restored back to pending.'); ?></h3>
<?php foreach ($rejected_and_deleted_runs as $run): ?>
<p style="text-decoration:line-through;">
	<?= e($run['user_name']) ?> &nbsp; <?= e($run['category_name']) ?> &nbsp; <?= e(format_run_time((int) $run['time_milliseconds'])) ?> &nbsp;
	<?= e($run['status']) ?>
	<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin" style="display:inline; text-decoration:none;">
		<input type="hidden" name="action" value="restore_run">
		<input type="hidden" name="run_id" value="<?= e($run['id']) ?>">
		<button type="submit">restore</button>
	</form>
</p>
<?php endforeach; ?>

<?php if ($current_user_is_admin): ?>
<hr>
<h3>game details</h3>
<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin" enctype="multipart/form-data">
	<input type="hidden" name="action" value="upload_game_image">
	<p>game image <?= $game['image'] !== null ? '(current image set)' : '(no image set)' ?> <input type="file" name="image" accept="image/*" required> <button type="submit">upload</button></p>
</form>
<form method="post" action="/game/<?= e($game_url_shorthand) ?>/admin">
	<input type="hidden" name="action" value="save_game_details">
	<p><label>game-wide rules</label><br><textarea name="rules" rows="2"><?= e($game['rules']) ?></textarea></p>
	<p><label>game details</label><br><textarea name="details" rows="4"><?= e($game['details']) ?></textarea></p>
	<button type="submit">save details</button>
</form>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
