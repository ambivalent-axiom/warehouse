<?php
namespace App;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Helper\Table;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Warehouse implements \JsonSerializable
{
    private string $name;
    private User $user;
    private array $users;
    private array $products;
    private const VALID_STR_LENGTH = 20;
    private const CODE_LENGTH = 4;
    private const MAIN_MENU = [
        'admin' => ['users', 'warehouse', 'logout', 'exit'],
        'customer' => ['warehouse', 'logout', 'exit'],
        'submenu' => ['add', 'update', 'remove', 'back']
    ];
    public function __construct(string $name, array $products = [])
    {
        $this->name = $name;
        $this->logger = new Logger($this->name);
        $this->logger->pushHandler(new StreamHandler('warehouse.log'));
        $this->db = new JsonDatabase();
        $this->users = $this->loadUsers();
        $this->products = $products;
        $this->symfonyInput = new ArgvInput();
        $this->symfonyOutput = new ConsoleOutput();
        $this->symfonyHelper = new QuestionHelper();
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
        $table = new Table($this->symfonyOutput);
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
        $table = new Table($this->symfonyOutput);
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
    private function selectUser(): User
    {
        $options = array_map(function ($user) {
            return strtolower($user->getName());
        }, $this->users);
        $choice = new ChoiceQuestion('Choose a user: ', $options);
        $choice->setErrorMessage('Option %s is invalid.');
        $choice = $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput, $choice);
        return $this->users[array_search($choice, $options)];
    }
    private function login(User $user): void
    {
        $this->logger->info('Authorization in progress ...');
        if($this->validateAccess($user)) {
            $this->logger->info('Login success!');
            $this->user = $user;
            return;
        }
        throw new \Exception("Access denied!");
    }
    private function validateAccess(User $user): bool
    {
        $code = self::validateNum('PIN', 'Enter PIN: ');
        return $user->getCode() == $code && ($code = true);
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
    private function mainMenu(): string
    {
        $options = $this->user->getRole() == 'admin' ? self::MAIN_MENU['admin'] : self::MAIN_MENU['customer'];
        $choice = new ChoiceQuestion('Select the database for operation: ', $options);
        $choice->setErrorMessage('Option %s is invalid.');
        return $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput, $choice);
    }
    private function menu(array $options): string
    {
        $choice = new ChoiceQuestion('Select the database for operation: ', $options);
        $choice->setErrorMessage('Option %s is invalid.');
        return $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput, $choice);
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
        if(count($this->users) === 0) {
            $this->createUser('admin');
        }
        try {
            $this->login($this->selectUser());
        } catch (\Exception $e) {
            echo $e->getMessage();
            $this->logger->error($e->getMessage());
        }
        while(true) {
            self::cls();
            switch ($this->mainMenu())
            {
                case 'users':
                    self::cls();
                    $this->showUsers();
                    switch ($this->menu(self::MAIN_MENU['submenu'])) {
                        case 'add':
                            $question = new ConfirmationQuestion("Is NEW user an ADMIN? [y/N]: ");
                            if ($this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput , $question)) {
                                $this->createUser('admin');
                            } else {
                                $this->createUser();
                            }
                            break;
                        case 'update':
                            break; //TODO add update case
                        case 'remove':
                            break; //TODO add remove case
                        case 'back':
                            break;
                        case 'exit':
                            exit;
                    }
                    break;
                case 'warehouse':
                    echo "Warehouse";
                    break;
                case 'exit':
                    exit;
            }
        }
    }
}