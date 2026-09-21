<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUserAccount;
use App\Enums\UserRole;
use App\Enums\UserSegment;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Support\States;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /** Columns for components/ui/DataTable.vue. */
    /**
     * A constant cannot hold a function call, and these labels are
     * translated now.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'name', 'label' => __('props.admin.nama'), 'sortable' => true],
            ['key' => 'email', 'label' => __('props.admin.emel_2'), 'sortable' => true],
            ['key' => 'role', 'label' => __('props.admin.peranan')],
            ['key' => 'bookings', 'label' => __('props.admin.tempahan_2'), 'align' => 'right'],
            ['key' => 'joined', 'label' => __('props.admin.daftar'), 'sort' => 'created_at', 'sortable' => true],
        ];
    }

    /**
     * A segment is already one role, so the role column would repeat itself.
     * What the team needs instead is a phone number to call and, on the last
     * column, how far this account actually got.
     */
    /**
     * A constant cannot hold a function call, and these labels are
     * translated now.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function segmentColumns(): array
    {
        return [
            ['key' => 'name', 'label' => __('props.admin.nama_2'), 'sortable' => true],
            ['key' => 'email', 'label' => __('props.admin.emel_3'), 'sortable' => true],
            ['key' => 'phone', 'label' => __('props.admin.telefon')],
            ['key' => 'joined', 'label' => __('props.admin.daftar_2'), 'sort' => 'created_at', 'sortable' => true],
            ['key' => 'progress', 'label' => __('props.admin.setakat_ini'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $segment = UserSegment::tryFrom($request->string('segment')->toString());
        $counts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('admin.users.index', [
            'columns' => $segment ? self::segmentColumns() : self::columns(),
            // Two groups the table swaps between in place. They are exclusive:
            // a segment is already one role, and the data endpoint ignores the
            // role once a segment is asked for.
            'filters' => [
                [
                    'key' => 'role',
                    'exclusive' => true,
                    'value' => $segment ? null : UserRole::tryFrom($request->string('role')->toString())?->value,
                    'allLabel' => __('props.common.all'),
                    'allCount' => $counts->sum(),
                    'options' => array_map(fn (UserRole $case): array => [
                        'value' => $case->value,
                        'label' => $case->label(),
                        'count' => $counts[$case->value] ?? 0,
                    ], UserRole::cases()),
                ],
                [
                    'key' => 'segment',
                    'exclusive' => true,
                    'label' => __('props.admin.perlu_diikuti'),
                    'value' => $segment?->value,
                    'allLabel' => __('props.common.none'),
                    'hint' => __('props.admin.pilih_satu_kumpulan_untuk_melihat'),
                    'options' => array_map(fn (array $row): array => [
                        'value' => $row['segment']->value,
                        'label' => $row['segment']->label(),
                        'count' => number_format($row['total']),
                        'description' => $row['segment']->description(),
                    ], $this->segmentCounts()),
                ],
            ],
        ]);
    }

    /**
     * Every segment with how many accounts are sitting in it. Five counts on
     * one page load, which is what makes the chips worth reading at a glance;
     * they are the whole analytic, so they are not worth caching until the
     * table itself is slow.
     *
     * @return array<int, array{segment: UserSegment, total: int}>
     */
    private function segmentCounts(): array
    {
        return array_map(
            fn (UserSegment $segment): array => [
                'segment' => $segment,
                'total' => $segment->apply(User::query())->count(),
            ],
            UserSegment::cases(),
        );
    }

    /**
     * A page of accounts. Impersonation is offered per row, but only for the
     * accounts a policy actually allows it on.
     */
    public function data(Request $request): JsonResponse
    {
        $segment = UserSegment::tryFrom($request->string('segment')->toString());
        $role = $segment ? null : UserRole::tryFrom($request->string('role')->toString());
        $sort = in_array($request->string('sort')->toString(), ['name', 'email', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $matching = User::query()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            });

        $users = User::query()
            ->withCount(['bookings', 'weddings'])
            ->when($segment, fn ($query) => $segment->apply($query))
            ->when($segment, fn ($query) => $query->with($this->segmentRelations($segment)))
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
                'phone' => $user->phone ?: '—',
                'role' => $user->role->label().($user->isDeactivated() ? ' · dinyahaktif' : ''),
                'bookings' => $user->bookings_count,
                'joined' => $user->created_at->translatedFormat('j M Y'),
                'progress' => $segment ? $this->progressPill($user, $segment) : null,
                'impersonate_url' => $user->canBeImpersonated() ? route('admin.users.impersonate', $user) : null,
            ])->all(),
            // The chips count what the search left, and a segment is counted
            // through its own query, the same one the rows come from.
            'filters' => [
                'role' => TableFilter::countsByColumn($matching, 'role'),
                'segment' => collect(UserSegment::cases())
                    ->mapWithKeys(fn (UserSegment $case): array => [$case->value => $case->apply($matching->clone())->count()])
                    ->prepend($matching->clone()->count(), '')
                    ->all(),
            ],
            // A segment view needs its own columns (a phone number to call and
            // how far the account got), and the table now switches without a
            // page load, so the columns travel with the rows.
            'columns' => $segment ? self::segmentColumns() : self::columns(),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * What each segment's rows need loaded so the last column costs no query
     * of its own. The vendor counts are the ones hasCompleteCatalogue() reads.
     *
     * @return array<string, \Closure>
     */
    private function segmentRelations(UserSegment $segment): array
    {
        return $segment->role() === UserRole::Vendor
            ? ['vendor' => fn ($vendor) => $vendor->withCount([
                'packages as active_packages_count' => fn ($packages) => $packages->where('is_active', true),
                'portfolioItems',
            ])]
            : ['createdWeddings' => fn ($weddings) => $weddings->orderBy('event_date')];
    }

    /**
     * How far this account got, said in the fewest words that still tell the
     * team what to open the call with.
     */
    private function progressPill(User $user, UserSegment $segment): string
    {
        [$label, $tone] = match ($segment->role()) {
            UserRole::Vendor => $this->vendorProgress($user),
            default => $this->coupleProgress($user),
        };

        return view('components.admin.status-pill', ['label' => $label, 'tone' => $tone])->render();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function vendorProgress(User $user): array
    {
        if ($user->vendor === null) {
            return ['Tiada profil', 'red'];
        }

        $missing = array_values(array_filter([
            $user->vendor->hasCompleteProfile() ? null : 'profil',
            $user->vendor->hasCompleteCatalogue() ? null : 'pakej & gambar',
        ]));

        return $missing === []
            ? ['Lengkap', 'emerald']
            : ['Perlu '.implode(' dan ', $missing), 'amber'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function coupleProgress(User $user): array
    {
        $wedding = $user->createdWeddings->first();

        return $wedding === null
            ? [__('props.admin.tiada_majlis'), 'amber']
            : [$wedding->event_date?->translatedFormat('j M Y') ?? __('props.admin.date_not_set'), 'sky'];
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
                    ['label' => __('props.admin.emel_4'), 'value' => $user->email],
                    ['label' => __('props.admin.telefon_2'), 'value' => $user->phone ?: '—'],
                    ['label' => __('props.admin.peranan_2'), 'value' => $user->role->label()],
                    ['label' => __('props.admin.status_5'), 'value' => $user->isDeactivated()
                        ? 'Dinyahaktif sejak '.$user->deactivated_at->translatedFormat('j M Y')
                        : 'Aktif'],
                    ['label' => __('props.admin.daftar_3'), 'value' => $user->created_at->translatedFormat('j M Y').($user->google_id ? ' · Google' : '')],
                    ['label' => __('props.admin.majlis_2'), 'value' => __('props.units.shared_created', ['shared' => $user->weddings_count, 'created' => $user->created_weddings_count])],
                    ['label' => __('props.admin.tempahan_sebagai_pengantin'), 'value' => $user->bookings_count],
                    ['label' => __('props.admin.enquiry_review'), 'value' => $user->enquiries_count.' · '.$user->reviews_count],
                ],
                'actions' => $this->accountActions($user, $admin),
                'vendorForm' => $admin->can('switchToVendor', $user) ? VueProps::for([
                    'action' => route('admin.users.vendor.store', $user),
                    'loginUrl' => route('login'),
                    'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                    'states' => States::options(),
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
                'heading' => __('props.admin.tukar_ke_akaun_vendor'),
                'allowed' => $allowed,
                'body' => $allowed
                    ? __('props.admin.convert_to_vendor_yes')
                    : __('props.admin.convert_to_vendor_no'),
            ];
        }

        if ($user->isVendor()) {
            $allowed = $admin->can('switchToCouple', $user);
            $actions[] = [
                'key' => 'switchToCouple',
                'heading' => __('props.admin.tukar_ke_akaun_pengantin'),
                'allowed' => $allowed,
                'body' => $allowed
                    ? __('props.admin.convert_to_couple_yes')
                    : __('props.admin.convert_to_couple_no'),
                'label' => __('props.admin.tukar_ke_pengantin'),
                'url' => route('admin.users.vendor.destroy', $user),
                'method' => 'DELETE',
                'tone' => 'danger',
                'confirm_title' => __('props.admin.tukar').$user->name.' ke akaun pengantin?',
                'confirm_message' => __('props.admin.profil_vendor_pakej_dan_gambar'),
                'confirm_label' => 'Ya, tukar',
            ];
        }

        $actions[] = $admin->can('reactivate', $user)
            ? [
                'key' => 'reactivate',
                'heading' => __('props.admin.aktifkan_semula'),
                'allowed' => true,
                'body' => __('props.admin.reactivate_body').($user->vendor ? __('props.admin.reactivate_body_vendor') : ''),
                'label' => __('props.admin.aktifkan_semula_2'),
                'url' => route('admin.users.reactivate', $user),
                'method' => 'DELETE',
                'confirm_title' => __('props.admin.aktifkan_semula_3').$user->name.'?',
                'confirm_message' => __('props.admin.pengguna_ini_akan_boleh_log'),
                'confirm_label' => 'Ya, aktifkan',
            ]
            : [
                'key' => 'deactivate',
                'heading' => __('props.admin.nyahaktifkan_akaun'),
                'allowed' => $admin->can('deactivate', $user),
                'body' => __('props.admin.deactivate_body').($user->vendor ? __('props.admin.deactivate_body_vendor') : ''),
                'label' => __('props.admin.nyahaktifkan'),
                'url' => route('admin.users.deactivate', $user),
                'method' => 'POST',
                'tone' => 'danger',
                'confirm_title' => __('props.admin.nyahaktifkan_2').$user->name.'?',
                'confirm_message' => __('props.admin.pengguna_ini_akan_dilog_keluar'),
                'confirm_label' => 'Ya, nyahaktifkan',
            ];

        $canDelete = $admin->can('delete', $user);
        $actions[] = [
            'key' => 'delete',
            'heading' => __('props.admin.padam_akaun'),
            'allowed' => $canDelete,
            'body' => $canDelete
                ? __('props.admin.delete_yes')
                : __('props.admin.delete_no'),
            'label' => __('props.admin.padam_akaun_2'),
            'url' => route('admin.users.destroy', $user),
            'method' => 'DELETE',
            'tone' => 'danger',
            'confirm_title' => __('props.admin.padam_akaun_3').$user->name.'?',
            'confirm_message' => __('props.admin.akaun_majlis_enquiry_review_dan'),
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
            ->with('status', __('flash.admin.user_deleted', ['name' => $user->name, 'email' => $user->email]));
    }
}
