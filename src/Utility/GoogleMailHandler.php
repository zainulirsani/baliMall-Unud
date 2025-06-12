<?php

namespace App\Utility;

class GoogleMailHandler
{
    public static function validate($email): string
    {
        $valid = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($valid) {
            $reserved = ['gmail.com', 'googlemail.com', 'google.com'];
            [$account, $domain] = explode('@', $email);

            if (in_array($domain, $reserved, false)) {
                $account = preg_replace('/[-=].*/', '', $account); // Remove tags
                // $account = str_replace('.', '', $account); // Remove dots
                $email = $account.'@'.$reserved[0]; // Return formatted account
            }
        }

        return $email;
    }
}
