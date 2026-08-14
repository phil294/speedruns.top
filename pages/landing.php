<?php
declare(strict_types=1);

$page_title = 'games';

$games = sql(
	"select game.url_shorthand, game.name, leader.category_name, leader.time_milliseconds, leader.user_name
	from game
	left join (
		select
			run.game_url_shorthand,
			run.category_name,
			run.time_milliseconds,
			run.user_name,
			row_number() over (
				partition by run.game_url_shorthand
				order by category.sort_order, run.time_milliseconds
			) as leaderboard_rank
		from run
		join category on category.game_url_shorthand = run.game_url_shorthand and category.name = run.category_name
		where run.status = 'verified'
	) as leader on leader.game_url_shorthand = game.url_shorthand and leader.leaderboard_rank = 1
	order by game.name",
);

$recent_runs = sql(
	"select run.time_milliseconds, run.status, run.created_at, run.user_name, run.category_name, game.url_shorthand, game.name as game_name
	from run
	join game on game.url_shorthand = run.game_url_shorthand
	where run.status in ('pending', 'verified')
	order by run.created_at desc
	limit 10",
);

require __DIR__ . '/../templates/header.php';
?>
<h2>games (<?= count($games) ?>) <a href="/games/request" style="float:right;">+ request a game</a></h2>
<table>
	<?php foreach ($games as $game): ?>
	<tr>
		<td><a href="/game/<?= e($game['url_shorthand']) ?>"><?= e($game['name']) ?></a></td>
		<?php if ($game['category_name'] !== null): ?>
		<td><?= e($game['category_name']) ?></td>
		<td><?= e(format_run_time((int) $game['time_milliseconds'])) ?></td>
		<td>by <?= e($game['user_name']) ?></td>
		<?php else: ?>
		<td colspan="3">no verified runs yet</td>
		<?php endif; ?>
	</tr>
	<?php endforeach; ?>
</table>

<h2>recent runs <?php help_icon('runs marked pending have not been verified by a game admin or moderator yet.'); ?></h2>
<table>
	<?php foreach ($recent_runs as $run): ?>
	<tr>
		<td><?= e(format_date($run['created_at'])) ?></td>
		<td><?= e($run['user_name']) ?></td>
		<td><a href="/game/<?= e($run['url_shorthand']) ?>"><?= e($run['game_name']) ?></a> / <?= e($run['category_name']) ?></td>
		<td><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
		<td><span class="tag"><?= $run['status'] === 'pending' ? 'pending' : 'verified' ?></span></td>
	</tr>
	<?php endforeach; ?>
</table>

<hr>
<section id="about">
	<p>Speedruns.top is an alternative to the popular <a href="https://speedrun.com">speedrun.com</a> site. While we also enforce moderation and game verification, <a href="https://github.com/speedruns-top/speedruns.top">we are open source</a> and accept all sorts of games and categories - SRC doesn't.</p>
	<p>Anybody can request the addition of a new game, and you can submit runs to any game. Game moderators will then review and approve them.</p>
	<p>Feel free to contact us any time via <a href="mailto:contact@speedruns.top">contact@speedruns.top</a>.</p>
</section>
<?php
require __DIR__ . '/../templates/footer.php';
