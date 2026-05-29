@php
    $projectUrl = route('public.projects.show', $project);
    $categoryName = $project->category?->name ?? $project->categories->first()?->name ?? 'Nie przypisano';
    $budget = filled($project->cost_formatted) ? number_format((float) $project->cost_formatted, 2, ',', ' ').' zł' : 'Nie podano';
@endphp
<x-mail.partials.template-shell
    title="Twój projekt został opublikowany"
    preheader="Projekt jest widoczny na publicznej liście projektów."
    badge="Mieszkaniec"
    hero-title="Projekt opublikowany"
    :button-url="$projectUrl"
    button-label="Zobacz projekt"
    notice="Przed rozpoczęciem głosowania możesz udostępnić link do projektu i zachęcić mieszkańców do zapoznania się ze szczegółami."
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Projekt został opublikowany</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Twój projekt przeszedł weryfikację i jest już dostępny na publicznej liście projektów. Mieszkańcy mogą zapoznać się z opisem, kosztem i lokalizacją.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Numer projektu', 'value' => $project->number_drawn ?? $project->number ?? '#'.$project->id],
        ['label' => 'Kategoria', 'value' => $categoryName],
        ['label' => 'Szacunkowy koszt', 'value' => $budget],
    ]" />
</x-mail.partials.template-shell>
