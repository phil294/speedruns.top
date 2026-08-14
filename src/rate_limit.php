<?php
declare(strict_types=1);

const SECONDS_BETWEEN_ACTIONS = 60;
const LOGIN_LINK_SECONDS_BETWEEN_REQUESTS = 15 * 60;
const MAXIMUM_ATTEMPTS_PER_IP_PER_DAY = 3;

function get_client_ip_address(): string {
	return $_SERVER['REMOTE_ADDR'];
}

function seconds_until_next_action_allowed(array $user): int {
	if ($user['last_action_at'] === null) {
		return 0;
	}
	$elapsed_seconds = time() - strtotime($user['last_action_at'] . ' UTC');
	return max(0, SECONDS_BETWEEN_ACTIONS - $elapsed_seconds);
}

function require_action_allowed(array $user, ?string $retry_url = null, array $retry_fields = []): void {
	$seconds_remaining = seconds_until_next_action_allowed($user);
	if ($seconds_remaining > 0) {
		render_rate_limit_error($seconds_remaining, $retry_url, $retry_fields);
	}
	write('update user set last_action_at = datetime(\'now\') where name = ?', [$user['name']]);
}

function seconds_until_login_link_allowed(array $user): int {
	if ($user['last_login_link_sent_at'] === null) {
		return 0;
	}
	$elapsed_seconds = time() - strtotime($user['last_login_link_sent_at'] . ' UTC');
	return max(0, LOGIN_LINK_SECONDS_BETWEEN_REQUESTS - $elapsed_seconds);
}

function ip_attempts_today(string $table_name, string $ip_address): int {
	$row = sql_one("select count(*) as count from $table_name where ip_address = ? and created_at > datetime('now', '-1 day')", [$ip_address]);
	return (int) $row['count'];
}

function require_ip_rate_limit_allowed(string $table_name, string $ip_address, ?string $retry_url = null, array $retry_fields = []): void {
	if (ip_attempts_today($table_name, $ip_address) >= MAXIMUM_ATTEMPTS_PER_IP_PER_DAY) {
		render_rate_limit_error((int) (strtotime('tomorrow') - time()), $retry_url, $retry_fields);
	}
	write("insert into $table_name (ip_address) values (?)", [$ip_address]);
}

function render_rate_limit_error(int $seconds_remaining, ?string $retry_url, array $retry_fields): never {
	http_response_code(429);
	require __DIR__ . '/../templates/header.php';
	?>
	<p>error: please wait <?= $seconds_remaining ?> more seconds before doing anything else.</p>
	<?php if ($retry_url !== null): ?>
	<form method="post" action="<?= e($retry_url) ?>">
		<?php foreach ($retry_fields as $field_name => $field_value): ?>
		<input type="hidden" name="<?= e($field_name) ?>" value="<?= e($field_value) ?>">
		<?php endforeach; ?>
		<button type="submit">retry</button>
	</form>
	<?php else: ?>
	<button type="button" onclick="location.reload()">reload</button>
	<?php endif; ?>
	<?php
	require __DIR__ . '/../templates/footer.php';
	exit;
}
