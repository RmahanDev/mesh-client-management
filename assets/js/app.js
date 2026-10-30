/* =====================================================================
   مدیریت مش - اسکریپت اصلی (بدون jQuery)
   ===================================================================== */
(function () {
    'use strict';

    const MM = window.MM = window.MM || {};
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    MM.csrf = CSRF;

    /* ---------------- ارقام ---------------- */
    const FA = '۰۱۲۳۴۵۶۷۸۹';
    MM.fa = s => String(s).replace(/\d/g, d => FA[d]);
    MM.en = s => String(s).replace(/[۰-۹]/g, d => FA.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
    MM.money = n => MM.fa(Math.round(Number(n) || 0).toLocaleString('en-US'));
    MM.parseMoney = s => parseInt(MM.en(String(s || '')).replace(/[^\d-]/g, ''), 10) || 0;

    /* ---------------- تقویم شمسی ---------------- */
    const J = MM.jalali = {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        toJalali(gy, gm, gd) {
            const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            const gy2 = gm > 2 ? gy + 1 : gy;
            let days = 355666 + 365 * gy + ~~((gy2 + 3) / 4) - ~~((gy2 + 99) / 100) + ~~((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
            let jy = -1595 + 33 * ~~(days / 12053);
            days %= 12053;
            jy += 4 * ~~(days / 1461);
            days %= 1461;
            if (days > 365) { jy += ~~((days - 1) / 365); days = (days - 1) % 365; }
            const jm = days < 186 ? 1 + ~~(days / 31) : 7 + ~~((days - 186) / 30);
            const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
            return [jy, jm, jd];
        },
        toGregorian(jy, jm, jd) {
            jy += 1595;
            let days = -355668 + 365 * jy + ~~(jy / 33) * 8 + ~~(((jy % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
            let gy = 400 * ~~(days / 146097);
            days %= 146097;
            if (days > 36524) { gy += 100 * ~~(--days / 36524); days %= 36524; if (days >= 365) days++; }
            gy += 4 * ~~(days / 1461);
            days %= 1461;
            if (days > 365) { gy += ~~((days - 1) / 365); days = (days - 1) % 365; }
            let gd = days + 1;
            const sal = [0, 31, (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            let gm = 0;
            for (gm = 0; gm < 13 && gd > sal[gm]; gm++) gd -= sal[gm];
            return [gy, gm, gd];
        },
        isLeap(jy) { const g = J.toGregorian(jy, 12, 30); const b = J.toJalali(g[0], g[1], g[2]); return b[1] === 12 && b[2] === 30; },
        monthLength(jy, jm) { return jm <= 6 ? 31 : jm <= 11 ? 30 : (J.isLeap(jy) ? 30 : 29); },
        today() { const d = new Date(); return J.toJalali(d.getFullYear(), d.getMonth() + 1, d.getDate()); },
        pad: n => String(n).padStart(2, '0'),
        format(a) { return a[0] + '/' + J.pad(a[1]) + '/' + J.pad(a[2]); },
        parse(s) {
            const m = MM.en(s || '').trim().match(/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/);
            if (!m) return null;
            const a = [+m[1], +m[2], +m[3]];
            if (a[1] < 1 || a[1] > 12 || a[2] < 1 || a[2] > J.monthLength(a[0], a[1])) return null;
            return a;
        },
        addMonths(a, n) {
            const total = a[0] * 12 + (a[1] - 1) + n;
            const jy = Math.floor(total / 12), jm = total % 12 + 1;
            return [jy, jm, Math.min(a[2], J.monthLength(jy, jm))];
        },
        weekday(jy, jm, jd) { // 0 = شنبه
            const g = J.toGregorian(jy, jm, jd);
            return (new Date(g[0], g[1] - 1, g[2]).getDay() + 1) % 7;
        }
    };

    /* ---------------- Datepicker ---------------- */
    let dp = null, dpInput = null, dpView = null;
    function closeDp() { dp?.remove(); dp = null; dpInput = null; }
    function renderDp() {
        const [vy, vm] = dpView;
        const sel = J.parse(dpInput.value);
        const t = J.today();
        const first = J.weekday(vy, vm, 1);
        const len = J.monthLength(vy, vm);
        let yearOpts = '';
        for (let y = t[0] - 80; y <= t[0] + 10; y++) yearOpts += `<option value="${y}" ${y === vy ? 'selected' : ''}>${MM.fa(y)}</option>`;
        let monthOpts = J.months.map((n, i) => `<option value="${i + 1}" ${i + 1 === vm ? 'selected' : ''}>${n}</option>`).join('');
        let html = `<div class="jdp-head">
            <button type="button" class="jdp-nav" data-nav="-1"><i class="bi bi-chevron-right"></i></button>
            <div class="jdp-title"><select data-m>${monthOpts}</select><select data-y>${yearOpts}</select></div>
            <button type="button" class="jdp-nav" data-nav="1"><i class="bi bi-chevron-left"></i></button></div>
            <div class="jdp-grid">${['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map(w => `<div class="wd">${w}</div>`).join('')}`;
        for (let i = 0; i < first; i++) html += '<div></div>';
        for (let d = 1; d <= len; d++) {
            const cls = [];
            if (t[0] === vy && t[1] === vm && t[2] === d) cls.push('today');
            if (sel && sel[0] === vy && sel[1] === vm && sel[2] === d) cls.push('sel');
            if ((first + d - 1) % 7 === 6) cls.push('fri');
            html += `<button type="button" class="${cls.join(' ')}" data-d="${d}">${MM.fa(d)}</button>`;
        }
        html += `</div><div class="jdp-foot"><button type="button" data-today>امروز</button><button type="button" data-clear>پاک کردن</button></div>`;
        dp.innerHTML = html;
    }
    function positionDp() {
        const r = dpInput.getBoundingClientRect();
        let top = r.bottom + 6, left = r.right - 290;
        if (left < 8) left = 8;
        if (top + 340 > window.innerHeight) top = Math.max(8, r.top - 346);
        dp.style.top = top + 'px'; dp.style.left = left + 'px';
    }
    function openDp(input) {
        if (dpInput === input) return;
        closeDp();
        dpInput = input;
        const v = J.parse(input.value) || J.today();
        dpView = [v[0], v[1]];
        dp = document.createElement('div');
        dp.className = 'jdp';
        (input.closest('.modal') || document.body).appendChild(dp);
        renderDp(); positionDp();
        dp.addEventListener('mousedown', e => { if (!e.target.closest('select')) e.preventDefault(); });
        dp.addEventListener('click', e => {
            const b = e.target.closest('button');
            if (!b) return;
            if (b.dataset.nav) {
                let m = dpView[1] + (+b.dataset.nav), y = dpView[0];
                if (m < 1) { m = 12; y--; } if (m > 12) { m = 1; y++; }
                dpView = [y, m]; renderDp();
            } else if (b.dataset.d) {
                setDate(J.format([dpView[0], dpView[1], +b.dataset.d]));
            } else if (b.hasAttribute('data-today')) {
                setDate(J.format(J.today()));
            } else if (b.hasAttribute('data-clear')) {
                setDate('');
            }
        });
        dp.addEventListener('change', e => {
            if (e.target.matches('[data-m]')) dpView[1] = +e.target.value;
            if (e.target.matches('[data-y]')) dpView[0] = +e.target.value;
            renderDp();
        });
    }
    function setDate(v) {
        const inp = dpInput;
        inp.value = v;
        inp.dispatchEvent(new Event('change', { bubbles: true }));
        closeDp();
    }
    document.addEventListener('focusin', e => { if (e.target.matches('input.jdate')) openDp(e.target); });
    document.addEventListener('click', e => {
        if (e.target.matches('input.jdate')) { openDp(e.target); return; }
        if (dp && !e.composedPath().includes(dp)) closeDp();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && dp) { e.stopPropagation(); closeDp(); } }, true);
    document.addEventListener('scroll', e => { if (dp && !e.target.closest?.('.jdp')) positionDp(); }, true);
    window.addEventListener('resize', () => dp && positionDp());

    /* ---------------- Toast ---------------- */
    MM.toast = function (msg, type = 'success') {
        let box = document.querySelector('.mm-toasts');
        if (!box) { box = document.createElement('div'); box.className = 'mm-toasts'; document.body.appendChild(box); }
        const icons = { success: 'bi-check-circle-fill text-success', error: 'bi-x-octagon-fill text-danger', warning: 'bi-exclamation-triangle-fill text-warning' };
        const el = document.createElement('div');
        el.className = 'mm-toast ' + type;
        el.innerHTML = `<i class="bi ${icons[type] || icons.success} fs-5"></i><div></div>`;
        el.lastChild.textContent = msg;
        box.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = 0; setTimeout(() => el.remove(), 300); }, 4200);
    };

    /* ---------------- قالب‌بندی مبلغ ---------------- */
    function formatMoneyInput(inp) {
        const n = MM.parseMoney(inp.value);
        inp.value = inp.value.trim() === '' ? '' : n.toLocaleString('en-US');
    }
    document.addEventListener('input', e => {
        if (e.target.matches('input.money')) {
            const el = e.target, fromEnd = el.value.length - el.selectionStart;
            formatMoneyInput(el);
            const pos = Math.max(0, el.value.length - fromEnd);
            el.setSelectionRange(pos, pos);
        }
        if (e.target.matches('input.digits')) e.target.value = MM.en(e.target.value);
    });
    MM.formatMoneyInputs = root => (root || document).querySelectorAll('input.money').forEach(i => { if (i.value) formatMoneyInput(i); });

    /* ---------------- ارسال فرم‌ها با AJAX ---------------- */
    MM.post = async function (url, data) {
        const fd = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData) && data) Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        if (!fd.has('_csrf')) fd.append('_csrf', CSRF);
        const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF } });
        let json;
        try { json = await res.json(); } catch (e) { json = { ok: false, message: 'خطای سرور (' + res.status + ')' }; }
        if (json.redirect && res.status === 401) location.href = json.redirect;
        return json;
    };

    function handleResult(json, form) {
        if (json.ok) {
            MM.toast(json.message || 'انجام شد');
            const modal = form?.closest('.modal');
            if (modal) bootstrap.Modal.getInstance(modal)?.hide();
            if (json.redirect) setTimeout(() => location.href = json.redirect, 450);
            else if (!form || form.dataset.reload !== 'false') setTimeout(() => location.reload(), 450);
        } else {
            MM.toast(json.message || 'خطا', 'error');
            const alert = form?.querySelector('.form-alert');
            if (alert) { alert.textContent = json.message; alert.classList.remove('d-none'); }
            if (json.errors && form) {
                Object.entries(json.errors).forEach(([name, msg]) => {
                    const f = form.querySelector(`[name="${name}"]`);
                    if (f) { f.classList.add('is-invalid'); f.title = msg; }
                });
            }
        }
    }

    document.addEventListener('submit', async e => {
        const form = e.target;
        if (!form.matches('form.ajax-form')) return;
        e.preventDefault();
        form.querySelectorAll('.is-invalid').forEach(f => f.classList.remove('is-invalid'));
        form.querySelector('.form-alert')?.classList.add('d-none');
        if (typeof form.onBeforeSubmit === 'function' && form.onBeforeSubmit() === false) return;
        const btn = form.querySelector('[type="submit"]') || document.querySelector(`[type="submit"][form="${form.id}"]`);
        const old = btn?.innerHTML;
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> در حال ذخیره...'; }
        try {
            const json = await MM.post(form.action, new FormData(form));
            handleResult(json, form);
        } catch (err) {
            MM.toast('ارتباط با سرور برقرار نشد', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = old; }
        }
    });

    /* ---------------- پر کردن مودال‌ها برای ویرایش ---------------- */
    MM.fillForm = function (form, data) {
        Object.entries(data || {}).forEach(([key, val]) => {
            const fields = form.querySelectorAll(`[name="${key}"], [name="${key}[]"]`);
            fields.forEach(f => {
                if (f.type === 'checkbox') {
                    f.checked = Array.isArray(val) ? val.map(String).includes(f.value) : (String(val) === f.value || val === true || val === 1 || val === '1');
                } else if (f.type === 'radio') {
                    f.checked = String(val) === f.value;
                } else if (f.tagName === 'SELECT' && f.multiple) {
                    const vals = (Array.isArray(val) ? val : [val]).map(String);
                    [...f.options].forEach(o => o.selected = vals.includes(o.value));
                } else if (f.type !== 'file') {
                    f.value = val ?? '';
                }
                f.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
        MM.formatMoneyInputs(form);
    };

    document.addEventListener('show.bs.modal', e => {
        const modal = e.target, trigger = e.relatedTarget;
        const form = modal.querySelector('form');
        if (!trigger || !form) return;
        if (!trigger.hasAttribute('data-keep')) {
            form.reset();
            form.querySelectorAll('input[type="hidden"]:not([name="_csrf"]):not([data-fixed])').forEach(i => i.value = '');
            form.querySelectorAll('.is-invalid').forEach(f => f.classList.remove('is-invalid'));
            form.querySelector('.form-alert')?.classList.add('d-none');
            form.querySelectorAll('.dropzone .files').forEach(f => f.textContent = '');
            form.querySelectorAll('[data-reset-html]').forEach(el => el.innerHTML = '');
        }
        if (trigger.dataset.title) {
            const t = modal.querySelector('.modal-title span');
            if (t) t.textContent = trigger.dataset.title;
        }
        if (trigger.dataset.action) form.action = trigger.dataset.action;
        let data = {};
        if (trigger.dataset.fill) { try { data = JSON.parse(trigger.dataset.fill); } catch (err) { } }
        form.dispatchEvent(new CustomEvent('mm:fill', { detail: data }));
        MM.fillForm(form, data);
        form.dispatchEvent(new CustomEvent('mm:filled', { detail: data }));
    });

    /* ---------------- تایید حذف / عملیات ---------------- */
    let confirmModal;
    MM.confirm = function (text, onOk, opts = {}) {
        const el = document.getElementById('confirmModal');
        confirmModal = confirmModal || new bootstrap.Modal(el);
        el.querySelector('.confirm-text').textContent = text || 'آیا مطمئن هستید؟';
        const btn = el.querySelector('.confirm-ok');
        btn.textContent = opts.okText || 'بله، حذف شود';
        btn.className = 'btn confirm-ok ' + (opts.okClass || 'btn-danger');
        btn.onclick = async () => { btn.disabled = true; await onOk(); btn.disabled = false; confirmModal.hide(); };
        confirmModal.show();
    };
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-confirm-url]');
        if (!b) return;
        e.preventDefault();
        MM.confirm(b.dataset.confirm || 'این مورد حذف شود؟', async () => {
            const extra = b.dataset.post ? JSON.parse(b.dataset.post) : {};
            const json = await MM.post(b.dataset.confirmUrl, extra);
            handleResult(json, null);
        }, { okText: b.dataset.okText, okClass: b.dataset.okClass });
    });

    /* ---------------- پیش‌نمایش پیوست ---------------- */
    document.addEventListener('click', e => {
        const a = e.target.closest('.js-preview');
        if (!a) return;
        e.preventDefault();
        const el = document.getElementById('previewModal');
        const body = el.querySelector('.preview-body');
        const mime = a.dataset.mime || '';
        el.querySelector('.preview-name').textContent = a.dataset.name || '';
        el.querySelector('.preview-download').href = a.dataset.download;
        if (mime.startsWith('image/')) body.innerHTML = `<img src="${a.dataset.src}" alt="">`;
        else if (mime === 'application/pdf') body.innerHTML = `<iframe src="${a.dataset.src}"></iframe>`;
        else body.innerHTML = `<div class="empty-state"><i class="bi bi-file-earmark"></i>پیش‌نمایش این نوع فایل ممکن نیست.<br>از دکمه دانلود استفاده کنید.</div>`;
        bootstrap.Modal.getOrCreateInstance(el).show();
    });

    /* ---------------- Dropzone ---------------- */
    document.addEventListener('change', e => {
        if (!e.target.matches('.dropzone input[type=file]')) return;
        const files = [...e.target.files];
        const out = e.target.closest('.dropzone').querySelector('.files');
        out.textContent = files.length ? files.map(f => f.name).join('، ') : '';
    });
    document.addEventListener('dragover', e => { const d = e.target.closest('.dropzone'); if (d) { e.preventDefault(); d.classList.add('drag'); } });
    document.addEventListener('dragleave', e => e.target.closest('.dropzone')?.classList.remove('drag'));
    document.addEventListener('drop', e => {
        const d = e.target.closest('.dropzone');
        if (!d) return;
        e.preventDefault(); d.classList.remove('drag');
        const inp = d.querySelector('input[type=file]');
        inp.files = e.dataTransfer.files;
        inp.dispatchEvent(new Event('change', { bubbles: true }));
    });

    /* ---------------- Repeater (راه‌های ارتباطی و ...) ---------------- */
    MM.repeaterAdd = function (container, data) {
        const tpl = document.getElementById(container.dataset.template);
        const node = tpl.content.firstElementChild.cloneNode(true);
        container.appendChild(node);
        if (data) Object.entries(data).forEach(([k, v]) => { const f = node.querySelector(`[data-field="${k}"]`); if (f) f.value = v ?? ''; });
        return node;
    };
    document.addEventListener('click', e => {
        const add = e.target.closest('[data-repeater-add]');
        if (add) { MM.repeaterAdd(document.querySelector(add.dataset.repeaterAdd)); return; }
        const rm = e.target.closest('[data-repeater-remove]');
        if (rm) rm.closest('.repeater-row').remove();
    });

    /* ---------------- روزشمار ---------------- */
    function tickCountdowns() {
        document.querySelectorAll('[data-countdown]').forEach(el => {
            const end = new Date(el.dataset.countdown.replace(' ', 'T'));
            let diff = Math.floor((end - new Date()) / 1000);
            const expired = diff <= 0;
            diff = Math.abs(diff);
            const d = Math.floor(diff / 86400), h = Math.floor(diff % 86400 / 3600), m = Math.floor(diff % 3600 / 60), s = diff % 60;
            el.classList.toggle('expired', expired);
            el.classList.toggle('soon', !expired && d < 15);
            el.classList.toggle('ok', !expired && d >= 15);
            const set = (k, v) => { const n = el.querySelector(`[data-cd="${k}"]`); if (n) n.textContent = MM.fa(String(v).padStart(k === 'd' ? 1 : 2, '0')); };
            set('d', d); set('h', h); set('m', m); set('s', s);
            const lbl = el.parentElement.querySelector('[data-cd-label]');
            if (lbl) lbl.textContent = expired ? 'از پایان پشتیبانی گذشته' : 'تا پایان پشتیبانی';
        });
    }
    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    /* ---------------- تم و سایدبار ---------------- */
    document.addEventListener('click', e => {
        if (e.target.closest('[data-toggle-theme]')) {
            const html = document.documentElement;
            const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('mm-theme', next); } catch (err) { }
            document.dispatchEvent(new CustomEvent('mm:theme', { detail: next }));
        }
        if (e.target.closest('[data-toggle-sidebar]') || e.target.closest('.mm-backdrop')) {
            document.body.classList.toggle('sidebar-open');
        }
    });

    /* ---------------- نوتیفیکیشن ---------------- */
    document.addEventListener('click', async e => {
        const a = e.target.closest('[data-mark-all-read]');
        if (!a) return;
        e.preventDefault();
        const json = await MM.post(a.dataset.markAllRead);
        if (json.ok) location.reload();
    });

    /* ---------------- جستجوی زنده جدول ---------------- */
    document.addEventListener('input', e => {
        if (!e.target.matches('[data-table-filter]')) return;
        const q = MM.en(e.target.value.trim().toLowerCase());
        document.querySelectorAll(e.target.dataset.tableFilter + ' tbody tr').forEach(tr => {
            tr.style.display = MM.en(tr.textContent.toLowerCase()).includes(q) ? '' : 'none';
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        MM.formatMoneyInputs();
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        // باز کردن تب از روی آدرس
        const tab = new URLSearchParams(location.search).get('tab');
        if (tab) { const t = document.querySelector(`[data-bs-target="#tab-${tab}"]`); if (t) bootstrap.Tab.getOrCreateInstance(t).show(); }
        document.querySelectorAll('[data-bs-toggle="pill"][data-bs-target^="#tab-"]').forEach(t => t.addEventListener('shown.bs.tab', () => {
            const u = new URL(location.href); u.searchParams.set('tab', t.dataset.bsTarget.replace('#tab-', '')); history.replaceState(null, '', u);
        }));
    });

    /* ---------------- نصب برنامه (PWA) ---------------- */
    let installEvt = null;
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        installEvt = e;
        document.querySelectorAll('[data-install-app]').forEach(b => b.classList.remove('d-none'));
    });
    window.addEventListener('appinstalled', () => {
        installEvt = null;
        document.querySelectorAll('[data-install-app]').forEach(b => b.classList.add('d-none'));
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-install-app]') || !installEvt) return;
        installEvt.prompt();
        installEvt.userChoice.finally(() => { installEvt = null; });
    });
    if ('serviceWorker' in navigator) {
        const man = document.querySelector('link[rel="manifest"]');
        if (man) window.addEventListener('load', () => navigator.serviceWorker.register(man.href.replace('manifest.webmanifest', 'sw.js')).catch(() => { }));
    }
})();
