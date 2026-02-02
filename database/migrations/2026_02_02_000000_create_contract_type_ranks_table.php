<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_types')) {
            Schema::create('contract_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('contract_type_ranks')) {
            Schema::create('contract_type_ranks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->decimal('from', 15, 2)->nullable();
                $table->decimal('to', 15, 2)->nullable();
                $table->decimal('percent', 8, 2)->default(0);
                $table->unsignedBigInteger('contract_type_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('contract_type_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_type_ranks');
        Schema::dropIfExists('contract_types');
    }
};
