<?php

namespace Tests\Feature\AuditLog;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\Offer;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_users_cannot_view_or_export_audit_logs(): void
    {
        $candidate = $this->createUserWithRole('candidate');
        $log = $this->createAuditLog();

        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
        $this->actingAs($candidate)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($candidate)->get(route('audit-logs.show', $log))->assertForbidden();
        $this->actingAs($candidate)->get(route('audit-logs.export'))->assertForbidden();
    }

    public function test_authorized_user_can_view_audit_index_and_detail(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create(['name' => 'Northstar Systems']);
        $log = $this->createAuditLog([
            'actor_id' => $user->id,
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
            'summary' => 'Northstar Systems was updated.',
            'old_values' => ['is_active' => true],
            'new_values' => ['is_active' => false],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Audit test agent',
        ]);

        $this->actingAs($user)
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertViewIs('audit-logs.index')
            ->assertSeeText('Audit Logs')
            ->assertSeeText('Northstar Systems was updated.')
            ->assertSeeText($user->name);

        $this->actingAs($user)
            ->get(route('audit-logs.show', $log))
            ->assertOk()
            ->assertViewIs('audit-logs.show')
            ->assertSeeText('Audit Event Detail')
            ->assertSeeText('Northstar Systems was updated.')
            ->assertSeeText('Audit test agent')
            ->assertSeeText('is_active');
    }

    public function test_company_pipeline_and_offer_actions_create_audit_entries(): void
    {
        $user = $this->createUserWithRole('hr_manager');

        $this->actingAs($user)->post(route('companies.store'), [
            'name' => 'Ledger Company',
            'city' => 'Berlin',
            'country' => 'Germany',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $company = Company::query()->where('name', 'Ledger Company')->sole();
        $this->actingAs($user)->put(route('companies.update', $company), [
            'name' => 'Ledger Company Updated',
            'city' => 'Hamburg',
            'country' => 'Germany',
            'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->delete(route('companies.destroy', $company->refresh()))
            ->assertSessionHasNoErrors();

        $application = Application::factory()->create(['current_status' => 'applied']);
        $this->actingAs($user)->post(route('pipeline.transition', $application), [
            'to_stage' => 'screening',
        ])->assertSessionHasNoErrors();

        $offer = Offer::factory()->create(['status' => 'draft']);
        $this->actingAs($user)->post(route('offers.transition', $offer), [
            'to_status' => 'sent',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => AuditLog::ACTION_CREATED,
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_UPDATED,
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_DELETED,
            'auditable_type' => Company::class,
            'auditable_id' => $company->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_STATUS_CHANGED,
            'auditable_type' => Application::class,
            'auditable_id' => $application->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_STATUS_CHANGED,
            'auditable_type' => Offer::class,
            'auditable_id' => $offer->id,
        ]);
    }

    public function test_resume_events_store_only_safe_file_metadata(): void
    {
        Storage::fake('local');
        $user = $this->createUserWithRole('hr_manager');
        $candidate = Candidate::factory()->create();

        $this->actingAs($user)->post(route('candidates.resumes.store', $candidate), [
            'resume' => UploadedFile::fake()->create('candidate-resume.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $resume = CandidateResume::query()->sole();
        $this->actingAs($user)
            ->get(route('candidates.resumes.download', [$candidate, $resume]))
            ->assertDownload('candidate-resume.pdf');
        $this->actingAs($user)
            ->delete(route('candidates.resumes.destroy', [$candidate, $resume]))
            ->assertSessionHasNoErrors();

        $logs = AuditLog::query()
            ->where('auditable_type', CandidateResume::class)
            ->orderBy('id')
            ->get();

        $this->assertSame([
            AuditLog::ACTION_UPLOADED,
            AuditLog::ACTION_DOWNLOADED,
            AuditLog::ACTION_DELETED,
        ], $logs->pluck('action')->all());

        foreach ($logs as $log) {
            $metadata = [...($log->old_values ?? []), ...($log->new_values ?? [])];
            $this->assertArrayNotHasKey('stored_path', $metadata);
            $this->assertArrayNotHasKey('disk', $metadata);
            $this->assertStringNotContainsString('private resume content', json_encode($metadata));
        }
    }

    public function test_filters_scope_audit_logs_by_actor_action_entity_date_and_keyword(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $otherActor = User::factory()->create(['name' => 'Other Auditor']);
        $matching = $this->createAuditLog([
            'actor_id' => $user->id,
            'action' => AuditLog::ACTION_UPDATED,
            'auditable_type' => Company::class,
            'summary' => 'Distinctive governance event.',
            'created_at' => '2026-06-15 10:00:00',
        ]);
        $other = $this->createAuditLog([
            'actor_id' => $otherActor->id,
            'action' => AuditLog::ACTION_DELETED,
            'auditable_type' => Candidate::class,
            'summary' => 'Unrelated event.',
            'created_at' => '2026-05-15 10:00:00',
        ]);

        foreach ([
            ['actor_id' => $user->id],
            ['action' => AuditLog::ACTION_UPDATED],
            ['auditable_type' => Company::class],
            ['date_from' => '2026-06-01', 'date_to' => '2026-06-30'],
            ['search' => 'Distinctive governance'],
        ] as $filters) {
            $this->actingAs($user)
                ->get(route('audit-logs.index', $filters))
                ->assertOk()
                ->assertSeeText($matching->summary)
                ->assertDontSeeText($other->summary);
        }
    }

    public function test_csv_export_is_permission_gated_and_respects_filters(): void
    {
        $hrManager = $this->createUserWithRole('hr_manager');
        $recruiter = $this->createUserWithRole('recruiter');
        $this->createAuditLog([
            'action' => AuditLog::ACTION_CREATED,
            'summary' => 'Included audit event.',
            'old_values' => ['private_field' => 'must not be exported'],
        ]);
        $this->createAuditLog([
            'action' => AuditLog::ACTION_DELETED,
            'summary' => 'Excluded audit event.',
        ]);

        $this->actingAs($recruiter)->get(route('audit-logs.export'))->assertForbidden();

        $response = $this->actingAs($hrManager)->get(route('audit-logs.export', [
            'action' => AuditLog::ACTION_CREATED,
        ]));

        $response
            ->assertOk()
            ->assertDownload('audit-logs-'.now()->format('Y-m-d').'.csv');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Included audit event.', $csv);
        $this->assertStringNotContainsString('Excluded audit event.', $csv);
        $this->assertStringNotContainsString('must not be exported', $csv);
    }

    public function test_sensitive_values_are_removed_before_storage(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $company = Company::factory()->create();

        $this->actingAs($user);
        app(AuditLogService::class)->log(
            AuditLog::ACTION_UPDATED,
            $company,
            'Sensitive metadata sanitizer test.',
            [
                'password' => 'plain-text-password',
                'remember_token' => 'remember-me',
                'stored_path' => 'resumes/private/file.pdf',
                'nested' => ['api_key' => 'secret-key', 'status' => 'draft'],
            ],
            [
                'authorization' => 'Bearer secret-token',
                'status' => 'active',
            ],
        );

        $log = AuditLog::query()->sole();
        $serialized = json_encode([$log->old_values, $log->new_values]);

        $this->assertStringNotContainsString('plain-text-password', $serialized);
        $this->assertStringNotContainsString('remember-me', $serialized);
        $this->assertStringNotContainsString('resumes/private', $serialized);
        $this->assertStringNotContainsString('secret-key', $serialized);
        $this->assertStringNotContainsString('secret-token', $serialized);
        $this->assertSame('draft', $log->old_values['nested']['status']);
        $this->assertSame('active', $log->new_values['status']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAuditLog(array $overrides = []): AuditLog
    {
        return AuditLog::query()->forceCreate(array_replace([
            'actor_id' => null,
            'action' => AuditLog::ACTION_UPDATED,
            'auditable_type' => Company::class,
            'auditable_id' => 1,
            'summary' => 'Audit event.',
            'old_values' => null,
            'new_values' => null,
            'ip_address' => null,
            'user_agent' => null,
            'created_at' => now(),
        ], $overrides));
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
