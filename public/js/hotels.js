(function ($) {
    'use strict';

    var API_URL = '/api/hotels';
    var REFRESH_MS = 60000; // re-fetch prices every 60 seconds

    var SOURCE_COLORS = {
        staydeal: '#ff6b35',
        roomhub:  '#2b6cb0',
        tripnest: '#1a8f4c',
        bookeasy: '#7b2cbf'
    };

    var currentPage = 1;
    var currentXhr = null;
    var detailXhr = null;
    var chart = null;
    var detailData = null;

    var $form = $('#filters');
    var $results = $('#results');
    var $pagination = $('#pagination');
    var $status = $('#status');
    var $priceDate = $('#price-date');
    var $modal = $('#hotelModal');
    var $modalArea = $('#modal-content-area');

    /* ---------- Helpers ---------- */

    function yen(amount) {
        return '\u00A5' + Math.round(amount).toLocaleString('en-US');
    }

    function esc(text) {
        return $('<div>').text(text == null ? '' : text).html();
    }

    function stars(count) {
        return new Array(count + 1).join('\u2605');
    }

    function formatDate(isoDate) {
        var d = new Date(isoDate + 'T00:00:00');
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function shortDate(isoDate) {
        var d = new Date(isoDate + 'T00:00:00');
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    }

    function photoFor(id) {
        return '/img/img_' + ((id % 5) + 1) + '.jpg';
    }

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function savedEmail() {
        try {
            return localStorage.getItem('hk_email') || '';
        } catch (e) {
            return '';
        }
    }

    function saveEmail(email) {
        try {
            localStorage.setItem('hk_email', email);
        } catch (e) {
            // storage unavailable: ignore
        }
    }

    function getParams(page) {
        var params = { page: page };

        $.each(['q', 'city', 'stars', 'min_score', 'max_price', 'sort'], function (i, id) {
            var value = $.trim($('#' + id).val());
            if (value !== '') {
                params[id] = value;
            }
        });

        return params;
    }

    /* ---------- Shared pieces ---------- */

    function changeHtml(price) {
        if (price.change_pct === null || price.change_pct === undefined) {
            return '';
        }
        if (price.change_pct < 0) {
            return '<span class="chg down">\u25BC ' + Math.abs(price.change_pct) + '%</span>';
        }
        if (price.change_pct > 0) {
            return '<span class="chg up">\u25B2 ' + price.change_pct + '%</span>';
        }
        return '<span class="chg flat">no change</span>';
    }

    // One clickable price row. Clicking it opens that booking site's page.
    function sourceRowHtml(hotelId, p) {
        return '<div class="source-row' + (p.is_best ? ' is-best' : '') + '"' +
            ' role="link" tabindex="0" style="cursor:pointer"' +
            ' title="Open ' + esc(p.source) + '"' +
            ' data-url="/go/' + hotelId + '/' + esc(p.slug) + '">' +
            '<span class="src-name">' + esc(p.source) +
            (p.is_best ? '<span class="best-tag">Best</span>' : '') + '</span>' +
            '<span class="src-price">' + yen(p.price) + changeHtml(p) + '</span>' +
            '</div>';
    }

    /* ---------- Hotel cards ---------- */

    function cardHtml(hotel) {
        var prices = hotel.prices || [];
        var best = prices[0];
        var worst = prices[prices.length - 1];
        var score = Number(hotel.review_score).toFixed(1);

        var dropBadge = '';
        if (best && best.change_pct !== null && best.change_pct <= -2) {
            dropBadge = '<span class="badge-drop">\u25BC ' + Math.abs(best.change_pct) + '% today</span>';
        }

        var rows = '';
        $.each(prices, function (i, p) {
            rows += sourceRowHtml(hotel.id, p);
        });

        var saveLine = '';
        if (prices.length > 1 && worst.price > best.price) {
            saveLine = '<div class="save-line">Save ' + yen(worst.price - best.price) +
                ' compared with the most expensive source</div>';
        }

        return '<div class="col-12 col-md-6 col-lg-4 mb-4">' +
            '<div class="hotel-card" data-id="' + hotel.id + '" role="button" tabindex="0">' +
                '<div class="hotel-img">' +
                    '<div class="hotel-photo" style="background-image:url(' + photoFor(hotel.id) + ')"></div>' +
                    dropBadge +
                    '<span class="hotel-score">' + score + '</span>' +
                '</div>' +
                '<div class="hotel-body">' +
                    '<h3 class="hotel-name">' + esc(hotel.name) + '</h3>' +
                    '<div class="hotel-meta">' + esc(hotel.area) + ', ' + esc(hotel.city) + '</div>' +
                    '<div class="hotel-stars">' + stars(hotel.star_rating) + '</div>' +
                    '<div class="source-list">' + rows + '</div>' +
                    '<div class="hotel-price-row">' +
                        '<div><small>Best price today</small>' +
                        '<div class="hotel-price">' + yen(best ? best.price : hotel.best_price) + '</div></div>' +
                    '</div>' +
                    saveLine +
                    '<div class="view-hint">View price history <span class="fa fa-arrow-right"></span></div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function showSkeleton() {
        var html = '';
        for (var i = 0; i < 6; i++) {
            html += '<div class="col-12 col-md-6 col-lg-4 mb-4"><div class="skeleton-card">' +
                '<div class="sk-img"></div>' +
                '<div class="sk-body">' +
                '<div class="sk-line"></div><div class="sk-line short"></div>' +
                '<div class="sk-line"></div><div class="sk-line short"></div>' +
                '</div></div></div>';
        }
        $results.html(html);
        $pagination.empty();
    }

    function pageButton(label, page, disabled, active) {
        return '<button type="button" data-page="' + page + '"' +
            (disabled ? ' disabled' : '') +
            (active ? ' class="active"' : '') + '>' + label + '</button>';
    }

    function renderPagination(resp) {
        var current = resp.current_page;
        var last = resp.last_page;

        if (last <= 1) {
            $pagination.empty();
            return;
        }

        var html = pageButton('\u2039 Prev', current - 1, current === 1, false);
        var start = Math.max(1, current - 2);
        var end = Math.min(last, current + 2);

        for (var p = start; p <= end; p++) {
            html += pageButton(p, p, false, p === current);
        }

        html += pageButton('Next \u203A', current + 1, current === last, false);
        $pagination.html(html);
    }

    function render(resp, animate) {
        $results.empty();

        if (!resp.data.length) {
            $results.html(
                '<div class="col-12"><div class="empty-state">' +
                '<span class="fa fa-search"></span>' +
                '<p>No hotels match your search. Try changing the filters.</p>' +
                '</div></div>'
            );
            $pagination.empty();
            $status.text('No hotels found');
        } else {
            $.each(resp.data, function (i, hotel) {
                var $card = $(cardHtml(hotel));
                $results.append($card);
                if (animate) {
                    $card.hide().delay(i * 70).fadeIn(350);
                }
            });

            renderPagination(resp);
            $status.text('Showing ' + resp.from + '\u2013' + resp.to + ' of ' + resp.total + ' hotels');
        }

        $priceDate.html(
            '<span class="live-dot"></span>Prices as of ' + formatDate(resp.price_date) +
            ' \u00B7 updated ' + new Date().toLocaleTimeString()
        );
    }

    /* ---------- Loading the hotel list ---------- */

    function load(page, opts) {
        opts = opts || {};

        // A background refresh never interrupts a search that is still running
        if (opts.silent && currentXhr && currentXhr.readyState !== 4) {
            return;
        }

        if (currentXhr) {
            currentXhr.abort();
        }

        currentPage = page || 1;

        if (!opts.silent) {
            showSkeleton();
            $status.text('Loading prices...');
        }

        currentXhr = $.getJSON(API_URL, getParams(currentPage))
            .done(function (resp) {
                currentPage = resp.current_page;
                render(resp, !opts.silent);

                if (opts.scroll) {
                    $('html, body').animate({ scrollTop: $results.offset().top - 120 }, 400);
                }
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort' || opts.silent) {
                    return;
                }

                var message = xhr.status === 422
                    ? 'Please check your filters and try again.'
                    : 'Could not load prices. Please try again.';

                $results.html(
                    '<div class="col-12"><div class="empty-state">' +
                    '<span class="fa fa-exclamation-triangle"></span><p>' + message + '</p>' +
                    '</div></div>'
                );
                $status.text('');
            });
    }

    /* ---------- Hotel popup ---------- */

    function destroyChart() {
        if (chart) {
            chart.destroy();
            chart = null;
        }
    }

    function alertBoxHtml(d) {
        return '' +
            '<div class="p-3 rounded bg-light mb-4">' +
                '<h5 class="mb-1"><span class="fa fa-bell-o"></span> Get a price drop alert</h5>' +
                '<p class="text-muted small mb-3">Today\'s best price is ' + yen(d.stats.current) +
                '. Tell us your target and we\'ll email you when it falls to that price or lower.</p>' +
                '<form id="alert-form" novalidate data-hotel-id="' + d.hotel.id + '">' +
                    '<div class="form-row">' +
                        '<div class="col-12 col-md-5 mb-2">' +
                            '<input type="email" id="alert-email" class="form-control" placeholder="Your email" ' +
                            'autocomplete="email" value="' + esc(savedEmail()) + '" aria-label="Your email">' +
                        '</div>' +
                        '<div class="col-7 col-md-4 mb-2">' +
                            '<input type="number" id="alert-price" class="form-control" placeholder="Target price (JPY)" ' +
                            'min="1000" step="100" aria-label="Target price in yen">' +
                        '</div>' +
                        '<div class="col-5 col-md-3 mb-2">' +
                            '<button type="submit" class="btn btn-primary btn-block" id="alert-btn">Set alert</button>' +
                        '</div>' +
                    '</div>' +
                    '<div id="alert-msg" class="small" role="status" aria-live="polite"></div>' +
                '</form>' +
            '</div>';
    }

    function modalHtml(d) {
        var h = d.hotel;
        var rows = '';

        $.each(d.sources, function (i, p) {
            rows += sourceRowHtml(h.id, p);
        });

        return '' +
            '<div class="hk-modal-hero" style="background-image:url(' + photoFor(h.id) + ')">' +
                '<div class="hero-text">' +
                    '<h3>' + esc(h.name) + '</h3>' +
                    '<span>' + esc(h.area) + ', ' + esc(h.city) + ' &nbsp;\u00B7&nbsp; ' +
                    '<span style="color:#ffc107;letter-spacing:2px">' + stars(h.star_rating) + '</span>' +
                    ' &nbsp;\u00B7&nbsp; Score ' + Number(h.review_score).toFixed(1) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="modal-body">' +
                '<div class="verdict ' + esc(d.verdict.status) + '">' +
                    '<strong>' + esc(d.verdict.title) + '</strong>' + esc(d.verdict.message) +
                '</div>' +
                '<div class="stat-tiles">' +
                    '<div class="stat-tile"><div class="tile-value">' + yen(d.stats.current) + '</div><div class="tile-label">Best today</div></div>' +
                    '<div class="stat-tile"><div class="tile-value">' + yen(d.stats.lowest) + '</div><div class="tile-label">Lowest in ' + d.stats.days + ' days</div></div>' +
                    '<div class="stat-tile"><div class="tile-value">' + yen(d.stats.average) + '</div><div class="tile-label">' + d.stats.days + '-day average</div></div>' +
                '</div>' +
                '<h5 class="mb-2">Today\'s prices <small class="text-muted">(click a site to open it)</small></h5>' +
                '<div class="source-list mb-4" style="margin-top:6px">' + rows + '</div>' +
                alertBoxHtml(d) +
                '<div class="d-flex justify-content-between align-items-center flex-wrap">' +
                    '<h5 class="mb-2">Price trend</h5>' +
                    '<div class="range-tabs">' +
                        '<button type="button" data-days="30">30 days</button>' +
                        '<button type="button" data-days="90" class="active">90 days</button>' +
                    '</div>' +
                '</div>' +
                '<div class="chart-wrap"><canvas id="price-chart"></canvas></div>' +
            '</div>';
    }

    function drawChart(d, days) {
        destroyChart();

        var canvas = document.getElementById('price-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        var h = d.history;
        var start = Math.max(0, h.dates.length - days);

        var labels = [];
        $.each(h.dates.slice(start), function (i, date) {
            labels.push(shortDate(date));
        });

        var datasets = [{
            label: 'Best price',
            data: h.best.slice(start),
            borderColor: '#e61c5d',
            backgroundColor: 'rgba(230, 28, 93, 0.10)',
            borderWidth: 3,
            fill: true,
            tension: 0.3,
            pointRadius: 0,
            pointHoverRadius: 5
        }];

        $.each(h.series, function (i, s) {
            datasets.push({
                label: s.name,
                data: s.data.slice(start),
                borderColor: SOURCE_COLORS[s.slug] || '#999999',
                borderWidth: 1.5,
                fill: false,
                tension: 0.3,
                pointRadius: 0,
                pointHoverRadius: 4,
                spanGaps: true
            });
        });

        chart = new Chart(canvas, {
            type: 'line',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.parsed.y === null) {
                                    return ctx.dataset.label + ': -';
                                }
                                return ctx.dataset.label + ': ' + yen(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8, maxRotation: 0 } },
                    y: { ticks: { callback: function (value) { return yen(value); } } }
                }
            }
        });
    }

    function openHotel(id) {
        destroyChart();
        detailData = null;

        $modalArea.html(
            '<div class="modal-loading"><span class="fa fa-spinner fa-spin"></span> Loading price history...</div>'
        );
        $modal.modal('show');

        if (detailXhr) {
            detailXhr.abort();
        }

        detailXhr = $.getJSON(API_URL + '/' + id)
            .done(function (d) {
                detailData = d;
                $modalArea.html(modalHtml(d));
                drawChart(d, 90);
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort') {
                    return;
                }
                $modalArea.html(
                    '<div class="modal-loading"><span class="fa fa-exclamation-triangle"></span> ' +
                    'Could not load this hotel. Please try again.</div>'
                );
            });
    }

    function openSource(url) {
        window.open(url, '_blank', 'noopener');
    }

    /* ---------- Price alert form ---------- */

    function showAlertMessage(text, type) {
        $('#alert-msg')
            .css('color', type === 'ok' ? '#1a8f4c' : '#c92a2a')
            .text(text);
    }

    function submitAlert($alertForm) {
        var email = $.trim($('#alert-email').val());
        var price = $.trim($('#alert-price').val());
        var $btn = $('#alert-btn');

        if (!email || email.indexOf('@') < 1) {
            showAlertMessage('Please enter a valid email address.', 'error');
            return;
        }
        if (!price || Number(price) < 1000) {
            showAlertMessage('Please enter a target price of at least \u00A51,000.', 'error');
            return;
        }

        $btn.prop('disabled', true).text('Saving...');
        showAlertMessage('', 'ok');

        $.ajax({
            url: '/api/alerts',
            method: 'POST',
            dataType: 'json',
            headers: { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') },
            data: {
                hotel_id: $alertForm.data('hotel-id'),
                email: email,
                target_price: price
            }
        })
        .done(function (resp) {
            saveEmail(email);
            showAlertMessage(resp.message, 'ok');
        })
        .fail(function (xhr) {
            var message = 'Could not save your alert. Please try again.';

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var errors = xhr.responseJSON.errors;
                var firstKey = Object.keys(errors)[0];
                message = errors[firstKey][0];
            } else if (xhr.status === 419) {
                message = 'Your session expired. Please refresh the page and try again.';
            } else if (xhr.status === 429) {
                message = 'Too many attempts. Please wait a minute and try again.';
            }

            showAlertMessage(message, 'error');
        })
        .always(function () {
            $btn.prop('disabled', false).text('Set alert');
        });
    }

    /* ---------- Events ---------- */

    // Search button (and the Enter key inside the form)
    $form.on('submit', function (e) {
        e.preventDefault();
        load(1, { scroll: true });
    });

    // Dropdowns update the results straight away
    $('#city, #stars, #min_score, #sort').on('change', function () {
        load(1);
    });

    $('#reset').on('click', function () {
        $form[0].reset();
        load(1);
    });

    $pagination.on('click', 'button', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        load(parseInt($(this).data('page'), 10), { scroll: true });
    });

    // Click a card: open the popup (but not when a price row was clicked)
    $results.on('click', '.hotel-card', function (e) {
        if ($(e.target).closest('.source-row').length) {
            return;
        }
        openHotel($(this).data('id'));
    });

    $results.on('keydown', '.hotel-card', function (e) {
        if (e.which === 13 && e.target === this) {
            openHotel($(this).data('id'));
        }
    });

    // Click a price row (on a card or in the popup): open that booking site
    $(document).on('click', '.source-row[data-url]', function () {
        openSource($(this).data('url'));
    });

    $(document).on('keydown', '.source-row[data-url]', function (e) {
        if (e.which === 13) {
            openSource($(this).data('url'));
        }
    });

    // 30 / 90 day switch on the chart
    $modalArea.on('click', '.range-tabs button', function () {
        if (!detailData) {
            return;
        }
        $(this).addClass('active').siblings().removeClass('active');
        drawChart(detailData, parseInt($(this).data('days'), 10));
    });

    // Price alert form inside the popup
    $modalArea.on('submit', '#alert-form', function (e) {
        e.preventDefault();
        submitAlert($(this));
    });

    $modal.on('hidden.bs.modal', function () {
        destroyChart();
        if (detailXhr) {
            detailXhr.abort();
        }
    });

    // Keep prices live: re-fetch quietly every minute
    setInterval(function () {
        if (!document.hidden) {
            load(currentPage, { silent: true });
        }
    }, REFRESH_MS);

    /* ---------- Start ---------- */
    load(1);

})(jQuery);