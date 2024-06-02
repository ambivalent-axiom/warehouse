<?php
require_once "vendor/autoload.php";

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

//TODO ievietot loggeri klasēs, no šejienes izņemt ārā
$logger = new Logger('my_logger');


$logger->pushHandler(new StreamHandler(__DIR__.'/my_app.log'));
$logger->pushHandler(new FirePHPHandler());

// You can now use your logger
$logger->info('My logger is now ready');