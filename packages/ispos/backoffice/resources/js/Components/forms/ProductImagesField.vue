<script setup lang="ts">
import Button from '@/Components/ui/Button.vue';
import { toast } from '@/Composables/useToast';
import type { ProductImageRow } from '@/types/product';
import { nextClientKey } from '@/types/product';
import { computed, ref } from 'vue';

const props = defineProps<{
    modelValue: ProductImageRow[];
    errorPrefix?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [ProductImageRow[]];
}>();

const inputRef = ref<HTMLInputElement | null>(null);

const MAX_IMAGES = 10;
const MAX_IMAGE_BYTES = 1024 * 1024;

const images = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const canAddMore = computed(() => images.value.length < MAX_IMAGES);

function openPicker() {
    inputRef.value?.click();
}

function onFilesSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

    if (!files.length) {
        return;
    }

    const remaining = MAX_IMAGES - images.value.length;
    if (remaining <= 0) {
        toast.warning(`You can upload up to ${MAX_IMAGES} images per product.`);
        return;
    }

    const accepted = files.slice(0, remaining);
    const next = [...images.value];
    let rejectedSize = 0;

    for (const file of accepted) {
        if (file.size > MAX_IMAGE_BYTES) {
            rejectedSize += 1;
            continue;
        }

        const isFirst = next.length === 0;
        next.push({
            client_key: nextClientKey('img'),
            is_default: isFirst,
            sort_order: String(next.length),
            preview_url: URL.createObjectURL(file),
            name: file.name,
            file,
        });
    }

    if (rejectedSize > 0) {
        toast.error(`${rejectedSize} file(s) exceeded the 1 MB size limit.`);
    }

    if (next.length === images.value.length) {
        return;
    }

    if (!next.some((row) => row.is_default) && next.length) {
        next[0].is_default = true;
    }

    images.value = next;
}

function setDefault(index: number) {
    images.value = images.value.map((row, rowIndex) => ({
        ...row,
        is_default: rowIndex === index,
    }));
}

function removeImage(index: number) {
    const removed = images.value[index];
    if (removed.preview_url?.startsWith('blob:')) {
        URL.revokeObjectURL(removed.preview_url);
    }

    const next = images.value.filter((_, rowIndex) => rowIndex !== index);
    if (removed.is_default && next.length) {
        next[0].is_default = true;
    }

    images.value = next.map((row, rowIndex) => ({
        ...row,
        sort_order: String(rowIndex),
    }));
}

function formatSize(bytes?: number): string {
    if (!bytes) {
        return '';
    }

    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-ink">Product images</p>
                <p class="text-xs text-ink-muted">Upload up to {{ MAX_IMAGES }} images, max 1 MB each. Pick one as the default appearance image.</p>
            </div>
            <Button type="button" variant="secondary" :disabled="!canAddMore" @click="openPicker">
                Upload images
            </Button>
        </div>

        <input
            ref="inputRef"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            multiple
            class="hidden"
            @change="onFilesSelected"
        />

        <div v-if="!images.length" class="rounded-xl border border-dashed border-line/80 bg-surface-muted/30 px-4 py-8 text-center">
            <p class="text-sm font-medium text-ink">No images yet</p>
            <p class="mt-1 text-xs text-ink-muted">JPEG, PNG, WebP, or GIF up to 1 MB.</p>
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="(image, index) in images"
                :key="image.client_key"
                class="overflow-hidden rounded-xl border bg-surface"
                :class="image.is_default ? 'border-accent/40 ring-2 ring-accent/20' : 'border-line/80'"
            >
                <div class="relative aspect-[4/3] bg-surface-muted/40">
                    <img
                        :src="image.preview_url || image.url"
                        :alt="image.name || 'Product image'"
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-if="image.is_default"
                        class="absolute left-2 top-2 rounded-md bg-accent px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white dark:text-zinc-950"
                    >
                        Default
                    </span>
                </div>
                <div class="space-y-2 p-3">
                    <p class="truncate text-sm font-medium text-ink">{{ image.name || 'Image' }}</p>
                    <p v-if="image.size_bytes" class="text-xs text-ink-muted">{{ formatSize(image.size_bytes) }}</p>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="!image.is_default"
                            type="button"
                            variant="ghost"
                            class="!px-2 !py-1 text-xs"
                            @click="setDefault(index)"
                        >
                            Set as default
                        </Button>
                        <Button type="button" variant="ghost" class="!px-2 !py-1 text-xs" @click="removeImage(index)">
                            Remove
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
