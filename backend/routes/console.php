<?php

use App\Services\ArticleWorkflowService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('articles:publish-scheduled', function (ArticleWorkflowService $workflow) {
    $count = $workflow->publishDueScheduled();
    $this->info("{$count} matéria(s) agendada(s) publicada(s).");
})->purpose('Publica matérias agendadas cujo horário já chegou');

Schedule::command('articles:publish-scheduled')->everyMinute()->withoutOverlapping();
