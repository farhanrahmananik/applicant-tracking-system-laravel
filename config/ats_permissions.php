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
        'companies.view' => [
            'name' => 'View companies',
            'description' => 'View company records and details.',
        ],
        'companies.create' => [
            'name' => 'Create companies',
            'description' => 'Create company records.',
        ],
        'companies.update' => [
            'name' => 'Update companies',
            'description' => 'Update company records.',
        ],
        'companies.delete' => [
            'name' => 'Delete companies',
            'description' => 'Soft delete company records.',
        ],
        'departments.view' => [
            'name' => 'View departments',
            'description' => 'View department records and details.',
        ],
        'departments.create' => [
            'name' => 'Create departments',
            'description' => 'Create department records.',
        ],
        'departments.update' => [
            'name' => 'Update departments',
            'description' => 'Update department records.',
        ],
        'departments.delete' => [
            'name' => 'Delete departments',
            'description' => 'Soft delete department records.',
        ],
        'job-postings.view' => [
            'name' => 'View job postings',
            'description' => 'View job posting records and details.',
        ],
        'job-postings.create' => [
            'name' => 'Create job postings',
            'description' => 'Create job posting records.',
        ],
        'job-postings.update' => [
            'name' => 'Update job postings',
            'description' => 'Update job posting records and status.',
        ],
        'job-postings.delete' => [
            'name' => 'Delete job postings',
            'description' => 'Soft delete job posting records.',
        ],
        'candidates.view' => [
            'name' => 'View candidates',
            'description' => 'View candidate records and profiles.',
        ],
        'candidates.create' => [
            'name' => 'Create candidates',
            'description' => 'Create candidate records.',
        ],
        'candidates.edit' => [
            'name' => 'Edit candidates',
            'description' => 'Update candidate records.',
        ],
        'candidates.delete' => [
            'name' => 'Delete candidates',
            'description' => 'Soft delete candidate records.',
        ],
        'candidate-resumes.upload' => [
            'name' => 'Upload candidate resumes',
            'description' => 'Upload private resume files for candidate records.',
        ],
        'candidate-resumes.download' => [
            'name' => 'Download candidate resumes',
            'description' => 'Download authorized candidate resume files.',
        ],
        'candidate-resumes.delete' => [
            'name' => 'Delete candidate resumes',
            'description' => 'Soft delete candidate resume records and stored files.',
        ],
        'applications.view' => [
            'name' => 'View applications',
            'description' => 'View job application records and details.',
        ],
        'applications.create' => [
            'name' => 'Create applications',
            'description' => 'Link existing candidates to job postings.',
        ],
        'applications.update' => [
            'name' => 'Update applications',
            'description' => 'Update application details and status.',
        ],
        'applications.delete' => [
            'name' => 'Delete applications',
            'description' => 'Soft delete application records.',
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
                'companies.view',
                'companies.create',
                'companies.update',
                'companies.delete',
                'departments.view',
                'departments.create',
                'departments.update',
                'departments.delete',
                'job-postings.view',
                'job-postings.create',
                'job-postings.update',
                'job-postings.delete',
                'candidates.view',
                'candidates.create',
                'candidates.edit',
                'candidates.delete',
                'candidate-resumes.upload',
                'candidate-resumes.download',
                'candidate-resumes.delete',
                'applications.view',
                'applications.create',
                'applications.update',
                'applications.delete',
            ],
        ],
        'recruiter' => [
            'name' => 'Recruiter',
            'description' => 'Access recruitment workflows.',
            'permissions' => [
                'access-dashboard',
                'job-postings.view',
                'job-postings.create',
                'job-postings.update',
                'candidates.view',
                'candidates.create',
                'candidates.edit',
                'candidate-resumes.upload',
                'candidate-resumes.download',
                'applications.view',
                'applications.create',
                'applications.update',
            ],
        ],
        'interviewer' => [
            'name' => 'Interviewer',
            'description' => 'Access assigned interview workflows.',
            'permissions' => [
                'access-dashboard',
                'job-postings.view',
            ],
        ],
        'candidate' => [
            'name' => 'Candidate',
            'description' => 'Access candidate-owned information.',
            'permissions' => ['view-own-candidate-profile'],
        ],
    ],
];
