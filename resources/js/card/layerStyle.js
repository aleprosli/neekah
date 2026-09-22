/**
 * One layer of a card design, as CSS.
 *
 * A canvas is designed at 1080 × 1920 and rendered at whatever width it is given,
 * so nothing here is expressed in pixels: positions and sizes are percentages of
 * the scene and every length is cqw (1% of the scene's own width). That is what
 * lets the same layers be a 110px thumbnail in the editor and a full-screen card
 * on a phone without a second set of numbers.
 *
 * Colours and type faces are stored as role tokens ("role:acc", "role:d") and
 * resolved to CSS variables here, so swapping a palette repaints the card without
 * touching a single layer.
 */

/** Trim the float noise out of generated CSS. */
const n = (value) => {
    const rounded = Math.round((Number(value) || 0) * 1000) / 1000;

    return String(rounded);
};

export const colour = (value) => {
    if (typeof value !== 'string' || value === '') return null;

    return value.startsWith('role:') ? `var(--c-${value.slice(5)})` : value;
};

export const fontFamily = (value) => {
    if (typeof value !== 'string' || value === '') return 'var(--f-r)';

    return value.startsWith('role:') ? `var(--f-${value.slice(5)})` : `"${value}", serif`;
};

/** A length from the design's own grid, in scene-relative units. */
export const cq = (px, width) => `${n((px / width) * 100)}cqw`;

/**
 * A role colour at partial opacity. color-mix keeps this working with variables,
 * which rgba() could not: the colour is not known until the palette is applied.
 */
export const alpha = (value, opacity) => `color-mix(in srgb, ${colour(value)} ${n(opacity)}%, transparent)`;

/** Position, size, opacity, rotation and filters — shared by every layer type. */
export const boxStyle = (scene, layer) => {
    const width = Math.max(1, scene.width);
    const height = Math.max(1, scene.height);

    const style = {
        left: `${n((layer.x / width) * 100)}%`,
        top: `${n((layer.y / height) * 100)}%`,
        width: `${n((layer.w / width) * 100)}%`,
        height: `${n((layer.h / height) * 100)}%`,
        opacity: n(layer.opacity / 100),
    };

    if (layer.blend && layer.blend !== 'normal') style.mixBlendMode = layer.blend;
    if (layer.rotation) style.transform = `rotate(${n(layer.rotation)}deg)`;

    const filters = [];
    if (layer.blur > 0) filters.push(`blur(${cq(layer.blur, width)})`);
    if (layer.type !== 'text' && layer.shadow) {
        const s = layer.shadow;
        filters.push(`drop-shadow(${cq(s.x, width)} ${cq(s.y, width)} ${cq(s.blur, width)} ${alpha(s.color, s.opacity)})`);
    }
    if (filters.length) style.filter = filters.join(' ');

    return style;
};

const VERTICAL = { top: 'flex-start', middle: 'center', bottom: 'flex-end' };

export const textStyle = (scene, layer) => {
    const width = Math.max(1, scene.width);

    const style = {
        fontFamily: fontFamily(layer.font),
        fontSize: cq(layer.size, width),
        fontWeight: layer.weight,
        fontStyle: layer.italic ? 'italic' : 'normal',
        color: colour(layer.color),
        textAlign: layer.align,
        letterSpacing: `${n(layer.spacing)}em`,
        lineHeight: n(layer.lineHeight),
        textTransform: layer.transform,
        alignItems: VERTICAL[layer.valign] ?? 'center',
    };

    if (layer.shadow) {
        const s = layer.shadow;
        style.textShadow = `${cq(s.x, width)} ${cq(s.y, width)} ${cq(s.blur, width)} ${alpha(s.color, s.opacity)}`;
    }

    return style;
};

/** The mask and border an image or shape layer is cut to. */
export const frameStyle = (scene, layer) => {
    const width = Math.max(1, scene.width);
    const style = {};

    const shape =
        layer.type === 'image'
            ? layer.shape
            : ['circle', 'ring'].includes(layer.kind) && layer.radius === 0
              ? 'circle'
              : layer.radius > 0
                ? 'rounded'
                : 'rect';

    if (shape === 'rounded') style.borderRadius = cq(layer.radius, width);
    if (shape === 'circle' || shape === 'oval') style.borderRadius = '50%';
    if (shape === 'arch') style.borderRadius = '999cqw 999cqw 0 0';

    if (layer.border) style.border = `${cq(layer.border.width, width)} solid ${colour(layer.border.color)}`;

    return style;
};

export const fillStyle = (layer) => {
    const fill = colour(layer.fill);
    const fill2 = colour(layer.fill2) ?? fill;

    switch (layer.kind) {
        case 'glow':
            return `radial-gradient(circle, ${fill} 0%, transparent 70%)`;
        case 'ring':
            return 'transparent';
        case 'fade':
            return `linear-gradient(${n(layer.angle)}deg, ${fill}, transparent)`;
        default:
            return `linear-gradient(${n(layer.angle)}deg, ${fill}, ${fill2})`;
    }
};

/** A tinted SVG ornament: the artwork is the mask, the colour is a gradient. */
export const ornamentStyle = (scene, layer) => {
    const width = Math.max(1, scene.width);
    const gradient = `linear-gradient(${n(layer.angle)}deg, ${colour(layer.color)}, ${colour(layer.color2) ?? colour(layer.color)})`;
    const size = layer.tile > 0 ? `0 0/${cq(layer.tile, width)} ${cq(layer.tile, width)} repeat` : 'center/contain no-repeat';
    const mask = `url('${layer.src}') ${size}`;

    // The hyphenated key sets the property directly, which is what Vue's style
    // binding needs for a prefixed property; a camelCase one renders as the
    // invalid "webkit-mask" anywhere the markup is generated as a string.
    return { background: gradient, '-webkit-mask': mask, mask };
};
