<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Repository;

/**
 * Data access for password reset tokens. No business logic here.
 */
final class PasswordResetRepository extends Repository
{
    public function create(string $email, string $tokenHash, string $expiresAt): void
    {
        // Invalidate previous unused tokens for the same email.
        $this->db->execute(
            'UPDATE `password_resets` SET `used_at` = NOW()
             WHERE `email` = :email AND `used_at` IS NULL',
            ['email' => $email]
        );

        $this->db->execute(
            'INSERT INTO `password_resets` (`email`, `token_hash`, `expires_at`, `created_at`)
             VALUES (:email, :token_hash, :expires_at, NOW())',
            ['email' => $email, 'token_hash' => $tokenHash, 'expires_at' => $expiresAt]
        );
    }

    /**
     * Find a valid (unused, unexpired) token row for an email.
     *
     * @return array<string, mixed>|null
     */
    public function findValid(string $email): ?array
    {
        return $this->db->fetch(
            'SELECT `id`, `email`, `token_hash`, `expires_at`
             FROM `password_resets`
             WHERE `email` = :email AND `used_at` IS NULL AND `expires_at` >= NOW()
             ORDER BY `id` DESC LIMIT 1',
            ['email' => $email]
        );
    }

    public function markUsed(int $id): void
    {
        $this->db->execute(
            'UPDATE `password_resets` SET `used_at` = NOW() WHERE `id` = :id',
            ['id' => $id]
        );
    }
}
