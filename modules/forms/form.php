<?php
session_start();
require_once __DIR__ . '/../../core/bootstrap.php';

$allowedCountries = ['Canada', 'Vietnam', 'UK'];
$forcedCountry = defined('FORM_COUNTRY') ? FORM_COUNTRY : '';
$requestedCountry = trim((string)($_GET['country'] ?? ''));
$formCountry = in_array($forcedCountry, $allowedCountries, true)
    ? $forcedCountry
    : (in_array($requestedCountry, $allowedCountries, true) ? $requestedCountry : 'Canada');
$formContainerId = 'form-container-' . strtolower($formCountry);
$formDisplayTitle = $formCountry . ' Visa Application';
$_SESSION['form_country'] = $formCountry;

$db = getDB();

// DB se data load karo
$countries     = $db->query("SELECT id, name FROM countries WHERE is_active=1 ORDER BY name")->fetchAll();
$nationalities = $countries; // nationalities alag table nahi — countries hi use hoti hai
$purposes      = $db->query("SELECT id, name FROM visit_purposes WHERE is_active=1 ORDER BY id")->fetchAll();

// Occupations table exist kare to load karo
try {
    $occupations = $db->query("SELECT id, name FROM occupations WHERE is_active=1 ORDER BY name")->fetchAll();
} catch(Exception $e) {
    $occupations = [
        ['id'=>1,'name'=>'Employed'],
        ['id'=>2,'name'=>'Self-Employed'],
        ['id'=>3,'name'=>'Student'],
        ['id'=>4,'name'=>'Retired'],
        ['id'=>5,'name'=>'Unemployed'],
        ['id'=>6,'name'=>'Homemaker'],
    ];
}

// Job titles
try {
    $jobTitles = $db->query("SELECT id, name FROM job_titles WHERE is_active=1 ORDER BY name")->fetchAll();
} catch(Exception $e) {
    $jobTitles = [
        ['id'=>1,'name'=>'Engineer'],['id'=>2,'name'=>'Doctor'],['id'=>3,'name'=>'Teacher'],
        ['id'=>4,'name'=>'Manager'],['id'=>5,'name'=>'Accountant'],['id'=>6,'name'=>'Lawyer'],
        ['id'=>7,'name'=>'Business Owner'],['id'=>8,'name'=>'Other'],
    ];
}

// No-job occupations
$noJobOccupations = ['Retired','Unemployed','Homemaker'];

function radioSvg(): string {
    return '<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <circle cx="10" cy="10" r="9" stroke="#00000052" stroke-width="2"/>
        <path d="M10,7C8.34,7 7,8.34 7,10C7,11.66 8.34,13 10,13C11.66,13 13,11.66 13,10C13,8.34 11.66,7 10,7Z" class="inner" stroke="var(--primary-blue)" stroke-width="6" stroke-dasharray="19" stroke-dashoffset="19"/>
        <path d="M10,1C14.97,1 19,5.03 19,10C19,14.97 14.97,19 10,19C5.03,19 1,14.97 1,10C1,5.03 5.03,1 10,1Z" class="outer" stroke="var(--primary-blue)" stroke-width="2" stroke-dasharray="57" stroke-dashoffset="57"/>
    </svg>';
}

function countryOpts(array $countries, string $placeholder = 'Select country...'): string {
    $html = "<option value=''>$placeholder</option>";
    foreach ($countries as $c) {
        $name = htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8');
        $id = htmlspecialchars((string)$c['id'], ENT_QUOTES, 'UTF-8');
        $html .= "<option value='{$name}' data-id='{$id}'>{$name}</option>";
    }
    return $html;
}

