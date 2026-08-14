<?php
declare(strict_types=1);

function e(string|int|null $value): string {
	return htmlspecialchars((string) $value, ENT_QUOTES);
}

function help_icon(string $text): void {
	echo '<details class="help"><summary>?</summary><span>' . e($text) . '</span></details>';
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

function render_rules(string $rules_text): void {
	if ($rules_text === '') {
		return;
	}

	$lines = explode("\n", $rules_text);
	$preview = implode(' ', array_slice($lines, 0, 2));

	if (count($lines) <= 2 && mb_strlen($preview) <= 160) {
		echo '<p>' . nl2br(e($rules_text)) . '</p>';
		return;
	}

	echo '<details class="rules"><summary>' . e(mb_strimwidth($preview, 0, 160, '...')) . ' <em>vvv expand rules vvv</em></summary><p>' . nl2br(e($rules_text)) . '</p></details>';
}
