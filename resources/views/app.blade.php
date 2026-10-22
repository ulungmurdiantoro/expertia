<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        {{-- SSR (halaman publik) dijalankan di sini; head-nya sudah membawa <title> dari <Head> Vue. --}}
        @inertiaHead
        @php($meta = $page['props']['meta'] ?? null)
        @if (empty($__inertiaSsrResponse))
            <title inertia>{{ $meta['title'] ?? config('app.name', 'Laravel') }}</title>
        @endif

        {{-- Dicetak dari server: WhatsApp, LinkedIn, dan sebagian crawler AI tidak menjalankan JavaScript. --}}
        @if ($meta)
            <meta name="description" content="{{ $meta['description'] }}">
            <meta name="robots" content="{{ $meta['robots'] ?? \App\Support\PageMeta::ROBOTS_INDEX }}">
            <link rel="canonical" href="{{ $meta['url'] }}">
            <meta property="og:site_name" content="{{ config('expertia.name') }}">
            <meta property="og:locale" content="id_ID">
            <meta property="og:type" content="{{ $meta['type'] }}">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            <meta property="og:url" content="{{ $meta['url'] }}">
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
            @foreach (['published_time', 'modified_time', 'section', 'author'] as $articleKey)
                @if (! empty($meta['article'][$articleKey]))
                    <meta property="article:{{ $articleKey }}" content="{{ $meta['article'][$articleKey] }}">
                @endif
            @endforeach
            @foreach ($meta['article']['tags'] ?? [] as $articleTag)
                <meta property="article:tag" content="{{ $articleTag }}">
            @endforeach
            <link rel="alternate" type="application/rss+xml" title="{{ config('expertia.name') }}" href="{{ route('feed') }}">
            @foreach ($meta['alternates'] ?? [] as $alternate)
                <link rel="alternate" type="{{ $alternate['type'] }}" href="{{ $alternate['href'] }}" @if (! empty($alternate['title'])) title="{{ $alternate['title'] }}" @endif>
            @endforeach
            @foreach ($meta['json_ld'] ?? [] as $schema)
                <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
            @endforeach
        @else
            {{-- Login, registrasi, dan dashboard tidak perlu muncul di hasil pencarian. --}}
            <meta name="robots" content="noindex, nofollow">
        @endif
        <link rel="icon" type="image/png" href="/assets/logo/LOGO-EXPERTIA-003.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;1,6..72,400&family=Figtree:wght@400;500;600;700&display=swap">
        @unless (\App\Support\PublicPagesSsrGateway::isPublicComponent($page['component']))
            {{-- Ikon dashboard; halaman publik tidak memakainya, jadi CSS ini tidak memperlambat halaman publik. --}}
            <link
                rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
                referrerpolicy="no-referrer"
            />
        @endunless

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
