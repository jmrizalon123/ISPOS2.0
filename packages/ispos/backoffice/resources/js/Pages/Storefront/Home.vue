<script setup lang="ts">
import Toast from '@/Components/ui/Toast.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';
import { useStorefrontTheme } from '@/Composables/useStorefrontTheme';
import { toast } from '@/Composables/useToast';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Product {
    id: string;
    name: string;
    sku: string;
    description?: string | null;
    image: string | null;
    pos_unit_price: string | number;
    category_id: string | null;
    category?: { id: string; name: string } | null;
    has_variants: boolean;
    variants: Array<{ id: string; name: string; pos_unit_price: string | number }>;
    modifier_groups: Array<{
        id: string;
        name: string;
        options: Array<{ id: string; name: string; price_adjustment: string }>;
    }>;
    avg_rating?: number;
    rating_count?: number;
}

interface CartItem {
    key: string;
    product_id: string;
    product_variant_id: string | null;
    name: string;
    image: string | null;
    qty: number;
    unit_price: number;
    modifiers: Array<{ product_modifier_option_id: string; option_name: string; price_adjustment: number }>;
}

interface HomepageNavLink {
    label?: string;
    target?: string;
}

const props = defineProps<{
    setting: {
        slug: string;
        storefront_name: string;
        tagline: string | null;
        description: string | null;
        logo_url: string | null;
        hero_image_url: string | null;
        primary_color: string;
        accent_color: string;
        announcement: string | null;
        min_order_amount: string | number;
        preparation_minutes: number;
        delivery_fee?: string | number;
        accept_delivery?: boolean;
        support_phone?: string | null;
        support_email?: string | null;
        homepage?: Record<string, any>;
    };
    store: {
        store_name: string;
        store_category: string | null;
        currency: string;
        city: string | null;
        logo_url?: string | null;
    };
    categories: Array<{ id: string; name: string; category_code: string | null }>;
    products: Product[];
    layout: 'menu' | 'grid';
    acceptingOrders: boolean;
    customer: { id: string; name: string; email: string | null; phone: string | null } | null;
    favoriteIds: string[];
    myRatings: Record<string, number | { rating: number; review?: string | null }>;
    priceBounds: { min: number; max: number };
    filters: { q: string; category: string; price_min?: string; price_max?: string };
}>();

const { mode: themeMode, themeLabel, colorModeAttrs, cycleTheme } = useStorefrontTheme();

const cart = ref<CartItem[]>([]);
const search = ref(props.filters.q);
const activeCategory = ref(props.filters.category || '');
const chipFilter = ref('all');
const favoritesOnly = ref(false);
const browseAll = ref(false);
const mobileNavOpen = ref(false);
const mobileCartOpen = ref(false);
const searchOpen = ref(false);
const catsMenuOpen = ref(false);
const rtNavOpen = ref(false);
const rtCartOpen = ref(false);
const accountMenuOpen = ref(false);
let catsMenuCloseTimer: number | null = null;
let accountMenuCloseTimer: number | null = null;
const ratingModal = ref<{
    productId: string;
    productName: string;
    productImage: string | null;
    rating: number;
    review: string;
} | null>(null);
const ratingSubmitting = ref(false);
const priceBoundMin = computed(() => Math.floor(Number(props.priceBounds?.min ?? 0)));
const priceBoundMax = computed(() => {
    const max = Math.ceil(Number(props.priceBounds?.max ?? 0));
    return max > priceBoundMin.value ? max : priceBoundMin.value + 1;
});
const priceMin = ref(
    props.filters.price_min !== undefined && props.filters.price_min !== ''
        ? Number(props.filters.price_min)
        : priceBoundMin.value,
);
const priceMax = ref(
    props.filters.price_max !== undefined && props.filters.price_max !== ''
        ? Number(props.filters.price_max)
        : priceBoundMax.value,
);
const favoriteSet = computed(() => new Set((props.favoriteIds || []).map(String)));
const storeLogo = computed(() => props.store.logo_url || props.setting.logo_url || null);
const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' });
let searchDebounce: number | null = null;
let countdownTimer: number | null = null;
let defaultCountdownEnd: number | null = null;

const storageKey = computed(() => `ispos-storefront-cart:${props.setting.slug}`);
const brandName = computed(() => props.setting.storefront_name || props.store.store_name);
const isRestaurant = computed(() => props.layout === 'menu');
const isBrowsing = computed(() =>
    Boolean(
        browseAll.value ||
            activeCategory.value ||
            search.value.trim() ||
            favoritesOnly.value ||
            priceMin.value > priceBoundMin.value ||
            priceMax.value < priceBoundMax.value,
    ),
);
const homepage = computed(() => props.setting.homepage ?? {});
const hero = computed(() => (homepage.value.hero ?? {}) as Record<string, any>);
const heroSlideIndex = ref(0);
let heroTimer: number | null = null;

const heroSlides = computed(() => {
    const defaults = {
        eyebrow: String(hero.value.eyebrow || props.setting.tagline || 'Trending now'),
        headline: String(hero.value.headline || 'Discover Products You’ll Love'),
        subheadline: String(
            hero.value.subheadline ||
                props.setting.description ||
                'Shop the latest trending products curated for modern lifestyles.',
        ),
        primary_cta_label: String(hero.value.primary_cta_label || 'Shop Now'),
        primary_cta_target: String(hero.value.primary_cta_target || 'shop'),
        secondary_cta_label: String(hero.value.secondary_cta_label || 'Explore Collection'),
        secondary_cta_target: String(hero.value.secondary_cta_target || 'categories'),
        image: String(props.setting.hero_image_url || props.setting.logo_url || ''),
    };

    const raw = Array.isArray(hero.value.slides) ? hero.value.slides : [];
    const mapped = raw
        .map((slide: Record<string, any>) => ({
            eyebrow: String(slide.eyebrow || defaults.eyebrow),
            headline: String(slide.headline || defaults.headline),
            subheadline: String(slide.subheadline || defaults.subheadline),
            primary_cta_label: String(slide.primary_cta_label || defaults.primary_cta_label),
            primary_cta_target: String(slide.primary_cta_target || defaults.primary_cta_target),
            secondary_cta_label: String(slide.secondary_cta_label || defaults.secondary_cta_label),
            secondary_cta_target: String(slide.secondary_cta_target || defaults.secondary_cta_target),
            image: String(slide.image_url || slide.image || defaults.image || ''),
        }))
        .filter((slide: { image: string; headline: string }) => slide.image || slide.headline);

    if (mapped.length) {
        return mapped;
    }

    return [defaults];
});
const activeHeroSlide = computed(() => heroSlides.value[heroSlideIndex.value] ?? heroSlides.value[0]);
const heroAutoplay = computed(() => hero.value.autoplay !== false && heroSlides.value.length > 1);
const heroIntervalMs = computed(() => {
    const raw = Number(hero.value.interval_ms ?? 5500);
    return Number.isFinite(raw) ? Math.min(30000, Math.max(2000, raw)) : 5500;
});

function goToHeroSlide(index: number) {
    const total = heroSlides.value.length;
    if (!total) return;
    heroSlideIndex.value = ((index % total) + total) % total;
    restartHeroTimer();
}

function nextHeroSlide() {
    goToHeroSlide(heroSlideIndex.value + 1);
}

function prevHeroSlide() {
    goToHeroSlide(heroSlideIndex.value - 1);
}

function clearHeroTimer() {
    if (heroTimer !== null) {
        window.clearInterval(heroTimer);
        heroTimer = null;
    }
}

function restartHeroTimer() {
    clearHeroTimer();
    if (!heroAutoplay.value) return;
    heroTimer = window.setInterval(() => {
        heroSlideIndex.value = (heroSlideIndex.value + 1) % heroSlides.value.length;
    }, heroIntervalMs.value);
}

watch(heroSlides, (slides) => {
    if (heroSlideIndex.value >= slides.length) {
        heroSlideIndex.value = 0;
    }
    restartHeroTimer();
});

watch(heroAutoplay, () => restartHeroTimer());
watch(heroIntervalMs, () => restartHeroTimer());

const trustSection = computed(() => (homepage.value.trust ?? {}) as Record<string, any>);
const categoriesSection = computed(() => (homepage.value.categories_section ?? {}) as Record<string, any>);
const arrivalsSection = computed(() => (homepage.value.new_arrivals ?? {}) as Record<string, any>);
const bestsellersSection = computed(() => (homepage.value.bestsellers ?? {}) as Record<string, any>);
const promoSale = computed(() => (homepage.value.promo_sale ?? {}) as Record<string, any>);
const promoCollection = computed(() => (homepage.value.promo_collection ?? {}) as Record<string, any>);
const footerTrust = computed(() => (homepage.value.footer_trust ?? {}) as Record<string, any>);
const navLinks = computed(() => {
    const links = homepage.value.nav_links;
    return Array.isArray(links) ? (links as HomepageNavLink[]).filter((l) => l?.label) : [];
});
const showAnnouncement = computed(() => homepage.value.show_announcement !== false);
const searchPlaceholder = computed(
    () => String(homepage.value.search_placeholder || 'Search products…'),
);
const collectionImage = computed(
    () =>
        String(promoCollection.value.image_url || props.setting.hero_image_url || props.setting.logo_url || ''),
);

const themeStyle = computed(() => ({
    '--bh-accent': props.setting.primary_color || '#f97316',
    '--bh-accent-soft': `${props.setting.primary_color || '#f97316'}18`,
    '--bh-accent-rgb': hexToRgb(props.setting.primary_color || '#f97316'),
    '--rt-accent': props.setting.primary_color || '#ff5a1f',
    '--rt-accent-rgb': hexToRgb(props.setting.primary_color || '#ff5a1f'),
}));

function hexToRgb(hex: string): string {
    const clean = hex.replace('#', '');
    const full = clean.length === 3 ? clean.split('').map((c) => c + c).join('') : clean;
    const n = Number.parseInt(full, 16);
    if (Number.isNaN(n)) return '249, 115, 22';
    return `${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}`;
}

const categoryStats = computed(() =>
    props.categories
        .map((category) => ({
            ...category,
            count: props.products.filter((p) => p.category_id === category.id).length,
            image: props.products.find((p) => p.category_id === category.id && p.image)?.image ?? null,
        }))
        .filter((c) => c.count > 0),
);

const filteredProducts = computed(() => {
    let list = props.products;
    if (activeCategory.value) {
        list = list.filter((p) => p.category_id === activeCategory.value);
    }
    if (favoritesOnly.value) {
        list = list.filter((p) => favoriteSet.value.has(String(p.id)));
    }
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                (p.sku || '').toLowerCase().includes(q) ||
                (p.description || '').toLowerCase().includes(q),
        );
    }
    const min = Number(priceMin.value);
    const max = Number(priceMax.value);
    if (Number.isFinite(min) && Number.isFinite(max)) {
        list = list.filter((p) => {
            const price = Number(p.pos_unit_price ?? 0);
            return price >= min && price <= max;
        });
    }
    return list;
});

const pageTitle = computed(() => {
    if (!activeCategory.value) return brandName.value;
    return categoryStats.value.find((c) => c.id === activeCategory.value)?.name || brandName.value;
});

const chips = computed(() => {
    const base = [{ id: 'all', label: `All ${pageTitle.value}` }];
    if (!activeCategory.value) {
        return [
            { id: 'all', label: 'All items' },
            ...categoryStats.value.slice(0, 6).map((c) => ({ id: c.id, label: c.name })),
        ];
    }
    return base;
});

const displayProducts = computed(() => {
    if (chipFilter.value === 'all' || activeCategory.value) {
        return filteredProducts.value;
    }
    return filteredProducts.value.filter((p) => p.category_id === chipFilter.value);
});

useScrollReveal({
    deps: [() => isBrowsing.value, () => displayProducts.value.length, () => filteredProducts.value.length],
});

