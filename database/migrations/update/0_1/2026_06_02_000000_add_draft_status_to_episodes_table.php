<?php

declare(strict_types=1);

use App\Enums\Person\EpisodeStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $values = implode("', '", EpisodeStatus::values());

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE episodes DROP CONSTRAINT IF EXISTS episodes_status_check");
            DB::statement("ALTER TABLE episodes ADD CONSTRAINT episodes_status_check CHECK (status IN ('$values'))");
        } else {
            DB::statement("ALTER TABLE episodes MODIFY status ENUM('$values') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('episodes')
            ->where('status', EpisodeStatus::DRAFT->value)
            ->update(['status' => EpisodeStatus::ACTIVE->value]);

        $values = implode("', '", array_filter(
            EpisodeStatus::values(),
            static fn (string $value) => $value !== EpisodeStatus::DRAFT->value
        ));

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE episodes DROP CONSTRAINT IF EXISTS episodes_status_check");
            DB::statement("ALTER TABLE episodes ADD CONSTRAINT episodes_status_check CHECK (status IN ('$values'))");
        } else {
            DB::statement("ALTER TABLE episodes MODIFY status ENUM('$values') NOT NULL");
        }
    }
};
