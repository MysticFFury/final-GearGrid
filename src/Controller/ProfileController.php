<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Log;
use App\Form\ChangePasswordType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {
    }

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->render('profile/index.html.twig', [
                'user' => $user,
            ]);
        }

        $orders = $this->orderRepository->findBy(
            ['placedBy' => $user],
            ['createdAt' => 'DESC'],
        );

        return $this->render('customer/account.html.twig', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');

                return $this->render(
                    ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF'))
                        ? 'profile/edit.html.twig'
                        : 'customer/edit_password.html.twig',
                    [
                        'form' => $form->createView(),
                        'user' => $user,
                    ]
                );
            }

            // Hash and set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $entityManager->flush();

            // Log password change
            $log = new Log();
            $log->setAction('PASSWORD_CHANGE')
                ->setMessage("User '{$user->getUserIdentifier()}' changed their password")
                ->setStatus('active')
                ->setUserName($user->getUserIdentifier())
                ->setUserRole(implode(', ', $user->getRoles()))
                ->setEntity('User')
                ->setCreatedAt(new \DateTime());
            $entityManager->persist($log);
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully!');

            return $this->redirectToRoute('app_profile');
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_STAFF')) {
            return $this->render('profile/edit.html.twig', [
                'form' => $form->createView(),
                'user' => $user,
            ]);
        }

        return $this->render('customer/edit_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}

