<?php

$command = "php " . __DIR__ . "/child.php > /dev/null &";

$output = $return_var = null;
exec($command, $output, $return_var);

var_dump($output, $return_var);

