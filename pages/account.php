<?
declare(strict_types=1);

$current_user = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string) ($_POST['action'] ?? '');

	if ($action === 'change_username') {
		try {
			change_username($current_user, trim((string) ($_POST['new_username'] ?? '')));
			header('Location: /account');
			exit;
		} catch (RuntimeException $exception) {
			$error_message = $exception->getMessage();
		}
	} elseif ($action === 'upload_profile_picture') {
		if (($_FILES['picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			$error_message = 'that image could not be uploaded, it may be too large.';
		} else {
			$scaled_image = scale_image_to_max_dimension((string) file_get_contents($_FILES['picture']['tmp_name']), 200);
			write_blob('update user set profile_picture = ? where name = ?', $scaled_image, [$current_user['name']]);
			header('Location: /account');
			exit;
		}
	}
}

require __DIR__ . '/../templates/header.php';
?>

<p>username: <?= e($current_user['name']) ?></p>
<? if ($current_user['email'] !== null): ?>
<p>email: <?= e($current_user['email']) ?></p>
<? endif; ?>
<p>public profile: <a href="/user/<?= e($current_user['name']) ?>">/user/<?= e($current_user['name']) ?></a></p>

<hr>
<h3>profile picture</h3>
<? if ($current_user['profile_picture'] !== null): ?>
<img src="/user/<?= e($current_user['name']) ?>/picture" alt="" style="max-width:100px;max-height:100px;display:block;">
<? endif; ?>
<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="action" value="upload_profile_picture">
	<p><?= $current_user['profile_picture'] !== null ? '(current picture set)' : '(no picture set)' ?> <input type="file" name="picture" accept="image/*" required> <button type="submit">upload</button></p>
</form>

<hr>
<h3>change username</h3>
<? if ($current_user['username_already_changed']): ?>
<p>you have already used your one-time username change.</p>
<? else: ?>
<p>you may change your username ONLY ONCE. choose carefully, this cannot be undone!</p>
<form method="post">
	<input type="hidden" name="action" value="change_username">
	<label for="new_username">new username</label> <input type="text" id="new_username" name="new_username" maxlength="32" required>
	<button type="submit">change username (once, permanently)</button>
</form>
<? endif; ?>
<?
require __DIR__ . '/../templates/footer.php';
