(function ($) {
    'use strict';

    var REFRESH_MS = 60000;

    var counters = [
        { id: 'kpi-hotels',  key: 'hotels',  prefix: '' },
        { id: 'kpi-sources', key: 'sources', prefix: '' },
        { id: 'kpi-average', key: 'average', prefix: '\u00A5' },
        { id: 'kpi-drops',   key: 'drops',   prefix: '' }
    ];

    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var stats = null;      // latest numbers from the API
    var visible = false;   // dashboard has scrolled into view
    var played = false;    // count-up animation already ran

    function format(value, prefix) {
        return prefix + Math.round(value).toLocaleString('en-US');
    }

    // Count from 0 up to the target with an ease-out curve
    function animate(el, target, prefix) {
        if (reduceMotion || target === 0) {
            el.textContent = format(target, prefix);
            return;
        }

        var duration = 1600;
        var start = null;

        function step(timestamp) {
            if (start === null) {
                start = timestamp;
            }

            var progress = Math.min((timestamp - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);

            el.textContent = format(target * eased, prefix);

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }

        requestAnimationFrame(step);
    }

    function setAll(data) {
        $.each(counters, function (i, c) {
            var el = document.getElementById(c.id);
            if (el) {
                el.textContent = format(Number(data[c.key]) || 0, c.prefix);
            }
        });
    }

    function play() {
        if (played || !stats || !visible) {
            return;
        }

        played = true;

        $.each(counters, function (i, c) {
            var el = document.getElementById(c.id);
            if (el) {
                animate(el, Number(stats[c.key]) || 0, c.prefix);
            }
        });
    }

    function loadStats() {
        $.getJSON('/api/stats').done(function (data) {
            stats = data;

            if (played) {
                setAll(data);   // quiet refresh, no animation
            } else {
                play();
            }
        });
    }

    // Start the count-up when the dashboard scrolls into view
    var section = document.getElementById('dashboard');

    if (section && typeof Waypoint !== 'undefined') {
        new Waypoint({
            element: section,
            handler: function () {
                visible = true;
                play();
                this.destroy();
            },
            offset: '85%'
        });
    } else {
        visible = true;
    }

    loadStats();

    setInterval(function () {
        if (!document.hidden) {
            loadStats();
        }
    }, REFRESH_MS);

})(jQuery);