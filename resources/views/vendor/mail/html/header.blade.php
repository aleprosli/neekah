@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- A hosted image, because an email client will not load an attachment-free inline asset. --}}
<img src="{{ asset(config('neekah.brand.lockup')) }}" class="logo" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
