@extends('admin-dashboard::layouts.app')

@section('title', $title ?? config('admin-dashboard.title'))

@section('content')
    <div class="admin-dashboard">
        <h1>{{ $title ?? 'Dashboard' }}</h1>

        <div class="admin-dashboard__grid">
            {{-- Feature cards — rendered conditionally based on feature flags --}}

            @adminFeature('audit_logs')
                <div class="admin-card">
                    <h3>Audit Logs</h3>
                    <p>Review recent system activity and user actions.</p>
                </div>
            @endadminFeature

            @adminFeature('notifications')
                <div class="admin-card">
                    <h3>Notifications</h3>
                    <p>Manage system notifications and alerts.</p>
                </div>
            @endadminFeature

            @adminFeature('file_manager')
                <div class="admin-card">
                    <h3>File Manager</h3>
                    <p>Browse and manage uploaded files.</p>
                </div>
            @endadminFeature

            @adminFeature('two_factor_auth')
                <div class="admin-card">
                    <h3>Two-Factor Authentication</h3>
                    <p>Manage 2FA settings for admin users.</p>
                </div>
            @endadminFeature
        </div>
    </div>
@endsection