function escOpt(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($formDisplayTitle, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="csrf-token" content="<?= csrfToken() ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css">
<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-form-country="<?= htmlspecialchars($formCountry, ENT_QUOTES, 'UTF-8') ?>">

<!-- LOADER -->
<div id="eta-loader">
    <div class="eta-loader-box">
        <div class="eta-spinner"></div>
        <p id="eta-loader-msg">Please wait...</p>
    </div>
</div>
<!-- TOAST -->
<div id="eta-toast"></div>

<!-- NAVBAR -->
<?php require __DIR__ . '/../../includes/navbar.php'; ?>

<!-- FORM -->
<div id="<?= htmlspecialchars($formContainerId, ENT_QUOTES, 'UTF-8') ?>" class="country-form-container country-<?= htmlspecialchars(strtolower($formCountry), ENT_QUOTES, 'UTF-8') ?>">
<section id="stepper" data-country="<?= htmlspecialchars($formCountry, ENT_QUOTES, 'UTF-8') ?>">
<div class="container">
<div class="row justify-content-center">
<div class="col-xl-9 col-lg-10">

    <div class="text-end mb-2">
        <span class="badge bg-primary"><?= htmlspecialchars($formCountry, ENT_QUOTES, 'UTF-8') ?> Form</span>
    </div>

    <!-- Stepper Nav -->
    <div class="stepper-nav">
        <div class="step-item active" id="st-1">1. Personal &amp; Travel Info</div>
        <div class="step-item"        id="st-2">2. Confirm Details</div>
        <div class="step-item"        id="st-3">3. Payment</div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 1.1 — Contact Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card active" id="card-contact">
        <div class="header-title">
            Contact Details
            <span class="person-label" id="contact-person-label">Traveller 1</span>
        </div>
        <div class="body-content">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="input-field">
                        <label>First Name <span>*</span></label>
                        <input type="text" name="t_first_name" class="form-control" placeholder="First Name">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-field">
                        <label>Middle Name <small class="text-muted fw-normal">(Optional)</small></label>
                        <input type="text" name="t_middle_name" class="form-control" placeholder="Middle Name (Optional)">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-field">
                        <label>Last Name <span>*</span></label>
                        <input type="text" name="t_last_name" class="form-control" placeholder="Last Name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Email Address <span>*</span></label>
                        <input type="email" name="t_email" class="form-control" placeholder="email@example.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Phone Number <span>*</span></label>
                        <input type="tel" id="phone_field" name="t_phone" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Date of Intended Travel <span>*</span></label>
                        <input type="date" name="t_travel_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Main Purpose of Visit <span>*</span></label>
                        <select name="t_purpose_of_visit" class="form-select s2">
                            <option value="">Select purpose...</option>
                            <?php foreach($purposes as $p): ?>
                            <option value="<?= escOpt((string)$p['name']) ?>"><?= escOpt((string)$p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Traveller Type — only shown for Traveller 1 -->
                <div class="col-12" id="traveller-type-section">
                    <div class="input-field">
                        <label>Who is travelling?</label>
                        <div class="row g-3 mt-1">
                            <div class="col-sm-6">
                                <label class="choice-card selected" onclick="selectMode('solo',this)">
                                    <input type="radio" name="travel_mode" value="solo" checked style="display:none">
                                    <div class="tick-icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="icon-box"><i class="fas fa-user-circle"></i></div>
                                    <div class="des">
                                        <div class="title-text">Solo</div>
                                        <p>1 Person</p>
                                    </div>
                                </label>
                            </div>
                            <div class="col-sm-6">
                                <label class="choice-card" onclick="selectMode('group',this)">
                                    <input type="radio" name="travel_mode" value="group" style="display:none">
                                    <div class="tick-icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="icon-box"><i class="fas fa-users"></i></div>
                                    <div class="des">
                                        <div class="title-text">Group</div>
                                        <p>Family / Friends</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="group-dropdown" style="display:none;">
                        <label class="form-label fw-bold mb-2">Total Applicants (Including You)</label>
                        <select class="form-select" id="total-travellers-count">
                            <option value="2">2 People (You + 1)</option>
                            <option value="3">3 People (You + 2)</option>
                            <option value="4">4 People (You + 3)</option>
                            <option value="5">5 People (You + 4)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-primary btn-custom" id="btn-contact-next">
                    Continue <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 1.2 — Personal Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-personal">
        <div class="header-title">
            Personal Details
            <span class="person-label" id="personal-person-label">Traveller 1</span>
        </div>
        <div class="body-content">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Date of Birth <span>*</span></label>
                        <input type="date" name="t_date_of_birth" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Gender <span>*</span></label>
                        <div class="cntr mt-2">
                            <label class="btn-radio">
                                <input type="radio" name="t_gender" value="male"><?= radioSvg() ?><span>Male</span>
                            </label>
                            <label class="btn-radio">
                                <input type="radio" name="t_gender" value="female"><?= radioSvg() ?><span>Female</span>
                            </label>
                            <label class="btn-radio">
                                <input type="radio" name="t_gender" value="other"><?= radioSvg() ?><span>Other</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Country of Birth <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-cob" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                            <input type="hidden" name="t_country_of_birth" id="sdc-cob-val">
                            <input type="hidden" id="sdc-cob-id">
                            <div class="sdc-list" id="sdc-cob-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>City / Town of Birth <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-cob-city" class="sdc-input" placeholder="Select country first..." autocomplete="off">
                            <input type="hidden" name="t_city_of_birth" id="sdc-cob-city-val">
                            <div class="sdc-list" id="sdc-cob-city-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Marital Status <span>*</span></label>
                        <select name="t_marital_status" class="form-select s2">
                            <option value="">Select status...</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Common-Law">Common-Law</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Country of Citizenship / Nationality <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-nat" class="sdc-input" placeholder="Type to search nationality..." autocomplete="off">
                            <input type="hidden" name="t_nationality" id="sdc-nat-val">
                            <div class="sdc-list" id="sdc-nat-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-light btn-custom" onclick="navTo('card-contact')">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary btn-custom" id="btn-personal-next">
                    Next <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 1.3 — Passport Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-passport">
        <div class="header-title">
            Passport Details
            <span class="person-label" id="passport-person-label">Traveller 1</span>
        </div>
        <div class="body-content">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Country of Passport <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-ppc" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                            <input type="hidden" name="t_passport_country" id="sdc-ppc-val">
                            <div class="sdc-list" id="sdc-ppc-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Passport Number <span>*</span></label>
                        <input type="text" name="t_passport_number" id="passport-number-1" class="form-control" placeholder="e.g. A1234567" style="text-transform:uppercase" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Confirm Passport Number <span>*</span></label>
                        <input type="text" name="t_passport_number_confirm" id="passport-number-2" class="form-control" placeholder="Re-enter passport number" style="text-transform:uppercase" autocomplete="off" onpaste="return false">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Passport Date of Issue <span>*</span></label>
                        <input type="date" name="t_passport_issue_date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Passport Expiry Date <span>*</span></label>
                        <input type="date" name="t_passport_expiry" class="form-control">
                    </div>
                </div>

                <!-- Conditional 1: Dual Citizenship -->
                <div class="col-12">
                    <div class="input-field">
                        <label>Are you currently a citizen or national of another country? <span>*</span></label>
                        <div class="cntr mt-2">
                            <label class="btn-radio">
                                <input type="radio" name="t_dual_citizen" value="1" class="cond-trigger" data-show="dual-citizen-fields"><?= radioSvg() ?><span>Yes</span>
                            </label>
                            <label class="btn-radio">
                                <input type="radio" name="t_dual_citizen" value="0" class="cond-trigger" data-hide="dual-citizen-fields" checked><?= radioSvg() ?><span>No</span>
                            </label>
                        </div>
                    </div>
                    <div id="dual-citizen-fields" class="conditional-box" style="display:none">
                        <div class="input-field">
                            <label>Country of Citizenship / Nationality <span>*</span></label>
                            <div class="sdc-wrap">
                                <input type="text" id="sdc-occ" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                                <input type="hidden" name="t_other_citizenship_country" id="sdc-occ-val">
                                <div class="sdc-list" id="sdc-occ-list"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditional 2: Previous Canada Application -->
                <div class="col-12">
                    <div class="input-field">
                        <label>Have you previously applied to enter or stay in Canada? <span>*</span></label>
                        <div class="cntr mt-2">
                            <label class="btn-radio">
                                <input type="radio" name="t_prev_canada_app" value="1" class="cond-trigger" data-show="prev-canada-fields"><?= radioSvg() ?><span>Yes</span>
                            </label>
                            <label class="btn-radio">
                                <input type="radio" name="t_prev_canada_app" value="0" class="cond-trigger" data-hide="prev-canada-fields" checked><?= radioSvg() ?><span>No</span>
                            </label>
                        </div>
                    </div>
                    <div id="prev-canada-fields" class="conditional-box" style="display:none">
                        <div class="input-field">
                            <label>UCI / Previous Visa or Licence Number <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="t_uci_number" class="form-control" placeholder="e.g. 0000-0000">
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-light btn-custom" onclick="navTo('card-personal')">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary btn-custom" id="btn-passport-next">
                    Next <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 1.4 — Residential Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-residential">
        <div class="header-title">
            Residential Details
            <span class="person-label" id="residential-person-label">Traveller 1</span>
        </div>
        <div class="body-content">
            <div class="row g-3">
                <div class="col-12">
                    <div class="input-field">
                        <label>Address Line <span>*</span></label>
                        <input type="text" name="t_address_line" class="form-control" placeholder="House / Building name or number">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Street Number <span>*</span></label>
                        <input type="text" name="t_street_number" class="form-control" placeholder="Street name">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Apartment Number <small class="text-muted">(Optional)</small></label>
                        <input type="text" name="t_apartment_number" class="form-control" placeholder="Apt / Suite number">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Country <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-country" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                            <input type="hidden" name="t_country" id="sdc-country-val">
                            <input type="hidden" id="sdc-country-id">
                            <div class="sdc-list" id="sdc-country-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>State / Province <small class="text-muted">(Optional)</small></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-state" class="sdc-input" placeholder="Select country first..." autocomplete="off">
                            <input type="hidden" name="t_state" id="sdc-state-val">
                            <input type="hidden" id="sdc-state-id">
                            <div class="sdc-list" id="sdc-state-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>City / Town <span>*</span></label>
                        <div class="sdc-wrap">
                            <input type="text" id="sdc-city" class="sdc-input" placeholder="Type city or select from list..." autocomplete="off">
                            <input type="hidden" name="t_city" id="sdc-city-val">
                            <div class="sdc-list" id="sdc-city-list"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Postal / Zip Code <span>*</span></label>
                        <input type="text" name="t_postal_code" class="form-control" placeholder="e.g. 110001">
                    </div>
                </div>

                <!-- Occupation -->
                <div class="col-12"><hr class="hr"></div>
                <div class="col-md-6">
                    <div class="input-field">
                        <label>Occupation <span>*</span></label>
                        <select name="t_occupation" id="occupation-select" class="form-select s2">
                            <option value="">Select occupation...</option>
                            <?php foreach($occupations as $o): ?>
                            <option value="<?= escOpt((string)$o['name']) ?>"><?= escOpt((string)$o['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Job fields — shown only if occupation requires them -->
                <div class="col-12" id="job-detail-fields" style="display:none">
                    <div class="row g-3 p-3 rounded-3" style="background:#f8fafc;border:1px dashed #cbd5e1">
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Job Title <span>*</span></label>
                                <select name="t_job_title" class="form-select s2">
                                    <option value="">Select job title...</option>
                                    <?php foreach($jobTitles as $j): ?>
                                    <option value="<?= escOpt((string)$j['name']) ?>"><?= escOpt((string)$j['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Employer or School Name <span>*</span></label>
                                <input type="text" name="t_employer_name" class="form-control" placeholder="Company / School name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Country <span>*</span></label>
                                <div class="sdc-wrap">
                                    <input type="text" id="sdc-ec" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                                    <input type="hidden" name="t_employer_country" id="sdc-ec-val">
                                    <input type="hidden" id="sdc-ec-id">
                                    <div class="sdc-list" id="sdc-ec-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>State / Province <small class="text-muted">(Optional)</small></label>
                                <div class="sdc-wrap">
                                    <input type="text" id="sdc-es" class="sdc-input" placeholder="Select country first..." autocomplete="off">
                                    <input type="hidden" id="sdc-es-id">
                                    <div class="sdc-list" id="sdc-es-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>City <span>*</span></label>
                                <div class="sdc-wrap">
                                    <input type="text" id="sdc-ecity" class="sdc-input" placeholder="Type city or select from list..." autocomplete="off">
                                    <input type="hidden" name="t_employer_city" id="sdc-ecity-val">
                                    <div class="sdc-list" id="sdc-ecity-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Start Year <span>*</span></label>
                                <select name="t_start_year" class="form-select s2">
                                    <option value="">Select year...</option>
                                    <?php for($y=date('Y'); $y>=1970; $y--): ?>
                                    <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-light btn-custom" onclick="navTo('card-passport')">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary btn-custom" id="btn-residential-next">
                    Continue to Declaration <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 1.5 — Background Questions
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-background">
        <div class="header-title">
            Background Questions
            <span class="person-label" id="background-person-label">Traveller 1</span>
        </div>
        <div class="body-content">

            <!-- Q1: Visa Refusal -->
            <div class="bg-question-block">
                <label class="bg-question-label">Have you ever been refused a visa or eTA for Canada, denied entry, or ordered to leave? <span>*</span></label>
                <div class="cntr mt-2">
                    <label class="btn-radio">
                        <input type="radio" name="t_visa_refusal" value="1" class="cond-trigger" data-show="visa-refusal-details"><?= radioSvg() ?><span>Yes</span>
                    </label>
                    <label class="btn-radio">
                        <input type="radio" name="t_visa_refusal" value="0" class="cond-trigger" data-hide="visa-refusal-details" checked><?= radioSvg() ?><span>No</span>
                    </label>
                </div>
                <div id="visa-refusal-details" class="conditional-box" style="display:none">
                    <label class="form-label mt-2">Please provide details <span>*</span></label>
                    <textarea name="t_visa_refusal_details" class="form-control" rows="3" placeholder="Explain the circumstances..."></textarea>
                </div>
            </div>
            <hr class="hr">

            <!-- Q2: Tuberculosis -->
            <div class="bg-question-block">
                <label class="bg-question-label">Have you been diagnosed with tuberculosis or been in close contact with someone with tuberculosis in the last two years? <span>*</span></label>
                <div class="cntr mt-2">
                    <label class="btn-radio">
                        <input type="radio" name="t_tuberculosis" value="1" class="cond-trigger" data-show="tuberculosis-details"><?= radioSvg() ?><span>Yes</span>
                    </label>
                    <label class="btn-radio">
                        <input type="radio" name="t_tuberculosis" value="0" class="cond-trigger" data-hide="tuberculosis-details" checked><?= radioSvg() ?><span>No</span>
                    </label>
                </div>
                <div id="tuberculosis-details" class="conditional-box" style="display:none">
                    <label class="form-label mt-2">Please provide details <span>*</span></label>
                    <textarea name="t_tuberculosis_details" class="form-control" rows="3" placeholder="Explain the circumstances..."></textarea>
                </div>
            </div>
            <hr class="hr">

            <!-- Q3: Criminal History -->
            <div class="bg-question-block">
                <label class="bg-question-label">Have you ever committed, been arrested for, charged with, or convicted of any criminal offence? <span>*</span></label>
                <div class="cntr mt-2">
                    <label class="btn-radio">
                        <input type="radio" name="t_criminal_history" value="1" class="cond-trigger" data-show="criminal-details"><?= radioSvg() ?><span>Yes</span>
                    </label>
                    <label class="btn-radio">
                        <input type="radio" name="t_criminal_history" value="0" class="cond-trigger" data-hide="criminal-details" checked><?= radioSvg() ?><span>No</span>
                    </label>
                </div>
                <div id="criminal-details" class="conditional-box" style="display:none">
                    <label class="form-label mt-2">Please provide details <span>*</span></label>
                    <textarea name="t_criminal_details" class="form-control" rows="3" placeholder="Explain the circumstances..."></textarea>
                </div>
            </div>
            <hr class="hr">

            <!-- Q4: Health Conditions -->
            <div class="bg-question-block">
                <label class="bg-question-label">Do you have one of these health conditions? <span>*</span></label>
                <select name="t_health_condition" class="form-select s2 mt-2">
                    <option value="">Select health condition...</option>
                    <option value="None">None</option>
                    <option value="Diabetes">Diabetes</option>
                    <option value="Hypertension">Hypertension</option>
                    <option value="Heart Disease">Heart Disease</option>
                    <option value="Cancer">Cancer</option>
                    <option value="Respiratory Condition">Respiratory Condition</option>
                    <option value="Mental Health Condition">Mental Health Condition</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <hr class="hr">

            <!-- Criminal Warning Notice -->
            <div class="alert alert-warning" style="font-size:13px;line-height:1.7;border-left:4px solid #f59e0b;background:#fffbeb;border-radius:8px;padding:16px">
                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                <strong>Important Notice:</strong> If you have committed or been convicted of a crime, you may not be eligible for a Canada eTA. This includes both minor and serious crimes, such as theft, assault, manslaughter, dangerous driving, driving while under the influence of drugs or alcohol, possession of or trafficking in drugs or controlled substances.
            </div>

            <!-- Privacy Notice -->
            <div class="alert alert-info" style="font-size:12px;line-height:1.7;border-left:4px solid #3b82f6;background:#eff6ff;border-radius:8px;padding:16px">
                <strong>Privacy Notice:</strong> Information provided to CIC is collected under the authority of the Immigration and Refugee Protection Act (IRPA) to determine admissibility to Canada. Information provided may be shared with other Canadian government institutions, such as, but not limited to, the Canada Border Services Agency (CBSA), the Royal Canadian Mounted Police (RCMP), the Canadian Security Intelligence Service (CSIS), the Department of Foreign Affairs, Trade and Development (DFATD), Employment and Social Development Canada (ESDC), the Canada Revenue Agency (CRA), provincial and territorial governments and foreign governments in accordance with subsection 8(2) of the Privacy Act.
            </div>

            <hr class="hr">

            <!-- Declaration of Applicant -->
            <h5 class="fw-bold mb-3" style="color:var(--primary-blue)"><i class="fas fa-signature me-2"></i>Declaration of Applicant</h5>
            <div class="declaration-check">
                <label class="d-flex align-items-start gap-3 p-3 rounded-3 border mb-3" style="cursor:pointer;background:#f8fafc">
                    <input type="checkbox" name="t_decl_accurate" class="decl-checkbox mt-1" style="width:20px;height:20px;accent-color:var(--primary-blue)">
                    <span style="font-size:14px;line-height:1.6">
                        <strong>I confirm</strong> that all the information provided above is true and accurate.
                    </span>
                </label>
                <label class="d-flex align-items-start gap-3 p-3 rounded-3 border" style="cursor:pointer;background:#f8fafc">
                    <input type="checkbox" name="t_decl_terms" class="decl-checkbox mt-1" style="width:20px;height:20px;accent-color:var(--primary-blue)">
                    <span style="font-size:14px;line-height:1.6">
                        <strong>I have read and agree</strong> to the
                        <a href="#" target="_blank">Terms and Conditions</a>,
                        <a href="#" target="_blank">Privacy Policy</a>, and
                        <a href="#" target="_blank">Refund Policy</a>.
                    </span>
                </label>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button class="btn btn-light btn-custom" onclick="navTo('card-residential')">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary btn-custom" id="btn-declaration-save">
                    Confirm &amp; Save <i class="fas fa-check ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         TRAVELLER ADDED SCREEN (Group only)
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-traveller-added">
        <div class="body-content text-center py-5">
            <div style="width:80px;height:80px;background:#eef2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
                <i class="fas fa-user-check fa-2x text-primary"></i>
            </div>
            <h3 class="fw-bold mb-2">Traveller Added!</h3>
            <p class="text-muted mb-1" id="traveller-added-msg">Traveller 1 details saved successfully.</p>
            <p class="text-muted" id="travellers-remaining-msg"></p>
            <button class="btn btn-primary btn-custom btn-lg mt-4 px-5" id="btn-add-next-traveller">
                Add Next Traveller <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 2 — Confirm Your Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-confirm">
        <div class="header-title">Confirm Your Details</div>
        <div class="body-content">
            <div id="ref-display" class="alert alert-success mb-4" style="display:none">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Application Saved!</strong> Reference: <strong id="app-ref-number"></strong>
            </div>

            <!-- Travellers list — JS se populate hoga -->
            <div id="travellers-review-list">
                <p class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin me-2"></i> Loading traveller details...
                </p>
            </div>

            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                Neeche di gayi tamam details ko dhyan se verify karein. Agar kisi traveller ki details edit karni ho to us card par click karein.
            </div>

            <!-- Processing Plan -->
            <div class="plan-box">
                <h4>Select Processing Plan</h4>
                <div class="price-wrap">
                    <label class="price-box">
                        <input type="radio" name="plan" value="standard" checked>
                        <div class="price">Standard</div>
                        <div class="duration">24 – 48 Hours</div>
                        <div class="tick"><i class="fas fa-check" style="font-size:12px"></i></div>
                    </label>
                    <label class="price-box">
                        <input type="radio" name="plan" value="priority">
                        <div class="price">Priority</div>
                        <div class="duration">Within 6 Hours</div>
                        <div class="tick"><i class="fas fa-check" style="font-size:12px"></i></div>
                    </label>
                </div>
            </div>

            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="confirm-details-check">
                <label class="form-check-label" for="confirm-details-check">
                    Main confirm karta/karti hoon ke meri tamam details sahi hain aur main payment proceed karna chahta/chahti hoon.
                </label>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
                <button class="btn btn-light btn-custom" id="btn-confirm-back">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-outline-primary btn-custom" id="btn-add-another-traveller">
                    <i class="fas fa-user-plus me-2"></i> Add Another Traveler
                </button>
                <button class="btn btn-primary btn-custom" id="btn-confirm-pay-now">
                    Confirm &amp; Continue to Payment <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         STEP 3 — Payment Details
    ═════════════════════════════════════════════════════ -->
    <div class="mini-card" id="card-payment">
        <div class="header-title">Payment Details</div>
        <div class="body-content">

            <!-- Card Info -->
            <div class="review-section mb-4">
                <div class="review-title-head">
                    <h6><i class="fas fa-credit-card me-2"></i> Payment Information</h6>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="input-field">
                                <label>Card Holder Name <span>*</span></label>
                                <input type="text" name="card_holder_name" class="form-control" placeholder="Name as on card">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="input-field">
                                <label>Card Number <span>*</span></label>
                                <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" id="card-number-input">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Expiry Date <span>*</span></label>
                                <input type="text" name="card_expiry" class="form-control" placeholder="MM / YY" maxlength="7" id="card-expiry-input">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>CVV <span>*</span></label>
                                <input type="password" name="card_cvv" class="form-control" placeholder="•••" maxlength="4" id="card-cvv-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Info -->
            <div class="review-section">
                <div class="review-title-head">
                    <h6><i class="fas fa-map-marker-alt me-2"></i> Billing Information</h6>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>First Name <span>*</span></label>
                                <input type="text" name="billing_first_name" class="form-control" placeholder="First name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Last Name <span>*</span></label>
                                <input type="text" name="billing_last_name" class="form-control" placeholder="Last name">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="input-field">
                                <label>Billing Email</label>
                                <input type="email" name="billing_email" class="form-control" placeholder="billing@example.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="input-field">
                                <label>Address <span>*</span></label>
                                <input type="text" name="billing_address" class="form-control" placeholder="Street address">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Country</label>
                                <div class="sdc-wrap">
                                    <input type="text" id="sdc-bc" class="sdc-input" placeholder="Type to search country..." autocomplete="off">
                                    <input type="hidden" name="billing_country" id="sdc-bc-val">
                                    <input type="hidden" id="sdc-bc-id">
                                    <div class="sdc-list" id="sdc-bc-list"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>State / Province</label>
                                <select name="billing_state" id="billing-state-field" class="form-select s2">
                                    <option value="">Select country first...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>City <span>*</span></label>
                                <input type="text" name="billing_city" class="form-control" placeholder="City" list="billing-city-options">
                                <datalist id="billing-city-options"></datalist>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-field">
                                <label>Zip / Postal Code <span>*</span></label>
                                <input type="text" name="billing_zip" class="form-control" placeholder="Zip code">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <button class="btn btn-light btn-custom" id="btn-payment-back">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-success btn-custom px-5" id="submit-payment-btn">
                    <i class="fas fa-check-circle me-2"></i> Submit
                </button>
            </div>
        </div>
    </div>

</div><!-- col -->
</div><!-- row -->
</div><!-- container -->
</section>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div>
            <div class="footer-logo">YourBrand</div>
            <p class="footer-text">Fast, modern and scalable web solutions.</p>
        </div>
        <div>
            <h4 class="footer-title">Company</h4>
            <ul class="footer-links">
                <li><a href="#">About</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Support</h4>
            <ul class="footer-links">
                <li><a href="#">Help Center</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms &amp; Conditions</a></li>
            </ul>
        </div>
        <div>
            <h4 class="footer-title">Newsletter</h4>
            <div class="newsletter">
                <input type="email" placeholder="Your email">
                <button>Subscribe</button>
            </div>
        </div>
    </div>
    <div class="footer-bottom">© <?= date('Y') ?> YourBrand. All rights reserved.</div>
</footer>

<!-- JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js"></script>

<script>
// ═══════════════════════════════════════════════════════════
//  INLINE JS — UI Interactions
// ═══════════════════════════════════════════════════════════

// ── Solo / Group toggle ───────────────────────────────────
function selectMode(mode, el) {
    document.querySelectorAll('.choice-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
    document.getElementById('group-dropdown').style.display = mode === 'group' ? 'block' : 'none';
}

// ── Conditional radio show/hide ───────────────────────────
document.querySelectorAll('.cond-trigger').forEach(r => {
    r.addEventListener('change', function() {
        const show = this.dataset.show;
        const hide = this.dataset.hide;
        if (show) { const el = document.getElementById(show); if(el) el.style.display='block'; }
        if (hide) { const el = document.getElementById(hide); if(el) el.style.display='none';  }
    });
});

// ── Occupation → show/hide job fields ────────────────────
// NOTE: Select2 fires native 'change' on the original <select>
// We use both native listener AND $(document).on for Select2
const NO_JOB_LIST = ['Retired','Unemployed','Homemaker'];
function handleOccupationChange(val) {
    const jobFields = document.getElementById('job-detail-fields');
    if (!jobFields) return;
    if (!val || NO_JOB_LIST.includes(val)) {
        jobFields.style.display = 'none';
        // Clear job field values so they don't get validated
        jobFields.querySelectorAll('input,select,textarea').forEach(f => {
            f.value = '';
            if (window.$ && $(f).data('select2')) $(f).val('').trigger('change');
        });
    } else {
        jobFields.style.display = 'block';
    }
}

// ── Card number formatting ────────────────────────────────
document.getElementById('card-number-input')?.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'').substring(0,16);
    this.value = v.replace(/(.{4})/g,'$1 ').trim();
});
document.getElementById('card-expiry-input')?.addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    if (v.length >= 2) v = v.substring(0,2) + ' / ' + v.substring(2,4);
    this.value = v;
});
document.getElementById('card-cvv-input')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').substring(0,4);
});

// ── intlTelInput init ─────────────────────────────────────
window.itiInstances = {};
const phoneEl = document.getElementById('phone_field');
if (phoneEl) {
    const iti = window.intlTelInput(phoneEl, {
        initialCountry: 'in',
        separateDialCode: true,
        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.js'
    });
    window.itiInstances['phone_field'] = iti;
    phoneEl._itiInstance = iti;
}

// ═══════════════════════════════════════════════════════════
//  Select2 — only for non-geo dropdowns
// ═══════════════════════════════════════════════════════════
$(document).ready(function() {
    // Select2 only for simple dropdowns (not country/state/city)
    $('.s2').select2({
        placeholder: 'Select...',
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: false
    });

    // Occupation change
    $(document).on('change', '#occupation-select', function() {
        handleOccupationChange($(this).val());
    });
});

// ═══════════════════════════════════════════════════════════
//  SDC — Searchable Dropdown Combobox
//  Works for Country → State → City chain
//  - Type to filter
//  - Click to select
//  - If no match: keep typed value (free text allowed for city)
// ═══════════════════════════════════════════════════════════

// All countries loaded from PHP into JS array
const SDC_COUNTRIES = <?= json_encode(array_map(function($c) {
    return ['id' => (string)$c['id'], 'name' => $c['name']];
}, $countries), JSON_UNESCAPED_UNICODE) ?>;

// ── SDC Core ─────────────────────────────────────────────
function sdcInit(inputId, listId, hiddenValId, hiddenIdId, opts) {
    const inp    = document.getElementById(inputId);
    const list   = document.getElementById(listId);
    const hidVal = document.getElementById(hiddenValId);
    const hidId  = hiddenIdId ? document.getElementById(hiddenIdId) : null;
    if (!inp || !list) return;

    let allItems = opts || [];       // array of {id, name}
    let highlighted = -1;

    function render(items) {
        list.innerHTML = '';
        highlighted = -1;
        if (!items.length) {
            list.innerHTML = '<div class="sdc-item sdc-empty">No results found</div>';
            list.classList.add('open');
            return;
        }
        items.forEach(function(item, i) {
            const div = document.createElement('div');
            div.className = 'sdc-item';
            div.textContent = item.name;
            div.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectItem(item);
            });
            list.appendChild(div);
        });
        list.classList.add('open');
    }

    function selectItem(item) {
        inp.value    = item.name;
        hidVal.value = item.name;
        if (hidId) hidId.value = item.id || '';
        list.classList.remove('open');
        highlighted = -1;
        // Trigger custom event for chaining
        inp.dispatchEvent(new CustomEvent('sdc:select', { detail: item, bubbles: true }));
    }

    function filter(term) {
        if (!term) return allItems.slice(0, 80); // show first 80 if no term
        const t = term.toLowerCase();
        return allItems.filter(function(i) {
            return i.name.toLowerCase().indexOf(t) > -1;
        }).slice(0, 80);
    }

    inp.addEventListener('input', function() {
        hidVal.value = inp.value; // free text fallback
        if (hidId) hidId.value = '';
        render(filter(inp.value));
    });

    inp.addEventListener('focus', function() {
        render(filter(inp.value));
    });

    inp.addEventListener('keydown', function(e) {
        const items = list.querySelectorAll('.sdc-item:not(.sdc-empty)');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlighted = Math.min(highlighted + 1, items.length - 1);
            items.forEach(function(el, i) { el.classList.toggle('highlighted', i === highlighted); });
            if (items[highlighted]) items[highlighted].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlighted = Math.max(highlighted - 1, 0);
            items.forEach(function(el, i) { el.classList.toggle('highlighted', i === highlighted); });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlighted >= 0 && items[highlighted]) items[highlighted].dispatchEvent(new MouseEvent('mousedown'));
        } else if (e.key === 'Escape') {
            list.classList.remove('open');
        }
    });

    document.addEventListener('click', function(e) {
        if (!inp.contains(e.target) && !list.contains(e.target)) {
            list.classList.remove('open');
        }
    });

    // Public API
    inp._sdcSetItems = function(newItems) {
        allItems = newItems;
    };
    inp._sdcReset = function(placeholder, disabled) {
        inp.value    = '';
        if (hidVal) hidVal.value = '';
        if (hidId)  hidId.value  = '';
        list.classList.remove('open');
        allItems = [];
        inp.placeholder = placeholder || '';
        inp.disabled    = disabled !== false;
    };
    inp._sdcSetItems(allItems);
}

