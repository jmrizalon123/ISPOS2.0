<script setup lang="ts">
import { computed, ref } from 'vue';
import { useFormPageTitle } from '@/Composables/useFormPageTitle';
import { useFormTabErrors, type FormTabErrorConfig } from '@/Composables/useFormTabErrors';
import { useLocale } from '@/Composables/useLocale';
import { useModulePage } from '@/Composables/useModulePage';
import FormActionBar from '@/Components/forms/FormActionBar.vue';
import FormField from '@/Components/forms/FormField.vue';
import FormSection from '@/Components/forms/FormSection.vue';
import FormShell from '@/Components/forms/FormShell.vue';
import FormTabs from '@/Components/forms/FormTabs.vue';
import FormTextarea from '@/Components/forms/FormTextarea.vue';
import FormToggle from '@/Components/forms/FormToggle.vue';
import Input from '@/Components/ui/Input.vue';
import Select from '@/Components/ui/Select.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { confirmDelete, confirmSave } from '@/Composables/useConfirm';
import { usePermissions } from '@/Composables/usePermissions';
import Button from '@/Components/ui/Button.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

type NavLink = { label: string; target: string };
type TrustItem = { title: string; subtitle?: string };
type HeroSlide = {
    image: string | File | null;
    image_url?: string | null;
    preview_url?: string | null;
    remove_image?: boolean;
    eyebrow: string;
    headline: string;
    subheadline: string;
    primary_cta_label: string;
    primary_cta_target: string;
    secondary_cta_label: string;
    secondary_cta_target: string;
};

interface HomepageContent {
    show_announcement: boolean;
    announcement_items: string[];
    search_placeholder: string;
    nav_links: NavLink[];
    hero: {
        eyebrow: string;
        headline: string;
        subheadline: string;
        primary_cta_label: string;
        primary_cta_target: string;
        secondary_cta_label: string;
        secondary_cta_target: string;
        social_proof: string;
        show_social_proof: boolean;
        show_float_cards: boolean;
        autoplay: boolean;
        interval_ms: number;
        slides: HeroSlide[];
    };
    trust: { show: boolean; items: TrustItem[] };
    categories_section: { show: boolean; title: string; view_all_label: string; cta_label: string };
    new_arrivals: { show: boolean; title: string; view_all_label: string };
    bestsellers: {
        show: boolean;
        title: string;
        view_all_label: string;
        badge_label: string;
        quick_add_label: string;
    };
    promo_sale: {
        show: boolean;
        eyebrow: string;
        title: string;
        subtitle: string;
        cta_label: string;
        cta_target: string;
        countdown_ends_at: string;
    };
    promo_collection: {
        show: boolean;
        eyebrow: string;
        title: string;
        subtitle: string;
        cta_label: string;
        cta_target: string;
        image_url: string;
    };
    footer_trust: { show: boolean; items: Array<{ title: string }> };
    footer_tagline: string;
    about_text: string;
    contact_text: string;
}

interface SettingRecord {
    id: string;
    store_id: string;
    slug: string;
    is_published: boolean;
    storefront_name: string | null;
    tagline: string | null;
    description: string | null;
    logo: string | null;
    hero_image: string | null;
    logo_url?: string | null;
    hero_image_url?: string | null;
    primary_color: string | null;
    accent_color: string | null;
    accept_pickup: boolean;
    accept_delivery: boolean;
    accept_dine_in: boolean;
    min_order_amount: string | number | null;
    delivery_fee: string | number | null;
    free_delivery_threshold: string | number | null;
    preparation_minutes: number | null;
    payment_methods: string[] | null;
    auto_accept_orders: boolean;
    announcement: string | null;
    support_phone: string | null;
    support_email: string | null;
    orders_open_at: string | null;
    orders_close_at: string | null;
    orders_open_days: string[] | null;
    seo_title: string | null;
    seo_description: string | null;
    homepage?: Partial<HomepageContent> | null;
    status: string;
    public_url?: string;
}

const page = useModulePage('onlineStoreSettings');
const { t, submit } = useLocale();
const { can } = usePermissions();

const props = defineProps<{
    setting: SettingRecord | null;
    homepageDefaults: HomepageContent;
    companies: Array<{ id: string; name: string; display_name?: string | null }>;
    stores: Array<{ id: string; store_name: string; store_code: string; company_id: string; store_category?: string | null }>;
}>();

const isEdit = computed(() => !!props.setting);
const pageTitle = useFormPageTitle('storefront', isEdit);
const activeTab = ref('identity');
const deleting = ref(false);

const tabs = [
    { key: 'identity', label: 'Identity', description: 'Name, slug, and publish status' },
    { key: 'operations', label: 'Operations', description: 'Fulfillment, fees, and payments' },
    { key: 'branding', label: 'Branding', description: 'Colors and media paths' },
    { key: 'hours', label: 'Hours', description: 'Order window and support' },
    { key: 'homepage', label: 'Homepage', description: 'Announcement, nav, and hero' },
    { key: 'merchandising', label: 'Merchandising', description: 'Sections and promo banners' },
    { key: 'content', label: 'Content & SEO', description: 'About, footer, and search listing' },
];

