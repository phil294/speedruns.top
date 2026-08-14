<?php
declare(strict_types=1);

function generate_unique_username(string $base_name): string {
	$sanitized_base_name = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $base_name));
	$sanitized_base_name = trim($sanitized_base_name, '_');
	if ($sanitized_base_name === '') {
		$sanitized_base_name = 'runner';
	}

	$candidate_username = $sanitized_base_name;
	$suffix = 1;
	while (sql_one('select 1 from user where name = ?', [$candidate_username]) !== null) {
		$suffix++;
		$candidate_username = $sanitized_base_name . $suffix;
	}
	return $candidate_username;
}

function find_or_create_user_by_email(string $email_address): array {
	$user = sql_one('select * from user where email = ?', [$email_address]);
	if ($user !== null) {
		return $user;
	}

	$username = generate_unique_username(explode('@', $email_address)[0]);
	write('insert into user (name, email) values (?, ?)', [$username, $email_address]);
	notify_site_admin('new registration on speedruns.top', "$username registered using email $email_address.");
	return sql_one('select * from user where name = ?', [$username]);
}

function find_or_create_user_by_discord(string $discord_user_id, string $discord_username): array {
	$user = sql_one('select * from user where discord_user_id = ?', [$discord_user_id]);
	if ($user !== null) {
		return $user;
	}

	$username = generate_unique_username($discord_username);
	write('insert into user (name, discord_user_id) values (?, ?)', [$username, $discord_user_id]);
	notify_site_admin('new registration on speedruns.top', "$username registered using discord.");
	return sql_one('select * from user where name = ?', [$username]);
}