// ── Init ALL Country SDC instances ───────────────────────
// Residential country/state/city chain
sdcInit('sdc-country', 'sdc-country-list', 'sdc-country-val', 'sdc-country-id', SDC_COUNTRIES);
sdcInit('sdc-state',   'sdc-state-list',   'sdc-state-val',   'sdc-state-id', []);
sdcInit('sdc-city',    'sdc-city-list',    'sdc-city-val',    null,           []);

// Personal details
sdcInit('sdc-cob',  'sdc-cob-list',  'sdc-cob-val',  'sdc-cob-id', SDC_COUNTRIES);  // country of birth
sdcInit('sdc-cob-city', 'sdc-cob-city-list', 'sdc-cob-city-val', null, []);
sdcInit('sdc-nat',  'sdc-nat-list',  'sdc-nat-val',  null, SDC_COUNTRIES);  // nationality

// Passport
sdcInit('sdc-ppc',  'sdc-ppc-list',  'sdc-ppc-val',  null, SDC_COUNTRIES);  // passport country
sdcInit('sdc-occ',  'sdc-occ-list',  'sdc-occ-val',  null, SDC_COUNTRIES);  // other citizenship

// Job / employer
sdcInit('sdc-ec',   'sdc-ec-list',   'sdc-ec-val',   'sdc-ec-id', SDC_COUNTRIES);  // employer country
sdcInit('sdc-es',   'sdc-es-list',   null,           'sdc-es-id', []);
sdcInit('sdc-ecity','sdc-ecity-list','sdc-ecity-val',null,         []);

