@props(['facts'])
<tr>
    <td class="email-pad" style="padding:12px 46px 24px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e1e1dc; border-radius:18px; overflow:hidden; border-collapse:separate; border-spacing:0;">
            <tr>
                @foreach ($facts as $fact)
                    <td class="fact-cell" width="{{ 100 / max(count($facts), 1) }}%" style="padding:16px 18px; vertical-align:top; @if (! $loop->last) border-right:1px solid #e1e1dc; @endif">
                        <div style="font-size:11px; line-height:1.1; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#60605d;">{{ $fact['label'] }}</div>
                        <div style="margin-top:7px; font-size:16px; line-height:1.25; font-weight:800; color:#262626;">{{ $fact['value'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </td>
</tr>