const newArrivals = computed(() => filteredProducts.value.slice(0, 6));
const bestSellers = computed(() => filteredProducts.value.slice(0, 3));
const heroFloats = computed(() => props.products.filter((p) => p.image).slice(0, 4));
const announcementItems = computed(() => {
    const fromCms = Array.isArray(homepage.value.announcement_items)
        ? homepage.value.announcement_items
              .map((item: unknown) => String(item ?? '').trim())
              .filter(Boolean)
        : [];
    if (fromCms.length) return fromCms;

    const custom = props.setting.announcement?.trim();
    if (custom) return [custom, 'Secure checkout', 'Fast delivery available'];
    const min = Number(props.setting.min_order_amount || 0);
    return [
        props.setting.accept_delivery ? 'Free delivery on qualifying orders' : 'Order online anytime',
        'Secure payments',
        min > 0
            ? `Min. order ${props.store.currency} ${min.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            : 'Fresh stock updated daily',
    ];
});

const trustItems = computed(() => {
    const items = Array.isArray(trustSection.value.items) ? trustSection.value.items : [];
    return items.filter((item: any) => item?.title);
});

const footerTrustItems = computed(() => {
    const items = Array.isArray(footerTrust.value.items) ? footerTrust.value.items : [];
    return items.filter((item: any) => item?.title);
});

const cartCount = computed(() => cart.value.reduce((sum, item) => sum + item.qty, 0));
const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.qty * item.unit_price, 0));
const taxEstimate = computed(() => subtotal.value * 0.1);
const deliveryEstimate = computed(() =>
    props.setting.accept_delivery ? Number(props.setting.delivery_fee || 0) : 0,
);
const orderTotal = computed(() => subtotal.value + taxEstimate.value + deliveryEstimate.value);

function sectionIdForTarget(target?: string): string {
    const map: Record<string, string> = {
        shop: 'rt-shop',
        arrivals: 'rt-arrivals',
        bestsellers: 'rt-bestsellers',
        categories: 'rt-categories',
        about: 'rt-about',
        contact: 'rt-contact',
    };
    return target ? map[target] || '' : '';
}

function goToTarget(target?: string) {
    const t = (target || 'shop').toLowerCase();
    if (t === 'home') {
        clearBrowse();
        return;
    }
    if (t === 'shop') {
        browseAll.value = true;
        return;
    }
    const id = sectionIdForTarget(t) || (t === 'categories' ? 'rt-categories' : '');
    if (id) scrollToSection(id);
}

function handleNavTarget(target?: string) {
    const t = (target || 'home').toLowerCase();
    if (t === 'categories') {
        if (catsMenuOpen.value) closeCatsMenu();
        else openCatsMenu();
        return;
    }
    closeCatsMenu();
    goToTarget(t);
}

function openCatsMenu() {
    if (catsMenuCloseTimer) {
        window.clearTimeout(catsMenuCloseTimer);
        catsMenuCloseTimer = null;
    }
    catsMenuOpen.value = true;
}

function scheduleCloseCatsMenu() {
    if (catsMenuCloseTimer) window.clearTimeout(catsMenuCloseTimer);
    catsMenuCloseTimer = window.setTimeout(() => {
        catsMenuOpen.value = false;
        catsMenuCloseTimer = null;
    }, 160);
}

function closeCatsMenu() {
    if (catsMenuCloseTimer) {
        window.clearTimeout(catsMenuCloseTimer);
        catsMenuCloseTimer = null;
    }
    catsMenuOpen.value = false;
}

function openAccountMenu() {
    if (accountMenuCloseTimer) {
        window.clearTimeout(accountMenuCloseTimer);
        accountMenuCloseTimer = null;
    }
    accountMenuOpen.value = true;
}

function scheduleCloseAccountMenu() {
    if (accountMenuCloseTimer) window.clearTimeout(accountMenuCloseTimer);
    accountMenuCloseTimer = window.setTimeout(() => {
        accountMenuOpen.value = false;
        accountMenuCloseTimer = null;
    }, 160);
}

function closeAccountMenu() {
    if (accountMenuCloseTimer) {
        window.clearTimeout(accountMenuCloseTimer);
        accountMenuCloseTimer = null;
    }
    accountMenuOpen.value = false;
}

function getCountdownEndMs(): number {
    const raw = promoSale.value.countdown_ends_at;
    if (raw) {
        const parsed = new Date(String(raw)).getTime();
        if (!Number.isNaN(parsed)) return parsed;
    }
    if (defaultCountdownEnd === null) {
        defaultCountdownEnd = Date.now() + 3 * 24 * 60 * 60 * 1000;
    }
    return defaultCountdownEnd;
}

function tickCountdown() {
    const diff = Math.max(0, getCountdownEndMs() - Date.now());
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
    const minutes = Math.floor((diff / (1000 * 60)) % 60);
    const seconds = Math.floor((diff / 1000) % 60);
    countdown.value = {
        days: String(days).padStart(2, '0'),
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
    };
}

onMounted(() => {
    try {
        const raw = localStorage.getItem(storageKey.value);
        if (raw) cart.value = JSON.parse(raw);
    } catch {
        cart.value = [];
    }
    const id = 'bh-fonts';
    if (!document.getElementById(id)) {
        const link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href =
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap';
        document.head.appendChild(link);
    }
    if (!isRestaurant.value) {
        tickCountdown();
        countdownTimer = window.setInterval(tickCountdown, 1000);
        restartHeroTimer();
    }
});

onUnmounted(() => {
    if (countdownTimer) window.clearInterval(countdownTimer);
    if (searchDebounce) window.clearTimeout(searchDebounce);
    clearHeroTimer();
});

watch(
    cart,
    (value) => {
        localStorage.setItem(storageKey.value, JSON.stringify(value));
    },
    { deep: true },
);

function money(amount: number | string): string {
    return `${props.store.currency} ${Number(amount).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function productPrice(product: Product): number {
    const variant = product.variants[0];
    return Number(variant?.pos_unit_price ?? product.pos_unit_price ?? 0);
}

function blurb(product: Product): string {
    if (product.description) {
        return product.description.length > 72 ? `${product.description.slice(0, 72)}…` : product.description;
    }
    return product.category?.name ? `From ${product.category.name}` : 'Freshly prepared';
}

function retailBlurb(product: Product): string {
    if (product.description) {
        return product.description.length > 90 ? `${product.description.slice(0, 90)}…` : product.description;
    }
    return product.category?.name
        ? `Top pick from ${product.category.name}. Quality you can trust.`
        : 'Curated for everyday essentials and best value.';
}

function badgeFor(product: Product, index: number): string | null {
    if (index === 0) return 'BESTSELLER';
    if (index === 1) return 'POPULAR';
    return null;
}

function retailBadge(index: number): { label: string; tone: 'new' | 'sale' | 'hot' } {
    if (index === 0) return { label: 'New', tone: 'new' };
    if (index % 3 === 1) return { label: '-20%', tone: 'sale' };
    if (index % 4 === 2) return { label: '-15%', tone: 'sale' };
    return { label: 'New', tone: 'new' };
}

function retailRating(product: Product, index: number): { stars: number; count: number } {
    const count = Number(product.rating_count ?? 0);
    const avg = Number(product.avg_rating ?? 0);
    if (count > 0 && avg > 0) {
        return { stars: Math.round(avg), count };
    }
    const counts = [12, 8, 21, 5, 15, 9, 17, 4];
    return { stars: 0, count: counts[index % counts.length] };
}

function myRatingFor(productId: string): number {
    const entry = props.myRatings?.[productId];
    if (entry == null) return 0;
    if (typeof entry === 'number') return Number(entry) || 0;
    return Number(entry.rating ?? 0) || 0;
}

function myReviewFor(productId: string): string {
    const entry = props.myRatings?.[productId];
    if (entry == null || typeof entry === 'number') return '';
    return String(entry.review ?? '');
}

function isFavorite(productId: string): boolean {
    return favoriteSet.value.has(String(productId));
}

function requireAuth(next?: () => void) {
    if (props.customer) {
        next?.();
        return true;
    }
    router.visit(route('storefront.login', props.setting.slug));
    return false;
}

function toggleFavorite(productId: string) {
    if (!requireAuth()) return;
    router.post(
        route('storefront.favorites.toggle', [props.setting.slug, productId]),
        {},
        { preserveScroll: true, preserveState: false },
    );
}

function openRatingModal(product: Product, rating: number) {
    if (!requireAuth()) return;
    ratingModal.value = {
        productId: product.id,
        productName: product.name,
        productImage: product.image,
        rating: Math.min(5, Math.max(1, rating)),
        review: myReviewFor(product.id),
    };
}

function closeRatingModal() {
    if (ratingSubmitting.value) return;
    ratingModal.value = null;
}

function setModalRating(rating: number) {
    if (!ratingModal.value) return;
    ratingModal.value.rating = Math.min(5, Math.max(1, rating));
}

function submitRating() {
    if (!ratingModal.value || ratingSubmitting.value) return;
    const review = ratingModal.value.review.trim();
    if (!review) {
        return;
    }
    ratingSubmitting.value = true;
    const { productId, rating } = ratingModal.value;
    router.post(
        route('storefront.ratings.store', [props.setting.slug, productId]),
        {
            rating,
            review,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => {
                ratingSubmitting.value = false;
            },
            onSuccess: () => {
                ratingModal.value = null;
            },
        },
    );
}

/** @deprecated use openRatingModal — kept for any remaining call sites */
function rateProduct(product: Product | string, rating: number) {
    if (typeof product === 'string') {
        const found = props.products.find((p) => p.id === product);
        if (!found) return;
        openRatingModal(found, rating);
        return;
    }
    openRatingModal(product, rating);
}

function toggleFavoritesFilter() {
    if (!requireAuth()) return;
    favoritesOnly.value = !favoritesOnly.value;
    if (favoritesOnly.value) browseAll.value = true;
}

function logoutCustomer() {
    closeAccountMenu();
    router.post(route('storefront.logout', props.setting.slug));
}

function clampPriceRange() {
    let min = Number(priceMin.value);
    let max = Number(priceMax.value);
    if (!Number.isFinite(min)) min = priceBoundMin.value;
    if (!Number.isFinite(max)) max = priceBoundMax.value;
    if (min > max) [min, max] = [max, min];
    priceMin.value = Math.max(priceBoundMin.value, Math.min(min, priceBoundMax.value));
    priceMax.value = Math.max(priceBoundMin.value, Math.min(max, priceBoundMax.value));
}

function resetPriceFilter() {
    priceMin.value = priceBoundMin.value;
    priceMax.value = priceBoundMax.value;
}

function retailComparePrice(price: number, index: number): number | null {
    if (index % 3 !== 1 && index % 4 !== 2) return null;
    return Math.round(price * 1.25 * 100) / 100;
}

function scrollRail(id: string, direction: 1 | -1) {
    const el = document.getElementById(id);
    if (!el) return;
    const amount = Math.max(240, Math.round(el.clientWidth * 0.7));
    el.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

function categoryIcon(name: string): string {
    const n = name.toLowerCase();
    if (/(burger|beef|chicken|meal|rice|main)/.test(n)) return 'burger';
    if (/(side|fries|snack)/.test(n)) return 'fries';
    if (/(drink|beverage|juice|coffee|tea)/.test(n)) return 'drink';
    if (/(dessert|cake|sweet|ice)/.test(n)) return 'dessert';
    return 'plate';
}

function selectCategory(id: string) {
    activeCategory.value = id;
    browseAll.value = true;
    chipFilter.value = 'all';
    mobileNavOpen.value = false;
    closeCatsMenu();
    rtNavOpen.value = false;
}

function clearBrowse() {
    activeCategory.value = '';
    search.value = '';
    chipFilter.value = 'all';
    favoritesOnly.value = false;
    browseAll.value = false;
    resetPriceFilter();
    router.get(route('storefront.show', props.setting.slug), {}, { preserveState: true, replace: true });
}

function scrollToSection(id: string) {
    rtNavOpen.value = false;
    const go = () => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (!isBrowsing.value) {
        go();
        return;
    }
    activeCategory.value = '';
    search.value = '';
    chipFilter.value = 'all';
    router.get(
        route('storefront.show', props.setting.slug),
        {},
        {
            preserveState: true,
            replace: true,
            onSuccess: () => requestAnimationFrame(go),
        },
    );
}

function onSearchInput() {
    if (searchDebounce) window.clearTimeout(searchDebounce);
    searchDebounce = window.setTimeout(() => {
        router.get(
            route('storefront.show', props.setting.slug),
            { q: search.value || undefined, category: activeCategory.value || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 280);
}

function addToCart(product: Product) {
    if (!props.acceptingOrders) return;
    const variant = product.variants[0] ?? null;
    const unitPrice = productPrice(product);
    const name = variant ? `${product.name} — ${variant.name}` : product.name;
    const key = `${product.id}:${variant?.id ?? 'base'}`;
    const existing = cart.value.find((item) => item.key === key);
    if (existing) {
        existing.qty += 1;
    } else {
        cart.value.push({
            key,
            product_id: product.id,
            product_variant_id: variant?.id ?? null,
            name,
            image: product.image,
            qty: 1,
            unit_price: unitPrice,
            modifiers: [],
        });
    }
    toast.success(`${name} added to cart`);
}

function changeQty(key: string, delta: number) {
    const item = cart.value.find((row) => row.key === key);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) cart.value = cart.value.filter((row) => row.key !== key);
}

function removeFromCart(key: string) {
    cart.value = cart.value.filter((row) => row.key !== key);
}

function selectChip(id: string) {
    chipFilter.value = id;
}

function goCheckout() {
    router.visit(route('storefront.checkout', props.setting.slug));
}

function promoCode(): string {
    const code = brandName.value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 8).toUpperCase();
    return code ? `${code}20` : 'SAVE20';
}
</script>

<template>
    <Head :title="brandName" />

    <!-- ===================== RESTAURANT / CAFE (BurgerHub-style) ===================== -->
    <div v-if="isRestaurant" class="bh-app" v-bind="colorModeAttrs" :style="themeStyle">
        <button type="button" class="bh-mobile-bar__btn bh-mobile-nav" @click="mobileNavOpen = true">Menu</button>
        <button type="button" class="bh-mobile-bar__btn bh-mobile-cart" @click="mobileCartOpen = true">
            Cart <span>{{ cartCount }}</span>
        </button>

        <aside class="bh-sidebar" :class="{ open: mobileNavOpen }">
            <div class="bh-brand">
                <img v-if="storeLogo" :src="storeLogo" alt="" class="bh-brand__logo" />
                <span v-else class="bh-brand__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 10h16v2H4v-2zm1 4h14l-1.2 6.2a2 2 0 01-2 1.8H8.2a2 2 0 01-2-1.8L5 14zm7-8a3 3 0 013 3H9a3 3 0 013-3z"/></svg>
                </span>
                <span class="bh-brand__name">{{ brandName }}</span>
            </div>

            <p class="bh-nav-label">Menu</p>
            <nav class="bh-nav">
                <button
                    type="button"
                    class="bh-nav__item"
                    :class="{ active: !activeCategory }"
                    @click="selectCategory('')"
                >
                    <span class="bh-nav__ico" data-icon="plate" />
                    <span>All items</span>
                </button>
                <button
                    v-for="category in categoryStats"
                    :key="category.id"
                    type="button"
                    class="bh-nav__item"
                    :class="{ active: activeCategory === category.id }"
                    @click="selectCategory(category.id)"
                >
                    <span class="bh-nav__ico" :data-icon="categoryIcon(category.name)" />
                    <span>{{ category.name }}</span>
                </button>
            </nav>

            <div class="bh-price">
                <p class="bh-nav-label">Price range</p>
                <div class="bh-price__values">
                    <span>{{ money(priceMin) }}</span>
                    <span>{{ money(priceMax) }}</span>
                </div>
                <label>
                    Min
                    <input v-model.number="priceMin" type="range" :min="priceBoundMin" :max="priceBoundMax" step="1" @change="clampPriceRange" />
                </label>
                <label>
                    Max
                    <input v-model.number="priceMax" type="range" :min="priceBoundMin" :max="priceBoundMax" step="1" @change="clampPriceRange" />
                </label>
                <button type="button" class="bh-price__reset" @click="resetPriceFilter">Reset</button>
            </div>

            <p class="bh-nav-label">Account</p>
            <nav class="bh-nav bh-nav--muted">
                <template v-if="customer">
                    <div class="bh-nav__item bh-nav__item--static">
                        <span class="bh-nav__ico" data-icon="user" />
                        <span>{{ customer.name }}</span>
                    </div>
                    <Link :href="route('storefront.orders.index', setting.slug)" class="bh-nav__item">
                        <span class="bh-nav__ico" data-icon="clock" />
                        <span>My orders</span>
                    </Link>
                    <button type="button" class="bh-nav__item" :class="{ active: favoritesOnly }" @click="toggleFavoritesFilter">
                        <span class="bh-nav__ico" data-icon="heart" />
                        <span>Favorites</span>
                    </button>
                    <button type="button" class="bh-nav__item" @click="logoutCustomer">
                        <span class="bh-nav__ico" data-icon="user" />
                        <span>Sign out</span>
                    </button>
                </template>
                <template v-else>
                    <Link :href="route('storefront.login', setting.slug)" class="bh-nav__item">
                        <span class="bh-nav__ico" data-icon="user" />
                        <span>Sign in</span>
                    </Link>
                    <Link :href="route('storefront.register', setting.slug)" class="bh-nav__item">
                        <span class="bh-nav__ico" data-icon="heart" />
                        <span>Create account</span>
                    </Link>
                </template>
                <button
                    type="button"
                    class="bh-nav__item"
                    :title="`Theme: ${themeLabel}`"
                    @click="cycleTheme"
                >
                    <span class="bh-theme-ico" aria-hidden="true">
                        <svg v-if="themeMode === 'dark'" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <svg v-else-if="themeMode === 'system'" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M8 20h8M12 16v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none">
                            <path d="M21 14.5A8.5 8.5 0 1110.5 3a7 7 0 0010.5 11.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>{{ themeLabel }}</span>
                </button>
            </nav>

            <div class="bh-promo">
                <p class="bh-promo__title">{{ setting.announcement ? 'Special offer' : 'Get 20% Off' }}</p>
                <p class="bh-promo__text">
                    {{
                        setting.announcement ||
                        `Use code ${promoCode()} at checkout`
                    }}
                </p>
                <button type="button" class="bh-promo__btn" @click="goCheckout">
                    {{ setting.announcement ? 'Order now' : 'Apply Code' }}
                </button>
            </div>
        </aside>

        <main class="bh-main">
            <header class="bh-main__head">
                <div class="bh-search">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search menu items..."
                        @input="onSearchInput"
                    />
                </div>
            </header>

            <div class="bh-chips" data-reveal>
                <button
                    v-for="chip in chips"
                    :key="chip.id"
                    type="button"
                    class="bh-chip"
                    :class="{ active: chipFilter === chip.id }"
                    @click="selectChip(chip.id)"
                >
                    {{ chip.label }}
                </button>
            </div>

            <div class="bh-section-head" data-reveal>
                <h2>Featured {{ pageTitle }}</h2>
                <p v-if="!acceptingOrders" class="bh-closed">Currently closed for orders</p>
            </div>

            <div class="bh-grid">
                <article
                    v-for="(product, index) in displayProducts"
                    :key="product.id"
                    class="bh-card"
                    data-reveal
                    :style="{ '--reveal-delay': `${Math.min(index, 8) * 45}ms` }"
                >
                    <div class="bh-card__media">
                        <button
                            type="button"
                            class="bh-card__wish"
                            :class="{ on: isFavorite(product.id) }"
                            :aria-label="isFavorite(product.id) ? 'Remove favorite' : 'Add favorite'"
                            @click="toggleFavorite(product.id)"
                        >♥</button>
                        <img
                            v-if="product.image"
                            :src="product.image"
                            :alt="product.name"
                            loading="lazy"
                        />
                    </div>
                    <div class="bh-card__body">
                        <span v-if="badgeFor(product, index)" class="bh-card__badge">{{ badgeFor(product, index) }}</span>
                        <h3>{{ product.name }}</h3>
                        <p class="bh-card__desc">{{ blurb(product) }}</p>
                        <div class="bh-card__rating">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                class="bh-card__star"
                                :class="{ on: star <= (myRatingFor(product.id) || retailRating(product, index).stars) }"
                                @click="rateProduct(product, star)"
                            >★</button>
                            <em>({{ retailRating(product, index).count }})</em>
                        </div>
                        <div class="bh-card__foot">
                            <strong>{{ money(productPrice(product)) }}</strong>
                            <button
                                type="button"
                                class="bh-card__add"
                                :disabled="!acceptingOrders"
                                aria-label="Add to order"
                                @click="addToCart(product)"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <div v-if="!displayProducts.length" class="bh-empty">
                <p>No menu items found.</p>
            </div>
        </main>

        <aside class="bh-order" :class="{ open: mobileCartOpen }">
            <div class="bh-order__head">
                <div>
                    <h2>Your order</h2>
                    <p class="bh-order__count">{{ cartCount }} {{ cartCount === 1 ? 'item' : 'items' }}</p>
                </div>
                <button type="button" class="bh-order__close" aria-label="Close cart" @click="mobileCartOpen = false">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>

            <ul v-if="cart.length" class="bh-order__list">
                <li v-for="item in cart" :key="item.key" class="bh-order__item">
                    <div class="bh-order__thumb">
                        <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy" />
                        <span v-else>{{ item.name.slice(0, 1) }}</span>
                    </div>
                    <div class="bh-order__meta">
                        <div class="bh-order__top">
                            <p class="bh-order__name">{{ item.name }}</p>
                            <button type="button" class="bh-order__remove" aria-label="Remove" @click="removeFromCart(item.key)">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M9 7l1-2h4l1 2M8 7l1 12a1 1 0 001 1h4a1 1 0 001-1l1-12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <p class="bh-order__unit">{{ money(item.unit_price) }} each</p>
                        <div class="bh-order__controls">
                            <div class="bh-qty">
                                <button type="button" aria-label="Decrease" @click="changeQty(item.key, -1)">−</button>
                                <span>{{ item.qty }}</span>
                                <button type="button" aria-label="Increase" @click="changeQty(item.key, 1)">+</button>
                            </div>
                            <strong class="bh-order__price">{{ money(item.qty * item.unit_price) }}</strong>
                        </div>
                    </div>
                </li>
            </ul>
            <div v-else class="bh-order__empty">
                <div class="bh-order__empty-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                </div>
                <p>Your order is empty</p>
                <span>Add items from the menu to get started.</span>
            </div>

            <div class="bh-order__foot">
                <div class="bh-totals">
                    <div><span>Subtotal</span><span>{{ money(subtotal) }}</span></div>
                    <div><span>Tax (10%)</span><span>{{ money(taxEstimate) }}</span></div>
                    <div v-if="deliveryEstimate > 0"><span>Delivery fee</span><span>{{ money(deliveryEstimate) }}</span></div>
                    <div class="bh-totals__grand"><span>Total</span><strong>{{ money(orderTotal) }}</strong></div>
                </div>
                <button
                    type="button"
                    class="bh-checkout"
                    :disabled="!cart.length || !acceptingOrders"
                    @click="goCheckout"
                >
                    Proceed to checkout
                </button>
            </div>
        </aside>

        <div v-if="mobileNavOpen" class="bh-backdrop" @click="mobileNavOpen = false" />
        <div v-if="mobileCartOpen" class="bh-backdrop bh-backdrop--cart" @click="mobileCartOpen = false" />
        <Toast />
    </div>

    <!-- ===================== RETAIL / WHOLESALE ===================== -->
    <div v-else class="rt-app" v-bind="colorModeAttrs" :class="{ 'is-nav-open': rtNavOpen }" :style="themeStyle">
        <div v-if="showAnnouncement && announcementItems.length" class="rt-announce">
            <div class="rt-announce__track">
                <span v-for="(item, i) in [...announcementItems, ...announcementItems]" :key="`${item}-${i}`">
                    {{ item }}
                </span>
            </div>
        </div>

        <header class="rt-header">
            <button type="button" class="rt-header__menu" aria-label="Open menu" @click="rtNavOpen = true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>

            <a class="rt-logo" href="#" @click.prevent="clearBrowse">
                <img v-if="storeLogo" :src="storeLogo" alt="" />
                <span>{{ brandName }}</span>
            </a>

            <nav
                class="rt-nav"
                :class="{ open: rtNavOpen }"
                aria-label="Store navigation"
                @click.self="rtNavOpen = false"
            >
                <div class="rt-nav__sheet">
                    <div class="rt-nav__sheet-head">
                        <strong>Menu</strong>
                        <button type="button" class="rt-nav__close" aria-label="Close menu" @click="rtNavOpen = false">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                    <template v-for="(link, linkIndex) in navLinks" :key="`${link.target}-${linkIndex}`">
                        <div
                            v-if="(link.target || '').toLowerCase() === 'categories'"
                            class="rt-nav__drop"
                            @mouseenter="openCatsMenu"
                            @mouseleave="scheduleCloseCatsMenu"
                        >
                            <button
                                type="button"
                                class="rt-nav__link"
                                :aria-expanded="catsMenuOpen"
                                @click="handleNavTarget('categories')"
                            >
                                {{ link.label }}
                                <svg viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <ul v-show="catsMenuOpen" class="rt-nav__menu" role="menu">
                                <li role="none">
                                    <button type="button" role="menuitem" @click="selectCategory('')">All products</button>
                                </li>
                                <li v-for="category in categoryStats" :key="category.id" role="none">
                                    <button type="button" role="menuitem" @click="selectCategory(category.id)">
                                        {{ category.name }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <button
                            v-else
                            type="button"
                            class="rt-nav__link"
                            :class="{ active: (link.target || '').toLowerCase() === 'home' && !isBrowsing }"
                            @click="handleNavTarget(link.target)"
                        >
                            {{ link.label }}
                        </button>
                    </template>
                </div>
            </nav>

            <div class="rt-actions">
                <div class="rt-search" :class="{ open: searchOpen }">
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="searchPlaceholder"
                        @input="onSearchInput"
                        @focus="searchOpen = true"
                    />
                    <button type="button" aria-label="Search" @click="searchOpen = !searchOpen">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <button
                    type="button"
                    class="rt-icon"
                    :title="`Theme: ${themeLabel}`"
                    :aria-label="`Theme: ${themeLabel}`"
                    @click="cycleTheme"
                >
                    <!-- Moon when light (click for dark), sun when dark, half when system -->
                    <svg v-if="themeMode === 'dark'" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <svg v-else-if="themeMode === 'system'" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="4" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 20h8M12 16v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none">
                        <path d="M21 14.5A8.5 8.5 0 1110.5 3a7 7 0 0010.5 11.5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button
                    type="button"
                    class="rt-icon"
                    :class="{ 'rt-icon--active': favoritesOnly }"
                    :title="customer ? 'Favorites' : 'Sign in to save favorites'"
                    aria-label="Wishlist"
                    @click="toggleFavoritesFilter"
                >
                    <svg viewBox="0 0 24 24" :fill="favoritesOnly ? 'currentColor' : 'none'"><path d="M12 21s-7-4.4-9.5-8.5C.5 9 2.5 5 6.5 5c2 0 3.5 1.2 4.5 2.5C12 6.2 13.5 5 15.5 5c4 0 6 4 4 7.5C19 16.6 12 21 12 21z" stroke="currentColor" stroke-width="1.8"/></svg>
                </button>
                <div
                    class="rt-account"
                    @mouseenter="openAccountMenu"
                    @mouseleave="scheduleCloseAccountMenu"
                >
                    <button
                        type="button"
                        class="rt-icon"
                        :class="{ 'rt-icon--active': !!customer }"
                        :title="customer ? customer.name : 'Account'"
                        aria-label="Account"
                        :aria-expanded="accountMenuOpen"
                        @click="accountMenuOpen ? closeAccountMenu() : openAccountMenu()"
                    >
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 100-8 4 4 0 000 8zM4 20a8 8 0 0116 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    </button>
                    <ul v-show="accountMenuOpen" class="rt-account__menu" role="menu">
                        <template v-if="customer">
                            <li class="rt-account__name" role="presentation">{{ customer.name }}</li>
                            <li role="none">
                                <Link :href="route('storefront.orders.index', setting.slug)" role="menuitem">My orders</Link>
                            </li>
                            <li role="none">
                                <button type="button" role="menuitem" @click="toggleFavoritesFilter">Favorites</button>
                            </li>
                            <li role="none">
                                <button type="button" role="menuitem" @click="cycleTheme">Theme: {{ themeLabel }}</button>
                            </li>
                            <li role="none">
                                <button type="button" role="menuitem" @click="logoutCustomer">Sign out</button>
                            </li>
                        </template>
                        <template v-else>
                            <li role="none">
                                <Link :href="route('storefront.login', setting.slug)" role="menuitem">Sign in</Link>
                            </li>
                            <li role="none">
                                <Link :href="route('storefront.register', setting.slug)" role="menuitem">Create account</Link>
                            </li>
                            <li role="none">
                                <button type="button" role="menuitem" @click="cycleTheme">Theme: {{ themeLabel }}</button>
                            </li>
                        </template>
                    </ul>
                </div>
                <button type="button" class="rt-icon rt-icon--cart" aria-label="Cart" @click="rtCartOpen = true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                    <span v-if="cartCount">{{ cartCount }}</span>
                </button>
            </div>
        </header>

        <template v-if="!isBrowsing">
            <section
                class="rt-hero"
                data-reveal
                @mouseenter="clearHeroTimer"
                @mouseleave="restartHeroTimer"
            >
                <div class="rt-hero__inner">
                    <div class="rt-hero__copy">
                        <Transition name="rt-hero-fade" mode="out-in">
                            <div :key="`copy-${heroSlideIndex}`">
                                <p class="rt-eyebrow">{{ activeHeroSlide?.eyebrow }}</p>
                                <h1>{{ activeHeroSlide?.headline }}</h1>
                                <p class="rt-hero__sub">{{ activeHeroSlide?.subheadline }}</p>
                                <div class="rt-hero__cta">
                                    <button
                                        type="button"
                                        class="rt-btn rt-btn--primary"
                                        @click="goToTarget(activeHeroSlide?.primary_cta_target || 'shop')"
                                    >
                                        {{ activeHeroSlide?.primary_cta_label || 'Shop Now' }}
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="rt-btn rt-btn--ghost"
                                        @click="goToTarget(activeHeroSlide?.secondary_cta_target || 'categories')"
                                    >
                                        {{ activeHeroSlide?.secondary_cta_label || 'Explore Collection' }}
                                    </button>
                                </div>
                            </div>
                        </Transition>
                        <p v-if="!acceptingOrders" class="rt-closed">Currently closed for orders</p>
                        <div v-if="hero.show_social_proof !== false" class="rt-proof">
                            <div class="rt-proof__avatars" aria-hidden="true">
                                <span /><span /><span /><span />
                            </div>
                            <span>{{ hero.social_proof || `Shop ${products.length}+ products online` }}</span>
                        </div>
                    </div>
                    <div class="rt-hero__visual">
                        <div class="rt-hero__glow" aria-hidden="true" />
                        <div class="rt-hero__stage">
                            <Transition name="rt-hero-fade" mode="out-in">
                                <img
                                    v-if="activeHeroSlide?.image"
                                    :key="`img-${heroSlideIndex}-${activeHeroSlide.image}`"
                                    :src="activeHeroSlide.image"
                                    :alt="activeHeroSlide.headline || brandName"
                                    class="rt-hero__img"
                                />
                                <div v-else :key="`ph-${heroSlideIndex}`" class="rt-hero__placeholder">{{ brandName.slice(0, 1) }}</div>
                            </Transition>
                        </div>
                        <article
                            v-for="(product, index) in hero.show_float_cards === false ? [] : heroFloats"
                            :key="product.id"
                            class="rt-float"
                            :class="`rt-float--${index}`"
                        >
                            <img v-if="product.image" :src="product.image" :alt="product.name" />
                            <div>
                                <strong>{{ product.name }}</strong>
                                <span>{{ money(productPrice(product)) }}</span>
                            </div>
                        </article>
                    </div>
                </div>
                <div v-if="heroSlides.length > 1" class="rt-hero__controls" aria-label="Hero slideshow controls">
                    <button type="button" class="rt-hero__nav" aria-label="Previous slide" @click="prevHeroSlide">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="rt-hero__dots">
                        <button
                            v-for="(_, index) in heroSlides"
                            :key="`dot-${index}`"
                            type="button"
                            class="rt-hero__dot"
                            :class="{ 'rt-hero__dot--active': index === heroSlideIndex }"
                            :aria-label="`Go to slide ${index + 1}`"
                            :aria-current="index === heroSlideIndex ? 'true' : undefined"
                            @click="goToHeroSlide(index)"
                        />
                    </div>
                    <button type="button" class="rt-hero__nav" aria-label="Next slide" @click="nextHeroSlide">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </section>

            <section v-if="trustSection.show !== false && trustItems.length" class="rt-trust" data-reveal>
                <div
                    v-for="(item, trustIndex) in trustItems"
                    :key="`${item.title}-${trustIndex}`"
                    class="rt-trust__item"
                    data-reveal
                    :style="{ '--reveal-delay': `${trustIndex * 70}ms` }"
                >
                    <svg v-if="trustIndex % 4 === 0" viewBox="0 0 24 24" fill="none"><path d="M3 7h13l3 5v5H3V7z" stroke="currentColor" stroke-width="1.6"/><circle cx="7.5" cy="18.5" r="1.5" fill="currentColor"/><circle cx="16.5" cy="18.5" r="1.5" fill="currentColor"/></svg>
                    <svg v-else-if="trustIndex % 4 === 1" viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M3 11h18" stroke="currentColor" stroke-width="1.6"/></svg>
                    <svg v-else-if="trustIndex % 4 === 2" viewBox="0 0 24 24" fill="none"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <svg v-else viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8v4l2.5 1.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <div>
                        <strong>{{ item.title }}</strong>
                        <span v-if="item.subtitle">{{ item.subtitle }}</span>
                    </div>
                </div>
            </section>

            <section
                v-if="categoriesSection.show !== false && categoryStats.length"
                id="rt-categories"
                class="rt-section"
                data-reveal
            >
                <div class="rt-section__head">
                    <h2>{{ categoriesSection.title || 'Shop by Categories' }}</h2>
                    <button type="button" class="rt-link" @click="selectCategory('')">
                        {{ categoriesSection.view_all_label || 'View All Categories' }} →
                    </button>
                </div>
                <div class="rt-cats">
                    <button
                        v-for="(category, catIndex) in categoryStats.slice(0, 6)"
                        :key="category.id"
                        type="button"
                        class="rt-cat"
                        data-reveal
                        :style="{ '--reveal-delay': `${catIndex * 55}ms` }"
                        @click="selectCategory(category.id)"
                    >
                        <img v-if="category.image" :src="category.image" :alt="category.name" />
                        <div v-else class="rt-cat__fallback">{{ category.name.slice(0, 1) }}</div>
                        <span class="rt-cat__overlay">
                            <strong>{{ category.name }}</strong>
                            <em>{{ categoriesSection.cta_label || 'Shop Now' }} →</em>
                        </span>
                    </button>
                </div>
            </section>

            <section v-if="arrivalsSection.show !== false" id="rt-arrivals" class="rt-section" data-reveal>
                <div class="rt-section__head">
                    <h2>{{ arrivalsSection.title || 'New Arrivals' }}</h2>
                    <button type="button" class="rt-link" @click="selectCategory('')">
                        {{ arrivalsSection.view_all_label || 'View All New Arrivals' }} →
                    </button>
                </div>
                <div class="rt-rail-wrap">
                    <button
                        type="button"
                        class="rt-rail__nav rt-rail__nav--prev"
                        aria-label="Previous products"
                        @click="scrollRail('rt-shop', -1)"
                    >
                        <svg viewBox="0 0 24 24" fill="none"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div id="rt-shop" class="rt-rail">
                        <article
                            v-for="(product, index) in newArrivals"
                            :key="product.id"
                            class="rt-card"
                            data-reveal
                            :style="{ '--reveal-delay': `${Math.min(index, 6) * 50}ms` }"
                        >
                            <div class="rt-card__media">
                                <span
                                    class="rt-card__badge"
                                    :class="`rt-card__badge--${retailBadge(index).tone}`"
                                >{{ retailBadge(index).label }}</span>
                                <button
                                    type="button"
                                    class="rt-card__wish"
                                    :class="{ 'rt-card__wish--on': isFavorite(product.id) }"
                                    :aria-label="isFavorite(product.id) ? 'Remove favorite' : 'Add favorite'"
                                    @click="toggleFavorite(product.id)"
                                >
                                    <svg viewBox="0 0 24 24" :fill="isFavorite(product.id) ? 'currentColor' : 'none'"><path d="M12 21s-7-4.4-9.5-8.5C.5 9 2.5 5 6.5 5c2 0 3.5 1.2 4.5 2.5C12 6.2 13.5 5 15.5 5c4 0 6 4 4 7.5C19 16.6 12 21 12 21z" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
                                <div class="rt-card__frame">
                                    <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" />
                                    <div v-else class="rt-card__empty" />
                                </div>
                            </div>
                            <div class="rt-card__body">
                                <h3>{{ product.name }}</h3>
                                <div class="rt-card__price">
                                    <strong>{{ money(productPrice(product)) }}</strong>
                                    <s v-if="retailComparePrice(productPrice(product), index)">
                                        {{ money(retailComparePrice(productPrice(product), index)!) }}
                                    </s>
                                </div>
                                <div class="rt-card__footer">
                                    <div class="rt-card__rating" aria-label="Rating">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            class="rt-card__star"
                                            :class="{ on: star <= (myRatingFor(product.id) || retailRating(product, index).stars) }"
                                            :aria-label="`Rate ${star} stars`"
                                            @click="rateProduct(product, star)"
                                        >★</button>
                                        <em>({{ retailRating(product, index).count }})</em>
                                    </div>
                                    <button
                                        type="button"
                                        class="rt-card__add"
                                        :disabled="!acceptingOrders"
                                        aria-label="Add to cart"
                                        @click="addToCart(product)"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <button
                        type="button"
                        class="rt-rail__nav rt-rail__nav--next"
                        aria-label="Next products"
                        @click="scrollRail('rt-shop', 1)"
                    >
                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </section>

            <section
                v-if="bestsellersSection.show !== false && bestSellers.length"
                id="rt-bestsellers"
                class="rt-section"
                data-reveal
            >
                <div class="rt-section__head">
                    <h2>{{ bestsellersSection.title || 'Best Sellers' }}</h2>
                    <button
                        type="button"
                        class="rt-link"
                        @click="selectCategory('')"
                    >
                        {{ bestsellersSection.view_all_label || 'View All Best Sellers' }} →
                    </button>
                </div>
                <div class="rt-best">
                    <article
                        v-for="(product, index) in bestSellers"
                        :key="product.id"
                        class="rt-best__card"
                        data-reveal
                        :style="{ '--reveal-delay': `${index * 80}ms` }"
                    >
                        <div class="rt-best__media">
                            <span class="rt-best__badge">{{ bestsellersSection.badge_label || 'Bestseller' }}</span>
                            <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" />
                            <div v-else class="rt-best__empty">{{ product.name.slice(0, 1) }}</div>
                        </div>
                        <div class="rt-best__body">
                            <h3>{{ product.name }}</h3>
                            <strong class="rt-best__price">{{ money(productPrice(product)) }}</strong>
                            <div class="rt-card__rating" aria-label="Rating">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    type="button"
                                    class="rt-card__star"
                                    :class="{ on: star <= (myRatingFor(product.id) || retailRating(product, index + 2).stars) }"
                                    :aria-label="`Rate ${star} stars`"
                                    @click="rateProduct(product, star)"
                                >★</button>
                                <em>({{ retailRating(product, index + 2).count }})</em>
                            </div>
                            <p class="rt-best__desc">{{ retailBlurb(product) }}</p>
                            <div class="rt-best__actions">
                                <button
                                    type="button"
                                    class="rt-btn rt-btn--dark rt-best__quick"
                                    :disabled="!acceptingOrders"
                                    @click="addToCart(product)"
                                >
                                    {{ bestsellersSection.quick_add_label || 'Quick Add' }}
                                </button>
                                <button
                                    type="button"
                                    class="rt-best__cart"
                                    :disabled="!acceptingOrders"
                                    aria-label="Add to cart"
                                    @click="addToCart(product)"
                                >
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section
                v-if="promoSale.show !== false || promoCollection.show !== false"
                class="rt-promos"
                data-reveal
            >
                <div v-if="promoSale.show !== false" class="rt-promo rt-promo--sale" data-reveal>
                    <p class="rt-eyebrow">{{ promoSale.eyebrow || 'Limited Time' }}</p>
                    <h3>{{ promoSale.title || 'Flash Sale' }}</h3>
                    <p>{{ promoSale.subtitle || `Save more — use code ${promoCode()}` }}</p>
                    <div class="rt-countdown" aria-label="Sale countdown">
                        <div><strong>{{ countdown.days }}</strong><span>Days</span></div>
                        <div><strong>{{ countdown.hours }}</strong><span>Hours</span></div>
                        <div><strong>{{ countdown.minutes }}</strong><span>Mins</span></div>
                        <div><strong>{{ countdown.seconds }}</strong><span>Secs</span></div>
                    </div>
                    <button
                        type="button"
                        class="rt-btn rt-btn--dark"
                        @click="goToTarget(promoSale.cta_target || 'shop')"
                    >
                        {{ promoSale.cta_label || 'Shop Sale Now' }}
                    </button>
                </div>
                <div
                    v-if="promoCollection.show !== false"
                    class="rt-promo rt-promo--dark"
                    data-reveal
                    :style="{
                        '--reveal-delay': '90ms',
                        ...(collectionImage
                            ? {
                                  backgroundImage: `linear-gradient(120deg, rgb(0 0 0 / 0.78), rgb(0 0 0 / 0.45)), url(${collectionImage})`,
                              }
                            : {}),
                    }"
                >
                    <p class="rt-eyebrow">{{ promoCollection.eyebrow || 'New Collection' }}</p>
                    <h3>{{ promoCollection.title || setting.tagline || 'Fresh picks this season' }}</h3>
                    <p>
                        {{
                            promoCollection.subtitle ||
                            (store.city ? `Available for ${store.city}` : 'Browse the latest arrivals online.')
                        }}
                    </p>
                    <button
                        type="button"
                        class="rt-btn rt-btn--light"
                        @click="goToTarget(promoCollection.cta_target || 'arrivals')"
                    >
                        {{ promoCollection.cta_label || 'Explore' }}
                    </button>
                </div>
            </section>

            <section id="rt-about" class="rt-section rt-info" data-reveal>
                <h2>About</h2>
                <p>{{ homepage.about_text || setting.description || 'Quality products, clear pricing, and checkout in minutes.' }}</p>
            </section>

            <section id="rt-contact" class="rt-section rt-info" data-reveal>
                <h2>Contact</h2>
                <p>
                    {{
                        homepage.contact_text ||
                        'Reach out anytime — we’re happy to help with orders and product questions.'
                    }}
                </p>
                <p v-if="setting.support_phone || setting.support_email" class="rt-info__meta">
                    <span v-if="setting.support_phone">{{ setting.support_phone }}</span>
                    <span v-if="setting.support_phone && setting.support_email"> · </span>
                    <span v-if="setting.support_email">{{ setting.support_email }}</span>
                </p>
            </section>
        </template>

        <section v-else class="rt-section rt-catalog" data-reveal>
            <div class="rt-catalog__layout">
                <aside class="rt-filters">
                    <h3>Filters</h3>
                    <div class="rt-filters__block">
                        <p class="rt-filters__label">Price range</p>
                        <div class="rt-filters__values">
                            <span>{{ money(priceMin) }}</span>
                            <span>{{ money(priceMax) }}</span>
                        </div>
                        <label>
                            Min
                            <input
                                v-model.number="priceMin"
                                type="range"
                                :min="priceBoundMin"
                                :max="priceBoundMax"
                                step="1"
                                @change="clampPriceRange"
                            />
                        </label>
                        <label>
                            Max
                            <input
                                v-model.number="priceMax"
                                type="range"
                                :min="priceBoundMin"
                                :max="priceBoundMax"
                                step="1"
                                @change="clampPriceRange"
                            />
                        </label>
                        <button type="button" class="rt-filters__reset" @click="resetPriceFilter">Reset price</button>
                    </div>
                    <div class="rt-filters__block">
                        <p class="rt-filters__label">Saved</p>
                        <button
                            type="button"
                            class="rt-filters__chip"
                            :class="{ active: favoritesOnly }"
                            @click="toggleFavoritesFilter"
                        >
                            Favorites only
                        </button>
                    </div>
                </aside>

                <div class="rt-catalog__main">
                    <div class="rt-section__head">
                        <div>
                            <button type="button" class="rt-link" @click="clearBrowse">← Back to home</button>
                            <h2>
                                {{
                                    favoritesOnly
                                        ? 'Your favorites'
                                        : activeCategory
                                          ? pageTitle
                                          : `Results for “${search || 'all products'}”`
                                }}
                            </h2>
                        </div>
                        <p class="rt-count">{{ filteredProducts.length }} products</p>
                    </div>
                    <div class="rt-grid">
                        <article
                            v-for="(product, index) in filteredProducts"
                            :key="product.id"
                            class="rt-card"
                            data-reveal
                            :style="{ '--reveal-delay': `${Math.min(index, 10) * 40}ms` }"
                        >
                            <div class="rt-card__media">
                                <span
                                    class="rt-card__badge"
                                    :class="`rt-card__badge--${retailBadge(index).tone}`"
                                >{{ retailBadge(index).label }}</span>
                                <button
                                    type="button"
                                    class="rt-card__wish"
                                    :class="{ 'rt-card__wish--on': isFavorite(product.id) }"
                                    :aria-label="isFavorite(product.id) ? 'Remove favorite' : 'Add favorite'"
                                    @click="toggleFavorite(product.id)"
                                >
                                    <svg viewBox="0 0 24 24" :fill="isFavorite(product.id) ? 'currentColor' : 'none'"><path d="M12 21s-7-4.4-9.5-8.5C.5 9 2.5 5 6.5 5c2 0 3.5 1.2 4.5 2.5C12 6.2 13.5 5 15.5 5c4 0 6 4 4 7.5C19 16.6 12 21 12 21z" stroke="currentColor" stroke-width="1.8"/></svg>
                                </button>
                                <div class="rt-card__frame">
                                    <img v-if="product.image" :src="product.image" :alt="product.name" loading="lazy" />
                                    <div v-else class="rt-card__empty" />
                                </div>
                            </div>
                            <div class="rt-card__body">
                                <h3>{{ product.name }}</h3>
                                <div class="rt-card__price">
                                    <strong>{{ money(productPrice(product)) }}</strong>
                                    <s v-if="retailComparePrice(productPrice(product), index)">
                                        {{ money(retailComparePrice(productPrice(product), index)!) }}
                                    </s>
                                </div>
                                <div class="rt-card__footer">
                                    <div class="rt-card__rating" aria-label="Rating">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            class="rt-card__star"
                                            :class="{ on: star <= (myRatingFor(product.id) || retailRating(product, index).stars) }"
                                            :aria-label="`Rate ${star} stars`"
                                            @click="rateProduct(product, star)"
                                        >★</button>
                                        <em>({{ retailRating(product, index).count }})</em>
                                    </div>
                                    <button
                                        type="button"
                                        class="rt-card__add"
                                        :disabled="!acceptingOrders"
                                        aria-label="Add to cart"
                                        @click="addToCart(product)"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                    <p v-if="!filteredProducts.length" class="rt-empty">No products match your filters.</p>
                </div>
            </div>
        </section>

        <footer class="rt-foot" data-reveal>
            <div v-if="footerTrust.show !== false && footerTrustItems.length" class="rt-trust rt-trust--foot">
                <div
                    v-for="(item, footIndex) in footerTrustItems"
                    :key="`${item.title}-${footIndex}`"
                    class="rt-trust__item"
                >
                    <strong>{{ item.title }}</strong>
                </div>
            </div>
            <p v-if="homepage.footer_tagline" class="rt-foot__tagline">{{ homepage.footer_tagline }}</p>
            <p>© {{ brandName }}</p>
        </footer>

        <aside class="rt-cart" :class="{ open: rtCartOpen }" aria-label="Shopping cart">
            <div class="rt-cart__head">
                <div>
                    <h3>Your cart</h3>
                    <p class="rt-cart__count">{{ cartCount }} {{ cartCount === 1 ? 'item' : 'items' }}</p>
                </div>
                <button type="button" class="rt-cart__close" aria-label="Close cart" @click="rtCartOpen = false">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div v-if="!cart.length" class="rt-cart__empty">
                <div class="rt-cart__empty-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="17" cy="20" r="1.2" fill="currentColor"/></svg>
                </div>
                <strong>Your cart is empty</strong>
                <span>Browse products and add what you love.</span>
                <button type="button" class="rt-cart__continue" @click="rtCartOpen = false; goToTarget('shop')">
                    Continue shopping
                </button>
            </div>

            <div v-else class="rt-cart__list">
                <article v-for="item in cart" :key="item.key" class="rt-cart__row">
                    <div class="rt-cart__thumb">
                        <img v-if="item.image" :src="item.image" :alt="item.name" />
                        <span v-else>{{ item.name.slice(0, 1) }}</span>
                    </div>
                    <div class="rt-cart__meta">
                        <div class="rt-cart__top">
                            <strong>{{ item.name }}</strong>
                            <button type="button" class="rt-cart__remove" aria-label="Remove item" @click="removeFromCart(item.key)">
                                <svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M9 7l1-2h4l1 2M8 7l1 12a1 1 0 001 1h4a1 1 0 001-1l1-12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <p class="rt-cart__unit">{{ money(item.unit_price) }} each</p>
                        <div class="rt-cart__controls">
                            <div class="rt-cart__qty">
                                <button type="button" aria-label="Decrease" @click="changeQty(item.key, -1)">−</button>
                                <em>{{ item.qty }}</em>
                                <button type="button" aria-label="Increase" @click="changeQty(item.key, 1)">+</button>
                            </div>
                            <strong class="rt-cart__line">{{ money(item.qty * item.unit_price) }}</strong>
                        </div>
                    </div>
                </article>
            </div>

            <div class="rt-cart__foot">
                <div class="rt-cart__summary">
                    <div><span>Subtotal</span><span>{{ money(subtotal) }}</span></div>
                    <div v-if="taxEstimate > 0"><span>Est. tax</span><span>{{ money(taxEstimate) }}</span></div>
                    <div v-if="deliveryEstimate > 0"><span>Delivery</span><span>{{ money(deliveryEstimate) }}</span></div>
                    <div class="rt-cart__grand"><span>Total</span><strong>{{ money(orderTotal) }}</strong></div>
                </div>
                <button
                    type="button"
                    class="rt-cart__checkout"
                    :disabled="!cart.length || !acceptingOrders"
                    @click="goCheckout"
                >
                    Proceed to checkout
                </button>
                <button type="button" class="rt-cart__keep" @click="rtCartOpen = false">Keep shopping</button>
            </div>
        </aside>
        <div v-if="rtCartOpen" class="rt-backdrop" @click="rtCartOpen = false" />
        <Toast />
    </div>

    <Teleport to="body">
        <div
            v-if="ratingModal"
            class="sf-rate"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sf-rate-title"
            @keydown.esc="closeRatingModal"
        >
            <button type="button" class="sf-rate__scrim" aria-label="Close" @click="closeRatingModal" />
            <div class="sf-rate__card">
                <button type="button" class="sf-rate__close" aria-label="Close" @click="closeRatingModal">✕</button>
                <div class="sf-rate__product">
                    <img v-if="ratingModal.productImage" :src="ratingModal.productImage" :alt="ratingModal.productName" />
                    <div v-else class="sf-rate__fallback">{{ ratingModal.productName.slice(0, 1) }}</div>
                    <div>
                        <p class="sf-rate__eyebrow">Rate your experience</p>
                        <h3 id="sf-rate-title">{{ ratingModal.productName }}</h3>
                    </div>
                </div>

                <div class="sf-rate__stars" role="group" aria-label="Star rating">
                    <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        class="sf-rate__star"
                        :class="{ on: star <= ratingModal.rating }"
                        :aria-label="`${star} stars`"
                        @click="setModalRating(star)"
                    >★</button>
                </div>

                <label class="sf-rate__label">
                    <span>Comment about your experience <i>*</i></span>
                    <textarea
                        v-model="ratingModal.review"
                        rows="4"
                        maxlength="1000"
                        required
                        placeholder="What did you like? How was the quality, fit, or service?"
                    />
                    <em>{{ ratingModal.review.length }}/1000</em>
                </label>

                <div class="sf-rate__actions">
                    <button type="button" class="sf-rate__cancel" :disabled="ratingSubmitting" @click="closeRatingModal">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="sf-rate__submit"
                        :disabled="ratingSubmitting || !ratingModal.review.trim()"
                        @click="submitRating"
                    >
                        {{ ratingSubmitting ? 'Saving…' : 'Submit review' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.bh-app {
    --bh-bg: #f5f5f4;
    --bh-ink: #1c1917;
    --bh-muted: #78716c;
    --bh-line: #e7e5e4;
    --bh-panel: #ffffff;
    --bh-elevated: #f5f5f4;
    --bh-shadow: rgb(0 0 0 / 0.12);
    display: grid;
    grid-template-columns: 14.5rem minmax(0, 1fr) 17.5rem;
    min-height: 100vh;
    min-height: 100dvh;
    background: var(--bh-bg);
    color: var(--bh-ink);
    font-family: Inter, system-ui, sans-serif;
    color-scheme: light;
}
.bh-app[data-color-mode='dark'] {
    --bh-bg: #0c0c0c;
    --bh-ink: #f5f5f4;
    --bh-muted: #a8a29e;
    --bh-line: #292524;
    --bh-panel: #171717;
    --bh-elevated: #1c1c1c;
    --bh-shadow: rgb(0 0 0 / 0.45);
    color-scheme: dark;
}
.bh-theme-ico {
    display: inline-grid;
    place-items: center;
    width: 1.15rem;
    height: 1.15rem;
    flex-shrink: 0;
}
.bh-theme-ico svg {
    width: 1.05rem;
    height: 1.05rem;
}

@media (max-width: 1280px) {
    .bh-app {
        grid-template-columns: 13.5rem minmax(0, 1fr) 16rem;
    }
}

@media (max-width: 1100px) {
    .bh-app {
        grid-template-columns: 1fr;
    }
}

.bh-mobile-bar__btn {
    display: none;
}
@media (max-width: 1100px) {
    .bh-mobile-nav,
    .bh-mobile-cart {
        display: inline-flex;
        position: fixed;
        z-index: 40;
        bottom: max(1rem, env(safe-area-inset-bottom));
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        background: var(--bh-ink);
        color: #fff;
        padding: 0.7rem 1rem;
        font-size: 0.85rem;
        font-weight: 700;
        box-shadow: 0 10px 30px rgb(0 0 0 / 0.2);
    }
    .bh-mobile-nav { left: max(1rem, env(safe-area-inset-left)); }
    .bh-mobile-cart { right: max(1rem, env(safe-area-inset-right)); background: var(--bh-accent); }
    .bh-mobile-cart span {
        background: #fff;
        color: var(--bh-accent);
        border-radius: 999px;
        min-width: 1.25rem;
        height: 1.25rem;
        display: inline-grid;
        place-items: center;
        font-size: 0.7rem;
    }
}

/* Left sidebar */
.bh-sidebar {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    background: var(--bh-panel);
    border-right: 1px solid var(--bh-line);
    padding: 1.35rem 1rem 1.25rem;
}
.bh-brand {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin-bottom: 1.5rem;
    padding: 0 0.35rem;
}
.bh-brand__logo {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.65rem;
    object-fit: cover;
}
.bh-brand__icon {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.65rem;
    background: var(--bh-accent-soft);
    color: var(--bh-accent);
}
.bh-brand__icon svg { width: 1.2rem; height: 1.2rem; }
.bh-brand__name {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.bh-nav-label {
    margin: 0.85rem 0 0.4rem;
    padding: 0 0.55rem;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #a8a29e;
}
.bh-nav {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}
.bh-nav__item {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    border-radius: 0.85rem;
    padding: 0.7rem 0.75rem;
    text-align: left;
    font-size: 0.9rem;
    font-weight: 600;
    color: #44403c;
    transition: background 0.15s ease, color 0.15s ease;
}
.bh-nav__item:hover:not(:disabled) {
    background: #f5f5f4;
}
.bh-nav__item.active {
    background: var(--bh-accent);
    color: #fff;
    box-shadow: 0 8px 18px rgb(var(--bh-accent-rgb) / 0.28);
}
.bh-nav__item:disabled {
    opacity: 0.45;
    cursor: default;
}
.bh-nav__ico {
    width: 1.15rem;
    height: 1.15rem;
    border-radius: 0.3rem;
    background: currentColor;
    opacity: 0.85;
    -webkit-mask: center / contain no-repeat;
    mask: center / contain no-repeat;
}
.bh-nav__ico[data-icon='burger'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 10h16v2H4zm1 4h14l-1 5H6zm7-8a4 4 0 014 4H8a4 4 0 014-4z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M4 10h16v2H4zm1 4h14l-1 5H6zm7-8a4 4 0 014 4H8a4 4 0 014-4z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='fries'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 3h2v6H7zm4 0h2v6h-2zm4 0h2v6h-2zM6 10h12l-1 11H7z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 3h2v6H7zm4 0h2v6h-2zm4 0h2v6h-2zM6 10h12l-1 11H7z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='drink'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8 2h8l1 4H7zm-1 5h10l-1.2 14H7.8z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M8 2h8l1 4H7zm-1 5h10l-1.2 14H7.8z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='dessert'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3c2 2 3 3.5 3 5.5A3 3 0 019 8.5C9 6.5 10 5 12 3zM6 14h12l-1 7H7z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 3c2 2 3 3.5 3 5.5A3 3 0 019 8.5C9 6.5 10 5 12 3zM6 14h12l-1 7H7z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='plate'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 4a8 8 0 110 16 8 8 0 010-16zm0 3a5 5 0 100 10 5 5 0 000-10z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 4a8 8 0 110 16 8 8 0 010-16zm0 3a5 5 0 100 10 5 5 0 000-10z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='user'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='clock'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 100 20 10 10 0 000-20zm1 5v5.2l3.5 2.1-.8 1.3L11 13V7z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 2a10 10 0 100 20 10 10 0 000-20zm1 5v5.2l3.5 2.1-.8 1.3L11 13V7z'/%3E%3C/svg%3E");
}
.bh-nav__ico[data-icon='heart'] {
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 21s-7-4.4-9.5-8.5C.5 9 2.5 5 6.5 5c2 0 3.5 1.2 4.5 2.5C12 6.2 13.5 5 15.5 5c4 0 6 4 4 7.5C19 16.6 12 21 12 21z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 21s-7-4.4-9.5-8.5C.5 9 2.5 5 6.5 5c2 0 3.5 1.2 4.5 2.5C12 6.2 13.5 5 15.5 5c4 0 6 4 4 7.5C19 16.6 12 21 12 21z'/%3E%3C/svg%3E");
}

.bh-promo {
    margin-top: auto;
    border-radius: 1.1rem;
    background: linear-gradient(145deg, var(--bh-accent), color-mix(in srgb, var(--bh-accent) 70%, #9a3412));
    color: #fff;
    padding: 1.1rem;
}
.bh-promo__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
}
.bh-promo__text {
    margin: 0.35rem 0 0.85rem;
    font-size: 0.8rem;
    line-height: 1.4;
    opacity: 0.92;
}
.bh-promo__btn {
    border-radius: 0.55rem;
    background: #fff;
    color: var(--bh-accent);
    padding: 0.45rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
}

/* Center */
.bh-main {
    padding: 1.15rem 1.15rem 2rem;
    overflow: auto;
    min-width: 0;
}
@media (max-width: 1100px) {
    .bh-main {
        padding: 1rem 0.85rem 5.5rem;
    }
}
@media (max-width: 480px) {
    .bh-main {
        padding: 0.75rem 0.65rem 5.5rem;
    }
}
.bh-main__head {
    display: flex;
    align-items: center;
    margin-bottom: 0.85rem;
}
.bh-main__head h1 {
    margin: 0;
    font-size: clamp(1.25rem, 2.8vw, 1.75rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}
.bh-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    border-radius: 999px;
    background: var(--bh-panel);
    border: 1.5px solid var(--bh-line);
    padding: 0.35rem 0.85rem;
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.03);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.bh-search:focus-within {
    border-color: var(--bh-accent, #f97316);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--bh-accent, #f97316) 22%, transparent);
}
.bh-search svg {
    width: 1rem;
    height: 1rem;
    color: var(--bh-muted);
    flex-shrink: 0;
}
.bh-search input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: none;
    background: transparent;
    font: inherit;
    font-size: 0.875rem;
    color: var(--bh-ink);
}
.bh-search input::placeholder {
    color: var(--bh-muted);
}
.bh-search input:focus {
    outline: none;
    box-shadow: none;
}

.bh-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}
.bh-chip {
    border-radius: 999px;
    border: 1px solid var(--bh-line);
    background: var(--bh-panel);
    padding: 0.45rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #57534e;
}
.bh-chip.active {
    background: var(--bh-accent);
    border-color: var(--bh-accent);
    color: #fff;
}

.bh-section-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.9rem;
}
.bh-section-head h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
}
.bh-closed {
    margin: 0;
    color: #c2410c;
    font-size: 0.8rem;
    font-weight: 600;
}

.bh-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(132px, 152px));
    justify-content: start;
    gap: 0.65rem;
}
@media (max-width: 720px) {
    .bh-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.5rem;
    }
}
@media (max-width: 380px) {
    .bh-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.bh-card {
    overflow: hidden;
    max-width: 152px;
    width: 100%;
    border-radius: 0.85rem;
    background: var(--bh-panel);
    border: 1px solid var(--bh-line);
    box-shadow: 0 4px 14px rgb(28 25 23 / 0.04);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
@media (max-width: 720px) {
    .bh-card {
        max-width: none;
    }
}
.bh-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgb(28 25 23 / 0.08);
}
.bh-card__media {
    position: relative;
    height: 96px;
    overflow: hidden;
    background: linear-gradient(145deg, #f5f5f4, #e7e5e4);
}
.bh-card__wish {
    position: absolute;
    top: 0.35rem;
    right: 0.35rem;
    z-index: 2;
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 999px;
    background: #fff;
    color: #9ca3af;
    font-size: 0.75rem;
    box-shadow: 0 4px 10px rgb(0 0 0 / 0.1);
}
.bh-card__wish.on { color: #e11d48; }
.bh-card__rating {
    display: flex;
    align-items: center;
    gap: 0.1rem;
    margin: 0.2rem 0 0.35rem;
}
.bh-card__star {
    padding: 0;
    color: #d1d5db;
    font-size: 0.72rem;
    line-height: 1;
}
.bh-card__star.on { color: #f5a524; }
.bh-card__rating em {
    margin-left: 0.15rem;
    color: #9ca3af;
    font-style: normal;
    font-size: 0.65rem;
}
.bh-card__media img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
@media (max-width: 720px) {
    .bh-card__media {
        height: 88px;
    }
}
.bh-card__body {
    padding: 0.45rem 0.55rem 0.55rem;
}
.bh-card__badge {
    display: inline-block;
    margin-bottom: 0.25rem;
    border-radius: 0.3rem;
    background: color-mix(in srgb, var(--bh-accent) 16%, white);
    color: var(--bh-accent);
    padding: 0.12rem 0.35rem;
    font-size: 0.55rem;
    font-weight: 800;
    letter-spacing: 0.04em;
}
.bh-card__body h3 {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: -0.01em;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bh-card__desc {
    margin: 0.2rem 0 0.55rem;
    font-size: 0.68rem;
    line-height: 1.35;
    color: var(--bh-muted);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 0;
}
.bh-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
}
.bh-card__foot strong {
    font-size: 0.78rem;
    font-weight: 800;
}
.bh-card__add {
    width: 1.7rem;
    height: 1.7rem;
    flex-shrink: 0;
    border-radius: 0.5rem;
    background: var(--bh-accent);
    color: #fff;
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1;
    box-shadow: 0 4px 10px rgb(var(--bh-accent-rgb) / 0.28);
}
.bh-card__add:disabled {
    opacity: 0.4;
}
.bh-empty {
    padding: 3rem 1rem;
    text-align: center;
    color: var(--bh-muted);
}

/* Right order panel */
.bh-order {
    display: flex;
    flex-direction: column;
    background: #fafafa;
    border-left: 1px solid var(--bh-line);
    padding: 1.15rem 1rem 1.25rem;
}
.bh-order__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--bh-line);
}
.bh-order__head h2 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.bh-order__count {
    margin: 0.2rem 0 0;
    color: var(--bh-muted);
    font-size: 0.78rem;
    font-weight: 600;
}
.bh-order__close {
    display: none;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    color: var(--bh-muted);
    background: #fff;
    border: 1px solid var(--bh-line);
}
.bh-order__close svg {
    width: 0.95rem;
    height: 0.95rem;
    margin: 0 auto;
}

.bh-order__list {
    flex: 1;
    overflow: auto;
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.65rem;
    align-content: start;
}
.bh-order__item {
    display: grid;
    grid-template-columns: 3.5rem minmax(0, 1fr);
    gap: 0.75rem;
    align-items: start;
    padding: 0.75rem;
    border-radius: 1rem;
    background: #fff;
    border: 1px solid #f0eeec;
}
.bh-order__thumb {
    width: 3.5rem;
    height: 3.5rem;
    overflow: hidden;
    border-radius: 0.8rem;
    background: #f3f4f6;
    display: grid;
    place-items: center;
    color: #9ca3af;
    font-weight: 800;
}
.bh-order__thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
.bh-order__meta {
    min-width: 0;
}
.bh-order__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.45rem;
}
.bh-order__name {
    margin: 0;
    font-size: 0.84rem;
    font-weight: 700;
    line-height: 1.3;
}
.bh-order__remove {
    color: #9ca3af;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.4rem;
    flex-shrink: 0;
}
.bh-order__remove:hover { color: #e11d48; background: #fef2f2; }
.bh-order__remove svg { width: 0.9rem; height: 0.9rem; }
.bh-order__unit {
    margin: 0.2rem 0 0.55rem;
    color: var(--bh-muted);
    font-size: 0.72rem;
    font-weight: 600;
}
.bh-order__controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.bh-order__price {
    color: var(--bh-ink);
    font-size: 0.88rem;
    font-weight: 800;
}
.bh-qty {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    border-radius: 999px;
    background: #f5f5f4;
    padding: 0.12rem;
    border: 1px solid #ebe8e6;
}
.bh-qty button {
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 999px;
    background: #fff;
    font-weight: 700;
    color: var(--bh-ink);
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
}
.bh-qty span {
    min-width: 1.15rem;
    text-align: center;
    font-size: 0.8rem;
    font-weight: 700;
}
.bh-order__empty {
    flex: 1;
    display: grid;
    place-content: center;
    justify-items: center;
    text-align: center;
    color: var(--bh-muted);
    gap: 0.35rem;
    padding: 2rem 0.5rem;
}
.bh-order__empty-ico {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #fff;
    border: 1px solid var(--bh-line);
    color: #a8a29e;
    margin-bottom: 0.35rem;
}
.bh-order__empty-ico svg { width: 1.35rem; height: 1.35rem; }
.bh-order__empty p {
    margin: 0;
    font-weight: 800;
    color: var(--bh-ink);
}
.bh-order__empty span {
    font-size: 0.82rem;
}

.bh-order__foot {
    margin-top: auto;
    padding-top: 1rem;
}
.bh-totals {
    display: grid;
    gap: 0.45rem;
    padding: 0.9rem;
    border-radius: 1rem;
    background: #fff;
    border: 1px solid #f0eeec;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    color: var(--bh-muted);
}
.bh-totals > div {
    display: flex;
    justify-content: space-between;
}
.bh-totals__grand {
    margin-top: 0.35rem;
    padding-top: 0.55rem;
    border-top: 1px dashed var(--bh-line);
    color: var(--bh-ink);
    font-size: 1rem;
}
.bh-checkout {
    display: inline-flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border-radius: 999px;
    background: var(--bh-accent);
    color: #fff;
    padding: 0.9rem 1rem;
    font-size: 0.92rem;
    font-weight: 800;
    box-shadow: 0 10px 24px rgb(var(--bh-accent-rgb) / 0.28);
}
.bh-checkout:disabled {
    opacity: 0.45;
}
.bh-checkout svg {
    width: 1.15rem;
    height: 1.15rem;
}

@media (max-width: 1100px) {
    .bh-sidebar,
    .bh-order {
        position: fixed;
        top: 0;
        bottom: 0;
        z-index: 60;
        width: min(18rem, 88vw);
        max-width: 100%;
        transform: translateX(-105%);
        transition: transform 0.28s ease;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }
    .bh-order {
        right: 0;
        left: auto;
        transform: translateX(105%);
        border-left: 0;
        box-shadow: -12px 0 40px rgb(0 0 0 / 0.12);
    }
    .bh-sidebar.open,
    .bh-order.open {
        transform: translateX(0);
    }
    .bh-order__close {
        display: inline-flex;
    }
    .bh-sidebar {
        box-shadow: 12px 0 40px rgb(0 0 0 / 0.12);
    }
}

@media (max-width: 480px) {
    .bh-chips {
        gap: 0.35rem;
        margin-bottom: 0.85rem;
    }
    .bh-chip {
        padding: 0.35rem 0.7rem;
        font-size: 0.72rem;
    }
    .bh-section-head h2 {
        font-size: 0.95rem;
    }
}

.bh-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    background: rgb(0 0 0 / 0.35);
}

/* Retail / wholesale storefront */
.rt-app {
    --rt-accent: #ff5a1f;
    --rt-gutter: clamp(1.25rem, 5vw, 4.5rem);
    --rt-max: 100%;
    --rt-bg: #ffffff;
    --rt-ink: #0f0f0f;
    --rt-muted: #6b7280;
    --rt-line: #ececec;
    --rt-panel: #ffffff;
    --rt-elevated: #f5f5f5;
    --rt-soft: #fafafa;
    --rt-header-bg: rgb(255 255 255 / 0.96);
    --rt-shadow: rgb(15 23 42 / 0.12);
    width: 100%;
    min-height: 100vh;
    background: var(--rt-bg);
    color: var(--rt-ink);
    font-family: 'Plus Jakarta Sans', Inter, system-ui, sans-serif;
    overflow-x: clip;
    color-scheme: light;
}
.rt-app[data-color-mode='dark'] {
    --rt-bg: #0b0b0c;
    --rt-ink: #f4f4f5;
    --rt-muted: #a1a1aa;
    --rt-line: #2a2a2e;
    --rt-panel: #161618;
    --rt-elevated: #1e1e22;
    --rt-soft: #121214;
    --rt-header-bg: rgb(12 12 14 / 0.94);
    --rt-shadow: rgb(0 0 0 / 0.5);
    color-scheme: dark;
}
.rt-announce {
    overflow: hidden;
    background: #111;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.02em;
}
.rt-announce__track {
    display: flex;
    gap: 3rem;
    width: max-content;
    padding: 0.55rem 1rem;
    animation: rt-marquee 28s linear infinite;
}
.rt-announce__track span {
    white-space: nowrap;
    opacity: 0.92;
}
@keyframes rt-marquee {
    from { transform: translateX(0); }
    to { transform: translateX(-50%); }
}

.rt-header {
    position: sticky;
    top: 0;
    z-index: 40;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas: 'logo nav actions';
    align-items: center;
    gap: 0.75rem 1rem;
    width: 100%;
    max-width: 100%;
    padding: 0.85rem var(--rt-gutter);
    background: var(--rt-header-bg);
    border-bottom: 1px solid var(--rt-line);
}
.rt-header__menu {
    display: none;
    grid-area: menu;
    width: 2.35rem;
    height: 2.35rem;
    place-items: center;
    color: var(--rt-ink);
    flex-shrink: 0;
}
.rt-header__menu svg { width: 1.25rem; height: 1.25rem; }
.rt-logo {
    grid-area: logo;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
    max-width: 100%;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: inherit;
    text-decoration: none;
}
.rt-logo img {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    object-fit: cover;
    flex-shrink: 0;
}
.rt-logo span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.rt-nav {
    grid-area: nav;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.15rem 1.15rem;
    min-width: 0;
}
.rt-nav__sheet { display: contents; }
.rt-nav__sheet-head { display: none; }
.rt-nav__close { display: none; }
.rt-nav__link {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.35rem 0;
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--rt-ink);
    background: transparent;
}
.rt-nav__link svg { width: 0.85rem; height: 0.85rem; opacity: 0.7; }
.rt-nav__link:hover,
.rt-nav__link.active { color: var(--rt-ink); }
.rt-nav__link.active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: var(--rt-accent);
}
.rt-nav__drop { position: relative; }
.rt-nav__menu {
    list-style: none;
    margin: 0;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 50;
    min-width: 12rem;
    max-height: 16rem;
    overflow: auto;
    padding: 0.55rem 0.4rem 0.4rem;
    border-radius: 0 0 0.85rem 0.85rem;
    background: var(--rt-panel);
    border: 1px solid var(--rt-line);
    border-top: 0;
    box-shadow: 0 16px 40px var(--rt-shadow);
}
.rt-nav__menu::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: -0.65rem;
    height: 0.65rem;
}
.rt-nav__menu li { margin: 0; }
.rt-nav__menu button {
    display: block;
    width: 100%;
    text-align: left;
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.85rem;
    font-weight: 500;
}
.rt-nav__menu button:hover { background: var(--rt-elevated); }

.rt-actions {
    grid-area: actions;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.05rem;
    justify-self: end;
    flex-shrink: 0;
    margin-left: auto;
}
.rt-search {
    display: flex;
    align-items: center;
}
.rt-search input {
    width: 0;
    opacity: 0;
    border: 0;
    outline: none;
    background: transparent;
    font: inherit;
    font-size: 0.85rem;
    transition: width 0.2s ease, opacity 0.2s ease, margin 0.2s ease;
}
.rt-search.open input {
    width: min(12rem, 36vw);
    opacity: 1;
    margin-right: 0.25rem;
}
.rt-icon {
    position: relative;
    display: inline-grid;
    place-items: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 999px;
    color: var(--rt-ink);
    background: transparent;
}
.rt-icon:hover:not(:disabled) { background: var(--rt-elevated); }
.rt-icon:disabled { opacity: 0.4; cursor: default; }
.rt-icon svg { width: 1.15rem; height: 1.15rem; }
.rt-icon--cart span {
    position: absolute;
    top: 0.15rem;
    right: 0.1rem;
    min-width: 1rem;
    height: 1rem;
    border-radius: 999px;
    background: var(--rt-accent);
    color: #fff;
    font-size: 0.62rem;
    font-weight: 800;
    line-height: 1rem;
    text-align: center;
    padding: 0 0.2rem;
}
.rt-icon--boxed {
    border-radius: 0.75rem;
    border: 1px solid #ddd;
    width: 2.75rem;
    height: 2.75rem;
}

.rt-hero {
    position: relative;
    width: 100%;
    background: var(--rt-elevated);
    padding: clamp(1.75rem, 4vw, 3.75rem) var(--rt-gutter) clamp(1.5rem, 3vw, 2.25rem);
}
.rt-hero__inner {
    display: grid;
    grid-template-columns: minmax(16rem, 0.85fr) minmax(0, 1.35fr);
    gap: clamp(1.25rem, 3.5vw, 3rem);
    align-items: center;
    width: 100%;
    min-height: clamp(26rem, 52vh, 36rem);
}
.rt-hero__copy h1 {
    margin: 0.35rem 0 0.75rem;
    font-size: clamp(2.4rem, 5.5vw, 4.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1.02;
    color: var(--rt-ink);
}
.rt-eyebrow {
    margin: 0;
    color: var(--rt-accent);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.rt-hero__sub {
    margin: 0 0 1.35rem;
    max-width: 32rem;
    color: var(--rt-muted, #6b7280);
    font-size: 1.05rem;
    line-height: 1.55;
}
.rt-hero__cta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-bottom: 1.25rem;
}
.rt-closed {
    margin: 0 0 0.85rem;
    color: #b45309;
    font-size: 0.85rem;
    font-weight: 600;
}
.rt-proof {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--rt-muted);
    font-size: 0.85rem;
}
.rt-proof__avatars {
    display: flex;
}
.rt-proof__avatars span {
    width: 1.7rem;
    height: 1.7rem;
    border-radius: 999px;
    border: 2px solid var(--rt-elevated);
    margin-left: -0.4rem;
    background: linear-gradient(135deg, #fda4af, #fb923c);
}
.rt-proof__avatars span:first-child { margin-left: 0; }
.rt-proof__avatars span:nth-child(2) { background: linear-gradient(135deg, #93c5fd, #60a5fa); }
.rt-proof__avatars span:nth-child(3) { background: linear-gradient(135deg, #86efac, #34d399); }
.rt-proof__avatars span:nth-child(4) { background: linear-gradient(135deg, #fcd34d, #f59e0b); }

.rt-hero__controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    margin-top: 1.35rem;
}
.rt-hero__nav {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    border: 1px solid var(--rt-line);
    background: var(--rt-panel);
    color: var(--rt-ink);
    transition: border-color 0.15s ease, background 0.15s ease;
}
.rt-hero__nav:hover {
    border-color: color-mix(in srgb, var(--rt-accent) 45%, var(--rt-line));
    background: color-mix(in srgb, var(--rt-accent) 8%, var(--rt-panel));
}
.rt-hero__nav svg {
    width: 1rem;
    height: 1rem;
}
.rt-hero__dots {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}
.rt-hero__dot {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    border: 0;
    background: #c4c4c4;
    padding: 0;
    transition: width 0.2s ease, background 0.2s ease;
}
.rt-hero__dot--active {
    width: 1.5rem;
    background: var(--rt-accent);
}
.rt-hero-fade-enter-active,
.rt-hero-fade-leave-active {
    transition: opacity 0.35s ease, transform 0.35s ease;
}
.rt-hero-fade-enter-from,
.rt-hero-fade-leave-to {
    opacity: 0;
    transform: translateY(0.35rem);
}

.rt-hero__visual {
    --rt-hero-banner-h: clamp(26rem, 56vh, 38rem);
    position: relative;
    width: 100%;
    height: var(--rt-hero-banner-h);
    min-height: var(--rt-hero-banner-h);
    max-height: var(--rt-hero-banner-h);
    border-radius: 1.75rem;
    overflow: visible;
    background: var(--rt-elevated);
    flex-shrink: 0;
}
.rt-hero__stage {
    position: absolute;
    inset: 0;
    z-index: 1;
    border-radius: inherit;
    overflow: hidden;
    background: var(--rt-elevated);
}
.rt-hero__glow {
    display: none;
}
.rt-hero__img,
.rt-hero__placeholder {
    position: absolute;
    inset: 0;
    z-index: 1;
    display: block;
    width: 100%;
    height: 100%;
    max-width: none;
    border-radius: 0;
    object-fit: cover;
    object-position: center center;
    background: var(--rt-elevated);
}
.rt-hero__placeholder {
    display: grid;
    place-items: center;
    font-size: 5rem;
    font-weight: 800;
    color: color-mix(in srgb, var(--rt-accent) 40%, var(--rt-ink));
    background: linear-gradient(145deg, var(--rt-panel), color-mix(in srgb, var(--rt-accent) 18%, var(--rt-elevated)));
}
.rt-float {
    position: absolute;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    max-width: 11.5rem;
    padding: 0.5rem 0.6rem;
    border-radius: 0.95rem;
    background: var(--rt-panel);
    color: var(--rt-ink);
    box-shadow: 0 14px 34px var(--rt-shadow);
}
.rt-float img {
    width: 2.55rem;
    height: 2.55rem;
    border-radius: 0.6rem;
    object-fit: cover;
    background: var(--rt-elevated);
}
.rt-float strong {
    display: block;
    font-size: 0.72rem;
    line-height: 1.2;
    color: var(--rt-ink);
}
.rt-float span {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--rt-accent);
}
.rt-float--0 { left: -0.35rem; top: 14%; }
.rt-float--1 { right: -0.15rem; top: 28%; }
.rt-float--2 { left: 8%; bottom: 12%; }
.rt-float--3 { right: 6%; bottom: 8%; }

.rt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    border-radius: 999px;
    padding: 0.75rem 1.25rem;
    font-size: 0.88rem;
    font-weight: 700;
    transition: transform 0.15s ease, background 0.15s ease, color 0.15s ease;
}
.rt-btn svg { width: 1rem; height: 1rem; }
.rt-btn:hover:not(:disabled) { transform: translateY(-1px); }
.rt-btn:disabled { opacity: 0.45; cursor: default; }
.rt-btn--primary {
    background: var(--rt-accent);
    color: #fff;
    box-shadow: 0 10px 24px rgb(var(--rt-accent-rgb, 255 90 31) / 0.28);
}
.rt-btn--ghost {
    background: var(--rt-panel);
    color: var(--rt-ink);
    border: 1.5px solid var(--rt-ink);
}
.rt-btn--dark { background: var(--rt-ink); color: var(--rt-bg); border-radius: 0.75rem; }
.rt-btn--light { background: var(--rt-panel); color: var(--rt-ink); }
.rt-btn--block { width: 100%; }

.rt-trust {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    width: 100%;
    margin: 0;
    padding: 1.35rem var(--rt-gutter);
    border-bottom: 1px solid var(--rt-line);
    background: var(--rt-panel);
}
.rt-trust__item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 0.5rem;
}
.rt-trust__item svg {
    width: 1.5rem;
    height: 1.5rem;
    color: var(--rt-ink);
    flex-shrink: 0;
}
.rt-trust__item strong {
    display: block;
    font-size: 0.88rem;
}
.rt-trust__item span {
    font-size: 0.75rem;
    color: var(--rt-muted, #6b7280);
}

.rt-section {
    width: 100%;
    margin: 0;
    padding: 2.5rem var(--rt-gutter);
}
.rt-section__head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.rt-section__head h2 {
    margin: 0.25rem 0 0;
    font-size: clamp(1.35rem, 2.5vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--rt-ink);
}
.rt-link {
    color: var(--rt-ink);
    font-size: 0.88rem;
    font-weight: 600;
    background: transparent;
    white-space: nowrap;
}
.rt-link:hover { color: var(--rt-accent); }

.rt-cats {
    display: grid;
    grid-template-columns: repeat(6, minmax(8.5rem, 10.25rem));
    gap: 0.85rem;
    justify-content: start;
}
.rt-cat {
    position: relative;
    width: 100%;
    max-width: 10.25rem;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    border-radius: 1.1rem;
    background: #f3f4f6;
    text-align: left;
    box-shadow: 0 10px 28px rgb(0 0 0 / 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.rt-cat:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgb(0 0 0 / 0.12);
}
.rt-cat img,
.rt-cat__fallback {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
}
.rt-cat:hover img {
    transform: scale(1.05);
}
.rt-cat__fallback {
    display: grid;
    place-items: center;
    font-size: 1.65rem;
    font-weight: 800;
    color: #9ca3af;
    background: linear-gradient(160deg, #f9fafb, #e5e7eb);
}
.rt-cat__overlay {
    position: absolute;
    left: 0.55rem;
    right: 0.55rem;
    bottom: 0.55rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.1rem;
    border-radius: 0.75rem;
    padding: 0.55rem 0.65rem;
    background: var(--rt-panel);
    box-shadow: 0 6px 16px var(--rt-shadow);
}
.rt-cat__overlay strong {
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
    color: var(--rt-ink);
}
.rt-cat__overlay em {
    font-style: normal;
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--rt-muted);
}

.rt-rail-wrap {
    position: relative;
}
.rt-rail {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 10.75rem;
    gap: 0.9rem;
    overflow-x: auto;
    padding: 0.15rem 0.15rem 0.55rem;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
}
.rt-rail::-webkit-scrollbar { display: none; }
.rt-rail > * { scroll-snap-align: start; }
.rt-rail__nav {
    position: absolute;
    top: 38%;
    z-index: 3;
    display: grid;
    place-items: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    border: 1px solid var(--rt-line);
    background: var(--rt-panel);
    color: var(--rt-ink);
    box-shadow: 0 8px 22px var(--rt-shadow);
    transform: translateY(-50%);
}
.rt-rail__nav svg { width: 1.05rem; height: 1.05rem; }
.rt-rail__nav--prev { left: -0.35rem; }
.rt-rail__nav--next { right: -0.35rem; }
.rt-rail__nav:hover {
    border-color: color-mix(in srgb, var(--rt-accent) 35%, var(--rt-line));
}
@media (min-width: 1280px) {
    .rt-rail {
        grid-auto-flow: unset;
        grid-auto-columns: unset;
        grid-template-columns: repeat(6, 10.75rem);
        justify-content: start;
        overflow: visible;
        scroll-snap-type: none;
        padding-inline: 0;
    }
    .rt-rail__nav { display: none; }
}
.rt-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, 10.75rem);
    gap: 0.9rem;
    justify-content: start;
}
.rt-card {
    display: flex;
    flex-direction: column;
    width: 10.75rem;
    max-width: 10.75rem;
    border-radius: 1rem;
    background: var(--rt-panel);
    border: 1px solid var(--rt-line);
    color: var(--rt-ink);
    overflow: hidden;
    transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}
.rt-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--rt-accent) 25%, var(--rt-line));
    box-shadow: 0 14px 28px var(--rt-shadow);
}
.rt-card__media {
    position: relative;
    padding: 0.55rem 0.55rem 0;
    background: var(--rt-panel);
}
.rt-card__frame {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 0.8rem;
    background: var(--rt-elevated);
}
.rt-card__frame img,
.rt-card__empty {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rt-card__empty {
    background: linear-gradient(160deg, #f8fafc, #e5e7eb);
}
.rt-card__badge {
    position: absolute;
    top: 0.85rem;
    left: 0.85rem;
    z-index: 2;
    border-radius: 999px;
    padding: 0.22rem 0.5rem;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.01em;
    color: #fff;
}
.rt-card__badge--new { background: #111; }
.rt-card__badge--sale { background: var(--rt-accent); }
.rt-card__badge--hot { background: #111; }
.rt-card__wish {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 2;
    display: inline-grid;
    place-items: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 999px;
    background: var(--rt-panel);
    color: var(--rt-ink);
    box-shadow: 0 4px 12px var(--rt-shadow);
}
.rt-card__wish svg { width: 0.85rem; height: 0.85rem; }
.rt-card__wish:disabled { opacity: 0.9; cursor: default; }
.rt-card__wish--on { color: #e11d48; }
.rt-card__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0.28rem;
    padding: 0.65rem 0.7rem 0.8rem;
}
.rt-card__body h3 {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.3;
    letter-spacing: -0.01em;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.rt-card__price {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 0.3rem;
}
.rt-card__price strong {
    font-size: 0.88rem;
    font-weight: 800;
}
.rt-card__price s {
    color: #9ca3af;
    font-size: 0.7rem;
    font-weight: 500;
}
.rt-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.35rem;
    margin-top: auto;
    padding-top: 0.2rem;
}
.rt-card__rating {
    display: flex;
    align-items: center;
    gap: 0.12rem;
    margin: 0;
    color: #9ca3af;
    font-size: 0.65rem;
    font-weight: 600;
}
.rt-card__stars {
    color: #f5a524;
    letter-spacing: 0.01em;
    font-size: 0.68rem;
}
.rt-card__star {
    padding: 0;
    color: #d1d5db;
    font-size: 0.78rem;
    line-height: 1;
}
.rt-card__star.on { color: #f5a524; }
.rt-card__rating em {
    font-style: normal;
    color: #9ca3af;
}
.rt-card__desc {
    margin: 0 0 0.55rem;
    color: var(--rt-muted, #6b7280);
    font-size: 0.75rem;
    line-height: 1.4;
}
.rt-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.rt-card__meta strong { font-size: 0.95rem; }
.rt-card__add {
    display: inline-grid;
    place-items: center;
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    border-radius: 999px;
    background: #111;
    color: #fff;
}
.rt-card__add svg {
    width: 0.9rem;
    height: 0.9rem;
}
.rt-card__add:disabled { opacity: 0.4; }

.rt-best {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.15rem;
}
.rt-best__card {
    display: grid;
    grid-template-columns: 0.95fr 1.05fr;
    gap: 0;
    overflow: hidden;
    border-radius: 1.25rem;
    background: #f4f4f5;
}
.rt-best__media {
    position: relative;
    min-height: 14rem;
    background: #eaeaec;
}
.rt-best__media img,
.rt-best__empty {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rt-best__empty {
    display: grid;
    place-items: center;
    font-size: 2.5rem;
    font-weight: 800;
    color: #a1a1aa;
}
.rt-best__badge {
    position: absolute;
    top: 0.85rem;
    left: 0.85rem;
    z-index: 1;
    border-radius: 999px;
    background: var(--rt-accent);
    color: #fff;
    padding: 0.3rem 0.65rem;
    font-size: 0.68rem;
    font-weight: 800;
}
.rt-best__body {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.4rem;
    padding: 1.2rem 1.2rem 1.25rem;
}
.rt-best__body h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.rt-best__price {
    font-size: 1.05rem;
    font-weight: 800;
}
.rt-best__desc {
    margin: 0;
    color: var(--rt-muted, #6b7280);
    font-size: 0.82rem;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.rt-best__actions {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin-top: 0.45rem;
}
.rt-best__quick {
    flex: 1;
    border-radius: 0.85rem !important;
    padding: 0.75rem 1rem !important;
}
.rt-best__cart {
    display: inline-grid;
    place-items: center;
    width: 2.65rem;
    height: 2.65rem;
    flex-shrink: 0;
    border-radius: 0.85rem;
    border: 1px solid #d4d4d8;
    background: #fff;
    color: #111;
}
.rt-best__cart svg {
    width: 1.1rem;
    height: 1.1rem;
}
.rt-best__cart:disabled,
.rt-best__quick:disabled {
    opacity: 0.45;
}

.rt-promos {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    width: 100%;
    margin: 0;
    padding: 0.5rem var(--rt-gutter) 2.5rem;
}
.rt-promo {
    border-radius: 1.35rem;
    padding: 1.75rem;
    min-height: 14rem;
    background-size: cover;
    background-position: center;
}
.rt-promo h3 {
    margin: 0.35rem 0 0.55rem;
    font-size: clamp(1.4rem, 3vw, 2rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: inherit;
}
.rt-promo p { margin: 0 0 1rem; max-width: 22rem; line-height: 1.45; color: inherit; }
.rt-promo--sale {
    background: linear-gradient(135deg, var(--rt-accent), color-mix(in srgb, var(--rt-accent) 70%, #9a3412));
    color: #fff;
}
.rt-promo--sale .rt-eyebrow { color: rgb(255 255 255 / 0.85); }
.rt-promo--sale .rt-btn--dark {
    background: rgb(0 0 0 / 0.28);
    color: #fff;
    border: 1px solid rgb(255 255 255 / 0.35);
}
.rt-promo--dark {
    background: #111;
    color: #fff;
}
.rt-promo--dark .rt-eyebrow { color: var(--rt-accent); }
.rt-promo--dark .rt-btn--light {
    background: #fff;
    color: #111;
}
.rt-countdown {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.5rem;
    max-width: 18rem;
    margin: 0 0 1.1rem;
}
.rt-countdown div {
    display: grid;
    place-items: center;
    gap: 0.15rem;
    border-radius: 0.75rem;
    padding: 0.55rem 0.35rem;
    background: rgb(0 0 0 / 0.18);
}
.rt-countdown strong {
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1;
}
.rt-countdown span {
    font-size: 0.62rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.85;
}
.rt-info h2 {
    margin: 0 0 0.55rem;
    font-size: 1.35rem;
    font-weight: 800;
}
.rt-info p {
    margin: 0;
    max-width: 42rem;
    color: var(--rt-muted, #6b7280);
    line-height: 1.55;
}
.rt-info__meta {
    margin-top: 0.65rem !important;
    color: var(--rt-ink) !important;
    font-weight: 600;
}
.rt-foot__tagline {
    margin: 0.35rem 0 0 !important;
    color: #6b7280 !important;
    font-size: 0.85rem !important;
}

.rt-catalog .rt-count {
    margin: 0;
    color: var(--rt-muted, #6b7280);
    font-size: 0.85rem;
}
.rt-catalog__layout {
    display: grid;
    gap: 1.25rem;
}
@media (min-width: 900px) {
    .rt-catalog__layout {
        grid-template-columns: 14rem minmax(0, 1fr);
        align-items: start;
    }
}
.rt-filters {
    border: 1px solid var(--rt-line, #ececec);
    border-radius: 1rem;
    padding: 1rem;
    background: #fff;
    position: sticky;
    top: 5.5rem;
}
.rt-filters h3 {
    margin: 0 0 0.85rem;
    font-size: 0.95rem;
    font-weight: 800;
}
.rt-filters__block {
    display: grid;
    gap: 0.55rem;
    margin-bottom: 1rem;
}
.rt-filters__label {
    margin: 0;
    font-size: 0.78rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.rt-filters__values {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    font-weight: 700;
}
.rt-filters label {
    display: grid;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
}
.rt-filters input[type='range'] {
    width: 100%;
    accent-color: var(--rt-accent, #ff5a1f);
}
.rt-filters__reset,
.rt-filters__chip {
    border-radius: 999px;
    border: 1px solid var(--rt-line, #ececec);
    padding: 0.45rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: #fff;
}
.rt-filters__chip.active {
    background: var(--rt-accent, #ff5a1f);
    border-color: transparent;
    color: #fff;
}
.rt-account {
    position: relative;
}
.rt-account__menu {
    list-style: none;
    margin: 0;
    position: absolute;
    top: 100%;
    right: 0;
    z-index: 40;
    min-width: 11rem;
    display: grid;
    gap: 0.2rem;
    padding: 0.55rem 0.65rem 0.65rem;
    border-radius: 0.85rem;
    background: var(--rt-panel);
    box-shadow: 0 14px 40px var(--rt-shadow);
    border: 1px solid var(--rt-line);
}
.rt-account__menu::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: -0.45rem;
    height: 0.45rem;
}
.rt-account__name {
    margin: 0 0 0.15rem;
    padding: 0.2rem 0.35rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--rt-muted);
}
.rt-account__menu a,
.rt-account__menu button {
    display: block;
    width: 100%;
    text-align: left;
    border-radius: 0.5rem;
    padding: 0.45rem 0.5rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: #111;
}
.rt-account__menu a:hover,
.rt-account__menu button:hover {
    background: var(--rt-elevated);
}
.rt-icon--active { color: var(--rt-accent, #ff5a1f); }
.bh-price {
    margin: 0.75rem 0 1rem;
    padding: 0 0.25rem;
}
.bh-price__values {
    display: flex;
    justify-content: space-between;
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 0.45rem;
}
.bh-price label {
    display: grid;
    gap: 0.2rem;
    margin-bottom: 0.45rem;
    font-size: 0.72rem;
    color: #6b7280;
}
.bh-price input[type='range'] {
    width: 100%;
    accent-color: var(--bh-accent, #ff5a1f);
}
.bh-price__reset {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--bh-accent, #ff5a1f);
}
.bh-nav__item--static {
    opacity: 0.85;
    cursor: default;
}
.bh-nav a.bh-nav__item {
    text-decoration: none;
    color: inherit;
}
.rt-empty {
    margin: 2rem 0 0;
    text-align: center;
    color: var(--rt-muted, #6b7280);
}

.rt-foot {
    border-top: 1px solid var(--rt-line, #ececec);
    padding: 1.25rem var(--rt-gutter) 2.25rem;
    text-align: center;
}
.rt-foot > p {
    margin: 0.5rem 0 0;
    color: #9ca3af;
    font-size: 0.8rem;
}
.rt-trust--foot {
    padding: 0.5rem 0 1rem;
    border-bottom: 1px solid var(--rt-line, #ececec);
    margin-bottom: 0.75rem;
}
.rt-trust--foot .rt-trust__item {
    justify-content: center;
    padding: 0.35rem;
}

.rt-cart {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 70;
    display: flex;
    flex-direction: column;
    width: min(24rem, 94vw);
    height: 100%;
    background: var(--rt-soft);
    color: var(--rt-ink);
    box-shadow: -20px 0 50px var(--rt-shadow);
    transform: translateX(105%);
    transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
}
.rt-cart.open { transform: translateX(0); }
.rt-cart__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1.15rem 1.15rem 1rem;
    background: #fff;
    border-bottom: 1px solid #eee;
}
.rt-cart__head h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.rt-cart__count {
    margin: 0.2rem 0 0;
    color: #6b7280;
    font-size: 0.78rem;
    font-weight: 600;
}
.rt-cart__close {
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #f5f5f5;
    color: #4b5563;
}
.rt-cart__close svg { width: 0.95rem; height: 0.95rem; }
.rt-cart__close:hover { background: #ececec; }

.rt-cart__empty {
    flex: 1;
    display: grid;
    place-content: center;
    justify-items: center;
    gap: 0.35rem;
    padding: 2rem 1.25rem;
    text-align: center;
    color: #6b7280;
}
.rt-cart__empty-ico {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #fff;
    border: 1px solid #ececec;
    color: #9ca3af;
    margin-bottom: 0.45rem;
}
.rt-cart__empty-ico svg { width: 1.4rem; height: 1.4rem; }
.rt-cart__empty strong {
    color: #111;
    font-size: 1rem;
}
.rt-cart__empty span {
    font-size: 0.85rem;
    max-width: 16rem;
}
.rt-cart__continue {
    margin-top: 0.75rem;
    border-radius: 999px;
    background: #111;
    color: #fff;
    padding: 0.65rem 1.1rem;
    font-size: 0.82rem;
    font-weight: 800;
}

.rt-cart__list {
    overflow: auto;
    padding: 0.9rem 1rem;
    flex: 1;
    display: grid;
    gap: 0.7rem;
    align-content: start;
}
.rt-cart__row {
    display: grid;
    grid-template-columns: 4rem minmax(0, 1fr);
    gap: 0.8rem;
    padding: 0.8rem;
    border-radius: 1rem;
    background: #fff;
    border: 1px solid #efefef;
}
.rt-cart__thumb {
    width: 4rem;
    height: 4rem;
    border-radius: 0.85rem;
    overflow: hidden;
    background: #f3f4f6;
    display: grid;
    place-items: center;
    color: #9ca3af;
    font-weight: 800;
}
.rt-cart__thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rt-cart__meta { min-width: 0; }
.rt-cart__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.45rem;
}
.rt-cart__top strong {
    font-size: 0.88rem;
    line-height: 1.3;
    font-weight: 700;
}
.rt-cart__remove {
    width: 1.55rem;
    height: 1.55rem;
    border-radius: 0.45rem;
    color: #9ca3af;
    flex-shrink: 0;
}
.rt-cart__remove:hover { color: #e11d48; background: #fef2f2; }
.rt-cart__remove svg { width: 0.9rem; height: 0.9rem; }
.rt-cart__unit {
    margin: 0.2rem 0 0.55rem;
    color: #6b7280;
    font-size: 0.74rem;
    font-weight: 600;
}
.rt-cart__controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.rt-cart__qty {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    border-radius: 999px;
    background: #f5f5f5;
    border: 1px solid #ebebeb;
    padding: 0.12rem;
}
.rt-cart__qty button {
    width: 1.6rem;
    height: 1.6rem;
    border-radius: 999px;
    background: #fff;
    font-weight: 700;
    color: #111;
    box-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
}
.rt-cart__qty em {
    font-style: normal;
    font-weight: 800;
    min-width: 1.2rem;
    text-align: center;
    font-size: 0.82rem;
}
.rt-cart__line {
    font-size: 0.9rem;
    font-weight: 800;
    color: #111;
}

.rt-cart__foot {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    padding: 1rem 1.1rem 1.2rem;
    background: #fff;
    border-top: 1px solid #eee;
}
.rt-cart__summary {
    display: grid;
    gap: 0.4rem;
    margin-bottom: 0.25rem;
}
.rt-cart__summary > div {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    color: #6b7280;
    font-size: 0.84rem;
    font-weight: 600;
}
.rt-cart__grand {
    margin-top: 0.2rem;
    padding-top: 0.55rem;
    border-top: 1px dashed #e5e7eb;
    color: #111 !important;
    font-size: 0.95rem !important;
}
.rt-cart__grand strong {
    font-size: 1.05rem;
    font-weight: 800;
}
.rt-cart__checkout {
    border-radius: 999px;
    background: var(--rt-accent, #ff5a1f);
    color: #fff;
    padding: 0.9rem 1rem;
    font-size: 0.9rem;
    font-weight: 800;
}
.rt-cart__checkout:disabled { opacity: 0.5; }
.rt-cart__keep {
    background: transparent;
    color: #6b7280;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 0.35rem;
}
.rt-cart__keep:hover { color: #111; }
.rt-backdrop {
    position: fixed;
    inset: 0;
    z-index: 60;
    background: rgb(15 23 42 / 0.4);
    backdrop-filter: blur(2px);
}

@media (max-width: 1280px) {
    .rt-best {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1100px) {
    .rt-hero__inner { grid-template-columns: 1fr; }
    .rt-hero__visual {
        --rt-hero-banner-h: clamp(18rem, 70vw, 28rem);
        order: -1;
    }
    .rt-float--0,
    .rt-float--1,
    .rt-float--2,
    .rt-float--3 { display: none; }
    .rt-cats {
        grid-template-columns: repeat(6, minmax(7.5rem, 9.5rem));
        overflow-x: auto;
        padding-bottom: 0.25rem;
        -webkit-overflow-scrolling: touch;
    }
    .rt-cat { max-width: 9.5rem; }
    .rt-best { grid-template-columns: 1fr; }
    .rt-best__card { grid-template-columns: 0.9fr 1.1fr; }
    .rt-promos { grid-template-columns: 1fr; }
    .rt-trust { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 1024px) {
    .rt-app {
        --rt-gutter: clamp(1rem, 4vw, 1.5rem);
    }
    .rt-header {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.7rem var(--rt-gutter);
    }
    .rt-header__menu {
        display: inline-grid;
        order: 1;
        flex-shrink: 0;
    }
    .rt-logo {
        order: 2;
        flex: 1 1 auto;
        min-width: 0;
        font-size: 1rem;
    }
    .rt-logo img { width: 1.75rem; height: 1.75rem; }
    .rt-actions {
        order: 3;
        flex: 0 0 auto;
        margin-left: auto;
    }
    .rt-icon {
        width: 2.15rem;
        height: 2.15rem;
    }
    .rt-search.open input {
        width: min(9rem, 28vw);
    }
    /* Elevate header above page content while nav is open; drawer owns its own scrim
       so the cart backdrop (z-index 60) no longer paints over the white sheet. */
    .rt-app.is-nav-open .rt-header {
        z-index: 90;
    }
    .rt-app.is-nav-open .rt-header__menu,
    .rt-app.is-nav-open .rt-logo,
    .rt-app.is-nav-open .rt-actions {
        visibility: hidden;
        pointer-events: none;
    }
    .rt-nav {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: block;
        order: 0;
        padding: 0;
        pointer-events: none;
        visibility: hidden;
        background: transparent;
    }
    .rt-nav.open {
        pointer-events: auto;
        visibility: visible;
        background: rgb(15 23 42 / 0.45);
    }
    .rt-nav__sheet {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.15rem;
        width: min(18rem, 86vw);
        height: 100%;
        margin: 0;
        padding: 0.85rem 0.85rem calc(1.25rem + env(safe-area-inset-bottom));
        padding-top: calc(0.85rem + env(safe-area-inset-top));
        background: var(--rt-panel);
        color: var(--rt-ink);
        box-shadow: 16px 0 48px var(--rt-shadow);
        transform: translateX(-105%);
        transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
        overflow: auto;
        -webkit-overflow-scrolling: touch;
        isolation: isolate;
    }
    .rt-nav.open .rt-nav__sheet { transform: translateX(0); }
    .rt-nav__sheet-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--rt-line);
    }
    .rt-nav__sheet-head strong {
        font-size: 1rem;
        font-weight: 800;
        color: var(--rt-ink);
    }
    .rt-nav__close {
        display: inline-grid;
        place-items: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: var(--rt-elevated);
        color: var(--rt-ink);
        flex-shrink: 0;
    }
    .rt-nav__close svg { width: 0.9rem; height: 0.9rem; }
    .rt-nav__link {
        width: 100%;
        justify-content: space-between;
        padding: 0.8rem 0.55rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--rt-ink);
    }
    .rt-nav__link:hover,
    .rt-nav__link.active {
        color: var(--rt-ink);
        background: var(--rt-elevated);
        border-radius: 0.65rem;
    }
    .rt-nav__link.active::after { display: none; }
    .rt-nav__drop { width: 100%; }
    .rt-nav__menu {
        position: static;
        box-shadow: none;
        margin-top: 0.2rem;
        max-height: none;
        border: 1px solid var(--rt-line);
        border-radius: 0.85rem;
        padding: 0.4rem;
        background: var(--rt-soft);
    }
    .rt-nav__menu button {
        color: var(--rt-ink);
    }
    .rt-nav__menu::before { display: none; }
    .rt-float { display: none; }
    .rt-hero__copy h1 {
        font-size: clamp(2rem, 9vw, 2.75rem);
    }
    .rt-hero__inner { min-height: 0; }
    .rt-catalog__layout { grid-template-columns: 1fr; }
    .rt-filters {
        position: static;
        display: grid;
        grid-template-columns: 1fr;
    }
}
@media (max-width: 560px) {
    .rt-header { gap: 0.35rem; }
    .rt-actions .rt-search { display: none; }
    .rt-cats {
        grid-template-columns: repeat(6, minmax(7.25rem, 8.75rem));
    }
    .rt-cat { max-width: 8.75rem; }
    .rt-rail { grid-auto-columns: 9.75rem; }
    .rt-card { width: 9.75rem; max-width: 9.75rem; }
    .rt-grid { grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); justify-content: stretch; }
    .rt-best__card { grid-template-columns: 1fr; }
    .rt-trust,
    .rt-trust--foot { grid-template-columns: 1fr 1fr; }
    .rt-hero__cta { flex-direction: column; align-items: stretch; }
    .rt-hero {
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
    }
    .rt-proof { flex-wrap: wrap; }
}

.sf-rate {
    position: fixed;
    inset: 0;
    z-index: 120;
    display: grid;
    place-items: center;
    padding: 1rem;
}
.sf-rate__scrim {
    position: absolute;
    inset: 0;
    background: rgb(15 23 42 / 0.55);
}
.sf-rate__card {
    position: relative;
    z-index: 1;
    width: min(100%, 26rem);
    border-radius: 1.25rem;
    background: #fff;
    padding: 1.35rem 1.35rem 1.2rem;
    box-shadow: 0 24px 60px rgb(0 0 0 / 0.22);
}
.sf-rate__close {
    position: absolute;
    top: 0.75rem;
    right: 0.85rem;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    color: #6b7280;
    font-size: 0.95rem;
}
.sf-rate__product {
    display: flex;
    gap: 0.85rem;
    align-items: center;
    margin-bottom: 1rem;
    padding-right: 1.5rem;
}
.sf-rate__product img,
.sf-rate__fallback {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.85rem;
    object-fit: cover;
    background: #f3f4f6;
    flex-shrink: 0;
}
.sf-rate__fallback {
    display: grid;
    place-items: center;
    font-weight: 800;
    color: #6b7280;
}
.sf-rate__eyebrow {
    margin: 0 0 0.2rem;
    color: #6b7280;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.sf-rate__product h3 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.25;
}
.sf-rate__stars {
    display: flex;
    gap: 0.2rem;
    margin-bottom: 1rem;
}
.sf-rate__star {
    padding: 0;
    color: #d1d5db;
    font-size: 1.65rem;
    line-height: 1;
}
.sf-rate__star.on { color: #f5a524; }
.sf-rate__label {
    display: grid;
    gap: 0.4rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #374151;
}
.sf-rate__label i {
    color: #e11d48;
    font-style: normal;
}
.sf-rate__label textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 0.85rem;
    padding: 0.75rem 0.85rem;
    font: inherit;
    font-weight: 500;
    resize: vertical;
    min-height: 6rem;
}
.sf-rate__label em {
    justify-self: end;
    font-style: normal;
    font-size: 0.72rem;
    font-weight: 600;
    color: #9ca3af;
}
.sf-rate__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    margin-top: 1rem;
}
.sf-rate__cancel,
.sf-rate__submit {
    border-radius: 999px;
    padding: 0.65rem 1.05rem;
    font-size: 0.85rem;
    font-weight: 800;
}
.sf-rate__cancel {
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
}
.sf-rate__submit {
    background: var(--rt-accent, var(--bh-accent, #ff5a1f));
    color: #fff;
}
.sf-rate__submit:disabled,
.sf-rate__cancel:disabled {
    opacity: 0.6;
}

/* Dark mode surface remaps for remaining light-only hardcodes */
.rt-app[data-color-mode='dark'] .rt-card,
.rt-app[data-color-mode='dark'] .rt-cat,
.rt-app[data-color-mode='dark'] .rt-best__card,
.rt-app[data-color-mode='dark'] .rt-filters,
.rt-app[data-color-mode='dark'] .rt-grid__card,
.rt-app[data-color-mode='dark'] .rt-rail .rt-card,
.rt-app[data-color-mode='dark'] .sf-rate__card {
    background: var(--rt-panel);
    border-color: var(--rt-line);
    color: var(--rt-ink);
}
.rt-app[data-color-mode='dark'] .rt-search input,
.rt-app[data-color-mode='dark'] .rt-filters input,
.rt-app[data-color-mode='dark'] .rt-filters select,
.rt-app[data-color-mode='dark'] .sf-rate__review {
    color: var(--rt-ink);
    background: var(--rt-elevated);
    border-color: var(--rt-line);
}
.rt-app[data-color-mode='dark'] .rt-btn--ghost {
    color: var(--rt-ink);
    border-color: var(--rt-ink);
    background: transparent;
}
.rt-app[data-color-mode='dark'] .rt-promo--sale {
    background: linear-gradient(135deg, var(--rt-accent), color-mix(in srgb, var(--rt-accent) 65%, #000));
    color: #fff;
}
.rt-app[data-color-mode='dark'] .rt-promo--sale h3,
.rt-app[data-color-mode='dark'] .rt-promo--sale p,
.rt-app[data-color-mode='dark'] .rt-promo--sale .rt-eyebrow {
    color: #fff;
}
.rt-app[data-color-mode='dark'] .rt-promo--dark {
    background: #1a1a1d;
    color: #fff;
    border: 1px solid var(--rt-line);
}
.rt-app[data-color-mode='dark'] .rt-promo--dark h3,
.rt-app[data-color-mode='dark'] .rt-promo--dark p {
    color: #fff;
}
.rt-app[data-color-mode='dark'] .rt-promo--dark .rt-eyebrow {
    color: var(--rt-accent);
}
.rt-app[data-color-mode='dark'] .rt-countdown div {
    background: rgb(255 255 255 / 0.12);
    color: #fff;
}
.rt-app[data-color-mode='dark'] .rt-section h2,
.rt-app[data-color-mode='dark'] .rt-logo,
.rt-app[data-color-mode='dark'] .rt-trust__item strong {
    color: var(--rt-ink);
}
.rt-app[data-color-mode='dark'] .rt-trust__item span,
.rt-app[data-color-mode='dark'] .rt-foot__tagline {
    color: var(--rt-muted) !important;
}
.rt-app[data-color-mode='dark'] .rt-cart__row,
.rt-app[data-color-mode='dark'] .rt-cart__head,
.rt-app[data-color-mode='dark'] .rt-cart__foot {
    border-color: var(--rt-line);
}
.rt-app[data-color-mode='dark'] .rt-cart__close,
.rt-app[data-color-mode='dark'] .rt-cart__qty,
.rt-app[data-color-mode='dark'] .rt-cart__empty-ico {
    background: var(--rt-elevated);
    color: var(--rt-ink);
}
.rt-app[data-color-mode='dark'] .rt-cart__keep {
    color: var(--rt-muted);
}
.rt-app[data-color-mode='dark'] .sf-rate__cancel {
    background: var(--rt-elevated);
    border-color: var(--rt-line);
    color: var(--rt-ink);
}
.bh-app[data-color-mode='dark'] .bh-card,
.bh-app[data-color-mode='dark'] .bh-promo,
.bh-app[data-color-mode='dark'] .bh-order,
.bh-app[data-color-mode='dark'] .bh-search input,
.bh-app[data-color-mode='dark'] .bh-chip {
    background: var(--bh-panel);
    border-color: var(--bh-line);
    color: var(--bh-ink);
}
.bh-app[data-color-mode='dark'] .bh-chip.active {
    background: var(--bh-accent);
    color: #fff;
}
.bh-app[data-color-mode='dark'] .bh-search input {
    background: var(--bh-elevated);
}
.bh-app[data-color-mode='dark'] .bh-main,
.bh-app[data-color-mode='dark'] .bh-order {
    background: var(--bh-bg);
}
.bh-app[data-color-mode='dark'] .sf-rate__card {
    background: var(--bh-panel);
    color: var(--bh-ink);
}
.bh-app[data-color-mode='dark'] .sf-rate__cancel {
    background: var(--bh-elevated);
    border-color: var(--bh-line);
    color: var(--bh-ink);
}
</style>
