<x-mail.partials.template-shell
    title="Ruszyło głosowanie w Budżecie Obywatelskim"
    preheader="Możesz już oddać głos na projekty."
    badge="Mieszkaniec"
    hero-title="Głosowanie"
    :button-url="$votingUrl"
    button-label="Przejdź do głosowania"
    notice="Po oddaniu głosu otrzymasz potwierdzenie. Jeżeli potrzebujesz pomocy, skorzystaj z informacji kontaktowych w stopce wiadomości."
>
    <tr>
        <td class="email-pad" style="padding:36px 46px 16px;">
            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Głosowanie jest otwarte</h1>
            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Możesz już oddać głos na projekty w Budżecie Obywatelskim. Sprawdź listę, wybierz inicjatywy ważne dla Twojej okolicy i zatwierdź głos online.</p>
        </td>
    </tr>
    <x-mail.partials.project-facts :facts="[
        ['label' => 'Start', 'value' => $votingStartDate],
        ['label' => 'Koniec', 'value' => $votingEndDate],
        ['label' => 'Uprawniony mieszkaniec', 'value' => $residentName],
    ]" />
</x-mail.partials.template-shell>
