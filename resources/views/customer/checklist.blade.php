@php
    $total = $tasks->count();
    $progress = $total ? (int) round($done / $total * 100) : 0;
    $groups = $tasks->groupBy(fn ($task) => $task->isDone() ? 'done' : 'todo');
@endphp

<x-layouts.customer title="Checklist" heading="Checklist majlis" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('j F Y').' · '.$wedding->event_date->diffForHumans()">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Progress" :value="$progress.'%'" :hint="$done.' daripada '.$total.' selesai'" />
        <x-stat-card label="Belum selesai" :value="$total - $done" hint="Termasuk tugasan akan datang" />
        <x-stat-card label="Lewat" :value="$overdue" :hint="$overdue ? 'Perlu perhatian segera' : 'Semua mengikut jadual'" />
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium">Kemajuan keseluruhan</span>
            <span class="text-ink-muted">{{ $done }} / {{ $total }}</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full rounded-full bg-brand-600 transition-all" style="width: {{ $progress }}%"></div>
        </div>
    </div>

    {{-- Add a task --}}
    <form method="POST" action="{{ route('weddings.tasks.store', $wedding) }}" class="mt-6 flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5 sm:flex-row sm:items-end">
        @csrf
        <label class="flex flex-1 flex-col gap-1.5">
            <span class="text-sm font-medium">Tambah tugasan</span>
            <input type="text" name="title" value="{{ old('title') }}" placeholder="Contoh: Tempah kereta pengantin" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-1.5 sm:w-44">
            <span class="text-sm font-medium">Kategori</span>
            <select name="category_id" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <option value="">Tiada</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1.5 sm:w-40">
            <span class="text-sm font-medium">Tarikh akhir</span>
            <input type="date" name="due_date" value="{{ old('due_date') }}" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </label>
        <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah</button>
    </form>

    {{-- Outstanding --}}
    <section class="mt-8 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Belum selesai ({{ $groups->get('todo')?->count() ?? 0 }})</h2>
        @if ($groups->get('todo')?->isNotEmpty())
            <ul class="divide-y divide-line rounded-2xl border border-line">
                @foreach ($groups['todo'] as $task)
                    <li class="flex items-center gap-3 p-4">
                        <form method="POST" action="{{ route('weddings.tasks.update', [$wedding, $task]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="done" value="1">
                            <button type="submit" class="flex size-6 items-center justify-center rounded-full border-2 border-line transition hover:border-brand-500 hover:bg-brand-50" aria-label="Tandakan selesai"></button>
                        </form>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $task->title }}</p>
                            <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                                @if ($task->category)<span>{{ $task->category->icon }} {{ $task->category->name }}</span>@endif
                                @if ($task->due_date)
                                    <span @class(['font-medium text-red-600' => $task->isOverdue()])>
                                        {{ $task->isOverdue() ? 'Lewat ' : '' }}{{ $task->due_date->translatedFormat('j M Y') }}
                                    </span>
                                @endif
                            </p>
                        </div>
                        @if ($task->category)
                            <a href="{{ route('vendors.index', ['category' => $task->category->slug]) }}" class="hidden shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 sm:inline">Cari vendor</a>
                        @endif
                        <x-confirm-action
                            :action="route('weddings.tasks.destroy', [$wedding, $task])"
                            method="DELETE"
                            tone="danger"
                            :title="'Padam tugasan ini?'"
                            :message="$task->title"
                            confirm="Padam"
                            trigger-class="shrink-0 text-xs font-medium text-ink-muted hover:text-brand-700"
                        >Padam</x-confirm-action>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Semua tugasan selesai. Tahniah!</p>
        @endif
    </section>

    {{-- Done --}}
    @if ($groups->get('done')?->isNotEmpty())
        <details class="mt-8">
            <summary class="cursor-pointer font-display text-xl font-semibold [&::-webkit-details-marker]:hidden">Selesai ({{ $groups['done']->count() }})</summary>
            <ul class="mt-4 divide-y divide-line rounded-2xl border border-line">
                @foreach ($groups['done'] as $task)
                    <li class="flex items-center gap-3 p-4">
                        <form method="POST" action="{{ route('weddings.tasks.update', [$wedding, $task]) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="done" value="0">
                            <button type="submit" class="flex size-6 items-center justify-center rounded-full bg-emerald-500 text-xs text-white" aria-label="Buka semula">✓</button>
                        </form>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-ink-muted line-through">{{ $task->title }}</p>
                            <p class="text-xs text-ink-muted">Selesai {{ $task->completed_at->translatedFormat('j M Y') }}@if ($task->completer) oleh {{ $task->completer->name }}@endif</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</x-layouts.customer>
