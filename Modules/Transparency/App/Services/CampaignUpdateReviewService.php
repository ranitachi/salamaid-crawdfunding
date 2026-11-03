<?php

namespace Modules\Transparency\App\Services;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\App\Enums\CampaignStatus;
use Modules\Catalog\Entities\Campaign;
use Modules\Operations\Entities\Payout;
use Modules\Transparency\App\Enums\CampaignUpdateStatus;
use Modules\Transparency\App\Enums\CampaignUpdateType;
use Modules\Transparency\App\Events\CampaignUpdateStatusChanged;
use Modules\Transparency\Entities\CampaignUpdate;
use Modules\Transparency\Entities\CampaignUpdateAttachment;

class CampaignUpdateReviewService
{
    private const AUDITED_FIELDS = [
        'status', 'submitted_at', 'reviewed_by', 'reviewed_at',
        'review_note', 'published_at', 'unpublished_at',
    ];

    public function __construct(private AdminAuditLogger $audit)
    {
    }

    /**
     * @param array{campaign_id: string, type: string, title: string, content: string, payout_id?: ?string, amount_used?: ?int} $data
     */
    public function create(array $data, User $actor): CampaignUpdate
    {
        /** @var Campaign $campaign */
        $campaign = Campaign::query()->findOrFail($data['campaign_id']);
        if ($actor->isOrganizationAdmin() && ! in_array((string) $campaign->organization_id, $actor->managedOrganizationIds(), true)) {
            throw AdminActionException::requirement('Campaign bukan bagian dari lembaga yang Anda kelola.');
        }
        $type = CampaignUpdateType::from($data['type']);
        $payoutId = $type === CampaignUpdateType::FUND_REPORT ? ($data['payout_id'] ?? null) : null;

        if ($payoutId) {
            $this->assertPayoutMatchesCampaign($campaign, $payoutId, (int) ($data['amount_used'] ?? 0));
        }

        $update = new CampaignUpdate([
            'campaign_id' => $campaign->getKey(),
            'type' => $type->value,
            'title' => $data['title'],
            'content' => $data['content'],
            'payout_id' => $payoutId,
            'amount_used' => $type === CampaignUpdateType::FUND_REPORT ? $data['amount_used'] : null,
        ]);
        $update->forceFill(['status' => CampaignUpdateStatus::DRAFT->value, 'created_by' => $actor->getKey()])->save();

        $this->audit->log(
            'transparency.create',
            $update,
            [],
            $update->only(['campaign_id', 'type', 'status', 'payout_id', 'amount_used']),
            null,
            $actor,
            $this->context(),
        );

        return $update;
    }

    public function submit(CampaignUpdate $update, User $actor): CampaignUpdate
    {
        return $this->transition($update, CampaignUpdateStatus::UNDER_REVIEW, $actor, 'transparency.submit', null,
            ['submitted_at' => now(), 'review_note' => null],
            function (CampaignUpdate $locked) {
                if ($locked->isFundReport() && ! $locked->attachments()->exists()) {
                    throw AdminActionException::requirement('Laporan penggunaan dana wajib memiliki minimal satu bukti.');
                }
            },
            markReviewer: false,
        );
    }

    public function approve(CampaignUpdate $update, User $actor): CampaignUpdate
    {
        return $this->transition($update, CampaignUpdateStatus::APPROVED, $actor, 'transparency.approve', null,
            ['review_note' => null]);
    }

    /** Request revision: back to the organization with a mandatory reason. */
    public function reject(CampaignUpdate $update, User $actor, string $reason): CampaignUpdate
    {
        $reason = $this->requireReason($reason, 'Alasan revisi wajib diisi.');

        return $this->transition($update, CampaignUpdateStatus::REJECTED, $actor, 'transparency.reject', $reason,
            ['review_note' => $reason]);
    }

    public function publish(CampaignUpdate $update, User $actor): CampaignUpdate
    {
        return $this->transition($update, CampaignUpdateStatus::PUBLISHED, $actor, 'transparency.publish', null,
            ['published_at' => $update->published_at ?? now(), 'unpublished_at' => null, 'review_note' => null],
            function (CampaignUpdate $locked) {
                $status = CampaignStatus::tryFrom((string) $locked->campaign?->status);

                if (! in_array($status, [CampaignStatus::PUBLISHED, CampaignStatus::CLOSED], true)) {
                    throw AdminActionException::requirement(
                        'Update hanya dapat dipublikasikan untuk campaign yang published atau closed.',
                    );
                }
            },
        );
    }

    public function unpublish(CampaignUpdate $update, User $actor, string $reason): CampaignUpdate
    {
        $reason = $this->requireReason($reason, 'Alasan penarikan update wajib diisi.');

        return $this->transition($update, CampaignUpdateStatus::UNPUBLISHED, $actor, 'transparency.unpublish', $reason,
            ['unpublished_at' => now(), 'review_note' => $reason]);
    }

