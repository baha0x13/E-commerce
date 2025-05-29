<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\RequestStack;

class RegistrationController extends AbstractController
{
    public function __construct(private MailerInterface $mailer, private UrlGeneratorInterface $urlGenerator, private RequestStack $requestStack) {}

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Generate verification token
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $user->setIsVerified(false);


            $entityManager->persist($user);
            $entityManager->flush();

            // Generate verification URL
            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );


            // Send verification email
            $context = [
                'user' => $user,
                'verificationUrl' => $this->urlGenerator->generate(
                    'app_verify_email',
                    ['token' => $user->getVerificationToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ];

            $email = (new Email())
                ->from(new Address('mojo.2025.jojo@gmail.com', 'E-commerce'))
                ->to(new Address($user->getEmail(), $user->getUsername()))
                ->subject('Vérifiez votre email')
                ->html($this->renderView(
                    'emails/registration_verification.html.twig',
                    $context
                ));

            try {
                $this->mailer->send($email);
                $this->addFlash('success', 'Un email de vérification a été envoyé à votre adresse email.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de l\'email de vérification.');
            }

            return $this->redirectToRoute('check_email');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/registration/check-email', name: 'check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('registration/confirmation_email.html.twig');
    }
    
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre email a déjà été vérifié.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Votre email a été vérifié avec succès. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
