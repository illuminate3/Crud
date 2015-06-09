<?php

namespace Illuminate3\Crud;
use Illuminate3\Form\FormBuilder;
use View, Input, URL, App, Redirect;

class ManagerController extends \BaseController
{
    protected $scanner;
    protected $generator;
    protected $formBuilder;

    public function __construct(Scanner $scanner, ControllerGenerator $generator, FormBuilder $formBuilder)
    {
        $this->scanner = $scanner;
        $this->generator = $generator;
        $this->formBuilder = $formBuilder;
    }
    
    public function index()
    {
        $controllers = $this->scanner->scanForControllers(array('../app/controllers'));
        
        return View::make('crud::manager/index', compact('controllers'));
    }
    
    public function scan()
    {
        $controllers = $this->scanner->scanForControllers(array('../workbench', '../vendor'));
        
        return View::make('crud::manager/scan', compact('controllers'));
    }

    public function manage($class)
    {
        $controller = $this->getController($class);
        $model =  $controller->getModelBuilder()->getName();
        
        $fb = $this->formBuilder;
        $fb->action(URL::action(get_called_class() . '@store'));

        $fb->text('original')
			->label('Original controller')
			->value(get_class($controller));

        $fb->text('controller')
			->label('Controller name')
			->value($model . 'Controller')
			->placeholder('Enter a name for the controller, for example \'NewsController\'.');

        $fb->text('path')
			->label('Path')
			->value('../app/controllers');

        $form = $fb->build();
                
        return View::make('crud::manager/manage', compact('form'));
    }

	public function create()
	{
		$fb = $this->formBuilder;
		$fb->action(URL::action(get_called_class() . '@store'));

		$fb->text('class')
			->label('Name')
			->placeholder('Enter a name for the resource, for example \'News\'.');

		$fb->text('url')
			->label('Url to the model overview')
			->placeholder('What should be the url pointing to your resource? An example would be \'admin/news\'.');

		$fb->text('path')
			->label('Path')
			->value('../app/controllers')
			->help('This is the folder where the controller is being generated in. It defaults to
					the application controllers folder');

		$form = $fb->build();

		return View::make('crud::manager/create', compact('form'));
	}
    
    public function store()
    {
		if(Input::has('original')) {
			$controller = App::make(Input::get('original'));
			$this->generator->setController($controller);
			$class = Input::get('controller');
			$filename = \Input::get('path') . '/' . $class . '.php';
		}
		else {
			$this->generator->setClassName(Input::get('class'));
			$class = Input::get('class') . 'Controller';
			$filename = \Input::get('path') . '/' . str_replace('\\', '/', $class) . '.php';
		}

		// Write the new controller file to the controller folder
		@mkdir(dirname($filename), 0755, true);
		file_put_contents($filename, $this->generator->generate());

		// Add resource route to routes.php
		$line = sprintf(PHP_EOL . 'Route::resource(\'%s\', \'%s\');', Input::get('url'), $class);
		file_put_contents(app_path() . '/routes.php', $line, FILE_APPEND);

		// Redirect to the resource url overview
        return Redirect::to(Input::get('url'));
    }

    /**
     * 
     * @param string $key
     * @return CrudController
     */
    protected function getController($key)
    {
        $class = str_replace('/', '\\', $key);
        return \App::make($class);        
    }


}