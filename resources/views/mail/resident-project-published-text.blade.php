Twój projekt został opublikowany

Twój projekt przeszedł weryfikację i jest już dostępny na publicznej liście projektów.

Numer projektu: {{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}
Tytuł: {{ $project->title }}
Status: {{ $project->publicStatusLabel() }}

Zobacz projekt:
{{ route('public.projects.show', $project) }}