const activeTabMeta = computed(() => tabs.find((item) => item.key === activeTab.value) ?? tabs[0]);

const days = [
    { value: 'mon', label: 'Mon' },
    { value: 'tue', label: 'Tue' },
    { value: 'wed', label: 'Wed' },
    { value: 'thu', label: 'Thu' },
    { value: 'fri', label: 'Fri' },
    { value: 'sat', label: 'Sat' },
    { value: 'sun', label: 'Sun' },
];

const paymentOptions = [
    { value: 'cod', label: 'Cash on delivery' },
    { value: 'pay_at_store', label: 'Pay at store' },
    { value: 'gcash', label: 'GCash' },
    { value: 'card', label: 'Card' },
    { value: 'bank_transfer', label: 'Bank transfer' },
];

const sectionTargets = [
    { value: 'home', label: 'Home' },
    { value: 'shop', label: 'Shop' },
    { value: 'arrivals', label: 'New Arrivals' },
    { value: 'bestsellers', label: 'Best Sellers' },
    { value: 'categories', label: 'Categories' },
    { value: 'about', label: 'About' },
    { value: 'contact', label: 'Contact' },
];

function timeValue(value: string | null | undefined): string {
    if (!value) return '';
    return value.length >= 5 ? value.slice(0, 5) : value;
}

function deepMergeHomepage(stored?: Partial<HomepageContent> | null): HomepageContent {
    // Avoid structuredClone — Inertia/Vue props are Proxies and cannot be cloned.
    const base = props.homepageDefaults;
    return {
        ...base,
        ...stored,
        announcement_items: stored?.announcement_items?.length
            ? [...stored.announcement_items]
            : [...(base.announcement_items ?? [])],
        nav_links: stored?.nav_links?.length
            ? stored.nav_links.map((l) => ({ ...l }))
            : (base.nav_links ?? []).map((l) => ({ ...l })),
        hero: {
            ...(base.hero ?? {}),
            ...(stored?.hero ?? {}),
            autoplay: stored?.hero?.autoplay ?? base.hero?.autoplay ?? true,
            interval_ms: stored?.hero?.interval_ms ?? base.hero?.interval_ms ?? 5500,
            slides: (stored?.hero?.slides?.length
                ? stored.hero.slides
                : (base.hero?.slides ?? [])
            ).map((slide) => ({
                image: typeof slide.image === 'string' ? slide.image : '',
                image_url: slide.image_url ?? null,
                preview_url: null,
                remove_image: false,
                eyebrow: slide.eyebrow ?? '',
                headline: slide.headline ?? '',
                subheadline: slide.subheadline ?? '',
                primary_cta_label: slide.primary_cta_label ?? '',
                primary_cta_target: slide.primary_cta_target ?? 'shop',
                secondary_cta_label: slide.secondary_cta_label ?? '',
                secondary_cta_target: slide.secondary_cta_target ?? 'categories',
            })),
        },
        trust: {
            ...(base.trust ?? {}),
            ...(stored?.trust ?? {}),
            items: stored?.trust?.items?.length
                ? stored.trust.items.map((i) => ({ ...i }))
                : (base.trust?.items ?? []).map((i) => ({ ...i })),
        },
        categories_section: { ...(base.categories_section ?? {}), ...(stored?.categories_section ?? {}) },
        new_arrivals: { ...(base.new_arrivals ?? {}), ...(stored?.new_arrivals ?? {}) },
        bestsellers: { ...(base.bestsellers ?? {}), ...(stored?.bestsellers ?? {}) },
        promo_sale: {
            ...(base.promo_sale ?? {}),
            ...(stored?.promo_sale ?? {}),
            countdown_ends_at: stored?.promo_sale?.countdown_ends_at
                ? datetimeLocal(stored.promo_sale.countdown_ends_at)
                : '',
        },
        promo_collection: {
            ...(base.promo_collection ?? {}),
            ...(stored?.promo_collection ?? {}),
            image_url: stored?.promo_collection?.image_url ?? base.promo_collection?.image_url ?? '',
        },
        footer_trust: {
            ...(base.footer_trust ?? {}),
            ...(stored?.footer_trust ?? {}),
            items: stored?.footer_trust?.items?.length
                ? stored.footer_trust.items.map((i) => ({ ...i }))
                : (base.footer_trust?.items ?? []).map((i) => ({ ...i })),
        },
        footer_tagline: stored?.footer_tagline ?? base.footer_tagline ?? '',
        about_text: stored?.about_text ?? base.about_text ?? '',
        contact_text: stored?.contact_text ?? base.contact_text ?? '',
    };
}

function datetimeLocal(value: string | null | undefined): string {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value).slice(0, 16);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const initialHomepage = deepMergeHomepage(props.setting?.homepage);

