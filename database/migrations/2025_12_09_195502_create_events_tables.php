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
            $table->timestamps();
        });

        // events
        // ##########################################################
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('message')->nullable();
            $table->timestamp('date')->useCurrent();
            $table->unsignedBigInteger('severity_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('screen_id')->nullable();
            $table->uuid('related_content_id')->nullable();
            $table->timestamps();

            $table->foreign('severity_id')->references('id')->on('severity_types')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('event_types')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('set null');
            $table->foreign('related_content_id')->references('id')->on('content_objects')->onDelete('set null');
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
        Schema::dropIfExists('severity_types');
    }
}
