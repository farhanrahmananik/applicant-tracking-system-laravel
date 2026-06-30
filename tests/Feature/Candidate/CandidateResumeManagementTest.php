<?php

namespace Tests\Feature\Candidate;

use App\Models\Candidate;
use App\Models\CandidateResume;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateResumeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_upload_pdf_doc_and_docx_resumes_with_metadata(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $files = [
            UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->create('resume.doc', 140, 'application/msword'),
            UploadedFile::fake()->create(
                'resume.docx',
                160,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ),
        ];

        foreach ($files as $file) {
            $this->actingAs($user)
                ->post(route('candidates.resumes.store', $candidate), [
                    'resume' => $file,
                ])
                ->assertRedirect(route('candidates.show', $candidate))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('candidate_resumes', 3);

        $resumes = CandidateResume::query()->orderBy('id')->get();

        $this->assertTrue($resumes->first()->is_primary);
        $this->assertSame('resume.pdf', $resumes->first()->original_name);
        $this->assertSame('pdf', $resumes->first()->extension);
        $this->assertSame('local', $resumes->first()->disk);
        $this->assertSame($candidate->id, $resumes->first()->candidate_id);
        $this->assertSame($user->id, $resumes->first()->uploaded_by_id);
        $this->assertGreaterThan(0, $resumes->first()->size_bytes);
        $this->assertNotNull($resumes->first()->uploaded_at);

        foreach ($resumes as $resume) {
            Storage::disk('local')->assertExists($resume->stored_path);
        }
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();

        $this->actingAs($user)
            ->from(route('candidates.show', $candidate))
            ->post(route('candidates.resumes.store', $candidate), [
                'resume' => UploadedFile::fake()->create('malware.exe', 50, 'application/x-msdownload'),
            ])
            ->assertRedirect(route('candidates.show', $candidate))
            ->assertSessionHasErrors('resume');

        $this->assertDatabaseCount('candidate_resumes', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_uploaded_resume_appears_on_candidate_show_page(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        CandidateResume::factory()->for($candidate)->create([
            'original_name' => 'avery-stone-cv.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertSeeText('Resumes and CVs')
            ->assertSeeText('avery-stone-cv.pdf');
    }

    public function test_authorized_user_can_download_resume(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $resume = CandidateResume::factory()->for($candidate)->create([
            'original_name' => 'download-me.pdf',
            'stored_path' => "resumes/candidates/{$candidate->id}/download-me.pdf",
        ]);
        Storage::disk('local')->put($resume->stored_path, 'private resume content');

        $this->actingAs($user)
            ->get(route('candidates.resumes.download', [$candidate, $resume]))
            ->assertOk()
            ->assertDownload('download-me.pdf');
    }

    public function test_unauthorized_user_cannot_download_resume(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('candidate');
        $candidate = Candidate::factory()->create();
        $resume = CandidateResume::factory()->for($candidate)->create();
        Storage::disk('local')->put($resume->stored_path, 'private resume content');

        $this->actingAs($user)
            ->get(route('candidates.resumes.download', [$candidate, $resume]))
            ->assertForbidden();
    }

    public function test_authorized_user_can_delete_resume_and_stored_file(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('hr_manager');
        $candidate = Candidate::factory()->create();
        $resume = CandidateResume::factory()->primary()->for($candidate)->create([
            'stored_path' => "resumes/candidates/{$candidate->id}/delete-me.pdf",
        ]);
        Storage::disk('local')->put($resume->stored_path, 'private resume content');

        $this->actingAs($user)
            ->delete(route('candidates.resumes.destroy', [$candidate, $resume]))
            ->assertRedirect(route('candidates.show', $candidate));

        $this->assertSoftDeleted($resume);
        Storage::disk('local')->assertMissing($resume->stored_path);
    }

    public function test_deleting_primary_resume_promotes_an_existing_resume(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('hr_manager');
        $candidate = Candidate::factory()->create();
        $primaryResume = CandidateResume::factory()->primary()->for($candidate)->create([
            'stored_path' => "resumes/candidates/{$candidate->id}/primary.pdf",
        ]);
        $replacementResume = CandidateResume::factory()->for($candidate)->create([
            'stored_path' => "resumes/candidates/{$candidate->id}/replacement.pdf",
        ]);
        Storage::disk('local')->put($primaryResume->stored_path, 'primary resume');
        Storage::disk('local')->put($replacementResume->stored_path, 'replacement resume');

        $this->actingAs($user)
            ->delete(route('candidates.resumes.destroy', [$candidate, $primaryResume]))
            ->assertRedirect(route('candidates.show', $candidate));

        $this->assertSoftDeleted($primaryResume);
        $this->assertTrue($replacementResume->refresh()->is_primary);
        Storage::disk('local')->assertMissing($primaryResume->stored_path);
        Storage::disk('local')->assertExists($replacementResume->stored_path);
    }

    public function test_unauthorized_user_cannot_delete_resume(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('recruiter');
        $candidate = Candidate::factory()->create();
        $resume = CandidateResume::factory()->for($candidate)->create();
        Storage::disk('local')->put($resume->stored_path, 'private resume content');

        $this->actingAs($user)
            ->delete(route('candidates.resumes.destroy', [$candidate, $resume]))
            ->assertForbidden();

        $this->assertDatabaseHas('candidate_resumes', [
            'id' => $resume->id,
            'deleted_at' => null,
        ]);
        Storage::disk('local')->assertExists($resume->stored_path);
    }

    public function test_resume_from_another_candidate_is_not_resolved_by_scoped_route(): void
    {
        Storage::fake('local');

        $user = $this->createUserWithRole('hr_manager');
        $candidate = Candidate::factory()->create();
        $otherResume = CandidateResume::factory()->create();

        $this->actingAs($user)
            ->get(route('candidates.resumes.download', [$candidate, $otherResume]))
            ->assertNotFound();
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
