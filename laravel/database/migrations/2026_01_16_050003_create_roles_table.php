<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("roles", function (Blueprint $table) {
            $table->id();
            $table->foreignId("organization_id")->constrained()->cascadeOnDelete();
            $table->string("name");
            $table->string("slug");
            $table->json("permissions")->nullable();
            $table->boolean("is_admin")->default(false);
            $table->timestamps();

            $table->unique(["organization_id", "slug"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("roles");
    }
};