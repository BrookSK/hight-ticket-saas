<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Service;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserRepository;

/**
 * User business logic: CRUD and password recovery.
 *
 * Passwords are always hashed with password_hash(); never stored in plain
 * text. Recovery uses single-use, expiring tokens.
 */
final class UserService extends Service
{
    public const STATUSES = ['active', 'inactive'];

    private const RESET_TTL_SECONDS = 3600;

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>, id?:int}
     */
    public function create(array $input): array
    {
        $errors = $this->validate($input, true);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->users()->emailExists($email)) {
            return ['ok' => false, 'errors' => ['email' => 'validation.unique']];
        }

        $id = $this->users()->create([
            'name'           => trim((string) $input['name']),
            'email'          => $email,
            'phone'          => $this->nullableTrim($input['phone'] ?? null),
            'password_hash'  => password_hash((string) $input['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role_id'        => $this->nullableInt($input['role_id'] ?? null),
            'is_super_admin' => !empty($input['is_super_admin']) ? 1 : 0,
            'status'         => in_array($input['status'] ?? '', self::STATUSES, true)
                ? (string) $input['status']
                : 'active',
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok:bool, errors?:array<string,string>}
     */
    public function update(int $id, array $input): array
    {
        if ($this->users()->findById($id) === null) {
            return ['ok' => false, 'errors' => ['id' => 'errors.not_found']];
        }

        $errors = $this->validate($input, false);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $email = strtolower(trim((string) $input['email']));
        if ($this->users()->emailExists($email, $id)) {
            return ['ok' => false, 'errors' => ['email' => 'validation.unique']];
        }

        $this->users()->update($id, [
            'name'    => trim((string) $input['name']),
            'email'   => $email,
            'phone'   => $this->nullableTrim($input['phone'] ?? null),
            'role_id' => $this->nullableInt($input['role_id'] ?? null),
            'status'  => in_array($input['status'] ?? '', self::STATUSES, true)
                ? (string) $input['status']
                : 'active',
        ]);

        $password = (string) ($input['password'] ?? '');
        if ($password !== '') {
            $this->users()->updatePassword(
                $id,
                password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
            );
        }

        return ['ok' => true];
    }

    public function delete(int $id): bool
    {
        if ($this->users()->findById($id) === null) {
            return false;
        }

        $this->users()->softDelete($id);

        return true;
    }

    // ------------------------------------------------------- password recovery

    /**
     * Create a reset token for an e-mail if the account exists. Returns the raw
     * token (to be e-mailed) or null when the account does not exist. Callers
     * must not reveal which case occurred to the user.
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $email = strtolower(trim($email));
        if ($this->users()->findByEmail($email) === null) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::RESET_TTL_SECONDS);

        $this->resets()->create($email, hash('sha256', $token), $expiresAt);

        return $token;
    }

    /**
     * Reset the password using a valid token. Returns true on success.
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $email = strtolower(trim($email));

        if (strlen($newPassword) < 8) {
            return false;
        }

        $row = $this->resets()->findValid($email);
        if ($row === null) {
            return false;
        }

        if (!hash_equals((string) $row['token_hash'], hash('sha256', $token))) {
            return false;
        }

        $this->users()->updatePasswordByEmail(
            $email,
            password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])
        );
        $this->resets()->markUsed((int) $row['id']);

        return true;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private function validate(array $input, bool $requirePassword): array
    {
        $errors = [];

        if (trim((string) ($input['name'] ?? '')) === '') {
            $errors['name'] = 'validation.required';
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email === '') {
            $errors['email'] = 'validation.required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'validation.email';
        }

        $password = (string) ($input['password'] ?? '');
        if ($requirePassword && $password === '') {
            $errors['password'] = 'validation.required';
        } elseif ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'validation.min';
        }

        return $errors;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        return (int) $value;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function users(): UserRepository
    {
        /** @var UserRepository $repository */
        $repository = $this->container->get(UserRepository::class);

        return $repository;
    }

    private function resets(): PasswordResetRepository
    {
        /** @var PasswordResetRepository $repository */
        $repository = $this->container->get(PasswordResetRepository::class);

        return $repository;
    }
}
