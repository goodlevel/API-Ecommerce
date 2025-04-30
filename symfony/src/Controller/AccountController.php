<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Account Management")
 */
#[Route('/api', name: 'app_account_')]
class AccountController extends AbstractController
{
    private $passwordEncoder;
    private $userRepository;

    public function __construct(UserPasswordHasherInterface $passwordEncoder, UserRepository $userRepository)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user account
     * 
     * @OA\RequestBody(
     *     description="User registration data",
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *             type="object",
     *             required={"username", "firstname", "email", "password"},
     *             @OA\Property(property="username", type="string", example="johndoe", description="Unique username"),
     *             @OA\Property(property="firstname", type="string", example="John", description="User's first name"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Valid email address"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePassword123!", description="Password (min 8 characters)"),
     *         )
     *     )
     * )
     * @OA\Response(
     *     response=201,
     *     description="User registered successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="User Created Successfully")
     *     )
     * )
     * @OA\Response(
     *     response=400,
     *     description="Validation error",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Missing required fields")
     *     )
     * )
     * @OA\Response(
     *     response=409,
     *     description="Conflict",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Email already exists")
     *     )
     * )
     */
    #[Route('/account', name: 'register', methods: 'post')]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        
        $data = json_decode($request->getContent());

        if (!$data) {
            return $this->json(['message' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $username = $data->username ?? null;
        $firstname = $data->firstname ?? null;
        $email = $data->email ?? null;
        $password = $data->password ?? null;
        
        if (!$username || !$firstname || !$email || !$password) {
            return $this->json(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Email already exists'], Response::HTTP_CONFLICT);
        }
    
        $user = new User();
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $password
        );

        $user->setPassword($hashedPassword);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstname($firstname);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
    
        return $this->json(['message' => 'User Created Successfully'], Response::HTTP_CREATED);
    }

}
