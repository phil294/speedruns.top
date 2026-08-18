#!/bin/bash

# This script can also be run any amount of time for validation purposes.

set -eo pipefail

schema_expected_file=$(mktemp -t tmp.schema.expected.XXXXX.sql)
schema_actual_file=$(mktemp -t tmp.schema.actual.XXXXX.sql)
dbfile_cp=$(mktemp -t tmp.db_cp.XXXXX.db)
data_inserts_file=$(mktemp -t tmp.data_inserts.XXXXX.sql)
on_exit() {
	rm -f $dbfile_cp $schema_expected_file $schema_actual_file $data_inserts_file
}
trap on_exit EXIT

dbfile="$1"
[ -f "$dbfile" ]
dbfile_wal="${dbfile}-wal"
migrations_dir="./src/db/migrations"

! [ -f "$dbfile" ] && echo "db not found" && exit 1
[ -f "$dbfile_wal" ] && echo "other client connected?" && exit 1

sql() {
	\sqlite3 -init /dev/null "$@"
}
disable_fks="pragma foreign_keys = \"OFF\""

cp "$dbfile" $dbfile_cp

### 0: Sanity check
user_count_before=$(sql $dbfile_cp "select count(*) from user")

### 1: migrations
current_version=$(sql $dbfile_cp "select max_version from migration_version limit 1" 2>/dev/null || echo "0000-00-00")
ls "$migrations_dir" \
| sort | awk '$0>"'"$current_version"'"' \
| while read -r migration_file; do
	echo "Migrating: $migration_file"
	sql $dbfile_cp "$disable_fks" \
		".read '$migrations_dir/$migration_file'"
done
new_version=$(ls "$migrations_dir" | tail -1)
sql $dbfile_cp "delete from migration_version; insert into migration_version(max_version) values('$new_version')";

### 2: Dump
echo "Dumping data"
# Like `.dump` but also add full column name inserts https://stackoverflow.com/a/79916675/3779853
src/tool/db-dump-data.sh $dbfile_cp \
	> $data_inserts_file
echo "Dropping and recreating db file"
rm $dbfile_cp

### 3: Structure
echo "Creating tables"
sql $dbfile_cp "$disable_fks" ".read ./schema.sql"

### 4: Recreate
sql $dbfile_cp "$disable_fks" \
	".read $data_inserts_file" \
	|& head -10 || {
		read -rsn 1 -p "Importing failed. Press any key to view the dump file."; echo
		less -N $data_inserts_file
		exit 1 ;}

### 5: Check FK
echo "Checking FKs"
check_out=$(src/tool/db-foreign-key-check.sh $dbfile_cp |& head -15 ||:)
if ! [ -z "$check_out" ]; then
	echo "$check_out"
	echo "Failed FK check"
	exit 1
fi

### 6. Check schema
echo "Confirming schema"
# Sorts all entries but not the columns within. This step essentially enforces you to always create the db
# using schema.sql.
dump_schema_sql="select sql || ';' from sqlite_master where type in ('table','index','trigger','view') and sql is not null and name not glob 'sqlite_*' order by lower(name)"
sql :memory: \
	".read ./schema.sql" \
	"$dump_schema_sql" \
	> $schema_expected_file
sql $dbfile_cp \
	"$dump_schema_sql" \
	> $schema_actual_file

if ! diff $schema_expected_file $schema_actual_file; then
	read -rsn 1 -p "Schema mismatch. Press any key to open vimdiff."; echo
	vimdiff -c "windo set wrap" -c "nnoremap q :qa<CR>" $schema_expected_file $schema_actual_file
	exit 1
fi

### 7. Sanity check
user_count_after=$(sql $dbfile_cp "select count(*) from user")
if [[ $user_count_before != $user_count_after ]]; then
	echo "user count changed from $user_count_before to $user_count_after. data getting lost? aborting."
	read
	exit 1
fi

### 8. apply
[ -f "$dbfile_wal" ] && echo "other client connected???" && exit 1
mv $dbfile_cp "$dbfile"
