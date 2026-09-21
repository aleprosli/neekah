import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * A popup list anchored to the button that opens it.
 *
 * The list is teleported and positioned in viewport coordinates, because a
 * dropdown inside an overflow-hidden section (the marketplace hero) would
 * otherwise be cut off. Inside a <dialog> it teleports into that dialog
 * instead of the body: a modal dialog lives in the browser's top layer and
 * would paint over anything left behind.
 */
export const useAnchoredMenu = ({ minWidth = 232 } = {}) => {
    const root = ref(null);
    const trigger = ref(null);
    const menu = ref(null);
    const open = ref(false);
    const style = ref({});
    const target = ref('body');

    const place = () => {
        const rect = trigger.value?.getBoundingClientRect();
        if (!rect) return;

        const width = Math.max(rect.width, minWidth);
        const left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);

        style.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${left}px`,
            width: `${width}px`,
            maxHeight: `${Math.max(160, window.innerHeight - rect.bottom - 24)}px`,
        };
    };

    const close = () => {
        open.value = false;
    };

    const toggle = () => {
        open.value = !open.value;

        if (open.value) place();
    };

    // The list is teleported out of the component, so a click on an option is
    // outside root: close only when the click missed both.
    const onPointerDown = (event) => {
        if (!root.value?.contains(event.target) && !menu.value?.contains(event.target)) close();
    };

    const onViewportChange = () => {
        if (open.value) place();
    };

    onMounted(() => {
        target.value = root.value?.closest('dialog') ?? 'body';
        document.addEventListener('pointerdown', onPointerDown);
        window.addEventListener('resize', onViewportChange);
        window.addEventListener('scroll', onViewportChange, true);
    });

    onBeforeUnmount(() => {
        document.removeEventListener('pointerdown', onPointerDown);
        window.removeEventListener('resize', onViewportChange);
        window.removeEventListener('scroll', onViewportChange, true);
    });

    return { root, trigger, menu, open, style, target, place, close, toggle };
};
