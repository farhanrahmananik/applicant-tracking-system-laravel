<?php

return [
    'permissions' => [
        'access-dashboard' => [
            'name' => 'Access dashboard',
            'description' => 'Access the authenticated ATS dashboard.',
        ],
        'manage-users' => [
            'name' => 'Manage users',
            'description' => 'Manage internal ATS user accounts.',
        ],
        'manage-roles' => [
            'name' => 'Manage roles',
            'description' => 'Manage role definitions and assignments.',
        ],
        'manage-permissions' => [
            'name' => 'Manage permissions',
            'description' => 'Manage permission definitions and role grants.',
        ],
        'view-audit-logs' => [
            'name' => 'View audit logs',
            'description' => 'View security and business audit activity.',
        ],
        'view-own-candidate-profile' => [
            'name' => 'View own candidate profile',
            'description' => 'View the authenticated candidate profile.',
        ],
    ],

    'roles' => [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Full platform administration access.',
            'permissions' => ['*'],
        ],
        'hr_manager' => [
            'name' => 'HR Manager',
            'description' => 'Manage users and oversee HR activity.',
            'permissions' => [
                'access-dashboard',
                'manage-users',
                'view-audit-logs',
            ],
        ],
        'recruiter' => [
            'name' => 'Recruiter',
            'description' => 'Access recruitment workflows.',
            'permissions' => ['access-dashboard'],
        ],
        'interviewer' => [
            'name' => 'Interviewer',
            'description' => 'Access assigned interview workflows.',
            'permissions' => ['access-dashboard'],
        ],
        'candidate' => [
            'name' => 'Candidate',
            'description' => 'Access candidate-owned information.',
            'permissions' => ['view-own-candidate-profile'],
        ],
    ],
];
