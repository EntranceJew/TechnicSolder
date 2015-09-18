@extends('layouts/master')
@section('title')
    <title>New Build - {{ $modpack->name }} - TechnicSolder</title>
@stop
@section('content')
<div class="page-header">
<h1>Build Management</h1>
</div>
<div class="panel panel-default">
	<div class="panel-heading">
	Create New Build ({{ $modpack->name }})
	</div>
	<div class="panel-body">
		@if ($errors->all())
			<div class="alert alert-danger">
			@foreach ($errors->all() as $error)
				{{ $error }}<br />
			@endforeach
			</div>
		@endif
		{{ Form::open() }}
		<div class="row">
			<div class="col-md-6">
				<h4>Clone Build</h4>
				<p>This will clone all the mods and mod versions of another build in the specified pack.</p>
				<div class="form-group row" id="clone-area">
					<div class="col-md-6">
						<div class="form-group has-feedback">
							<label class="control-label" for="clone-modpack">Modpack to Clone</label>
							<select class="form-control" name="clone-modpack" id="clone-modpack">
								@foreach ($modpacks as $the_modpack)
									<option value="{{ $the_modpack->id }}" {{ $the_modpack->id == $clone_modpack->id ? 'selected="selected"' : '' }}>{{ $the_modpack->name }}</option>
								@endforeach
							</select>
						</div>
						<p class="alert alert-warning">If you change this value after any others, all the rest will be reset. If you're going to clone a build, change this first.</p>
					</div>
					<div class="col-md-6">
						<div class="form-group has-feedback">
							<label class="control-label" for="clone">Build Version to Clone</label>
							<select class="form-control" name="clone" id="clone">
								<option value="">Do not clone</option>
								@foreach ($clone_modpack->builds as $build)
									<option value="{{ $build->id }}" {{ $clone_build_id == $build->id ? 'selected="selected"' : '' }}>{{ $build->version }}</option>
								@endforeach
							</select>
							<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
						</div>
					</div>
				</div>
				<hr>
				<h4>Create Build</h4>
				<p>All new builds by default will not be available in the API. They need to be published before they will show up.</p>
				<hr>
				<div class="form-group">
					<label for="version">Build Number</label>
					<input type="text" class="form-control" name="version" id="version">
				</div>
				<div class="form-group">
					<label for="version">Minecraft Version</label>
					<select class="form-control" name="minecraft">
						@foreach ($minecraft as $version)
						<option value="{{ $version['version'] }}">{{ $version['version'] }}</option>
						@endforeach
					</select>
				</div>
			</div>
			<div class="col-md-6">
				<h4>Build Requirements</h4>
				<p>These are requirements that are passed onto the launcher to prevent players from playing your pack without the required minumum settings</p>
				<hr>
				<div class="form-group">
					<label for="java-version">Minimum Java Version</label>
					<select class="form-control" name="java-version" id="java-version">
						<option value="1.8">Java 1.8</option>
						<option value="1.7">Java 1.7</option>
						<option value="1.6">Java 1.6</option>
						<option value="">No Requirement</option>
					</select>
				</div>
				<div class="form-group">
					<label for="memory">Minimum Memory (<i>in MB</i>)</label>
					<div class="input-group">
						<span class="input-group-addon">
							<input type="checkbox" id="memory-enabled" name="memory-enabled" aria-label="mb">
						</span>
						<input disabled type="text" class="form-control" name="memory" id="memory" aria-label="mb" aria-describedby="addon-mb">
						<span class="input-group-addon" id="addon-mb">MB</span>
					</div>
					<p class="help-block">Check the checkbox to enable the memory requirement.</p>
				</div>
			</div>
		</div>
		<hr>
		{{ Form::submit('Add Build', array('class' => 'btn btn-success')) }}
		{{ HTML::link('modpack/view/'.$modpack->id, 'Go Back', array('class' => 'btn btn-primary')) }}
		{{ Form::close() }}
	</div>
</div>
@endsection
@section('bottom')
<script type="text/javascript">
$('#memory-enabled').change(function(){
    if ($('#memory-enabled').is(':checked') == true){
        $('#memory').prop('disabled', false);
    } else {
        $('#memory').val('').prop('disabled', true);
    }
});

$('#clone-modpack').change(function(){
	var destination = '/modpack/add-build/{{ $modpack->id }}';
	var target = $(this).val();

	// only provde the $clone_modpack if we need to
	if (target != "{{ $modpack->id }}"){
		destination += '/'+target
	}
	window.location.href = destination;
});
</script>
@endsection
