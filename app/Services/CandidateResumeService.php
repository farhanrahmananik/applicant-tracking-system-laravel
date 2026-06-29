<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateResume;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CandidateResumeService
{
    private const DISK = 'local';

    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function upload(
        Candidate $candidate,
        UploadedFile $file,
        bool $isPrimary = false,
    ): CandidateResume {
        $extension = Str::lower($file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $directory = "resumes/candidates/{$candidate->getKey()}";
        $path = Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        if ($path === false) {
            throw new RuntimeException('The resume could not be stored.');
        }

        try {
            return DB::transaction(function () use ($candidate, $file, $path, $extension, $isPrimary): CandidateResume {
                $existingResumes = $candidate->resumes()->lockForUpdate()->get();
                $makePrimary = $isPrimary || $existingResumes->isEmpty();

                if ($makePrimary) {
                    $candidate->resumes()->update(['is_primary' => false]);
                }

                $resume = $candidate->resumes()->create([
                    'uploaded_by_id' => Auth::id(),
                    'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
                    'stored_path' => $path,
                    'disk' => self::DISK,
                    'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'extension' => $extension,
                    'is_primary' => $makePrimary,
                    'uploaded_at' => now(),
                ]);
                $this->auditLogService->uploaded(
                    $resume,
                    "Resume uploaded for candidate #{$candidate->getKey()}.",
                    $this->auditLogService->snapshot($resume),
                );

                return $resume;
            });
        } catch (Throwable $exception) {
            Storage::disk(self::DISK)->delete($path);

            throw $exception;
        }
    }

    public function download(CandidateResume $resume): StreamedResponse
    {
        abort_unless(
            Storage::disk($resume->disk)->exists($resume->stored_path),
            404,
            'The requested resume file is unavailable.',
        );

        $this->auditLogService->downloaded(
            $resume,
            "Resume downloaded for candidate #{$resume->candidate_id}.",
            $this->auditLogService->snapshot($resume),
        );

        return Storage::disk($resume->disk)->download(
            $resume->stored_path,
            $resume->original_name,
            [
                'Content-Type' => $resume->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function delete(CandidateResume $resume): void
    {
        $disk = $resume->disk;
        $path = $resume->stored_path;

        DB::transaction(function () use ($resume): void {
            $candidate = $resume->candidate;
            $wasPrimary = $resume->is_primary;
            $before = $this->auditLogService->snapshot($resume);

            $resume->delete();
            $this->auditLogService->deleted(
                $resume,
                "Resume deleted for candidate #{$resume->candidate_id}.",
                $before,
            );

            if ($wasPrimary) {
                $candidate->resumes()
                    ->lockForUpdate()
                    ->first()
                    ?->update(['is_primary' => true]);
            }
        });

        if (Storage::disk($disk)->exists($path) && ! Storage::disk($disk)->delete($path)) {
            Log::warning('Candidate resume file could not be deleted.', [
                'disk' => $disk,
                'path' => $path,
            ]);
        }
    }

    private function sanitizeOriginalName(string $originalName): string
    {
        $name = preg_replace('/[^\pL\pN._ -]+/u', '_', basename($originalName)) ?: 'resume';

        return Str::limit($name, 255, '');
    }
}