    public function attach(CampaignUpdate $update, UploadedFile $file, User $actor): CampaignUpdateAttachment
    {
        $disk = (string) config('transparency.evidence.disk');
        $maxFiles = (int) config('transparency.evidence.max_files');

        // Store first (outside the transaction), then commit metadata; remove the file if the commit fails.
        $path = $file->store(config('transparency.evidence.directory').'/'.$update->getKey(), $disk);

        if ($path === false) {
            throw AdminActionException::requirement('File bukti gagal disimpan.');
        }

        try {
            $attachment = DB::transaction(function () use ($update, $file, $actor, $disk, $path, $maxFiles) {
                $locked = $this->lock($update);

                if (! $locked->isEditable()) {
                    throw AdminActionException::requirement('Bukti hanya dapat diubah saat update berstatus draft atau perlu revisi.');
                }

                if ($locked->attachments()->count() >= $maxFiles) {
                    throw AdminActionException::requirement("Maksimal {$maxFiles} file bukti per update.");
                }

                $attachment = new CampaignUpdateAttachment;
                $attachment->forceFill([
                    'campaign_update_id' => $locked->getKey(),
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $this->safeName($file),
                    'mime_type' => (string) $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $actor->getKey(),
                ])->save();

                return $attachment;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        $this->audit->log(
            'transparency.attachment.upload',
            $update,
            [],
            $attachment->only(['id', 'original_name', 'mime_type', 'size', 'checksum']),
            null,
            $actor,
            $this->context(['type' => 'attachment']),
        );

        return $attachment;
    }

    public function removeAttachment(CampaignUpdate $update, CampaignUpdateAttachment $attachment, User $actor): void
    {
        DB::transaction(function () use ($update, $attachment) {
            if (! $this->lock($update)->isEditable()) {
                throw AdminActionException::requirement('Bukti hanya dapat diubah saat update berstatus draft atau perlu revisi.');
            }

            $attachment->delete();
        });

        Storage::disk($attachment->disk)->delete($attachment->path);

        $this->audit->log(
            'transparency.attachment.delete',
            $update,
            $attachment->only(['id', 'original_name', 'mime_type', 'size', 'checksum']),
            [],
            null,
            $actor,
            $this->context(['type' => 'attachment']),
        );
    }

    /**
     * @param array<string, mixed> $attributes
     * @param callable(CampaignUpdate): void|null $guard
     */
    private function transition(
        CampaignUpdate $update,
        CampaignUpdateStatus $target,
        User $actor,
        string $action,
        ?string $reason,
        array $attributes = [],
        ?callable $guard = null,
        bool $markReviewer = true,
    ): CampaignUpdate {
        $before = $update->only(self::AUDITED_FIELDS);
        $from = (string) $update->status;

        $updated = DB::transaction(function () use ($update, $target, $actor, $attributes, $guard, $markReviewer, &$from) {
            $locked = $this->lock($update);
            $current = $locked->statusEnum();

            if (! $current || ! $current->canTransitionTo($target)) {
                throw AdminActionException::invalidTransition('update campaign', (string) $locked->status, $target->value);
            }

            $from = $current->value;

            if ($guard) {
                $guard($locked);
            }

            $locked->forceFill(array_merge(
                $attributes,
                ['status' => $target->value],
                $markReviewer ? ['reviewed_by' => $actor->getKey(), 'reviewed_at' => now()] : [],
            ))->save();

            return $locked;
        });

        $this->audit->log($action, $updated, $before, $updated->only(self::AUDITED_FIELDS), $reason, $actor, $this->context());

        event(new CampaignUpdateStatusChanged($updated, $from, $target->value, $actor, $reason));

        return $updated->refresh();
    }

    private function lock(CampaignUpdate $update): CampaignUpdate
    {
        return CampaignUpdate::query()->whereKey($update->getKey())->lockForUpdate()->firstOrFail();
    }

    /** A linked payout must be a disbursed payout of the campaign's own organization. */
    private function assertPayoutMatchesCampaign(Campaign $campaign, string $payoutId, int $amountUsed): void
    {
        $payout = Payout::query()->find($payoutId);

        if (! $payout || $payout->organization_id !== $campaign->organization_id) {
            throw AdminActionException::requirement('Payout tidak terkait dengan organisasi pemilik campaign.');
        }

        if ($payout->status !== 'paid') {
            throw AdminActionException::requirement('Laporan penggunaan dana hanya dapat dikaitkan dengan payout yang sudah dibayar.');
        }

        if ($amountUsed > (int) $payout->amount) {
            throw AdminActionException::requirement('Nominal penggunaan dana melebihi nominal payout.');
        }
    }

    private function requireReason(string $reason, string $message): string
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw AdminActionException::requirement($message);
        }

        return $reason;
    }

    private function safeName(UploadedFile $file): string
    {
        $name = preg_replace('/[^\pL\pN._ -]+/u', '_', basename($file->getClientOriginalName())) ?: 'bukti';

        return Str::limit($name, 200, '');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function context(array $extra = []): array
    {
        return array_merge(['module' => 'Transparency', 'entity' => 'campaign_update'], $extra);
    }
}
