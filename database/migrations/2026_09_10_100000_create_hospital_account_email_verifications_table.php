<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_account_email_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospital_account_invitation_id');
            $table->string('email', 254);
            $table->string('code_hash');
            $table->char('verification_token_hash', 64)->nullable()->unique('h_email_verify_token_unique');
            $table->unsignedTinyInteger('failed_attempt_count')->default(0);
            $table->timestamp('code_expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
            $table->foreign('hospital_account_invitation_id', 'h_email_verify_invite_fk')
                ->references('id')->on('hospital_account_invitations')->cascadeOnDelete();
            $table->index(['hospital_account_invitation_id', 'id'], 'h_email_verify_invite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_account_email_verifications');
    }
};
