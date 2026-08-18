<?php
namespace App\Domain\Workflow;
enum StepExecutionStatus:string {case Pending='pending';case Running='running';case RetryWait='retry_wait';case Succeeded='succeeded';case Failed='failed';case Skipped='skipped';}
