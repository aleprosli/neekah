<script setup>
/**
 * One made-up Neekah screen inside the About page's phone (FlowPhoneDemo),
 * showing a step being done rather than describing it: the dashboard with
 * the budget, the marketplace scrolling, a vendor's profile with its
 * contact button, the WhatsApp chat with that vendor, the digital card, and
 * the day itself with guests' photos coming into the Kenangan album.
 *
 * Each screen is mounted fresh when its step comes on, so its animations
 * play from the start every time. The couple and vendor are invented.
 */
defineProps({
    /** plan, search, contact, deal, invite or celebrate. */
    name: { type: String, required: true },
    /** pages.landing.flow_demo */
    text: { type: Object, required: true },
});

const tiles = ['from-brand-200 to-brand-400', 'from-gold-300 to-gold-500', 'from-brand-100 to-gold-300', 'from-gold-300 to-brand-300', 'from-brand-300 to-brand-500', 'from-gold-400 to-brand-200'];
const vendors = [
    { tile: 'from-brand-300 to-brand-500', rating: '4.9', price: 'RM2,800' },
    { tile: 'from-gold-300 to-gold-500', rating: '4.8', price: 'RM1,500' },
    { tile: 'from-brand-100 to-gold-300', rating: '5.0', price: 'RM3,200' },
    { tile: 'from-gold-300 to-brand-300', rating: '4.7', price: 'RM900' },
];
const confetti = Array.from({ length: 14 }, (_, i) => ({
    left: `${(i * 37) % 100}%`,
    delay: `${(i % 7) * 0.18}s`,
    color: ['bg-brand-400', 'bg-gold-400', 'bg-brand-200', 'bg-gold-300'][i % 4],
    rotate: `${(i * 47) % 180}deg`,
}));
</script>

