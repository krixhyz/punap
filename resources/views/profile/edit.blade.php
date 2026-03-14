@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <h1 class="text-3xl font-extrabold text-slate-900">Profile Settings</h1>

    <section class="surface-card p-6 sm:p-8">
        <div class="max-w-2xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </section>

    <section class="surface-card p-6 sm:p-8">
        <div class="max-w-2xl">
            @include('profile.partials.update-password-form')
        </div>
    </section>

    <section class="surface-card p-6 sm:p-8">
        <div class="max-w-2xl">
            @include('profile.partials.delete-user-form')
        </div>
    </section>
</div>
@endsection