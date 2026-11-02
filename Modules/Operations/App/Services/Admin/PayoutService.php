<?php

namespace Modules\Operations\App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Operations\Entities\Payout;

class PayoutService
{
    public function __construct(
        private readonly PayoutBalanceService $balance,
        private readonly AdminAuditLogger $audit,
    ) {
    }

    public function approve(Payout $payout, User $actor): Payout
    {
        if ((string) $payout->requested_by === (string) $actor->getKey()) {
            throw AdminActionException::requirement('Pengaju payout tidak dapat menyetujui payout yang sama.');
        }

        if ($payout->status !== 'requested') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'approved');
        }

        $organization = $payout->organization;
        if (! $organization || ! $organization->isVerified()) {
            throw AdminActionException::requirement('Organisasi harus terverifikasi sebelum payout disetujui.');
        }

        return $this->transition($payout, $actor, 'approved', [
            'approved_by' => $actor->getKey(),
        ], null, true);
    }

    public function reject(Payout $payout, User $actor, string $reason): Payout
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw AdminActionException::requirement('Alasan penolakan payout wajib diisi.');
        }

        if ($payout->status !== 'requested') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'rejected');
        }

        return $this->transition($payout, $actor, 'rejected', ['note' => $reason], $reason);
    }

    /**
     * @param array{type: 'file', file: UploadedFile}|array{type: 'url', url: string}|array{type: 'path', path: string} $evidence
     *        as returned by MarkPayoutPaidRequest::validatedEvidence()
     */
    public function markPaid(Payout $payout, User $actor, array $evidence): Payout
    {
        if ($payout->status !== 'approved') {
            throw AdminActionException::invalidTransition('payout', $payout->status, 'paid');
        }

        $disk = Storage::disk((string) config('operations.evidence.disk'));
        $storedNow = null;

        try {
            $attributes = match ($evidence['type'] ?? null) {
                'file' => $this->privateFileAttributes($payout, $disk, $storedNow = $this->storeUpload($payout, $disk, $evidence['file'] ?? null)),
                'path' => $this->privateFileAttributes($payout, $disk, (string) ($evidence['path'] ?? '')),
                'url' => $this->legacyUrlAttributes((string) ($evidence['url'] ?? '')),
                default => throw AdminActionException::requirement('Format bukti tidak dikenal.'),
            };

            return $this->transition($payout, $actor, 'paid', $attributes + ['paid_at' => now()], null, true);
        } catch (\Throwable $exception) {
            if ($storedNow) {
                $disk->delete($storedNow); // no orphan evidence when the payout could not be marked paid
            }

            throw $exception;
        }
    }

    private function storeUpload(Payout $payout, Filesystem $disk, mixed $file): string
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw AdminActionException::requirement('File bukti tidak valid.');
        }

        $path = $file->store($payout->evidenceDirectory(), ['disk' => (string) config('operations.evidence.disk')]);

        if ($path === false) {
            throw AdminActionException::requirement('File bukti gagal disimpan.');
        }

        return $path;
    }

    /** MIME and size always come from the stored file on the private disk, never from the client. */
    private function privateFileAttributes(Payout $payout, Filesystem $disk, string $path): array
    {
        $insideOwnDirectory = str_starts_with($path, $payout->evidenceDirectory().'/') && ! str_contains($path, '..');

        if (! $insideOwnDirectory || ! $disk->exists($path)) {
            throw AdminActionException::requirement('File bukti tidak ditemukan di storage private.');
        }

        $mime = (string) $disk->mimeType($path);

        if (! in_array($mime, (array) config('operations.evidence.mimetypes'), true)) {
            throw AdminActionException::requirement('Isi file bukti bukan JPG, PNG, atau PDF.');
        }

        return [
            'evidence_path' => $path,
            'evidence_mime' => $mime,
            'evidence_size' => (int) $disk->size($path),
            'evidence_url' => null, // no public URL is ever generated for private evidence
        ];
    }

    /** Legacy external URL: kept as a link only, never treated as a private file. */
    private function legacyUrlAttributes(string $url): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, ['http', 'https'], true)) {
            throw AdminActionException::requirement('Format URL bukti tidak valid.');
        }

        return ['evidence_url' => $url, 'evidence_path' => null, 'evidence_mime' => null, 'evidence_size' => null];
    }

    private function transition(
        Payout $payout,
        User $actor,
        string $target,
        array $attributes,
        ?string $reason = null,
        bool $checkBalance = false,
    ): Payout
    {
        $before = $this->auditSnapshot($payout);

        $updated = DB::transaction(function () use ($payout, $actor, $target, $attributes, $checkBalance) {
            $locked = Payout::query()->whereKey($payout->getKey())->lockForUpdate()->firstOrFail();

            // Re-checked on approve and again right before paid (settled donations can change, e.g. refunds).
            if ($checkBalance && $locked->amount > $this->balance->availableAmount($locked->organization_id, (string) $locked->getKey())) {
                throw AdminActionException::requirement('Saldo tersedia organisasi tidak mencukupi untuk payout ini.');
            }

            $locked->forceFill(array_merge($attributes, ['status' => $target]))->save();

            return $locked->refresh();
        });

        $this->audit->log(
            'payout.'.$target,
            $updated,
            $before,
            $this->auditSnapshot($updated),
            $reason,
            $actor,
            ['module' => 'Operations', 'entity' => 'payout'],
        );

        return $updated;
    }

    /**
     * Audit-safe view of a payout: no file contents, and URL query strings
     * (signed-URL tokens) and fragments are dropped.
     */
    private function auditSnapshot(Payout $payout): array
    {
        $url = (string) $payout->evidence_url;
        $parts = $url !== '' ? parse_url($url) : false;

        return [
            'status' => $payout->status,
            'approved_by' => $payout->approved_by,
            'paid_at' => $payout->paid_at?->toIso8601String(),
            'note' => $payout->note,
            'evidence_type' => $payout->evidence_path ? 'private_file' : ($url !== '' ? 'url' : null),
            'evidence_path' => $payout->evidence_path,
            'evidence_mime' => $payout->evidence_mime,
            'evidence_size' => $payout->evidence_size,
            'evidence_url' => is_array($parts) && isset($parts['host'])
                ? ($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '')
                : null,
        ];
    }
}
