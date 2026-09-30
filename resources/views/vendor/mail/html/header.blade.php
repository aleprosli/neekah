@props(['url'])
{{-- The marketplace hero's peonies, drawn as PNGs in public/img/mail: no email
     client understands the SVG masks <x-site.ornament> paints with, and Gmail
     drops SVG altogether. Three cells rather than a background image, because
     Outlook ignores backgrounds and would lose the florals entirely. --}}
<tr>
<td class="header">
<table class="header-inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-corner" width="180" valign="top" align="left">
<img src="{{ asset('img/mail/corner-left.png') }}" width="180" height="150" alt="">
</td>
<td class="header-brand" align="center" valign="middle">
<a href="{{ $url }}" style="display: inline-block;">
{{-- A hosted image, because an email client will not load an attachment-free inline asset. --}}
<img src="{{ asset(config('neekah.brand.lockup')) }}" class="logo" alt="{{ config('app.name') }}">
</a>
<br>
<img src="{{ asset('img/mail/divider.png') }}" class="divider" width="120" height="16" alt="">
</td>
<td class="header-corner" width="180" valign="top" align="right">
<img src="{{ asset('img/mail/corner-right.png') }}" width="180" height="150" alt="">
</td>
</tr>
</table>
</td>
</tr>
