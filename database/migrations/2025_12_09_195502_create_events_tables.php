<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTables extends Migration
{
    public function up() : void
    {
        // severity_types
        // ##########################################################
        Schema::create('severity_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        // event_types
        // ##########################################################
        Schema::create('event_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique(); // content_added, tv_online, layout_changed...
            $table->unsignedBigInteger('severity')->nullable();
            $table->timestamps();

            $table->foreign('severity')->references('id')->on('severity_types')->onDelete('set null');
        });

        // events
        // ##########################################################
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('type')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('screen_id')->nullable();
            $table->uuid('related_content')->nullable();
            $table->timestamps();

            $table->foreign('type')->references('id')->on('event_types')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('set null');
            $table->foreign('related_content')->references('id')->on('content_objects')->onDelete('set null');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
        Schema::dropIfExists('severity_types');
    }
}
