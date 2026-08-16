<?
declare(strict_types=1);

const SITE_ADMIN_EMAIL = 'admin@speedruns.top';
// Set these in ../config.local.php:
// const BASE_URL = 'https://speedruns.top';
// const DEBUG_PRINT_MAILS = false;
// const DISCORD_CLIENT_ID = '';
// const DISCORD_CLIENT_SECRET = '';
const DISCORD_REDIRECT_URI = BASE_URL . '/discord-callback';

const MIN_SECONDS_BETWEEN_ACTIONS = 30;
const MIN_SECONDS_BETWEEN_LOGIN_LINK_REQUESTS = 15 * 60;
const MAX_LOGIN_ATTEMPTS_PER_IP_PER_DAY = 5;

define('COOKIE_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
