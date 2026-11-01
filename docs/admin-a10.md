# A10 — User, Role, Category, dan Configuration

Dokumen ini merangkum implementasi Agent OpenCode untuk paket **Agent C — A10 User, Role, Category, Configuration** sesuai `docs/pembagian-tugas-admin.md`.

## Status

Status keseluruhan: `DONE` untuk user admin, role summary, dan kategori. Payment channel/fee tetap menjadi handoff A7.

- **User admin**: daftar, filter role/status, pencarian, detail, aktivasi/nonaktifasi, ubah role, kirim tautan reset password. `DONE`.
- **Role**: halaman ringkasan role + jumlah pemakai + permission, guard agar role yang masih dipakai tidak dapat dihapus. `DONE`.
- **Kategori**: CRUD, slug unik, status aktif, proteksi kategori yang dipakai campaign. `DONE`.
- **Payment channel/fee configuration**: **handoff ke Agent D**. Agent D sudah memiliki ownership eksplisit untuk payment channel/fee pada prompt A6/A7. Tidak ada file Agent D yang diubah.
- **Template notifikasi/email**: belum dikerjakan; bergantung pada A11.

## Workspace dan branch

- Worktree: `/private/var/folders/lk/4fc4gmld0z517l55lkg8j6lr0000gn/T/opencode/salamaid-a10`
- Branch: `agent-opencode-a10`
- Baseline repo dibuat karena workspace awal belum memiliki metadata git. Branch `main` berisi snapshot sebelum pekerjaan A10.

## Route

