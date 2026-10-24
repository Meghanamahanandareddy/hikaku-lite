<!doctype html>
<html lang="en">
<head>
    <title>{{ $source->name }} - {{ $hotel->name }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Mukta+Mahee:200,300,400|Playfair+Display:400,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <style>
        body { background: #f4f6f8; font-family: "Mukta Mahee", sans-serif; color: #333; }
        .partner-bar { background: {{ $brand }}; color: #fff; padding: 16px 0; }
        .partner-bar .logo { font-family: "Playfair Display", serif; font-size: 1.6rem; font-weight: 700; }
        .partner-bar .tag { opacity: 0.85; font-size: 0.9rem; }
        .partner-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.1); margin-top: 30px; }
        .partner-photo { height: 260px; background-size: cover; background-position: center; }
        .partner-body { padding: 28px; }
        .partner-price { font-family: "Playfair Display", serif; font-size: 2.2rem; font-weight: 700; color: {{ $brand }}; }
        .btn-brand { background: {{ $brand }}; border-color: {{ $brand }}; color: #fff; padding: 12px 32px; border-radius: 50px; }
        .btn-brand:hover { color: #fff; opacity: 0.9; }
        .demo-note { background: #fff8e1; border-left: 4px solid #f2b705; padding: 12px 16px; border-radius: 8px; font-size: 0.9rem; margin-top: 24px; }
    </style>
</head>
<body>

<div class="partner-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="logo">{{ $source->name }}</span>
        <span class="tag">Special deal via Hikaku Lite</span>
    </div>
</div>

<div class="container mb-5">
    <div class="partner-card">
        <div class="partner-photo" style="background-image: url({{ $photo }})"></div>
        <div class="partner-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-1">{{ $hotel->name }}</h1>
                    <p class="text-muted mb-1">{{ $hotel->area }}, {{ $hotel->city }}</p>
                    <p class="mb-0" style="color:#e8a317; letter-spacing:2px;">{{ str_repeat('★', $hotel->star_rating) }}</p>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <small class="text-muted d-block">Price per night</small>
                    <div class="partner-price">&yen;{{ number_format($price) }}</div>
                    <small class="text-muted">as of {{ \Carbon\Carbon::parse($priceDate)->format('j M Y') }}</small>
                </div>
            </div>

            <hr class="my-4">

            <p>{{ $hotel->description }}</p>

            <div class="mt-4">
                <a href="#" class="btn btn-brand mr-2" onclick="alert('Demo only: no booking is made.'); return false;">Book now</a>
                <a href="/" class="btn btn-outline-secondary" style="border-radius:50px;">Back to Hikaku Lite</a>
            </div>

            <div class="demo-note">
                <strong>Demo page.</strong> {{ $source->name }} is a mock booking site used by the Hikaku Lite demo.
                A real integration would send visitors to the partner's own booking page for this hotel.
            </div>
        </div>
    </div>
</div>

</body>
</html>