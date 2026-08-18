<?
declare(strict_types=1);

function e(string|int|null $value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES);
}

function render_as_hidden_inputs(array $data, string $name_prefix = ''): void {
	foreach ($data as $key => $value) {
		$name = $name_prefix === '' ? (string) $key : "{$name_prefix}[{$key}]";
		if (is_array($value))
			render_as_hidden_inputs($value, $name);
		else
			echo '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
	}
}

function help_icon_html(string $html): void {
	static $help_icon_count = 0;
	$id = 'help-' . (++$help_icon_count);
	echo '<button type="button" class="help" popovertarget="' . $id . '" style="anchor-name:--' . $id . ';">?</button>'
		. '<span id="' . $id . '" popover class="help" style="position-anchor:--' . $id . ';">' . $html . '</span>';
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

function markdown(string $text): string {
	$text = e($text);
	$text = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function ($m) {
        $url = str_replace('"', '%22', $m[2]);
        if (!preg_match('/^(https?:\/\/|\/|#)/i', $url)) {
            return $m[1]; // no javascript: etc
        }
		return "<a href=\"{$url}\">{$m[1]}</a>";
    }, $text);
	$text = preg_replace('/^#{1,6}\s+(.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*(?=\S)(.+?)(?<=\S)\*(?!\*)/s', '<em>$1</em>', $text);
	$text = preg_replace('/^[*-]\s+(.+)$/m', '<li>$1</li>', $text);
	$text = preg_replace('/((?:^<li>.*<\/li>\s*)+)/m', "<ul>\n$1</ul>", $text);

	$text = nl2br($text);
	$blocks = '(?:ul|ol|li|h[1-6])';
	$text = preg_replace('/<br\s*\/?>\s*(<\/?'.$blocks. ')/i', '$1', $text);
	return $text;
}