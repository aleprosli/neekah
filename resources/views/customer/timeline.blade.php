<x-layouts.customer title="Timeline" heading="Wedding timeline" :subheading="$wedding->title.' · '.$wedding->event_date->translatedFormat('l, j F Y')">
    @if ($bookedVendors->isEmpty())
        <p class="mb-6 rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">Tempah vendor dahulu untuk menugaskan mereka pada slot timeline. Vendor hanya nampak slot yang ditugaskan kepada mereka.</p>
    @endif

    {{-- Add a slot --}}
    <form method="POST" action="{{ route('weddings.timeline.store', $wedding) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
        @csrf
        <div class="grid gap-3 sm:grid-cols-[7rem_7rem_1fr]">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Mula</span>
                <input type="time" name="starts_at" value="{{ old('starts_at') }}" required class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Tamat</span>
                <input type="time" name="ends_at" value="{{ old('ends_at') }}" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Aktiviti</span>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Contoh: Akad Nikah" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Vendor terlibat</span>
                <select name="vendor_id" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <option value="">Tiada</option>
                    @foreach ($bookedVendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->category->icon }} {{ $vendor->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Lokasi</span>
                <input type="text" name="location" value="{{ old('location') }}" placeholder="Rumah pengantin / dewan" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Nota</span>
                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Arahan untuk vendor" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
        </div>
        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah aktiviti</button>
    </form>

    {{-- The day --}}
    <section class="mt-8">
        @if ($items->isEmpty())
            <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
                <span class="text-4xl">🗓️</span>
                <h2 class="font-display text-xl font-semibold">Timeline masih kosong</h2>
                <p class="max-w-sm text-sm text-ink-muted">Susun perjalanan hari majlis dari makeup pagi hingga majlis tamat. Setiap vendor akan nampak slot mereka sendiri.</p>
            </div>
        @else
            <ol class="relative flex flex-col gap-1 border-l-2 border-line pl-6 sm:pl-8">
                @foreach ($items as $item)
                    <li class="relative pb-6 last:pb-0">
                        <span class="absolute top-1.5 -left-[1.85rem] flex size-3 rounded-full bg-brand-600 ring-4 ring-surface sm:-left-[2.35rem]"></span>

                        <div class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-start">
                            <div class="shrink-0 sm:w-32">
                                <p class="font-display text-lg font-semibold">{{ $item->startsAtLabel() }}</p>
                                @if ($item->endsAtLabel())
                                    <p class="text-xs text-ink-muted">hingga {{ $item->endsAtLabel() }}</p>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $item->title }}</p>
                                <p class="flex flex-wrap items-center gap-x-2 text-sm text-ink-muted">
                                    @if ($item->vendor)
                                        <a href="{{ route('vendors.show', $item->vendor) }}" class="hover:text-ink">{{ $item->vendor->category->icon }} {{ $item->vendor->name }}</a>
                                    @endif
                                    @if ($item->location)<span>📍 {{ $item->location }}</span>@endif
                                </p>
                                @if ($item->notes)
                                    <p class="mt-1 text-sm text-ink-muted">{{ $item->notes }}</p>
                                @endif
                            </div>

                            <x-confirm-action
                                :action="route('weddings.timeline.destroy', [$wedding, $item])"
                                method="DELETE"
                                tone="danger"
                                title="Padam aktiviti ini?"
                                :message="$item->startsAtLabel().' · '.$item->title"
                                confirm="Padam"
                                trigger-class="shrink-0 self-start text-xs font-medium text-ink-muted hover:text-brand-700"
                            >Padam</x-confirm-action>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</x-layouts.customer>
