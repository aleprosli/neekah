<script setup>
/**
 * The guest list: who was invited, who answered, and the personal link each
 * guest is sent.
 *
 * Two headline numbers, never one — the confirmed sum is what the caterer is
 * told, and the outstanding invitations are a ceiling, not an expectation.
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiEmptyState from '../ui/UiEmptyState.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    guests: { type: Array, required: true },
    walkIns: { type: Array, default: () => [] },
    sides: { type: Array, required: true },
    groups: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    importUrl: { type: String, required: true },
    cardNotice: { type: Object, default: null },
    importErrors: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const copied = ref(null);

const copyLink = async (guest) => {
    try {
        await navigator.clipboard.writeText(guest.invite_url);
        copied.value = guest.id;
        setTimeout(() => (copied.value = null), 2000);
    } catch {
        // A browser that refuses the clipboard leaves the button as it was.
    }
};

const tones = {
    attending: 'bg-emerald-100 text-emerald-800',
    declined: 'bg-red-100 text-red-800',
    pending: 'bg-surface-muted text-ink-muted',
};
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <p v-if="cardNotice" class="mt-4 rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">
        Kad jemputan belum diterbitkan, jadi pautan peribadi belum boleh dikongsi.
        <a :href="cardNotice.url" class="font-medium text-brand-700 hover:underline">Sediakan kad jemputan</a>.
    </p>

    <ul v-if="importErrors.length" class="mt-4 flex flex-col gap-1 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <li v-for="message in importErrors" :key="message">{{ message }}</li>
    </ul>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <form :action="storeUrl" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">
            <h2 class="font-display text-lg font-semibold">Tambah tetamu</h2>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Nama</span>
                    <input type="text" name="name" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Nombor telefon</span>
                    <input type="tel" name="phone" placeholder="012-345 6789" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Pihak</span>
                    <select name="side" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="side in sides" :key="side.value" :value="side.value">{{ side.label }}</option>
                    </select>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Kumpulan</span>
                    <select name="group" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="group in groups" :key="group.value" :value="group.value">{{ group.label }}</option>
                    </select>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Jemputan (pax)</span>
                    <input type="number" name="pax_invited" value="1" min="1" max="20" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <p v-if="errors.name" class="text-xs text-brand-700">{{ errors.name }}</p>

            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah tetamu</button>
        </form>

        <form :action="importUrl" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">
            <h2 class="font-display text-lg font-semibold">Tampal senarai sedia ada</h2>
            <p class="text-sm text-ink-muted">Satu tetamu satu baris: <code class="text-xs">nama, telefon, pihak, kumpulan, pax</code>. Medan selepas nama boleh dikosongkan.</p>

            <textarea
                name="rows"
                rows="6"
                placeholder="Aina Sofea, 0123456789, bride, family, 2&#10;Pak Long Rahim, , groom, family, 4"
                class="rounded-xl border border-line bg-surface px-4 py-2.5 font-mono text-xs focus:border-brand-400 focus:outline-none"
            ></textarea>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Pihak (jika kosong)</span>
                    <select name="side" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="side in sides" :key="side.value" :value="side.value">{{ side.label }}</option>
                    </select>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">Kumpulan (jika kosong)</span>
                    <select name="group" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="group in groups" :key="group.value" :value="group.value" :selected="group.value === 'other'">{{ group.label }}</option>
                    </select>
                </label>
            </div>

            <p v-if="errors.rows" class="text-xs text-brand-700">{{ errors.rows }}</p>

            <button type="submit" class="w-fit rounded-full border border-line px-6 py-2.5 text-sm font-semibold transition hover:border-brand-400">Import senarai</button>
        </form>
    </div>

    <section class="mt-8">
        <UiEmptyState
            v-if="!guests.length"
            icon="🧑‍🤝‍🧑"
            title="Senarai tetamu masih kosong"
            message="Tambah tetamu satu per satu, atau tampal senarai dari Excel atau WhatsApp. Setiap tetamu dapat pautan kad peribadi mereka sendiri."
        />

        <ul v-else class="flex flex-col gap-3">
            <li v-for="guest in guests" :key="guest.id" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-2 font-medium">
                        {{ guest.name }}
                        <span :class="['rounded-full px-2 py-0.5 text-xs font-semibold', tones[guest.status_tone]]">{{ guest.status }}</span>
                        <span v-if="guest.duplicate" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">Kemungkinan duplikasi</span>
                    </p>

                    <p class="flex flex-wrap items-center gap-x-3 text-sm text-ink-muted">
                        <span>{{ guest.side }} · {{ guest.group }}</span>
                        <span>Jemputan {{ guest.pax_invited }} orang</span>
                        <span v-if="guest.phone">{{ guest.phone }}</span>
                    </p>

                    <template v-if="guest.rsvp">
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ guest.rsvp.summary }}
                            <span v-if="guest.rsvp.soft_matched" class="text-amber-700">· dipadan melalui nombor telefon</span>
                        </p>

                        <form v-if="guest.rsvp.soft_matched" :action="guest.rsvp.detach_url" method="POST" class="mt-1">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="_method" value="PUT">
                            <input type="hidden" name="detach" value="1">
                            <button type="submit" class="text-xs font-medium text-ink-muted underline hover:text-brand-700">Bukan orang ini? Buang padanan</button>
                        </form>

                        <p v-if="guest.rsvp.message" class="mt-1 text-sm text-ink-muted italic">“{{ guest.rsvp.message }}”</p>
                    </template>

                    <p v-else-if="guest.shared_at" class="mt-1 text-xs text-ink-muted">
                        Anda kongsi pada {{ guest.shared_at }}. WhatsApp tidak memberitahu kami sama ada mesej sampai atau dibaca.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2 self-start">
                    <template v-if="guest.invite_url">
                        <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-semibold transition hover:border-brand-400" @click="copyLink(guest)">
                            {{ copied === guest.id ? 'Disalin!' : 'Salin pautan' }}
                        </button>

                        <form :action="guest.share_url" method="POST">
                            <input type="hidden" name="_token" :value="csrf">
                            <button type="submit" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">WhatsApp</button>
                        </form>
                    </template>

                    <form v-if="guest.shared_at" :action="guest.unshare_url" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-xs font-medium text-ink-muted hover:text-brand-700">Buang tanda hantar</button>
                    </form>

                    <UiConfirm
                        :action="guest.destroy_url"
                        method="DELETE"
                        tone="danger"
                        title="Padam tetamu ini?"
                        :message="`${guest.name} akan dibuang dari senarai. Jawapan RSVP mereka kekal.`"
                        confirm-label="Padam"
                        trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                        :csrf="csrf"
                    >Padam</UiConfirm>
                </div>
            </li>
        </ul>
    </section>

    <section v-if="walkIns.length" class="mt-8">
        <h2 class="font-display text-lg font-semibold">Jawapan tanpa nama dalam senarai</h2>
        <p class="mt-1 text-sm text-ink-muted">Jawapan ini datang tanpa pautan peribadi dan kami tidak dapat memadankannya dengan sesiapa. Ia tetap dikira melainkan anda keluarkan.</p>

        <ul class="mt-3 flex flex-col gap-3">
            <li v-for="rsvp in walkIns" :key="rsvp.id" class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="font-medium">
                        {{ rsvp.name }}
                        <span v-if="!rsvp.counted" class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-ink-muted">Tidak dikira</span>
                    </p>
                    <p class="text-sm text-ink-muted">{{ rsvp.summary }}</p>
                    <p v-if="rsvp.message" class="mt-1 text-sm text-ink-muted italic">“{{ rsvp.message }}”</p>
                </div>

                <form :action="rsvp.update_url" method="POST" class="shrink-0">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="counted" :value="rsvp.counted ? 0 : 1">
                    <button type="submit" class="rounded-full border border-line px-4 py-1.5 text-xs font-semibold transition hover:border-brand-400">
                        {{ rsvp.counted ? 'Keluarkan dari kiraan' : 'Kira semula' }}
                    </button>
                </form>
            </li>
        </ul>
    </section>
</template>
