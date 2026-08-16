<?
declare(strict_types=1);

$games = sql(
	"select game.name, count(run.proof) as run_count, count(category.name) as category_count
	from game
	left join category on category.game_name = game.name
	left join run on run.game_name = game.name and run.deleted_at is null and (run.verified = 1 or run.verified is null)
	group by game.name
	order by game.name"
);
require __DIR__ . '/../templates/header.php';
?>
<a href="/game-add" class="prominent-link" style="float:right;">+ add a game</a></h3>
<table>
	<? foreach ($games as $game): ?>
	<tr>
		<td><a href="/game/<?= e($game['name']) ?>"><?= e($game['name']) ?></a></td>
		<td><?= e((int) $game['category_count']) ?> categories</td>
		<td><?= e((int) $game['run_count']) ?> runs</td>
	</tr>
	<? endforeach; ?>
</table>
<?
require __DIR__ . '/../templates/footer.php';
