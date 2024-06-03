<?php
namespace App;
use Carbon\Carbon;
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
        'admin' => ['users', 'warehouse'],
        'customer' => ['warehouse'],
        'submenu' => ['add', 'update', 'remove']
    ];
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->logger = new Logger($this->name);
        $this->logger->pushHandler(new StreamHandler('warehouse.log'));
        $this->db = new JsonDatabase();
        $this->users = $this->loadUsers();
        $this->products = $this->loadProducts();
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

    //Products
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
                    $product->getCreated('Europe/Riga')->format('m/d/Y H:i:s'),
                    $product->getUpdated('Europe/Riga')->format('m/d/Y H:i:s'),
                    $product->getName(),
                    $product->getQuantity(),
                ];
            }, $this->products))
            ->setFooterTitle($this->user->getName())
            ->render();
    }
    private function createProduct(): void
    {
        $name = self::validateName('Product', 'Enter product name: ');
        $quantity = self::validateNum(2, 'Quantity', 'Enter quantity: ', false);
        $this->products[] = new Product(
            $this->getAutoIncrementId($this->products),
            $name,
            Carbon::now('UTC'),
            Carbon::now('UTC'),
            $quantity
        );
        $this->writeProducts();
        $this->logger->info($this->user->getName() .
            ' added prod. ' .
            $name .
            " quant. " .
            $quantity .
            " to products");
    }
    private function deleteProduct(Product $product): void
    {
        $question = new ConfirmationQuestion("Are You sure? [y/N]: ");
        if($this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput , $question)) {
            $key = array_search($product, $this->products);
            $this->logger->info($this->user->getName() .
                ' deleted product ' .
                $product->getID() .
                " " .
                $product->getName() .
                '.');
            unset($this->users[$key]);
        }
    }
    private function updateProduct(Product $product): void
    {
        switch (self::menu(array_slice($product::getColumns(), 3))) {
            case 'name':
                $nameOld = $product->getName();
                $product->setName(self::validateName('name', 'Enter New Name: '));
                $product->setUpdated(Carbon::now('UTC'));
                $this->writeProducts();
                $this->logger->info($this->user->getName() .
                    ' updated product ' .
                    $product->getID() . " " .
                    $product->getName() . ' changed name from  ' .
                    $nameOld
                );
                break;
            case 'quantity':
                $quantityOld = $product->getQuantity();
                $product->setQuantity(self::validateNum(2, 'Quantity', 'Enter new quantity: '));
                $product->setUpdated(Carbon::now('UTC'));
                $this->writeProducts();
                $this->logger->info($this->user->getName() .
                    ' updated product ' .
                    $product->getID() . " " .
                    $product->getName() . ' changed quantity from ' .
                    $quantityOld . " to " .
                    $product->getQuantity()
                );
                break;
            case 'back':
                break;
        }
    }
    private function selectProduct(): Product
    {
        $index = self::validateNum(strlen(max($this->products)->getId()), 'Product id', 'Select product id: ', false);
        return $this->products[$index];
    }
    private function withdrawProduct(Product $product): void
    {
        $amount = (int) self::validateNum(2, 'Amount', 'Enter amount: ', false);
        if ($product->getQuantity() >= $amount) {
            $product->addQuantity(-$amount);
            $product->setUpdated(Carbon::now('UTC'));
            $this->logger->info($this->user->getName() .
                ' withdrawed ' .
                $amount . ' of ' .
                $product->getName()
            );
            return;
        }
        $this->logger->info($this->user->getName() .
            ' unable to process withdrawal request, amount exceeds stock.'
        );
        throw new \Exception('Amount exceeds stock!');
    }
    private function writeProducts(): void
    {
        $this->db->connect('products.json');
        $this->db->write($this->products);
    }
    private function loadProducts(): array
    {
        $this->logger->info('Loading products ...');
        $products = [];
        $this->db->connect('products.json');
        foreach ($this->db->read() as $product) {
            $this->logger->info($product->id . " " . $product->name . " Loaded.");
            $products[] = new Product(
                $product->id,
                $product->name,
                Carbon::parse($product->created),
                Carbon::parse($product->updated),
                $product->quantity,
            );
        }
        return $products;
    }



    //Users
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
        $name = self::validateName('name', 'Enter Your Name: ');
        $code = self::validateNum(self::CODE_LENGTH, 'PIN', 'Enter PIN: ');
        $this->users[] = new User($this->getAutoIncrementId($this->users), $name, $code, $role);
        $this->logger->info('User ' . $name . ' created.');
        $this->writeUsers();
    }
    private function chooseUserRole(): string {
        $question = new ConfirmationQuestion("Is NEW user an ADMIN? [y/N]: ");
        return $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput , $question) ? 'admin' : 'customer';
    }
    private function updateUser(User $user): void
    {
        switch ($this->menu(array_slice(User::getColumns(), 1))) {
            case 'name':
                $user->setName(self::validateName('name', 'Enter New Name: '));
                $this->writeUsers();
                $this->logger->info('User ' . $user->getID() . " " . $user->getName() . ' updated name ...');
                break;
            case 'code':
                $user->setCode(self::validateNum(self::CODE_LENGTH, 'PIN', 'Enter New PIN: '));
                $this->writeUsers();
                $this->logger->info('User ' . $user->getID() . " " . $user->getName() . ' updated PIN ...');
                break;
            case 'role':
                $user->setRole($this->chooseUserRole());
                $this->writeUsers();
                $this->logger->info('User '  . $user->getID() . " ". $user->getName() . ' updated role ...');
                break;
        }
    }
    private function deleteUser(User $user): void
    {
        $question = new ConfirmationQuestion("Are You sure? [y/N]: ");
        if($this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput , $question)) {
            $key = array_search($user, $this->users);
            $this->logger->info('User ' . $user->getID() . " " . $user->getName() . ' deleted.');
            unset($this->users[$key]);
        }
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
    private function writeUsers(): void
    {
        $this->db->connect('users.json');
        $this->db->write($this->users);
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
        $code = self::validateNum(self::CODE_LENGTH, 'PIN', 'Enter PIN: ');
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

    //Menu
    private function mainMenu(): string
    {
        $options = $this->user->getRole() == 'admin' ? self::MAIN_MENU['admin'] : self::MAIN_MENU['customer'];
        $options[] = 'exit';
        $choice = new ChoiceQuestion('Select the database for operation: ', $options);
        $choice->setErrorMessage('Option %s is invalid.');
        return $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput, $choice);
    }
    private function menu(array $options): string
    {
        $options[] = 'back';
        $choice = new ChoiceQuestion('Select the action: ', $options);
        $choice->setErrorMessage('Option %s is invalid.');
        return $this->symfonyHelper->ask($this->symfonyInput, $this->symfonyOutput, $choice);
    }


    //static methods
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
    public static function validateNum(int $length, string $what, string $prompt, bool $fixed = true): string
    {
        while(true) {
            $num = readline($prompt);
            if ($fixed) {
                if (is_numeric($num) && strlen($num) == $length) {
                    return $num;
                }
                echo "$what must be a valid " . $length . " digit integer.\n";
            }
            if ( ! $fixed) {
                if (is_numeric($num) && strlen($num) <= $length) {
                    return $num;
                }
                echo "$what must be a valid integer up to " . $length . "\n";
            }

        }
    }

    //main
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
            switch ($this->mainMenu())
            {
                case 'users':
                    $this->showUsers();
                    switch ($this->menu(self::MAIN_MENU['submenu'])) {
                        case 'add':
                            if ($this->chooseUserRole()) {
                                $this->createUser('admin');
                            } else {
                                $this->createUser();
                            }
                            break;
                        case 'update':
                            $this->updateUser($this->selectUser());
                            break;
                        case 'remove':
                            $this->deleteUser($this->selectUser());
                            break;
                        case 'back':
                            break;
                        case 'exit':
                            exit;
                    }
                    break;
                case 'warehouse':
                    $this->showProducts();
                    $options = self::MAIN_MENU['submenu'];
                    $options[] = 'withdraw';
                    switch ($this->menu($options)) {
                        case 'add':
                            $this->createProduct();
                            break;
                        case 'update':
                            $this->updateProduct($this->selectProduct());
                            break;
                        case 'remove':
                            $this->deleteProduct($this->selectProduct());
                            break;
                        case 'withdraw':
                            try {
                                $this->withdrawProduct($this->selectProduct());
                            } catch (\Exception $e) {
                                echo $e->getMessage();
                            }
                            break;
                        case 'back':
                            break;
                        case 'exit':
                            exit;
                    }
                    break;
                case 'exit':
                    exit;
            }
        }
    }
}