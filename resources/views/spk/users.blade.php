@extends('layouts.spk')

@section('content')
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold">Data Pengguna Sistem</h5>
            <button class="btn btn-orange" data-bs-toggle="collapse" data-bs-target="#userFormCollapse">
                <i class="fa-solid fa-plus me-1"></i> Tambah User
            </button>
        </div>

        <div class="collapse mb-4" id="userFormCollapse">
            <div class="card card-body bg-light border-0">
                <form action="{{ route('users.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-orange w-100" type="submit">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('users.index') }}" method="GET" class="row mb-4">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama/email" value="{{ $currentQuery }}">
                </div>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select name="role" class="form-select">
                    <option value="all" {{ $currentRole === 'all' ? 'selected' : '' }}>Semua Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" {{ $currentRole === $role ? 'selected' : '' }}>{{ $role }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" type="submit">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-custom" id="userTable">
                <thead>
                <tr>
                    <th>Profil</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=FF7A00&color=fff" class="rounded-circle" width="40" alt="Avatar">
                        </td>
                        <td class="fw-bold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->role === 'Supervisor' ? 'bg-primary' : ($user->role === 'Admin' ? 'bg-success' : 'bg-secondary') }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary btn-edit-user" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}" data-user-email="{{ $user->email }}" data-user-role="{{ $user->role }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus pengguna ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Belum ada data user.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editName" class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="editRole" class="form-label">Role</label>
                        <select name="role" id="editRole" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                        <input type="password" name="password" id="editPassword" class="form-control">
                        <small class="text-muted">Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-orange">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.btn-edit-user');
            const editUserForm = document.getElementById('editUserForm');
            const editNameInput = document.getElementById('editName');
            const editEmailInput = document.getElementById('editEmail');
            const editRoleSelect = document.getElementById('editRole');

            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const userId = this.dataset.userId;
                    const userName = this.dataset.userName;
                    const userEmail = this.dataset.userEmail;
                    const userRole = this.dataset.userRole;

                    editUserForm.action = "{{ route('users.update', ['user' => 'USER_ID_PLACEHOLDER']) }}".replace('USER_ID_PLACEHOLDER', userId);
                    editNameInput.value = userName;
                    editEmailInput.value = userEmail;
                    editRoleSelect.value = userRole;

                    const editUserModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                    editUserModal.show();
                });
            });
        });
    </script>
@endpush
