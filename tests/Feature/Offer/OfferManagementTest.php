<?php

namespace Tests\Feature\Offer;

use App\Models\Application;
use App\Models\Offer;
use App\Models\OfferStatusHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_offer_index(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->create();

        $this->actingAs($user)
            ->get(route('offers.index'))
            ->assertOk()
            ->assertViewIs('offers.index')
            ->assertSeeText($offer->application->candidate->full_name)
            ->assertSeeText($offer->offer_title);
    }

    public function test_authorized_user_can_create_offer_for_selected_application(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'selected']);

        $response = $this->actingAs($user)->post(
            route('offers.store'),
            $this->validPayload($application, [
                'offer_title' => ' Senior Platform Engineer ',
                'currency' => ' eur ',
                'notes' => ' Initial package approved. ',
            ]),
        );

        $offer = Offer::query()->sole();

        $response->assertRedirect(route('offers.show', $offer));
        $this->assertSame('draft', $offer->status);
        $this->assertSame('Senior Platform Engineer', $offer->offer_title);
        $this->assertSame('EUR', $offer->currency);
        $this->assertSame('Initial package approved.', $offer->notes);
        $this->assertSame($user->id, $offer->created_by_id);
        $this->assertSame($user->id, $offer->updated_by_id);
    }

    public function test_offer_creation_requires_selected_pipeline_stage(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'interview']);

        $this->actingAs($user)
            ->from(route('offers.create'))
            ->post(route('offers.store'), $this->validPayload($application))
            ->assertRedirect(route('offers.create'))
            ->assertSessionHasErrors([
                'application_id' => 'Offers can only be created for applications in the selected pipeline stage.',
            ]);

        $this->assertDatabaseCount('offers', 0);
    }

    public function test_duplicate_active_or_accepted_offer_is_blocked(): void
    {
        $user = $this->createUserWithRole('recruiter');

        foreach (Offer::BLOCKING_STATUSES as $status) {
            $application = Application::factory()->create(['current_status' => 'selected']);
            Offer::factory()->for($application)->create(['status' => $status]);

            $this->actingAs($user)
                ->from(route('offers.create'))
                ->post(route('offers.store'), $this->validPayload($application))
                ->assertRedirect(route('offers.create'))
                ->assertSessionHasErrors('application_id');
        }

        $this->assertDatabaseCount('offers', 3);
    }

    public function test_terminal_unsuccessful_offer_allows_replacement_offer(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $application = Application::factory()->create(['current_status' => 'selected']);
        Offer::factory()->for($application)->create(['status' => 'declined']);

        $this->actingAs($user)
            ->post(route('offers.store'), $this->validPayload($application))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('offers', 2);
        $this->assertSame(1, Offer::query()->where('status', 'draft')->count());
    }

    public function test_authorized_user_can_update_draft_offer(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $offer = Offer::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($user)->put(
            route('offers.update', $offer),
            $this->validUpdatePayload([
                'offer_title' => 'Updated Engineering Offer',
                'salary_amount' => 92500,
            ]),
        );

        $offer->refresh();

        $response->assertRedirect(route('offers.show', $offer));
        $this->assertSame('Updated Engineering Offer', $offer->offer_title);
        $this->assertSame('92500.00', $offer->salary_amount);
        $this->assertSame($user->id, $offer->updated_by_id);
    }

    public function test_non_draft_offer_cannot_be_edited(): void
    {
        $user = $this->createUserWithRole('hr_manager');
        $offer = Offer::factory()->sent()->create(['offer_title' => 'Locked Offer']);

        $this->actingAs($user)
            ->from(route('offers.edit', $offer))
            ->put(
                route('offers.update', $offer),
                $this->validUpdatePayload(['offer_title' => 'Changed Offer']),
            )
            ->assertRedirect(route('offers.edit', $offer))
            ->assertSessionHasErrors('offer_title');

        $this->assertSame('Locked Offer', $offer->refresh()->offer_title);
    }

    public function test_offer_update_validation_rejects_invalid_terms(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->create();

        $this->actingAs($user)
            ->from(route('offers.edit', $offer))
            ->put(route('offers.update', $offer), [
                'offer_title' => '',
                'salary_amount' => 0,
                'currency' => 'EURO',
                'employment_type' => 'permanent',
                'expiry_date' => now()->subDay()->toDateString(),
                'expected_joining_date' => now()->subDays(2)->toDateString(),
                'notes' => str_repeat('a', 5001),
            ])
            ->assertRedirect(route('offers.edit', $offer))
            ->assertSessionHasErrors([
                'offer_title',
                'salary_amount',
                'currency',
                'employment_type',
                'expiry_date',
                'expected_joining_date',
                'notes',
            ]);
    }

    public function test_valid_offer_status_transition_records_history(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->create(['status' => 'draft']);

        $this->actingAs($user)
            ->post(route('offers.transition', $offer), [
                'to_status' => 'sent',
                'note' => 'Offer approved and shared with the candidate.',
            ])
            ->assertRedirect(route('offers.show', $offer))
            ->assertSessionHasNoErrors();

        $this->assertSame('sent', $offer->refresh()->status);
        $history = OfferStatusHistory::query()->sole();
        $this->assertSame('draft', $history->from_status);
        $this->assertSame('sent', $history->to_status);
        $this->assertSame($user->id, $history->changed_by_id);
        $this->assertSame('Offer approved and shared with the candidate.', $history->note);
        $this->assertNotNull($history->changed_at);
    }

    public function test_invalid_offer_status_transition_is_blocked(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->create(['status' => 'draft']);

        $this->actingAs($user)
            ->from(route('offers.show', $offer))
            ->post(route('offers.transition', $offer), ['to_status' => 'accepted'])
            ->assertRedirect(route('offers.show', $offer))
            ->assertSessionHasErrors([
                'to_status' => 'The requested offer status transition is not allowed.',
            ]);

        $this->assertSame('draft', $offer->refresh()->status);
        $this->assertDatabaseCount('offer_status_histories', 0);
    }

    public function test_application_show_page_displays_offer_summary(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $offer = Offer::factory()->sent()->create([
            'offer_title' => 'Platform Engineering Offer',
            'salary_amount' => 88000,
            'currency' => 'EUR',
        ]);

        $this->actingAs($user)
            ->get(route('applications.show', $offer->application))
            ->assertOk()
            ->assertSeeText('Offers')
            ->assertSeeText('Platform Engineering Offer')
            ->assertSeeText('EUR 88,000.00')
            ->assertSeeText('Sent');
    }

    public function test_unauthorized_user_cannot_manage_offers(): void
    {
        $user = $this->createUserWithRole('interviewer');
        $application = Application::factory()->create(['current_status' => 'selected']);
        $offer = Offer::factory()->for($application)->create();

        $this->actingAs($user)->get(route('offers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('offers.create'))->assertForbidden();
        $this->actingAs($user)->post(route('offers.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('offers.show', $offer))->assertForbidden();
        $this->actingAs($user)->get(route('offers.edit', $offer))->assertForbidden();
        $this->actingAs($user)->put(route('offers.update', $offer), [])->assertForbidden();
        $this->actingAs($user)->post(route('offers.transition', $offer), [])->assertForbidden();
    }

    public function test_offer_index_search_and_status_filter_work(): void
    {
        $user = $this->createUserWithRole('recruiter');
        $matching = Offer::factory()->create([
            'offer_title' => 'Distinctive Platform Offer',
            'status' => 'draft',
        ]);
        $other = Offer::factory()->create([
            'offer_title' => 'Unrelated Finance Offer',
            'status' => 'declined',
        ]);

        foreach ([['search' => 'Distinctive Platform'], ['status' => 'draft']] as $filter) {
            $this->actingAs($user)
                ->get(route('offers.index', $filter))
                ->assertOk()
                ->assertSeeText($matching->offer_title)
                ->assertDontSeeText($other->offer_title);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Application $application, array $overrides = []): array
    {
        return array_replace([
            'application_id' => $application->id,
            'offer_title' => 'Software Engineer Offer',
            'salary_amount' => 85000,
            'currency' => 'EUR',
            'employment_type' => 'full_time',
            'expiry_date' => now()->addWeeks(2)->toDateString(),
            'expected_joining_date' => now()->addMonth()->toDateString(),
            'notes' => 'Standard employment offer.',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUpdatePayload(array $overrides = []): array
    {
        return array_replace([
            'offer_title' => 'Software Engineer Offer',
            'salary_amount' => 85000,
            'currency' => 'EUR',
            'employment_type' => 'full_time',
            'expiry_date' => now()->addWeeks(2)->toDateString(),
            'expected_joining_date' => now()->addMonth()->toDateString(),
            'notes' => 'Standard employment offer.',
        ], $overrides);
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
