<?php
namespace App;
use Carbon\Carbon;

class Product implements \JsonSerializable
{
    private int $id;
    private string $name;
    private Carbon $created;
    private Carbon $updated;
    private int $quantity;

    public function __construct(int $id, string $name, int $quantity, Carbon $updated)
    {
        $this->id = $id;
        $this->name = $name;
        $this->created = Carbon::now();
        $this->updated = $updated;
        $this->quantity = $quantity;
    }
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created' => $this->created,
            'updated' => $this->updated,
            'quantity' => $this->quantity,
        ];
    }
}