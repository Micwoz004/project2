@php
    $projectUrl = route('public.projects.show', $project);
@endphp
<x-mail.partials.template-shell
    title="Twój projekt został przyjęty do weryfikacji"
    preheader="Projekt jest już w systemie."
    badge="Mieszkaniec"
    hero-title="Projekt przyjęty"
    :button-url="$projectUrl"
    button-label="Sprawdź status projektu"
    notice="Jeśli urzędnik będzie potrzebował uzupełnienia informacji, otrzymasz osobną wiadomość oraz powiadomienie na koncie mieszkańca."
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Projekt jest już w systemie</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Dziękujemy za zgłoszenie projektu. Wniosek został zapisany i przekazany do weryfikacji formalnej przez zespół Budżetu Obywatelskiego.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Numer projektu', 'value' => $project->number_drawn ?? $project->number ?? '#'.$project->id],
        ['label' => 'Tytuł', 'value' => $project->title],
        ['label' => 'Status', 'value' => $project->publicStatusLabel()],
    ]" />
</x-mail.partials.template-shell>
