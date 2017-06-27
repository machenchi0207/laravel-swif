<?php

namespace Macc\Laravel\SWIF;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;

class SWIFServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'swif.php' => app()->path().'/config/'.('swif.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('macc.swif.filter', function ($app) {
            $memcache = app()->make('memcached.connector')->getMemcached();
            $wordsAdapter = new \SIWF\Words\FileWordsAdapter(config('swif.blacklist.path'));
            $builder = new \SIWF\Tree\Builder($wordsAdapter);
            $storage = new \SIWF\Storage\MemcacheStorageAdapter($builder,$memcache);
            $resStorage = new \SIWF\Filter\Result\MemcacheAdapter($memcache);
//update words list
            if(file_exists(config('swif.blacklist.path')))
            {
                $mtime = filemtime(config('swif.blacklist.path'));

                if($mtime - $memcache->get('siwf_blacklist_create_time') >0)
                {
                    $memcache->set('siwf_blacklist_create_time',time());
                    $storage->clear();
                    $resStorage->clear();
                }
            }


//$filter = new \SIWF\Filter\Filter($storage);
            $filter = new \SIWF\Filter\CachedFilter($storage,$resStorage);

            return $filter;
        });
    }
}
