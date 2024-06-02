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
    private const VALID_STR_LENGTH = 20;
    private const CODE_LENGTH = 4;
    public function __construct(string $name, array $products = [])
    {
        $this->name = $name;
        $this->logger = new Logger($this->name);
        $this->logger->pushHandler(new StreamHandler('warehouse.log'));
        $this->db = new JsonDatabase();
        $this->users = $this->loadUsers();
        $this->products = $products;
        $this->run();
    }
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'users' => $this->users,
            'products' => $this->products,
        ];
    }
    private function showProducts(): void
    {
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table
            ->setHeaderTitle($this->name)
            ->setStyle('box-double')
            ->setHeaders(Product::getColumns())
            ->setRows(array_map(function ($product) {
                return [
                    $product->getId(),
                    $product->getName(),
                    $product->getCreated(),
                    $product->getUpdated(),
                    $product->getQuantity(),
                ];
            }, $this->products))
            ->render();
    }
    private function showUsers(): void
    {
        $this->logger->info('Outputting users to symfony table ...');
        $output = new ConsoleOutput();
        $table = new Table($output);
        $table
            ->setHeaderTitle($this->name)
            ->setHeaders(User::getColumns())
            ->setStyle('box-double')
            ->setRows(array_map(function ($user) {
                return [
                    $user->getId(),
                    $user->getName(),
                    $user->getCode(),
                    $user->getRole(),
                ];
            }, $this->users))
            ->render();
    }

    private function createUser(string $role = 'customer'): void
    {
        $this->logger->info('Creating user ...');
        $name = self::validateName('name', 'Enter Your Name: ');
        $code = self::validateNum('PIN', 'Enter PIN: ');
        $this->logger->info('Initiating new user instance ...');
        $this->users[] = new User($this->getAutoIncrementId($this->users), $name, $code, $role);
        $this->logger->info('Writing new user instance to database ...');
        $this->db->connect('users.json');
        $this->db->write($this->users);
        $this->logger->info('Success ...');
    }
    private function loadUsers(): array
    {
        $this->logger->info('Loading users ...');
        $users = [];
        $this->db->connect('users.json');
        foreach ($this->db->read() as $user) {
            $this->logger->info($user->id . " " . $user->name . " Loaded.");
            $users[] = new User($user->id, $user->name, $user->code, $user->role);
        }
        return $users;
    }





    private function getAutoIncrementId(array $object): int
    {
        if (count($object) === 0) {
            return 0;
        }
        $ids = array_map(function ($object) {
            return $object->getId();
        }, $object);
        return max($ids) + 1;
    }
    public static function cls(): void {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
    public static function validateName(string $what, string $prompt): string
    {
        while(true) {
            $name = readline($prompt);
            if($name != '' && strlen($name) <= self::VALID_STR_LENGTH && !is_numeric($name)) {
                return $name;
            }
            echo "$what name must be a string, max " . self::VALID_STR_LENGTH . " chars.\n";
        }
    }
    public static function validateNum(string $what, string $prompt): string
    {
        while(true) {
            $num = readline($prompt);
            if (is_numeric($num) && strlen($num) == self::CODE_LENGTH) {
                return $num;
            }
            echo "$what must be a valid " . self::CODE_LENGTH . " digit integer.\n";
        }
    }
    public function run(): void
    {
        $this->logger->info('Running Warehouse ...');
        while(true) {
            //self::cls();
            if(count($this->users) === 0) {
                $this->createUser('admin');
            }
            $this->showUsers();
        }

    }
}