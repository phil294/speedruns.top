<?php
declare(strict_types=1);

const SITE_ADMIN_EMAIL = 'admin@speedruns.top';
const BASE_URL = 'https://speedruns.top';
const DISCORD_CLIENT_ID = '';
const DISCORD_CLIENT_SECRET = '';
const DISCORD_REDIRECT_URI = BASE_URL . '/discord-callback';

// flip to true once served over https in production, must stay false for
// plain-http local/docker testing since browsers drop secure cookies otherwise.
const COOKIE_SECURE = false;
