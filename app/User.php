<?php
namespace App;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class User implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $code;
    private string $role;

    public function __construct(int $id, string $name, string $code, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->role = $role;
        $this->logger = new Logger($this->name . ' ' . $this->id);
    }
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'role' => $this->role,
        ];
    }
}