<template>
    <div class="flex size-full flex-col gap-2.5 text-ink">
        <!-- 1. Plan: the couple's dashboard, budget filling in, checklist ticking. -->
        <template v-if="name === 'plan'">
            <p class="fps-rise text-[11px] font-semibold">{{ text.greeting }}</p>
            <div class="fps-rise flex items-center justify-between rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 p-3 text-white shadow-md shadow-brand-900/20" style="--d: 80ms">
                <div>
                    <p class="font-display text-2xl leading-none font-semibold">72</p>
                    <p class="text-[10px] opacity-80">{{ text.days_left }}</p>
                </div>
                <svg class="size-8 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="14" r="5.5"/><circle cx="16" cy="14" r="5.5"/><path d="m9 5 1.5 2.5h-3L9 5Z"/></svg>
            </div>
            <div class="fps-rise flex items-center gap-3 rounded-2xl bg-white p-3 ring-1 ring-line" style="--d: 160ms">
                <svg class="size-14 shrink-0 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15" fill="none" stroke="var(--color-gold-300)" stroke-opacity=".4" stroke-width="4"/>
                    <circle class="fps-ring" cx="18" cy="18" r="15" fill="none" stroke="var(--color-brand-600)" stroke-width="4" stroke-linecap="round" pathLength="100"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-[10px] text-ink-muted">{{ text.budget }}</p>
                    <p class="font-display text-base leading-tight font-semibold">RM30,000</p>
                    <p class="text-[10px] text-brand-600"><span class="font-semibold">62%</span> {{ text.spent }}</p>
                </div>
            </div>
            <div class="fps-rise rounded-2xl bg-white p-3 ring-1 ring-line" style="--d: 240ms">
                <p class="mb-2 text-[10px] font-semibold">{{ text.checklist }}</p>
                <div v-for="n in 3" :key="n" class="flex items-center gap-2 py-1">
                    <span class="relative grid size-4 shrink-0 place-items-center rounded-full ring-1 ring-line">
                        <span class="fps-tick absolute inset-0 grid place-items-center rounded-full bg-emerald-500 text-white" :style="{ '--d': `${0.6 + n * 0.35}s` }">
                            <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        </span>
                    </span>
                    <span class="h-1.5 rounded-full bg-ink/15" :style="{ width: `${[70, 52, 60][n - 1]}%` }"></span>
                </div>
            </div>
        </template>

        <!-- 2. Search: the marketplace scrolling past. -->
        <template v-else-if="name === 'search'">
            <div class="fps-rise flex items-center gap-2 rounded-full bg-white px-3 py-2 ring-1 ring-line">
                <svg class="size-3.5 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span class="text-[10px] text-ink-muted">{{ text.search }}</span>
            </div>
            <div class="fps-rise flex gap-1.5" style="--d: 80ms">
                <span class="rounded-full bg-brand-600 px-2.5 py-1 text-[9px] font-semibold text-white">{{ text.category }}</span>
                <span v-for="n in 3" :key="n" class="flex items-center rounded-full bg-white px-3 ring-1 ring-line"><span class="h-1 w-6 rounded-full bg-ink/20"></span></span>
            </div>
            <div class="fps-fade-y relative min-h-0 flex-1 overflow-hidden">
                <div class="fps-scroll flex flex-col gap-2">
                    <div v-for="(vendor, i) in [...vendors, ...vendors]" :key="i" class="flex gap-2.5 rounded-2xl bg-white p-2 ring-1 ring-line">
                        <span :class="['size-14 shrink-0 rounded-xl bg-linear-to-br', vendor.tile]"></span>
                        <span class="flex min-w-0 flex-1 flex-col justify-center gap-1">
                            <span class="h-2 w-3/4 rounded-full bg-ink/70"></span>
                            <span class="h-1.5 w-1/2 rounded-full bg-ink/20"></span>
                            <span class="flex items-center justify-between text-[9px]">
                                <span class="flex items-center gap-0.5 font-semibold"><svg class="size-2.5 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="m12 3.5 2.6 5.5 6 .8-4.3 4.2 1 6-5.3-2.9-5.3 2.9 1-6L3.4 9.8l6-.8L12 3.5Z"/></svg>{{ vendor.rating }}</span>
                                <span class="font-semibold text-brand-600">{{ vendor.price }}</span>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <!-- 3. Contact: a vendor's profile, and a tap on its contact button. -->
        <template v-else-if="name === 'contact'">
            <div class="fps-rise overflow-hidden rounded-2xl bg-white ring-1 ring-line">
                <div class="h-20 bg-linear-to-br from-brand-300 via-gold-300 to-brand-200"></div>
                <div class="-mt-6 flex flex-col items-center gap-1 px-3 pb-3 text-center">
                    <span class="grid size-12 place-items-center rounded-full bg-brand-600 font-display text-lg font-semibold text-white ring-4 ring-white">S</span>
                    <p class="flex items-center gap-1 font-display text-sm font-semibold">
                        {{ text.vendor }}
                        <svg class="size-3 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 9.5 4.5H6a1.5 1.5 0 0 0-1.5 1.5v3.5L2 12l2.5 2.5V18A1.5 1.5 0 0 0 6 19.5h3.5L12 22l2.5-2.5H18a1.5 1.5 0 0 0 1.5-1.5v-3.5L22 12l-2.5-2.5V6A1.5 1.5 0 0 0 18 4.5h-3.5z"/><path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </p>
                    <p class="text-[10px] text-ink-muted">{{ text.category }} · <span class="font-semibold text-ink">★ 4.9</span></p>
                </div>
            </div>
            <div class="fps-rise grid grid-cols-3 gap-1.5" style="--d: 100ms">
                <span v-for="(tile, i) in tiles.slice(0, 3)" :key="i" :class="['aspect-square rounded-xl bg-linear-to-br', tile]"></span>
            </div>
            <div class="fps-rise flex flex-col gap-1 rounded-2xl bg-white p-3 ring-1 ring-line" style="--d: 180ms">
                <span class="h-1.5 w-full rounded-full bg-ink/15"></span>
                <span class="h-1.5 w-4/5 rounded-full bg-ink/15"></span>
            </div>
            <div class="relative mt-auto mb-1">
                <span class="fps-press flex h-10 items-center justify-center gap-2 rounded-full bg-emerald-600 text-[11px] font-semibold text-white shadow-lg shadow-emerald-900/20">
                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.3-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.7-1.2-4.4-3.9-4.5-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.1.1.3 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.6 1.9 1.1 1 2 1.3 2.3 1.4.3.1.5.1.6-.1l.9-1c.2-.3.4-.2.6-.1l1.9.9c.3.1.5.2.5.3.1.2.1.7-.1 1.3z"/></svg>
                    {{ text.contact }}
                </span>
                <span class="fps-ripple pointer-events-none absolute top-1/2 left-1/2 size-10 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/50"></span>
                <svg class="fps-finger absolute top-1/2 left-1/2 size-8 text-ink drop-shadow" viewBox="0 0 24 24" fill="#fff" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M9 11V5a1.5 1.5 0 0 1 3 0v5l4.4.9a2 2 0 0 1 1.6 2.2l-.6 4.3a3 3 0 0 1-3 2.6h-3.2a3 3 0 0 1-2.5-1.3L6 15.6a1.5 1.5 0 0 1 2.3-1.9L9 14.5z"/></svg>
            </div>
        </template>

        <!-- 4. Deal: the WhatsApp chat with the vendor. -->
        <template v-else-if="name === 'deal'">
            <div class="-mx-4 -mt-1 flex items-center gap-2 bg-emerald-700 px-4 py-2 text-white">
                <span class="grid size-7 place-items-center rounded-full bg-brand-600 font-display text-xs font-semibold ring-2 ring-white/30">S</span>
                <span class="min-w-0">
                    <span class="block truncate text-[11px] font-semibold">{{ text.vendor }}</span>
                    <span class="block text-[9px] opacity-80">{{ text.online }}</span>
                </span>
            </div>
            <div class="-mx-4 -mb-2 flex flex-1 flex-col gap-2 bg-[#efe7dc] px-3 pt-3 text-[10.5px] leading-snug">
                <p class="fps-bubble max-w-[80%] self-end rounded-xl rounded-tr-sm bg-[#d9fdd3] px-2.5 py-1.5 shadow-sm" style="--d: 200ms">{{ text.chat_ask }}<span class="ml-1.5 text-[8px] text-emerald-700">✓✓</span></p>
                <p class="fps-typing flex w-fit gap-1 rounded-xl rounded-tl-sm bg-white px-2.5 py-2 shadow-sm">
                    <span v-for="n in 3" :key="n" class="size-1.5 rounded-full bg-ink/40" :style="{ animationDelay: `${n * 0.15}s` }"></span>
                </p>
                <p class="fps-bubble max-w-[80%] self-start rounded-xl rounded-tl-sm bg-white px-2.5 py-1.5 shadow-sm" style="--d: 1.9s">{{ text.chat_reply }}</p>
                <div class="fps-bubble max-w-[80%] self-start overflow-hidden rounded-xl bg-white p-1 shadow-sm" style="--d: 2.3s">
                    <span class="block h-14 rounded-lg bg-linear-to-br from-gold-300 to-brand-300"></span>
                    <span class="flex items-center gap-1.5 px-1.5 py-1 text-[9.5px] font-semibold"><svg class="size-3 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6zM14 3v5h5"/></svg>Pakej-2026.pdf</span>
                </div>
                <p class="fps-bubble max-w-[80%] self-end rounded-xl rounded-tr-sm bg-[#d9fdd3] px-2.5 py-1.5 shadow-sm" style="--d: 3.1s">{{ text.chat_deal }}<span class="ml-1.5 text-[8px] text-emerald-700">✓✓</span></p>
            </div>
        </template>

        <!-- 5. Invite: the digital card rising out of its envelope. -->
        <template v-else-if="name === 'invite'">
            <div class="relative flex flex-1 items-end justify-center pb-2">
                <div class="fps-card absolute inset-x-3 top-1 bottom-16 flex flex-col items-center justify-center gap-1.5 rounded-xl border border-gold-400 bg-ivory p-4 text-center shadow-xl shadow-brand-900/10">
                    <span class="absolute inset-1.5 rounded-lg border border-gold-300/70"></span>
                    <svg class="absolute top-2 left-2 size-8 text-gold-400" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"><path d="M2 14C2 7 7 2 14 2M2 20C2 10 10 2 20 2"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>
                    <svg class="absolute right-2 bottom-2 size-8 rotate-180 text-gold-400" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"><path d="M2 14C2 7 7 2 14 2M2 20C2 10 10 2 20 2"/><circle cx="8" cy="8" r="2" fill="currentColor"/></svg>
                    <p class="text-[8px] font-semibold tracking-[0.25em] text-gold-600 uppercase">{{ text.invite }}</p>
                    <p class="font-script text-3xl leading-tight text-brand-600">{{ text.couple }}</p>
                    <span class="h-px w-12 bg-gold-400"></span>
                    <p class="text-[10px] text-ink-muted">{{ text.date }}</p>
                    <span class="mt-2 rounded-full bg-brand-600 px-4 py-1.5 text-[10px] font-semibold text-white">{{ text.rsvp }}</span>
                </div>
                <svg class="relative h-24 w-full" viewBox="0 0 200 96" preserveAspectRatio="none">
                    <path d="M0 10 100 60 200 10v86H0z" fill="var(--color-brand-600)"/>
                    <path d="M0 96 80 46M200 96l-80-50" stroke="var(--color-brand-700)" stroke-width="2"/>
                    <path d="M0 10 100 60 200 10" fill="none" stroke="var(--color-brand-700)" stroke-width="2"/>
                    <circle cx="100" cy="58" r="9" fill="var(--color-gold-400)" stroke="var(--color-gold-600)" stroke-width="1.5"/>
                </svg>
            </div>
        </template>

        <!-- 6. Celebrate: the day itself, guests' photos landing in the Kenangan album. -->
        <template v-else>
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <span v-for="(piece, i) in confetti" :key="i" :class="['fps-confetti absolute -top-3 h-2.5 w-1.5 rounded-[1px]', piece.color]" :style="{ left: piece.left, animationDelay: piece.delay, '--r': piece.rotate }"></span>
            </div>
            <div class="fps-rise rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 px-3 py-4 text-center text-white shadow-md shadow-brand-900/20">
                <p class="font-script text-2xl leading-tight">{{ text.celebrate }}</p>
                <p class="mt-0.5 text-[10px] opacity-85">{{ text.couple }}</p>
            </div>
            <div class="fps-rise rounded-2xl bg-white p-2.5 ring-1 ring-line" style="--d: 120ms">
                <p class="mb-2 flex items-center gap-1.5 text-[10px] font-semibold">
                    <svg class="size-3.5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3.5"/></svg>
                    {{ text.album }}
                </p>
                <div class="grid grid-cols-3 gap-1">
                    <span v-for="(tile, i) in tiles" :key="i" :class="['fps-pop aspect-square rounded-lg bg-linear-to-br', tile]" :style="{ '--d': `${0.4 + i * 0.22}s` }"></span>
                </div>
            </div>
            <div class="fps-bubble flex items-start gap-2 rounded-2xl bg-white p-2.5 ring-1 ring-line" style="--d: 1.9s">
                <svg class="fps-beat size-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21s-7.5-4.6-9.6-9.3C.9 8.3 3 4.5 6.6 4.5c2.1 0 3.6 1.2 5.4 3.2 1.8-2 3.3-3.2 5.4-3.2 3.6 0 5.7 3.8 4.2 7.2C19.5 16.4 12 21 12 21z"/></svg>
                <p class="text-[10.5px] leading-snug italic">“{{ text.wish }}”</p>
            </div>
        </template>
    </div>
