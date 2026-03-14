<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Create the core event/audit tables used by the event stream UI.
 */
class CreateEventsTables extends Migration
{
    /**
     * Create severity, type, and event tables with optional foreign keys to audited models.
     */
    public function up() : void
    {
        // Severity buckets drive styling and filtering for event rows.
        Schema::create('severity_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
        });

        // Event types are stable identifiers such as `content_created` or `screen_offline`.
        Schema::create('event_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->unsignedBigInteger('severity')->nullable();

            $table->foreign('severity')->references('id')->on('severity_types')->onDelete('set null');
        });

        // Each row stores one emitted event with optional pointers to related domain records.
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('screen_id')->nullable();
            $table->uuid('related_content')->nullable();
            $table->uuid('related_profile')->nullable();
            $table->uuid('related_module')->nullable();
            $table->unsignedBigInteger('related_role')->nullable();
            $table->unsignedBigInteger('related_tag')->nullable();
            $table->unsignedBigInteger('related_content_type')->nullable();
            $table->unsignedBigInteger('related_module_category')->nullable();
            $table->unsignedBigInteger('related_page')->nullable();
            $table->unsignedBigInteger('related_page_module')->nullable();
            $table->uuid('related_user')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->foreign('type_id')->references('id')->on('event_types')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('screen_id')->references('id')->on('screens')->onDelete('set null');
            $table->foreign('related_content')->references('id')->on('content_objects')->onDelete('set null');
            $table->foreign('related_profile')->references('id')->on('profiles')->onDelete('set null');
            $table->foreign('related_module')->references('id')->on('modules')->onDelete('set null');
            $table->foreign('related_role')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('related_tag')->references('id')->on('tags')->onDelete('set null');
            $table->foreign('related_content_type')->references('id')->on('content_types')->onDelete('set null');
            $table->foreign('related_module_category')->references('id')->on('module_categories')->onDelete('set null');
            $table->foreign('related_page')->references('id')->on('pages')->onDelete('set null');
            $table->foreign('related_page_module')->references('id')->on('page_modules')->onDelete('set null');
            $table->foreign('related_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Drop event tables in reverse dependency order.
     */
    public function down() : void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_types');
        Schema::dropIfExists('severity_types');
    }
}
