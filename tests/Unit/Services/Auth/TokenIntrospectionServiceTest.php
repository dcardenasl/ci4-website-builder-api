<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use App\DTO\Request\Auth\IntrospectRequestDTO;
use App\Interfaces\Tokens\JwtServiceInterface;
use App\Interfaces\Tokens\TokenRevocationServiceInterface;
use App\Models\UserModel;
use App\Services\Auth\TokenIntrospectionService;
use App\Services\Iam\EffectivePermissionsResolver;
use CodeIgniter\Test\CIUnitTestCase;

final class TokenIntrospectionServiceTest extends CIUnitTestCase
{
    private JwtServiceInterface $jwtService;
    private TokenRevocationServiceInterface $tokenRevocationService;
    private EffectivePermissionsResolver $effectivePermissionsResolver;
    private UserModel $userModel;
    private TokenIntrospectionService $service;
    private bool $userExists = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = $this->createMock(JwtServiceInterface::class);
        $this->tokenRevocationService = $this->createMock(TokenRevocationServiceInterface::class);
        $this->effectivePermissionsResolver = $this->createMock(EffectivePermissionsResolver::class);
        $this->userModel = $this->createMock(UserModel::class);
        $this->userModel->method('find')->willReturnCallback(
            fn (): ?object => $this->userExists ? (object) ['auth_token_version' => 0] : null
        );
        $this->service = new TokenIntrospectionService(
            $this->jwtService,
            $this->tokenRevocationService,
            $this->effectivePermissionsResolver,
            $this->userModel
        );
    }

    public function testIntrospectRejectsTokenWithStaleSessionVersion(): void
    {
        $this->jwtService->method('decode')->willReturn((object) [
            'jti' => 'stale-jti',
            'uid' => 7,
            'token_version' => 1,
        ]);
        $this->tokenRevocationService->method('isRevoked')->willReturn(false);

        $result = $this->service->introspect($this->request('stale-token'));

        $this->assertFalse($result->valid);
        $this->assertSame('revoked', $result->error);
    }

    public function testIntrospectRejectsUserTokenWhenUserNoLongerExists(): void
    {
        $this->jwtService->method('decode')->willReturn((object) [
            'jti' => 'orphan-jti',
            'uid' => 7,
            'token_version' => 0,
        ]);
        $this->tokenRevocationService->method('isRevoked')->willReturn(false);
        $this->userExists = false;

        $result = $this->service->introspect($this->request('orphan-token'));

        $this->assertFalse($result->valid);
        $this->assertSame('invalid_or_expired', $result->error);
    }

    private function request(string $token): IntrospectRequestDTO
    {
        /** @var IntrospectRequestDTO */
        return \Config\Services::requestDtoFactory()->make(IntrospectRequestDTO::class, ['token' => $token]);
    }
}
