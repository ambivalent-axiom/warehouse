<?php
namespace App;


class Warehouse implements \JsonSerializable
{
    private string $name;
    private array $users;
    private array $products;
    public function __construct(string $name, array $users, array $products)
    {
        $this->name = $name;
        $this->users = $users;
        $this->products = $products;
    }
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'users' => $this->users,
            'products' => $this->products,
        ];
    }
}