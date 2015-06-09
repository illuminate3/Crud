<?php

namespace Illuminate3\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate3\Form\FormBuilder;
use Illuminate3\Model\ModelBuilder;
use Illuminate3\Overview\OverviewBuilder;
use View,
    BaseController,
    Validator,
    Input,
	Request,
    Redirect,
	Response,
    Config,
    Session,
    Event;

abstract class CrudController extends BaseController
{
    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * @var OverviewBuilder
     */
    protected $overviewBuilder;

    /**
     * @var ModelBuilder
     */
    protected $modelBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $viewMode;

    /**
     *
     * @param FormBuilder     $fb
     * @param ModelBuilder 	  $mb
     * @param OverviewBuilder $ob
     */
    public function __construct(FormBuilder $fb, ModelBuilder $mb, OverviewBuilder $ob)
    {
        $this->formBuilder = $fb;
        $this->modelBuilder = $mb;
        $this->overviewBuilder = $ob;
    }

    /**
     * @param FormBuilder $fb
     */
    abstract public function buildForm(FormBuilder $fb);

    /**
     * @param OverviewBuilder $ob
     */
    abstract public function buildOverview(OverviewBuilder $ob);

    /**
     * @param OverviewBuilder $mb
     */
    abstract public function buildModel(ModelBuilder $mb);

    /**
     * 
     * @return ModelBuilder
     */
    public function getModelBuilder()
    {
        return $this->modelBuilder;
    }

    /**
     * 
     * @return FormBuilder
     */
    public function getFormBuilder()
    {
        return $this->formBuilder;
    }

    /**
     * 
     * @return OverviewBuilder
     */
    public function getOverviewBuilder()
    {
        return $this->overviewBuilder;
    }

    /**
     * Override this method to provide a custom config
     *
     * @return array
     */
    public function config()
    {
        return array();
    }

    /**
     *
     */
    public function buildConfig()
    {
        Config::set('crud::config.title', $this->getModelBuilder()->getName());
        Config::set('crud::config.redirects.success.store', $this->getBaseRoute() . '.index');
        Config::set('crud::config.redirects.success.update', $this->getBaseRoute() . '.index');
        Config::set('crud::config.redirects.success.destroy', $this->getBaseRoute() . '.index');
        Config::set('crud::config.redirects.error.store', $this->getBaseRoute() . '.create');
        Config::set('crud::config.redirects.error.update', $this->getBaseRoute() . '.edit');

        Config::set('crud::config', array_replace_recursive(Config::get('crud::config'), $this->config()));
    }

