@props(['filters', 'except' => []])

@foreach ($filters as $key => $value)
    @if ($value !== null && ! in_array($key, (array) $except, true) && ! ($key === 'sort' && $value === 'recommended'))
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
