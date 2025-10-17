<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Tokens
{
    private const EXPIRATION_TIME = 14400; // 4 heures en secondes

    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private readonly string $appSecret,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * Génère un token pour un utilisateur
     */
    public function generate(User $user): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));

        $expiresAt = time() + self::EXPIRATION_TIME;

        $payload = $this->base64UrlEncode(json_encode([
            'email' => $user->getEmail(),
            'exp' => $expiresAt
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->appSecret, true)
        );

        return "$header.$payload.$signature";
    }

    /**
     * Valide un token et retourne l'utilisateur associé
     */
    public function validate(string $token): ?User
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Vérifier la signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->appSecret, true)
        );

        if ($signature !== $expectedSignature) {
            return null;
        }

        // Décoder le payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        if (!$payloadData || !isset($payloadData['email'], $payloadData['exp'])) {
            return null;
        }

        // Vérifier l'expiration
        if ($payloadData['exp'] < time()) {
            return null;
        }

        // Récupérer l'utilisateur
        return $this->userRepository->findOneBy(['email' => $payloadData['email']]);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
