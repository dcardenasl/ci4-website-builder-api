<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Interfaces\System\EmailServiceInterface;
use App\Interfaces\Users\UserRepositoryInterface;
use CodeIgniter\I18n\Time;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;
use dcardenasl\Ci4ApiCore\Exceptions\ConflictException;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Security\Hasher;
use dcardenasl\Ci4ApiCore\Services\AuditServiceInterface;
use dcardenasl\Ci4ApiCore\Support\ResolvesWebAppLinks;

/**
 * Modernized Verification Service
 */
class VerificationService implements \App\Interfaces\Auth\VerificationServiceInterface
{
    use ResolvesWebAppLinks;
    use \dcardenasl\Ci4ApiCore\Services\HandlesTransactions;

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected EmailServiceInterface $emailService,
        protected AuditServiceInterface $auditService
    ) {
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail(int $userId, ?SecurityContext $context = null): bool
    {
        $user = $this->userRepository->find($userId);

        if (!$user instanceof \App\Entities\UserEntity) {
            throw new NotFoundException(lang('Verification.userNotFound'));
        }

        if ($user->email_verified_at !== null) {
            throw new ConflictException(lang('Verification.alreadyVerified'));
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = Hasher::token($token);
        $timestamp = strtotime('+24 hours');

        // Ensure $timestamp is int for date()
        $finalTimestamp = is_int($timestamp) ? $timestamp : (time() + 86400);
        $expiresAt = date('Y-m-d H:i:s', $finalTimestamp);

        $this->userRepository->withAuditAction('verification_email_sent')->update($userId, [
            'email_verification_token' => $tokenHash,
            'verification_token_expires' => $expiresAt,
        ]);

        $verificationLink = $this->buildVerificationUrl($token);

        $this->emailService->queueTemplate('verification', (string) $user->email, [
            'subject' => lang('Email.verification.subject'),
            'display_name' => (string) $user->getDisplayName(),
            'verification_link' => $verificationLink,
            'expires_at' => date('F j, Y g:i A', strtotime($expiresAt)),
        ]);

        return true;
    }

    public function verifyEmail(DataTransferObjectInterface $request, ?SecurityContext $context = null): bool
    {
        /** @var \App\DTO\Request\Identity\VerificationRequestDTO $request */
        $user = $this->userRepository->findByVerificationToken($request->token);

        if (! $user) {
            $this->auditService->log(
                'email_verification_failed',
                'users',
                null,
                [],
                ['reason' => 'invalid_token', 'token_prefix' => substr($request->token, 0, 8)],
                $context,
                'failure',
                'warning'
            );
            throw new NotFoundException(lang('Verification.invalidToken'));
        }

        /** @var \App\Entities\UserEntity $user */

        $expiresAtVal = $user->verification_token_expires;
        $expiresAtStr = '';

        if ($expiresAtVal instanceof Time) {
            $expiresAtStr = $expiresAtVal->toDateTimeString();
        } elseif (is_string($expiresAtVal)) {
            $expiresAtStr = $expiresAtVal;
        }

        if ($expiresAtStr !== '') {
            $expiresAtTimestamp = strtotime($expiresAtStr);
            if (is_int($expiresAtTimestamp) && $expiresAtTimestamp < time()) {
                $this->auditService->log(
                    'email_verification_failed',
                    'users',
                    (int) $user->id,
                    [],
                    ['reason' => 'token_expired', 'email' => $user->email],
                    $context,
                    'failure',
                    'warning'
                );
                throw new BadRequestException(lang('Verification.tokenExpired'));
            }
        }

        $now = date('Y-m-d H:i:s');

        $this->wrapInTransaction(function () use ($user, $now): void {
            $this->userRepository->withAuditAction('email_verified')->update($user->id, [
                'email_verified_at' => $now,
                'email_verification_token' => null,
                'verification_token_expires' => null,
            ]);
        });

        return true;
    }

    /**
     * Resend verification email
     */
    public function resendVerification(int $userId, ?SecurityContext $context = null): bool
    {
        return $this->sendVerificationEmail($userId, $context);
    }
}
