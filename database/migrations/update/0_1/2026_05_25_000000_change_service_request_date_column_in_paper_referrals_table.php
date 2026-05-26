<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE paper_referrals
            ALTER COLUMN service_request_date
            TYPE timestamp(0) WITHOUT TIME ZONE
            USING service_request_date::timestamp(0) WITHOUT TIME ZONE
        ');

        DB::statement('ALTER TABLE paper_referrals ALTER COLUMN service_request_date DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE paper_referrals
            ALTER COLUMN service_request_date
            TYPE varchar(255)
            USING service_request_date::varchar
        ');
    }
};
