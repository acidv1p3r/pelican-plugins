<?php

namespace JoanFo\StarryNight\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class StarryNightPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->ensureCssPublished();
    }

    public function boot(): void
    {
        $source = __DIR__.'/../../css/starry-night.css';
        $destination = public_path('plugins/starrynight/css/starry-night.css');

        $this->publishes([
            $source => $destination,
        ], 'starrynight-assets');
        $this->ensureCssPublished();
    }

    private function ensureCssPublished(): void
    {
        $source = __DIR__.'/../../css/starry-night.css';
        $destination = public_path('plugins/starrynight/css/starry-night.css');

        if (! File::exists($destination) && File::exists($source)) {
            $dir = dirname($destination);
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            File::copy($source, $destination);
        }
    }
}