// Billing
sdcInit('sdc-bc',   'sdc-bc-list',   'sdc-bc-val',   'sdc-bc-id', SDC_COUNTRIES);  // billing country

// Country selected → load states
function loadStatesForCountry(countryId) {
    const stateInp = document.getElementById('sdc-state');
    const cityInp  = document.getElementById('sdc-city');
    if (!stateInp || !cityInp) return;

    stateInp._sdcReset('Loading states...', false);
    stateInp.disabled = true;
    cityInp._sdcReset('Select state or type city...', false);
    cityInp.disabled = true;

    if (!countryId) {
        stateInp._sdcReset('Type your state / province...', false);
        stateInp._sdcSetItems([]);
        stateInp.disabled = false;
        cityInp._sdcReset('Type your city / town...', false);
        cityInp.disabled = false;
        return;
    }

    fetch('modules/ajax/get_states.php?country_id=' + encodeURIComponent(countryId))
        .then(function(r) { return r.json(); })
        .then(function(states) {
            if (states.length) {
                const items = states.map(function(s) { return { id: s.id, name: s.name }; });
                stateInp._sdcSetItems(items);
                stateInp.placeholder = 'Type to search state...';
                stateInp.disabled = false;
            } else {
                stateInp._sdcReset('Type your state / province...', false);
                stateInp._sdcSetItems([]);
                stateInp.disabled = false;
            }
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
            cityInp._sdcSetItems([]);
        })
        .catch(function() {
            stateInp._sdcReset('Type your state / province...', false);
            stateInp._sdcSetItems([]);
            stateInp.disabled = false;
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
        });
}

