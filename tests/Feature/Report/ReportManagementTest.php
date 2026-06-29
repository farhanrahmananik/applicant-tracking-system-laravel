<?php

namespace Tests\Feature\Report;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\InterviewFeedback;
use App\Models\InterviewSchedule;
use App\Models\JobPosting;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_users_cannot_access_reports_or_exports(): void
    {
        $candidate = $this->createUserWithRole('candidate');

        $this->get(route('reports.index'))->assertRedirect(route('login'));

        $this->actingAs($candidate)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('reports.applications'))->assertForbidden();
        $this->actingAs($candidate)->get(route('reports.applications.export'))->assertForbidden();
    }

    public function test_authorized_user_can_access_report_overview(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        Application::factory()->count(2)->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertSeeText('Reports')
            ->assertSeeText('Application Summary')
            ->assertSeeText('Candidate Source & Status')
            ->assertSeeText('Job Posting Performance')
            ->assertSeeText('Interview Schedule & Outcome')
            ->assertSeeText('Hiring Pipeline Stages')
            ->assertSeeText('Offer Status');
    }

    public function test_authorized_user_can_render_every_report_page(): void
    {
        $user = $this->createUserWithRole('recruiter');

        foreach ([
            'reports.applications' => 'Application Summary',
            'reports.candidates' => 'Candidate Source & Status',
            'reports.job-postings' => 'Job Posting Performance',
            'reports.interviews' => 'Interview Schedule & Outcome',
            'reports.pipeline' => 'Hiring Pipeline Stages',
            'reports.offers' => 'Offer Status',
        ] as $route => $heading) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSeeText($heading)
                ->assertSeeText('Apply filters');
        }
    }

    public function test_application_candidate_pipeline_and_offer_filters_scope_results(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $matching = Application::factory()->create([
            'current_status' => 'screening',
            'applied_date' => '2026-06-10',
        ]);
        Application::factory()->create([
            'current_status' => 'rejected',
            'applied_date' => '2026-05-10',
        ]);
        Candidate::factory()->create(['source' => 'community_event', 'status' => 'active']);
        Candidate::factory()->create(['source' => 'agency', 'status' => 'archived']);
        Offer::factory()->for($matching)->create([
            'status' => 'sent',
            'created_at' => '2026-06-12 10:00:00',
        ]);
        Offer::factory()->create([
            'status' => 'declined',
            'created_at' => '2026-05-12 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('reports.applications', [
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-30',
                'application_status' => 'screening',
            ]))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total'] === 1)
            ->assertViewHas('rows', fn ($rows): bool => $rows->firstWhere('key', 'screening')['count'] === 1);

        $this->actingAs($user)
            ->get(route('reports.candidates', [
                'candidate_source' => 'community_event',
                'candidate_status' => 'active',
            ]))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total'] === 1);

        $this->actingAs($user)
            ->get(route('reports.pipeline', [
                'job_posting_id' => $matching->job_posting_id,
                'pipeline_stage' => 'screening',
            ]))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total'] === 1);

        $this->actingAs($user)
            ->get(route('reports.offers', [
                'date_from' => '2026-06-01',
                'offer_status' => 'sent',
            ]))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total'] === 1);
    }

    public function test_job_posting_and_interview_reports_aggregate_existing_workflow_data(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $jobPosting = JobPosting::factory()->create(['title' => 'Platform Engineer']);
        $candidate = Candidate::factory()->create();
        $application = Application::factory()
            ->for($jobPosting)
            ->for($candidate)
            ->create(['current_status' => 'selected']);
        Application::factory()->for($jobPosting)->for($candidate)->create();
        $interview = InterviewSchedule::factory()->completed()->for($application)->create();
        InterviewFeedback::factory()->for($interview)->create([
            'recommendation' => 'strong_hire',
        ]);
        Offer::factory()->for($application)->create(['status' => 'accepted']);

        $this->actingAs($user)
            ->get(route('reports.job-postings', ['job_posting_id' => $jobPosting->id]))
            ->assertOk()
            ->assertViewHas('rows', function ($rows): bool {
                $row = $rows->sole();

                return $row['applications'] === 2
                    && $row['candidates'] === 1
                    && $row['interviews'] === 1
                    && $row['offers'] === 1;
            })
            ->assertSeeText('Platform Engineer');

        $this->actingAs($user)
            ->get(route('reports.interviews', [
                'job_posting_id' => $jobPosting->id,
                'interview_status' => 'completed',
            ]))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total'] === 1)
            ->assertViewHas(
                'outcomeRows',
                fn ($rows): bool => $rows->firstWhere('key', 'strong_hire')['count'] === 1,
            );
    }

    public function test_report_filter_validation_rejects_invalid_ranges_and_values(): void
    {
        $user = $this->createUserWithRole('recruiter');

        $this->actingAs($user)
            ->get(route('reports.applications', [
                'date_from' => '2026-06-30',
                'date_to' => '2026-06-01',
                'application_status' => 'invented',
            ]))
            ->assertSessionHasErrors(['date_to', 'application_status']);

        $this->actingAs($user)
            ->get(route('reports.applications', ['date_to' => '2026-06-30']))
            ->assertOk()
            ->assertSessionHasNoErrors();
    }

    public function test_csv_exports_download_and_preserve_active_filters(): void
    {
        $user = $this->createUserWithRole('recruiter');
        Application::factory()->create(['current_status' => 'screening']);
        Application::factory()->create(['current_status' => 'rejected']);

        $response = $this->actingAs($user)->get(route('reports.applications.export', [
            'application_status' => 'screening',
        ]));

        $response
            ->assertOk()
            ->assertDownload('application-summary-'.now()->format('Y-m-d').'.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Screening,1,100%', $csv);
        $this->assertStringContainsString('Rejected,0,0%', $csv);

        foreach ([
            'reports.job-postings.export',
            'reports.interviews.export',
            'reports.pipeline.export',
            'reports.offers.export',
        ] as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    private function createUserWithRole(string $roleSlug): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->roles()->sync([
            Role::query()->where('slug', $roleSlug)->value('id'),
        ]);

        return $user;
    }
}
