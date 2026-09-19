{{-- A coloured band carries the names across the sheet, edge to edge. --}}
<section class="relative flex min-h-[100svh] flex-col items-center justify-center py-16 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    @include('sites.ornaments.'.$ornament, ['position' => 'bottom-right'])
    <div class="relative flex w-full flex-col items-center">
        @include('sites.partials.bismillah', ['template' => $template, 'class' => 'mb-6'])
        @if ($site->cover_image)
            <img src="{{ Storage::disk('public')->url($site->cover_image) }}" alt="" class="mb-10 size-36 rounded-full object-cover" style="box-shadow: 0 0 0 5px var(--nk-page), 0 0 0 6px var(--nk-accent)">
        @else
            <div class="mb-10">@include('sites.partials.monogram', ['site' => $site])</div>
        @endif
        <div class="nk-ribbon relative z-[2] w-full px-8 py-7 shadow-lg">
            <p class="text-[11px] tracking-[0.38em] uppercase opacity-80">{{ $eyebrow }}</p>
            <h1 class="nk-script mt-3 text-5xl leading-tight">{{ $site->bride_name }}<span class="block text-3xl opacity-80">&amp;</span>{{ $site->groom_name }}</h1>
        </div>
        <div class="mt-10 px-8">@include('sites.partials.date-block', ['site' => $site])</div>
    </div>
</section>
