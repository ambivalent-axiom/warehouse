<?php
namespace App;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
class JsonDatabase implements Database
{
    private string $filepath;
    private array $data;
    public function connect($path): void
    {
        $this->filepath = "db/" . $path;
        if (file_exists($path)) {
            $this->data = json_decode(file_get_contents($path));
        } else {
            $this->data = [];
        }
    }
    public function read(): array
    {
        return $this->data;
    }
    public function write(array $data): void
    {
        $this->data = $data;
        file_put_contents($this->filepath, json_encode($data, JSON_PRETTY_PRINT));
    }
}