(function () {
    'use strict';
    if (!window.MEDIA_EDIT) return;
    var D = window.MEDIA_EDIT;
    var KIND = D.kind;
    var BINDS = D.bindings || [];
    var ACTIVE = D.active || null;
    var pdfDoc = null;
    var pdfPage = 1;
    var drawing = false;
    var drawStart = null;
    var canGeom = (KIND === 'image' || KIND === 'pdf') && !!ACTIVE;
    var canTime = (KIND === 'video' || KIND === 'audio') && !!ACTIVE;
    var colorFor = function (i) { return 'hsl(' + ((i * 47) % 360) + ',55%,50%)'; };

    function geomStage() {
        return document.getElementById(KIND === 'pdf' ? 'pdfStage' : 'regionStage');
    }

    function overlayBoxes() {
        var stage = geomStage();
        if (!stage) return;
        [].slice.call(stage.querySelectorAll('.region-box')).forEach(function (el) { el.remove(); });
        BINDS.forEach(function (b, i) {
            var onThis = (KIND === 'image' && b.btype === 'region') || (KIND === 'pdf' && b.btype === 'page' && b.page === pdfPage - 1);
            if (!onThis) return;
            if (!(b.w > 0 && b.h > 0)) return;
            var div = document.createElement('div');
            div.className = 'region-box';
            if (ACTIVE && ACTIVE.id === b.id) div.classList.add('editing-box');
            div.style.left = b.x + '%';
            div.style.top = b.y + '%';
            div.style.width = b.w + '%';
            div.style.height = b.h + '%';
            div.style.borderColor = colorFor(i);
            div.title = b.person_name + (ACTIVE && ACTIVE.id === b.id ? ' (выделение)' : '');
            stage.appendChild(div);
        });
    }

    function setGeom(x, y, w, h) {
        var form = document.getElementById('bindSaveForm');
        if (!form) return;
        var px = Math.max(0, Math.min(100, x));
        var py = Math.max(0, Math.min(100, y));
        var pw = Math.max(0, Math.min(100 - px, w));
        var ph = Math.max(0, Math.min(100 - py, h));
        form.querySelector('input[name=x]').value = px.toFixed(3);
        form.querySelector('input[name=y]').value = py.toFixed(3);
        form.querySelector('input[name=w]').value = pw.toFixed(3);
        form.querySelector('input[name=h]').value = ph.toFixed(3);
        if (KIND === 'pdf') {
            form.querySelector('input[name=page]').value = pdfPage - 1;
        }
        var box = document.getElementById('drawBox');
        if (box) {
            box.style.display = pw > 0 && ph > 0 ? 'block' : 'none';
            box.style.left = px + '%';
            box.style.top = py + '%';
            box.style.width = pw + '%';
            box.style.height = ph + '%';
        }
    }

    function relCoords(clientX, clientY) {
        var el = KIND === 'pdf' ? document.getElementById('pdfCanvas') : document.getElementById('stageImg');
        if (!el) return null;
        var r = el.getBoundingClientRect();
        return { x: ((clientX - r.left) / r.width) * 100, y: ((clientY - r.top) / r.height) * 100 };
    }

    function bindGeomInput() {
        var stage = geomStage();
        if (!stage) return;
        stage.addEventListener('mousedown', function (e) {
            if (!canGeom || e.target.closest && e.target.closest('.region-box')) return;
            e.preventDefault();
            var p = relCoords(e.clientX, e.clientY);
            if (!p) return;
            drawing = true;
            drawStart = p;
        });
        window.addEventListener('mousemove', function (e) {
            if (!drawing) return;
            var p = relCoords(e.clientX, e.clientY);
            if (!p) return;
            var x = Math.min(drawStart.x, p.x);
            var y = Math.min(drawStart.y, p.y);
            setGeom(x, y, Math.abs(p.x - drawStart.x), Math.abs(p.y - drawStart.y));
        });
        window.addEventListener('mouseup', function () {
            if (!drawing) return;
            drawing = false;
            var box = document.getElementById('drawBox');
            if (box) box.style.display = 'none';
        });
    }

    function initImage() {
        var img = document.getElementById('stageImg');
        if (!img) return;
        var redraw = function () { overlayBoxes(); };
        if (img.complete) redraw();
        else img.addEventListener('load', redraw);
        if (canGeom) bindGeomInput();
    }

    function initPdf() {
        var state = document.getElementById('pdfLoadState');
        if (!window.pdfjsLib) {
            if (state) state.textContent = 'библиотека PDF не загрузилась';
            return;
        }
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
        window.pdfjsLib.getDocument({ url: D.preview }).promise.then(function (doc) {
            pdfDoc = doc;
            renderPdf();
        }).catch(function () {
            if (state) state.textContent = 'не удалось открыть PDF';
        });
        document.getElementById('pdfNext').addEventListener('click', function () { if (pdfDoc && pdfPage < pdfDoc.numPages) { pdfPage++; renderPdf(); } });
        document.getElementById('pdfPrev').addEventListener('click', function () { if (pdfPage > 1) { pdfPage--; renderPdf(); } });
        document.getElementById('pdfPageInput').addEventListener('change', function () {
            var v = parseInt(this.value, 10);
            if (!isNaN(v) && pdfDoc && v >= 1 && v <= pdfDoc.numPages) { pdfPage = v; renderPdf(); }
        });
    }

    function renderPdf() {
        if (!pdfDoc) return;
        var canvas = document.getElementById('pdfCanvas');
        var stage = document.getElementById('pdfStage');
        var state = document.getElementById('pdfLoadState');
        pdfDoc.getPage(pdfPage).then(function (page) {
            var base = page.getViewport({ scale: 1 });
            var target = Math.min(1000, (stage.clientWidth || 900) - 8);
            var scale = target / base.width;
            var vp = page.getViewport({ scale: scale });
            canvas.width = vp.width;
            canvas.height = vp.height;
            return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise.then(function () {
                stage.style.display = 'block';
                document.getElementById('pdfPageNo').textContent = pdfPage;
                document.getElementById('pdfPageInput').value = pdfPage;
                if (state) state.textContent = 'страница ' + pdfPage + ' из ' + pdfDoc.numPages;
                overlayBoxes();
            });
        }).catch(function () {
            if (state) state.textContent = 'ошибка чтения PDF';
        });
    }

    function initTime() {
        if (!canTime) return;
        var player = document.getElementById('mediaPlayer');
        var startBtn = document.getElementById('setStart');
        var endBtn = document.getElementById('setEnd');
        var form = document.getElementById('bindSaveForm');
        if (!form || !startBtn || !endBtn) return;
        var ts = document.getElementById('tstart');
        var te = document.getElementById('tend');
        var preview = document.getElementById('timePreview');
        function sync() {
            form.querySelector('input[name=tstart]').value = parseFloat(ts.value) || 0;
            form.querySelector('input[name=tend]').value = parseFloat(te.value) || 0;
            if (preview) preview.textContent = fmt(parseFloat(ts.value) || 0) + ' – ' + fmt(parseFloat(te.value) || 0);
        }
        ts.addEventListener('input', sync);
        te.addEventListener('input', sync);
        startBtn.addEventListener('click', function () {
            if (player && isFinite(player.currentTime)) { ts.value = player.currentTime.toFixed(2); sync(); }
        });
        endBtn.addEventListener('click', function () {
            if (player && isFinite(player.currentTime)) { te.value = player.currentTime.toFixed(2); sync(); }
        });
        sync();
    }

    function fmt(sec) {
        sec = Math.max(0, Math.round(sec));
        var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60), s = sec % 60;
        return (h ? h + ':' + pad(m) + ':' : m + ':') + pad(s);
    }
    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function init() {
        if (KIND === 'image') initImage();
        else if (KIND === 'pdf') initPdf();
        else if (canTime) initTime();
        if (canGeom && KIND === 'pdf') bindGeomInput();
        var saveBtn = document.getElementById('saveBindingBtn');
        if (saveBtn && (canGeom || canTime)) {
            saveBtn.addEventListener('click', function (e) {
                if (!canTime) {
                    var form = document.getElementById('bindSaveForm');
                    var ok = parseFloat(form.querySelector('input[name=w]').value) > 0 && parseFloat(form.querySelector('input[name=h]').value) > 0;
                    if (!ok) {
                        e.preventDefault();
                        alert('Сначала растяните рамку на изображении/странице.');
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
