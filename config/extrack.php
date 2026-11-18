<?php

return [
    'demo' => env('APP_DEMO', false),
    'report_reader' => env('EXTRACK_REPORT_READER', 'null'),   // later: 'template'
    'auth_provider' => env('AUTH_PROVIDER', 'local'),          // later: 'zitadel'
    'invite_days' => 7,
    'campus_prefixes' => ['Main' => 'MAIN', 'Balilihan' => 'BAL', 'Bilar' => 'BIL', 'Calape' => 'CAL', 'Candijay' => 'CAN', 'Clarin' => 'CLA'],
    'code' => ['format' => '{PREFIX}-{YEAR}-{SEQ}', 'seq_digits' => 3, 'assign_on' => 'publish'], // or 'draft'
    'draft' => ['required' => ['report_file', 'title']],
    'publish' => ['required' => ['title', 'category_id', 'municipality_id', 'barangay_ids', 'start_date', 'end_date', 'beneficiaries', 'sector_ids', 'narrative', 'report_file']],
    'files' => ['max_mb' => 20, 'types' => ['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'], 'report_types' => ['pdf', 'docx', 'jpg', 'jpeg', 'png']],
    // ability => roles. "own campus" limits are applied by the policies, not here.
    'role_labels' => ['president' => 'University President', 'campus_uploader' => 'Campus Uploader', 'campus_director' => 'Campus Director'],
    'permissions' => [
        'view_published'   => ['president', 'campus_uploader', 'campus_director'],
        'view_drafts'      => ['campus_uploader'],
        'create'           => ['campus_uploader'],
        'edit_draft'       => ['campus_uploader'],
        'edit_published'   => ['campus_uploader'],
        'publish'          => ['campus_uploader'],
        'discard_draft'    => ['campus_uploader'],
        'delete_published' => ['president'],
        'manage_users'     => ['president'],
        'manage_reference' => ['president'],
        'view_activity'    => ['president'],
    ],
    'app_switcher' => [/* ['name' => 'Procurement', 'url' => '#'], ['name' => 'Alumni Connect', 'url' => '#'] */],
    'footer' => ['social' => [/* ['name' => 'Facebook', 'url' => 'https://www.facebook.com/...'] */], 'developers' => 'EXTRACK team'],
];
