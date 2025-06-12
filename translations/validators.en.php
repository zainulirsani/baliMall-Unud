<?php

return [
    'user' => [
        'username_taken' => 'Username already taken',
        'email_taken' => 'Email already taken',
        'email_not_valid' => 'This value is not a valid email address',
        'email_valid' => 'This value is a valid email address',
        'password_not_match' => 'Password and Confirm Password does not match',
        'password_weak' => 'Your password is weak. It should consist of: 1 number, 1 special character, with minimum length of 6 characters',
        'not_valid' => 'Not a valid User entity',
        'valid' => 'Valid',
        'disabled_account' => 'Your account is in verification process by our team. Please wait.',
        'inactive_account' => 'Your account has not been activated. We have sent step-by-step to activate your account via email. Please check your inbox.',
        'phone_numeric' => 'Not a valid format. Phone number format: 081x-xxxx-xxx',
        'invalid_credentials' => 'Incorrect email or password. Please check again',
    ],
    'product' => [
        'price_check' => 'Reseller price should not be greater than selling price',
    ],
    'store' => [
        'invalid_new_owner' => 'Please select a valid new owner!',
    ],
    'order' => [
        'select_address' => 'Please select delivery address!',
        'select_courier' => 'Please select delivery courier!',
        'select_service' => 'Please select delivery service!',
        'select_tax_document' => 'Please select tax document!',
        'accept_tnc' => 'Please accept Terms & Condition!',
        'inactive_items' => 'Selected items from your cart is not available: %products% !',
        'invalid_vouchers' => 'Selected vouchers from your cart is not available: %vouchers% !',
        'lkpp_invalid_category' => 'Selected product category is not available: %categories% !',
        'no_stock_items' => 'Stock for selected items from your cart is not available: %products% !',
    ],
    'search' => [
        'query_not_empty' => 'Please enter your query',
        'search_not_empty' => 'Search query cannot be empty',
        'location_not_empty' => 'Location query cannot be empty',
    ],
    'global' => [
        'empty_input' => 'Empty Input',
        'not_empty' => 'This value should not be blank.',
        'message_empty' => 'Message should not be blank.',
        'too_long' => 'This value is too long. It should have %limit% characters or less.',
        'invalid_date' => 'This value is not a valid date.',
        'not_valid' => 'This value is not valid.',
        'slug_taken' => 'Slug is already taken',
        'reserved_names' => 'This value can not be used because it is a reserved name',
        'file_not_valid' => "Please enter a valid file type.",
        'proof_not_empty' => 'Payment Proof should not be blank.',
    ],
    'disbursement' => [
        'disbursement_protect_done' => "Can't change the disbursement's status to done using this feature",
        'allowed_change_status' => "Only able to revert the disbursement with status 'done'",
    ]
];
