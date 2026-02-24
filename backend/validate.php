<?php
/**
 * ============================================================
 * backend/validate.php
 * Purpose: Form ke har ek field ko double-check karna.
 * Yahin tay hota hai ki data DB mein jayega ya user ko error dikhega.
 * ============================================================
 */

// ── Regex Patterns (Validation ke Rules) ────────────────────────────────────────
// In patterns se hum enforce karte hain ki data ka format sahi ho.
const RGX_NAME    = '/^[a-zA-Z\s\-\'\.]{2,100}$/u'; // Letters, spaces aur common symbols (min 2 chars)
const RGX_EMAIL   = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/';
const RGX_PHONE   = '/^\+?[1-9]\d{6,14}$/';         // International format (+ ke saath ya bina)
const RGX_PASSPORT = '/^[A-Z0-9]{6,20}$/i';         // Numbers aur Letters mix
const RGX_POSTAL  = '/^[A-Z0-9\s\-]{3,10}$/i';      // Different countries ke postal codes ke liye flexible
const RGX_UCI     = '/^[\d\-]{8,20}$/';             // Canada UCI number format

// ── Single Field Validators (Chote Tools) ────────────────────────────────

/**
 * Name Validate: Khali nahi hona chahiye aur regex match hona chahiye.
 */
function validateName(string $v, string $label): ?string {
    $v = trim($v);
    if ($v === '') return "$label is required.";
    if (!preg_match(RGX_NAME, $v)) return "$label can only contain letters, spaces, hyphens and apostrophes (min 2 chars).";
    return null;
}

/**
 * Email Validate: PHP ke filter_var aur regex dono use kiye hain extra safety ke liye.
 */
function validateEmail(string $v): ?string {
    $v = trim($v);
    if ($v === '') return "Email address is required.";
    if (!filter_var($v, FILTER_VALIDATE_EMAIL) || !preg_match(RGX_EMAIL, $v))
        return "Please enter a valid email address (e.g. name@example.com).";
    if (strlen($v) > 255) return "Email must be under 255 characters.";
    return null;
}

/**
 * Phone Validate: Spaces aur brackets hata kar check karta hai.
 */
function validatePhone(string $v): ?string {
    $v = trim($v);
    if ($v === '') return "Phone number is required.";
    $clean = preg_replace('/[\s\-\(\)]/', '', $v); // Formatting saaf karo
    if (!preg_match(RGX_PHONE, $clean))
        return "Please enter a valid phone number with country code (e.g. +91 9876543210).";
    return null;
}

/**
 * Date Logic: Check karta hai ki date real hai ya nahi (e.g., 31st Feb reject karega).
 */
function validateDate(string $v, string $label): ?string {
    $v = trim($v);
    if ($v === '') return "$label is required.";
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) return "$label must be a valid date.";
    return null;
}

// Travel date hamesha aaj ke baad ki honi chahiye.
function validateFutureDate(string $v, string $label): ?string {
    if ($e = validateDate($v, $label)) return $e;
    if (new DateTime($v) <= new DateTime('today')) return "$label must be a future date.";
    return null;
}

// DOB hamesha aaj se pehle ki honi chahiye.
function validatePastDate(string $v, string $label): ?string {
    if ($e = validateDate($v, $label)) return $e;
    if (new DateTime($v) >= new DateTime('today')) return "$label must be a past date.";
    return null;
}

/**
 * Select/Dropdown: Check karta hai ki "Please Select" wala option (0 value) toh nahi hai.
 */
function validateSelect(string $v, string $label): ?string {
    if (trim($v) === '' || $v === '0') return "Please select a valid $label.";
    return null;
}

/**
 * Required text field validation.
 */
function validateRequired(string $v, string $label): ?string {
    $v = trim($v);
    if ($v === '') return "$label is required.";
    if (strlen($v) > 255) return "$label must be under 255 characters.";
    return null;
}

/**
 * Postal code validation (country-agnostic basic rule).
 */
function validatePostalCode(string $v): ?string {
    $v = trim($v);
    if ($v === '') return "Postal code is required.";
    if (!preg_match(RGX_POSTAL, $v)) return "Please enter a valid postal code.";
    return null;
}

/**
 * Passport Logic: Space hata kar hamesha Uppercase mein check karta hai.
 */
function validatePassportNum(string $v): ?string {
    $v = strtoupper(trim($v));
    if ($v === '') return "Passport number is required.";
    if (!preg_match(RGX_PASSPORT, $v)) return "Passport number must be 6–20 alphanumeric characters.";
    return null;
}

/**
 * Textarea/Background details: Min length check taaki user sirf "." daal kar na nikal jaye.
 */
function validateTextarea(string $v, string $label, int $min = 10): ?string {
    $v = trim($v);
    if ($v === '') return "$label is required.";
    if (strlen($v) < $min) return "$label must be at least $min characters.";
    if (strlen($v) > 2000) return "$label must be under 2000 characters.";
    return null;
}

// ── Step Validators (Inko AJAX files call karti hain) ────────────────────────

