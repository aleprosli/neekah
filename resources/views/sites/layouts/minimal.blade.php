{{-- Type only: a large serif, a hairline and a great deal of paper. --}}
<section class="relative flex min-h-[100svh] flex-col items-center justify-center px-10 py-16 text-center">
    @include('sites.ornaments.'.$ornament, ['position' => 'top-left'])
    <div class="relative flex flex-col items-center">
        @include('sites.partials.bismillah', ['template' => $template, 'class' => 'mb-8'])
        <p class="nk-eyebrow">{{ $eyebrow }}</p>
        <h1 class="nk-name mt-10 text-5xl leading-[1.1] font-light tracking-tight">
            {{ $site->bride_name }}<span class="nk-script nk-accent my-2 block text-4xl font-normal">&amp;</span>{{ $site->groom_name }}
        </h1>
        <div class="nk-hairline my-10 w-16 border-t"></div>
        @include('sites.partials.date-block', ['site' => $site])
    </div>
</section>
