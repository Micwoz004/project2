Twój projekt został przyjęty do weryfikacji

Dziękujemy za zgłoszenie projektu. Wniosek został zapisany i przekazany do weryfikacji formalnej.

Numer projektu: {{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}
Tytuł: {{ $project->title }}
Status: {{ $project->publicStatusLabel() }}

Sprawdź status projektu:
{{ route('public.projects.show', $project) }}
