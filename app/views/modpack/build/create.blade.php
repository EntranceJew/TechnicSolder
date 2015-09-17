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
				<hr>
				<h4>Clone Build</h4>
				<p>This will clone all the mods and mod versions of another build in the specified pack.</p>
				<div class="form-group row" id="clone-area">
					<div class="col-md-6">
						<div class="form-group has-feedback">
							<label class="control-label" for="clone-modpack">Modpack to Clone</label>
							<select class="form-control" name="clone-modpack" id="clone-modpack">
								<option value="{{ $modpack->slug }}">{{ $modpack->name }}</option>
								@foreach ($modpacks as $the_modpack)
									<option value="{{ $the_modpack->slug }}">{{ $the_modpack->name }}</option>
								@endforeach
								<option value="totally-nonexistant-pack">Totally Nonexistant Pack</option>
								<option value="break">Break HTTP Response</option>
							</select>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group has-feedback">
							<label class="control-label" for="clone-build-version">Build Version to Clone</label>
							<select class="form-control" name="clone-build-version" id="clone-build-version">
								<option value="">Do not clone</option>
								@foreach ($modpack->builds as $build)
									<option value="{{ $build->version }}">{{ $build->version }}</option>
								@endforeach
							</select>
							<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
						</div>
					</div>
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

var blame = function(jTarget){
	$(jTarget).parent().addClass('has-error');
}

var forgive = function(jTarget){
	$(jTarget).parent().removeClass('has-error');
}

var errorOut = function(errorSink, textError){
	$("<div class='alert alert-danger clone-modpack-error'>"+ textError +"</div>" ).insertBefore( $(errorSink) );
}

var startWaiting = function(jTarget, statusText){
	$(".clone-modpack-error").remove();
	jTarget.parent().addClass('has-waiting').removeClass('has-error');
	jTarget.prop('disabled', true).addClass('disabled');
	jTarget.next().addClass('glyphicon-technic');
	jTarget.empty();
	jTarget.append($("<option class='status'>"+ statusText+ "</option>"));
}

var stopWaiting = function(jTarget, statusText){
	jTarget.parent().removeClass('has-waiting');
	jTarget.prop('disabled', false).removeClass('disabled');
	jTarget.next().removeClass('glyphicon-technic');
	jTarget.empty();
	jTarget.append($("<option class='status'>"+ statusText+ "</option>"));
}

var fetchModpackVersions = function(targetElement,modpackSlug,errorSink){
	targetElement = typeof targetElement !== 'undefined' ?  targetElement : this;
	var jTarget = $(targetElement);
	startWaiting(jTarget, "Getting modpack versions ...");

	var targetURL = "/api/modpack/"+modpackSlug;
	if(modpackSlug == 'break'){
		targetURL = "/api/breadsticks/";
	}
	$.ajax({
		method: "GET",
		url: targetURL,
		dataType: "json",
		success: function(data){
			setTimeout(function (){
				if(data && data.error){
					stopWaiting(jTarget, "API Error");
					errorOut(errorSink, data.error);
					blame("#clone-modpack");
				} else if(data && data.builds) {
					stopWaiting(jTarget, "Done");

					jTarget.empty();
					jTarget.append($("<option>Do not clone.</option>"));
					for (var buildIndex in data.builds){
						var buildVersion = data.builds[buildIndex];
						jTarget.append($("<option></option>")
								.attr("value",buildVersion).text(buildVersion));
					}

					jTarget.focus();
				} else {
					stopWaiting(jTarget, "Unknown API Error");
					errorOut(errorSink, "Unknown error. Try again.");
				}
			}, 2000);
		},
		error: function(j,status,error){
			stopWaiting(jTarget, "HTTP Error");
			errorOut(errorSink, j.statusText);
		}
	});
	// good: show new dropdown
	// bad: show error as dropdown, retry button
}

$('#clone-modpack').change(function(){
	forgive($(this));
	fetchModpackVersions('#clone-build-version', $(this).val(), "#clone-area");
});
</script>
@endsection
