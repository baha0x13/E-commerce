<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\ProductType;


#[Route('/user/dashboard')]
class UserDashboardController extends AbstractController
{
    #[Route('/', name: 'user_dashboard')]
    public function index(): Response
    {
        return $this->render('user_dashboard/index.html.twig');
    }

    #[Route('/product', name: 'user_products')]
    public function products(EntityManagerInterface $entityManager,
    ProductRepository $productRepository,
    PaginatorInterface $paginator,
    Request $request
): Response {
    $searchTerm = $request->query->get('q', '');
    $category = $request->query->get('category', '');

    $products = $productRepository->search(
        $searchTerm,
        $category,
        $request->query->getInt('page', 1),
        $paginator
    );

    return $this->render('product/index.html.twig', [
        'products' => $products,
        'searchTerm' => $searchTerm,
        'selectedCategory' => $category,
        'categories' => $productRepository->getAvailableCategories()
    ]);
}

    #[Route('/orders', name: 'user_orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('user_dashboard/orders.html.twig', [
            'orders' => $orders,
        ]);
    }
} 