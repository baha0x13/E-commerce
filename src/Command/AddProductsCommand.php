<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-products',
    description: 'Add sample products for testing',
)]
class AddProductsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Sample products
        $products = [
            ['name' => 'Laptop Dell XPS 13', 'price' => 999.99],
            ['name' => 'iPhone 13', 'price' => 799.00],
            ['name' => 'Samsung Galaxy S21', 'price' => 749.99],
            ['name' => 'Sony PlayStation 5', 'price' => 499.99],
            ['name' => 'Nintendo Switch', 'price' => 299.99],
            ['name' => 'Logitech MX Master 3', 'price' => 99.99],
        ];
        
        $addedCount = 0;
        
        foreach ($products as $productData) {
            // Check if a product with this name already exists
            $existingProduct = $this->entityManager->getRepository(Product::class)
                ->findOneBy(['name' => $productData['name']]);
            
            if (!$existingProduct) {
                $product = new Product();
                $product->setName($productData['name']);
                $product->setPrice($productData['price']);
                
                $this->entityManager->persist($product);
                $addedCount++;
            }
        }
        
        if ($addedCount > 0) {
            $this->entityManager->flush();
            $io->success("$addedCount products have been added.");
        } else {
            $io->info("No new products were added. They may already exist.");
        }

        return Command::SUCCESS;
    }
} 