</template>

<style scoped>
.fps-rise {
    animation: fps-rise 0.5s cubic-bezier(0.22, 1, 0.36, 1) var(--d, 0ms) both;
}

.fps-ring {
    stroke-dasharray: 0 100;
    animation: fps-ring 1.4s cubic-bezier(0.22, 1, 0.36, 1) 0.4s forwards;
}

.fps-tick {
    animation: fps-pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) var(--d) both;
}

.fps-fade-y {
    mask-image: linear-gradient(to bottom, transparent, #000 8%, #000 80%, transparent);
}

.fps-scroll {
    animation: fps-scroll 2.6s cubic-bezier(0.45, 0, 0.25, 1) 0.3s both;
}

.fps-press {
    animation: fps-press 0.35s ease-in-out 1.3s both;
}

.fps-ripple {
    opacity: 0;
    animation: fps-ripple 0.7s ease-out 1.35s both;
}

.fps-finger {
    animation: fps-finger 1.6s cubic-bezier(0.22, 1, 0.36, 1) 0.2s both;
}

.fps-bubble {
    animation: fps-bubble 0.4s cubic-bezier(0.34, 1.4, 0.64, 1) var(--d, 0ms) both;
}

.fps-typing {
    animation: fps-typing 1.1s ease 0.7s both;
}

.fps-typing span {
    animation: fps-dot 0.9s ease-in-out infinite;
}

.fps-card {
    animation: fps-card 1s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
}

.fps-confetti {
    animation: fps-confetti 2.4s linear infinite;
}

.fps-pop {
    animation: fps-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) var(--d) both;
}

