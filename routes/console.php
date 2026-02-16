<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content:archive', function () {
    $archived = \App\Models\ContentObject::where('status_id', 2)
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', date('Y-m-d H:i:s'))
        ->update(['status_id' => 3, 'archived_at' => date('Y-m-d H:i:s')]);

    if ($archived > 0) {
        Log::info("Scheduler: {$archived} content objects have been automatically archived.");
    }
})->purpose('Archives all expired content objects');

Schedule::call(function () {
    Artisan::call('content:archive');
})->everyMinute();