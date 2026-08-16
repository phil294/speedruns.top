<?
declare(strict_types=1);

$recent_runs = sql(
	"select run.time_milliseconds, run.verified, run.created_at, run.user_name, run.category_name, game.name as game_name
	from run
	join game on game.name = run.game_name
	where run.deleted_at is null and (run.verified is null or run.verified = 1)
	order by run.created_at desc
	limit 10",
);


require __DIR__ . '/../templates/header.php';
?>

<section id="about">
	<p>speedruns.top is an alternative to the popular <a href="https://speedrun.com">speedrun.com</a> site. <? help_icon_html('Speedrunning is the act of playing a video game, or section of a video game, with the goal of completing it as fast as possible. <cite><a href="https://en.wikipedia.org/wiki/Speedrunning">en.wikipedia.org</a></cite>') ?></p>
	<p>We are <a href="https://github.com/phil294/speedruns.top">open source</a> and accept <em>all</em> sorts of games and categories.</p>
	<p>Anybody can request the addition of a new game, and you can submit runs to any game. Game moderators will then review and approve them.</p>
	<p>Feel free to contact us any time via <a href="mailto:contact@speedruns.top">contact@speedruns.top</a>.</p>
</section>

<hr>

<h3>list of games</h3>
<a href="/game">go to list of games</a>


<h3>recent runs</h3>
<table>
	<? foreach ($recent_runs as $run): ?>
	<tr>
		<td><?= e(format_date($run['created_at'])) ?></td>
		<td><a href="/game/<?= e($run['game_name']) ?>"><?= e($run['game_name']) ?></a> / <?= e($run['category_name']) ?></td>
		<td><a href="/user/<?= e($run['user_name']) ?>"><?= e($run['user_name']) ?></a></td>
		<td><?= e(format_run_time((int) $run['time_milliseconds'])) ?></td>
		<td><span class="tag"><?= $run['verified'] === null ? 'pending' : 'verified' ?></span></td>
	</tr>
	<? endforeach; ?>
</table>

<?
require __DIR__ . '/../templates/footer.php';
