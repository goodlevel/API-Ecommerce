<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordCredentials;

class JwtAuthenticator extends AbstractAuthenticator
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    public function authenticate(Request $request): Passport
    {
        $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));

        // Décoder le JWT pour obtenir les données de l'utilisateur
        if (!is_string($token) || empty($token)) {
            throw new AuthenticationException('Invalid or missing token');
        }

        $decodedToken = $this->jwtManager->decode($token);

        // Vérifier que le token est valide
        if (!$decodedToken) {
            throw new AuthenticationException('Invalid or expired token');
        }

        // Créer un token Symfony UsernamePasswordToken à partir du token décodé
        $userEmail = $decodedToken['email'];
        $user = new User($userEmail); // Utilisez la classe User ou un service pour récupérer l'utilisateur

        // Créer un token Symfony valide
        $symfonyToken = new UsernamePasswordToken($user, null, 'api', ['ROLE_USER']); 

        return new Passport(
            new UserBadge($userEmail),
            new PasswordCredentials(''),   // Pas besoin de mot de passe ici
            []
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        // Retourner une réponse d'erreur avec le code 401
        return new JsonResponse(
            ['message' => 'Authentication failed', 'error' => $exception->getMessage()],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        // Aucune action spécifique après une authentification réussie, renvoyer null ou une autre réponse
        return null;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }
}