// Step 1: Contact Info
function validateStepContact(array $d): array {
    $e = [];
    if ($err = validateName($d['first_name'] ?? '', 'First Name'))      $e['first_name']      = $err;
    if (!empty(trim($d['middle_name'] ?? ''))) // Middle name sirf tab validate karo agar bhara ho
        if ($err = validateName($d['middle_name'], 'Middle Name'))       $e['middle_name']     = $err;
    if ($err = validateName($d['last_name'] ?? '', 'Last Name'))        $e['last_name']       = $err;
    if ($err = validateEmail($d['email'] ?? ''))                         $e['email']           = $err;
    if ($err = validatePhone($d['phone'] ?? ''))                         $e['phone']           = $err;
    if ($err = validateFutureDate($d['travel_date'] ?? '', 'Travel Date')) $e['travel_date']  = $err;
    if ($err = validateSelect($d['purpose_of_visit'] ?? '', 'Purpose of Visit')) $e['purpose_of_visit'] = $err;
    return $e;
}

// Step 2: Personal Info
function validateStepPersonal(array $d): array {
    $e = [];
    if ($err = validatePastDate($d['date_of_birth'] ?? '', 'Date of Birth')) $e['date_of_birth'] = $err;
    if (!in_array($d['gender'] ?? '', ['male','female','other']))            $e['gender']         = 'Please select a gender.';
    if ($err = validateSelect($d['country_of_birth'] ?? '', 'Country of Birth')) $e['country_of_birth'] = $err;
    if ($err = validateRequired($d['city_of_birth'] ?? '', 'City/Town of Birth')) $e['city_of_birth'] = $err;
    if ($err = validateSelect($d['marital_status'] ?? '', 'Marital Status'))  $e['marital_status']  = $err;
    if ($err = validateSelect($d['nationality'] ?? '', 'Country of Citizenship / Nationality')) $e['nationality'] = $err;
    return $e;
}

// Step 3: Passport Info
function validateStepPassport(array $d): array {
    $e = [];
    if ($err = validateSelect($d['passport_country'] ?? '', 'Country of Passport'))  $e['passport_country'] = $err;
    if ($err = validatePassportNum($d['passport_number'] ?? ''))                      $e['passport_number']  = $err;
    
    // Double confirmation logic: Dono passport number match hone chahiye
    if (strtoupper(trim($d['passport_number'] ?? '')) !== strtoupper(trim($d['passport_number_confirm'] ?? '')))
        $e['passport_number_confirm'] = 'Passport numbers do not match.';
    
    if ($err = validatePastDate($d['passport_issue_date'] ?? '', 'Passport Date of Issue')) $e['passport_issue_date'] = $err;
    if ($err = validateFutureDate($d['passport_expiry'] ?? '', 'Passport Expiry Date')) $e['passport_expiry'] = $err;
    
    // Conditional logic: Agar 'Dual Citizen' Yes hai, toh dusri country batana lazmi hai
    if (($d['dual_citizen'] ?? '0') === '1')
        if ($err = validateSelect($d['other_citizenship_country'] ?? '', 'Other Country of Citizenship')) $e['other_citizenship_country'] = $err;
    
    return $e;
}

// Step 4: Residential & Job Info
function validateStepResidential(array $d): array {
    $e = [];
    if ($err = validateRequired($d['address_line'] ?? '', 'Address Line'))    $e['address_line']   = $err;
    if ($err = validateRequired($d['street_number'] ?? '', 'Street Number'))  $e['street_number']  = $err;
    if ($err = validateSelect($d['country'] ?? '', 'Country'))                $e['country']        = $err;
    if ($err = validateRequired($d['city'] ?? '', 'City / Town'))             $e['city']           = $err;
    if ($err = validatePostalCode($d['postal_code'] ?? ''))                   $e['postal_code']    = $err;
    if ($err = validateSelect($d['occupation'] ?? '', 'Occupation'))          $e['occupation']     = $err;

    // Job logic: Agar user "Retired" hai toh employer details ki zaroorat nahi
    $noJob = ['Retired','Unemployed','Homemaker'];
    $occ   = trim($d['occupation'] ?? '');
    if ($occ !== '' && !in_array($occ, $noJob)) {
        if ($err = validateSelect($d['job_title'] ?? '', 'Job Title'))         $e['job_title']      = $err;
        if ($err = validateRequired($d['employer_name'] ?? '', 'Employer / School Name')) $e['employer_name'] = $err;
        if ($err = validateSelect($d['employer_country'] ?? '', 'Employer Country')) $e['employer_country'] = $err;
        if ($err = validateRequired($d['employer_city'] ?? '', 'Employer City'))  $e['employer_city']  = $err;
        if ($err = validateSelect($d['start_year'] ?? '', 'Start Year'))       $e['start_year']     = $err;
    }
    return $e;
}

// Step 5: Background Info
function validateStepBackground(array $d): array {
    $e = [];
    // Loop chala rahe hain taaki har Yes/No sawal check ho jaye
    foreach (['visa_refusal','tuberculosis','criminal_history'] as $q) {
        if (!isset($d[$q]) || !in_array($d[$q], ['0','1']))
            $e[$q] = 'Please answer this question.';
        elseif ($d[$q] === '1') {
            // Agar 'Yes' (1) hai, toh details mangna compulsory hai
            $details = $d[$q . '_details'] ?? '';
            if ($err = validateTextarea($details, 'Details', 10)) $e[$q . '_details'] = $err;
        }
    }
    if ($err = validateSelect($d['health_condition'] ?? '', 'Health Condition')) $e['health_condition'] = $err;
    return $e;
}

// Step 6: Final Declaration
function validateStepDeclaration(array $d): array {
    $e = [];
    if (($d['decl_accurate'] ?? '0') !== '1') $e['decl_accurate'] = 'You must confirm that the information is accurate.';
    if (($d['decl_terms']   ?? '0') !== '1') $e['decl_terms']    = 'You must agree to the Terms & Conditions.';
    return $e;
}
