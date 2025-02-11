<?php
// app/Providers/FirebaseServiceProvider.php
namespace App\Providers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function ($app) {
            $serviceAccount = ServiceAccount::fromJsonFile(
                storage_path('firebase/firebase_credentials.json')
            );
            
            $firebase = (new Factory)->withServiceAccount($serviceAccount);
            return $firebase->createAuth();
        });
    }

    public function boot()
    {
        //
    }
}
