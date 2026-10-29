(function () {
    'use strict';
    if (!window.MEDIA_VIEW) return;
    var D = window.MEDIA_VIEW;
    var KIND = D.kind;
    var BINDS = D.bindings || [];
    var pdfDoc = null;
    var pdfPage = 1;
    var pdfScale = 1.7;
    var colorFor = function (i) { return 'hsl(' + ((i * 47) % 360) + ',55%,50%)'; };

    function activeBoxes() {
        if (KIND === 'image') return BINDS.filter(function (b) { return b.btype === 'region'; });
        if (KIND === 'pdf') return BINDS.filter(function (b) { return b.btype === 'page'; });
        return [];
    }

    function overlayImg() {
        var stage = document.getElementById('regionStage');
        if (!stage) return;
        [].slice.call(stage.querySelectorAll('.region-box')).forEach(function (el) { el.remove(); });
        BINDS.forEach(function (b, i) {
            if (b.btype !== 'region') return;
            if (!(b.w > 0 && b.h > 0)) return;
            var div = document.createElement('div');
            div.className = 'region-box';
            div.dataset.idx = String(i);
            div.style.left = b.x + '%';
            div.style.top = b.y + '%';
            div.style.width = b.w + '%';
            div.style.height = b.h + '%';
            div.style.borderColor = colorFor(i);
            div.title = b.ghost ? 'Скрытая персона' : b.person_name;
            if (!b.ghost) {
                div.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var chip = document.querySelector('.binding-chip[data-idx="' + i + '"]');
                    if (chip && chip.dataset.href) window.location.href = chip.dataset.href;
                });
            }
            stage.appendChild(div);
        });
    }

    function overlayPdf() {
        var stage = document.getElementById('pdfStage');
        if (!stage) return;
        var canvas = document.getElementById('pdfCanvas');
        [].slice.call(stage.querySelectorAll('.region-box')).forEach(function (el) { el.remove(); });
        BINDS.forEach(function (b, i) {
            if (b.btype !== 'page' || b.page !== pdfPage - 1) return;
            if (!(b.w > 0 && b.h > 0)) return;
            var div = document.createElement('div');
            div.className = 'region-box';
            div.dataset.idx = String(i);
            div.style.left = b.x + '%';
            div.style.top = b.y + '%';
            div.style.width = b.w + '%';
            div.style.height = b.h + '%';
            div.style.borderColor = colorFor(i);
            div.title = b.ghost ? 'Скрытая персона' : b.person_name;
            if (!b.ghost) {
                div.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var chip = document.querySelector('.binding-chip[data-idx="' + i + '"]');
                    if (chip && chip.dataset.href) window.location.href = chip.dataset.href;
                });
            }
            stage.appendChild(div);
        });
    }

    function renderPdfPage() {
        if (!pdfDoc) return;
        var canvas = document.getElementById('pdfCanvas');
        var stage = document.getElementById('pdfStage');
        pdfDoc.getPage(pdfPage).then(function (page) {
            var base = page.getViewport({ scale: 1 });
            var target = 900;
            pdfScale = target / base.width;
            var viewport = page.getViewport({ scale: pdfScale });
            var ctx = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                stage.style.display = 'block';
                var lbl = document.getElementById('pdfPageNo');
                if (lbl) lbl.textContent = pdfPage;
                var inp = document.getElementById('pdfPageInput');
                if (inp) inp.value = pdfPage;
                var state = document.getElementById('pdfLoadState');
                if (state) state.textContent = 'страница ' + pdfPage + ' из ' + pdfDoc.numPages;
                overlayPdf();
            });
        }).catch(function (err) {
            var state = document.getElementById('pdfLoadState');
            if (state) state.textContent = 'ошибка чтения PDF: ' + err;
        });
    }

    function initPdf() {
        var pdfjs = window.pdfjsLib;
        var state = document.getElementById('pdfLoadState');
        if (!pdfjs) {
            if (state) state.textContent = 'библиотека PDF не загрузилась';
            return;
        }
        pdfjs.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
        pdfjs.getDocument({ url: D.preview }).promise.then(function (doc) {
            pdfDoc = doc;
            renderPdfPage();
        }).catch(function () {
            if (state) state.textContent = 'не удалось открыть PDF';
        });
        var next = document.getElementById('pdfNext');
        var prev = document.getElementById('pdfPrev');
        var input = document.getElementById('pdfPageInput');
        if (next) next.addEventListener('click', function () { if (pdfPage < pdfDoc.numPages) { pdfPage++; renderPdfPage(); } });
        if (prev) prev.addEventListener('click', function () { if (pdfPage > 1) { pdfPage--; renderPdfPage(); } });
        if (input) input.addEventListener('change', function () {
            var v = parseInt(input.value, 10);
            if (!isNaN(v) && v >= 1 && pdfDoc && v <= pdfDoc.numPages) { pdfPage = v; renderPdfPage(); }
        });
    }

    function initTime() {
        var player = document.getElementById('mediaPlayer');
        if (!player) return;
        BINDS.forEach(function (b, i) {
            var chip = document.querySelector('.binding-chip[data-idx="' + i + '"]');
            if (!chip) return;
            if (b.btype !== 'time') return;
            if (b.ghost) return;
            var href = chip.dataset.href;
            chip.style.cursor = 'pointer';
            chip.addEventListener('click', function () {
                if (typeof b.tstart === 'number' && isFinite(b.tstart)) {
                    try { player.currentTime = b.tstart; } catch (e) {}
                    player.play();
                }
                highlightRow(chip);
            });
        });
    }

    function highlightRow(chip) {
        [].slice.call(document.querySelectorAll('.binding-chip')).forEach(function (el) { el.style.outline = 'none'; });
        if (chip) chip.style.outline = '2px solid var(--accent)';
    }

    function initChips() {
        if (KIND !== 'time') {
            [].slice.call(document.querySelectorAll('.binding-chip')).forEach(function (chip, i) {
                var boxes = document.querySelectorAll('.region-box');
                chip.addEventListener('mouseenter', function () {
                    if (KIND === 'pdf') {
                        var b = BINDS[i];
                        if (b && b.btype === 'page' && b.page + 1 !== pdfPage) {
                            pdfPage = b.page + 1;
                            renderPdfPage();
                            var self = this;
                            setTimeout(function () { flashBox(i); }, 350);
                            return;
                        }
                    }
                    flashBox(i);
                });
                chip.addEventListener('mouseleave', function () {
                    [].slice.call(document.querySelectorAll('.region-box')).forEach(function (b) { b.classList.remove('active'); });
                });
                if (chip.dataset.href && !chip.dataset.followed) {
                    chip.addEventListener('click', function () {
                        window.location.href = chip.dataset.href;
                    });
                }
            });
        }
    }

    function flashBox(idx) {
        var box = document.querySelector('.region-box[data-idx="' + idx + '"]');
        if (!box) return;
        [].slice.call(document.querySelectorAll('.region-box')).forEach(function (b) { b.classList.remove('active'); });
        box.classList.add('active');
        box.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    function init() {
        if (KIND === 'image') {
            var img = document.getElementById('stageImg');
            if (img) {
                if (img.complete) overlayImg();
                else img.addEventListener('load', overlayImg);
            }
        } else if (KIND === 'pdf') {
            initPdf();
        } else if (KIND === 'time') {
            initTime();
        }
        initChips();
        if (KIND === 'image') {
            setTimeout(function () {
                var stage = document.getElementById('regionStage');
                if (stage) {
                    var imgEl = stage.querySelector('img');
                    if (imgEl && imgEl.complete) overlayImg();
                }
            }, 300);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