function resolveCountryIdFromInput() {
    const countryInp = document.getElementById('sdc-country');
    const countryIdHidden = document.getElementById('sdc-country-id');
    const countryValHidden = document.getElementById('sdc-country-val');
    if (!countryInp || !countryIdHidden || !countryValHidden) return '';

    const typed = (countryInp.value || '').trim();
    if (!typed) {
        countryIdHidden.value = '';
        countryValHidden.value = '';
        return '';
    }

    const match = SDC_COUNTRIES.find(function(c) {
        return c.name.toLowerCase() === typed.toLowerCase();
    });

    if (match) {
        countryInp.value = match.name;
        countryValHidden.value = match.name;
        countryIdHidden.value = match.id;
        return match.id;
    }

    countryIdHidden.value = '';
    countryValHidden.value = typed;
    return '';
}

function resolveCountryIdByInput(inputId, hiddenValId, hiddenIdId) {
    const countryInp = document.getElementById(inputId);
    const countryValHidden = hiddenValId ? document.getElementById(hiddenValId) : null;
    const countryIdHidden = hiddenIdId ? document.getElementById(hiddenIdId) : null;
    if (!countryInp) return '';

    const typed = (countryInp.value || '').trim();
    if (!typed) {
        if (countryValHidden) countryValHidden.value = '';
        if (countryIdHidden) countryIdHidden.value = '';
        return '';
    }

    const match = SDC_COUNTRIES.find(function(c) {
        return c.name.toLowerCase() === typed.toLowerCase();
    });

    if (match) {
        countryInp.value = match.name;
        if (countryValHidden) countryValHidden.value = match.name;
        if (countryIdHidden) countryIdHidden.value = match.id;
        return match.id;
    }

    if (countryValHidden) countryValHidden.value = typed;
    if (countryIdHidden) countryIdHidden.value = '';
    return '';
}

