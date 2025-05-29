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
        // Get the current user
        $user = $this->getUser();
        
        // If user is logged in, redirect based on role
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return $this->redirectToRoute('admin_dashboard');
            } else {
                return $this->redirectToRoute('user_dashboard');
            }
        }
        
        // If no user is logged in, show the home page
        return $this->render('home/index.html.twig');
    }
} 