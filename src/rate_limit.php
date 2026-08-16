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

function rate_limit_action(): void {
	$user = current_user();
	if ($user === null) {
		return;
	}
	if (!$user['is_site_admin'] && $user['last_action_at'] !== null) {
		// TODO: move this into sql as computed part of what the current_user() query returns as a `seconds_remaining_until_next_action`, then remove this property from the queried user object
		$elapsed_seconds = time() - strtotime($user['last_action_at'] . ' UTC');
		$min_seconds_between_actions = 30;
		$seconds_remaining = max(0, $min_seconds_between_actions - $elapsed_seconds);
		if ($seconds_remaining > 0) {
			render_rate_limit_error($seconds_remaining);
			exit;
		}
	}
	write('update user set last_action_at = datetime(\'now\') where name = ?', [$user['name']]);
}
function rate_limit_login_by_user(array $user) {
	if ($user['last_login_link_sent_at'] !== null) {
		// TODO: same as above
		$elapsed_seconds = time() - strtotime($user['last_login_link_sent_at'] . ' UTC');
		$min_seconds_between_login_link_requests = 15 * 60;
		$seconds_remaining = max(0, $min_seconds_between_login_link_requests - $elapsed_seconds);
		if ($seconds_remaining > 0) {
			render_rate_limit_error($seconds_remaining);
			exit;
		}
	}
	write('update user set last_login_link_sent_at = datetime(\'now\') where name = ?', [$user['name']]);
}

function rate_limit_login_attempts_by_ip() {
	$ip_address = get_client_ip_address();
	$attempts_today = (int) sql_one("select count(*) as count from login_link_attempt where ip_address = ? and created_at > datetime('now', '-1 day')", [$ip_address])['count'];
	$max_login_attempts_per_ip_per_day = 5;
	if ($attempts_today >= $max_login_attempts_per_ip_per_day) {
		render_rate_limit_error(strtotime('tomorrow') - time());
		exit;
	}
	write("insert into login_link_attempt (ip_address) values (?)", [$ip_address]);
}

/** does **not** `exit` */
function render_rate_limit_error(int $seconds_remaining) {
	http_response_code(429);
	// TODO:
	// $page_title = 'please wait';
	global $breadcrumbs;
	require __DIR__ . '/../templates/header.php';
	?>
	<p>error: please wait <?= $seconds_remaining ?> more seconds before doing that. if you think this is an error, please contact admin@speedruns.top</p>
	<!-- TODO: change this to js-less by resubmitting all $_POST data via hidden inputs -->
	<button type="button" onclick="location.reload()">try again</button>
	<?
	require __DIR__ . '/../templates/footer.php';
}
