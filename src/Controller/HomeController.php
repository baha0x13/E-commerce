<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();
        $seeProductsRoute = 'user_products'; // default for users
        $isAdmin = false;

        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $seeProductsRoute = 'app_product_index'; // admin route
            $isAdmin = true;
        }

        return $this->render('home/index.html.twig', [
            'seeProductsRoute' => $seeProductsRoute,
            'isAdmin' => $isAdmin,
        ]);
    }
}