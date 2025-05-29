<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]  // Restreint l'accès aux admins uniquement
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Double vérification du rôle admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/products', name: 'product_list')]
    public function productList(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $products = $entityManager->getRepository(Product::class)->findAll();
        return $this->render('admin/products/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/orders', name: 'admin_orders')]
    public function orderList(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $orders = $entityManager->getRepository(Order::class)->findAll();
        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function userList(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $users = $entityManager->getRepository(User::class)->findAll();
        return $this->render('admin/users/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/users/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function editUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            // Get roles as an array from the form
            $roles = $request->request->all('roles') ?? [];
            $isVerified = $request->request->get('isVerified') === 'on';
            
            // Ensure ROLE_USER is always present
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            
            $user->setRoles($roles);
            $user->setIsVerified($isVerified);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'User updated successfully');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user
        ]);
    }
}