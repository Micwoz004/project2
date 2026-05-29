Zbliża się termin weryfikacji projektu

Projekt pozostaje w statusie weryfikacji, a termin obsługi etapu zbliża się do końca.

Numer projektu: {{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}
Tytuł: {{ $project->title }}
Status: {{ $project->publicStatusLabel() }}

Otwórz projekt:
{{ url('/admin/projects/'.$project->id.'/edit') }}
