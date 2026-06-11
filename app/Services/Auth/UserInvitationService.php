<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Entities\UserEntity;
use App\Interfaces\System\EmailServiceInterface;
use App\Models\PasswordResetModel;
use dcardenasl\Ci4ApiCore\Security\Token;
use dcardenasl\Ci4ApiCore\Support\ResolvesWebAppLinks;

/**
 * User Invitation Service
 *
 * Handles the orchestration of user invitations and account activation flows.
 */
class UserInvitationService
{
    use ResolvesWebAppLinks;

    public function __construct(
        protected PasswordResetModel $passwordResetModel,
        protected EmailServiceInterface $emailService
    ) {
    }

    /**
     * Send invitation email to a newly created user.
     */
    public function sendInvitation(UserEntity $user, ?string $clientBaseUrl = null): void
    {
        $email = (string) ($user->email ?? '');
        if ($email === '') {
            return;
        }

        $token = Token::generate();

        // Standardize the password reset invitation flow
        $this->passwordResetModel->where('email', $email)->delete();
        $this->passwordResetModel->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $resetLink = $this->buildResetPasswordUrl($token, $email, $clientBaseUrl);
        $displayName = (string) $user->getDisplayName();

        $this->emailService->queueTemplate('invitation', $email, [
            'subject' => lang('Email.invitation.subject'),
            'display_name' => $displayName,
            'reset_link' => $resetLink,
            'expires_in' => '60 minutes',
        ]);
    }
}
