Potwierdź adres e-mail

Dzień dobry{{ filled($residentName) ? ', '.$residentName : '' }}.

Kliknij link, aby potwierdzić adres {{ $residentEmail }} w serwisie {{ $cityName }}:
{{ $verificationUrl }}

Jeśli nie zakładano konta w serwisie Budżetu Obywatelskiego, tę wiadomość można zignorować.

Pomoc: {{ $supportEmail }}@if (filled($supportPhone)) | {{ $supportPhone }}@endif
