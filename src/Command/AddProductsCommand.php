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
    description: 'Add sample products to the database',
)]
class AddProductsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Adding Sample Products');

        $products = [
            [
                'name' => 'Laptop Dell XPS 13',
                'price' => 999.99,
                'stock' => 50,
                'description' => '13.4" FHD+ laptop with 11th Gen Intel Core i7',
                'category' => 'Electronics',
                'photo' => 'dell.jpg'
            ],
            [
                'name' => 'iPhone 13',
                'price' => 799.00,
                'stock' => 100,
                'description' => '6.1-inch Super Retina XDR display with A15 Bionic chip',
                'category' => 'Electronics',
                'photo' => 'iphone.jpg'
            ],
            [
                'name' => 'Samsung Galaxy S21',
                'price' => 749.99,
                'stock' => 75,
                'description' => '5G smartphone with 64MP camera and 8K video',
                'category' => 'Electronics',
                'photo' => 'galaxy.jpg'
            ],
            [
                'name' => 'Sony PlayStation 5',
                'price' => 499.99,
                'stock' => 30,
                'description' => 'Next-gen gaming console with ultra-high speed SSD',
                'category' => 'Gaming',
                'photo' => 'ps5.jpg'
            ],
            [
                'name' => 'Nintendo Switch',
                'price' => 299.99,
                'stock' => 60,
                'description' => 'Hybrid gaming console for home and on-the-go play',
                'category' => 'Gaming',
                'photo' => 'switch.jpg'
            ],
            [
                'name' => 'Logitech MX Master 3',
                'price' => 99.99,
                'stock' => 120,
                'description' => 'Advanced wireless mouse with ultra-fast scrolling',
                'category' => 'Accessories',
                'photo' => 'logitik.jpg'
            ],
            [
                'name' => 'Ergonomic Office Chair',
                'price' => 249.99,
                'stock' => 25,
                'description' => 'Comfortable chair with lumbar support and adjustable arms',
                'category' => 'Furniture',
                'photo' => 'chair.jpg'
            ],
            [
                'name' => 'Wireless Charging Pad',
                'price' => 29.99,
                'stock' => 200,
                'description' => 'Qi-certified fast charging pad for smartphones',
                'category' => 'Accessories',
                'photo' => 'pad.jpg'
            ],
        ];

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($products as $productData) {
            $existingProduct = $this->entityManager->getRepository(Product::class)
                ->findOneBy(['name' => $productData['name']]);

            if ($existingProduct) {
                $io->note(sprintf('Product already exists: %s', $productData['name']));
                $skippedCount++;
                continue;
            }

            $product = new Product();
            $product->setName($productData['name']);
            $product->setPrice($productData['price']);
            $product->setStock($productData['stock']);
            $product->setDescription($productData['description']);
            $product->setCategory($productData['category']);

            if (!empty($productData['photo'])) {
                $photoPath = __DIR__ . '/../../public/uploads/' . $productData['photo'];
                if (file_exists($photoPath)) {
                    $product->setPhoto($productData['photo']);
                } else {
                    $io->warning(sprintf('Photo file not found: %s', $photoPath));
                }
            }

            $this->entityManager->persist($product);
            $addedCount++;
            $io->text(sprintf('Added product: <info>%s</info>', $productData['name']));
        }

        if ($addedCount > 0) {
            $this->entityManager->flush();
        }

        $io->success([
            sprintf('Added %d new products', $addedCount),
            sprintf('Skipped %d existing products', $skippedCount),
            sprintf('Total products processed: %d', count($products))
        ]);

        return Command::SUCCESS;
    }
}
