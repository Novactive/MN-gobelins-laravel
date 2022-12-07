<!DOCTYPE html>
<html dir="ltr" lang="fr-FR" class="no-js ArticleLayout @yield('html_classes')">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ mix('css/article.css') }}" rel="stylesheet" type="text/css">

    @if (app()->environment('production'))
        <!-- Matomo -->
        <script>
            var _paq = window._paq = window._paq || [];
            /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
            _paq.push(['trackPageView']);
            _paq.push(['enableLinkTracking']);
            (function() {
                var u = "https://mobiliernational.matomo.cloud/";
                _paq.push(['setTrackerUrl', u + 'matomo.php']);
                _paq.push(['setSiteId', '2']);
                var d = document,
                    g = d.createElement('script'),
                    s = d.getElementsByTagName('script')[0];
                g.async = true;
                g.src = '//cdn.matomo.cloud/mobiliernational.matomo.cloud/matomo.js';
                s.parentNode.insertBefore(g, s);
            })();
        </script>
        <!-- End Matomo Code -->
    @endif
</head>

<body>
    @yield('content')

    <script src="{{ mix('js/manifest.js') }}"></script>
    <script src="{{ mix('js/article.js') }}"></script>

</body>

</html>
