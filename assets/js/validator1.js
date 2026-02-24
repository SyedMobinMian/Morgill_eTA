/**
 * ============================================================
 * assets/js/validator.js
 * Frontend Validation Engine for Canada eTA Application
 * Works seamlessly on Localhost and Production Servers.
 * ============================================================
 */

const EtaValidator = {

    /**
     * ── VALIDATION RULES CONFIGURATION ──
     * Define rules for each field name (mapped without 't_' prefix).
     * Supports: required, min/max length, regex, matching, and date constraints.
     */
    rules: {
        // ── Trip Details ──────────────────────────────────
        first_name:               { required:true,  minLen:2, maxLen:100, regex:/^[a-zA-Z\s\-']+$/,    regexMsg:'Only letters, spaces and hyphens.' },
        middle_name:              { required:false, minLen:2, maxLen:100, regex:/^[a-zA-Z\s\-']+$/,    regexMsg:'Only letters, spaces and hyphens.' },
        last_name:                { required:true,  minLen:2, maxLen:100, regex:/^[a-zA-Z\s\-']+$/,    regexMsg:'Only letters, spaces and hyphens.' },
        email:                    { required:true,  regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,             regexMsg:'Enter a valid email address.' },
        phone:                    { required:true,  isPhone:true },
        travel_date:              { required:true,  futureDate:true },
        purpose_of_visit:         { required:true },

        // ── Personal Details ──────────────────────────────
        date_of_birth:            { required:true,  pastDate:true },
        gender:                   { required:true },
        country_of_birth:         { required:true },
        city_of_birth:            { required:true,  minLen:2 },
        marital_status:           { required:true },
        nationality:              { required:true },

        // ── Passport Details ──────────────────────────────
        passport_country:         { required:true },
        passport_number:          { required:true,  regex:/^[A-Z0-9]{6,20}$/i, regexMsg:'6-20 letters/numbers only.' },
        passport_number_confirm:  { required:true,  mustMatch:'passport_number', matchMsg:'Passport numbers do not match.' },
        passport_issue_date:      { required:true,  pastDate:true },
        passport_expiry:          { required:true,  futureDate:true },
        other_citizenship_country: { required:false },
        uci_number:               { required:false },

        // ── Residential & Employment Details ──────────────
        address_line:             { required:true,  minLen:5 },
        street_number:            { required:true,  minLen:2 },
        apartment_number:         { required:false },
        country:                  { required:true },
        state:                    { required:false },
        city:                     { required:true, minLen:2 },
        postal_code:              { required:true,  regex:/^[A-Z0-9\s\-]{3,10}$/i, regexMsg:'Enter a valid postal/zip code.' },
        occupation:               { required:true },
        job_title:                { required:false, minLen:2 },
        employer_name:            { required:false, minLen:2 },
        employer_country:         { required:false },
        employer_city:            { required:false, minLen:2 },
        start_year:               { required:false },

        // ── Background Questions (Conditional) ─────────────
        visa_refusal_details:     { required:false, minLen:10 },
        tuberculosis_details:     { required:false, minLen:10 },
        criminal_details:         { required:false, minLen:10 },
        health_condition:         { required:true },

        // ── Billing Details ───────────────────────────────
        billing_first_name:       { required:true,  minLen:2, regex:/^[a-zA-Z\s\-']+$/, regexMsg:'Only letters.' },
        billing_last_name:        { required:true,  minLen:2, regex:/^[a-zA-Z\s\-']+$/, regexMsg:'Only letters.' },
        billing_email:            { required:false, regex:/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, regexMsg:'Valid email required.' },
        billing_address:          { required:true,  minLen:5 },
        billing_city:             { required:true,  minLen:2 },
        billing_zip:              { required:true,  regex:/^[A-Z0-9\s\-]{3,10}$/i, regexMsg:'Valid zip code required.' },
    },

    /**
     * Extracts full E.164 formatted number from 'intlTelInput' instances.
     * Handles multiple possible ways the ITI instance might be stored on the element.
     */
    getPhoneNumber(field) {
        try {
            if (window.itiInstances && window.itiInstances[field.id])
                return window.itiInstances[field.id].getNumber() || field.value;
            if (window.intlTelInputGlobals) {
                const inst = Object.values(window.intlTelInputGlobals.instances || {}).find(i => i.telInput === field);
                if (inst) return inst.getNumber() || field.value;
            }
            if (field._itiInstance) return field._itiInstance.getNumber() || field.value;
        } catch(e) {}
        return field.value;
    },

    /**
     * Logic for validating a single input field against its specific rule.
     * @return {string|null} Error message or null if valid.
     */
    validateField(name, value, forceRequired) {
        const rule = this.rules[name];
        if (!rule) return null;
        const val = String(value || '').trim();
        const req = rule.required || forceRequired;

        // Requirement check
        if (req && val === '') return this.label(name) + ' is required.';
        if (val === '') return null; // Exit if optional and empty

        // Phone number pattern check
        if (rule.isPhone) {
            const clean = val.replace(/[\s\-\(\)]/g, '');
            if (!/^\+?[1-9]\d{6,14}$/.test(clean))
                return 'Enter a valid phone number with country code (e.g. +91 9876543210).';
            return null;
        }

        // Cross-field matching (e.g., Passport Confirmation)
        if (rule.mustMatch) {
            const other = document.querySelector(`[name="t_${rule.mustMatch}"]`);
            if (other && val.toUpperCase() !== (other.value || '').trim().toUpperCase())
                return rule.matchMsg || this.label(name) + ' does not match.';
        }

        // Length Constraints
        if (rule.minLen && val.length < rule.minLen)
            return `${this.label(name)} must be at least ${rule.minLen} characters.`;
        if (rule.maxLen && val.length > rule.maxLen)
            return `${this.label(name)} must be under ${rule.maxLen} characters.`;

        // Regular Expression Match
        if (rule.regex && !rule.regex.test(val))
            return rule.regexMsg || this.label(name) + ' format is invalid.';

        // Future Date Validation (Travel Date)
        if (rule.futureDate) {
            const d = new Date(val), today = new Date(); today.setHours(0,0,0,0);
            if (isNaN(d) || d <= today) return `${this.label(name)} must be a future date.`;
        }

        // Past Date Validation (DOB / Passport Issue)
        if (rule.pastDate) {
            const d = new Date(val), today = new Date(); today.setHours(0,0,0,0);
            if (isNaN(d) || d >= today) return `${this.label(name)} must be a past date.`;
        }
        return null;
    },

    /**
     * Validates all inputs within a specific container (cardId).
     * Handles complex conditional requirements (visibility/logic-based).
     */
    validateStep(cardId) {
        const card = document.getElementById(cardId);
        if (!card) return true;
        let ok = true;

        card.querySelectorAll('input[name], select[name], textarea[name]').forEach(f => {
            if (f.type === 'file' || f.type === 'radio' || f.type === 'checkbox') return;

            // Visibility Check: Skip hidden fields (except Select2 which hides the raw select)
            if (f.tagName !== 'SELECT' && !this.isVisible(f)) return;

            // Filter out internal helpers
            if (f.name === 't_city_text') return;

            const name = f.name.replace(/^t_/, '');
            let val = f.value;
            if (f.id === 'phone_field') val = this.getPhoneNumber(f);

            // ── CONDITIONAL VALIDATION LOGIC ──
            let forceReq = false;

            // Dual Citizenship Country Requirement
            if (name === 'other_citizenship_country') {
                const r = card.querySelector('input[name="t_dual_citizen"][value="1"]');
                if (!r || !r.checked) return;
                forceReq = true;
            }
            // Background Questions Details Requirement
            if (name === 'visa_refusal_details') {
                const r = card.querySelector('input[name="t_visa_refusal"][value="1"]');
                if (!r || !r.checked) return;
                forceReq = true;
            }
            if (name === 'tuberculosis_details') {
                const r = card.querySelector('input[name="t_tuberculosis"][value="1"]');
                if (!r || !r.checked) return;
                forceReq = true;
            }
            if (name === 'criminal_details') {
                const r = card.querySelector('input[name="t_criminal_history"][value="1"]');
                if (!r || !r.checked) return;
                forceReq = true;
            }

            // Employment Details Logic: Required ONLY if user is NOT Retired/Unemployed/Homemaker
            const NO_JOB = ['Retired', 'Unemployed', 'Homemaker'];
            if (['job_title','employer_name','employer_country','employer_city','start_year'].includes(name)) {
                const occ = card.querySelector('select[name="t_occupation"]');
                if (!occ || !occ.value || NO_JOB.includes(occ.value)) return;
                forceReq = true;
            }

            const err = this.validateField(name, val, forceReq);
            if (err) { this.showError(f, err); ok = false; }
            else      { this.clearError(f); }
        });

        // Mandatory Declaration Checkboxes
        card.querySelectorAll('.decl-checkbox').forEach(cb => {
            if (!cb.checked) {
                this.showCheckboxError(cb, 'This declaration is required.');
                ok = false;
            } else {
                this.clearCheckboxError(cb);
            }
        });

        return ok;
    },

    /**
     * Helper to determine if an element is currently hidden via CSS display.
     */
    isVisible(el) {
        if (el.tagName === 'SELECT') return true;
        let p = el.parentElement;
        while (p && p !== document.body) {
            if (window.getComputedStyle(p).display === 'none') return false;
            p = p.parentElement;
        }
        return true;
    },

    /**
     * UI: Appends error message and applies bootstrap 'is-invalid' class.
     * Special handling for Select2 containers.
     */
    showError(field, msg) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        const wrap = field.closest('.input-field') || field.parentElement;
        wrap.querySelectorAll('.eta-error').forEach(e => e.remove());
        
        const err = document.createElement('div');
        err.className = 'eta-error';
        err.textContent = msg;

        if (field.tagName === 'SELECT') {
            const s2 = wrap.querySelector('.select2-container');
            if (s2) { 
                s2.classList.add('select2-is-invalid'); 
                s2.insertAdjacentElement('afterend', err); 
                return; 
            }
        }
        wrap.appendChild(err);
    },

    /**
     * UI: Removes error messages and applies 'is-valid' class.
     */
    clearError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        const wrap = field.closest('.input-field') || field.parentElement;
        wrap.querySelectorAll('.eta-error').forEach(e => e.remove());
        const s2 = wrap.querySelector('.select2-container');
        if (s2) { 
            s2.classList.remove('select2-is-invalid'); 
            s2.classList.add('select2-is-valid'); 
        }
    },

    /**
     * Handles specific error UI for Checkbox/Declaration inputs.
     */
    showCheckboxError(cb, msg) {
        const wrap = cb.closest('label') || cb.parentElement;
        if (!wrap.querySelector('.eta-error')) {
            const err = document.createElement('div');
            err.className = 'eta-error';
            err.textContent = msg;
            wrap.insertAdjacentElement('afterend', err);
        }
    },
    clearCheckboxError(cb) {
        const wrap = cb.closest('label') || cb.parentElement;
        const next = wrap.nextElementSibling;
        if (next && next.classList.contains('eta-error')) next.remove();
    },

    /**
     * Attaches event listeners for real-time validation on user interaction.
     * Triggers on 'blur' (leave field) and 'input' (typing).
     */
    attachLiveValidation(cardId) {
        const card = document.getElementById(cardId);
        if (!card) return;
        card.querySelectorAll('input[name], select[name], textarea[name]').forEach(f => {
            if (f.type === 'radio' || f.type === 'checkbox' || f.type === 'file') return;
            
            f.addEventListener('blur', () => {
                const name = f.name.replace(/^t_/, '');
                const val  = f.id === 'phone_field' ? this.getPhoneNumber(f) : f.value;
                const err  = this.validateField(name, val);
                if (err) this.showError(f, err);
                else     this.clearError(f);
            });

            f.addEventListener('input', () => {
                if (f.classList.contains('is-invalid')) {
                    const name = f.name.replace(/^t_/, '');
                    const val  = f.id === 'phone_field' ? this.getPhoneNumber(f) : f.value;
                    if (!this.validateField(name, val)) this.clearError(f);
                }
            });
        });
    },

    /**
     * Map of technical field names to human-friendly labels for error messages.
     */
    label(n) {
        const m = {
            first_name:'First Name', middle_name:'Middle Name', last_name:'Last Name',
            email:'Email Address', phone:'Phone Number',
            travel_date:'Travel Date', purpose_of_visit:'Purpose of Visit',
            date_of_birth:'Date of Birth', gender:'Gender',
            country_of_birth:'Country of Birth', city_of_birth:'City/Town of Birth',
            marital_status:'Marital Status', nationality:'Nationality',
            passport_country:'Country of Passport',
            passport_number:'Passport Number', passport_number_confirm:'Confirm Passport Number',
            passport_issue_date:'Passport Issue Date', passport_expiry:'Passport Expiry Date',
            other_citizenship_country:'Other Country of Citizenship', uci_number:'UCI Number',
            address_line:'Address Line', street_number:'Street Number',
            apartment_number:'Apartment Number',
            country:'Country', state:'State / Province', city:'City / Town',
            postal_code:'Postal Code', occupation:'Occupation',
            job_title:'Job Title', employer_name:'Employer / School Name',
            employer_country:'Employer Country', employer_city:'Employer City',
            start_year:'Start Year',
            visa_refusal_details:'Visa Refusal Details',
            tuberculosis_details:'Tuberculosis Details',
            criminal_details:'Criminal History Details',
            health_condition:'Health Condition',
            billing_first_name:'First Name', billing_last_name:'Last Name',
            billing_email:'Billing Email', billing_address:'Billing Address',
            billing_city:'City', billing_zip:'Zip Code',
        };
        // Fallback: Convert snake_case to Title Case if not in map
        return m[n] || n.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
    }
};