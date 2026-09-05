@props(['class' => 'h-8'])

{{-- Every logo on the site comes from config('neekah.brand'), so the artwork
     is swapped in one place rather than hunted through the views. --}}
<img src="{{ asset(config('neekah.brand.lockup')) }}" alt="{{ config('app.name') }}" {{ $attributes->class(['w-auto', $class]) }}>
