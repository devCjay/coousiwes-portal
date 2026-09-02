@props(['url'])
@php
    $logo = asset('images/coou-logo.png');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" class="header-link">
<img src="{{ $logo }}" class="logo" alt="{{ config('app.name', 'COOU SIWES Portal') }} Logo">
</a>
<div class="header-title">{{ config('app.name', 'COOU SIWES Portal') }}</div>
<div class="header-subtitle">Industrial Training Portal</div>
</td>
</tr>
