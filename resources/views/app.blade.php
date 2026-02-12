<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Default Open Graph and Twitter (pages can override via Inertia Head) --}}
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:type" content="website">
        <meta property="og:image" content="{{ asset('logo.png') }}">
        <meta name="twitter:card" content="summary_large_image">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preload" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|newsreader:400,500,600,700|dm-sans:400,500,600,700" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|newsreader:400,500,600,700|dm-sans:400,500,600,700"></noscript>

        @if(!empty($lcp_image_url))
        <link rel="preload" as="image" href="{{ $lcp_image_url }}">
        @endif

        @vite(['resources/js/app.ts'])
        @inertiaHead

        @production
        {{-- Google Analytics --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-PNZ5W694WT"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-PNZ5W694WT');
        </script>
        @endproduction
    </head>
    <body class="font-sans antialiased">
        <a href="#main-content" class="skip-link">Skip to main content</a>
        @inertia
    </body>
</html>
