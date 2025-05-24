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

        $context = [
            'user' => $user,
            'order' => $order,
            'verificationUrl' => $this->urlGenerator->generate(
                'order_verify',
                ['token' => $order->getVerificationToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        ];

        $email = (new TemplatedEmail())
            ->from('mojo.2025.jojo@gmail.com')
            ->to($user->getEmail())
            ->subject('Verification de votre Commande')
            ->htmlTemplate('emails/order_verification.html.twig')
            ->context($context);
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


        if (!$order) {
            return $this->redirectToRoute('app_order');
        }

        if ($order->getStatus() === 'en_attente') {
            $order->setStatus('en_attente_paiement');
            $this->em->flush();

            return $this->redirectToRoute('order_payment', ['id' => $order->getId()]);
        }

        if ($order->getStatus() === 'en_attente_confirmation_payment') {
            $order->setStatus('confirme');
            $this->em->flush();

            return $this->redirectToRoute('order_confirmed', ['id' => $order->getId()]);
        }

        return $this->redirectToRoute('order_user');
    }


    #[Route('/order/check-email', name: 'check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('order/check_email.html.twig');
    }

    #[Route('/order/confirmed/{id}', name: 'order_confirmed', methods: ['GET'])]
    public function confirmed(int $id): Response
    {
        $order = $this->em->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('order/confirm.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/order/{id}/payment', name: 'order_payment', methods: ['GET', 'POST'])]
    public function payment(Order $order, Request $request,MailerInterface $mailer): Response
    {
        $user = $this->security->getUser();

        // Check if user owns this order and order is awaiting payment
        if ($order->getUser() !== $user || $order->getStatus() !== 'en_attente_paiement') {
            throw $this->createAccessDeniedException('Unauthorized access to payment page.');
        }

        if ($request->isMethod('POST')) {
            $cardNumber = $request->request->get('cardNumber');
            $expiry = $request->request->get('expiry');
            $cvc = $request->request->get('cvc');

            if (!$cardNumber || !$expiry || !$cvc) {
                $this->addFlash('error', 'Please fill all payment fields.');
            }
            elseif (
                strlen($cardNumber) != 16 ||
                !ctype_digit($cardNumber) ||
                strlen($cvc) != 3 ||
                !ctype_digit($cvc) ||
                !preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)
            ) {
                $this->addFlash('error', 'Invalid Credentials.');
            }
            else {
                // Simulate success: update order status etc.
                $order->setStatus('en_attente_confirmation_payment');
                $this->em->flush();
                $context = [
                    'user' => $user,
                    'order' => $order,
                    'verificationUrl' => $this->urlGenerator->generate(
                        'order_verify',
                        ['token' => $order->getVerificationToken()],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ];


                $email = (new TemplatedEmail())
                    ->from('mojo.2025.jojo@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Verification de Paiement')
                    ->htmlTemplate('emails/payment_verification.html.twig')
                    ->context($context);

                $mailer->send($email);

                // 3. Redirect to a "check your email" page
                return $this->redirectToRoute('check_email');
            }
        }


        return $this->render('order/payment.html.twig', [
            'order' => $order,
        ]);
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
