<?php
use App\Jobs\{DispatchDueSchedules,DispatchDueRetrySteps,RecoverStalledSteps};use Illuminate\Support\Facades\Schedule;
Schedule::job(new DispatchDueSchedules)->everyMinute()->withoutOverlapping();
Schedule::job(new DispatchDueRetrySteps)->everyMinute()->withoutOverlapping();
Schedule::job(new RecoverStalledSteps)->everyFiveMinutes()->withoutOverlapping();

Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();
