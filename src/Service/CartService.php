<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $requestStack;
    private const CART_KEY = 'cart';

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    // Get cart items from session, or empty array
    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_KEY, []);
    }

    // Add a product to cart (by product ID)
    public function addToCart(int $productId, int $quantity = 1): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }

        $this->getSession()->set(self::CART_KEY, $cart);
    }

    // Remove a product from cart
    public function removeFromCart(int $productId): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        $this->getSession()->set(self::CART_KEY, $cart);
    }

    // Update quantity of a product in cart
    public function updateQuantity(int $productId, int $quantity): void
    {
        $cart = $this->getCart();

        if ($quantity <= 0) {
            $this->removeFromCart($productId);
            return;
        }

        $cart[$productId] = $quantity;
        $this->getSession()->set(self::CART_KEY, $cart);
    }

    // Clear cart
    public function clearCart(): void
    {
        $this->getSession()->remove(self::CART_KEY);
    }
}
