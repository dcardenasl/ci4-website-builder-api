<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Interfaces\System\EmailServiceInterface;
use App\Interfaces\Tokens\RefreshTokenServiceInterface;
use App\Models\PasswordResetModel;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Security\Hasher;
use dcardenasl\Ci4ApiCore\Services\AuditServiceInterface;
use dcardenasl\Ci4ApiCore\Support\ResolvesWebAppLinks;

/**
 * Modernized Password Reset Service
 */
class PasswordResetService implements \App\Interfaces\Auth\PasswordResetServiceInterface
{
    use ResolvesWebAppLinks;
    use \dcardenasl\Ci4ApiCore\Services\HandlesTransactions;

    public function __construct(
        protected \App\Interfaces\Users\UserRepositoryInterface $userRepository,
        protected PasswordResetModel $passwordResetModel,
        protected EmailServiceInterface $emailService,
        protected RefreshTokenServiceInterface $refreshTokenService,
        protected AuditServiceInterface $auditService
    ) {
    }

    /**
     * Send password reset link to email
     */
    public function sendResetLink(DataTransferObjectInterface $request, ?SecurityContext $context = null): bool
    {
        /** @var \App\DTO\Request\Identity\ForgotPasswordRequestDTO $request */
        $email = $request->email;
        $user = $this->userRepository->findByEmail($email);

        if ($user instanceof \App\Entities\UserEntity) {
            $this->auditService->log('password_reset_request', 'users', (int) $user->id, [], ['email' => $email], $context);

            $token = bin2hex(random_bytes(32));
            $tokenHash = Hasher::token($token);
            $this->passwordResetModel->where('email', $email)->delete();
            $this->passwordResetModel->insert(['email' => $email, 'token' => $tokenHash, 'created_at' => date('Y-m-d H:i:s')]);

            $resetLink = $this->buildResetPasswordUrl($token, $email);
            try {
                $this->emailService->queueTemplate('password-reset', $email, [
                    'subject' => lang('Email.passwordReset.subject'),
                    'reset_link' => $resetLink,
                    'expires_in' => '60 minutes',
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Failed to queue password reset email: ' . $e->getMessage());
            }
        } else {
            $deletedUser = $this->userRepository->findByEmailWithDeleted($email);
            if ($deletedUser instanceof \App\Entities\UserEntity && $deletedUser->deleted_at !== null) {
                $this->reactivateDeletedUserForApproval($deletedUser, $email, $context);
            }
        }

        return true;
    }

    /**
     * Validate reset token
     */
    public function validateToken(DataTransferObjectInterface $request, ?SecurityContext $context = null): bool
    {
        /** @var \App\DTO\Request\Identity\PasswordResetTokenValidationDTO $request */
        $this->passwordResetModel->cleanExpired(60);

        if (!$this->passwordResetModel->isValidToken($request->email, $request->token, 60)) {
            $this->auditService->log(
                'password_reset_token_invalid',
                'users',
                null,
                [],
                ['email' => $request->email],
                $context,
                'failure',
                'warning'
            );
            throw new NotFoundException(lang('PasswordReset.invalidToken'));
        }

        return true;
    }

    /**
     * Reset password using token
     */
    public function resetPassword(DataTransferObjectInterface $request, ?SecurityContext $context = null): bool
    {
        /** @var \App\DTO\Request\Identity\ResetPasswordRequestDTO $request */
        $this->passwordResetModel->cleanExpired(60);

        if (!$this->passwordResetModel->isValidToken($request->email, $request->token, 60)) {
            $this->auditService->log(
                'password_reset_token_invalid',
                'users',
                null,
                [],
                ['email' => $request->email],
                $context,
                'failure',
                'warning'
            );
            throw new NotFoundException(lang('PasswordReset.invalidToken'));
        }

        $user = $this->userRepository->findByEmail($request->email);
        if (!$user) {
            throw new NotFoundException(lang('PasswordReset.userNotFound'));
        }

        $this->wrapInTransaction(function () use ($user, $request): void {
            $updateData = ['password' => password_hash($request->password, PASSWORD_BCRYPT)];

            $wasInvited = ($user->status ?? null) === 'invited' || ($user->invited_at ?? null) !== null;
            if ($wasInvited) {
                $updateData['email_verified_at'] = date('Y-m-d H:i:s');
                $updateData['invited_at'] = null;
                $updateData['invited_by'] = null;
                $updateData['status'] = 'active';
            }

            $this->userRepository->withAuditAction('password_reset_success')->update($user->id, $updateData);

            $tokenHash = Hasher::token($request->token);
            $this->passwordResetModel->where('email', $request->email)->where('token', $tokenHash)->delete();
        });

        return true;
    }

    private function reactivateDeletedUserForApproval(\App\Entities\UserEntity $user, string $email, ?SecurityContext $context = null): void
    {
        $this->wrapInTransaction(function () use ($user, $email, $context): void {
            $requiresVerification = Hasher::isEmailVerificationRequired();
            $status = $requiresVerification ? 'pending_approval' : 'active';
            $now = date('Y-m-d H:i:s');

            $this->userRepository->restore((int) $user->id, [
                'status'      => $status,
                'approved_at' => $status === 'active' ? $now : null,
                'approved_by' => null,
            ]);
            $this->refreshTokenService->revokeAllUserTokens((int) $user->id);
            $this->passwordResetModel->where('email', $email)->delete();
            $this->auditService->log('account_reactivation_requested', 'users', (int) $user->id, [], ['email' => $email], $context);
        });
    }
}
