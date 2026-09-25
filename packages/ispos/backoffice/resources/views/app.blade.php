<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ $appTitle ?? $appDisplayName ?? config('app.name', 'iSPOS') }}</title>
        @if (! empty($appLogoUrl))
            <link rel="icon" href="{{ $appLogoUrl }}" data-app-favicon>
        @endif
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|plus-jakarta-sans:600,700,800&display=swap" rel="stylesheet" />
        <script>
            (function () {
                function hexToRgb(hex) {
                    var n = hex.replace('#', '');
                    if (!/^[0-9a-fA-F]{6}$/.test(n)) return null;
                    return [parseInt(n.slice(0, 2), 16), parseInt(n.slice(2, 4), 16), parseInt(n.slice(4, 6), 16)];
                }
                function mix(a, b, w) {
                    return [Math.round(a[0] * (1 - w) + b[0] * w), Math.round(a[1] * (1 - w) + b[1] * w), Math.round(a[2] * (1 - w) + b[2] * w)];
                }
                function rgbStr(rgb) { return rgb[0] + ' ' + rgb[1] + ' ' + rgb[2]; }
                function tokensFromHex(hex, dark) {
                    var rgb = hexToRgb(hex);
                    if (!rgb) return null;
                    if (dark) {
                        var accent = mix(rgb, [255, 255, 255], 0.35);
                        return { a: rgbStr(accent), h: rgbStr(mix(rgb, [255, 255, 255], 0.5)), s: rgbStr(mix(rgb, [15, 23, 42], 0.88)), m: rgbStr(mix(rgb, [24, 24, 27], 0.75)) };
                    }
                    return { a: rgbStr(mix(rgb, [0, 0, 0], 0.08)), h: rgbStr(mix(rgb, [255, 255, 255], 0.12)), s: rgbStr(mix(rgb, [255, 255, 255], 0.9)), m: rgbStr(mix(rgb, [255, 255, 255], 0.72)) };
                }
                var prefs = @json($userPreferences ?? ['theme' => 'system', 'appearance' => []]);
                var theme = prefs.theme || 'system';
                var dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.dataset.theme = theme;
                document.documentElement.dataset.colorMode = dark ? 'dark' : 'light';
                try {
                    var appearanceMap = prefs.appearance || {};
                    var appearance = appearanceMap.accentSource || appearanceMap.accent
                        ? appearanceMap
                        : (appearanceMap[dark ? 'dark' : 'light'] || {});
                    var root = document.documentElement;
                    var style = root.style;
                    root.dataset.sidebar = appearance.sidebar || 'light';
                    root.dataset.topbar = appearance.topbar || 'default';
                    var presets = {
                        teal: { light: { a: '15 118 110', h: '13 148 136', s: '240 253 250', m: '204 251 241' }, dark: { a: '45 212 191', h: '94 234 212', s: '19 78 74', m: '17 94 89' } },
                        emerald: { light: { a: '5 150 105', h: '16 185 129', s: '236 253 245', m: '167 243 208' }, dark: { a: '52 211 153', h: '110 231 183', s: '6 78 59', m: '6 95 70' } },
                        blue: { light: { a: '37 99 235', h: '59 130 246', s: '239 246 255', m: '191 219 254' }, dark: { a: '96 165 250', h: '147 197 253', s: '30 58 138', m: '29 78 216' } },
                        indigo: { light: { a: '79 70 229', h: '99 102 241', s: '238 242 255', m: '199 210 254' }, dark: { a: '129 140 248', h: '165 180 252', s: '49 46 129', m: '67 56 202' } },
                        violet: { light: { a: '124 58 237', h: '139 92 246', s: '245 243 255', m: '221 214 254' }, dark: { a: '167 139 250', h: '196 181 253', s: '76 29 149', m: '91 33 182' } },
                        rose: { light: { a: '225 29 72', h: '244 63 94', s: '255 241 242', m: '254 205 211' }, dark: { a: '251 113 133', h: '253 164 175', s: '136 19 55', m: '159 18 57' } },
                        amber: { light: { a: '217 119 6', h: '245 158 11', s: '255 251 235', m: '253 230 138' }, dark: { a: '251 191 36', h: '252 211 77', s: '120 53 15', m: '146 64 14' } },
                        slate: { light: { a: '71 85 105', h: '100 116 139', s: '248 250 252', m: '226 232 240' }, dark: { a: '148 163 184', h: '203 213 225', s: '30 41 59', m: '51 65 85' } }
                    };
                    var t;
                    if (appearance.accentSource === 'custom' && appearance.customAccentColor) {
                        t = tokensFromHex(appearance.customAccentColor, dark);
                        root.dataset.accent = 'custom';
                    } else {
                        var key = appearance.accent && presets[appearance.accent] ? appearance.accent : 'blue';
                        t = presets[key][dark ? 'dark' : 'light'];
                        root.dataset.accent = key;
                    }
                    if (t) {
                        style.setProperty('--color-accent', t.a);
                        style.setProperty('--color-accent-hover', t.h);
                        style.setProperty('--color-accent-soft', t.s);
                        style.setProperty('--color-accent-muted', t.m);
                    }
                    if (appearance.customSidebarColor) {
                        var sb = hexToRgb(appearance.customSidebarColor);
                        if (sb) { style.setProperty('--sidebar-bg', rgbStr(sb)); root.dataset.sidebarCustom = '1'; }
                    }
                    if (appearance.customTopbarColor) {
                        var tb = hexToRgb(appearance.customTopbarColor);
                        if (tb) { style.setProperty('--topbar-bg', rgbStr(tb)); root.dataset.topbarCustom = '1'; }
                    }
                } catch (e) {}
            })();
        </script>
        @routes
        @vite(['packages/ispos/backoffice/resources/js/app.ts', "packages/ispos/backoffice/resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