document.getElementById('sdc-country')?.addEventListener('sdc:select', function(e) {
    loadStatesForCountry(e.detail.id);
});
document.getElementById('sdc-country')?.addEventListener('blur', function() {
    loadStatesForCountry(resolveCountryIdFromInput());
});
document.getElementById('sdc-country')?.addEventListener('change', function() {
    loadStatesForCountry(resolveCountryIdFromInput());
});
// State selected → load cities
document.getElementById('sdc-state')?.addEventListener('sdc:select', function(e) {
    const stateId = e.detail.id;
    const cityInp = document.getElementById('sdc-city');

    cityInp._sdcReset('Loading cities...', false);
    cityInp.disabled = true;

    if (!stateId) {
        cityInp._sdcReset('Type your city / town...', false);
        cityInp.disabled = false;
        return;
    }

    fetch('modules/ajax/get_cities.php?state_id=' + encodeURIComponent(stateId))
        .then(function(r) { return r.json(); })
        .then(function(cities) {
            const items = cities.map(function(c) { return { id: c.name, name: c.name }; });
            cityInp._sdcSetItems(items);
            cityInp.placeholder = cities.length ? 'Type to search or select city...' : 'Type your city / town...';
            cityInp.disabled = false;
        })
        .catch(function() {
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
        });
});

