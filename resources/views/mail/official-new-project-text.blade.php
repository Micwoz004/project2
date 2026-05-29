Nowy projekt czeka na weryfikację

W systemie pojawiło się nowe zgłoszenie projektu.

Numer projektu: {{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}
Tytuł: {{ $project->title }}
Status: {{ $project->publicStatusLabel() }}

Otwórz projekt:
{{ url('/admin/projects/'.$project->id.'/edit') }}
