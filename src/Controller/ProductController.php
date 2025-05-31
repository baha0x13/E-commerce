<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(
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

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('admin/product/index.html.twig', [
                'products' => $products,
                'searchTerm' => $searchTerm,
                'selectedCategory' => $category,
                'categories' => $productRepository->getAvailableCategories(),
                'seeProductsRoute' => 'app_product_index',
            ]);
        } else {
            return $this->render('product/user.html.twig', [
                'products' => $products,
                'searchTerm' => $searchTerm,
                'selectedCategory' => $category,
                'categories' => $productRepository->getAvailableCategories(),
            ]);
        }
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $user = $this->getUser();
        $backToProductsRoute = 'user_products';

        if ($user && in_array('ROLE_ADMIN', $user->getRoles())) {
            $backToProductsRoute = 'app_product_index';
            return $this->render('admin/product/show.html.twig', [
                'product' => $product,
                'backToProductsRoute' => $backToProductsRoute,
            ]);
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'backToProductsRoute' => $backToProductsRoute,
        ]);
    }

    #[Route('/admin/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    $product->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Image upload failed.');
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form->createView(),
            'categories' => $this->getAvailableCategories()
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    $product->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Image upload failed.');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('app_product_index');
        }

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'categories' => $this->getAvailableCategories()
        ]);
    }

    #[Route('/admin/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
            $this->addFlash('success', 'Product deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index');
    }

    private function getAvailableCategories(): array
    {
        return [
            'Electronique',
            'Vêtements',
            'Alimentation',
            'Maison',
            'Jardin',
            'Sport'
        ];
    }
}
