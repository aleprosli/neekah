<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUserAccount;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /** Columns for components/ui/DataTable.vue. */
    private const COLUMNS = [
        ['key' => 'name', 'label' => 'Nama', 'sortable' => true],
        ['key' => 'email', 'label' => 'Emel', 'sortable' => true],
        ['key' => 'role', 'label' => 'Peranan'],
        ['key' => 'bookings', 'label' => 'Tempahan', 'align' => 'right'],
        ['key' => 'joined', 'label' => 'Daftar', 'sort' => 'created_at', 'sortable' => true],
    ];

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'columns' => self::COLUMNS,
            'role' => UserRole::tryFrom($request->string('role')->toString()),
            'counts' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    /**
     * A page of accounts. Impersonation is offered per row, but only for the
     * accounts a policy actually allows it on.
     */
    public function data(Request $request): JsonResponse
    {
        $role = UserRole::tryFrom($request->string('role')->toString());
        $sort = in_array($request->string('sort')->toString(), ['name', 'email', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->withCount(['bookings', 'weddings'])
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user): array => [
                'url' => route('admin.users.show', $user),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->label().($user->isDeactivated() ? ' · dinyahaktif' : ''),
                'bookings' => $user->bookings_count,
                'joined' => $user->created_at->translatedFormat('j M Y'),
                'impersonate_url' => $user->canBeImpersonated() ? route('admin.users.impersonate', $user) : null,
            ])->all(),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * One account: what it has done, and the fixes an admin may apply to it.
     * Each action says why it is unavailable rather than simply disappearing.
     */
    public function show(Request $request, User $user): View
    {
        $user->loadCount(['weddings', 'createdWeddings', 'bookings', 'enquiries', 'reviews'])->load('vendor');
        $admin = $request->user();

        return view('admin.users.show', [
            'user' => $user,
            'props' => VueProps::for([
                'user' => [
                    'name' => $user->name,
                    'is_admin' => $user->isAdmin(),
                    'can_impersonate' => $user->canBeImpersonated(),
                ],
                'facts' => [
                    ['label' => 'Emel', 'value' => $user->email],
                    ['label' => 'Telefon', 'value' => $user->phone ?: '—'],
                    ['label' => 'Peranan', 'value' => $user->role->label()],
                    ['label' => 'Status', 'value' => $user->isDeactivated()
                        ? 'Dinyahaktif sejak '.$user->deactivated_at->translatedFormat('j M Y')
                        : 'Aktif'],
                    ['label' => 'Daftar', 'value' => $user->created_at->translatedFormat('j M Y').($user->google_id ? ' · Google' : '')],
                    ['label' => 'Majlis', 'value' => $user->weddings_count.' dikongsi · '.$user->created_weddings_count.' dicipta'],
                    ['label' => 'Tempahan (sebagai pengantin)', 'value' => $user->bookings_count],
                    ['label' => 'Enquiry · review', 'value' => $user->enquiries_count.' · '.$user->reviews_count],
                ],
                'actions' => $this->accountActions($user, $admin),
                'vendorForm' => $admin->can('switchToVendor', $user) ? VueProps::for([
                    'action' => route('admin.users.vendor.store', $user),
                    'loginUrl' => route('login'),
                    'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                    'states' => Vendor::STATES,
                    'old' => ['phone' => $user->phone, ...old()],
                    'account' => ['name' => $user->name, 'email' => $user->email],
                ]) : null,
            ]),
        ]);
    }

    /**
     * The fixes an admin may apply to one account, each carrying why it is not
     * available when it is not. None of them is silently dropped: an admin
     * looking for "delete" should read why it is refused, not wonder where it
     * went.
     *
     * @return array<int, array<string, mixed>>
     */
    private function accountActions(User $user, User $admin): array
    {
        $actions = [];

        if ($user->isCustomer()) {
            $allowed = $admin->can('switchToVendor', $user);
            $actions[] = [
                'key' => 'switchToVendor',
                'heading' => 'Tukar ke akaun vendor',
                'allowed' => $allowed,
                'body' => $allowed
                    ? 'Untuk vendor yang tersilap daftar sebagai pengantin. Isi maklumat perniagaan; profil akan menunggu kelulusan.'
                    : 'Tidak boleh: akaun ini ada tempahan sebagai pengantin. Nyahaktifkan akaun dan minta mereka daftar vendor dengan emel lain.',
            ];
        }

        if ($user->isVendor()) {
            $allowed = $admin->can('switchToCouple', $user);
            $actions[] = [
                'key' => 'switchToCouple',
                'heading' => 'Tukar ke akaun pengantin',
                'allowed' => $allowed,
                'body' => $allowed
                    ? 'Untuk pengantin yang tersilap daftar sebagai vendor. Profil vendor, pakej dan portfolio akan dipadam.'
                    : 'Tidak boleh: vendor ini sudah ada tempahan, enquiry atau review.',
                'label' => 'Tukar ke pengantin',
                'url' => route('admin.users.vendor.destroy', $user),
                'method' => 'DELETE',
                'tone' => 'danger',
                'confirm_title' => 'Tukar '.$user->name.' ke akaun pengantin?',
                'confirm_message' => 'Profil vendor, pakej dan gambar portfolio akaun ini akan dipadam. Tindakan ini tidak boleh diundur.',
                'confirm_label' => 'Ya, tukar',
            ];
        }

        $actions[] = $admin->can('reactivate', $user)
            ? [
                'key' => 'reactivate',
                'heading' => 'Aktifkan semula',
                'allowed' => true,
                'body' => 'Pengguna ini boleh log masuk semula.'.($user->vendor ? ' Profil vendor kekal digantung sehingga diluluskan semula.' : ''),
                'label' => 'Aktifkan semula',
                'url' => route('admin.users.reactivate', $user),
                'method' => 'DELETE',
                'confirm_title' => 'Aktifkan semula '.$user->name.'?',
                'confirm_message' => 'Pengguna ini akan boleh log masuk semula.',
                'confirm_label' => 'Ya, aktifkan',
            ]
            : [
                'key' => 'deactivate',
                'heading' => 'Nyahaktifkan akaun',
                'allowed' => $admin->can('deactivate', $user),
                'body' => 'Pengguna dilog keluar dan tidak boleh log masuk. Semua rekod dikekalkan.'.($user->vendor ? ' Profil vendor turut digantung.' : ''),
                'label' => 'Nyahaktifkan',
                'url' => route('admin.users.deactivate', $user),
                'method' => 'POST',
                'tone' => 'danger',
                'confirm_title' => 'Nyahaktifkan '.$user->name.'?',
                'confirm_message' => 'Pengguna ini akan dilog keluar dan tidak boleh log masuk sehingga diaktifkan semula.',
                'confirm_label' => 'Ya, nyahaktifkan',
            ];

        $canDelete = $admin->can('delete', $user);
        $actions[] = [
            'key' => 'delete',
            'heading' => 'Padam akaun',
            'allowed' => $canDelete,
            'body' => $canDelete
                ? 'Memadam akaun ini beserta majlis, enquiry dan gambar yang dimuat naik.'
                : 'Tidak boleh dipadam: akaun ini ada tempahan, atau majlis yang dikongsi dengan pengguna lain. Nyahaktifkan sahaja supaya rekod kekal.',
            'label' => 'Padam akaun',
            'url' => route('admin.users.destroy', $user),
            'method' => 'DELETE',
            'tone' => 'danger',
            'confirm_title' => 'Padam akaun '.$user->name.'?',
            'confirm_message' => 'Akaun, majlis, enquiry, review dan gambar yang dimuat naik akan dipadam kekal. Tindakan ini tidak boleh diundur.',
            'confirm_label' => 'Ya, padam kekal',
        ];

        return $actions;
    }

    public function destroy(Request $request, User $user, DeleteUserAccount $deleteUserAccount): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $deleteUserAccount->handle($user, $request->user());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akaun '.$user->name.' ('.$user->email.') telah dipadam.');
    }
}
