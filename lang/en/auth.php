<?php

return [
    // Laravel's own keys
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // Login page
    'login_title' => 'Log in',
    'login_subtitle' => 'Enter your university ID and password to continue',
    'university_id' => 'University ID',
    'university_id_hint' => 'Numbers only, as shown on your university card',
    'password_label' => 'Password',
    'show_password' => 'Show or hide password',
    'remember_me' => 'Remember me',
    'forgot_password' => 'Forgot your password?',
    'login_button' => 'Log in',
    'panel_text' => 'One place to follow your graduation project, from forming a team and submitting your idea to the defense and archive.',

    // Login results
    'not_registered' => 'This university ID is not registered in the system. Please contact your department.',
    'account_disabled' => 'This account is disabled. Please contact your department.',
    'wrong_password' => 'The password is incorrect.',

    'logout' => 'Log out',

    // Guests on protected pages
    'login_required' => 'Please log in to continue.',

    // Change password
    'change_password_title' => 'Change password',
    'change_password_subtitle' => 'Choose a strong password you don\'t use anywhere else.',
    'change_password_required' => 'To protect your account, you must change your temporary password before continuing.',
    'current_password' => 'Current password',
    'new_password' => 'New password',
    'confirm_password' => 'Confirm new password',
    'rule_length' => 'At least 8 characters',
    'rule_letters' => 'Contains letters',
    'rule_numbers' => 'Contains numbers',
    'save_password' => 'Save password',
    'password_changed' => 'Your password has been changed.',
    // Forgot / reset password
    'forgot_title' => 'Forgot your password',
    'forgot_subtitle' => 'Enter your university ID and we will send a reset link to your university email.',
    'send_link' => 'Send reset link',
    'reset_link_sent' => 'If this ID is registered, a reset link has been sent to your university email.',
    'back_to_login' => 'Back to log in',
    'reset_title' => 'Reset your password',
    'reset_subtitle' => 'Choose a new password for your account.',
    'reset_button' => 'Save new password',
    'password_reset_done' => 'Your password has been changed. You can log in now.',
    'reset_invalid_title' => 'Invalid link',
    'reset_invalid_text' => 'This reset link is wrong or has expired. Links are valid for 60 minutes only.',
    'request_new_link' => 'Request a new link',
];