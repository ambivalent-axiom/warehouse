<?php
namespace App;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class Product implements \JsonSerializable
{
    private string $uuid;
    private int $id;
    private string $name;
    private int $price;
    private Carbon $created;
    private Carbon $updated;
    private ?Carbon $expiration;
    private int $quantity;
    private const COLUMNS = ['uuid', 'id', 'created', 'updated', 'name', 'quantity', 'expiration', 'price'];

    public function __construct(
        int $id,
        string $name,
        int $quantity,
        int $price,
        $expiration = Null,
        string $uuid = Null,
        $updated = Null,
        $created = Null
        )
    {
        $this->uuid = $uuid ? : Uuid::uuid4()->toString();
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->expiration = $expiration ? Carbon::parse($expiration) : Null;
        $this->created = $created ? Carbon::parse($created) : Carbon::now('UTC');
        $this->updated = $updated ? Carbon::parse($updated) : Carbon::now('UTC');
        $this->quantity = $quantity;
    }
    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'id' => $this->id,
            'name' => $this->name,
            'created' => $this->created,
            'updated' => $this->updated,
            'quantity' => $this->quantity,
            'expiration' => $this->expiration,
            'price' => $this->price,
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
    public function setUpdated(): void
    {
        $this->updated = Carbon::now('UTC');
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
    public function getUuid(): string
    {
        return $this->uuid;
    }
    public function getPrice(): int
    {
        return $this->price;
    }
    public function setPrice(int $price): void
    {
        $this->price = $price;
    }
    public function getExpiration(string $timezone='UTC'): ?Carbon
    {
        return $this->expiration ? $this->expiration->timezone($timezone) : Null;
    }
    public function setExpiration(?Carbon $expiration): void
    {
        $this->expiration = $expiration;
    }
    public static function getColumns(): array
    {
        return self::COLUMNS;
    }
}