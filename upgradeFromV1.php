<?php
require "vendor/autoload.php";
use Ramsey\Uuid\Uuid;
use App\JsonDatabase;
use App\Product;
$db = new JsonDatabase();
$products = [];
$db->connect('productsOld.json');
foreach ($db->read() as $product) {
    $products[] = new Product(
        $product->id,
        $product->name,
        $product->quantity,
        0,
        Null,
        Uuid::uuid4()->toString(),
        $product->updated,
        $product->created
    );
}
file_put_contents('db/productsNew.json', json_encode($products, JSON_PRETTY_PRINT));