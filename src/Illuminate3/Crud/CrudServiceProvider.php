<?php

namespace Illuminate3\Crud;

use Illuminate\Support\ServiceProvider;
use Route, Config;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->package('crud', 'crud');

        $this->app->register('Illuminate3\Form\FormServiceProvider');
        $this->app->register('Illuminate3\Model\ModelServiceProvider');
        $this->app->register('Illuminate3\Overview\OverviewServiceProvider');
    }

    public function boot()
    {
        Route::get('admin/crud',                  	array('as' => 'admin.crud.index', 'uses' => 'Illuminate3\Crud\ManagerController@index'));
        Route::get('admin/crud/scan',             	array('as' => 'admin.crud.scan', 'uses' => 'Illuminate3\Crud\ManagerController@scan'));
        Route::get('admin/crud/manage/{class}',   	array('as' => 'admin.crud.manage', 'uses' => 'Illuminate3\Crud\ManagerController@manage'))->where('class', '(.*)');
        Route::get('admin/crud/create',          	array('as' => 'admin.crud.create', 'uses' => 'Illuminate3\Crud\ManagerController@create'));
        Route::post('admin/crud/create',          	array('as' => 'admin.crud.store', 'uses' => 'Illuminate3\Crud\ManagerController@store'));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
    }

}