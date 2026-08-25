<?php

declare(strict_types=1);

namespace Tests\Integration\Services\Iam;

use App\Models\UserModel;
use Config\Services;
use Tests\Support\IntegrationTestCase;

final class UserRoleAssignmentServiceTest extends IntegrationTestCase
{
    protected $seed = \App\Database\Seeds\RbacBootstrapSeeder::class;

    public function testCustomProfileComposesTheBaselineUserRole(): void
    {
        $db = db_connect();
        $userId = (int) (new UserModel())->insert([
            'email' => 'role-compose-' . uniqid('', true) . '@example.com',
            'password' => password_hash('Pass123!', PASSWORD_BCRYPT),
            'status' => 'active',
        ]);
        $roleCode = 'custom-profile-' . uniqid('', true);
        $db->table('roles')->insert([
            'code' => $roleCode,
            'name' => 'Custom profile',
            'description' => 'Test custom profile',
            'is_system' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $customRoleId = (int) $db->insertID();

        Services::userRoleAssignmentService(false)->syncRoles($userId, [$customRoleId]);

        $codes = array_map(
            static fn (array $row): string => (string) $row['code'],
            $db->table('user_roles ur')
                ->select('r.code')
                ->join('roles r', 'r.id = ur.role_id')
                ->where('ur.user_id', $userId)
                ->get()
                ->getResultArray()
        );

        $this->assertEqualsCanonicalizing(['user', $roleCode], $codes);
    }
}
