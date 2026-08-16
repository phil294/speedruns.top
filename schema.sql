pragma journal_mode = wal;
pragma synchronous = normal;
pragma foreign_keys = on;
pragma temp_store = memory;

create table user (
	name text not null primary key check (length(name) <= 32),
	email text null unique check (length(email) <= 254),
	discord_user_id text null unique,
	profile_picture blob null,
	username_changed_at text null,
	is_site_admin integer not null default 0 check (is_site_admin in (0, 1)),
	last_action_at text null,
	last_login_link_sent_at text null,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table login_session (
	token text not null primary key,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table login_link (
	token text not null primary key,
	email text not null check (length(email) <= 254),
	created_at text not null default (datetime('now')),
	used_at text null
) strict, without rowid;

create table registration_attempt (
	id integer primary key,
	ip_address text not null,
	created_at text not null default (datetime('now'))
) strict;

create table login_link_attempt (
	id integer primary key,
	ip_address text not null,
	created_at text not null default (datetime('now'))
) strict;

create table game (
	name text not null primary key check (length(name) <= 50),
	website text not null check (length(website) <= 300),
	details text not null default '' check (length(details) <= 4000),
	rules text not null default '' check (length(rules) <= 4000),
	image blob null,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table game_request (
	name text primary key check (length(name) <= 50),
	website text null check (length(website) <= 300),
	description text not null check (length(description) <= 4000),
	requested_by text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table game_admin (
	game_name text not null references game (name) on update cascade on delete cascade,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now')),
	primary key (game_name, user_name)
) strict, without rowid;

create table game_moderator (
	game_name text not null references game (name) on update cascade on delete cascade,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now')),
	primary key (game_name, user_name)
) strict, without rowid;

create table category (
	game_name text not null references game (name) on update cascade on delete cascade,
	name text not null check (length(name) <= 50),
	rules text not null default '' check (length(rules) <= 4000),
	primary key (game_name, name)
) strict, without rowid;

create table property (
	game_name text not null references game (name) on update cascade on delete cascade,
	category_name text not null,
	name text not null check (length(name) <= 50),
	primary key (game_name, category_name, name),
	foreign key (game_name, category_name) references category (game_name, name) on update cascade on delete cascade
) strict, without rowid;

create table run (
	user_name text not null references user (name) on update cascade on delete cascade,
	proof text not null check (length(proof) <= 500),
	game_name text not null references game (name) on update cascade on delete restrict,
	category_name text not null,
	time_milliseconds integer not null check (time_milliseconds > 0),
	comment text not null default '' check (length(comment) <= 300),
	verified integer null check (verified in (0, 1)),
	verified_by text null references user (name) on update cascade on delete set null,
	deleted_at text null,
	created_at text not null default (datetime('now')),
	primary key (user_name, proof),
	foreign key (game_name, category_name) references category (game_name, name) on update cascade on delete restrict
) strict, without rowid;

create table run__property (
	run_user_name text not null references user (name) on update cascade on delete cascade,
	run_proof text not null,
	game_name text not null,
	category_name text not null,
	property_name text not null,
	value text not null check (length(value) <= 200),
	primary key (run_user_name, run_proof, property_name),
	foreign key (run_user_name, run_proof) references run (user_name, proof) on update cascade on delete cascade,
	foreign key (game_name, category_name, property_name) references property (game_name, category_name, name) on update cascade on delete restrict
) strict, without rowid;

create index run_by_game_category on run (game_name, category_name);
create index category_by_game on category (game_name);
create index property_by_category on property (game_name, category_name);

create trigger category_count_limit_insert
before insert on category
when (select count(*) from category where game_name = new.game_name) >= 20
begin
	select raise(abort, 'a game may not have more than 20 categories');
end;

create trigger property_count_limit_insert
before insert on property
when (select count(*) from property where game_name = new.game_name and category_name = new.category_name) >= 10
begin
	select raise(abort, 'a game may not have more than 10 categories');
end;
