<?php
function send_otp_to_user($user_phone_or_email, $otp)
{
    error_log("OTP for {$user_phone_or_email}: {$otp}");
    return true;
}
