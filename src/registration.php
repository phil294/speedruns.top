<?
declare(strict_types=1);

function generate_random_username(): string {
	return 'user_' . time() . '_' . random_int(100000, 999999);}

/** returns its `name` */
function find_or_create_user_by_email(string $email_address): string {
	$user = sql_one('select name from user where email = ?', [$email_address]);
	if ($user !== null)
		return $user['name'];
	$name = generate_random_username();
	sql('insert into user (name, email) values (?, ?)', [$name, $email_address]);
	notify_site_admin('new registration on speedruns.top', "$name registered using email $email_address.");
	return $name;}

/** returns its `name` */
function find_or_create_user_by_discord(string $discord_user_id): string {
	$user = sql_one('select name from user where discord_user_id = ?', [$discord_user_id]);
	if ($user !== null)
		return $user['name'];
	$name = generate_random_username();
	sql('insert into user (name, discord_user_id) values (?, ?)', [$name, $discord_user_id]);
	notify_site_admin('new registration on speedruns.top', "$name registered using discord.");
	return $name;}

function change_username(array $user, string $new_name): void {
	if ($user['username_already_changed'])
		throw new RuntimeException('you have already used your one-time username change.');
	sql('update user set name = ?, username_changed_at = datetime(\'now\') where name = ?', [$new_name, $user['name']]);}
