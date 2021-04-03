<?php

namespace Dadsun\Crawler;


use Illuminate\Support\ServiceProvider;


class CrawlerServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/crawler.php','crawler');
    }

    public function boot()
    {
        $this->loadDependencies()
            ->publishDependencies();
    }

    private function loadDependencies()
    {
        return $this;
    }

    private function publishDependencies(){

    }
}
