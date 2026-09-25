import { ref } from 'vue';

const open = ref(false);

export function useCommandPalette() {
    function toggle() {
        open.value = !open.value;
    }

    function openPalette() {
        open.value = true;
    }

    function closePalette() {
        open.value = false;
    }

    return { open, toggle, openPalette, closePalette };
}
