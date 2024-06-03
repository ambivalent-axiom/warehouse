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
    private const COLUMNS = ['id', 'created', 'updated', 'name', 'quantity'];

    public function __construct(int $id, string $name, Carbon $updated, Carbon $created, int $quantity)
    {
        $this->id = $id;
        $this->name = $name;
        $this->created = $updated;
        $this->updated = $created;
        $this->quantity = $quantity;
        $this->logger = new Logger('Product ' . $this->name);
        $this->logger->pushHandler(new StreamHandler('warehouse.log'));
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
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    public function getCreated(string $timeZone='UTC'): Carbon
    {
        return $this->created->timezone($timeZone);
    }
    public function getUpdated(string $timeZone='UTC'): Carbon
    {
        return $this->updated->timezone($timeZone);
    }
    public function setUpdated(Carbon $updated): void
    {
        $this->updated = $updated;
    }
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
    public function addQuantity(int $quantity): void
    {
        $this->quantity += $quantity;
    }
    public static function getColumns(): array
    {
        return self::COLUMNS;
    }
}