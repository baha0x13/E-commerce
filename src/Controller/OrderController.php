<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private ProductRepository $productRepository,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    #[Route('/order/create', name: 'order_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) return $this->json(['error' => 'User not logged in'], 401);

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            return $this->json(['error' => 'Invalid or empty cart'], 400);
        }

        $order = (new Order())
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('en_attente')
            ->setVerificationToken(bin2hex(random_bytes(32)));

        $total = 0;
        foreach ($data as $productId => $quantity) {
            if (!is_numeric($productId) || !is_numeric($quantity)) {
                return $this->json(['error' => 'Invalid product data'], 400);
            }

            $product = $this->productRepository->find($productId);
            if (!$product) {
                return $this->json(['error' => "Product $productId not found"], 404);
            }

            $item = (new OrderItem())
                ->setAnOrder($order)
                ->setProduct($product)
                ->setQuantity($quantity)
                ->setPrice($product->getPrice());

            $this->em->persist($item);
            $total += $product->getPrice() * $quantity;
        }

        $order->setTotal($total);
        $this->em->persist($order);
        $this->em->flush();

        $email = (new TemplatedEmail())
            ->from('mojo.2025.jojo@gmail.com')
            ->to($user->getEmail())
            ->subject('Order Verification')
            ->htmlTemplate('emails/order_verification.html.twig')
            ->context([
                'user' => $user,
                'order' => $order,
                'verificationUrl' => $this->urlGenerator->generate('order_verify', ['token' => $order->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

        $this->mailer->send($email);

        return $this->json([
            'orderId' => $order->getId(),
            'total' => $total,
            'message' => 'Order created. Please verify via email.'
        ]);
    }

    #[Route('/order/verify/{token}', name: 'order_verify', methods: ['GET'])]
    public function verifyOrder(string $token): Response
    {
        $order = $this->em->getRepository(Order::class)->findOneBy(['verificationToken' => $token]);
        if (!$order || $order->getStatus() !== 'en_attente') {
            $this->addFlash('error', 'Invalid or already verified order.');
            return $this->redirectToRoute('app_order');
        }

        $order->setStatus('payée')->setVerificationToken(null);
        $this->em->flush();

        $this->addFlash('success', 'Order verified!');
        return $this->redirectToRoute('order_user');
    }

    #[Route('/order/confirm', name: 'order_confirm', methods: ['GET'])]
    public function confirm(Request $request): Response
    {
        $orderId = $request->query->get('orderId');
        $order = $orderId ? $this->em->getRepository(Order::class)->find($orderId) : null;

        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('app_order');
        }

        if ($order->getStatus() !== 'payée') {
            $order->setStatus('payée');
            $this->em->flush();
        }

        return $this->render('order/confirm.html.twig', ['order' => $order]);
    }

    #[Route('/order/user', name: 'order_user', methods: ['GET'])]
    public function listUserOrders(): Response
    {
        $user = $this->security->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $orders = $this->em->getRepository(Order::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->render('order/list.html.twig', ['orders' => $orders]);
    }

    #[Route('/order', name: 'app_order', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('order/index.html.twig');
    }
}
