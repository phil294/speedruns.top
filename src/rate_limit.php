<?
declare(strict_types=1);

function get_client_ip_address(): string {
	$ip_address = $_SERVER['REMOTE_ADDR'];
	if (str_contains($ip_address, ':')) { // ipv6
		$network_prefix = substr((string) inet_pton($ip_address), 0, 8) . str_repeat("\0", 8);
		return (string) inet_ntop($network_prefix);
	}
	return $ip_address;
}

function rate_limit_action(string $path): void {
	if ($path === '/logout')
		return;
	$user = current_user();
	if ($user === null)
		return;
	if (!$user['is_site_admin'] && $user['seconds_until_next_action'] > 0) {
		render_rate_limit_error((int) $user['seconds_until_next_action']);
		exit;
	}
	sql('update user set last_action_at = datetime(\'now\') where name = ?', [$user['name']]);
}
function rate_limit_login_by_user(array $user): void {
	if ($user['seconds_until_next_login_link'] > 0) {
		render_rate_limit_error((int) $user['seconds_until_next_login_link']);
		exit;
	}
	sql('update user set last_login_link_sent_at = datetime(\'now\') where name = ?', [$user['name']]);
}

function rate_limit_login_attempts_by_ip(): void {
	$ip_address = get_client_ip_address();
	$attempts_today = (int) sql_one("select count(*) as count from login_link_attempt where ip_address = ? and created_at > datetime('now', '-1 day')", [$ip_address])['count'];
	if ($attempts_today >= MAX_LOGIN_ATTEMPTS_PER_IP_PER_DAY) {
		render_rate_limit_error(strtotime('tomorrow') - time());
		exit;
	}
	sql("insert into login_link_attempt (ip_address) values (?)", [$ip_address]);
}

/** does **not** `exit` */
function render_rate_limit_error(int $seconds_remaining): void {
	http_response_code(429);
	// TODO:
	// $page_title = 'please wait';
	global $breadcrumbs;
	require __DIR__ . '/../templates/header.php';
	?>
	<p>error: please wait <?= $seconds_remaining ?> more seconds before doing that. if you think this is an error, please contact <a href="mailto:<?= SITE_ADMIN_EMAIL ?>"><?= SITE_ADMIN_EMAIL ?></a></p>
	<form method="post">
		<? render_as_hidden_inputs($_POST); ?>
		<button type="submit">try again</button>
	</form>
	<?
	require __DIR__ . '/../templates/footer.php';
}
