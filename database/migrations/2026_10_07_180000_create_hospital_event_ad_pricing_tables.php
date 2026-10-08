<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_event_ad_pricing_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 30)->unique();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });
        Schema::create('hospital_event_ad_pricing_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('hospital_event_ad_pricing_groups')->restrictOnDelete();
            $table->json('prices');
            $table->dateTime('effective_at', 6);
            $table->dateTime('cancelled_at', 6)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->timestamps();
            $table->index(['group_id', 'cancelled_at', 'effective_at'], 'ad_pricing_effective_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_event_ad_pricing_versions');
        Schema::dropIfExists('hospital_event_ad_pricing_groups');
    }
};
