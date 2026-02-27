/**
 * assets/js/form.js
 * Step order:
 * card-contact → card-personal → card-passport → card-residential
 * → card-background → card-declaration
 * → (group: card-traveller-added → repeat) → card-confirm → card-payment
 */

const BASE = 'modules/ajax/';

// ── State ─────────────────────────────────────────────────
let currentTraveller = 1;
let totalTravellers  = 1;
let travelMode       = 'solo';

// ── CSRF token ────────────────────────────────────────────
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// ── Loader ────────────────────────────────────────────────
function showLoader(msg = 'Please wait...') {
    document.getElementById('eta-loader-msg').textContent = msg;
    document.getElementById('eta-loader').style.display = 'flex';
}
function hideLoader() {
    document.getElementById('eta-loader').style.display = 'none';
}

// ── Toast ─────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const t = document.getElementById('eta-toast');
    t.textContent = msg;
    t.className = type === 'success' ? 'eta-toast-success' : 'eta-toast-error';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 4000);
}

// ── Navigate to card ──────────────────────────────────────
function navTo(cardId) {
    document.querySelectorAll('.mini-card').forEach(c => c.classList.remove('active'));
    const card = document.getElementById(cardId);
    if (card) {
        card.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    updateStepper(cardId);
    if (typeof EtaValidator !== 'undefined') EtaValidator.attachLiveValidation(cardId);
}

// ── Stepper update ────────────────────────────────────────
function updateStepper(cardId) {
    const step1Cards = ['card-contact','card-personal','card-passport','card-residential','card-background','card-declaration','card-traveller-added'];
    const step2Cards = ['card-confirm'];
    const step3Cards = ['card-payment'];

    const st1 = document.getElementById('st-1');
    const st2 = document.getElementById('st-2');
    const st3 = document.getElementById('st-3');

    [st1,st2,st3].forEach(s => { if(s) s.className = 'step-item'; });

    if (step1Cards.includes(cardId)) {
        st1?.classList.add('active');
    } else if (step2Cards.includes(cardId)) {
        st1?.classList.add('completed');
        st2?.classList.add('active');
    } else if (step3Cards.includes(cardId)) {
        st1?.classList.add('completed');
        st2?.classList.add('completed');
        st3?.classList.add('active');
    }
}

// ── Update person labels ───────────────────────────────────
function updatePersonLabels() {
    const label = `Traveller ${currentTraveller}`;
    ['contact','personal','passport','residential','background'].forEach(id => {
        const el = document.getElementById(`${id}-person-label`);
        if (el) el.textContent = label;
    });
}

// ── Collect form data from a card ────────────────────────
function collectData(cardId, extra = {}) {
    const card = document.getElementById(cardId);
    const fd   = new FormData();

    fd.append('csrf_token',       csrf());
    fd.append('traveller_num',    currentTraveller);
    fd.append('travel_mode',      travelMode);
    fd.append('total_travellers', totalTravellers);

    card.querySelectorAll('input[name], select[name], textarea[name]').forEach(f => {
        if (f.type === 'file') return;
        if (f.type === 'radio'    && !f.checked) return;
        if (f.type === 'checkbox') { fd.append(f.name, f.checked ? '1' : '0'); return; }

        // Skip old city helper inputs
        if (f.name === 't_city_text') return;

        let val = f.value;

        // intlTelInput phone
        if (f.id && f.id === 'phone_field') {
            if (window.itiInstances && window.itiInstances['phone_field']) {
                val = window.itiInstances['phone_field'].getNumber() || val;
            }
        }

        fd.append(f.name, val);
    });

    Object.entries(extra).forEach(([k,v]) => fd.append(k, v));
    return fd;
}

// ── Apply server-side errors to fields ───────────────────
function applyServerErrors(errors, cardId) {
    Object.entries(errors).forEach(([field, msg]) => {
        const el = document.querySelector(`#${cardId} [name="${field}"], #${cardId} [name="t_${field}"]`);
        if (el && typeof EtaValidator !== 'undefined') EtaValidator.showError(el, msg);
    });
}

// ── Generic save step ────────────────────────────────────
async function saveStep(cardId, endpoint, onSuccess) {
    if (typeof EtaValidator !== 'undefined' && !EtaValidator.validateStep(cardId)) {
        showToast('Please fill in all required fields correctly.', 'error');
        return;
    }

    const fd = collectData(cardId);
    showLoader('Saving...');

    try {
        const res  = await fetch(BASE + endpoint, { method: 'POST', body: fd });
        const raw  = await res.text();
        hideLoader();

        let data;
        try { data = JSON.parse(raw); }
        catch(e) {
            showToast('Server error. Please try again.', 'error');
            return;
        }

        if (data.success) {
            if (data.application_ref) {
                document.getElementById('app-ref-number').textContent = data.application_ref;
                document.getElementById('ref-display').style.display = 'block';
            }
            showToast('Saved!', 'success');
            if (typeof onSuccess === 'function') onSuccess(data);
        } else {
            if (data.errors) applyServerErrors(data.errors, cardId);
            showToast(data.message || 'Please check your entries.', 'error');
        }
    } catch(err) {
        hideLoader();
        showToast('Network error: ' + err.message, 'error');
    }
}

// ── Build traveller review list ───────────────────────────
function escHtml(v) {
    return String(v ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[ch]));
}

