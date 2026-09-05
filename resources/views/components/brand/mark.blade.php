@props(['class' => 'size-8'])

<img src="{{ asset(config('neekah.brand.mark')) }}" alt="{{ config('app.name') }}" {{ $attributes->class(['shrink-0 rounded-xl object-cover', $class]) }}>
