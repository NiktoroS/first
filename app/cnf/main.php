<?php
/**
 * @category
 * @author <first@mail.ru>
 * @since  2018-03-30
 */

mb_internal_encoding("UTF-8");
date_default_timezone_set("Europe/Moscow");

define("DS", DIRECTORY_SEPARATOR);

define("APP_DIR", dirname(dirname(__FILE__)) . DS);
define("ROOT_DIR", dirname(APP_DIR) . DS);

define("TMP_DIR", ROOT_DIR . ".tmp" . DS);
define("LOG_DIR", ROOT_DIR . ".log" . DS);

define("INC_DIR", APP_DIR . "inc" . DS);
define("MOD_DIR", APP_DIR . "mod" . DS);
define("TPL_DIR", APP_DIR . "tpl" . DS);
define("CNF_DIR", APP_DIR . "cnf" . DS);

define("SESSIONS_DIR", TMP_DIR . "sessions");

define("SEC_MIN",  60);
define("SEC_HOUR", SEC_MIN * 60);
define("SEC_DAY",  SEC_HOUR * 24);

define("TITLE",    "First");
define("KEYWORDS", "First");
define("DESCR",    "First");

if (isset($_SERVER["REQUEST_URI"])) {
    if (!is_dir(SESSIONS_DIR)) {
        mkdir(SESSIONS_DIR, 0777, true);
    }
    ini_set("session.save_path", SESSIONS_DIR);
    session_set_cookie_params(SEC_DAY);
    session_start();
}
