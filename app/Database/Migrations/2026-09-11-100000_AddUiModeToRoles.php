<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Enums\UiMode;
use CodeIgniter\Database\Migration;

/** Adds the presentation-only mode consumed by administration panels. */
final class AddUiModeToRoles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles') || $this->db->fieldExists('ui_mode', 'roles')) {
            return;
        }

        $this->forge->addColumn('roles', [
            'ui_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => UiMode::Full->value,
                'null'       => false,
                'after'      => 'is_system',
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->tableExists('roles') && $this->db->fieldExists('ui_mode', 'roles')) {
            $this->forge->dropColumn('roles', 'ui_mode');
        }
    }
}