const form = useForm({
    store_id: props.setting?.store_id ?? props.stores[0]?.id ?? '',
    slug: props.setting?.slug ?? '',
    is_published: props.setting?.is_published ?? false,
    storefront_name: props.setting?.storefront_name ?? '',
    tagline: props.setting?.tagline ?? '',
    description: props.setting?.description ?? '',
    logo: props.setting?.logo ?? '',
    hero_image: props.setting?.hero_image ?? '',
    primary_color: props.setting?.primary_color ?? '#ff5a1f',
    accent_color: props.setting?.accent_color ?? '#f59e0b',
    accept_pickup: props.setting?.accept_pickup ?? true,
    accept_delivery: props.setting?.accept_delivery ?? false,
    accept_dine_in: props.setting?.accept_dine_in ?? false,
    min_order_amount: props.setting?.min_order_amount ?? 0,
    delivery_fee: props.setting?.delivery_fee ?? 0,
    free_delivery_threshold: props.setting?.free_delivery_threshold ?? '',
    preparation_minutes: props.setting?.preparation_minutes ?? 30,
    payment_methods: props.setting?.payment_methods ?? ['cod', 'pay_at_store'],
    auto_accept_orders: props.setting?.auto_accept_orders ?? false,
    announcement: props.setting?.announcement ?? '',
    support_phone: props.setting?.support_phone ?? '',
    support_email: props.setting?.support_email ?? '',
    orders_open_at: timeValue(props.setting?.orders_open_at),
    orders_close_at: timeValue(props.setting?.orders_close_at),
    orders_open_days: props.setting?.orders_open_days ?? ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
    seo_title: props.setting?.seo_title ?? '',
    seo_description: props.setting?.seo_description ?? '',
    homepage: initialHomepage,
    status: props.setting?.status ?? 'active',
});

const tabErrorConfig: FormTabErrorConfig[] = [
    { key: 'identity', requiredFields: ['store_id', 'storefront_name', 'slug', 'status'], errorPrefixes: ['tagline', 'description', 'is_published'] },
    { key: 'operations', errorPrefixes: ['accept_pickup', 'accept_delivery', 'accept_dine_in', 'auto_accept_orders', 'min_order_amount', 'delivery_fee', 'free_delivery_threshold', 'preparation_minutes', 'payment_methods'] },
    { key: 'branding', errorPrefixes: ['primary_color', 'accent_color', 'logo', 'hero_image'] },
    { key: 'hours', errorPrefixes: ['orders_open_at', 'orders_close_at', 'orders_open_days', 'support_phone', 'support_email', 'announcement'] },
    { key: 'homepage', errorPrefixes: ['homepage.show_announcement', 'homepage.announcement_items', 'homepage.search_placeholder', 'homepage.nav_links', 'homepage.hero', 'homepage.trust'] },
    { key: 'merchandising', errorPrefixes: ['homepage.categories_section', 'homepage.new_arrivals', 'homepage.bestsellers', 'homepage.promo_sale', 'homepage.promo_collection'] },
    { key: 'content', errorPrefixes: ['homepage.about_text', 'homepage.contact_text', 'homepage.footer_tagline', 'homepage.footer_trust', 'seo_title', 'seo_description'] },
];

const tabErrors = useFormTabErrors(form, () => form.errors, tabErrorConfig);

function toggleDay(day: string) {
    form.orders_open_days = form.orders_open_days.includes(day)
        ? form.orders_open_days.filter((d) => d !== day)
        : [...form.orders_open_days, day];
}

function togglePayment(method: string) {
    form.payment_methods = form.payment_methods.includes(method)
        ? form.payment_methods.filter((m) => m !== method)
        : [...form.payment_methods, method];
}

function addAnnouncementItem() {
    if (form.homepage.announcement_items.length >= 8) return;
    form.homepage.announcement_items.push('');
}

function removeAnnouncementItem(index: number) {
    form.homepage.announcement_items.splice(index, 1);
}

function addNavLink() {
    if (form.homepage.nav_links.length >= 12) return;
    form.homepage.nav_links.push({ label: 'Link', target: 'shop' });
}

function removeNavLink(index: number) {
    form.homepage.nav_links.splice(index, 1);
}

function emptyHeroSlide(): HeroSlide {
    return {
        image: '',
        image_url: null,
        preview_url: null,
        remove_image: false,
        eyebrow: '',
        headline: '',
        subheadline: '',
        primary_cta_label: '',
        primary_cta_target: 'shop',
        secondary_cta_label: '',
        secondary_cta_target: 'categories',
    };
}

function addHeroSlide() {
    if (form.homepage.hero.slides.length >= 8) return;
    form.homepage.hero.slides.push(emptyHeroSlide());
}

function removeHeroSlide(index: number) {
    const slide = form.homepage.hero.slides[index];
    if (slide?.preview_url) {
        URL.revokeObjectURL(slide.preview_url);
    }
    form.homepage.hero.slides.splice(index, 1);
}

function slidePreview(slide: HeroSlide): string | null {
    if (slide.remove_image) return null;
    if (slide.preview_url) return slide.preview_url;
    if (slide.image_url) return slide.image_url;
    return null;
}

function onHeroSlideImageChange(index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    const slide = form.homepage.hero.slides[index];
    if (!slide) return;

    if (slide.preview_url) {
        URL.revokeObjectURL(slide.preview_url);
    }

    if (!file) {
        slide.preview_url = null;
        return;
    }

    slide.image = file;
    slide.preview_url = URL.createObjectURL(file);
    slide.remove_image = false;
}

