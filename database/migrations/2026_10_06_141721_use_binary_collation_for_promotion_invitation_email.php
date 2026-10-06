<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Invitation emails are stored trimmed and lowercase, so they must be compared byte for byte. MySQL's
     * default collation (utf8mb4_unicode_ci) ignores accents and character width, which would let an
     * invitation for jose@corp.com match a user registered as josé@corp.com. A binary collation makes the
     * equality exact. MODIFY COLUMN keeps the unique (promotion_id, email) and email indexes. SQLite and
     * PostgreSQL already compare strings exactly, so they are left alone.
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE promotion_invitations MODIFY COLUMN email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
    }
};
