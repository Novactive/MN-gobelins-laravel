<!DOCTYPE html>
<html dir="ltr" lang="fr-FR" class="no-js BlocksLayout @yield('html_classes')">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ mix('css/article.css') }}" rel="stylesheet" type="text/css">

    <script>
        var _paq = window._paq = window._paq || [];
        /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var env = '{{ env('APP_ENV') }}';
            var u = "https://mobiliernational.matomo.cloud/";
            _paq.push(['setTrackerUrl', u + 'matomo.php']);
            env === 'prod' ? _paq.push(['setSiteId', '2']) : _paq.push(['setSiteId', '4']);
            var d = document,
                g = d.createElement('script'),
                s = d.getElementsByTagName('script')[0];
            g.async = true;
            g.src = '//cdn.matomo.cloud/mobiliernational.matomo.cloud/matomo.js';
            s.parentNode.insertBefore(g, s);
        })();
    </script>
</head>

<body>

    @yield('content')
</body>

</html>
