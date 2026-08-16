<?
declare(strict_types=1);

function e(string|int|null $value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES);
}

// TODO: this works great but cant be placed inside <p> elements. must use something like button popovertarget with position-anchor instead for correct a11y. maybe this helper needs to go then.
function help_icon_html(string $text): void {
	echo '<details class="help"><summary>?</summary><span>' . $text . '</span></details>';
}

function format_run_time(int $time_milliseconds): string {
	$hours = intdiv($time_milliseconds, 3_600_000);
	$minutes = intdiv($time_milliseconds % 3_600_000, 60_000);
	$seconds = intdiv($time_milliseconds % 60_000, 1000);
	$milliseconds = $time_milliseconds % 1000;
	$hour_part = $hours > 0 ? sprintf('%d:', $hours) : '';
	return sprintf('%s%02d:%02d.%03d', $hour_part, $minutes, $seconds, $milliseconds);
}

function format_date(string $sqlite_datetime): string {
	return substr($sqlite_datetime, 0, 10);
}

/** does **not** `exit` */
function render_page_not_found(): void {
	http_response_code(404);
	global $breadcrumbs;
	require __DIR__ . '/../templates/header.php';
	echo '<p>404 not found :(</p><br><a href="/">back to main page</a>';
	require __DIR__ . '/../templates/footer.php';
}