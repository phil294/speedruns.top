<?php
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

function sql(string $query, array $parameters = []): array {
	$statement = database()->prepare($query);
	$statement->execute($parameters);
	return $statement->fetchAll();
}

function sql_one(string $query, array $parameters = []): array|null {
	$rows = sql($query, $parameters);
	return $rows[0] ?? null;
}

function write(string $query, array $parameters = []): void {
	$statement = database()->prepare($query);
	$statement->execute($parameters);
}
