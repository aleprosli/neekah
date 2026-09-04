@php use App\Enums\BookingStatus; @endphp

<x-layouts.customer title="Majlis saya" :heading="$wedding?->title ?? 'Majlis saya'" :subheading="$wedding ? $wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state : 'Cipta wedding project untuk mula merancang.'">
    <x-slot:actions>
        @if ($wedding)
            <a href="{{ route('weddings.edit', $wedding) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Edit majlis</a>
        @endif
        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
    </x-slot:actions>

    @if (! $wedding)
        <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
            <span class="text-4xl">💍</span>
            <h2 class="font-display text-xl font-semibold">Mula dengan wedding project anda</h2>
            <p class="max-w-sm text-sm text-ink-muted">Tetapkan tarikh, lokasi dan bajet. Neekah akan jejak vendor, bayaran dan baki bajet anda.</p>
            <a href="{{ route('weddings.create') }}" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Cipta wedding project</a>
        </div>
    @else
        @php
            $budget = (float) $wedding->budget;
            $remaining = $budget - $committed;
            $progress = $categories->count() ? round($bookedCategoryIds->count() / $categories->count() * 100) : 0;
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Bajet" :value="'RM'.number_format($budget, 0)" :hint="'Baki RM'.number_format($remaining, 0)" />
            <x-stat-card label="Ditempah" :value="'RM'.number_format($committed, 0)" :hint="'Dibayar RM'.number_format($paid, 0)" />
            <x-stat-card label="Vendor" :value="$bookedCategoryIds->count().' / '.$categories->count()" :hint="$progress.'% kategori ditempah'" />
            <x-stat-card label="Bayaran tertunggak" :value="$pendingPayments" hint="Bayaran belum diselesaikan" :href="route('bookings.index')" />
        </div>

        {{-- Budget bar --}}
        <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium">Penggunaan bajet</span>
                <span class="text-ink-muted">RM{{ number_format($committed, 0) }} / RM{{ number_format($budget, 0) }}</span>
            </div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
                @php $used = $budget > 0 ? min(100, round($committed / $budget * 100)) : 0; @endphp
                <div @class(['h-full rounded-full transition-all', 'bg-brand-600' => $committed <= $budget, 'bg-amber-500' => $committed > $budget]) style="width: {{ $used }}%"></div>
            </div>
            @if ($committed > $budget)
                <p class="mt-2 text-xs text-amber-700">Anda telah melebihi bajet sebanyak RM{{ number_format($committed - $budget, 0) }}.</p>
            @endif
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
            {{-- Booked vendors --}}
            <section class="flex flex-col gap-4">
                <h2 class="font-display text-xl font-semibold">Vendor majlis anda</h2>
                @if ($bookings->isEmpty())
                    <p class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Belum ada vendor ditempah. <a href="{{ route('vendors.index') }}" class="font-medium text-brand-600 underline underline-offset-4">Cari vendor</a> untuk mula.</p>
                @else
                    <ul class="divide-y divide-line rounded-2xl border border-line">
                        @foreach ($bookings as $booking)
                            <li>
                                <a href="{{ route('bookings.show', $booking) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-xl {{ $booking->vendor->cover_tone }}">{{ $booking->vendor->category->icon }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium">{{ $booking->vendor->name }}</p>
                                        <p class="truncate text-sm text-ink-muted">{{ $booking->vendor->category->name }} · {{ $booking->package_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold">RM{{ number_format((float) $booking->total_amount, 0) }}</p>
                                        <x-booking-status :status="$booking->status" />
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Partner --}}
            <section class="flex flex-col gap-4">
                <h2 class="font-display text-xl font-semibold">Pasangan</h2>
                @php
                    $owner = $wedding->members->firstWhere('id', $wedding->user_id);
                    $partner = $wedding->partner();
                    $pendingInvite = $wedding->invitations->first();
                    $isOwner = $wedding->isOwnedBy(auth()->user());
                @endphp

                <ul class="flex flex-col gap-2 rounded-2xl border border-line p-4">
                    @foreach ($wedding->members as $member)
                        <li class="flex items-center gap-3 text-sm">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ mb_substr($member->name, 0, 1) }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $member->name }} @if ($member->id === auth()->id())<span class="text-ink-muted">(anda)</span>@endif</p>
                                <p class="truncate text-xs text-ink-muted">{{ $member->id === $wedding->user_id ? 'Pemilik majlis' : 'Pasangan' }}</p>
                            </div>
                            @if ($isOwner && $member->id !== $wedding->user_id)
                                <form method="POST" action="{{ route('weddings.members.destroy', [$wedding, $member]) }}" onsubmit="return confirm('Buang {{ $member->name }} daripada majlis ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-ink-muted hover:text-brand-700">Buang</button>
                                </form>
                            @endif
                        </li>
                    @endforeach

                    @if ($pendingInvite)
                        <li class="flex items-center gap-3 border-t border-line pt-3 text-sm">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-dashed border-line text-xs">?</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-ink-muted">{{ $pendingInvite->email }}</p>
                                <p class="text-xs text-ink-muted">Menunggu jawapan · tamat {{ $pendingInvite->expires_at->translatedFormat('j M Y') }}</p>
                            </div>
                            @if ($isOwner)
                                <form method="POST" action="{{ route('weddings.invitations.destroy', [$wedding, $pendingInvite]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-ink-muted hover:text-brand-700">Batal</button>
                                </form>
                            @endif
                        </li>
                    @endif
                </ul>

                @if ($isOwner && ! $partner && ! $pendingInvite)
                    <form method="POST" action="{{ route('weddings.invitations.store', $wedding) }}" class="flex flex-col gap-3 rounded-2xl border border-dashed border-line p-4">
                        @csrf
                        <div>
                            <p class="text-sm font-medium">Jemput pasangan anda</p>
                            <p class="mt-0.5 text-xs text-ink-muted">Anda berdua akan berkongsi checklist, bajet, tempahan dan pembayaran yang sama.</p>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="emel pasangan anda" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar jemputan</button>
                    </form>
                @elseif (! $isOwner)
                    <p class="text-xs text-ink-muted">Hanya pemilik majlis boleh menjemput atau membuang pasangan.</p>
                @endif
            </section>

            {{-- Checklist --}}
            <section class="flex flex-col gap-4">
                <h2 class="font-display text-xl font-semibold">Checklist kategori</h2>
                <ul class="flex flex-col gap-1 rounded-2xl border border-line p-3">
                    @foreach ($categories as $category)
                        @php $done = $bookedCategoryIds->contains($category->id); @endphp
                        <li>
                            <a href="{{ $done ? route('bookings.index') : route('vendors.index', ['category' => $category->slug]) }}" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-sm transition hover:bg-surface-muted">
                                <span @class(['flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold', 'bg-emerald-500 text-white' => $done, 'border border-line' => ! $done])>{{ $done ? '✓' : '' }}</span>
                                <span aria-hidden="true">{{ $category->icon }}</span>
                                <span @class(['text-ink-muted line-through' => $done])>{{ $category->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif
</x-layouts.customer>