// City: free text always allowed — update hidden val on input
document.getElementById('sdc-city')?.addEventListener('input', function() {
    document.getElementById('sdc-city-val').value = this.value;
});

function loadEmployerStates(countryId) {
    const stateInp = document.getElementById('sdc-es');
    const cityInp  = document.getElementById('sdc-ecity');
    if (!stateInp || !cityInp) return;

    stateInp._sdcReset('Loading states...', false);
    stateInp.disabled = true;
    cityInp._sdcReset('Select state or type city...', false);
    cityInp.disabled = true;

    if (!countryId) {
        stateInp._sdcReset('Type your state / province...', false);
        stateInp._sdcSetItems([]);
        stateInp.disabled = false;
        cityInp._sdcReset('Type your city / town...', false);
        cityInp.disabled = false;
        return;
    }

    fetch('modules/ajax/get_states.php?country_id=' + encodeURIComponent(countryId))
        .then(function(r) { return r.json(); })
        .then(function(states) {
            const items = Array.isArray(states) ? states.map(function(s) { return { id: s.id, name: s.name }; }) : [];
            if (items.length) {
                stateInp._sdcSetItems(items);
                stateInp.placeholder = 'Type to search state...';
                stateInp.disabled = false;
            } else {
                stateInp._sdcReset('Type your state / province...', false);
                stateInp._sdcSetItems([]);
                stateInp.disabled = false;
            }
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
            cityInp._sdcSetItems([]);
        })
        .catch(function() {
            stateInp._sdcReset('Type your state / province...', false);
            stateInp._sdcSetItems([]);
            stateInp.disabled = false;
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
        });
}

function loadEmployerCities(stateId) {
    const cityInp = document.getElementById('sdc-ecity');
    if (!cityInp) return;

    cityInp._sdcReset('Loading cities...', false);
    cityInp.disabled = true;

    if (!stateId) {
        cityInp._sdcReset('Type your city / town...', false);
        cityInp.disabled = false;
        return;
    }

    fetch('modules/ajax/get_cities.php?state_id=' + encodeURIComponent(stateId))
        .then(function(r) { return r.json(); })
        .then(function(cities) {
            const items = Array.isArray(cities) ? cities.map(function(c) { return { id: c.name, name: c.name }; }) : [];
            cityInp._sdcSetItems(items);
            cityInp.placeholder = items.length ? 'Type to search or select city...' : 'Type your city / town...';
            cityInp.disabled = false;
        })
        .catch(function() {
            cityInp._sdcReset('Type your city / town...', false);
            cityInp.disabled = false;
        });
}

