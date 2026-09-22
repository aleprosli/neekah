<script setup>
/**
 * The guest's reply.
 *
 * It is a real form posting to the card's own RSVP route, so it still works with
 * no JavaScript; when JavaScript is there the answer is sent in the background,
 * because a reload would shut the card the guest just opened and scroll them back
 * to the sealed cover.
 *
 * A guest who arrived on their personal link has their name and how many they were
 * invited for filled in, and their token travels with the reply so the couple's
 * guest list ticks the right row.
 */
import { ref } from 'vue';
import CardSection from './CardSection.vue';

const props = defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
    preview: { type: Boolean, default: false },
    csrf: { type: String, default: null },
});

const guest = props.widget.guest;

const labelOrKey = (key) => props.labels[key] ?? key;

const form = ref({
    name: guest?.name ?? '',
    phone: '',
    attending: '1',
    pax: guest?.pax_invited ?? 1,
    message: '',
});

const sending = ref(false);
const done = ref(false);
const errors = ref([]);

const send = async (event) => {
    if (props.preview || !props.widget.action) {
        event.preventDefault();
        return;
    }

    event.preventDefault();
    sending.value = true;
    errors.value = [];

    const body = new FormData();
    Object.entries(form.value).forEach(([field, value]) => body.append(field, value));
    if (guest?.token) body.append('u', guest.token);
    body.append('_token', props.csrf ?? '');

    try {
        const response = await fetch(props.widget.action, {
            method: 'POST',
            body,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (response.ok) {
            done.value = true;
        } else {
            const data = await response.json().catch(() => ({}));
            errors.value = Object.values(data.errors ?? {}).flat();
            if (!errors.value.length) errors.value = [data.message ?? labelOrKey('rsvp_closed')];
        }
    } catch {
        // The form is still a real form: let the browser post it the ordinary way.
        event.target.submit();
        return;
    } finally {
        sending.value = false;
    }
};
</script>

<template>
    <CardSection :heading="widget.heading">
        <p v-if="!widget.open" class="nkc-note">{{ labels.rsvp_closed }}</p>

        <p v-else-if="done" class="nkc-thanks">{{ labels.rsvp_thanks }}</p>

        <form v-else class="nkc-form" :action="widget.action ?? '#'" method="POST" @submit="send">
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="guest?.token" type="hidden" name="u" :value="guest.token">

            <p v-if="guest" class="nkc-guest">
                <span>{{ labels.rsvp_invited_as }}</span>
                <strong>{{ guest.name }}</strong>
            </p>
            <p v-if="guest?.has_responded" class="nkc-note">{{ labels.rsvp_answered }}</p>

            <ul v-if="errors.length" class="nkc-errors">
                <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>

            <label class="nkc-label">
                {{ labels.rsvp_name }}
                <input v-model="form.name" class="nkc-input" type="text" name="name" required maxlength="80">
            </label>

            <label class="nkc-label">
                {{ labels.rsvp_phone }}
                <input v-model="form.phone" class="nkc-input" type="tel" name="phone" maxlength="30">
            </label>

            <div class="nkc-choice">
                <button type="button" :class="{ on: form.attending === '1' }" @click="form.attending = '1'">{{ labels.rsvp_attending }}</button>
                <button type="button" :class="{ on: form.attending === '0' }" @click="form.attending = '0'">{{ labels.rsvp_not_attending }}</button>
                <input type="hidden" name="attending" :value="form.attending">
            </div>

            <label v-if="form.attending === '1'" class="nkc-label">
                {{ labels.rsvp_pax }}
                <input v-model="form.pax" class="nkc-input" type="number" name="pax" min="1" :max="guest?.pax_invited ?? 20">
            </label>

            <label class="nkc-label">
                {{ labels.rsvp_message }}
                <textarea v-model="form.message" class="nkc-input" name="message" rows="3" maxlength="500" />
            </label>

            <button type="submit" class="nkc-btn" :disabled="sending">{{ sending ? labels.rsvp_sending : labels.rsvp_submit }}</button>

            <p v-if="widget.deadline" class="nkc-note">{{ labels.rsvp_deadline?.replace(':date', widget.deadline) }}</p>
        </form>
    </CardSection>
</template>
