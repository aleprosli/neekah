document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) {
        document.getElementById(opener.dataset.dialogOpen)?.showModal();
        return;
    }

    const closer = event.target.closest('[data-dialog-close]');
    if (closer) {
        closer.closest('dialog')?.close();
        return;
    }

    if (event.target instanceof HTMLDialogElement) {
        event.target.close();
    }
});

document.addEventListener(
    'toggle',
    (event) => {
        const details = event.target;
        if (!(details instanceof HTMLDetailsElement) || !details.open || details.dataset.popover === undefined) {
            return;
        }

        document.querySelectorAll('details[data-popover][open]').forEach((other) => {
            if (other !== details) {
                other.open = false;
            }
        });
    },
    true,
);

document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-popover][open]').forEach((details) => {
        if (!details.contains(event.target)) {
            details.open = false;
        }
    });
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) {
        return;
    }

    try {
        await navigator.clipboard.writeText(button.dataset.copy);
    } catch {
        return;
    }

    const original = button.textContent;
    button.textContent = 'Disalin!';
    setTimeout(() => {
        button.textContent = original;
    }, 2000);
});
