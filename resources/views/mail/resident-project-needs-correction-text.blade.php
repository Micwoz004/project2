Uzupełnij informacje w zgłoszonym projekcie

Projekt wymaga uzupełnienia.

Numer projektu: {{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}
Tytuł: {{ $project->title }}
Treść: {{ $notification->body ?? 'Uzupełnij informacje wskazane przez urząd.' }}

Uzupełnij projekt:
{{ route('public.projects.corrections.edit', $project) }}
