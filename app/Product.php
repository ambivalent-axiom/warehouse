<?php
namespace App;
use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class Product implements \JsonSerializable
{
    private int $id;
    private string $name;
    private Carbon $created;
    private Carbon $updated;
    private int $quantity;
    private const COLUMNS = ['id', 'name', 'created', 'updated', 'quantity'];

    public function __construct(int $id, string $name, int $quantity, Carbon $updated)
    {
        $this->id = $id;
        $this->name = $name;
        $this->created = Carbon::now();
        $this->updated = $updated;
        $this->quantity = $quantity;
        $this->logger = new Logger('product');
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
    public function getId(): int
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getCreated(): Carbon
    {
        return $this->created;
    }
    public function getUpdated(): Carbon
    {
        return $this->updated;
    }
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    public static function getColumns(): array
    {
        return self::COLUMNS;
    }
}