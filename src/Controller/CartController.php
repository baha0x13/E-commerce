<?php
namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(CartService $cartService, EntityManagerInterface $entityManager): Response
    {
        $cart = $cartService->getCart();
        $cartItems = [];
        $total = 0;
        
        foreach ($cart as $id => $quantity) {
            $product = $entityManager->getRepository(Product::class)->find($id);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];
                $total += $product->getPrice() * $quantity;
            }
        }
        
        return $this->render('cart/index.html.twig', [
            'items' => $cartItems,
            'total' => $total
        ]);
    }
    
    #[Route('/add/{id}', name: 'add')]
    public function add(int $id, Request $request, CartService $cartService, EntityManagerInterface $entityManager): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        
        $quantity = max(1, (int) $request->query->get('quantity', 1));
        $cartService->addToCart($id, $quantity);
        
        $this->addFlash('success', 'Product added to cart!');
        
        return $this->redirectToRoute('cart_index');
    }
    
    #[Route('/remove/{id}', name: 'remove')]
    public function remove(int $id, CartService $cartService): Response
    {
        $cartService->removeFromCart($id);
        
        $this->addFlash('success', 'Product removed from cart!');
        
        return $this->redirectToRoute('cart_index');
    }
    
    #[Route('/update/{id}', name: 'update')]
    public function update(int $id, Request $request, CartService $cartService): Response
    {
        $quantity = (int) $request->request->get('quantity');
        
        if ($quantity > 0) {
            $cartService->updateQuantity($id, $quantity);
            $this->addFlash('success', 'Quantity updated!');
        } else {
            $cartService->removeFromCart($id);
            $this->addFlash('success', 'Product removed from cart!');
        }
        
        return $this->redirectToRoute('cart_index');
    }
    
    #[Route('/clear', name: 'clear')]
    public function clear(CartService $cartService): Response
    {
        $cartService->clearCart();
        
        $this->addFlash('success', 'Cart cleared!');
        
        return $this->redirectToRoute('cart_index');
    }
    
    #[Route('/order', name: 'order')]
    public function order(): Response
    {
        // Here you would implement your order processing logic
        // For now, just display a thank you message
        
        return $this->render('cart/order.html.twig');
    }
}
