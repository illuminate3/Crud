<div class="page-header">
	<h1>Resource manager</h1>
	<p>
		Here can edit or create new resources.
		By creating a new resource, a resourceful route will be added to your existing ones.
	</p>
	<a href="{{ URL::action('Illuminate3\Crud\ManagerController@create') }}" class="btn btn-primary">Create a new resource</a>
</div>

<hr>
<br>

<div class="row">

	<div class="col-6">

		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Your resources</h3>
			</div>
			<div class="panel-body">
				@if($controllers)
				<ul class="list-group">
					@foreach($controllers as $key => $class)
					<li class="list-group-item"><a href="{{ URL::action('Illuminate3\Crud\ManagerController@manage', $key) }}">{{ $class->getName() }}</a></li>
					@endforeach
				</ul>
				@else
				<p>You have no resources that extend the CrudController yet.</p>
				@endif
			</div>
		</div>

	</div>

	<div class="col-6">

		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Existing controllers</h3>
			</div>
			<div class="panel-body">
				<p>
					If you have packages that include CrudControllers, you can copy the contents of the to your application.
				</p>
				<a href="{{ URL::action('Illuminate3\Crud\ManagerController@scan') }}" class="btn btn-info">Scan for controllers</a>
			</div>
		</div>

	</div>

</div>



