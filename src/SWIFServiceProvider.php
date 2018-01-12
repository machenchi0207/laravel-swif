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
//update words list
            if(file_exists(config('swif.blacklist.path')))
            {
                $cache = app('cache');
                $memcached = $cache->store('memcached')->getStore()->getMemcached();
                $wordsAdapter = new \SIWF\Words\FileWordsAdapter(config('swif.blacklist.path'));
                $builder = new \SIWF\Tree\Builder($wordsAdapter);
                $storage = new \SIWF\Storage\MemcachedStorageAdapter($builder,$memcached);
                $resStorage = new \SIWF\Filter\Result\MemcachedAdapter($memcached);
                $mtime = filemtime(config('swif.blacklist.path'));

                if($mtime - $memcached->get('siwf_blacklist_create_time') >0)
                {
                    $memcached->set('siwf_blacklist_create_time',time());
                    $storage->clear();
                    $resStorage->clear();
                }
            }else{
                throw new \Exception('Not found black list dic file!');
            }


//$filter = new \SIWF\Filter\Filter($storage);
            $filter = new \SIWF\Filter\CachedFilter($storage,$resStorage);

            return $filter;
        });
    }
}