.fps-beat {
    animation: fps-beat 1.2s ease-in-out 2.3s infinite;
}

@keyframes fps-rise {
    from { opacity: 0; transform: translateY(0.5rem); }
}

@keyframes fps-ring {
    to { stroke-dasharray: 62 100; }
}

@keyframes fps-pop {
    from { opacity: 0; transform: scale(0.5); }
}

@keyframes fps-scroll {
    to { transform: translateY(-46%); }
}

@keyframes fps-finger {
    from { opacity: 0; transform: translate(2.5rem, 3rem); }
    60% { opacity: 1; transform: translate(0.25rem, 0.25rem); }
    75% { transform: translate(0.25rem, 0.25rem) scale(0.88); }
    to { opacity: 1; transform: translate(0.25rem, 0.25rem); }
}

@keyframes fps-press {
    50% { transform: scale(0.95); filter: brightness(0.92); }
}

@keyframes fps-ripple {
    from { opacity: 0.8; transform: translate(-50%, -50%) scale(0.3); }
    to { opacity: 0; transform: translate(-50%, -50%) scale(4); }
}

@keyframes fps-bubble {
    from { opacity: 0; transform: translateY(0.5rem) scale(0.9); }
}

/* Shown while the vendor types, gone once the reply lands. */
@keyframes fps-typing {
    0% { opacity: 0; max-height: 0; padding-block: 0; margin-block: -0.25rem; }
    10%, 85% { opacity: 1; max-height: 2rem; padding-block: 0.5rem; margin-block: 0; }
    100% { opacity: 0; max-height: 0; padding-block: 0; margin-block: -0.25rem; }
}

@keyframes fps-dot {
    0%, 100% { transform: translateY(0); opacity: 0.4; }
    50% { transform: translateY(-2px); opacity: 1; }
}

@keyframes fps-card {
    from { transform: translateY(55%) scale(0.92); }
}

@keyframes fps-confetti {
    from { transform: translateY(0) rotate(0); opacity: 1; }
    to { transform: translateY(420px) rotate(calc(var(--r) + 360deg)); opacity: 0.2; }
}

@keyframes fps-beat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation: none !important;
    }

    .fps-ring {
        stroke-dasharray: 62 100;
    }

    .fps-typing {
        display: none;
    }
}
</style>
