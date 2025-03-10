<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\MessageSent;
use App\Listeners\MessageSentListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function register()
    {
        // Register the event and listener in the register method
        Event::listen(
            MessageSent::class,
            MessageSentListener::class
        );
    }
}
