<?php

namespace Tests\Feature\Quality;

use Tests\TestCase;

class ProtectedModuleAccessTest extends TestCase
{
    public function test_guests_are_redirected_from_major_admin_modules(): void
    {
        $protectedRoutes = [
            'dashboard',
            'companies.index',
            'departments.index',
            'job-postings.index',
            'candidates.index',
            'applications.index',
            'interviews.index',
            'pipeline.index',
            'offers.index',
            'reports.index',
            'audit-logs.index',
        ];

        foreach ($protectedRoutes as $routeName) {
            $response = $this->get(route($routeName));

            $this->assertTrue(
                $response->isRedirect(route('login')),
                "Guest access to route [{$routeName}] was not redirected to login.",
            );
        }
    }
}
