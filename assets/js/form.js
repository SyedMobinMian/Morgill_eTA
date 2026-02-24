/**
 * assets/js/form.js
 * Step order:
 * card-contact â†’ card-personal â†’ card-passport â†’ card-residential
 * â†’ card-background â†’ card-declaration
 * â†’ (group: card-traveller-added â†’ repeat) â†’ card-confirm â†’ card-payment
 */

const BASE = 'backend/ajax/';

// â”€â”€ State â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let currentTraveller = 1;
let totalTravellers  = 1;
let travelMode       = 'solo';

// â”€â”€ CSRF token â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// â”€â”€ Loader â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showLoader(msg = 'Please wait...') {
    document.getElementById('eta-loader-msg').textContent = msg;
    document.getElementById('eta-loader').style.display = 'flex';
}
function hideLoader() {
    document.getElementById('eta-loader').style.display = 'none';
}

// â”€â”€ Toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showToast(msg, type = 'success') {
    const t = document.getElementById('eta-toast');
    t.textContent = msg;
    t.className = type === 'success' ? 'eta-toast-success' : 'eta-toast-error';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 4000);
}

// â”€â”€ Navigate to card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Stepper update â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Update person labels â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function updatePersonLabels() {
    const label = `Traveller ${currentTraveller}`;
    ['contact','personal','passport','residential','background'].forEach(id => {
        const el = document.getElementById(`${id}-person-label`);
        if (el) el.textContent = label;
    });
}

// â”€â”€ Collect form data from a card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Apply server-side errors to fields â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function applyServerErrors(errors, cardId) {
    Object.entries(errors).forEach(([field, msg]) => {
        const el = document.querySelector(`#${cardId} [name="${field}"], #${cardId} [name="t_${field}"]`);
        if (el && typeof EtaValidator !== 'undefined') EtaValidator.showError(el, msg);
    });
}

// â”€â”€ Generic save step â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Build traveller review list â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
//  BUTTON WIRING â€” after DOM ready
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
document.addEventListener('DOMContentLoaded', function() {

    // â”€â”€ 1.1 Contact â†’ Personal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ 1.2 Personal â†’ Passport â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btn-personal-next')?.addEventListener('click', function() {
        saveStep('card-personal', 'save_step_personal.php', function() {
            navTo('card-passport');
        });
    });

    // â”€â”€ 1.3 Passport â†’ Residential â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btn-passport-next')?.addEventListener('click', function() {
        saveStep('card-passport', 'save_step_passport.php', function() {
            navTo('card-residential');
        });
    });

    // â”€â”€ 1.4 Residential â†’ Background â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btn-residential-next')?.addEventListener('click', function() {
        saveStep('card-residential', 'save_step_residential.php', function() {
            navTo('card-background');
        });
    });

    // â”€â”€ 1.5 Background + Declaration â†’ Confirm / Traveller Added â”€â”€
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

    // â”€â”€ Add Next Traveller â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Confirm Back â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btn-confirm-back')?.addEventListener('click', function() {
        navTo('card-declaration');
    });

    // â”€â”€ Confirm â†’ Payment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    // â”€â”€ Payment Back â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btn-payment-back')?.addEventListener('click', function() {
        navTo('card-confirm');
    });

    // â”€â”€ Pay Now â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€ Razorpay Payment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function initiatePayment() {
    showLoader('Initialising payment...');
    try {
        const fd = collectData('card-payment');
        const selectedPlan = document.querySelector('input[name="plan"]:checked')?.value || 'standard';
        fd.append('plan', selectedPlan);
        const res  = await fetch('backend/payment.php', { method: 'POST', body: fd });
        const data = await res.json();
        hideLoader();

        if (!data.success) { showToast(data.message || 'Payment init failed.', 'error'); return; }

        const options = {
            key:         data.key,
            amount:      data.amount,
            currency:    data.currency || 'INR',
            name:        window.FORM_DISPLAY_NAME || 'Visa Application',
            description: 'eTA Application Fee',
            order_id:    data.order_id,
            handler: async function(response) {
                showLoader('Verifying payment...');
                const vfd = new FormData();
                vfd.append('csrf_token',           csrf());
                vfd.append('razorpay_payment_id',  response.razorpay_payment_id);
                vfd.append('razorpay_order_id',    response.razorpay_order_id);
                vfd.append('razorpay_signature',   response.razorpay_signature);
                const vres  = await fetch('backend/payment_verify.php', { method: 'POST', body: vfd });
                const vdata = await vres.json();
                hideLoader();
                if (vdata.success) {
                    window.location.href = 'thank-you.php?ref=' + (vdata.reference || '');
                } else {
                    showToast('Payment verification failed.', 'error');
                }
            },
            prefill: {
                name:  document.querySelector('[name="billing_first_name"]')?.value + ' ' +
                       document.querySelector('[name="billing_last_name"]')?.value,
                email: document.querySelector('[name="billing_email"]')?.value,
            },
            theme: { color: '#0d6efd' }
        };
        new Razorpay(options).open();
    } catch(err) {
        hideLoader();
        showToast('Payment error: ' + err.message, 'error');
    }
}



