<?php

namespace Tests\Feature\Quality;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\Department;
use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_all_major_admin_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::factory()->create();
        $superAdmin->roles()->sync([
            Role::query()->where('slug', 'super_admin')->value('id'),
        ]);

        $interviewer = User::factory()->create();
        $interviewer->roles()->sync([
            Role::query()->where('slug', 'interviewer')->value('id'),
        ]);

        $company = Company::factory()->create();
        $department = Department::factory()->for($company)->create();
        $jobPosting = JobPosting::factory()
            ->forDepartment($department)
            ->for($superAdmin, 'createdBy')
            ->open()
            ->create();
        $candidate = Candidate::factory()->create();
        CandidateResume::factory()
            ->primary()
            ->for($candidate)
            ->for($superAdmin, 'uploadedBy')
            ->create();
        $application = Application::factory()
            ->for($candidate)
            ->for($jobPosting)
            ->for($superAdmin, 'createdBy')
            ->create(['current_status' => 'selected']);
        $interview = InterviewSchedule::factory()
            ->completed()
            ->for($application)
            ->for($interviewer, 'interviewer')
            ->for($superAdmin, 'createdBy')
            ->create();
        $feedback = InterviewFeedback::factory()
            ->for($interview, 'interviewSchedule')
            ->for($interviewer, 'submittedBy')
            ->create();
        $offer = Offer::factory()
            ->for($application)
            ->for($superAdmin, 'createdBy')
            ->create();
        $auditLog = AuditLog::query()->create([
            'actor_id' => $superAdmin->id,
            'action' => AuditLog::ACTION_CREATED,
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
            'summary' => 'Company created during admin page render regression setup.',
            'new_values' => ['name' => $company->name],
        ]);

        $adminPages = [
            'dashboard' => route('dashboard'),
            'companies.index' => route('companies.index'),
            'companies.create' => route('companies.create'),
            'companies.show' => route('companies.show', $company),
            'companies.edit' => route('companies.edit', $company),
            'departments.index' => route('departments.index'),
            'departments.create' => route('departments.create'),
            'departments.show' => route('departments.show', $department),
            'departments.edit' => route('departments.edit', $department),
            'job-postings.index' => route('job-postings.index'),
            'job-postings.create' => route('job-postings.create'),
            'job-postings.show' => route('job-postings.show', $jobPosting),
            'job-postings.edit' => route('job-postings.edit', $jobPosting),
            'candidates.index' => route('candidates.index'),
            'candidates.create' => route('candidates.create'),
            'candidates.show' => route('candidates.show', $candidate),
            'candidates.edit' => route('candidates.edit', $candidate),
            'applications.index' => route('applications.index'),
            'applications.create' => route('applications.create'),
            'applications.show' => route('applications.show', $application),
            'applications.edit' => route('applications.edit', $application),
            'interviews.index' => route('interviews.index'),
            'interviews.create' => route('interviews.create'),
            'interviews.show' => route('interviews.show', $interview),
            'interviews.edit' => route('interviews.edit', $interview),
            'interviews.feedback.create' => route('interviews.feedback.create', $interview),
            'interview-feedback.show' => route('interview-feedback.show', $feedback),
            'interview-feedback.edit' => route('interview-feedback.edit', $feedback),
            'pipeline.index' => route('pipeline.index'),
            'offers.index' => route('offers.index'),
            'offers.create' => route('offers.create'),
            'offers.show' => route('offers.show', $offer),
            'offers.edit' => route('offers.edit', $offer),
            'reports.index' => route('reports.index'),
            'reports.applications' => route('reports.applications'),
            'reports.candidates' => route('reports.candidates'),
            'reports.job-postings' => route('reports.job-postings'),
            'reports.interviews' => route('reports.interviews'),
            'reports.pipeline' => route('reports.pipeline'),
            'reports.offers' => route('reports.offers'),
            'audit-logs.index' => route('audit-logs.index'),
            'audit-logs.show' => route('audit-logs.show', $auditLog),
        ];

        $this->actingAs($superAdmin);

        foreach ($adminPages as $routeName => $url) {
            $response = $this->get($url);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Admin page [{$routeName}] did not render successfully.",
            );
        }
    }
}
