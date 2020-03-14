<?php

require_once __DIR__ . '/lhToggl/classes/lhTogglApi.php';

$tapi = new lhTogglApi("");
$tapi->_test(get_class_methods("lhTogglApi"));

