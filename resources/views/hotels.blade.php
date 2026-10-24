<!doctype html>
<html lang="en">
<head>
    <title>Hikaku Lite - Compare Hotel Prices</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Mukta+Mahee:200,300,400|Playfair+Display:400,700" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/ionicons/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fontawesome/css/font-awesome.min.css') }}">

    <!-- Theme style, then our own styles -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hikaku.css') }}">
</head>
<body>

<header class="site-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-4 site-logo" data-aos="fade"><a href="/"><em>Hikaku</em> Lite</a></div>
            <div class="col-8">

                <div class="site-menu-toggle js-site-menu-toggle" data-aos="fade">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <div class="site-navbar js-site-navbar">
                    <nav role="navigation">
                        <div class="container">
                            <div class="row full-height align-items-center">
                                <div class="col-md-6">
                                    <ul class="list-unstyled menu">
                                        <li class="active"><a href="/">Home</a></li>
                                        <li><a href="#dashboard">Dashboard</a></li>
                                        <li><a href="#search">Hotels</a></li>
                                    </ul>
                                </div>
                                <div class="col-md-6 extra-info">
                                    <h3>About Hikaku Lite</h3>
                                    <p>Compare hotel prices from several sources and see the price trend, so you know whether to book now or wait.</p>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- END header -->

<section class="site-hero overlay" style="background-image: url({{ asset('img/hero_1.jpg') }})">
    <div class="container">
        <div class="row site-hero-inner justify-content-center align-items-center">
            <div class="col-md-10 text-center">
                <h1 class="heading" data-aos="fade-up">Find the Best Hotel Price</h1>
                <p class="sub-heading mb-5" data-aos="fade-up" data-aos-delay="100">
                    We track prices every day, so you know when to book.
                </p>
                <a href="#search" class="btn btn-primary uppercase" data-aos="fade-up" data-aos-delay="200">Start Searching</a>
            </div>
        </div>
    </div>
</section>
<!-- END hero -->

<section class="section dashboard-section" id="dashboard">
    <div class="container">
        <div class="row text-center">
            <div class="col-6 col-md-3 mb-4" data-aos="fade-up">
                <div class="kpi-card">
                    <span class="kpi-icon fa fa-building-o"></span>
                    <div class="kpi-number" id="kpi-hotels" data-count="0">0</div>
                    <div class="kpi-label">Hotels tracked</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-4" data-aos="fade-up" data-aos-delay="100">
                <div class="kpi-card">
                    <span class="kpi-icon fa fa-globe"></span>
                    <div class="kpi-number" id="kpi-sources" data-count="0">0</div>
                    <div class="kpi-label">Price sources</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-4" data-aos="fade-up" data-aos-delay="200">
                <div class="kpi-card">
                    <span class="kpi-icon fa fa-jpy"></span>
                    <div class="kpi-number" id="kpi-average" data-count="0">0</div>
                    <div class="kpi-label">Average best price</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-4" data-aos="fade-up" data-aos-delay="300">
                <div class="kpi-card">
                    <span class="kpi-icon fa fa-line-chart"></span>
                    <div class="kpi-number" id="kpi-drops" data-count="0">0</div>
                    <div class="kpi-label">Price drops today</div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- END dashboard -->

<section class="section bg-light" id="search">
    <div class="container">

        <div class="row justify-content-center text-center mb-5">
            <div class="col-md-8">
                <h2 class="heading" data-aos="fade-up">Compare Hotels</h2>
                <p class="lead" data-aos="fade-up" data-aos-delay="100">Search, filter and sort. Every price shown is the cheapest across all sources today.</p>
            </div>
        </div>

        <form id="filters" class="filters-panel mb-4" autocomplete="off" data-aos="fade-up">
            <div class="form-row">
                <div class="col-12 mb-3">
                    <input type="search" id="q" name="q" class="form-control" placeholder="Search hotel or area">
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <select id="city" name="city" class="form-control" aria-label="City">
                        <option value="">All cities</option>
                        <option>Tokyo</option>
                        <option>Kyoto</option>
                        <option>Osaka</option>
                        <option>Fukuoka</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 mb-3">
                    <select id="stars" name="stars" class="form-control" aria-label="Stars">
                        <option value="">Any stars</option>
                        <option value="2">2 stars</option>
                        <option value="3">3 stars</option>
                        <option value="4">4 stars</option>
                        <option value="5">5 stars</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <select id="min_score" name="min_score" class="form-control" aria-label="Minimum review score">
                        <option value="">Any score</option>
                        <option value="7">7.0+</option>
                        <option value="8">8.0+</option>
                        <option value="9">9.0+</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 mb-3">
                    <input type="number" id="max_price" name="max_price" class="form-control" min="0" step="1000"
                           placeholder="Max price" aria-label="Maximum price">
                </div>
                <div class="col-12 col-md-2 mb-3">
                    <select id="sort" name="sort" class="form-control" aria-label="Sort by">
                        <option value="price_asc">Price: low to high</option>
                        <option value="price_desc">Price: high to low</option>
                        <option value="rating">Best reviewed</option>
                        <option value="stars">Star rating</option>
                        <option value="name">Name</option>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center mb-3">
                <button type="submit" id="search-btn" class="btn btn-primary uppercase">
                    <span class="fa fa-search"></span> Search
                </button>
                <button type="button" id="reset" class="btn-reset ml-4 p-0">Reset filters</button>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div id="status" class="status mb-0" aria-live="polite"></div>
            <div id="price-date" class="status mb-0"></div>
        </div>

        <div id="results" class="row"></div>
        <nav id="pagination" class="pagination-bar" aria-label="Pages"></nav>

    </div>
</section>
<!-- END search -->

<footer class="section footer-section">
    <div class="container">
        <div class="row bordertop pt-5">
            <p class="col-md-8 text-left">
                Hikaku Lite is a demo project with mock data.
                Template by <a href="https://colorlib.com" target="_blank" rel="noopener">Colorlib</a> (CC BY 3.0).
            </p>
            <p class="col-md-4 text-right social">
                <a href="#"><span class="fa fa-facebook"></span></a>
                <a href="#"><span class="fa fa-twitter"></span></a>
            </p>
        </div>
    </div>
</footer>

<!-- Hotel detail popup -->
<div class="modal fade hk-modal" id="hotelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="hk-modal-close" data-dismiss="modal" aria-label="Close">&times;</button>
            <div id="modal-content-area"></div>
        </div>
    </div>
</div>

<script src="{{ asset('js/jquery-3.2.1.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('js/jquery.waypoints.min.js') }}"></script>
<script src="{{ asset('js/aos.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="{{ asset('js/hotels.js') }}"></script>
</body>
</html>