<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer-when-downgrade">
    <title>EmbedLayer Dashboard</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; background: transparent; font-family: system-ui, sans-serif; }
        embed-layer-dashboard:not(:defined) { display: block; padding: 1rem; color: #6b7280; }
    </style>
</head>
<body>
    <embed-layer-dashboard
        embed-id="{{ $embed_id }}"
        token="{{ $token }}"
        api-base-url="{{ url('/api/embed') }}">
        Loading dashboard…
    </embed-layer-dashboard>

    {{-- The runtime bundle is built and served by the standalone
         @embedlayer/runtime package (see Plan §12). The path below is the
         expected V1 location for the served bundle. --}}
    <script src="{{ asset('vendor/embedlayer/runtime.js') }}" defer></script>
</body>
</html>
