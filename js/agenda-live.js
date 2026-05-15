(function () {
    var form = document.getElementById('form-filtros-agenda');
    var wrap = document.getElementById('tabla-citas-agenda');
    var input = document.getElementById('input-busqueda-agenda');
    if (!form || !wrap || !input) return;

    var debounceMs = 280;
    var t = null;
    var seq = 0;

    function mergeLocationParams(params) {
        var u = new URLSearchParams(window.location.search);
        ['orden', 'dir', 'mes', 'anio', 'fecha'].forEach(function (k) {
            if (!params.has(k) && u.has(k)) {
                params.set(k, u.get(k));
            }
        });
    }

    function queryFromForm() {
        var fd = new FormData(form);
        var params = new URLSearchParams(fd);
        mergeLocationParams(params);
        return params.toString();
    }

    function refreshTable() {
        var my = ++seq;
        wrap.classList.add('table-loading');
        var q = queryFromForm();
        var api =
            typeof window.DENTISTA_API_TABLA_CITAS === 'string' && window.DENTISTA_API_TABLA_CITAS
                ? window.DENTISTA_API_TABLA_CITAS
                : 'api_tabla_citas.php';
        fetch(api + '?' + q, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (my !== seq) return;
                if (typeof data.html === 'string') {
                    wrap.innerHTML = data.html;
                    if (typeof window.initCitasAgendaSort === 'function') {
                        window.initCitasAgendaSort();
                    }
                }
            })
            .catch(function () {
                if (my !== seq) return;
            })
            .finally(function () {
                if (my === seq) wrap.classList.remove('table-loading');
            });
    }

    function scheduleRefresh() {
        clearTimeout(t);
        t = setTimeout(refreshTable, debounceMs);
    }

    input.addEventListener('input', scheduleRefresh);

    ['estado', 'fecha_desde', 'fecha_hasta'].forEach(function (name) {
        var el = form.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('change', scheduleRefresh);
    });
})();
