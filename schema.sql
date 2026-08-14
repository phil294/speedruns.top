pragma journal_mode = wal;
pragma synchronous = normal;
pragma foreign_keys = on;
pragma temp_store = memory;

create table user (
	name text not null primary key,
	email text unique,
	discord_user_id text unique,
	profile_picture blob,
	is_site_admin integer not null default 0 check (is_site_admin in (0, 1)),
	last_action_at text,
	last_login_link_sent_at text,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table login_session (
	token text not null primary key,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table login_link (
	token text not null primary key,
	email text not null,
	created_at text not null default (datetime('now')),
	used_at text
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
	url_shorthand text not null primary key,
	name text not null unique,
	website text,
	details text not null default '',
	rules text not null default '',
	image blob,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table game_request (
	url_shorthand text not null primary key,
	name text not null,
	website text,
	description text not null,
	requested_by text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now'))
) strict, without rowid;

create table game_admin (
	game_url_shorthand text not null references game (url_shorthand) on update cascade on delete cascade,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now')),
	primary key (game_url_shorthand, user_name)
) strict, without rowid;

create table game_moderator (
	game_url_shorthand text not null references game (url_shorthand) on update cascade on delete cascade,
	user_name text not null references user (name) on update cascade on delete cascade,
	created_at text not null default (datetime('now')),
	primary key (game_url_shorthand, user_name)
) strict, without rowid;

create table category (
	game_url_shorthand text not null references game (url_shorthand) on update cascade on delete cascade,
	name text not null,
	rules text not null default '',
	sort_order integer not null,
	primary key (game_url_shorthand, name)
) strict, without rowid;

create table property (
	game_url_shorthand text not null references game (url_shorthand) on update cascade on delete cascade,
	name text not null,
	primary key (game_url_shorthand, name)
) strict, without rowid;

create table category__property (
	game_url_shorthand text not null,
	category_name text not null,
	property_name text not null,
	primary key (game_url_shorthand, category_name, property_name),
	foreign key (game_url_shorthand, category_name) references category (game_url_shorthand, name) on update cascade on delete cascade,
	foreign key (game_url_shorthand, property_name) references property (game_url_shorthand, name) on update cascade on delete cascade
) strict, without rowid;

create table run (
	id text not null primary key,
	game_url_shorthand text not null,
	category_name text not null,
	user_name text not null references user (name) on update cascade on delete cascade,
	time_milliseconds integer not null check (time_milliseconds > 0),
	proof text not null,
	comment text not null default '',
	status text not null default 'pending' check (status in ('pending', 'verified', 'rejected', 'deleted')),
	verified_by text references user (name) on update cascade on delete set null,
	created_at text not null default (datetime('now')),
	foreign key (game_url_shorthand, category_name) references category (game_url_shorthand, name) on update cascade on delete cascade
) strict, without rowid;

create table run__property (
	run_id text not null references run (id) on update cascade on delete cascade,
	property_name text not null,
	value text not null,
	primary key (run_id, property_name)
) strict, without rowid;

create index run_by_game_category_status on run (game_url_shorthand, category_name, status);
create index run_by_user on run (user_name);
create index category_by_game on category (game_url_shorthand);

create trigger category_count_limit_insert
before insert on category
when (select count(*) from category where game_url_shorthand = new.game_url_shorthand) >= 20
begin
	select raise(abort, 'a game may not have more than 20 categories');
end;
