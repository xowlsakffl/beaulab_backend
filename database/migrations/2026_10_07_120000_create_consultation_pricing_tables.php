<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_pricing_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 100)->unique();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });
        Schema::create('consultation_pricing_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scope_id')->constrained('consultation_pricing_scopes')->restrictOnDelete();
            $table->string('mode', 20);
            $table->json('ranges')->nullable();
            $table->unsignedInteger('fixed_price')->nullable();
            $table->dateTime('effective_at', 6);
            $table->dateTime('cancelled_at', 6)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('account_staffs')->nullOnDelete();
            $table->timestamps();
            $table->index(['scope_id', 'cancelled_at', 'effective_at'], 'consultation_pricing_effective_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_pricing_versions');
        Schema::dropIfExists('consultation_pricing_scopes');
    }
};
