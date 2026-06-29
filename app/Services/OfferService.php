<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Offer;
use App\Models\OfferStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferService
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'draft' => ['sent', 'withdrawn'],
        'sent' => ['accepted', 'declined', 'withdrawn', 'expired'],
        'accepted' => [],
        'declined' => [],
        'withdrawn' => [],
        'expired' => [],
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Offer>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $this->allowedStatus($filters['status'] ?? null);
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Offer::query()
            ->with([
                'application:id,candidate_id,job_posting_id,current_status,deleted_at',
                'application.candidate:id,first_name,last_name,email,deleted_at',
                'application.jobPosting:id,company_id,title,deleted_at',
                'application.jobPosting.company:id,name,deleted_at',
            ])
            ->when($terms !== [], function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function ($query) use ($term): void {
                        $query
                            ->where('offer_title', 'like', "%{$term}%")
                            ->orWhereHas('application.candidate', function ($query) use ($term): void {
                                $query
                                    ->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            })
                            ->orWhereHas(
                                'application.jobPosting',
                                fn ($query) => $query->where('title', 'like', "%{$term}%"),
                            );
                    });
                }
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Application>
     */
    public function applicationOptions(): Collection
    {
        return Application::query()
            ->select(['id', 'candidate_id', 'job_posting_id', 'current_status'])
            ->with([
                'candidate:id,first_name,last_name,email',
                'jobPosting:id,company_id,title,employment_type',
                'jobPosting.company:id,name',
            ])
            ->where('current_status', 'selected')
            ->whereDoesntHave(
                'offers',
                fn ($query) => $query->whereIn('status', Offer::BLOCKING_STATUSES),
            )
            ->latest('updated_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Offer
    {
        return DB::transaction(function () use ($data): Offer {
            $application = Application::query()
                ->whereKey($data['application_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureApplicationIsEligible($application);
            $this->ensureNoBlockingOffer($application);

            $actorId = Auth::id();
            $data['status'] = 'draft';
            $data['created_by_id'] = $actorId;
            $data['updated_by_id'] = $actorId;

            return Offer::query()->create($data)->load([
                'application.candidate',
                'application.jobPosting.company',
                'createdBy',
                'updatedBy',
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Offer $offer, array $data): Offer
    {
        return DB::transaction(function () use ($offer, $data): Offer {
            $offer = $this->lockOffer($offer);

            if (! $offer->isDraft()) {
                throw ValidationException::withMessages([
                    'offer_title' => 'Only draft offers can be edited.',
                ]);
            }

            $data['updated_by_id'] = Auth::id();
            $offer->update($data);

            return $offer->refresh()->load([
                'application.candidate',
                'application.jobPosting.company',
                'createdBy',
                'updatedBy',
            ]);
        }, 3);
    }

    public function transition(Offer $offer, string $toStatus, ?string $note = null): Offer
    {
        return DB::transaction(function () use ($offer, $toStatus, $note): Offer {
            $offer = $this->lockOffer($offer);
            $fromStatus = $offer->status;

            if (! in_array($toStatus, $this->allowedTransitions($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'to_status' => 'The requested offer status transition is not allowed.',
                ]);
            }

            $actorId = Auth::id();
            $offer->update([
                'status' => $toStatus,
                'updated_by_id' => $actorId,
            ]);

            OfferStatusHistory::query()->create([
                'offer_id' => $offer->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
                'changed_by_id' => $actorId,
                'changed_at' => now(),
            ]);

            return $offer->refresh();
        }, 3);
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    private function lockOffer(Offer $offer): Offer
    {
        return Offer::query()
            ->whereKey($offer->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function ensureApplicationIsEligible(Application $application): void
    {
        if ($application->current_status !== 'selected') {
            throw ValidationException::withMessages([
                'application_id' => 'Offers can only be created for applications in the selected pipeline stage.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureNoBlockingOffer(Application $application): void
    {
        $exists = Offer::query()
            ->where('application_id', $application->id)
            ->whereIn('status', Offer::BLOCKING_STATUSES)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'application_id' => 'This application already has an active or accepted offer.',
            ]);
        }
    }

    private function allowedStatus(mixed $value): ?string
    {
        return is_string($value) && in_array($value, Offer::STATUSES, true) ? $value : null;
    }
}
