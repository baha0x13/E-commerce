<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestDataController extends AbstractController
{
    #[Route('/setup/add-products', name: 'app_setup_add_products')]
    public function addProducts(EntityManagerInterface $entityManager): Response
    {
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
            $existingProduct = $entityManager->getRepository(Product::class)
                ->findOneBy(['name' => $productData['name']]);
            
            if (!$existingProduct) {
                $product = new Product();
                $product->setName($productData['name']);
                $product->setPrice($productData['price']);
                
                $entityManager->persist($product);
                $addedCount++;
            }
        }
        
        if ($addedCount > 0) {
            $entityManager->flush();
            $this->addFlash('success', "$addedCount products have been added.");
        } else {
            $this->addFlash('info', "No new products were added. They may already exist.");
        }
        
        return $this->redirectToRoute('app_product_index');
    }
} 