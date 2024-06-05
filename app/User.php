<?php
namespace App;

class User implements \JsonSerializable
{
    private int $id;
    private string $name;
    private string $code;
    private string $role;
    private const COLUMNS = ['id', 'name', 'code', 'role'];

    public function __construct(int $id, string $name, string $code, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->role = $role;
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
    public function getId(): int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getCode(): string
    {
        return $this->code;
    }
    public function setCode(string $code): void
    {
        $this->code = $code;
    }
    public function getRole(): string
    {
        return $this->role;
    }
    public function setRole(string $role): void
    {
        $this->role = $role;
    }
    public static function getColumns()
    {
        return self::COLUMNS;
    }
}