<?php
namespace App;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\Table;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


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
        $this->logger = new Logger($this->name);
    }
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'users' => $this->users,
            'products' => $this->products,
        ];
    }
    private function show(): void
    {
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table
            ->setHeaders(Product::getColumns())
            ->setRows(array_map(function ($product) {
                return [
                    $product->getId(),
                    $product->getName(),
                    $product->getCreated(),
                    $product->getUpdated(),
                    $product->getQuantity(),
                ];
            }, $this->products));
        $table->setHeaderTitle($this->name);
        $table->setStyle('box-double');
        $table->render();
    }
    private function createUser(): void
    {
        $this->users[] = new User();
    }
    public function run(): void
    {
         self::cls();
    }
    public static function cls(): void {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
}