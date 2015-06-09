<h1>
	@if($title)
	{{ $title }}
	@else
	Overview
	@endif
</h1>
<br>

<table class="table table-striped">
	<tr>
		<thead>
		@foreach($overview->labels() as $label)
		<th>{{ $label }}</th>
		@endforeach
		<th></th>
		</thead>
	</tr>
	<tbody>
	@foreach($overview->rows() as $id => $row)
	<tr>
		@foreach($row->columns() as $column)
		<td>{{ $column }}</td>
		@endforeach
		<td class="col-2">
			{{ Form::open(array('route' => array($route . '.destroy', $id), 'method' => 'DELETE')) }}
			<a href="{{ URL::route($route . '.edit', $id) }}" class="btn btn-xs btn-default">Edit</a>
			{{ Form::submit('Delete', array('class' => 'btn btn-xs btn-link')) }}
			{{ Form::close() }}
		</td>
	</tr>
	@endforeach
	</tbody>
</table>

{{ $overview->links() }}