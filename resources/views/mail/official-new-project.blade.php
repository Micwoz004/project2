@php
    $projectUrl = url('/admin/projects/'.$project->id.'/edit');
@endphp
<x-mail.partials.template-shell
    title="Nowy projekt czeka na weryfikację"
    preheader="W panelu urzędnika pojawiło się nowe zgłoszenie."
    badge="Urzędnik"
    badge-color="#ffe8e9"
    hero-kicker="Panel urzędnika"
    hero-title="Nowe zgłoszenie"
    :button-url="$projectUrl"
    button-label="Otwórz projekt w panelu"
    button-color="#d6262e"
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Nowy projekt do weryfikacji</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">W systemie pojawiło się nowe zgłoszenie projektu. Sprawdź kompletność danych i rozpocznij obsługę weryfikacji.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Numer projektu', 'value' => $project->number_drawn ?? $project->number ?? '#'.$project->id],
        ['label' => 'Tytuł', 'value' => $project->title],
        ['label' => 'Status', 'value' => $project->publicStatusLabel()],
    ]" />
</x-mail.partials.template-shell>
