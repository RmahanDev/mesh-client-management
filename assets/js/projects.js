/* =====================================================================
   سازنده برنامه پرداخت: یک‌جا / چندمرحله‌ای / اقساطی
   ===================================================================== */
(function () {
    'use strict';
    const J = MM.jalali;

    function rowHtml() {
        return document.getElementById('schedRowTpl').content.firstElementChild.cloneNode(true);
    }

    function Builder(root) {
        const form = root.closest('form');
        const rowsBox = root.querySelector('.schedule-rows');
        const totalInput = root.dataset.totalSource ? form.querySelector(root.dataset.totalSource) : null;
        const fixedTotal = root.dataset.total ? parseInt(root.dataset.total, 10) : null;
        const q = s => root.querySelector(s);

        const total = () => fixedTotal !== null ? fixedTotal : MM.parseMoney(totalInput?.value);
        const type = () => form.querySelector('input[name="payment_type"]:checked')?.value || 'single';

        function addRow(d = {}) {
            const r = rowHtml();
            r.querySelector('[data-f="title"]').value = d.title || '';
            r.querySelector('[data-f="amount"]').value = d.amount ? Number(d.amount).toLocaleString('en-US') : '';
            r.querySelector('[data-f="due"]').value = d.due || '';
            r.querySelector('[data-f="kind"]').value = d.kind || '';
            rowsBox.appendChild(r);
            renumber();
            return r;
        }
        function clear() { rowsBox.innerHTML = ''; }
        function renumber() {
            rowsBox.querySelectorAll('.schedule-row').forEach((r, i) => r.querySelector('.seq').textContent = MM.fa(i + 1));
            updateSum();
        }
        function rows() {
            return [...rowsBox.querySelectorAll('.schedule-row')].map(r => MM.parseMoney(r.querySelector('[data-f="amount"]').value));
        }
        function updateSum() {
            const t = total(), s = rows().reduce((a, b) => a + b, 0), d = t - s;
            q('[data-sum-total]').textContent = MM.money(t);
            q('[data-sum-rows]').textContent = MM.money(s);
            const diff = q('[data-sum-diff]');
            diff.textContent = d === 0 ? 'برابر ✓' : MM.money(Math.abs(d)) + (d > 0 ? ' کمتر از مبلغ کل' : ' بیشتر از مبلغ کل');
            diff.className = d === 0 ? 'good' : 'bad';
        }
        function round(n) {
            const r = parseInt(q('[data-round]')?.value || '1', 10);
            return r > 1 ? Math.floor(n / r) * r : Math.floor(n);
        }

        function buildSingle() {
            const prevDue = rowsBox.querySelector('[data-f="due"]')?.value || '';
            clear();
            addRow({ title: 'پرداخت یک‌جا', amount: total(), due: prevDue, kind: 'single' });
        }
        function buildStages() {
            const n = Math.max(2, parseInt(MM.en(q('[data-stage-count]').value), 10) || 2);
            const t = total();
            const each = round(t / n);
            const first = J.parse(q('[data-stage-first]')?.value || '');
            const gap = Math.max(0, parseInt(MM.en(q('[data-stage-gap]')?.value || '0'), 10) || 0);
            clear();
            for (let i = 0; i < n; i++) {
                const amount = i === n - 1 ? t - each * (n - 1) : each;
                const due = first ? J.format(gap ? J.addMonths(first, i * gap) : first) : '';
                addRow({ title: 'مرحله ' + MM.fa(i + 1), amount, due: i === 0 || gap ? due : '', kind: 'stage' });
            }
        }
        function buildInstallments() {
            const t = total();
            const pre = MM.parseMoney(q('[data-prepay]').value);
            const n = Math.max(1, parseInt(MM.en(q('[data-months]').value), 10) || 1);
            const interval = Math.max(1, parseInt(MM.en(q('[data-interval]').value), 10) || 1);
            const first = J.parse(q('[data-first-date]').value);
            if (!first) { MM.toast('تاریخ اولین قسط را انتخاب کنید', 'warning'); q('[data-first-date]').focus(); return; }
            if (pre > t) { MM.toast('پیش‌پرداخت از مبلغ کل بیشتر است', 'warning'); return; }
            const rest = t - pre;
            const each = round(rest / n);
            clear();
            if (pre > 0) addRow({ title: 'پیش‌پرداخت', amount: pre, due: q('[data-prepay-date]').value || '', kind: 'prepayment' });
            for (let i = 0; i < n; i++) {
                const amount = i === n - 1 ? rest - each * (n - 1) : each;
                addRow({ title: 'قسط ' + MM.fa(i + 1), amount, due: J.format(J.addMonths(first, i * interval)), kind: 'installment' });
            }
            MM.toast('اقساط محاسبه شد؛ می‌توانید مبلغ و تاریخ هر قسط را تغییر دهید');
        }

        function switchType() {
            const t = type();
            root.querySelectorAll('[data-pane]').forEach(p => p.classList.toggle('d-none', p.dataset.pane !== t));
            root.classList.toggle('mode-single', t === 'single');
            form.querySelectorAll('[data-show-for]').forEach(el => el.classList.toggle('d-none', el.dataset.showFor !== t));
            if (t === 'single') buildSingle();
            else if (t === 'stages' && rowsBox.querySelectorAll('.schedule-row').length < 2) buildStages();
            else if (t === 'installment') { clear(); updateSum(); }
        }

        form.addEventListener('change', e => {
            if (e.target.name === 'payment_type') switchType();
            if (e.target.matches('[data-single-due]') && type() === 'single') buildSingle();
        });
        totalInput?.addEventListener('input', () => { if (type() === 'single') buildSingle(); else updateSum(); });
        root.addEventListener('input', e => { if (e.target.matches('[data-f="amount"]')) updateSum(); });
        root.addEventListener('click', e => {
            if (e.target.closest('[data-build-stages]')) buildStages();
            if (e.target.closest('[data-build-inst]')) buildInstallments();
            if (e.target.closest('[data-add-row]')) addRow({ title: type() === 'installment' ? 'قسط ' + MM.fa(rows().length + 1) : 'مرحله ' + MM.fa(rows().length + 1), kind: type() === 'installment' ? 'installment' : (type() === 'single' ? 'single' : 'stage') });
            if (e.target.closest('[data-rm-row]')) { e.target.closest('.schedule-row').remove(); renumber(); }
            if (e.target.closest('[data-fill-diff]')) {
                const last = [...rowsBox.querySelectorAll('[data-f="amount"]')].pop();
                if (!last) return;
                const d = total() - rows().reduce((a, b) => a + b, 0);
                last.value = (MM.parseMoney(last.value) + d).toLocaleString('en-US');
                updateSum();
            }
        });

        form.onBeforeSubmit = function () {
            const t = total(), s = rows().reduce((a, b) => a + b, 0);
            if (t > 0 && s !== t) {
                MM.toast('جمع ردیف‌های پرداخت با مبلغ کل برابر نیست', 'error');
                return false;
            }
            if (type() === 'installment' && t > 0 && !rows().length) {
                MM.toast('ابتدا دکمه «محاسبه اقساط» را بزنید', 'error');
                return false;
            }
            return true;
        };

        form.addEventListener('mm:filled', () => { clear(); switchType(); });
        switchType();
    }

    document.querySelectorAll('.schedule-builder').forEach(el => Builder(el));

    // فیلتر دسته‌ها در مودال
    document.addEventListener('input', e => {
        if (!e.target.matches('[data-cat-search]')) return;
        const qv = e.target.value.trim();
        e.target.closest('.cat-picker').querySelectorAll('.form-check').forEach(fc => {
            fc.style.display = fc.textContent.includes(qv) ? '' : 'none';
        });
    });

    // نمایش تاریخ پایان پشتیبانی
    function supportPreview(form) {
        const out = form.querySelector('[data-support-end]');
        if (!out) return;
        const d = J.parse(form.querySelector('[name="delivery_date"]')?.value || '');
        const m = parseInt(MM.en(form.querySelector('[name="support_months"]')?.value || '0'), 10) || 0;
        out.textContent = d ? (m ? 'پایان پشتیبانی: ' + MM.fa(J.format(J.addMonths(d, m))) : 'بدون پشتیبانی') : 'با ثبت تاریخ تحویل، روزشمار پشتیبانی شروع می‌شود';
    }
    document.addEventListener('change', e => { if (e.target.matches('[name="delivery_date"],[name="support_months"]')) supportPreview(e.target.form); });
    document.addEventListener('input', e => { if (e.target.matches('[name="support_months"]')) supportPreview(e.target.form); });
    document.querySelectorAll('form').forEach(f => f.addEventListener('mm:filled', () => supportPreview(f)));

    // نمایش/مخفی کردن بخش سفته و ضامن
    document.addEventListener('change', e => {
        const t = e.target.closest('[data-toggle-box]');
        if (t) document.querySelector(t.dataset.toggleBox)?.classList.toggle('d-none', !t.checked);
    });
})();

