@php
    $editProjectUrl = route('public.projects.corrections.edit', $project);
    $deadline = $project->correction_end_time?->format('d.m.Y H:i') ?? data_get($notification ?? null, 'context.correction_deadline', 'Zgodnie z informacją w systemie');
    $reason = trim((string) ($notification->body ?? 'Uzupełnij informacje wskazane przez urząd.'));
@endphp
<x-mail.partials.template-shell
    title="Uzupełnij informacje w zgłoszonym projekcie"
    preheader="Urzędnik poprosił o doprecyzowanie projektu."
    badge="Mieszkaniec"
    badge-color="#fff6bf"
    hero-title="Korekta projektu"
    :button-url="$editProjectUrl"
    button-label="Uzupełnij projekt"
    button-color="#f4c600"
    button-text-color="#262626"
    notice="Brak uzupełnienia w terminie może spowodować pozostawienie projektu bez dalszego procedowania."
    notice-color="#f4c600"
    notice-background="#fff6bf"
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Projekt wymaga uzupełnienia</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Podczas weryfikacji projektu wykryto informacje, które wymagają doprecyzowania. Wejdź do panelu mieszkańca i uzupełnij wskazane pola.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Numer projektu', 'value' => $project->number_drawn ?? $project->number ?? '#'.$project->id],
        ['label' => 'Termin uzupełnienia', 'value' => $deadline],
        ['label' => 'Powód', 'value' => str($reason)->limit(80)],
    ]" />
</x-mail.partials.template-shell>