document.getElementById('sdc-ec')?.addEventListener('sdc:select', function(e) {
    loadEmployerStates(e.detail.id);
});
document.getElementById('sdc-ec')?.addEventListener('blur', function() {
    loadEmployerStates(resolveCountryIdByInput('sdc-ec', 'sdc-ec-val', 'sdc-ec-id'));
});
document.getElementById('sdc-ec')?.addEventListener('change', function() {
    loadEmployerStates(resolveCountryIdByInput('sdc-ec', 'sdc-ec-val', 'sdc-ec-id'));
});
document.getElementById('sdc-es')?.addEventListener('sdc:select', function(e) {
    loadEmployerCities(e.detail.id);
});
document.getElementById('sdc-ecity')?.addEventListener('input', function() {
    document.getElementById('sdc-ecity-val').value = this.value;
});

function loadBirthCities(countryId) {
    const cityInp = document.getElementById('sdc-cob-city');
    if (!cityInp) return;

    cityInp._sdcReset('Loading cities...', false);
    cityInp.disabled = true;

    if (!countryId) {
        cityInp._sdcReset('Type city / town of birth...', false);
        cityInp._sdcSetItems([]);
        cityInp.disabled = false;
        return;
    }

    fetch('modules/ajax/get_cities_by_country.php?country_id=' + encodeURIComponent(countryId))
        .then(function(r) { return r.json(); })
        .then(function(cities) {
            const items = Array.isArray(cities) ? cities.map(function(c) { return { id: c.id || c.name, name: c.name }; }) : [];
            cityInp._sdcSetItems(items);
            cityInp.placeholder = items.length ? 'Type to search or select city...' : 'Type city / town of birth...';
            cityInp.disabled = false;
        })
        .catch(function() {
            cityInp._sdcReset('Type city / town of birth...', false);
            cityInp._sdcSetItems([]);
            cityInp.disabled = false;
        });
}

document.getElementById('sdc-cob')?.addEventListener('sdc:select', function(e) {
    loadBirthCities(e.detail.id);
});
document.getElementById('sdc-cob')?.addEventListener('blur', function() {
    loadBirthCities(resolveCountryIdByInput('sdc-cob', 'sdc-cob-val', 'sdc-cob-id'));
});
document.getElementById('sdc-cob')?.addEventListener('change', function() {
    loadBirthCities(resolveCountryIdByInput('sdc-cob', 'sdc-cob-val', 'sdc-cob-id'));
});
document.getElementById('sdc-cob-city')?.addEventListener('input', function() {
    document.getElementById('sdc-cob-city-val').value = this.value;
});

function loadBillingStates(countryId) {
    const stateSel = document.getElementById('billing-state-field');
    if (!stateSel) return;

    stateSel.innerHTML = '<option value="">Loading states...</option>';
    if (window.$ && $(stateSel).data('select2')) {
        $(stateSel).val('').trigger('change');
    }

    if (!countryId) {
        stateSel.innerHTML = '<option value="">Select country first...</option>';
        if (window.$ && $(stateSel).data('select2')) {
            $(stateSel).val('').trigger('change');
        }
        loadBillingCities('');
        return;
    }

    fetch('modules/ajax/get_states.php?country_id=' + encodeURIComponent(countryId))
        .then(function(r) { return r.json(); })
        .then(function(states) {
            const rows = Array.isArray(states) ? states : [];
            const opts = ['<option value="">Select state/province...</option>']
                .concat(rows.map(function(s) {
                    return '<option value="' + String(s.name).replace(/"/g, '&quot;') + '" data-id="' + s.id + '">' + s.name + '</option>';
                }));
            stateSel.innerHTML = opts.join('');
            if (window.$ && $(stateSel).data('select2')) {
                $(stateSel).val('').trigger('change');
            }
            loadBillingCities('');
        })
        .catch(function() {
            stateSel.innerHTML = '<option value="">Could not load states</option>';
            if (window.$ && $(stateSel).data('select2')) {
                $(stateSel).val('').trigger('change');
            }
            loadBillingCities('');
        });
}

function loadBillingCities(stateId) {
    const datalist = document.getElementById('billing-city-options');
    if (!datalist) return;

    datalist.innerHTML = '';
    if (!stateId) return;

    fetch('modules/ajax/get_cities.php?state_id=' + encodeURIComponent(stateId))
        .then(function(r) { return r.json(); })
        .then(function(cities) {
            const rows = Array.isArray(cities) ? cities : [];
            datalist.innerHTML = rows.map(function(c) {
                return '<option value="' + String(c.name).replace(/"/g, '&quot;') + '"></option>';
            }).join('');
        })
        .catch(function() {
            datalist.innerHTML = '';
        });
}

document.getElementById('sdc-bc')?.addEventListener('sdc:select', function(e) {
    loadBillingStates(e.detail.id);
});
document.getElementById('sdc-bc')?.addEventListener('blur', function() {
    loadBillingStates(resolveCountryIdByInput('sdc-bc', 'sdc-bc-val', 'sdc-bc-id'));
});
document.getElementById('sdc-bc')?.addEventListener('change', function() {
    loadBillingStates(resolveCountryIdByInput('sdc-bc', 'sdc-bc-val', 'sdc-bc-id'));
});
document.getElementById('billing-state-field')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stateId = selected ? selected.getAttribute('data-id') : '';
    loadBillingCities(stateId || '');
});
</script>

<script>
window.FORM_COUNTRY = <?= json_encode($formCountry, JSON_UNESCAPED_UNICODE) ?>;
window.FORM_DISPLAY_NAME = <?= json_encode($formCountry . ' Visa Application', JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- Form Logic & Validation -->
<script src="assets/js/validator1.js?v=<?= filemtime(__DIR__ . '/../../assets/js/validator1.js') ?>"></script>
<script src="assets/js/form.js?v=<?= filemtime(__DIR__ . '/../../assets/js/form.js') ?>"></script>

</body>
</html>


