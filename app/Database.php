<?php

namespace App;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

interface Database
{
    public function connect($path);
    public function read();
    public function write(array $data);
}