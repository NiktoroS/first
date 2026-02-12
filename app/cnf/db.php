<?php
/**
 * @category подключение к базе
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

$dsnMySql = [
    "host" => getenv("MY_DB_HOST") ?? "localhost",
    "name" => getenv("MY_DB_NAME") ?? "first",
    "pass" => getenv("MY_DB_PASS") ?? "first",
    "user" => getenv("MY_DB_USER") ?? "first"
];

$dsnPgSql = [
    "prod" => [
        "hosts" => [getenv("PG_DB_HOST") ?? "localhost"],
        "name"  => getenv("PG_DB_NAME") ?? "first",
        "pass"  => getenv("PG_DB_PASS") ?? "first",
        "port"  => getenv("PG_DB_PORT") ?? 5432,
        "user"  => getenv("PG_DB_USER") ?? "first"
    ]
];
