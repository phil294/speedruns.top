<?php
declare(strict_types=1);

$page_title = 'login';
$login_link_expiry_seconds = 30 * 60;

if (isset($_GET['token'])) {
	$login_link = sql_one('select * from login_link where token = ?', [$_GET['token']]);
	$link_is_valid = $login_link !== null
		&& $login_link['used_at'] === null
		&& (time() - strtotime($login_link['created_at'] . ' UTC')) <= $login_link_expiry_seconds;

	if ($link_is_valid) {
		write('update login_link set used_at = datetime(\'now\') where token = ?', [$login_link['token']]);
		$user = find_or_create_user_by_email($login_link['email']);
		log_in_as($user['name']);
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

	$email_address = trim((string) ($_POST['email'] ?? ''));
	if (filter_var($email_address, FILTER_VALIDATE_EMAIL) === false) {
		$error_message = 'please enter a valid email address.';
	} else {
		$client_ip_address = get_client_ip_address();
		require_ip_rate_limit_allowed('login_link_attempt', $client_ip_address, '/login', ['email' => $email_address]);

		$existing_user = sql_one('select * from user where email = ?', [$email_address]);
		if ($existing_user !== null) {
			$seconds_remaining = seconds_until_login_link_allowed($existing_user);
			if ($seconds_remaining > 0) {
				render_rate_limit_error($seconds_remaining, '/login', ['email' => $email_address]);
			}
			write('update user set last_login_link_sent_at = datetime(\'now\') where name = ?', [$existing_user['name']]);
		}

		$token = bin2hex(random_bytes(32));
		write('insert into login_link (token, email) values (?, ?)', [$token, $email_address]);
		send_mail(
			$email_address,
			'your speedruns.top login link',
			'click to log in: ' . BASE_URL . '/login?token=' . $token . "\n\nif you didn't request this, ignore this email.",
		);

		$confirmation_message = 'if that address is valid, a login link is on its way. it expires in 30 minutes.';
	}
}

require __DIR__ . '/../templates/header.php';
?>
<h2>login</h2>
<?php if (isset($confirmation_message)): ?>
<p><?= e($confirmation_message) ?></p>
<?php else: ?>
<?php if (isset($error_message)): ?>
<p><?= e($error_message) ?></p>
<?php endif; ?>
<form method="post" action="/login">
	<label for="email">email</label>
	<input type="email" id="email" name="email" required>
	<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;">
	<button type="submit">send login link</button>
	<?php help_icon("we'll email you a one-time link, no password needed. new addresses get a speedruns.top account automatically."); ?>
</form>
<hr>
<form method="get" action="/discord-login">
	<button type="submit">login with discord</button>
</form>
<?php endif; ?>
<?php
require __DIR__ . '/../templates/footer.php';
