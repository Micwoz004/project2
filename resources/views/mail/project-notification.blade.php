<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $notification->subject }}</title>
    <style>
        @media (max-width: 680px) {
            .email-wrap { width: 100% !important; }
            .email-pad { padding-left: 22px !important; padding-right: 22px !important; }
            .email-title { font-size: 34px !important; line-height: 1.04 !important; }
            .email-button { display: block !important; width: 100% !important; text-align: center !important; }
            .fact-cell { display: block !important; width: 100% !important; border-right: 0 !important; border-bottom: 1px solid #e1e1dc !important; }
            .fact-cell:last-child { border-bottom: 0 !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f5f2; color:#262626; font-family:'Source Sans 3','Segoe UI',Arial,sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">{{ $notification->subject }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f2; border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:32px 14px 0;">
                <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:#ffffff; border-collapse:separate; border-spacing:0; border-radius:26px 26px 0 0; overflow:hidden; box-shadow:0 20px 70px rgba(38,38,38,.12);">
                    <tr>
                        <td class="email-pad" style="padding:30px 38px 22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <div style="font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:26px; line-height:1; font-weight:800; color:#262626;">
                                            <span style="display:inline-block; width:42px; height:42px; margin-right:10px; border-radius:14px; background:#168e45; color:#fff; text-align:center; line-height:42px;">BO</span>
                                            Budżet Obywatelski
                                        </div>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block; padding:8px 12px; border-radius:999px; background:#e7f6ed; color:#262626; font-size:12px; line-height:1; font-weight:800; text-transform:uppercase; letter-spacing:.08em;">Powiadomienie</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 38px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0; overflow:hidden; border-radius:22px; background:#262626;">
                                <tr>
                                    <td style="height:138px; background-color:#262626; background-image:linear-gradient(90deg, rgba(22,142,69,.94), rgba(244,198,0,.82)); background-size:cover; background-position:center;">
                                        <div style="padding:26px 28px; color:#fff;">
                                            <div style="font-size:12px; line-height:1; font-weight:900; text-transform:uppercase; letter-spacing:.12em;">Budżet Obywatelski</div>
                                            <div style="margin-top:12px; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:36px; line-height:1; font-weight:800;">Projekt</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-pad" style="padding:36px 46px 16px;">
                            <h1 class="email-title" style="margin:0; font-family:'Barlow Condensed','Arial Narrow',Arial,sans-serif; font-size:46px; line-height:1.02; font-weight:800; letter-spacing:0; color:#262626;">{{ $notification->subject }}</h1>
                            <p style="margin:20px 0 0; font-size:18px; line-height:1.58; color:#262626;">{!! nl2br(e($notification->body)) !!}</p>
                        </td>
                    </tr>
                    @if ($project)
                        <tr>
                            <td class="email-pad" style="padding:12px 46px 24px;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e1e1dc; border-radius:18px; overflow:hidden; border-collapse:separate; border-spacing:0;">
                                    <tr>
                                        <td class="fact-cell" width="33.33%" style="padding:16px 18px; vertical-align:top; border-right:1px solid #e1e1dc;">
                                            <div style="font-size:11px; line-height:1.1; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#60605d;">Numer projektu</div>
                                            <div style="margin-top:7px; font-size:16px; line-height:1.25; font-weight:800; color:#262626;">{{ $project->number_drawn ?? $project->number ?? '#'.$project->id }}</div>
                                        </td><td class="fact-cell" width="33.33%" style="padding:16px 18px; vertical-align:top; border-right:1px solid #e1e1dc;">
                                            <div style="font-size:11px; line-height:1.1; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#60605d;">Tytuł</div>
                                            <div style="margin-top:7px; font-size:16px; line-height:1.25; font-weight:800; color:#262626;">{{ $project->title }}</div>
                                        </td><td class="fact-cell" width="33.33%" style="padding:16px 18px; vertical-align:top;">
                                            <div style="font-size:11px; line-height:1.1; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#60605d;">Status</div>
                                            <div style="margin-top:7px; font-size:16px; line-height:1.25; font-weight:800; color:#262626;">{{ $project->publicStatusLabel() }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="email-pad" style="padding:8px 46px 42px;">
                            <a class="email-button" href="{{ url('/') }}" style="display:inline-block; padding:15px 22px; border-radius:10px; background:#168e45; color:#ffffff; font-size:16px; font-weight:800; text-decoration:none;">Przejdź do serwisu</a>
                        </td>
                    </tr>
                </table>
                <table role="presentation" class="email-wrap" width="640" cellspacing="0" cellpadding="0" style="width:640px; max-width:640px; background:#262626; color:#ffffff; border-collapse:collapse; border-radius:0 0 26px 26px;">
                    <tr>
                        <td style="padding:20px 38px; font-size:13px; line-height:1.5; color:#ffffff;">
                            Wiadomość automatyczna z systemu Budżetu Obywatelskiego.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
