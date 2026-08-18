#!/bin/bash

# a more readable alternative to `pragma foreign_key_check`.
# $1=dbfile, $2 onwards: optional sql strings to execute before running the check

set -eo pipefail
dbfile="$1"
[[ -f $dbfile ]]

sqlite3 -init /dev/null "$@" ".headers on" ".mode box" "
	select
		fk_check.\"table\" || '['
			|| coalesce(child_pk.name, '??pk??') || '='
			|| coalesce(cast(fk_check.rowid as text), '<without rowid>')
		|| ']:' || fk_list.\"from\" as child,
		fk_check.parent || ':' || fk_list.\"to\" as parent
	from pragma_foreign_key_check() as fk_check
	join pragma_foreign_key_list(fk_check.\"table\") as fk_list on fk_check.fkid = fk_list.id
	left join pragma_table_info(fk_check.\"table\") as child_pk on child_pk.pk > 0;
"