{{ $notification->subject }}

{{ $notification->body }}

@if ($project)
Projekt: {{ $project->title }}
Status: {{ $project->publicStatusLabel() }}
@endif

{{ url('/') }}
