@extends('layouts.app')

@section('title', $candidate->full_name)

@section('content')
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb resource-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $candidate->full_name }}</li>
        </ol>
    </nav>

    <header class="resource-page-header">
        <div class="candidate-profile-heading">
            <span class="candidate-avatar candidate-avatar-large" aria-hidden="true">
                {{ strtoupper(Illuminate\Support\Str::substr($candidate->first_name, 0, 1).Illuminate\Support\Str::substr($candidate->last_name ?? '', 0, 1)) }}
            </span>
            <div>
                <div class="page-kicker">Candidate profile</div>
                <h1 class="page-title">{{ $candidate->full_name }}</h1>
                <p class="page-subtitle">{{ $candidate->current_position ?? 'Position not provided' }}</p>
            </div>
        </div>

        <div class="resource-header-actions">
            <a class="btn btn-outline-secondary" href="{{ route('candidates.index') }}">Back</a>
            @can('candidates.edit')
                <a class="btn btn-primary" href="{{ route('candidates.edit', $candidate) }}">Edit candidate</a>
            @endcan
            @can('candidates.delete')
                <form
                    method="POST"
                    action="{{ route('candidates.destroy', $candidate) }}"
                    onsubmit="return confirm('Delete this candidate? The record will be soft deleted.')"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            @endcan
        </div>
    </header>

    <section class="candidate-summary-card" aria-label="Candidate summary">
        <div>
            <span>Status</span>
            <strong class="candidate-badge candidate-status-{{ $candidate->status }}">
                {{ Illuminate\Support\Str::headline($candidate->status) }}
            </strong>
        </div>
        <div>
            <span>Source</span>
            <strong>{{ $candidate->source ? Illuminate\Support\Str::headline($candidate->source) : 'Not provided' }}</strong>
        </div>
        <div>
            <span>Availability</span>
            <strong>{{ $candidate->availability ? Illuminate\Support\Str::headline($candidate->availability) : 'Not provided' }}</strong>
        </div>
        <div>
            <span>Added</span>
            <strong>{{ $candidate->created_at->format('M j, Y') }}</strong>
        </div>
    </section>

    <div class="company-detail-grid">
        <section class="detail-section" aria-labelledby="candidate-contact-title">
            <div class="section-heading">
                <div>
                    <h2 id="candidate-contact-title">Contact information</h2>
                    <p>Candidate communication and location details.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Email</dt>
                    <dd>{{ $candidate->email }}</dd>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd>{{ $candidate->phone ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Location</dt>
                    <dd>{{ $candidate->location ?? 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <section class="detail-section" aria-labelledby="candidate-professional-title">
            <div class="section-heading">
                <div>
                    <h2 id="candidate-professional-title">Professional details</h2>
                    <p>Current role and compensation expectations.</p>
                </div>
            </div>
            <dl class="detail-list">
                <div>
                    <dt>Position</dt>
                    <dd>{{ $candidate->current_position ?? 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Experience</dt>
                    <dd>{{ $candidate->experience_years !== null ? $candidate->experience_years.' years' : 'Not provided' }}</dd>
                </div>
                <div>
                    <dt>Expected salary</dt>
                    <dd>{{ $candidate->expected_salary !== null ? number_format((float) $candidate->expected_salary, 2) : 'Not provided' }}</dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="detail-section candidate-skills-section" aria-labelledby="candidate-skills-title">
        <div class="section-heading">
            <div>
                <h2 id="candidate-skills-title">Skills</h2>
                <p>Candidate-provided professional capabilities.</p>
            </div>
        </div>
        <div class="candidate-skills-copy">{{ $candidate->skills ?? 'No skills have been added.' }}</div>
    </section>

    <section class="detail-section candidate-resumes-section" aria-labelledby="candidate-resumes-title">
        <div class="section-heading candidate-resumes-heading">
            <div>
                <h2 id="candidate-resumes-title">Resumes and CVs</h2>
                <p>Private candidate documents available to authorized ATS users.</p>
            </div>
            <span class="section-status">{{ $candidate->resumes->count() }} files</span>
        </div>

        @can('candidate-resumes.upload')
            <form
                class="resume-upload-form"
                method="POST"
                action="{{ route('candidates.resumes.store', $candidate) }}"
                enctype="multipart/form-data"
            >
                @csrf
                <div class="resume-upload-field">
                    <label class="form-label" for="resume">Resume file</label>
                    <input
                        class="form-control @error('resume') is-invalid @enderror"
                        id="resume"
                        name="resume"
                        type="file"
                        accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        required
                    >
                    @error('resume')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">PDF, DOC, or DOCX. Maximum file size 10 MB.</div>
                </div>
                <div class="form-check resume-primary-check">
                    <input type="hidden" name="is_primary" value="0">
                    <input class="form-check-input" id="is_primary" name="is_primary" type="checkbox" value="1">
                    <label class="form-check-label" for="is_primary">Set as primary</label>
                </div>
                <button class="btn btn-primary" type="submit">Upload resume</button>
            </form>
        @endcan

        <div class="resume-list">
            @forelse ($candidate->resumes as $resume)
                @php
                    $size = $resume->size_bytes >= 1048576
                        ? number_format($resume->size_bytes / 1048576, 1).' MB'
                        : number_format($resume->size_bytes / 1024, 1).' KB';
                @endphp
                <article class="resume-list-item">
                    <div class="resume-file-mark" aria-hidden="true">{{ strtoupper($resume->extension) }}</div>
                    <div class="resume-file-info">
                        <div class="resume-file-name">
                            {{ $resume->original_name }}
                            @if ($resume->is_primary)
                                <span class="candidate-badge resume-primary-badge">Primary</span>
                            @endif
                        </div>
                        <div class="resume-file-meta">
                            <span>{{ strtoupper($resume->extension) }}</span>
                            <span>{{ $size }}</span>
                            <span>{{ $resume->uploaded_at->format('M j, Y H:i') }}</span>
                            <span>Uploaded by {{ $resume->uploadedBy?->name ?? 'System' }}</span>
                        </div>
                    </div>
                    <div class="resume-actions">
                        @can('candidate-resumes.download')
                            <a
                                class="btn btn-sm btn-outline-secondary"
                                href="{{ route('candidates.resumes.download', [$candidate, $resume]) }}"
                            >
                                Download
                            </a>
                        @endcan
                        @can('candidate-resumes.delete')
                            <form
                                method="POST"
                                action="{{ route('candidates.resumes.destroy', [$candidate, $resume]) }}"
                                onsubmit="return confirm('Delete this resume? The private file will also be removed.')"
                            >
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="resume-empty-state">No resumes or CVs have been uploaded.</div>
            @endforelse
        </div>
    </section>
@endsection
