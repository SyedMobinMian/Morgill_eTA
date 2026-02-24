<?php
/**
 * ============================================================
 * thank-you.php — Payment Success & Final Confirmation Page
 * Location: pages/thank-you.php (root wrapper: thank-you.php)
 * Purpose: Payment ke baad user ko reference number dikhana aur 
 * aglo steps (What happens next) samjhana.
 * ============================================================
 */
session_start();
require_once __DIR__ . '/../backend/config.php';

/**
 * URL se Reference Number uthana:
 * Humne 'ref' parameter ko sanitise kiya hai taaki koi XSS attack na ho sake.
 * Agar ref nahi milta toh 'N/A' dikhayenge.
 */
$reference = htmlspecialchars($_GET['ref'] ?? 'N/A', ENT_QUOTES, 'UTF-8');

// Note: Yahan hum session clear nahi kar rahe hain taaki agar user ko 
// turant dusra application bharna ho toh system ready rahe, 
// lekin backend par save_contact.php naya application ID generate karega.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted – Canada eTA</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Global Styles: Page ko vertically aur horizontally center karne ke liye flexbox ka use */
        body { 
            background: #f0f4f8; 
            font-family: 'Poppins', sans-serif; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }

        /* Success Card Design: Clean, modern aur shadow-based elevation */
        .success-card { 
            max-width: 620px; 
            width: 100%; 
            background: #fff; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 10px 40px rgba(0,0,0,.1); 
        }

        /* Header: Gradient use kiya hai taaki "Trust" aur "Success" wali feel aaye */
        .success-header { 
            background: linear-gradient(135deg, #22c55e, #16a34a); 
            padding: 45px 40px; 
            text-align: center; 
            color: #fff; 
        }

        .checkmark { font-size: 64px; margin-bottom: 15px; }

        /* Reference Box: Iska main maqsad hai user ko uski unique ID yaad dilana */
        .ref-box { 
            background: #f0fdf4; 
            border: 2px solid #22c55e; 
            border-radius: 14px; 
            padding: 22px; 
            text-align: center; 
            margin: 0 0 28px; 
        }

        .ref-box .ref { 
            font-size: 28px; 
            font-weight: 700; 
            color: #16a34a; 
            letter-spacing: 3px; /* Digit clarity ke liye thodi spacing */
        }

        /* Step List: User ko "Next Steps" clear dikhane ke liye custom bullet points */
        .step-list { list-style: none; padding: 0; margin: 0 0 30px; }
        .step-list li { 
            padding: 12px 0; 
            border-bottom: 1px solid #f1f5f9; 
            display: flex; 
            align-items: flex-start; 
            gap: 14px; 
            font-size: 14px; 
            color: #475569; 
        }
        .step-list li:last-child { border-bottom: none; }

        /* Numeric Bullet: Round blue icons steps ke liye */
        .step-icon { 
            width: 34px; height: 34px; 
            background: #dbeafe; 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            color: #2563eb; font-weight: 700; flex-shrink: 0; 
        }
    </style>
</head>
<body>

<div class="success-card">
    <div class="success-header">
        <div class="checkmark"><i class="fas fa-check-circle"></i></div>
        <h1>Application Submitted!</h1>
        <p>Your Canada eTA application is now being processed.</p>
    </div>

    <div class="info-body" style="padding: 35px;">
        <div class="ref-box">
            <div class="label" style="color: #64748b; font-size: 13px; margin-bottom: 6px;">Your Reference Number</div>
            <div class="ref"><?= $reference ?></div>
            <small>Save this number — you'll need it to track your application.</small>
        </div>

        <h6 style="font-weight:700;margin-bottom:16px;color:#1e293b;">What happens next?</h6>
        
        <ul class="step-list">
            <li>
                <div class="step-icon">1</div>
                <span>A confirmation email has been sent to your registered email address.</span>
            </li>
            <li>
                <div class="step-icon">2</div>
                <span>Our team will review your application and documents.</span>
            </li>
            <li>
                <div class="step-icon">3</div>
                <span>You will receive your eTA decision by email within your selected processing time.</span>
            </li>
            <li>
                <div class="step-icon">4</div>
                <span>If any documents or information are missing, we will contact you at the email provided.</span>
            </li>
        </ul>

        <div class="text-center">
            <a href="index.php" class="btn btn-primary px-5 py-2" style="border-radius:12px;font-weight:600;font-size:15px;">
                <i class="fas fa-plus me-2"></i>Apply for Another Person
            </a>
        </div>
    </div>
</div>

</body>
</html>
