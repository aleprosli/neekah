/**
 * A tap on a vendor's phone number, reported for their analytics. A tel: link
 * cannot go through a counting redirect the way WhatsApp does, so it sends a
 * beacon beside it. Delegated from the document, so it keeps working on pages
 * reached through navigation.js, and it never holds up the call itself.
 */
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-track-phone]') : null;
    const token = link?.dataset.trackToken;

    if (!link || !token || !navigator.sendBeacon) {
        return;
    }

    const body = new FormData();
    body.append('_token', token);
    navigator.sendBeacon(link.dataset.trackPhone, body);
});