function showVal(v) {
    const s = String(v ?? '').trim();
    return s === '' ? '-' : escHtml(s);
}

async function buildReviewList() {
    const list = document.getElementById('travellers-review-list');
    if (!list) return;
    list.innerHTML = '<p class="text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</p>';

    try {
        const rows = [];
        for (let i = 1; i <= totalTravellers; i++) {
            const res  = await fetch(`${BASE}get_traveller.php?traveller_num=${i}`);
            const data = await res.json();
            if (data.success && data.traveller) {
                const t = data.traveller;
                const dob = t.date_of_birth || '-';
                const fullName = `${t.first_name || ''} ${t.last_name || ''}`.trim() || 'N/A';
                const formStatus = (t.decl_accurate === '1' || t.decl_accurate === 1) && (t.decl_terms === '1' || t.decl_terms === 1)
                    ? 'Completed'
                    : (t.step_completed || 'In Progress');

                rows.push(`
                    <div class="traveler-row" onclick="editTraveller(${i})">
                        <div style="width:100%;">
                            <div class="traveler-info">
                                <div class="traveler-icon"><i class="fas fa-user"></i></div>
                                <div class="traveler-details">
                                    <span class="label">Traveller ${i}</span>
                                    <span class="name">${escHtml(fullName)}</span>
                                </div>
                                <div class="edit-arrow ms-auto"><i class="fas fa-chevron-right"></i></div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-4"><small class="text-muted">Email</small><div>${showVal(t.email)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Phone</small><div>${showVal(t.phone)}</div></div>
                                <div class="col-md-4"><small class="text-muted">DOB</small><div>${showVal(dob)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Nationality</small><div>${showVal(t.nationality)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Passport No</small><div>${showVal(t.passport_number)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Travel Date</small><div>${showVal(t.travel_date)}</div></div>
                                <div class="col-md-6"><small class="text-muted">Residential Address</small><div>${showVal(t.address_line)} ${showVal(t.street_number)}</div></div>
                                <div class="col-md-3"><small class="text-muted">Country / City</small><div>${showVal(t.country)} / ${showVal(t.city)}</div></div>
                                <div class="col-md-3"><small class="text-muted">Postal</small><div>${showVal(t.postal_code)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Occupation</small><div>${showVal(t.occupation)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Employer</small><div>${showVal(t.employer_name)}</div></div>
                                <div class="col-md-4"><small class="text-muted">Form Status</small><div>${showVal(formStatus)}</div></div>
                            </div>
                        </div>
                    </div>`);
            }
        }

        const appSummary = `
            <div class="review-section mb-3">
                <div class="review-title-head">
                    <h6><i class="fas fa-clipboard-check me-2"></i>Application Summary</h6>
                </div>
                <div class="p-3">
                    <div class="row g-2">
                        <div class="col-md-4"><small class="text-muted">Travel Mode</small><div>${escHtml(travelMode.toUpperCase())}</div></div>
                        <div class="col-md-4"><small class="text-muted">Total Travellers</small><div>${totalTravellers}</div></div>
                        <div class="col-md-4"><small class="text-muted">Current Form Country</small><div>${escHtml(window.FORM_COUNTRY || 'Canada')}</div></div>
                    </div>
                </div>
            </div>`;

        list.innerHTML = appSummary + (rows.join('') || '<p class="text-muted text-center">No travellers found.</p>');
    } catch(e) {
        list.innerHTML = '<p class="text-danger text-center">Could not load traveller details.</p>';
    }
}
function editTraveller(num) {
    currentTraveller = num;
    updatePersonLabels();
    navTo('card-contact');
}

