<?php

declare(strict_types=1);

namespace App\Service;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory as FirebaseFactory;

class FirebaseFactoryFactory
{
    public static function create(string $credentialsPath, string $projectDir, string $environment): ?FirebaseFactory
    {
        if (!\str_starts_with($credentialsPath, '/') && !\preg_match('#^[A-Z]:[/\\\]#i', $credentialsPath)) {
            $credentialsPath = \rtrim($projectDir, '/').'/'.\ltrim($credentialsPath, '/');
        }

        $hasFile = '' !== $credentialsPath && \is_file($credentialsPath);

        if (!$hasFile) {
            if (!\in_array($environment, ['dev', 'test'], true)) {
                throw new \RuntimeException(\sprintf('Fichier de credentials Firebase introuvable : %s', $credentialsPath));
            }

            @\trigger_error(
                'Firebase désactivé : fichier de credentials manquant en environnement dev/test.',
                E_USER_WARNING
            );

            return null;
        }

        return (new FirebaseFactory())->withServiceAccount($credentialsPath);
    }

    public static function createMessaging(?FirebaseFactory $factory): ?Messaging
    {
        return $factory?->createMessaging();
    }
}

