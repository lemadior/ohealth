<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->foreignId('person_id')->after('uuid')->constrained('persons');
            $table->foreignId('specimen_id')->after('context_id')->nullable()->constrained('identifiers');
            $table->foreignId('device_id')->after('specimen_id')->nullable()->constrained('identifiers');
            $table->foreignId('based_on_id')->after('device_id')->nullable()->constrained('identifiers');
            $table->foreignId('encounter_id')->nullable()->change();
            $table->string('explanatory_letter')->after('based_on_id')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable()->after('route_id');
            $table->timestamp('ehealth_updated_at')->nullable()->after('ehealth_inserted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['based_on_id']);
            $table->dropForeign(['specimen_id']);
            $table->dropForeign(['device_id']);
            $table->dropColumn(['person_id', 'based_on_id', 'specimen_id', 'device_id', 'explanatory_letter', 'ehealth_inserted_at', 'ehealth_updated_at']);
            $table->foreignId('encounter_id')->nullable(false)->change();
        });
    }
};