/* سهم‌بندی سود پروژه: محاسبه زنده مبلغ هر نفر */
(function () {
    const form = document.getElementById('sharesForm');
    if (!form) return;
    const share = (base, pct) => Math.floor(base * Math.round(pct * 100) / 10000);
    const pctLabel = n => MM.fa(String(Math.round(n * 100) / 100)) + '٪';
    function update() {
        let sum = 0;
        form.querySelectorAll('[data-share-row]').forEach(row => {
            const raw = MM.en(row.querySelector('[name="share_percent[]"]').value).replace(/[٫\/]/g, '.');
            const pct = parseFloat(raw) || 0;
            sum += pct;
            row.querySelector('[data-share-amount]').textContent = MM.money(share(+form.dataset.base, pct));
            row.querySelector('[data-share-expected]').textContent = MM.money(share(+form.dataset.expected, pct));
        });
        const sumEl = form.querySelector('[data-share-sum]');
        sumEl.textContent = pctLabel(sum);
        sumEl.classList.toggle('text-danger', sum > 100);
        form.querySelector('[data-share-left]').textContent = pctLabel(100 - sum);
    }
    form.addEventListener('input', update);
    form.addEventListener('click', e => { if (e.target.closest('[data-repeater-remove],[data-repeater-add]')) setTimeout(update); });
    update();
})();
