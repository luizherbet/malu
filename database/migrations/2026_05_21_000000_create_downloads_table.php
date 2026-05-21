<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('url');
            $table->string('status')->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('format')->default('mp4');
            $table->string('quality')->default('best');
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
