<?php
declare(strict_types=1);

$login_link_expiry_seconds = 30 * 60;

if (isset($_GET['token'])) {
	$login_link = sql_one('select created_at, used_at, token, email from login_link where token = ?', [$_GET['token']]);
	$link_is_valid = $login_link !== null
		// TODO: move these two checks into the sql query instead, then change var $login_link to $email instead of *-select
		&& $login_link['used_at'] === null
		&& (time() - strtotime($login_link['created_at'] . ' UTC')) <= $login_link_expiry_seconds;

	if ($link_is_valid) {
		write('update login_link set used_at = datetime(\'now\') where token = ?', [$login_link['token']]);
		$username = find_or_create_user_by_email($login_link['email']);
		log_in_as($username);
		header('Location: /');
		exit;
	}

	$error_message = 'this login link is invalid or has expired. request a new one below.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (($_POST['website'] ?? '') !== '') {
		http_response_code(400);
		exit;
	}
	rate_limit_login_attempts_by_ip();
	$email = trim((string) ($_POST['email'] ?? ''));
	if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
		$error_message = 'invalid email??';
	} else {
		$existing_user = sql_one('select last_login_link_sent_at, name from user where email = ?', [$email]);
		if ($existing_user !== null) {
			rate_limit_login_by_user($existing_user);
		}
		$token = bin2hex(random_bytes(32));
		write('insert into login_link (token, email) values (?, ?)', [$token, $email]);
		send_mail($email, 'your speedruns.top login link', 'click to log in: ' . BASE_URL . '/login?token=' . $token . "\n\nif you didn't request this login, please ignore this email.");
		$confirmation_message = 'a login link is on its way. it expires in 30 minutes.';
	}
}

require __DIR__ . '/../templates/header.php';
?>
<?php if (isset($confirmation_message)): ?>
<p><?= e($confirmation_message) ?></p>
<?php endif; ?>
<form method="post" action="/login">
	<label for="email">email</label>
	<input type="email" id="email" name="email" maxlength="254" required>
	<?php /* honeypot */ ?>
	<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;">
	<button type="submit">send login link</button>
	<?php help_icon_html("we'll email you a one-time link. this site uses no passwords. new addresses get a speedruns.top account automatically upon clicking the link."); ?>
</form>
<hr>
<form method="get" action="/discord-login">
	<button type="submit">login with discord</button>
</form>
<?php
require __DIR__ . '/../templates/footer.php';
