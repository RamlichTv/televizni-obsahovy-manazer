<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentTables extends Migration
{
    public function up() : void
    {
        // CONTENT_STATUSES
        // ##########################################################
        Schema::create('content_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
        });


        // CONTENT_TYPES
        // ##########################################################
        Schema::create('content_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->boolean('is_file')->default(false);
            $table->json('extension_whitelist')->nullable();
            $table->bigInteger('max_size')->nullable(); // MB
            $table->timestamps();
        });

        // TAGS
        // ##########################################################
        Schema::create('tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        // CONTENT_OBJECTS
        // ##########################################################
        Schema::create('content_objects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('type_id')->nullable(); // fk -> content_types
            $table->uuid('author_id')->nullable(); // fk -> users
            $table->text('description')->nullable();
            $table->integer('priority')->default(0);
            $table->unsignedInteger('status_id')->nullable(); // fk -> content_statuses
            $table->json('meta')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('status_id')->references('id')->on('content_statuses')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('content_types')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
        });

        // content_meta (multiple meta rows per content_object if needed)
        // ##########################################################
        Schema::create('content_meta', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('content_object_id');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('content_object_id')->references('id')->on('content_objects')->onDelete('cascade');
            $table->unique(['content_object_id','key']);
        });

        // content_tags
        // ##########################################################
        Schema::create('content_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('content_object_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['content_object_id','tag_id']);
            $table->foreign('content_object_id')->references('id')->on('content_objects')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('content_statuses');
        Schema::dropIfExists('content_tags');
        Schema::dropIfExists('content_meta');
        Schema::dropIfExists('content_objects');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('content_types');
    }
}
