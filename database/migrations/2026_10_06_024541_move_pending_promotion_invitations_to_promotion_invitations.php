<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Pending invitations used to be promotion_user rows with status `invited`. Invitations now belong to an
     * email address, so each pending row becomes a promotion_invitations row keyed by the invited account's
     * email and the pivot row is deleted. The status is spelled out because the enum case no longer exists.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            while (($pending = $this->pendingBatch())->isNotEmpty()) {
                foreach ($pending as $row) {
                    $this->move($row);
                }
            }
        });
    }

    /** @return Collection<int, stdClass> */
    private function pendingBatch(): Collection
    {
        return DB::table('promotion_user')
            ->join('users', 'users.id', '=', 'promotion_user.user_id')
            ->where('promotion_user.status', 'invited')
            ->orderBy('promotion_user.promotion_id')
            ->orderBy('promotion_user.user_id')
            ->limit(500)
            ->get([
                'promotion_user.promotion_id',
                'promotion_user.user_id',
                'users.email',
                'promotion_user.role',
                'promotion_user.created_at',
                'promotion_user.updated_at',
            ]);
    }

    private function move(stdClass $row): void
    {
        $email = Str::lower(mb_trim($row->email));

        $alreadyInvited = DB::table('promotion_invitations')
            ->where('promotion_id', $row->promotion_id)
            ->where('email', $email)
            ->exists();

        if (! $alreadyInvited) {
            DB::table('promotion_invitations')->insert([
                'promotion_id' => $row->promotion_id,
                'email' => $email,
                'role' => $row->role,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        DB::table('promotion_user')
            ->where('promotion_id', $row->promotion_id)
            ->where('user_id', $row->user_id)
            ->where('status', 'invited')
            ->delete();
    }
};
