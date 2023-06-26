<?php

$_GET['route'] = 'extension/module/trade_import';
$root = dirname(dirname(dirname(__FILE__)));
chdir(dirname($root));
require_once('index.php');