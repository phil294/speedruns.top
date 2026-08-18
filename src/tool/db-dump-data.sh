#!/bin/bash

# A perfectly alphabetically sorted SQL dump of the non-base data tables (data only) in the passed DB arg, printed
# to stdout for usage in diffs or in delta-efficient progressive data backups like borg or for db recreation in `db-migrate.sh`
# About twice as slow as `.dump` due to `.headers ON`. Import about 30% slower.
# $1=dbfile, $2 onwards: optional sql strings to execute before running the dump

set -eo pipefail
dbfile="$1"
[[ -f $dbfile ]]
shift

echo "--
-- $(date +"%Y-%m-%dT%H:%M:%S%z")
-- GENERATED WITH \`db-backup.sh\`. Please use this script to update only.
--"

echo "begin;" # speed

query=$(
	printf '%s\n' "$@"
	echo ".headers ON"
	sqlite3 -init /dev/null "$dbfile" "select name from sqlite_master where type = 'table'" \
		| grep -v sqlite \
		| sort \
		| while read -r table; do
			cols=$(sqlite3 -init /dev/null "$dbfile" "pragma table_info(${table})" \
				| cut -d '|' -f 2 | grep -E '^[^_]' | sort | paste -sd, -)
			echo ".mode insert $table"
			echo "select $cols from $table;"
		done)
sqlite3 -init /dev/null "$dbfile" <<< "$query"

echo "commit;"
