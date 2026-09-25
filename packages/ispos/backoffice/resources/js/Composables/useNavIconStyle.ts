/** Neon glow palette — vivid hues for luminous sidebar icon tiles. */
const navIconPalette: string[] = [
    '249 115 22',
    '234 179 8',
    '245 158 11',
    '236 72 153',
    '34 211 238',
    '59 130 246',
    '168 85 247',
    '52 211 153',
    '244 63 94',
    '20 184 166',
    '132 204 22',
    '99 102 241',
    '251 146 60',
    '56 189 248',
];

function hashSeed(seed: string): number {
    let hash = 0;

    for (let index = 0; index < seed.length; index++) {
        hash = (hash * 31 + seed.charCodeAt(index)) >>> 0;
    }

    return hash;
}

function getNavIconAccent(seed: string): string {
    return navIconPalette[hashSeed(seed) % navIconPalette.length];
}

export function navIconStyle(seed: string, _active = false): Record<string, string> {
    return { '--nav-icon-accent': getNavIconAccent(seed) };
}

/** Dedicated stroke for destructive / sign-out actions */
export function navIconDangerStyle(): Record<string, string> {
    return { '--nav-icon-accent': '244 63 94' };
}
