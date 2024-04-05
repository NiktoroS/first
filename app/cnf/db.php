<?php
/**
 * @category подключение к базе
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

$dsnMySql = [
    "host" => "localhost",
    "name" => "sro",
    "user" => "sro_db_user",
    "pass" => "Twz9GhWT2PNWxrcz",
];

$dsnPgSql = [
    "prod" => [
        "hostRows" => [
            "localhost"
        ],
        "port"      => "5432",
        "name"      => "postgres",
        "dbname"    => "postgres",
        "user"      => "first",
        "password"  => "first",
    ],
];