// ═══════════════════════════════════════════════════════════
//  BUTTON WIRING — after DOM ready
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {

    // ── 1.1 Contact → Personal ────────────────────────────
    document.getElementById('btn-contact-next')?.addEventListener('click', function() {
        // Read travel mode
        const modeRadio = document.querySelector('input[name="travel_mode"]:checked');
        travelMode      = modeRadio ? modeRadio.value : 'solo';
        totalTravellers = travelMode === 'group'
            ? parseInt(document.getElementById('total-travellers-count')?.value || 2)
            : 1;

        saveStep('card-contact', 'save_step_contact.php', function() {
            navTo('card-personal');
        });
    });

    // ── 1.2 Personal → Passport ───────────────────────────
    document.getElementById('btn-personal-next')?.addEventListener('click', function() {
        saveStep('card-personal', 'save_step_personal.php', function() {
            navTo('card-passport');
        });
    });

    // ── 1.3 Passport → Residential ───────────────────────
    document.getElementById('btn-passport-next')?.addEventListener('click', function() {
        saveStep('card-passport', 'save_step_passport.php', function() {
            navTo('card-residential');
        });
    });

    // ── 1.4 Residential → Background ─────────────────────
    document.getElementById('btn-residential-next')?.addEventListener('click', function() {
        saveStep('card-residential', 'save_step_residential.php', function() {
            navTo('card-background');
        });
    });

    // ── 1.5 Background + Declaration → Confirm / Traveller Added ──
    document.getElementById('btn-declaration-save')?.addEventListener('click', function() {
        // Check both declaration checkboxes first
        const cb1 = document.querySelector('#card-background input[name="t_decl_accurate"]');
        const cb2 = document.querySelector('#card-background input[name="t_decl_terms"]');
        if (!cb1?.checked || !cb2?.checked) {
            showToast('Please accept both declarations to continue.', 'error');
            return;
        }

        // Save background questions
        saveStep('card-background', 'save_step_background.php', function() {
            // Then save declaration (same card data)
            const fd = collectData('card-background');
            fetch(BASE + 'save_step_declaration.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (travelMode === 'group' && currentTraveller < totalTravellers) {
                        document.getElementById('traveller-added-msg').textContent =
                            `Traveller ${currentTraveller} details saved successfully.`;
                        const remaining = totalTravellers - currentTraveller;
                        document.getElementById('travellers-remaining-msg').textContent =
                            `${remaining} more traveller${remaining > 1 ? 's' : ''} to add.`;
                        navTo('card-traveller-added');
                    } else {
                        buildReviewList();
                        navTo('card-confirm');
                    }
                })
                .catch(() => navTo(travelMode === 'group' && currentTraveller < totalTravellers ? 'card-traveller-added' : 'card-confirm'));
        });
    });

    // ── Add Next Traveller ────────────────────────────────
    document.getElementById('btn-add-next-traveller')?.addEventListener('click', function() {
        currentTraveller++;
        updatePersonLabels();

        // Clear all form fields for new traveller
        ['card-contact','card-personal','card-passport','card-residential','card-background','card-declaration'].forEach(cardId => {
            const card = document.getElementById(cardId);
            if (!card) return;
            card.querySelectorAll('input:not([type=radio]):not([type=checkbox])').forEach(i => i.value = '');
            card.querySelectorAll('select').forEach(s => {
                s.selectedIndex = 0;
                if ($(s).data('select2')) $(s).val('').trigger('change');
            });
            card.querySelectorAll('textarea').forEach(t => t.value = '');
            card.querySelectorAll('.eta-error').forEach(e => e.remove());
            card.querySelectorAll('.is-invalid,.is-valid').forEach(f => f.classList.remove('is-invalid','is-valid'));
            // Reset radios to No
            card.querySelectorAll('input[type=radio][value="0"]').forEach(r => r.checked = true);
            // Hide conditional boxes
            card.querySelectorAll('.conditional-box').forEach(b => b.style.display = 'none');
        });

        // Hide traveller-type section for traveller 2+
        document.getElementById('traveller-type-section').style.display = 'none';

        navTo('card-contact');
    });
    document.getElementById('btn-add-another-traveller')?.addEventListener('click', function() {
        totalTravellers = Math.max(totalTravellers, currentTraveller + 1);
        travelMode = 'group';
        document.getElementById('btn-add-next-traveller')?.click();
    });

    // ── Confirm Back ──────────────────────────────────────
    document.getElementById('btn-confirm-back')?.addEventListener('click', function() {
        navTo('card-declaration');
    });

    // ── Confirm → Payment ─────────────────────────────────
    document.getElementById('btn-confirm-pay-now')?.addEventListener('click', function() {
        const agree = document.getElementById('confirm-details-check');
        if (!agree?.checked) {
            showToast('Please confirm that your details are correct before proceeding.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('csrf_token', csrf());

        showLoader('Confirming details and sending email...');
        fetch(BASE + 'confirm_submission.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                hideLoader();
                if (!data.success) {
                    showToast(data.message || 'Could not confirm details.', 'error');
                    return;
                }
                showToast(data.message || 'Details confirmed successfully.', 'success');
                navTo('card-payment');
            })
            .catch(err => {
                hideLoader();
                showToast('Network error: ' + err.message, 'error');
            });
    });

    // ── Payment Back ──────────────────────────────────────
    document.getElementById('btn-payment-back')?.addEventListener('click', function() {
        navTo('card-confirm');
    });

    // ── Pay Now ───────────────────────────────────────────
    document.getElementById('submit-payment-btn')?.addEventListener('click', function() {
        if (typeof EtaValidator !== 'undefined' && !EtaValidator.validateStep('card-payment')) {
            showToast('Please fill in all payment details.', 'error');
            return;
        }
        initiatePayment();
    });

    // Init first card validation
    if (typeof EtaValidator !== 'undefined') EtaValidator.attachLiveValidation('card-contact');
});

