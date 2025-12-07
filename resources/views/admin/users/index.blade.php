@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200">
    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold">Manage Users</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Role</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="p-3">{{ $user->name }}</td>
                        <td class="p-3">{{ $user->email }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select name="role" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                    <option value="user" @selected($user->role === 'user')>user</option>
                                    <option value="admin" @selected($user->role === 'admin')>admin</option>
                                </select>
                                <button class="px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700">Save</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.users.delete', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button class="px-3 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700"
                                        onclick="return confirm('Delete user?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4">
        {{ $users->links() }}
    </div>
</div>
@endsection