function clearHeroSlideImage(index: number) {
    const slide = form.homepage.hero.slides[index];
    if (!slide) return;
    if (slide.preview_url) {
        URL.revokeObjectURL(slide.preview_url);
    }
    slide.image = '';
    slide.preview_url = null;
    slide.image_url = null;
    slide.remove_image = true;
}

function hasHeroUploads(): boolean {
    return form.homepage.hero.slides.some((slide) => slide.image instanceof File);
}

async function submitForm() {
    const confirmed = await confirmSave(isEdit.value ? t('common.save') : t('common.create'), 'storefront');
    if (!confirmed) return;

    const useMultipart = hasHeroUploads();

    form.transform((data) => {
        const payload = { ...data } as Record<string, any>;
        const slides = (payload.homepage?.hero?.slides ?? []).map((slide: HeroSlide) => {
            const next: Record<string, unknown> = {
                eyebrow: slide.eyebrow ?? '',
                headline: slide.headline ?? '',
                subheadline: slide.subheadline ?? '',
                primary_cta_label: slide.primary_cta_label ?? '',
                primary_cta_target: slide.primary_cta_target ?? 'shop',
                secondary_cta_label: slide.secondary_cta_label ?? '',
                secondary_cta_target: slide.secondary_cta_target ?? 'categories',
                remove_image: Boolean(slide.remove_image),
            };

            if (slide.image instanceof File) {
                next.image = slide.image;
            } else if (typeof slide.image === 'string' && slide.image && !slide.remove_image) {
                next.image = slide.image;
            } else {
                next.image = '';
            }

            return next;
        });

        if (payload.homepage?.hero) {
            payload.homepage = {
                ...payload.homepage,
                hero: {
                    ...payload.homepage.hero,
                    slides,
                },
            };
        }

        if (isEdit.value && useMultipart) {
            payload._method = 'put';
        }

        return payload;
    });

    if (isEdit.value) {
        if (useMultipart) {
            form.post(route('admin.online-store-settings.update', props.setting!.id), { forceFormData: true });
        } else {
            form.put(route('admin.online-store-settings.update', props.setting!.id));
        }
    } else {
        form.post(route('admin.online-store-settings.store'), useMultipart ? { forceFormData: true } : {});
    }
}

async function destroySetting() {
    if (!props.setting || !can('online_store.manage')) {
        return;
    }

    const label = props.setting.storefront_name || props.setting.slug || 'storefront';
    if (!(await confirmDelete(`storefront “${label}”`))) {
        return;
    }

    deleting.value = true;
    router.delete(route('admin.online-store-settings.destroy', props.setting.id), {
        onFinish: () => {
            deleting.value = false;
        },
    });
}
</script>

