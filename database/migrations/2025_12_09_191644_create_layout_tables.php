<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLayoutTables extends Migration
{
    public function up() : void
    {
        // PROFILES - layout definitions; admin creates profile -> URL/page set
        // ##########################################################
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('version')->nullable();
            $table->json('settings')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // MODULE_CATEGORIES - categories for blade components
        // ##########################################################
        Schema::create('module_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

        // MODULES - catalog of blade components
        // ##########################################################
        Schema::create('modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('component_name');
            $table->json('config_schema')->nullable();
            $table->uuid('created_by')->nullable();
            $table->bigInteger('category');
            $table->string('thumbnail_url')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('category')->references('id')->on('module_categories')->onDelete('set null');
        });

        // PAGES - pages/slides belonging to profiles
        // ##########################################################
        Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('profile_id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->integer('row_count')->default(1);
            $table->integer('column_count')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('duration')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
        });

        // page_rows - per-page row sizes
        // ##########################################################
        Schema::create('page_rows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('page_id')->unsigned();
            $table->integer('index');
            $table->string('height')->nullable(); // "100px", "20%", "auto"
            $table->string('min_height')->nullable();
            $table->string('max_height')->nullable();
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->unique(['page_id','index']);
        });

        // page_columns - per-page column sizes
        // ##########################################################
        Schema::create('page_columns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('page_id')->unsigned();
            $table->integer('index');
            $table->string('width')->nullable(); // "1fr", "100px", "25%"
            $table->string('min_width')->nullable();
            $table->string('max_width')->nullable();
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->unique(['page_id','index']);
        });

        // page_modules - placements of modules into page-grid
        // ##########################################################
        Schema::create('page_modules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('page_id')->unsigned();
            $table->unsignedBigInteger('module_id');
            $table->integer('row_index')->unsigned();
            $table->integer('column_index')->unsigned();
            $table->integer('row_span')->unsigned()->default(1);
            $table->integer('column_span')->unsigned()->default(1);
            $table->json('config_overrides')->nullable();
            $table->integer('order')->default(0); // in case same cell has stacking behaviour
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('page_modules');
        Schema::dropIfExists('page_columns');
        Schema::dropIfExists('page_rows');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('profiles');
    }
}
