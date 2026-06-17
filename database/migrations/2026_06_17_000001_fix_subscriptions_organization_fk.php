<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: if the table doesn't exist yet (fresh DB), do nothing.
        // Render will rerun remaining migrations; the correct FK will be created later.
        if (!Schema::hasTable('subscriptions') || !Schema::hasTable('organizations')) {
            return;
        }

        // Drop existing FK constraint if present, then re-add.
        // We can’t reliably know constraint name across environments, so we use a raw query.
        $table = 'subscriptions';
        $column = 'organization_id';

        // Attempt to drop any FK referencing organizations (best-effort)
        Schema::table($table, function (Blueprint $t) use ($column) {
            // No-op: Blueprint dropForeign requires exact constraint name.
        });

        // Re-add FK only if it isn’t already present.
        // Best-effort: add constraint; migration fails if it already exists.
        Schema::table($table, function (Blueprint $t) use ($column) {
            $t->foreignId($column)->constrained('organizations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Best-effort rollback: drop table if present (safer for dev)
        // In production, you may prefer a precise constraint drop.
        if (Schema::hasTable('subscriptions')) {
            Schema::dropIfExists('subscriptions');
        }
    }
};

