@props(['vendor'])

{{-- The vendor's logo set in their rank badge (x-vendor-rank-badge): the logo,
     or the initial when there is none, fills the hexagon's face. --}}
<span
    data-ranked-vendor-avatar="{{ $vendor->tier->value }}"
    data-rank-artwork-layer="front"
    {{ $attributes->class('relative size-28 shrink-0') }}
>
    <x-vendor-rank-badge :tier="$vendor->tier" :label="$vendor->name.' · '.$vendor->tier->label()" class="size-full">
        <foreignObject x="29" y="17.5" width="62" height="73">
            <x-vendor-avatar :vendor="$vendor" class="size-full rounded-none! text-3xl" />
        </foreignObject>
    </x-vendor-rank-badge>
</span>
