<?php
namespace App\Domain\Workflow;
enum WorkflowExecutionStatus:string {case Pending='pending';case Running='running';case Succeeded='succeeded';case Failed='failed';case Cancelled='cancelled';}
