<script setup>
/**
 * The Cloudflare Turnstile widget, rendered explicitly.
 *
 * Turnstile's automatic mode scans the document once, as its script loads, and
 * never looks again. A widget that a Vue component adds afterwards is therefore
 * never drawn — no widget, no token, and a form that refuses every submission.
 * The same is true of a page swapped in by navigation.js.
 *
 * So the script is loaded in explicit mode and the widget is rendered here, by
 * this component, whenever it appears.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    siteKey: { type: String, required: true },
    // Turnstile draws its own wording, so it has to be told which language the
    // page is in; left alone it said "sahkan anda manusia" on /en. Taken from
    // the document rather than a prop, so every place that mounts this widget
    // gets it right without having to remember to pass it.
    language: { type: String, default: null },
});

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

const element = ref(null);
const widget = ref(null);

/** Resolves once Turnstile's script has loaded, loading it the first time. */
const turnstileReady = () => {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    window.nkTurnstileReady ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.defer = true;
        script.addEventListener('load', () => resolve(window.turnstile));
        script.addEventListener('error', reject);
        document.head.append(script);
    });

    return window.nkTurnstileReady;
};

onMounted(async () => {
    try {
        const turnstile = await turnstileReady();

        widget.value = turnstile.render(element.value, {
            sitekey: props.siteKey,
            language: props.language ?? document.documentElement.lang ?? 'ms',
            size: 'flexible',
        });
    } catch {
        // Cloudflare unreachable: the rule on the server lets the submission
        // through rather than turning a captcha outage into an outage of
        // registration, so an empty space here is the right outcome.
    }
});

onBeforeUnmount(() => {
    if (widget.value !== null) window.turnstile?.remove(widget.value);
});
</script>

<template>
    <!-- The widget sizes itself to its container, so it never pushes a phone layout wider than the screen. -->
    <div class="min-w-0 overflow-hidden">
        <div ref="element"></div>
    </div>
</template>
