<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleCode;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\AcademicClass;
use App\Models\HomeroomAssignment;
use App\Models\ReportSignature;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $teachers = $this->teacherQuery($request->user())
            ->with('staffProfile')
            ->withCount(['subjectAssignments', 'homeroomClasses'])
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = strtoupper($request->string('status')->toString());

                if (in_array($status, array_column(UserStatus::cases(), 'value'), true)) {
                    $query->where('status', $status);
                }
            })
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());

                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('staffProfile', fn (Builder $profileQuery): Builder => $profileQuery->where('employee_number', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $teacher = new User(['status' => UserStatus::Active]);
        $teacher->setRelation('staffProfile', new StaffProfile);

        return view('admin.teachers.create', compact('teacher'));
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();

        $teacher = DB::transaction(function () use ($data, $request): User {
            $teacher = User::query()->create([
                'institution_id' => $request->user()->institution_id,
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'status' => $data['status'],
            ]);

            $guruRole = Role::query()->firstOrCreate(
                ['code' => RoleCode::Guru->value],
                ['name' => RoleCode::Guru->label()],
            );
            $teacher->roles()->sync([$guruRole->getKey()]);
            $this->saveStaffProfile($teacher, $data);

            return $teacher;
        });

        return redirect()
            ->route('admin.teachers.show', $teacher)
            ->with('status', 'Akun Guru berhasil dibuat.');
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $teacher = $user->load('staffProfile')->loadCount(['subjectAssignments', 'homeroomClasses', 'reportSignatures']);

        return view('admin.teachers.show', compact('teacher'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $teacher = $user->load('staffProfile');

        return view('admin.teachers.edit', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validated();

        DB::transaction(function () use ($data, $user): void {
            $user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'status' => $data['status'],
            ]);
            $this->saveStaffProfile($user, $data);
        });

        return redirect()
            ->route('admin.teachers.show', $user)
            ->with('status', 'Data Guru berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($this->hasAssignmentHistory($user)) {
            $user->update(['status' => UserStatus::Inactive]);

            return redirect()
                ->route('admin.teachers.index')
                ->with('status', 'Guru memiliki riwayat penugasan sehingga akun dinonaktifkan, bukan dihapus.');
        }

        DB::transaction(function () use ($user): void {
            $user->roles()->detach();
            $user->delete();
        });

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Akun Guru berhasil dihapus.');
    }

    /**
     * @return Builder<User>
     */
    private function teacherQuery(User $admin): Builder
    {
        return User::query()
            ->where('institution_id', $admin->institution_id)
            ->whereHas('roles', fn (Builder $roles): Builder => $roles->where('code', RoleCode::Guru->value));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveStaffProfile(User $teacher, array $data): void
    {
        StaffProfile::query()->updateOrCreate(
            ['user_id' => $teacher->getKey()],
            [
                'employee_number' => $data['employee_number'] ?? null,
                'title' => $data['title'] ?? null,
                'phone' => $data['phone'] ?? null,
            ],
        );
    }

    private function hasAssignmentHistory(User $teacher): bool
    {
        return SubjectAssignment::query()->where('teacher_id', $teacher->getKey())->exists()
            || AcademicClass::query()->where('homeroom_teacher_id', $teacher->getKey())->exists()
            || HomeroomAssignment::query()->where('teacher_id', $teacher->getKey())->exists()
            || ReportSignature::query()->where('user_id', $teacher->getKey())->exists();
    }
}
