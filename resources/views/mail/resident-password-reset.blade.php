<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Ustaw nowe hasło</title>
    <style>
        @media (max-width: 680px) {
            .email-wrap { width: 100% !important; }
            .email-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .email-title { font-size: 34px !important; line-height: 1.04 !important; }
            .email-button { display: block !important; width: 100% !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f5f2; color:#262626; font-family:'Source Sans 3','Segoe UI',Arial,sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">Ustaw nowe hasło do konta mieszkańca</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f2; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:32px 14px 0;">
                <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:#ffffff; border-collapse:separate; border-spacing:0; border-radius:26px 26px 0 0; overflow:hidden; box-shadow:0 20px 70px rgba(38,38,38,.12);">
                    <tr>
                        <td class="email-pad" style="padding:30px 38px 22px;">
                            <div style="font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:26px; line-height:1; font-weight:800; color:#262626;">
                                <span style="display:inline-block; width:42px; height:42px; margin-right:10px; border-radius:14px; background:#168e45; color:#fff; text-align:center; line-height:42px;">BO</span>
                                Budżet Obywatelski
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 38px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border-radius:22px; background:#262626;">
                                <tr>
                                    <td style="height:138px; background-color:#262626; background-image:linear-gradient(90deg, rgba(22,142,69,.94), rgba(244,198,0,.82));">
                                        <div style="padding:26px 28px; color:#fff;">
                                            <div style="font-size:12px; line-height:1; font-weight:900; text-transform:uppercase; letter-spacing:.12em;">Konto mieszkańca</div>
                                            <div style="margin-top:12px; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:36px; line-height:1; font-weight:800;">Reset hasła</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:36px 46px 16px;">
                            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">Ustaw nowe hasło</h1>
                            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">Otrzymaliśmy prośbę o ustawienie nowego hasła dla konta {{ $residentEmail }} w serwisie {{ $cityName }}. Link jest ważny przez {{ $expiresInMinutes }} minut.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:8px 46px 42px;">
                            <a class="email-button" href="{{ $resetPasswordUrl }}" style="display:inline-block; padding:15px 22px; border-radius:10px; background:#168e45; color:#ffffff; font-size:16px; font-weight:800; text-decoration:none;">Ustaw nowe hasło</a>
                            <p style="margin:22px 0 0; font-size:14px; line-height:1.55; color:#60605d;">Jeśli nie proszono o zmianę hasła, tę wiadomość można zignorować.</p>
                        </td>
                    </tr>
                </table>
                <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:#262626; color:#ffffff; border-collapse:collapse; border-radius:0 0 26px 26px;">
                    <tr>
                        <td style="padding:20px 38px; font-size:13px; line-height:1.5; color:#ffffff;">
                            Pomoc: {{ $supportEmail }} @if (filled($supportPhone)) | {{ $supportPhone }} @endif
                            <br>
                            <a href="{{ $privacyUrl }}" style="color:#ffffff;">Prywatność</a> | <a href="{{ $accessibilityUrl }}" style="color:#ffffff;">Dostępność</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
