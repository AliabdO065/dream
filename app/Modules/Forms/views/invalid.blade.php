<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Please check your details</title>
<style>body{font-family:system-ui,sans-serif;max-width:32rem;margin:4rem auto;padding:0 1rem;color:#111827}a{color:#2563eb}</style></head>
<body>
    <h1>Please check your details</h1>
    <ul>@foreach($errors as $e)<li>{{ $e }}</li>@endforeach</ul>
    <p><a href="{{ $back }}">&larr; Back</a></p>
</body></html>
