<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScreensAndTypesTables extends Migration
{
    public function up() : void
    {
        // status_types
        // ##########################################################
        Schema::create('status_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        // orientation_types
        // ##########################################################
        Schema::create('orientation_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        // buildings
        // ##########################################################
        Schema::create('buildings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // rooms
        // ##########################################################
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('name');
            $table->string('floor')->nullable();
            $table->timestamps();

            $table->foreign('building_id')->references('id')->on('buildings')->onDelete('set null');
        });

        // SCREENS
        // ##########################################################
        Schema::create('screens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('height')->nullable();
            $table->integer('width')->nullable();
            $table->uuid('profile_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('orientation_id')->nullable();
            $table->string('hardware_id')->nullable()->unique();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_interactive')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('auto_refresh_interval')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('status_types')->onDelete('set null');
            $table->foreign('orientation_id')->references('id')->on('orientation_types')->onDelete('set null');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('set null');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('screens');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('buildings');
        Schema::dropIfExists('orientation_types');
        Schema::dropIfExists('status_types');
    }
}
