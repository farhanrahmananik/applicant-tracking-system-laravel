<?php

namespace App\Http\Controllers;

use App\Http\Requests\Offer\StoreOfferRequest;
use App\Http\Requests\Offer\TransitionOfferStatusRequest;
use App\Http\Requests\Offer\UpdateOfferRequest;
use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferService $offerService,
    ) {}

    public function index(Request $request): View
    {
        return view('offers.index', [
            'offers' => $this->offerService->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        return view('offers.create', [
            'applications' => $this->offerService->applicationOptions(),
        ]);
    }

    public function store(StoreOfferRequest $request): RedirectResponse
    {
        $offer = $this->offerService->create($request->validated());

        return redirect()
            ->route('offers.show', $offer)
            ->with('success', 'Offer created successfully.');
    }

    public function show(Offer $offer): View
    {
        $offer->load([
            'application.candidate',
            'application.jobPosting.company',
            'application.jobPosting.department',
            'createdBy',
            'updatedBy',
            'statusHistories.changedBy',
        ]);

        return view('offers.show', [
            'offer' => $offer,
            'statusTransitions' => $this->offerService->allowedTransitions($offer->status),
        ]);
    }

    public function edit(Offer $offer): View
    {
        $offer->load(['application.candidate', 'application.jobPosting.company']);

        return view('offers.edit', compact('offer'));
    }

    public function update(UpdateOfferRequest $request, Offer $offer): RedirectResponse
    {
        $offer = $this->offerService->update($offer, $request->validated());

        return redirect()
            ->route('offers.show', $offer)
            ->with('success', 'Offer updated successfully.');
    }

    public function transition(
        TransitionOfferStatusRequest $request,
        Offer $offer,
    ): RedirectResponse {
        $data = $request->validated();
        $this->offerService->transition(
            $offer,
            $data['to_status'],
            $data['note'] ?? null,
        );

        return redirect()
            ->route('offers.show', $offer)
            ->with('success', 'Offer status updated successfully.');
    }
}
