<?
declare(strict_types=1);

function send_mail(string $to_address, string $subject, string $body): void {
	if (DEBUG_PRINT_MAILS)
		echo "MAIL DEBUG TO: $to_address<br>$subject<br><br>$body";
	else
		mail($to_address, $subject, $body, "From: speedruns.top <admin@speedruns.top>\r\n");}

function notify_site_admin(string $subject, string $body): void {
	send_mail(SITE_ADMIN_EMAIL, $subject, $body);}

function notify_game_admins_and_moderators(string $game_name, string $subject, string $body): void {
	$recipients = sql('select email from user u join game_admin a on a.user_name = u.name where a.game_name = ?
		union
		select email from user u join game_moderator m on m.user_name = u.name where m.game_name = ?',
		[$game_name, $game_name],);
	foreach ($recipients as $recipient) {
		send_mail($recipient['email'], $subject, $body);}}
