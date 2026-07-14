<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\UpdatePublisherMetricsJob;
use App\Models\User;




Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('uploads:prune')->daily();





Schedule::call(function () {
    User::whereHas('editorials')
        ->orWhereHas('books')
        ->chunk(100, function ($publishers) {
            foreach ($publishers as $publisher) {
                UpdatePublisherMetricsJob::dispatch($publisher->id);
            }
        });
})->weekly()->name('update-publisher-metrics');