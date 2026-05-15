(function () {
    var inst = null;

    function getWrap() {
        return document.getElementById('tabla-citas-agenda');
    }

    function permiteArrastre() {
        var w = getWrap();
        if (!w) {
            return false;
        }
        var o = w.getAttribute('data-orden-actual') || '';
        return o === 'fecha';
    }

    function apiReordenar() {
        if (typeof window.DENTISTA_API_REORDENAR_CITAS === 'string' && window.DENTISTA_API_REORDENAR_CITAS) {
            return window.DENTISTA_API_REORDENAR_CITAS;
        }
        return 'api_reordenar_citas.php';
    }

    function destruir() {
        if (inst) {
            inst.destroy();
            inst = null;
        }
    }

    function initCitasAgendaSort() {
        destruir();
        var wrap = getWrap();
        var tbody = wrap && wrap.querySelector('tbody');
        if (!tbody || typeof Sortable === 'undefined') {
            return;
        }
        if (tbody.querySelectorAll('tr').length < 2) {
            return;
        }
        if (!permiteArrastre()) {
            return;
        }

        function fechaDeFila(el) {
            if (!el || !el.getAttribute) {
                return null;
            }
            if (el.tagName === 'TR' && el.getAttribute('data-fecha')) {
                return el.getAttribute('data-fecha');
            }
            var tr = el.closest && el.closest('tr[data-fecha]');
            return tr ? tr.getAttribute('data-fecha') : null;
        }

        inst = Sortable.create(tbody, {
            animation: 160,
            handle: '.citas-drag-cell',
            draggable: 'tr',
            forceFallback: true,
            fallbackTolerance: 3,
            ghostClass: 'citas-sort-ghost',
            chosenClass: 'citas-sort-chosen',
            dragClass: 'citas-sort-drag',
            filter: 'input, textarea, button, select, option, a',
            preventOnFilter: true,
            onMove: function (evt) {
                var draggedF = evt.dragged.getAttribute('data-fecha');
                var relatedF = fechaDeFila(evt.related);
                if (relatedF == null) {
                    return true;
                }
                return draggedF === relatedF;
            },
            onEnd: function () {
                var groups = {};
                tbody.querySelectorAll('tr').forEach(function (tr) {
                    var id = tr.getAttribute('data-cita-id');
                    var fd = tr.getAttribute('data-fecha');
                    if (!id || !fd) {
                        return;
                    }
                    if (!groups[fd]) {
                        groups[fd] = [];
                    }
                    groups[fd].push(parseInt(id, 10));
                });
                fetch(apiReordenar(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ byFecha: groups }),
                })
                    .then(function (r) {
                        return r.json().then(function (j) {
                            return { ok: r.ok, j: j };
                        });
                    })
                    .then(function (x) {
                        if (!x.ok || !x.j.ok) {
                            if (x.j && x.j.error) {
                                console.warn('Reordenar citas:', x.j.error);
                            }
                        }
                    })
                    .catch(function () {});
            },
        });
    }

    window.initCitasAgendaSort = initCitasAgendaSort;

    document.addEventListener('DOMContentLoaded', initCitasAgendaSort);
})();
