<?
declare(strict_types=1);

rate_limit_login_attempts_by_ip();

header('Location: ' . discord_authorize_url());
exit;
