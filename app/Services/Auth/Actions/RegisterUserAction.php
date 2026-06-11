<?php

declare(strict_types=1);

namespace App\Services\Auth\Actions;

use App\DTO\Request\Auth\RegisterRequestDTO;
use App\Interfaces\Auth\VerificationServiceInterface;
use App\Interfaces\System\EmailServiceInterface;
use App\Interfaces\Users\UserRepositoryInterface;
use App\Services\Iam\UserRoleAssignmentService;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\ValidationException;
use dcardenasl\Ci4ApiCore\Security\Hasher;
use dcardenasl\Ci4ApiCore\Support\ResolvesWebAppLinks;

class RegisterUserAction
{
    use ResolvesWebAppLinks;

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected VerificationServiceInterface $verificationService,
        protected EmailServiceInterface $emailService,
        protected UserRoleAssignmentService $userRoleAssignmentService
    ) {
    }

    public function execute(RegisterRequestDTO $request, ?SecurityContext $context = null): \App\Entities\UserEntity
    {
        $requiresVerification = Hasher::isEmailVerificationRequired();
        $status = $requiresVerification ? 'pending_approval' : 'active';
        $now = date('Y-m-d H:i:s');

        $userId = $this->userRepository->insert([
            'email'      => $request->email,
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'password'   => password_hash($request->password, PASSWORD_BCRYPT),
            'role'       => 'user',
            'status'     => $status,
            'approved_at' => $requiresVerification ? null : $now,
        ]);

        if (!$userId) {
            throw new ValidationException(lang('Api.validationFailed'), $this->userRepository->errors());
        }

        /** @var \App\Entities\UserEntity|null $user */
        $user = $this->userRepository->find((int) $userId);
        if ($user === null) {
            throw new ValidationException(lang('Api.validationFailed'), ['user' => lang('Api.resourceNotFound')]);
        }

        $this->userRoleAssignmentService->assignRoleByCode((int) $userId, 'user');

        if ($requiresVerification) {
            try {
                $this->verificationService->sendVerificationEmail((int) $userId, $context);
            } catch (\Throwable $exception) {
                log_message('error', 'Failed to send verification email: ' . $exception->getMessage());
            }
        } else {
            try {
                $this->emailService->queueTemplate('account-approved', (string) $user->email, [
                    'subject' => lang('Email.accountApproved.subject'),
                    'display_name' => $user->getDisplayName(),
                    'login_link' => $this->buildLoginUrl(),
                ]);
            } catch (\Throwable $exception) {
                log_message('error', 'Failed to queue approval email: ' . $exception->getMessage());
            }
        }

        return $user;
    }
}
