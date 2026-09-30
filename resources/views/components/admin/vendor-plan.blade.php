@props(['vendor'])

{{-- Basic or Pro on the admin vendor list, with when Pro runs out. --}}
@if ($vendor->isPro())
    <span class="inline-flex flex-col">
        <span class="inline-flex w-fit rounded-md bg-ink px-1.5 py-0.5 text-[10px] leading-none font-bold tracking-wider text-surface uppercase">Pro</span>
        <span class="mt-1 text-xs whitespace-nowrap text-ink-muted">{{ Str::ucfirst(__('props.admin.plan_until', ['date' => $vendor->pro_until->translatedFormat('j M Y')])) }}</span>
    </span>
@else
    <span class="inline-flex rounded-md bg-surface-muted px-1.5 py-0.5 text-[10px] leading-none font-bold tracking-wider text-ink-muted uppercase">Basic</span>
@endif
