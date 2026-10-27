<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:prune-private-data')->dailyAt('02:30')->withoutOverlapping();