<template>
    <Head :title="pageTitle" />
    <AppLayout>
        <template #header>{{ page.header }}</template>
        <template #subheader>{{ pageTitle }}</template>

        <FormShell
            :title="pageTitle"
            :eyebrow="page.eyebrow"
            :back-href="route('admin.online-store-settings.index')"
            :section-label="activeTabMeta?.label"
            :section-description="activeTabMeta?.description"
        >
            <template v-if="isEdit && can('online_store.manage')" #header-actions>
                <Button
                    type="button"
                    variant="danger"
                    size="sm"
                    :disabled="deleting || form.processing"
                    @click="destroySetting"
                >
                    {{ t('common.delete') }}
                </Button>
            </template>
            <form class="form-card-form" @submit.prevent="submitForm">
                <FormTabs v-model="activeTab" :tabs="tabs" :tab-errors="tabErrors">
                    <template #default="{ active }">
                        <div v-show="active === 'identity'" class="form-tab-panel">
                            <FormSection title="Storefront identity" description="Public name, URL slug, and publish status.">
                                <div class="form-grid">
                                    <FormField label="Store" required :error="form.errors.store_id">
                                        <Select v-model="form.store_id" :disabled="isEdit">
                                            <option v-for="store in stores" :key="store.id" :value="store.id">
                                                {{ store.store_name }} ({{ store.store_code }})
                                            </option>
                                        </Select>
                                    </FormField>
                                    <FormField label="Storefront name" required :error="form.errors.storefront_name">
                                        <Input v-model="form.storefront_name" />
                                    </FormField>
                                    <FormField label="URL slug" required :error="form.errors.slug" hint="Public URL: /store/{slug}">
                                        <Input v-model="form.slug" />
                                    </FormField>
                                    <FormField label="Tagline" :error="form.errors.tagline">
                                        <Input v-model="form.tagline" />
                                    </FormField>
                                    <FormField label="Status" required :error="form.errors.status">
                                        <Select v-model="form.status">
                                            <option value="active">{{ t('common.active') }}</option>
                                            <option value="inactive">{{ t('common.inactive') }}</option>
                                        </Select>
                                    </FormField>
                                    <FormField v-if="setting?.public_url" label="Public URL">
                                        <a :href="setting.public_url" target="_blank" rel="noopener" class="text-sm text-accent hover:underline">
                                            {{ setting.public_url }}
                                        </a>
                                    </FormField>
                                </div>
                                <FormField label="Description" class="mt-4" :error="form.errors.description">
                                    <FormTextarea v-model="form.description" :rows="3" />
                                </FormField>
                                <FormToggle v-model="form.is_published" class="mt-4" label="Published (visible to customers)" />
                            </FormSection>
                        </div>

                        <div v-show="active === 'operations'" class="form-tab-panel">
                            <div class="form-section-grid grid gap-8 xl:grid-cols-2">
                                <FormSection title="Fulfillment & fees" description="How customers receive orders and minimums.">
                                    <div class="form-toggle-grid">
                                        <FormToggle v-model="form.accept_pickup" label="Pickup" />
                                        <FormToggle v-model="form.accept_delivery" label="Delivery" />
                                        <FormToggle v-model="form.accept_dine_in" label="Dine-in" />
                                        <FormToggle v-model="form.auto_accept_orders" label="Auto-accept orders" />
                                    </div>
                                    <div class="form-grid mt-4">
                                        <FormField label="Min order amount" :error="form.errors.min_order_amount">
                                            <Input v-model="form.min_order_amount" type="number" step="0.01" min="0" />
                                        </FormField>
                                        <FormField label="Delivery fee" :error="form.errors.delivery_fee">
                                            <Input v-model="form.delivery_fee" type="number" step="0.01" min="0" />
                                        </FormField>
                                        <FormField label="Free delivery threshold" :error="form.errors.free_delivery_threshold">
                                            <Input v-model="form.free_delivery_threshold" type="number" step="0.01" min="0" />
                                        </FormField>
                                        <FormField label="Prep minutes" :error="form.errors.preparation_minutes">
                                            <Input v-model="form.preparation_minutes" type="number" min="0" max="1440" />
                                        </FormField>
                                    </div>
                                </FormSection>

                                <FormSection title="Payments" description="Accepted online payment methods.">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="opt in paymentOptions"
                                            :key="opt.value"
                                            type="button"
                                            class="rounded-md border px-3 py-1.5 text-sm"
                                            :class="form.payment_methods.includes(opt.value) ? 'border-amber-500 bg-amber-500 text-ink' : 'border-edge text-ink'"
                                            @click="togglePayment(opt.value)"
                                        >
                                            {{ opt.label }}
                                        </button>
                                    </div>
                                    <p v-if="form.errors.payment_methods" class="form-error mt-2">{{ form.errors.payment_methods }}</p>
                                </FormSection>
                            </div>
                        </div>

                        <div v-show="active === 'branding'" class="form-tab-panel">
                            <FormSection title="Branding & media" description="Colors, logo, and fallback hero image when no slideshow slides are set.">
                                <div class="form-grid">
                                    <FormField label="Primary color" :error="form.errors.primary_color">
                                        <Input v-model="form.primary_color" type="color" />
                                    </FormField>
                                    <FormField label="Accent color" :error="form.errors.accent_color">
                                        <Input v-model="form.accent_color" type="color" />
                                    </FormField>
                                    <FormField label="Logo path" :error="form.errors.logo" hint="Storage path under public disk">
                                        <Input v-model="form.logo" placeholder="storefront/logo.png" />
                                    </FormField>
                                    <FormField label="Fallback hero image" :error="form.errors.hero_image" hint="Used when the slideshow has no uploaded slides">
                                        <Input v-model="form.hero_image" placeholder="storefront/hero.jpg" />
                                    </FormField>
                                </div>
                                <div v-if="setting?.logo_url || setting?.hero_image_url" class="mt-4 flex flex-wrap gap-4">
                                    <img v-if="setting?.logo_url" :src="setting.logo_url" alt="Logo" class="h-16 rounded-lg border border-edge object-cover" />
                                    <img v-if="setting?.hero_image_url" :src="setting.hero_image_url" alt="Hero" class="h-24 rounded-lg border border-edge object-cover" />
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'hours'" class="form-tab-panel">
                            <FormSection title="Hours & support">
                                <div class="form-grid">
                                    <FormField label="Orders open at" :error="form.errors.orders_open_at">
                                        <Input v-model="form.orders_open_at" type="time" />
                                    </FormField>
                                    <FormField label="Orders close at" :error="form.errors.orders_close_at">
                                        <Input v-model="form.orders_close_at" type="time" />
                                    </FormField>
                                    <FormField label="Support phone" :error="form.errors.support_phone">
                                        <Input v-model="form.support_phone" />
                                    </FormField>
                                    <FormField label="Support email" :error="form.errors.support_email">
                                        <Input v-model="form.support_email" type="email" />
                                    </FormField>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        v-for="day in days"
                                        :key="day.value"
                                        type="button"
                                        class="rounded-md border px-3 py-1.5 text-sm"
                                        :class="form.orders_open_days.includes(day.value) ? 'border-teal-700 bg-teal-700 text-white' : 'border-edge text-ink'"
                                        @click="toggleDay(day.value)"
                                    >
                                        {{ day.label }}
                                    </button>
                                </div>
                                <FormField label="Fallback announcement" class="mt-4" :error="form.errors.announcement" hint="Used if homepage marquee items are empty">
                                    <FormTextarea v-model="form.announcement" :rows="2" />
                                </FormField>
                            </FormSection>
                        </div>

                        <div v-show="active === 'homepage'" class="form-tab-panel">
                            <FormSection title="Hero slideshow" description="Upload banner images and optional per-slide copy. Empty slide text falls back to the defaults below.">
                                <div class="form-grid">
                                    <FormToggle v-model="form.homepage.hero.autoplay" label="Autoplay slideshow" />
                                    <FormField label="Slide interval (ms)" :error="form.errors['homepage.hero.interval_ms']" hint="2000–30000">
                                        <Input v-model="form.homepage.hero.interval_ms" type="number" min="2000" max="30000" step="500" />
                                    </FormField>
                                    <FormToggle v-model="form.homepage.hero.show_social_proof" label="Show social proof" />
                                    <FormToggle v-model="form.homepage.hero.show_float_cards" label="Show floating product cards" />
                                </div>

                                <p class="mt-6 text-sm font-semibold text-ink">Default copy (used when a slide leaves a field blank)</p>
                                <div class="form-grid mt-3">
                                    <FormField label="Eyebrow">
                                        <Input v-model="form.homepage.hero.eyebrow" />
                                    </FormField>
                                    <FormField label="Headline">
                                        <Input v-model="form.homepage.hero.headline" />
                                    </FormField>
                                    <FormField label="Subheadline" class="sm:col-span-2">
                                        <FormTextarea v-model="form.homepage.hero.subheadline" :rows="2" />
                                    </FormField>
                                    <FormField label="Primary CTA label">
                                        <Input v-model="form.homepage.hero.primary_cta_label" />
                                    </FormField>
                                    <FormField label="Primary CTA target">
                                        <Select v-model="form.homepage.hero.primary_cta_target">
                                            <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </Select>
                                    </FormField>
                                    <FormField label="Secondary CTA label">
                                        <Input v-model="form.homepage.hero.secondary_cta_label" />
                                    </FormField>
                                    <FormField label="Secondary CTA target">
                                        <Select v-model="form.homepage.hero.secondary_cta_target">
                                            <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </Select>
                                    </FormField>
                                    <FormField label="Social proof text" class="sm:col-span-2">
                                        <Input v-model="form.homepage.hero.social_proof" />
                                    </FormField>
                                </div>

                                <div class="mt-6">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-ink">Slides</p>
                                        <button type="button" class="text-sm font-semibold text-accent" @click="addHeroSlide">+ Add slide</button>
                                    </div>
                                    <p v-if="!form.homepage.hero.slides.length" class="rounded-lg border border-dashed border-line px-4 py-6 text-sm text-mute">
                                        No slides yet. Add slides and upload images, or the storefront will use the branding hero image / logo.
                                    </p>
                                    <div class="space-y-4">
                                        <div
                                            v-for="(slide, index) in form.homepage.hero.slides"
                                            :key="`slide-${index}`"
                                            class="rounded-lg border border-line/70 p-4"
                                        >
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <p class="text-sm font-semibold text-ink">Slide {{ index + 1 }}</p>
                                                <button type="button" class="text-sm text-mute hover:text-ink" @click="removeHeroSlide(index)">Remove</button>
                                            </div>
                                            <div class="grid gap-4 lg:grid-cols-[12rem_minmax(0,1fr)]">
                                                <div class="space-y-2">
                                                    <div class="flex h-36 items-center justify-center overflow-hidden rounded-lg border border-line bg-surface-muted/40">
                                                        <img
                                                            v-if="slidePreview(slide)"
                                                            :src="slidePreview(slide) || ''"
                                                            alt=""
                                                            class="h-full w-full object-cover"
                                                        />
                                                        <span v-else class="px-3 text-center text-xs text-mute">No image</span>
                                                    </div>
                                                    <label class="flex cursor-pointer flex-col rounded-lg border border-dashed border-line px-3 py-2 text-center transition hover:border-accent/40">
                                                        <span class="text-xs font-medium text-ink">Upload image</span>
                                                        <span class="mt-0.5 text-[11px] text-mute">JPG, PNG, WebP · max 4MB</span>
                                                        <input
                                                            type="file"
                                                            accept="image/*"
                                                            class="sr-only"
                                                            @change="onHeroSlideImageChange(index, $event)"
                                                        />
                                                    </label>
                                                    <button
                                                        v-if="slidePreview(slide) || slide.image"
                                                        type="button"
                                                        class="text-xs text-mute hover:text-ink"
                                                        @click="clearHeroSlideImage(index)"
                                                    >
                                                        Remove image
                                                    </button>
                                                    <p v-if="form.errors[`homepage.hero.slides.${index}.image`]" class="form-error">
                                                        {{ form.errors[`homepage.hero.slides.${index}.image`] }}
                                                    </p>
                                                </div>
                                                <div class="form-grid">
                                                    <FormField label="Eyebrow">
                                                        <Input v-model="slide.eyebrow" :placeholder="form.homepage.hero.eyebrow" />
                                                    </FormField>
                                                    <FormField label="Headline">
                                                        <Input v-model="slide.headline" :placeholder="form.homepage.hero.headline" />
                                                    </FormField>
                                                    <FormField label="Subheadline" class="sm:col-span-2">
                                                        <FormTextarea v-model="slide.subheadline" :rows="2" :placeholder="form.homepage.hero.subheadline" />
                                                    </FormField>
                                                    <FormField label="Primary CTA">
                                                        <Input v-model="slide.primary_cta_label" :placeholder="form.homepage.hero.primary_cta_label" />
                                                    </FormField>
                                                    <FormField label="Primary target">
                                                        <Select v-model="slide.primary_cta_target">
                                                            <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                        </Select>
                                                    </FormField>
                                                    <FormField label="Secondary CTA">
                                                        <Input v-model="slide.secondary_cta_label" :placeholder="form.homepage.hero.secondary_cta_label" />
                                                    </FormField>
                                                    <FormField label="Secondary target">
                                                        <Select v-model="slide.secondary_cta_target">
                                                            <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                        </Select>
                                                    </FormField>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </FormSection>

                            <div class="form-section-grid mt-8 grid gap-8 xl:grid-cols-2">
                                <FormSection title="Announcement & nav" description="Header marquee and top navigation.">
                                    <FormToggle v-model="form.homepage.show_announcement" label="Show announcement bar" />
                                    <FormField label="Search placeholder" class="mt-4" :error="form.errors['homepage.search_placeholder']">
                                        <Input v-model="form.homepage.search_placeholder" />
                                    </FormField>

                                    <div class="mt-6">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-sm font-semibold text-ink">Announcement marquee</p>
                                            <button type="button" class="text-sm font-semibold text-accent" @click="addAnnouncementItem">+ Add</button>
                                        </div>
                                        <div class="divide-y divide-edge border-y border-edge">
                                            <div
                                                v-for="(_, index) in form.homepage.announcement_items"
                                                :key="`ann-${index}`"
                                                class="flex items-center gap-2 py-2"
                                            >
                                                <span class="w-6 shrink-0 text-xs text-mute">{{ index + 1 }}</span>
                                                <Input v-model="form.homepage.announcement_items[index]" class="min-w-0 flex-1" />
                                                <button
                                                    type="button"
                                                    class="shrink-0 px-2 text-sm text-mute hover:text-ink"
                                                    @click="removeAnnouncementItem(index)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-sm font-semibold text-ink">Navigation links</p>
                                            <button type="button" class="text-sm font-semibold text-accent" @click="addNavLink">+ Add</button>
                                        </div>
                                        <div class="hidden grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_4.5rem] gap-3 px-1 pb-1 text-xs font-semibold uppercase tracking-wide text-mute sm:grid">
                                            <span>Label</span>
                                            <span>Target</span>
                                            <span class="text-right"> </span>
                                        </div>
                                        <div class="divide-y divide-edge border-y border-edge">
                                            <div
                                                v-for="(link, index) in form.homepage.nav_links"
                                                :key="`nav-${index}`"
                                                class="grid grid-cols-1 items-center gap-2 py-2 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_4.5rem]"
                                            >
                                                <Input v-model="link.label" placeholder="Label" />
                                                <Select v-model="link.target">
                                                    <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </Select>
                                                <button
                                                    type="button"
                                                    class="justify-self-start px-2 text-sm text-mute hover:text-ink sm:justify-self-end"
                                                    @click="removeNavLink(index)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </FormSection>

                                <FormSection title="Trust bar">
                                    <FormToggle v-model="form.homepage.trust.show" label="Show trust bar" />
                                    <div class="mt-4">
                                        <div class="hidden grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] gap-3 px-1 pb-1 text-xs font-semibold uppercase tracking-wide text-mute sm:grid">
                                            <span>Title</span>
                                            <span>Subtitle</span>
                                        </div>
                                        <div class="divide-y divide-edge border-y border-edge">
                                            <div
                                                v-for="(item, index) in form.homepage.trust.items"
                                                :key="`trust-${index}`"
                                                class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]"
                                            >
                                                <Input v-model="item.title" :placeholder="`Title ${index + 1}`" />
                                                <Input v-model="item.subtitle" placeholder="Subtitle" />
                                            </div>
                                        </div>
                                    </div>
                                </FormSection>
                            </div>
                        </div>

                        <div v-show="active === 'merchandising'" class="form-tab-panel">
                            <FormSection title="Catalog sections" description="Category, new arrivals, and bestsellers headings.">
                                <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                                    <div class="rounded-lg border border-line/70 p-4">
                                        <FormToggle v-model="form.homepage.categories_section.show" label="Show categories" />
                                        <div class="mt-3 space-y-3">
                                            <FormField label="Title"><Input v-model="form.homepage.categories_section.title" /></FormField>
                                            <FormField label="View all label"><Input v-model="form.homepage.categories_section.view_all_label" /></FormField>
                                            <FormField label="Card CTA"><Input v-model="form.homepage.categories_section.cta_label" /></FormField>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-line/70 p-4">
                                        <FormToggle v-model="form.homepage.new_arrivals.show" label="Show new arrivals" />
                                        <div class="mt-3 space-y-3">
                                            <FormField label="Title"><Input v-model="form.homepage.new_arrivals.title" /></FormField>
                                            <FormField label="View all label"><Input v-model="form.homepage.new_arrivals.view_all_label" /></FormField>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-line/70 p-4 lg:col-span-2 xl:col-span-1">
                                        <FormToggle v-model="form.homepage.bestsellers.show" label="Show bestsellers" />
                                        <div class="mt-3 space-y-3">
                                            <FormField label="Title"><Input v-model="form.homepage.bestsellers.title" /></FormField>
                                            <FormField label="View all label"><Input v-model="form.homepage.bestsellers.view_all_label" /></FormField>
                                            <FormField label="Badge"><Input v-model="form.homepage.bestsellers.badge_label" /></FormField>
                                            <FormField label="Quick add label"><Input v-model="form.homepage.bestsellers.quick_add_label" /></FormField>
                                        </div>
                                    </div>
                                </div>
                            </FormSection>

                            <FormSection title="Promo banners">
                                <div class="grid gap-6 xl:grid-cols-2">
                                    <div class="rounded-lg border border-line/70 p-4">
                                        <FormToggle v-model="form.homepage.promo_sale.show" label="Show flash sale banner" />
                                        <div class="form-grid mt-3">
                                            <FormField label="Eyebrow"><Input v-model="form.homepage.promo_sale.eyebrow" /></FormField>
                                            <FormField label="Title"><Input v-model="form.homepage.promo_sale.title" /></FormField>
                                            <FormField label="Subtitle" class="sm:col-span-2"><Input v-model="form.homepage.promo_sale.subtitle" /></FormField>
                                            <FormField label="CTA label"><Input v-model="form.homepage.promo_sale.cta_label" /></FormField>
                                            <FormField label="CTA target">
                                                <Select v-model="form.homepage.promo_sale.cta_target">
                                                    <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </Select>
                                            </FormField>
                                            <FormField label="Countdown ends at" class="sm:col-span-2" hint="Leave empty for a rolling 3-day timer">
                                                <Input v-model="form.homepage.promo_sale.countdown_ends_at" type="datetime-local" />
                                            </FormField>
                                        </div>
                                    </div>
                                    <div class="rounded-lg border border-line/70 p-4">
                                        <FormToggle v-model="form.homepage.promo_collection.show" label="Show collection banner" />
                                        <div class="form-grid mt-3">
                                            <FormField label="Eyebrow"><Input v-model="form.homepage.promo_collection.eyebrow" /></FormField>
                                            <FormField label="Title"><Input v-model="form.homepage.promo_collection.title" /></FormField>
                                            <FormField label="Subtitle" class="sm:col-span-2"><Input v-model="form.homepage.promo_collection.subtitle" /></FormField>
                                            <FormField label="CTA label"><Input v-model="form.homepage.promo_collection.cta_label" /></FormField>
                                            <FormField label="CTA target">
                                                <Select v-model="form.homepage.promo_collection.cta_target">
                                                    <option v-for="opt in sectionTargets" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </Select>
                                            </FormField>
                                            <FormField label="Image URL" class="sm:col-span-2" hint="Optional absolute or relative URL">
                                                <Input v-model="form.homepage.promo_collection.image_url" />
                                            </FormField>
                                        </div>
                                    </div>
                                </div>
                            </FormSection>
                        </div>

                        <div v-show="active === 'content'" class="form-tab-panel">
                            <div class="form-section-grid grid gap-8 xl:grid-cols-2">
                                <FormSection title="About, contact & footer">
                                    <FormField label="About text">
                                        <FormTextarea v-model="form.homepage.about_text" :rows="3" />
                                    </FormField>
                                    <FormField label="Contact text" class="mt-4">
                                        <FormTextarea v-model="form.homepage.contact_text" :rows="2" />
                                    </FormField>
                                    <FormField label="Footer tagline" class="mt-4">
                                        <Input v-model="form.homepage.footer_tagline" />
                                    </FormField>
                                    <FormToggle v-model="form.homepage.footer_trust.show" class="mt-4" label="Show footer trust bar" />
                                    <div class="mt-3 divide-y divide-edge border-y border-edge">
                                        <div
                                            v-for="(item, index) in form.homepage.footer_trust.items"
                                            :key="`ft-${index}`"
                                            class="flex items-center gap-2 py-2"
                                        >
                                            <span class="w-6 shrink-0 text-xs text-mute">{{ index + 1 }}</span>
                                            <Input v-model="item.title" class="min-w-0 flex-1" :placeholder="`Footer item ${index + 1}`" />
                                        </div>
                                    </div>
                                </FormSection>

                                <FormSection title="SEO">
                                    <FormField label="SEO title" :error="form.errors.seo_title">
                                        <Input v-model="form.seo_title" />
                                    </FormField>
                                    <FormField label="SEO description" class="mt-4" :error="form.errors.seo_description">
                                        <FormTextarea v-model="form.seo_description" :rows="4" />
                                    </FormField>
                                </FormSection>
                            </div>
                        </div>
                    </template>
                </FormTabs>

                <FormActionBar
                    :cancel-href="route('admin.online-store-settings.index')"
                    :submit-label="isEdit ? submit('saveStorefront') : submit('createStorefront')"
                    :processing="form.processing"
                />
            </form>
        </FormShell>
    </AppLayout>
</template>
