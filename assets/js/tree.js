(function () {
    'use strict';
    if (!window.TREE_INIT) return;
    var T = window.TREE_INIT;
    var P = {};
    (T.persons || []).forEach(function (p) { P[p.id] = p; });
    var UNIONS = T.unions || [];

    var BW = 168, BH = 90, TIE = 30, VGEN = 78, UGAP = 40, MARG = 70, PAD = 60, YUP = BH + 92, GAPM = 26;
    var view = null;
    var rootId = null;
    var svg, wrap;
    var fitBase = null;

    var originCache = {};
    var depthCache = {};

    function unionsOf(pid) {
        return UNIONS.filter(function (u) { return (u.members || []).indexOf(pid) >= 0; });
    }
    function childPersons(u) {
        return (u.children || []).map(function (c) { return P[c]; }).filter(Boolean);
    }
    function originUnionOf(pid) {
        if (originCache[pid] !== undefined) return originCache[pid];
        var u = null;
        for (var i = 0; i < UNIONS.length; i++) {
            if ((UNIONS[i].children || []).indexOf(pid) >= 0 && (UNIONS[i].members || []).length > 0) {
                u = UNIONS[i];
                break;
            }
        }
        originCache[pid] = u;
        return u;
    }
    function rowsAbove(pid) {
        if (depthCache[pid] !== undefined) return depthCache[pid];
        depthCache[pid] = 0;
        var u = originUnionOf(pid);
        if (!u) return 0;
        var best = 0;
        (u.members || []).forEach(function (m) {
            if (P[m]) best = Math.max(best, rowsAbove(m));
        });
        depthCache[pid] = best + 1;
        return depthCache[pid];
    }

    function memberRowW(u) {
        var n = (u.members || []).filter(function (m) { return P[m]; }).length;
        if (n === 0) return 0;
        return n * BW + (n - 1) * TIE;
    }
    function otherMemberOf(u, pid) {
        var ms = (u.members || []).filter(function (m) { return P[m] && m !== pid; });
        return ms.length ? ms[0] : null;
    }

    function childChainW(u) {
        var k = childPersons(u);
        if (!k.length) return 0;
        var s = 0;
        k.forEach(function (c) { s += widthPersonUnit(c.id); });
        return s + (k.length - 1) * UGAP;
    }

    function marriageBandW(pid, u) {
        var cc = childChainW(u);
        var other = otherMemberOf(u, pid);
        if (!other && !cc) return 0;
        return other ? Math.max(cc, BW) : cc;
    }

    function clusterBands(pid) {
        var us = unionsOf(pid);
        var ex = [];
        var cur = [];
        us.forEach(function (u) {
            var w = marriageBandW(pid, u);
            if (w <= 0) return;
            (isFormer(u) ? ex : cur).push({ u: u, w: w });
        });
        if (!ex.length && !cur.length) return null;
        return { ex: ex, cur: cur };
    }

    function widthPersonUnit(pid) {
        var us = unionsOf(pid);
        if (!us.length) return BW;
        if (us.length === 1) {
            return marriageBandW(pid, us[0]) <= 0 ? BW : widthUnion(us[0]);
        }
        var b = clusterBands(pid);
        if (!b) return BW;
        var total = 0;
        b.ex.forEach(function (x) { total += x.w; });
        b.cur.forEach(function (x) { total += x.w; });
        total += BW + (b.ex.length + b.cur.length) * GAPM;
        return total;
    }
    function widthUnion(u) {
        var m = memberRowW(u);
        var kids = childPersons(u);
        if (!kids.length) return Math.max(m, BW);
        var w = 0;
        kids.forEach(function (c) { w += widthPersonUnit(c.id); });
        w += (kids.length - 1) * UGAP;
        return Math.max(m, w);
    }

    var boxes = [];
    var links = [];
    var quickLayer = null;
    var qBtns = [];
    var quickTarget = null;
    var quickBox = null;
    var currentSide = null;
    var quickHideT = null;
    var movePending = false;
    var isPanning = false;
    var allowAdd = (window.TREE_INIT.canEdit === 1);

    function isFormer(u) {
        return !!(u && u.end && String(u.end).trim() !== '');
    }

    function pushMarry(u, x0, x1, y) {
        links.push({ type: 'marry', x0: x0, x1: x1, y: y, ex: isFormer(u) ? 1 : 0 });
    }

    function placePersonBox(pid, cx, top) {
        var p = P[pid];
        if (!p) {
            if (window.console) console.warn('tree.js: персональная карточка отсутствует (id=' + pid + ')');
            return null;
        }
        var ghost = !!(p.placeholder);
        boxes.push({
            id: pid, cx: cx, x: cx - BW / 2, y: top, w: BW, h: BH,
            name: ghost ? '' : (p.name || ''),
            ghost: ghost, gender: p.gender || 'u',
            birth: p.birth || '', death: p.death || '', avatar: p.avatar || ''
        });
        return { x: cx, y: top };
    }

    function placeLeaf(pid, xLeft, top) {
        var cx = xLeft + BW / 2;
        var a = placePersonBox(pid, cx, top);
        if (!a) {
            return { x: xLeft, y: top, w: BW, anchorX: cx, anchorY: top + BH / 2, marriages: 0 };
        }
        return { x: a.x, y: a.y, w: BW, anchorX: a.x, anchorY: top + BH / 2, marriages: 0 };
    }

    function placeCluster(pid, xLeft, top) {
        var b = clusterBands(pid);
        if (!b) {
            return placeLeaf(pid, xLeft, top);
        }
        var yChild = top + BH + VGEN;
        var marr = [];
        var cursor = xLeft;
        var personCx;
        b.ex.forEach(function (x) {
            var start = cursor;
            var spouse = otherMemberOf(x.u, pid);
            var spouseCx = null;
            if (spouse != null) {
                spouseCx = start + x.w / 2;
                placePersonBox(spouse, spouseCx, top);
            }
            marr.push({ u: x.u, start: start, w: x.w, spouseCx: spouseCx });
            cursor += x.w + GAPM;
        });
        personCx = cursor + BW / 2;
        placePersonBox(pid, personCx, top);
        cursor += BW;
        b.cur.forEach(function (x) {
            cursor += GAPM;
            var start = cursor;
            var spouse = otherMemberOf(x.u, pid);
            var spouseCx = null;
            if (spouse != null) {
                spouseCx = start + x.w / 2;
                placePersonBox(spouse, spouseCx, top);
            }
            marr.push({ u: x.u, start: start, w: x.w, spouseCx: spouseCx });
            cursor += x.w;
        });
        marr.forEach(function (m) {
            if (m.spouseCx != null) {
                if (m.spouseCx < personCx) {
                    pushMarry(m.u, m.spouseCx + BW / 2, personCx - BW / 2, top + BH / 2);
                } else {
                    pushMarry(m.u, personCx + BW / 2, m.spouseCx - BW / 2, top + BH / 2);
                }
            }
            var kids = childPersons(m.u);
            if (!kids.length) return;
            var units = kids.map(function (c) { return { id: c.id, w: widthPersonUnit(c.id) }; });
            var totalW = units.reduce(function (s, u2) { return s + u2.w; }, 0);
            var free = m.w - totalW;
            var gaps = units.length + 1;
            var curX = m.start + Math.max(0, free / gaps);
            var anchors = [];
            units.forEach(function (un) {
                var res = placeUnit(un.id, curX, yChild);
                anchors.push({ x: res.anchorX });
                curX += un.w + free / gaps;
            });
            var trunk = (m.spouseCx != null) ? (m.spouseCx + personCx) / 2 : m.start + m.w / 2;
            links.push({ type: 'bus', x: trunk, y0: top + BH / 2, y1: yChild - 46 });
            anchors.forEach(function (a) {
                links.push({ type: 'drop', x: a.x, y0: yChild - 46, y1: yChild - 4 });
                links.push({ type: 'hub', x: Math.min(trunk, a.x) + 3, x2: Math.max(trunk, a.x) - 3, y: yChild - 46 });
            });
        });
        return { x: xLeft, y: top, w: cursor - xLeft, anchorX: personCx, anchorY: top + BH / 2, marriages: b.ex.length + b.cur.length };
    }

    function placeUnit(pid, xLeft, top) {
        var us = unionsOf(pid);
        if (!us.length) {
            return placeLeaf(pid, xLeft, top);
        }
        if (us.length === 1) {
            var u = us[0];
            if (marriageBandW(pid, u) <= 0) {
                return placeLeaf(pid, xLeft, top);
            }
            var w1 = widthUnion(u);
            var res1 = placeMarriage(u, xLeft, top, w1, null);
            var own1 = (res1.memberCx && res1.memberCx[pid] != null) ? res1.memberCx[pid] : res1.knotX;
            return { x: xLeft, y: top, w: w1, anchorX: own1, anchorY: res1.memberTop + BH / 2, marriages: 1 };
        }
        return placeCluster(pid, xLeft, top);
    }

    function placeMarriage(u, xLeft, top, W, focus) {
        var members = (u.members || []).filter(function (m) { return P[m]; });
        if (focus != null && members.length === 2 && members.indexOf(focus) >= 0) {
            members = isFormer(u)
                ? (members[0] === focus ? [members[1], focus] : [members[0], focus])
                : (members[0] === focus ? members : [members[1], members[0]]);
        }
        var m = memberRowW(u);
        var centerX = xLeft + W / 2;
        var startX = centerX - m / 2;
        var pos = [];
        var memberCx = {};
        var cx = startX;
        members.forEach(function (mid) {
            placePersonBox(mid, cx + BW / 2, top);
            pos.push({ x: cx, mid: mid });
            memberCx[mid] = cx + BW / 2;
            cx += BW + TIE;
        });
        if (members.length >= 2) {
            pushMarry(u, pos[0].x + BW, pos[pos.length - 1].x, top + BH / 2);
        }
        var knotX = centerX;
        var kids = childPersons(u);
        if (kids.length) {
            var childTop = top + BH + VGEN;
            var units = kids.map(function (c) { return { id: c.id, w: widthPersonUnit(c.id) }; });
            var totalW = units.reduce(function (s, u2) { return s + u2.w; }, 0);
            var free = W - totalW;
            var gaps = units.length + 1;
            var cur = xLeft + Math.max(0, free / gaps);
            var childAnchors = [];
            units.forEach(function (un) {
                var res = placeUnit(un.id, cur, childTop);
                childAnchors.push({ x: res.anchorX, top: childTop, right: cur + res.w });
                cur += un.w + free / gaps;
            });
            links.push({ type: 'bus', x: knotX, y0: top + BH, y1: childTop - 46 });
            childAnchors.forEach(function (a) {
                links.push({ type: 'drop', x: a.x, y0: childTop - 46, y1: childTop - 4 });
                links.push({ type: 'hub', x: Math.min(knotX, a.x) + 3, x2: Math.max(knotX, a.x) - 3, y: childTop - 46 });
            });
        }
        return { knotX: knotX, memberTop: top, memberCx: memberCx };
    }

    function drawAncestors(pid, childCx, childTop) {
        var u = originUnionOf(pid);
        if (!u) return;
        var members = (u.members || []).filter(function (m) { return P[m]; });
        if (!members.length) return;
        var n = members.length;
        var top = childTop - YUP;
        var mRowW = n * BW + (n - 1) * TIE;
        var startX = childCx - mRowW / 2;
        var centers = [];
        var cx = startX;
        members.forEach(function (mid) {
            placePersonBox(mid, cx + BW / 2, top);
            centers.push(cx + BW / 2);
            cx += BW + TIE;
        });
        if (n >= 2) {
            pushMarry(u, startX + BW, startX + mRowW - BW, top + BH / 2);
        }
        links.push({ type: 'bus', x: childCx, y0: top + BH, y1: childTop - 4 });
        members.forEach(function (mid, i) {
            drawAncestors(mid, centers[i], top);
        });
    }

    function build() {
        boxes = [];
        links = [];
        originCache = {};
        depthCache = {};
        var origin = originUnionOf(rootId);
        if (origin) {
            var up = 0;
            (origin.members || []).forEach(function (m) {
                if (P[m]) up = Math.max(up, rowsAbove(m));
            });
            var yCouple = PAD + up * YUP;
            var W = widthUnion(origin);
            placeMarriage(origin, PAD, yCouple, W);
            var mw = memberRowW(origin);
            var startX = PAD + W / 2 - mw / 2;
            var ancMembers = (origin.members || []).filter(function (m) { return P[m]; });
            ancMembers.forEach(function (m, i) {
                var cx = startX + i * (BW + TIE) + BW / 2;
                if (rowsAbove(m) > 0) {
                    drawAncestors(m, cx, yCouple);
                }
            });
        } else {
            placeUnit(rootId, PAD, PAD);
        }
        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        boxes.forEach(function (b) {
            if (b.x < minX) minX = b.x;
            if (b.y < minY) minY = b.y;
            if (b.x + b.w > maxX) maxX = b.x + b.w;
            if (b.y + b.h > maxY) maxY = b.y + b.h;
        });
        links.forEach(function (l) {
            var xs = [l.x0 || 0, l.x || 0, l.x2 || 0];
            var ys = [l.y0 || 0, l.y1 || 0, l.y || 0];
            xs.forEach(function (x) { if (x < minX) minX = x; if (x > maxX) maxX = x; });
            ys.forEach(function (y) { if (y < minY) minY = y; if (y > maxY) maxY = y; });
        });
        if (!isFinite(minX)) { minX = 0; minY = 0; maxX = 200; maxY = 200; }
        minY -= 34;
        var midX = (minX + maxX) / 2;
        var midY = (minY + maxY) / 2;
        var cw = Math.max(100, maxX - minX);
        var ch = Math.max(100, maxY - minY);
        return { minX: minX, minY: minY, midX: midX, midY: midY, cw: cw, ch: ch, w: cw, h: ch };
    }

    function renderBoxes() {
        var NS = 'http://www.w3.org/2000/svg';
        function el(tag, attrs) {
            var n = document.createElementNS(NS, tag);
            for (var k in attrs) n.setAttribute(k, attrs[k]);
            return n;
        }
        [].slice.call(svg.querySelectorAll('g.slot')).forEach(function (g) { g.remove(); });
        var slot = el('g', { class: 'slot' });
        svg.appendChild(slot);

        var defs = svg.querySelector('defs');
        if (!defs) {
            defs = el('defs', {});
            svg.appendChild(defs);
        }
        if (!defs.querySelector('#avc')) {
            var cp = el('clipPath', { id: 'avc' });
            cp.appendChild(el('circle', { cx: 84, cy: 0, r: 34 }));
            defs.appendChild(cp);
        }

        links.forEach(function (l) {
            if (l.type === 'marry') {
                var mLine = el('line', { x1: l.x0, y1: l.y, x2: l.x1, y2: l.y, class: l.ex ? 'tmarry tmarry-ex' : 'tmarry' });
                slot.appendChild(mLine);
            } else if (l.type === 'bus' || l.type === 'drop') {
                slot.appendChild(el('line', { x1: l.x, y1: l.y0, x2: l.x, y2: l.y1, class: 'tline' }));
            } else if (l.type === 'hub') {
                slot.appendChild(el('line', { x1: l.x, y1: l.y, x2: l.x2, y2: l.y, class: 'tline' }));
            }
        });

        boxes.forEach(function (b) {
            var cls = 'tbox' + (b.ghost ? ' t-hidden' : '') + (b.id === rootId ? ' tsel' : '');
            var g = el('g', { class: cls, transform: 'translate(' + b.x + ',' + b.y + ')' });
            g.style.cursor = 'pointer';
            g.setAttribute('title', 'Клик — показать родню относительно персоны');
            g.addEventListener('click', function (ev) {
                ev.preventDefault();
                if (b.id !== rootId) {
                    render(b.id, true);
                }
            });
            if (allowAdd && !b.ghost) {
                g.addEventListener('mouseenter', function () { showQuick(g, b); });
                g.addEventListener('mouseleave', function () { scheduleQuickHide(); });
            }
            var r = el('rect', { width: b.w, height: b.h, rx: 16 });
            r.setAttribute('class', 'card-body-r');
            g.appendChild(r);

            var midX = b.w / 2;
            var av = el('image', {
                href: avatarUrl(b),
                x: midX - 34, y: -34, width: 68, height: 68,
                'clip-path': 'url(#avc)',
                preserveAspectRatio: 'xMidYMid slice'
            });
            g.appendChild(av);
            var ring = el('circle', { cx: midX, cy: 0, r: 35 });
            ring.setAttribute('class', 't-avring');
            g.appendChild(ring);

            if (b.ghost) {
                var nm = el('text', { x: midX, y: 56, 'text-anchor': 'middle', class: 't-sub' });
                nm.textContent = 'Персона';
                g.appendChild(nm);
            } else {
                var nm2 = el('text', { x: midX, y: 54, 'text-anchor': 'middle', class: 't-name' });
                nm2.textContent = trunc(b.name, 20);
                nm2.style.pointerEvents = 'auto';
                nm2.style.cursor = 'pointer';
                nm2.setAttribute('title', 'Открыть карточку персоны');
                nm2.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    ev.preventDefault();
                    window.location.href = window.TREE_BASE + 'index.php?p=person&id=' + b.id;
                });
                g.appendChild(nm2);
                if (b.birth || b.death) {
                    var years = (b.birth || '…') + (b.death ? ' — ' + b.death : '');
                    var yr = el('text', { x: midX, y: 74, 'text-anchor': 'middle', class: 't-sub' });
                    yr.textContent = trunc(years, 24);
                    g.appendChild(yr);
                }
            }
            slot.appendChild(g);
        });
    }

    function avatarUrl(b) {
        if (b.avatar) {
            return window.TREE_BASE + b.avatar;
        }
        var g = b.gender === 'm' ? 'm' : b.gender === 'f' ? 'f' : 'u';
        return window.TREE_BASE + 'assets/img/avatar-' + g + '.svg';
    }

    function trunc(s, n) {
        if (!s) return '';
        return s.length > n ? s.slice(0, n - 1) + '…' : s;
    }

    function fitView(extent) {
        var W = wrap.clientWidth;
        var H = wrap.clientHeight || 640;
        var sx = (W - 40) / extent.cw;
        var sy = (H - 40) / extent.ch;
        var s = Math.min(1, Math.min(sx, sy));
        var w = extent.cw * s;
        var h = extent.ch * s;
        setView(extent.midX - w / 2, extent.midY - h / 2, w, h);
        fitBase = w;
    }

    function setView(x, y, w, h) {
        if (w < 60) { var ratio = h / w; w = 60; h = 60 * ratio; }
        view = { x: x, y: y, w: w, h: h };
        svg.setAttribute('viewBox', x + ' ' + y + ' ' + w + ' ' + h);
        var lvl = document.getElementById('zoomLevel');
        if (lvl) {
            lvl.textContent = fitBase ? Math.round(100 * fitBase / view.w) + '%' : '100%';
        }
        if (quickTarget) {
            requestAnimationFrame(function () { layoutQuick(); });
        }
    }

    function ensureQuickLayer() {
        if (quickLayer) return;
        quickLayer = document.createElement('div');
        quickLayer.className = 'tree-quickadd';
        wrap.appendChild(quickLayer);
        var meta = [
            { k: 'parent', t: 'Добавить родителя (предка)' },
            { k: 'child', t: 'Добавить ребёнка (потомка)' },
            { k: 'spouseL', t: 'Добавить супруга(у)' },
            { k: 'spouseR', t: 'Добавить супруга(у)' }
        ];
        meta.forEach(function (m) {
            var a = document.createElement('a');
            a.className = 'qa-btn qa-' + m.k;
            a.title = m.t;
            a.setAttribute('aria-label', m.t);
            a.innerHTML = '<span>+</span>';
            a.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (quickBox && window.TreeQuickAdd) {
                    window.TreeQuickAdd(m.k === 'parent' ? 'parent' : m.k === 'child' ? 'child' : 'spouse', quickBox.id);
                }
            });
            quickLayer.appendChild(a);
            qBtns.push({ k: m.k, el: a });
        });
        quickLayer.addEventListener('mouseenter', function () {
            if (quickHideT) { clearTimeout(quickHideT); quickHideT = null; }
        });
        quickLayer.addEventListener('mouseleave', function () { scheduleQuickHide(); });
    }

    function quickSideOf(e) {
        if (!quickTarget) return null;
        var r = quickTarget.getBoundingClientRect();
        var relX = (e.clientX - r.left) / r.width;
        var relY = (e.clientY - r.top) / r.height;
        var dTop = relY, dBot = 1 - relY, dL = relX, dR = 1 - relX;
        var min = Math.min(dTop, dBot, dL, dR);
        if (min === dTop) return 'parent';
        if (min === dBot) return 'child';
        if (min === dL) return 'spouseL';
        return 'spouseR';
    }

    function layoutQuick() {
        if (!quickTarget) return;
        var wr = wrap.getBoundingClientRect();
        var r = quickTarget.getBoundingClientRect();
        var size = 28;
        var midX = r.left + r.width / 2 - wr.left;
        var midY = r.top + r.height / 2 - wr.top;
        var pos = {
            parent: { x: midX - size / 2, y: r.top - wr.top - size - 3 },
            child: { x: midX - size / 2, y: r.bottom - wr.top + 5 },
            spouseL: { x: r.left - wr.left - size - 3, y: midY - size / 2 },
            spouseR: { x: r.right - wr.left + 5, y: midY - size / 2 }
        };
        qBtns.forEach(function (b) {
            var p = pos[b.k];
            b.el.style.left = p.x + 'px';
            b.el.style.top = p.y + 'px';
        });
    }

    function setSide(side) {
        if (side === currentSide) return;
        currentSide = side;
        qBtns.forEach(function (b) {
            if (b.k === side) {
                b.el.classList.add('on');
            } else {
                b.el.classList.remove('on');
            }
        });
    }

    function showQuick(g, b) {
        ensureQuickLayer();
        if (quickHideT) { clearTimeout(quickHideT); quickHideT = null; }
        quickTarget = g;
        quickBox = b;
        currentSide = null;
        layoutQuick();
        quickLayer.classList.add('active');
        setSide(null);
        qBtns.forEach(function (qb) { qb.el.classList.remove('on'); });
    }

    function hideQuick() {
        if (quickLayer) {
            quickLayer.classList.remove('active');
            qBtns.forEach(function (qb) { qb.el.classList.remove('on'); });
        }
        quickTarget = null;
        quickBox = null;
        currentSide = null;
    }

    function scheduleQuickHide() {
        if (quickHideT) clearTimeout(quickHideT);
        quickHideT = setTimeout(hideQuick, 260);
    }

    function handleMoveQuick(e) {
        if (!quickTarget || !quickBox) return;
        if (movePending) return;
        movePending = true;
        requestAnimationFrame(function () {
            movePending = false;
            var side = quickSideOf(e);
            if (!quickLayer) return;
            quickLayer.classList.add('active');
            setSide(side);
        });
    }

    function zoomBy(factor, cxRatio, cyRatio) {
        var cx = view.x + (cxRatio || 0.5) * view.w;
        var cy = view.y + (cyRatio || 0.5) * view.h;
        var nw = Math.max(60, Math.min(60000, view.w * factor));
        var nh = nw * (view.h / view.w);
        setView(cx - (cxRatio || 0.5) * nw, cy - (cyRatio || 0.5) * nh, nw, nh);
    }

    function rebase(base) {
        if (window.TREE_BASE) return;
        var parts = (window.location.pathname || '').split('/');
        parts.pop();
        window.TREE_BASE = base || parts.join('/') + '/';
    }

    function setupControls() {
        document.getElementById('zoomIn').addEventListener('click', function () { zoomBy(0.8); });
        document.getElementById('zoomOut').addEventListener('click', function () { zoomBy(1.25); });
        document.getElementById('zoomFit').addEventListener('click', function () { fit(); });
        svg.addEventListener('wheel', function (e) {
            e.preventDefault();
            var r = svg.getBoundingClientRect();
            zoomBy(e.deltaY > 0 ? 1.15 : 0.87, (e.clientX - r.left) / r.width, (e.clientY - r.top) / r.height);
        }, { passive: false });
        svg.addEventListener('mousemove', function (e) {
            if (isPanning) return;
            handleMoveQuick(e);
        });
        var drag = null;
        svg.addEventListener('mousedown', function (e) {
            if (e.target.closest && e.target.closest('.tbox')) return;
            isPanning = true;
            drag = { x: e.clientX, y: e.clientY, vx: view.x, vy: view.y };
            e.preventDefault();
        });
        window.addEventListener('mousemove', function (e) {
            if (!drag) return;
            var dx = (e.clientX - drag.x) * (view.w / svg.getBoundingClientRect().width);
            var dy = (e.clientY - drag.y) * (view.h / svg.getBoundingClientRect().height);
            setView(drag.vx - dx, drag.vy - dy, view.w, view.h);
        });
        window.addEventListener('mouseup', function () { drag = null; isPanning = false; });
    }

    var extent = null;
    function fit() { if (extent) fitView(extent); }

    function centerOnRoot() {
        var b = null;
        for (var i = 0; i < boxes.length; i++) {
            if (boxes[i].id === rootId) { b = boxes[i]; break; }
        }
        if (!b) { fit(); return; }
        var W = wrap.clientWidth;
        var H = wrap.clientHeight || 640;
        var aspect = (H > 0 && W > 0) ? W / H : 1.6;
        var w = Math.min(extent.cw, 1250);
        var h = w / aspect;
        if (h < 60) { h = 60; w = h * aspect; }
        var cx = b.x + b.w / 2;
        var cy = b.y + b.h / 2;
        setView(cx - w / 2, cy - h / 2, w, h);
        fitBase = w;
    }

    function render(pid, center) {
        rootId = pid;
        hideQuick();
        var sel = document.getElementById('rootSelect');
        if (sel && sel.value !== String(pid)) sel.value = String(pid);
        extent = build();
        renderBoxes();
        if (center) {
            centerOnRoot();
        } else {
            fitView(extent);
        }
    }

    function setFs(on) {
        wrap.classList.toggle('fullscreen', on);
        document.body.classList.toggle('tree-fs-open', on);
        var btn = document.getElementById('treeFullscreen');
        if (btn) {
            btn.classList.toggle('active', on);
            var ic = btn.querySelector('i');
            if (ic) ic.className = on ? 'bi bi-fullscreen-exit' : 'bi bi-fullscreen';
        }
        requestAnimationFrame(function () {
            setTimeout(function () { if (extent) fitView(extent); }, 40);
        });
    }

    function setupFullscreen() {
        var fsBtn = document.getElementById('treeFullscreen');
        if (fsBtn) {
            fsBtn.addEventListener('click', function () { setFs(!wrap.classList.contains('fullscreen')); });
        }
        var fsExit = document.getElementById('fsExit');
        if (fsExit) {
            fsExit.addEventListener('click', function () { setFs(false); });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && wrap.classList.contains('fullscreen')) {
                setFs(false);
            }
        });
        var t = null;
        window.addEventListener('resize', function () {
            clearTimeout(t);
            t = setTimeout(function () { if (extent) fitView(extent); }, 180);
        });
    }

    function init() {
        svg = document.getElementById('treeSvg');
        wrap = document.getElementById('treeWrap');
        if (!svg || !wrap) return;
        rebase();
        var firstId = null;
        (T.persons || []).forEach(function (p) {
            if (firstId === null || p.id < firstId) firstId = p.id;
        });
        var focus = parseInt(window.TREE_FOCUS, 10) || 0;
        var pid = (focus && P[focus]) ? focus : firstId;
        if (pid) render(pid, true);
        setupControls();
        setupFullscreen();
        window.addEventListener('load', function () { if (extent) fitView(extent); });
    }

    function repopulateSelect() {
        var sel = document.getElementById('rootSelect');
        if (!sel) return;
        sel.innerHTML = '';
        (T.persons || []).forEach(function (p) {
            var o = document.createElement('option');
            o.value = p.id;
            o.textContent = p.placeholder ? ('Персона (id ' + p.id + ')') : p.name;
            sel.appendChild(o);
        });
        sel.value = String(rootId);
    }

    window.TreeReload = function (data) {
        if (data && data.ok !== false && data.data) {
            T = data.data;
        }
        window.TREE_INIT = T;
        UNIONS = T.unions || [];
        P = {};
        (T.persons || []).forEach(function (p) { P[p.id] = p; });
        repopulateSelect();
        render(rootId, true);
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
