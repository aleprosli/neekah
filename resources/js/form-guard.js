/**
 * One submission per form, and a visible sign that it is on its way.
 *
 * Every form in Neekah posts the ordinary way, and a slow response used to
 * look like nothing happened: people tapped again, and a sign-up went through
 * twice. This runs once for the whole document, so every form gets it —
 * including forms Vue draws and dialogs it teleports — without each component
 * having to remember.
 *
 * It never uses the `disabled` attribute. A disabled submit button is dropped
 * from the form data, and some forms tell the server which button was
 * pressed. The button is marked busy instead, and a second submit is simply
 * cancelled.
 */

/** A response slower than this unlocks the form, so a lost request never leaves it stuck. */
const RELEASE_AFTER_MS = 20000;

const busyButton = (form, submitter) =>
    submitter ?? form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');

const release = (form) => {
    delete form.dataset.submitting;
    form.querySelectorAll('[data-busy]').forEach((button) => {
        delete button.dataset.busy;
        button.removeAttribute('aria-busy');
    });
};

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    // A component that took the submit over itself (the upload progress bar
    // posts over XHR) already said so, and draws its own progress.
    if (event.defaultPrevented) {
        return;
    }

    // Searching and filtering change nothing, and a second tap there is harmless.
    if ((form.getAttribute('method') || 'get').toLowerCase() === 'get' || form.target === '_blank') {
        return;
    }

    if (form.dataset.submitting) {
        event.preventDefault();
        return;
    }

    form.dataset.submitting = 'true';

    const button = busyButton(form, event.submitter);
    if (button) {
        button.dataset.busy = 'true';
        button.setAttribute('aria-busy', 'true');
    }

    setTimeout(() => release(form), RELEASE_AFTER_MS);
});

// Back to a page the browser kept in memory: the form is ready to use again.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('form[data-submitting]').forEach(release);
    }
});