// ── Razorpay Payment ──────────────────────────────────────
let mwPayframeInstance = null;
let mwPaymentContext = null;
let mwFrameMounted = false;

function ensurePayframeScript(scriptUrl) {
    return new Promise((resolve, reject) => {
        if (typeof window.payframe === 'function') {
            resolve();
            return;
        }
        const existing = document.querySelector('script[data-mw-payframe="1"]');
        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error('Unable to load payframe script.')), { once: true });
            return;
        }
        const s = document.createElement('script');
        s.src = scriptUrl;
        s.async = true;
        s.dataset.mwPayframe = '1';
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Unable to load payframe script.'));
        document.head.appendChild(s);
    });
}

async function initiatePayment() {
    try {
        if (mwPayframeInstance && mwPaymentContext?.orderId) {
            showLoader('Submitting card securely...');
            try {
                mwPayframeInstance.submitPayframe();
            } catch (e) {
                hideLoader();
                showToast('Could not submit payframe. Please reload and try again.', 'error');
            }
            return;
        }

        showLoader('Initialising payment...');
        const fd = collectData('card-payment');
        const selectedPlan = document.querySelector('input[name="plan"]:checked')?.value || 'standard';
        fd.append('plan', selectedPlan);

        const res = await fetch('modules/payments/payment.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
            hideLoader();
            showToast(data.message || 'Payment init failed.', 'error');
            return;
        }

        await ensurePayframeScript(data.payframe_js);
        mwPaymentContext = { orderId: data.order_id };

        if (!mwFrameMounted) {
            const cardInfoBlock = document.querySelector('#card-payment .review-section.mb-4 .p-3');
            if (cardInfoBlock) {
                cardInfoBlock.innerHTML = `
                    <p class="text-muted mb-2">Enter your card details in secure Merchant Warrior frame.</p>
                    <div id="mw-payframe-container" style="min-height:220px;"></div>
                `;
                mwFrameMounted = true;
            }
        }

        const payframeDiv = document.getElementById('mw-payframe-container');
        if (!payframeDiv) {
            hideLoader();
            showToast('Payment frame container not found.', 'error');
            return;
        }
        payframeDiv.innerHTML = '';

        mwPayframeInstance = new payframe(
            data.merchant_uuid,
            data.api_key,
            'mw-payframe-container',
            data.payframe_src || 'camp',
            data.submit_url || 'camp',
            null,
            'visa,mastercard',
            'addCard'
        );

        mwPayframeInstance.loaded = function() { hideLoader(); };

        mwPayframeInstance.mwCallback = async function(cardId, arg2, arg3) {
            const invalidCardIdValues = ['error', 'NO_TOKEN', 'TOKEN_ERROR', 'TOKEN_INVALID', 'TOKEN_EXPIRED', ''];
            const normalized = String(cardId || '').trim();
            if (arg2 !== undefined || arg3 !== undefined || invalidCardIdValues.includes(normalized)) {
                hideLoader();
                showToast('Card tokenisation failed. Please re-check card details.', 'error');
                return;
            }

            showLoader('Verifying payment...');
            try {
                const vfd = new FormData();
                vfd.append('csrf_token', csrf());
                vfd.append('order_id', mwPaymentContext?.orderId || '');
                vfd.append('card_id', cardId);

                const vres = await fetch('modules/payments/payment_verify.php', { method: 'POST', body: vfd });
                const vdata = await vres.json();
                hideLoader();

                if (vdata.success) {
                    window.location.href = 'thank-you.php?ref=' + (vdata.reference || '');
                    return;
                }
                showToast(vdata.message || 'Payment verification failed.', 'error');
            } catch (err) {
                hideLoader();
                showToast('Payment verify failed: ' + err.message, 'error');
            }
        };

        mwPayframeInstance.deploy();
        hideLoader();
        showToast('Secure card frame loaded. Fill card details and click Submit again.', 'success');
    } catch (err) {
        hideLoader();
        const msg = (err && err.message) ? err.message : String(err || 'Unknown error');
        showToast('Payment error: ' + msg, 'error');
    }
}
