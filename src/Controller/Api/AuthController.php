<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse([
                'error' => 'Email and password are required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $password = $data['password'];

        // Find user by email
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is verified
        if (!$user->isVerified()) {
            return new JsonResponse([
                'error' => 'Please verify your email before logging in'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verify password
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'error' => 'Invalid credentials'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $requiredFields = ['email', 'password', 'name'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new JsonResponse([
                    'error' => ucfirst($field) . ' is required'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Check if user already exists
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
        
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Email already exists'
            ], Response::HTTP_CONFLICT);
        }

        // Create new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setName($data['name']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true); // Auto-verify for API registration
        $user->setVerificationToken(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'error' => 'Not authenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
