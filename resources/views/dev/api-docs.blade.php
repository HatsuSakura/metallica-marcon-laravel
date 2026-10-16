<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>Metallica Marcon API — DEV</title>
    <style>html, body { margin: 0; min-height: 100%; }</style>
</head>
<body>
    <script
        id="api-reference"
        data-url="{{ route('dev.api-docs.openapi') }}"
    ></script>
    <script>
        document.getElementById('api-reference').dataset.configuration = JSON.stringify({
            theme: 'purple',
            layout: 'modern',
            hideTestRequestButton: true,
            hideClientButton: true,
            metaData: { title: 'Metallica Marcon API — DEV' },
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.24.66"></script>
</body>
</html>
