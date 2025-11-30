<?php
// Placeholder for OTP sending logic. Integrate SMS/Email provider here.
function send_otp_to_user($user_phone_or_email, $otp)
{
    // For now, just log or return true.
    error_log("OTP for {$user_phone_or_email}: {$otp}");
    return true;
}
