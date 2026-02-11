<meta charset="utf-8" />
<meta
    name="application-name"
    content="{{ config('app.name') }}"
/>
<meta
    name="csrf-token"
    content="{{ csrf_token() }}"
/>
<meta
    name="viewport"
    content="width=device-width, initial-scale=1, user-scalable=no, viewport-fit=cover"
/>
<link
    href="{{ route('home') }}"
    rel="canonical"
>
<title>{{ config('app.name') }}</title>
<meta
    property="og:title"
    content="{{ config('app.name') }}"
>
<meta
    name="keywords"
    content="{{ config('app.custom.app_keywords') }}"
>
<meta
    name="description"
    content="{{ config('app.custom.app_description') }}"
>
<meta
    property="og:description"
    content="{{ config('app.custom.app_description') }}"
>
<meta
    property="og:image"
    content="{{ asset('images/open-graph.jpg') }}"
>
<meta
    property="og:image:width"
    content="1200"
/>
<meta
    property="og:image:height"
    content="630"
/>
<meta
    name="theme-color"
    x-bind:content="$store.colorScheme.isDarkModeOn ?
        $store.colorScheme.bodyBackgroundHexes.dark :
        $store.colorScheme.bodyBackgroundHexes.light"
>
