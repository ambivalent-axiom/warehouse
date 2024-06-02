<?php
require_once "vendor/autoload.php";
use App\Warehouse;

$narvesen = new Warehouse('Narvessen');
$narvesen->run();
