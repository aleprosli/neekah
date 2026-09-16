<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->label(),
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
}
