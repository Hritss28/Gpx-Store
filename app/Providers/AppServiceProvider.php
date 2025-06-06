<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\Category;
use App\Models\Order; 
use App\Observers\OrderObserver; 

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Order::observe(OrderObserver::class);
        // Check if Filament exists and is properly loaded
        if (class_exists(\Filament\Facades\Filament::class)) {
            \Filament\Facades\Filament::serving(function () {
                // Safe customizations for Filament v2.x
                if (method_exists(\Filament\Facades\Filament::class, 'registerNavigationGroups')) {
                    \Filament\Facades\Filament::registerNavigationGroups([
                        'Shop',
                        'Customer Service',
                        'Settings',
                    ]);
                }
            });
        }
        // Global View Composer untuk kategori
        View::composer(['app', 'home', 'partials.header', 'partials.footer'], function ($view) {
            $categories = Cache::remember('all_categories', 60*60*24, function () {
                return Category::all();
            });
            
            $view->with('categories', $categories);
        });
        View::composer(['*'], function ($view) {
            $view->with('brandLookupCache', function ($brandId) {
                return Cache::remember('brand_'.$brandId, 60*60*24, function () use ($brandId) {
                    return \App\Models\Brand::find($brandId);
                });
            });
        });
        
        // google config
        Config::set('services.google', [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI', 'http://127.0.0.1:8000/auth/google/callback'),
        ]);

         // Force HTTPS on production
         if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        
        // Config GitHub OAuth
        Config::set('services.github', [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('GITHUB_REDIRECT'),
        ]);
    }
}