    /**
     * @param string $method
     * @param string $id
     * @return $this
     */
    public function init($method, $id = null)
    {
        $method = str_replace(__CLASS__ . '::', '', $method);
        $this->viewMode = $method;

        $fb = $this->formBuilder;
        $mb = $this->modelBuilder;
        $ob = $this->overviewBuilder;

        // Use a unique name for the FormBuilder instance. This helps identifying the
        // right FormBuilder instance in event listeners.
        $fb->name(get_called_class());

        // Extend the buildModel method to add columns and relations to your model.
        $this->buildModel($mb);

        // Extend the buildForm method to add form elements. These form elements are
        // translated to database columns using the event mentioned above.
        $this->buildForm($fb);

        if(!$fb->getOption('route') && !$fb->getOption('url')) {

            if($id) {
                $fb->route($this->getBaseRoute() . '.update', $id);
            }
            else {
                $fb->route($this->getBaseRoute() . '.store');
            }
        }

        // Setup the OverviewBuilder.
        $ob->setFormBuilder($fb);
        $ob->setModelBuilder($mb);

        // Extend the buildOverview method to configure the overview
        $this->buildOverview($ob);

        // There are several configuration options that you can set.
        // If they are not set yet, then we define some defaults.
        $this->buildConfig();

        // Now that everything is configured, let's trigger an event so
        // we can hook into this controller from the outside.
        Event::fire('crudController.init', array($this));

        if (method_exists($this, 'onCreate')) {
            Event::listen('crud::creating', array($this, 'onCreate'));
        }

        if (method_exists($this, 'onUpdate')) {
            Event::listen('crud::updating', array($this, 'onUpdate'));
        }

        if (method_exists($this, 'onSaved')) {
            Event::listen('crud::saved', array($this, 'onSaved'));
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $this->init(__METHOD__);

        $overview = $this->getOverview();
        $route = $this->getBaseRoute();
        $title = Config::get('crud::config.title');
        $view = Config::get('crud::config.view.index');

        return View::make($view, compact('title', 'overview', 'route'));
    }

    /**
     *
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->init(__METHOD__);

        $this->getBaseRoute();

        $form = $this->getForm();
        $model = $this->getModel();
        $errors = Session::get('errors');
        $route = $this->getBaseRoute();
        $title = Config::get('crud::config.title');
        $view = Config::get('crud::config.view.create');

        return View::make($view, compact('title', 'form', 'model', 'route', 'errors'));
    }

    /**
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $this->init(__METHOD__);

        $model = $this->getModel();
        $success = Config::get('crud::config.redirects.success.store');
        $error = Config::get('crud::config.redirects.error.store');

        Event::fire('crud::creating', array($model, $this));

		// Check if there are rules provided for this model in this controller
		// If not, we use the rules of the model itself
		$rules = method_exists($this, 'rules') ? $this->rules() : $this->getFormBuilder()->getRules();

        $v = Validator::make(Input::all(), $rules);

        if ($v->fails()) {
            return Redirect::route($error)->withInput()->withErrors($v->messages());
        }

		$model->fill(Input::all());
        $model->save();

        Event::fire('crud::saved', array($model, $this));

        $this->saveRelations($model);

		if(Request::ajax()) {
			return Response::json($model);
		}

        return Redirect::route($success);
    }

    /**
     *
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $this->init(__METHOD__, $id);

        $this->formBuilder->method('put');
        $model = $this->getModelWithRelations()->findOrFail($id);
        $form = $this->getForm($model->toArray());
        $route = $this->getBaseRoute();
        $errors = Session::get('errors');
        $title = Config::get('crud::config.title');
        $view = Config::get('crud::config.view.edit');

        return View::make($view, compact('title', 'form', 'model', 'route', 'errors'));
    }

    /**
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id)
    {
        $this->init(__METHOD__);

        $model = $this->getModel()->findOrFail($id);
        $success = Config::get('crud::redirects.success.update');
        $error = Config::get('crud::redirects.error.update');

        Event::fire('crud::updating', array($model, $this));

		// Check if there are rules provided for this model in this controller
		// If not, we use the rules of the model itself
		$rules = method_exists($this, 'rules') ? $this->rules() : $this->getFormBuilder()->getRules();

        $v = Validator::make(Input::all(), $rules);

        if ($v->fails()) {
            return Redirect::route($error, array($model->id))->withInput()->withErrors($v->messages());
        }

		$model->fill(Input::all());
        $model->save();

        Event::fire('crud::saved', array($model, $this));

        $this->saveRelations($model);

		if(Request::ajax()) {
			return Response::json($model);
		}

        return Redirect::route($success);
    }

    /**
     *
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $this->init(__METHOD__);

        $model = $this->getModel()->findOrFail($id);
        $success = Config::get('crud::redirects.success.destroy');

        $model->delete();

        return Redirect::route($success);
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->modelBuilder->build();
    }

    /**
     * @return Overview
     */
    public function getOverview()
    {
        return $this->overviewBuilder->build();
    }

    /**
     * 
     * @return Model
     */
    public function getModelWithRelations()
    {
        $model = $this->getModel();
        foreach ($this->modelBuilder->getRelations() as $alias => $relation) {
            if ($relation->getType() == 'hasMany') {
                $model = $model->with($alias);
            }
        }

        return $model;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getForm($values = null)
    {
        if (!$values) {
            $values = Input::old();
        }

        $this->formBuilder->defaults($values);
        return $this->formBuilder->build();
    }

    /**
     * 
     * @param Model $model
     */
    protected function saveRelations(Model $model)
    {
        foreach (Input::all() as $name => $value) {

            if (method_exists($model, $name) && $model->$name() instanceof Relations\BelongsToMany) {
                $data = isset($value['id']) ? $value['id'] : $value;
                $model->$name()->sync($data);
            }
        }
    }

    /**
     * Get the base route where the crud controller is working from. This is needed for
     * redirecting after saving the model.
     *
     * The routes can be changed thru a config file or thru the config() method.
     *
     * @return string
     */
    public function getBaseRoute()
    {
        // If there is a base route, simply return it
        if (Config::has('crud::config.baseroute')) {
            return Config::get('crud::config.baseroute');
        }

        $resourceDefaults = array('index', 'create', 'store', 'show', 'edit', 'update', 'destroy');
        $routeName = \Route::currentRouteName();

        // Just to be safe here, make sure the route is a resource. A resource has dots in
        // route name, where normal routes have a different structure. They start with
        // 'GET /news' for instance.
        if (strpos(' ', $routeName)) {
            throw new \Exception('Route must be a resource');
        }

        // Remove the action part of the route, so we get our base route
        foreach ($resourceDefaults as $default) {
            $routeName = str_replace('.' . $default, '', $routeName);
        }

        // Set it in the config, so we don't have to process the base route again
        Config::set('crud::config.baseroute', $routeName);

        return $routeName;
    }


    /**
     * @return bool
     */
    public function isOverview()
    {
        return $this->viewMode == __CLASS__ . '::index';
    }

    /**
     * @return bool
     */
    public function isCreate()
    {
        return $this->viewMode == __CLASS__ . '::create' || $this->viewMode == __CLASS__ . '::store';
    }

    /**
     * @return bool
     */
    public function isEdit()
    {
        return $this->viewMode == __CLASS__ . '::edit' || $this->viewMode == __CLASS__ . '::update';
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return $this->viewMode == __CLASS__ . '::destroy';
    }

}