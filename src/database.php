<?
declare(strict_types=1);

function database(): PDO {
	static $connection = null;
	if ($connection !== null) {
		return $connection;
	}

	$database_path = __DIR__ . '/../data/speedruns.sqlite';
	$database_already_exists = is_file($database_path);

	$connection = new PDO('sqlite:' . $database_path);
	$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$connection->exec('pragma foreign_keys = on');
	$connection->exec('pragma journal_mode = wal');
	$connection->exec('pragma synchronous = normal');

	if (!$database_already_exists) {
		$connection->exec((string) file_get_contents(__DIR__ . '/../schema.sql'));
	}

	return $connection;
}

$log_out = fopen(__DIR__ . '/../data/queries.log', 'a');
function log_query(string $query, float $start_time): void {
	global $log_out;
	fwrite($log_out, sprintf("[%.4f s] %s\n", microtime(true) - $start_time, preg_replace('/\s+/', ' ', trim($query))));
}

function sql(string $query, array $parameters = []): array|null {
	$start_time = microtime(true);
	$statement = database()->prepare($query);
	$statement->execute($parameters);
	$rows = $statement->columnCount() > 0 ? $statement->fetchAll() : null;
	log_query($query, $start_time);
	return $rows;
}

function sql_one(string $query, array $parameters = []): array|null {
	$rows = sql($query, $parameters);
	return $rows[0] ?? null;
}

function transaction(callable $callback): void {
	$connection = database();
	$connection->beginTransaction();
	try {
		$callback();
		$connection->commit();
	} catch (Throwable $e) {
		$connection->rollBack();
		throw $e;
	}
}

function write_blob(string $query, string $binary_value, array $other_parameters = []): void {
	$start_time = microtime(true);
	$statement = database()->prepare($query);
	$statement->bindValue(1, $binary_value, PDO::PARAM_LOB);
	foreach ($other_parameters as $index => $value) {
		$statement->bindValue($index + 2, $value);
	}
	$statement->execute();
	log_query($query, $start_time);
}
