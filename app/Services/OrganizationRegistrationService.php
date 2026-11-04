<?php

namespace App\Services;

use App\Http\Requests\PublicOrganizationRegistrationRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Entities\Organization;
use Modules\Core\Entities\OrganizationMember;

class OrganizationRegistrationService
{
    public function __construct(private readonly ImageUploadService $images)
    {
    }

    /** @return array{organization: Organization, reference: string} */
    public function register(PublicOrganizationRegistrationRequest $request): array
    {
        $data = $request->validated();
        $publicLogo = null;
        $privateDocument = null;

        try {
            if ($request->hasFile('logo')) {
                $publicLogo = $this->images->storeOrganizationLogo($request->file('logo'));
            }

            if ($request->hasFile('verification_document')) {
                $privateDocument = $request->file('verification_document')->store('organizations/verification', 'private');
            }

            return DB::transaction(function () use ($data, $publicLogo, $privateDocument): array {
                $user = User::create([
                    'name' => $data['responsible_name'],
                    'email' => $data['email'],
                    'mobile' => $data['mobile'],
                    'password' => Hash::make($data['password']),
                    'role' => 'organization_admin',
                    'is_active' => false,
                ]);

                $organization = Organization::create([
                    'slug' => $this->uniqueSlug($data['name']),
                    'name' => $data['name'],
                    'organization_type' => $data['organization_type'],
                    'responsible_name' => $data['responsible_name'],
                    'logo_url' => $publicLogo,
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'province' => $data['province'],
                    'contact_email' => $data['email'],
                    'contact_phone' => $data['mobile'],
                    'website_url' => $data['website_url'] ?? null,
                    'instagram_url' => $data['instagram_url'] ?? null,
                    'facebook_url' => $data['facebook_url'] ?? null,
                    'status' => 'pending',
                    'application_reference' => $this->reference(),
                    'verification_document_path' => $privateDocument,
                ]);

                OrganizationMember::create([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                ]);

                return ['organization' => $organization, 'reference' => $organization->application_reference];
            });
        } catch (\Throwable $exception) {
            if ($publicLogo) {
                $this->images->deleteOwnedFile($publicLogo);
            }
            if ($privateDocument) {
                Storage::disk('private')->delete($privateDocument);
            }

            throw $exception;
        }
    }

    public function findByReferenceAndEmail(string $reference, string $email): ?Organization
    {
        return Organization::query()
            ->where('application_reference', strtoupper(trim($reference)))
            ->whereRaw('LOWER(contact_email) = ?', [strtolower(trim($email))])
            ->first();
    }

    /** @return array{organization: Organization, reference: string} */
    public function updateRejected(PublicOrganizationRegistrationRequest $request): array
    {
        $data = $request->validated();
        $reference = (string) ($data['application_reference'] ?? '');
        $organization = Organization::query()->where('application_reference', $reference)->firstOrFail();
        abort_unless($organization->isRejected(), 403);

        $owner = $organization->members()->where('role', 'owner')->with('user')->firstOrFail()->user;
        abort_unless(strtolower((string) $owner->email) === strtolower((string) $data['email']), 403);
        $publicLogo = null;
        $privateDocument = null;
        $oldLogo = $organization->logo_url;
        $oldDocument = $organization->verification_document_path;

        try {
            if ($request->hasFile('logo')) {
                $publicLogo = $this->images->storeOrganizationLogo($request->file('logo'));
            }
            if ($request->hasFile('verification_document')) {
                $privateDocument = $request->file('verification_document')->store('organizations/verification', 'private');
            }

            $updated = DB::transaction(function () use ($data, $organization, $owner, $publicLogo, $privateDocument): Organization {
                $organization->update([
                    'name' => $data['name'],
                    'organization_type' => $data['organization_type'],
                    'responsible_name' => $data['responsible_name'],
                    'logo_url' => $publicLogo ?: $organization->logo_url,
                    'description' => $data['description'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'province' => $data['province'],
                    'contact_email' => $data['email'],
                    'contact_phone' => $data['mobile'],
                    'website_url' => $data['website_url'] ?? null,
                    'instagram_url' => $data['instagram_url'] ?? null,
                    'facebook_url' => $data['facebook_url'] ?? null,
                    'status' => 'pending',
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'verification_document_path' => $privateDocument ?: $organization->verification_document_path,
                ]);
                $owner->update([
                    'name' => $data['responsible_name'],
                    'email' => $data['email'],
                    'mobile' => $data['mobile'],
                    'password' => Hash::make($data['password']),
                    'is_active' => false,
                ]);

                return $organization->fresh();
            });

            if ($publicLogo && $oldLogo) $this->images->deleteOwnedFile($oldLogo);
            if ($privateDocument && $oldDocument) Storage::disk('private')->delete($oldDocument);

            return ['organization' => $updated, 'reference' => $updated->application_reference];
        } catch (\Throwable $exception) {
            if ($publicLogo) $this->images->deleteOwnedFile($publicLogo);
            if ($privateDocument) Storage::disk('private')->delete($privateDocument);
            throw $exception;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'lembaga';
        $slug = $base;
        $suffix = 2;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function reference(): string
    {
        do {
            $reference = 'SM-'.Str::upper(Str::random(10));
        } while (Organization::withTrashed()->where('application_reference', $reference)->exists());

        return $reference;
    }
}
