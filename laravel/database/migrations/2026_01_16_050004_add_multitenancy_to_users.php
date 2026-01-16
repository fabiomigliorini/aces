<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona organization_id na tabela users
        Schema::table("users", function (Blueprint $table) {
            $table->foreignId("organization_id")->nullable()->after("id")->constrained()->nullOnDelete();
        });

        // Pivot: user <-> tenant com role
        Schema::create("tenant_user", function (Blueprint $table) {
            $table->id();
            $table->foreignId("tenant_id")->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->foreignId("role_id")->constrained()->cascadeOnDelete();
            $table->boolean("is_default")->default(false);
            $table->timestamps();

            $table->unique(["tenant_id", "user_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tenant_user");
        
        Schema::table("users", function (Blueprint $table) {
            $table->dropConstrainedForeignId("organization_id");
        });
    }
};