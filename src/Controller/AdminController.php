<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin')]
#[\Symfony\Component\Security\Http\Attribute\IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    // ---------------------------
    // Edit logged-in admin profile
    // ---------------------------
    #[Route('/profile', name: 'admin_profile')]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'No user is logged in.');
            return $this->redirectToRoute('app_login'); // adjust your login route
        }

        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Preserve original roles - admin shouldn't change their own role
            $originalRoles = $user->getRoles();
            
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // Restore original roles to prevent self-demotion
            $user->setRoles($originalRoles);

            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('admin_profile');
        }

        return $this->render('admin/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ---------------------------
    // Create a new admin user
    // ---------------------------
    #[Route('/create-user', name: 'admin_create_user')]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                $this->addFlash('error', 'Password is required to create a new user.');
                return $this->redirectToRoute('admin_create_user');
            }

            // Set default role as admin
            $user->setRoles(['ROLE_ADMIN']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'New admin user created successfully!');
            return $this->redirectToRoute('admin_create_user');
        }

        return $this->render('admin/create_user.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
