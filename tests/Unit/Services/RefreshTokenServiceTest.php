<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Request\Identity\RefreshTokenRequestDTO;
use App\Enums\RefreshTokenRevocationReason;
use App\Interfaces\Tokens\JwtServiceInterface;
use App\Interfaces\Tokens\TokenVersionServiceInterface;
use App\Models\RefreshTokenModel;
use App\Models\UserModel;
use App\Services\Tokens\RefreshTokenService;
use CodeIgniter\Test\CIUnitTestCase;
use dcardenasl\Ci4ApiCore\Exceptions\AuthenticationException;
use dcardenasl\Ci4ApiCore\Services\AuditServiceInterface;
use Tests\Support\Traits\CustomAssertionsTrait;

/**
 * RefreshTokenService Unit Tests
 *
 * Tests token lifecycle with mocked dependencies.
 */
class RefreshTokenServiceTest extends CIUnitTestCase
{
    use CustomAssertionsTrait;

    private const VALID_REFRESH_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const UNKNOWN_REFRESH_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    protected RefreshTokenService $service;
    protected RefreshTokenModel $mockRefreshTokenModel;
    protected JwtServiceInterface $mockJwtService;
    protected UserModel $mockUserModel;
    protected \App\Services\Users\UserAccountGuard $mockUserAccountGuard;
    protected \App\Services\Iam\EffectivePermissionsResolver $mockPermissionsResolver;
    protected AuditServiceInterface $mockAuditService;
    protected TokenVersionServiceInterface $mockTokenVersionService;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('JWT_REFRESH_TOKEN_TTL=604800');

        $this->mockRefreshTokenModel = $this->createMock(RefreshTokenModel::class);
        $this->mockJwtService = $this->createMock(JwtServiceInterface::class);
        $this->mockUserModel = $this->createMock(UserModel::class);

        $this->mockUserAccountGuard = $this->createMock(\App\Services\Users\UserAccountGuard::class);
        $this->mockPermissionsResolver = $this->createMock(\App\Services\Iam\EffectivePermissionsResolver::class);
        $this->mockAuditService = $this->createMock(AuditServiceInterface::class);
        $this->mockTokenVersionService = $this->createMock(TokenVersionServiceInterface::class);

        $this->service = new RefreshTokenService(
            $this->mockRefreshTokenModel,
            $this->mockJwtService,
            $this->mockUserModel,
            $this->mockUserAccountGuard,
            $this->mockPermissionsResolver,
            $this->mockAuditService,
            $this->mockTokenVersionService
        );
    }

    protected function tearDown(): void
    {
        putenv('JWT_REFRESH_TOKEN_TTL');
        parent::tearDown();
    }

    // ==================== ISSUE REFRESH TOKEN TESTS ====================

    public function testIssueRefreshTokenReturnsTokenString(): void
    {
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function ($data) {
                return isset($data['user_id'])
                    && $data['user_id'] === 1
                    && isset($data['token'])
                    && strlen($data['token']) === 64  // 32 bytes = 64 hex chars
                    && isset($data['family_id'])
                    && strlen($data['family_id']) === 32
                    && $data['parent_id'] === null
                    && isset($data['expires_at']);
            }))
            ->willReturn(1);

        $token = $this->service->issueRefreshToken(1);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testIssueRefreshTokenGeneratesUniqueTokens(): void
    {
        $this->mockRefreshTokenModel
            ->method('insert')
            ->willReturn(1);

        $token1 = $this->service->issueRefreshToken(1);
        $token2 = $this->service->issueRefreshToken(1);

        $this->assertNotEquals($token1, $token2);
    }

    // ==================== REVOKE TESTS ====================

    public function testRevokeWithValidTokenReturnsSuccess(): void
    {
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('revokeToken')
            ->with(self::VALID_REFRESH_TOKEN)
            ->willReturn(true);

        $result = $this->service->revoke(new RefreshTokenRequestDTO([
            'refresh_token' => self::VALID_REFRESH_TOKEN,
        ], service('validation')));

        $this->assertSame(\dcardenasl\Ci4ApiCore\Support\OperationState::SUCCESS, $result->state);
    }

    public function testRevokeWithNonExistentTokenThrowsNotFoundException(): void
    {
        $this->mockRefreshTokenModel
            ->method('revokeToken')
            ->willReturn(false);

        $this->expectException(\dcardenasl\Ci4ApiCore\Exceptions\NotFoundException::class);

        $this->service->revoke(new RefreshTokenRequestDTO([
            'refresh_token' => self::UNKNOWN_REFRESH_TOKEN,
        ], service('validation')));
    }

    // ==================== REVOKE ALL USER TOKENS TESTS ====================

    public function testRevokeAllUserTokensCallsModel(): void
    {
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('revokeAllUserTokens')
            ->with(1, RefreshTokenRevocationReason::RevokeAll);
        $this->mockTokenVersionService
            ->expects($this->once())
            ->method('increment')
            ->with(1)
            ->willReturn(1);

        $result = $this->service->revokeAllUserTokens(1);

        $this->assertSame(\dcardenasl\Ci4ApiCore\Support\OperationState::SUCCESS, $result->state);
    }

    public function testRefreshRotatesWithinSameFamilyAndCarriesTokenVersion(): void
    {
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('findForUpdate')
            ->willReturn((object) [
                'id' => 11,
                'user_id' => 7,
                'family_id' => '11111111111111111111111111111111',
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'revoked_at' => null,
            ]);
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('revokeToken')
            ->with(self::VALID_REFRESH_TOKEN, RefreshTokenRevocationReason::Rotated)
            ->willReturn(true);
        $this->mockRefreshTokenModel->method('insert')->willReturn(12);
        $this->mockUserModel->method('find')->willReturn(new \App\Entities\UserEntity([
            'id' => 7,
            'email' => 'refresh@example.com',
            'status' => 'active',
            'auth_token_version' => 3,
        ]));
        $this->mockPermissionsResolver->method('resolveAll')->willReturn(['files.read']);
        $this->mockJwtService
            ->expects($this->once())
            ->method('encode')
            ->with(7, ['files.read'], 3)
            ->willReturn('access-token');

        $result = $this->service->refreshAccessToken(new RefreshTokenRequestDTO([
            'refresh_token' => self::VALID_REFRESH_TOKEN,
        ], service('validation')));

        $this->assertSame('access-token', $result->access_token);
        $this->assertNotSame(self::VALID_REFRESH_TOKEN, $result->refresh_token);
    }

    public function testReplayedRotatedTokenRevokesUserSessionAndFails(): void
    {
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('findForUpdate')
            ->willReturn((object) [
                'id' => 11,
                'user_id' => 7,
                'family_id' => '11111111111111111111111111111111',
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'revoked_at' => date('Y-m-d H:i:s'),
                'revoked_reason' => RefreshTokenRevocationReason::Rotated->value,
            ]);
        $this->mockRefreshTokenModel
            ->expects($this->once())
            ->method('revokeAllUserTokens')
            ->with(7, RefreshTokenRevocationReason::ReuseDetected);
        $this->mockTokenVersionService
            ->expects($this->once())
            ->method('increment')
            ->with(7)
            ->willReturn(4);
        $this->mockAuditService->expects($this->once())->method('log');

        $this->expectException(AuthenticationException::class);
        $this->service->refreshAccessToken(new RefreshTokenRequestDTO([
            'refresh_token' => self::VALID_REFRESH_TOKEN,
        ], service('validation')));
    }
}