Route berada di `routes/admin-master.php` dan di-include dari `routes/admin.php` di dalam group `['auth', 'admin']`.

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/admin/users` | `admin.users.index` | `users.roles.manage` |
| GET | `/admin/users/{user}` | `admin.users.show` | `users.roles.manage` |
| PATCH | `/admin/users/{user}/role` | `admin.users.role.update` | `users.roles.manage` |
| POST | `/admin/users/{user}/activate` | `admin.users.activate` | `users.roles.manage` |
| POST | `/admin/users/{user}/deactivate` | `admin.users.deactivate` | `users.roles.manage` |
| POST | `/admin/users/{user}/password-reset` | `admin.users.password-reset` | `users.roles.manage` + throttle 6/menit |
| GET | `/admin/roles` | `admin.roles.index` | `users.roles.manage` |
| DELETE | `/admin/roles/{role}` | `admin.roles.destroy` | `users.roles.manage` |
| GET | `/admin/categories` | `admin.categories.index` | `categories.manage` |
| GET | `/admin/categories/create` | `admin.categories.create` | `categories.manage` |
| POST | `/admin/categories` | `admin.categories.store` | `categories.manage` |
| GET | `/admin/categories/{category}/edit` | `admin.categories.edit` | `categories.manage` |
| PATCH | `/admin/categories/{category}` | `admin.categories.update` | `categories.manage` |
| POST | `/admin/categories/{category}/activate` | `admin.categories.activate` | `categories.manage` |
| POST | `/admin/categories/{category}/deactivate` | `admin.categories.deactivate` | `categories.manage` |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | `categories.manage` |

Permission mengikuti `config/admin.php` tanpa perubahan:

- `users.roles.manage` hanya diberikan ke `super_admin` melalui `*`.
- `categories.manage` diberikan ke `admin` dan `super_admin`.

Sidebar Agent A sudah memiliki link `/admin/categories` dan `/admin/users`, sehingga tidak ada perubahan layout.

## Aturan bisnis

### User

- Role hanya boleh salah satu key `config('admin.roles')`.
- Pengguna tidak dapat mengubah role akun sendiri.
- Pengguna tidak dapat menonaktifkan akun sendiri.
- Super admin aktif terakhir tidak dapat dihapus/dinonaktifkan/diturunkan role-nya.
- Aktivasi/nonaktifasi bersifat idempotent (tidak menulis audit jika status tidak berubah).
- Reset password memakai `Password::sendResetLink()` (flow reset link Laravel). Password tidak pernah ditampilkan, diubah langsung, atau dicatat pada audit.
- Route reset password dibatasi `throttle:6,1`.

### Role

- Role dan permission bersumber dari `config/admin.php`; panel tidak menyediakan editor config.
- Halaman role menampilkan jumlah pengguna total dan aktif per role.
- Endpoint hapus role selalu menolak:
  - role yang masih dipakai → pesan “masih dipakai oleh N user”;
  - role kosong → pesan bahwa role dikelola melalui `config/admin.php`.
- Tidak ada role yang dapat dihapus dari panel, sehingga acceptance “role tidak dapat dihapus jika masih dipakai” terpenuhi secara eksplisit.

### Kategori

- `name` wajib, maksimal 100 karakter.
- `slug` unik; jika kosong dibuat otomatis dari nama.
- Slug dicek dua lapis: Form Request `Rule::unique` dan service (untuk slug yang di-generate).
- `is_active` default `true`; kategori nonaktif tidak ditawarkan untuk campaign baru.
- Kategori yang masih dipakai campaign **termasuk campaign soft-deleted** tidak dapat dihapus.
- Perubahan kategori dicatat pada audit logger dan event domain.

## File yang ditambahkan/diubah

### Migration dan model

- `Modules/Core/Database/Migrations/2026_11_01_000050_add_is_active_to_categories_table.php` (baru)
- `Modules/Core/Entities/Category.php` (diubah: `is_active`, cast, scope, relasi `campaigns`)

### Policy dan provider

- `app/Policies/UserPolicy.php` (diubah: `viewAny`, `view`, `updateRole`, `activate`, `deactivate`, `sendPasswordResetLink`)
- `Modules/Core/App/Policies/CategoryPolicy.php` (baru)
- `app/Providers/AuthServiceProvider.php` (diubah: registrasi `CategoryPolicy`)

### Service, query, event

- `app/Services/Admin/UserManagementService.php` (baru)
- `app/Services/Admin/RoleManagementService.php` (baru)
- `Modules/Core/App/Services/CategoryManagementService.php` (baru)
- `app/Queries/Admin/UserManagementQuery.php` (baru)
- `Modules/Core/App/Queries/CategoryManagementQuery.php` (baru)
- `app/Events/Admin/UserRoleChanged.php` (baru)
- `app/Events/Admin/UserStatusChanged.php` (baru)
- `app/Events/Admin/UserPasswordResetLinkSent.php` (baru)
- `Modules/Core/App/Events/CategoryChanged.php` (baru)

### HTTP

- `app/Http/Controllers/Admin/UserAdminController.php` (baru)
- `app/Http/Controllers/Admin/RoleAdminController.php` (baru)
- `Modules/Core/App/Http/Controllers/Admin/CategoryAdminController.php` (baru)
- `app/Http/Requests/Admin/UserIndexRequest.php` (baru)
- `app/Http/Requests/Admin/UpdateUserRoleRequest.php` (baru)
- `app/Http/Requests/Admin/ActivateUserRequest.php` (baru)
- `app/Http/Requests/Admin/DeactivateUserRequest.php` (baru)
- `app/Http/Requests/Admin/SendUserPasswordResetRequest.php` (baru)
- `Modules/Core/App/Http/Requests/CategoryIndexRequest.php` (baru)
- `Modules/Core/App/Http/Requests/StoreCategoryRequest.php` (baru)
- `Modules/Core/App/Http/Requests/UpdateCategoryRequest.php` (baru)
- `Modules/Core/App/Http/Requests/CategoryActionRequest.php` (baru)
- `routes/admin-master.php` (baru)
- `routes/admin.php` (diubah satu baris: `require base_path('routes/admin-master.php')`)

### View

- `resources/views/admin/users/index.blade.php` (baru)
- `resources/views/admin/users/show.blade.php` (baru)
- `resources/views/admin/roles/index.blade.php` (baru)
- `resources/views/admin/categories/index.blade.php` (baru)
- `resources/views/admin/categories/create.blade.php` (baru)
- `resources/views/admin/categories/edit.blade.php` (baru)
- `resources/views/admin/categories/_form.blade.php` (baru)

View memakai layout `admin.layouts.app` dan komponen `x-admin.*` Agent A.

### Test

- `tests/Feature/Admin/UserManagementTest.php` (baru, 10 test)
- `tests/Feature/Admin/CategoryManagementTest.php` (baru, 11 test)

## Audit dan event

Audit memakai kontrak existing `App\Contracts\AdminAuditLogger` (tidak membuat logger baru):

| Action | Subject |
|---|---|
| `user.role_changed` | User |
| `user.activated` | User |
| `user.deactivated` | User |
| `user.password_reset_link_sent` | User |
| `category.created` | Category |
| `category.updated` | Category |
| `category.activated` | Category |
| `category.deactivated` | Category |
| `category.deleted` | Category |

Payload audit hanya berisi field non-sensitif (`role`, `is_active`, `name`, `slug`, `icon`). Tidak ada password, token reset, atau secret.

Event domain:

- `App\Events\Admin\UserRoleChanged`
- `App\Events\Admin\UserStatusChanged`
- `App\Events\Admin\UserPasswordResetLinkSent`
- `Modules\Core\App\Events\CategoryChanged`

## Validasi

Semua command dijalankan dari worktree dengan `rtk`:

```bash
export PATH="/Users/fcn/.local/bin:/opt/homebrew/bin:$PATH"
rtk php artisan test
rtk php artisan view:cache
rtk php artisan route:cache
rtk npm run build
```

Hasil:

- `rtk php artisan test` → **79 passed (464 assertions)**
- `rtk php artisan view:cache` → **Blade templates cached successfully**
- `rtk php artisan route:cache` → **Routes cached successfully**
- `rtk npm run build` → **built in 261ms**
- `migrate:fresh --seed` pada SQLite sementara → **semua migration + DevelopmentSeeder sukses**
- rollback `2026_11_01_000050_add_is_active_to_categories_table` lalu migrate ulang → **sukses**

## Handoff dan blocker

1. **Payment channel/fee configuration** — tidak dikerjakan karena Agent D memiliki ownership eksplisit. Schema `payment_channels`/`payment_fees` sudah ada dan DevelopmentSeeder sudah mengisi data demo. Agent D dapat langsung membangun CRUD/effective-date di area `Modules/Payment`.
2. **Role/permission editing** — role dan permission tetap config-driven. Jika product membutuhkan role CRUD dari UI, perlu tabel role/permission baru dan perubahan `hasAdminPermission()`, yang berada di luar batasan “jangan ubah `app/Models/User.php`”.
3. **Notification template/email** — belum ada; menunggu A11.
4. **Audit persistence** — audit saat ini masuk ke log aplikasi melalui `LogAdminAuditLogger`; A11 perlu mengganti binding ke logger database.
5. **`rtk`** — binary tidak ada di PATH default; tersedia di `/Users/fcn/.local/bin/rtk`. Semua validasi final memakai prefix `rtk`.
