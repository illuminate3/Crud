
<h2>New Crud controllers</h2>
<ul>
    @foreach($controllers as $key => $class)
    <li><a href="{{ URL::action('Illuminate3\Crud\ManagerController@manage', $key) }}">{{ $class->getName() }}</a></li>
    @endforeach
</ul>

<a href="{{ URL::action('Illuminate3\Crud\ManagerController@index') }}">View used controllers</a>