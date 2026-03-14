@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="text-3xl font-bold">Manage Users</h2>

        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..."
                       class="border border-slate-300 rounded-xl px-3 py-2 text-sm min-w-60">
                <button class="px-4 py-2 rounded-xl border border-slate-300 bg-white">Filter</button>
            </form>
        </div>
    </div>

    @if(! $admin->isSuperAdmin())
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-blue-700">
            <p class="font-semibold">Limited Access</p>
            <p class="text-sm mt-1">You can manage regular users only. Cannot manage Admins or Super Admins. Cannot access sensitive payment details.</p>
        </div>
    @endif

    @if($admin->isSuperAdmin())
        <details class="mb-5 rounded-xl border border-slate-200 p-4">
            <summary class="cursor-pointer font-semibold text-purple-700">Create User</summary>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <input name="name" placeholder="Full name" class="border border-slate-300 rounded-lg px-3 py-2" required>
                <input type="email" name="email" placeholder="Email" class="border border-slate-300 rounded-lg px-3 py-2" required>
                <input type="password" name="password" placeholder="Password" class="border border-slate-300 rounded-lg px-3 py-2" required>
                <select name="role" class="border border-slate-300 rounded-lg px-3 py-2">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                    <option value="super_admin">super_admin</option>
                </select>
                <button class="rounded-lg bg-purple-600 text-white px-4 py-2">Create</button>
            </form>
        </details>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="p-3 text-left">User</th>
                    <th class="p-3 text-left">Role</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Joined</th>
                    <th class="p-3 text-left">Listings</th>
                    <th class="p-3 text-left">Transactions</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    @php
                        $listingsCount = $user->products()->count();
                        $transactionsCount = $user->orders()->count();
                        $canManage = $admin->canManageUser($user);
                        $status = $user->account_status ?? 'active';
                    @endphp
                    <tr>
                        <td class="p-3">
                            <p class="font-semibold">{{ $user->name }}</p>
                            <p class="text-slate-500">{{ $user->email }}</p>
                        </td>
                        <td class="p-3">
                            @if($admin->isSuperAdmin())
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="border border-slate-300 rounded px-2 py-1">
                                        <option value="user" @selected($user->role === 'user')>user</option>
                                        <option value="admin" @selected($user->role === 'admin')>admin</option>
                                        <option value="super_admin" @selected($user->role === 'super_admin')>super_admin</option>
                                    </select>
                                    <button class="px-2 py-1 rounded bg-indigo-600 text-white text-xs">Save</button>
                                </form>
                            @else
                                <span class="px-2 py-1 rounded text-xs {{ $user->role === 'user' ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-700' }}">{{ $user->role }}</span>
                            @endif
                        </td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.users.status', $user) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="account_status" class="border border-slate-300 rounded px-2 py-1" @disabled(!$canManage)>
                                    <option value="active" @selected($status === 'active')>active</option>
                                    <option value="suspended" @selected($status === 'suspended')>suspended</option>
                                    <option value="banned" @selected($status === 'banned')>banned</option>
                                </select>
                                <button class="px-2 py-1 rounded bg-emerald-600 text-white text-xs" @disabled(!$canManage)>Apply</button>
                            </form>
                        </td>
                        <td class="p-3">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="p-3">{{ $listingsCount }}</td>
                        <td class="p-3">{{ $transactionsCount }}</td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="px-2 py-1 rounded bg-blue-600 text-white text-xs">View</a>
                                <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}">
                                    @csrf
                                    <button class="px-2 py-1 rounded bg-amber-600 text-white text-xs" @disabled(!$canManage)>Reset</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.delete', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-2 py-1 rounded bg-red-600 text-white text-xs" @disabled(!$canManage)
                                            onclick="return confirm('Delete user?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
