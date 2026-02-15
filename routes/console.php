<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $affected = \App\Models\ContentObject::where('status_id', 2)
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now('1'))
        ->update(['status_id' => 3, 'archived_at' => date('Y-m-d H:i:s')]);

    if ($affected > 0) {
        Log::info("Scheduler: {$affected} content objects have been automatically archived.");
    }
})->everyMinute();