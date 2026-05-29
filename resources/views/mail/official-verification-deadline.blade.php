@php
    $projectUrl = url('/admin/projects/'.$project->id.'/edit');
    $deadline = $project->budgetEdition?->pre_voting_verification_end?->format('d.m.Y H:i') ?? 'Zgodnie z harmonogramem edycji';
@endphp
<x-mail.partials.template-shell
    title="Zbliża się termin weryfikacji projektu"
    preheader="Projekt wymaga decyzji przed końcem etapu."
    badge="Urzędnik"
    badge-color="#fff6bf"
    hero-kicker="Panel urzędnika"
    hero-title="Termin weryfikacji"
    :button-url="$projectUrl"
    button-label="Przejdź do weryfikacji"
    button-color="#f4c600"
    button-text-color="#262626"
    notice="Zweryfikuj dane lub przekaż projekt do uzupełnienia przed końcem etapu."
    notice-color="#f4c600"
    notice-background="#fff6bf"
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Termin weryfikacji jest blisko</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Projekt pozostaje w statusie weryfikacji, a termin obsługi etapu zbliża się do końca.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Numer projektu', 'value' => $project->number_drawn ?? $project->number ?? '#'.$project->id],
        ['label' => 'Termin etapu', 'value' => $deadline],
        ['label' => 'Status', 'value' => $project->publicStatusLabel()],
    ]" />
</x-mail.partials.template-shell>
