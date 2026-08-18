<?php
namespace App\Providers;
use App\Domain\Workflow\ActionRegistry;use App\Domain\Workflow\Handlers\{ConditionalAction,DelayAction,EmailNotificationAction,HttpRequestAction,OutboundWebhookAction,StoreValueAction,TransformAction};use Illuminate\Support\ServiceProvider;
final class AppServiceProvider extends ServiceProvider
{
 public function register():void{$this->app->singleton(ActionRegistry::class,function($app){return new ActionRegistry([$app->make(TransformAction::class),$app->make(StoreValueAction::class),$app->make(DelayAction::class),$app->make(ConditionalAction::class),$app->make(HttpRequestAction::class),$app->make(OutboundWebhookAction::class),$app->make(EmailNotificationAction::class)]);});}
 public function boot():void{}
}
