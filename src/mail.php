<?php
declare(strict_types=1);

function send_mail(string $to_address, string $subject, string $body): void {
	mail($to_address, $subject, $body, "From: speedruns.top <no-reply@speedruns.top>\r\n");
}

function notify_site_admin(string $subject, string $body): void {
	send_mail(SITE_ADMIN_EMAIL, $subject, $body);
}

function notify_game_admins_and_moderators(string $game_url_shorthand, string $subject, string $body): void {
	$recipients = sql(
		'select email from user join game_admin on game_admin.user_name = user.name where game_admin.game_url_shorthand = ?
		union
		select email from user join game_moderator on game_moderator.user_name = user.name where game_moderator.game_url_shorthand = ?',
		[$game_url_shorthand, $game_url_shorthand],
	);
	foreach ($recipients as $recipient) {
		if ($recipient['email'] !== null) {
			send_mail($recipient['email'], $subject, $body);
		}
	}
}
