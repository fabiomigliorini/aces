<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("stocks", function (Blueprint $table) {
            $table->id();
            $table->foreignId("organization_id")->constrained()->cascadeOnDelete();
            $table->foreignId("tenant_id")->constrained()->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->integer("quantity")->default(0);
            $table->integer("min_quantity")->default(0);
            $table->timestamps();

            $table->unique(["tenant_id", "product_id"]);
            $table->index(["organization_id", "tenant_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("stocks");
    }
};