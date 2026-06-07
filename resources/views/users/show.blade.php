@extends('admin-dashboard::layouts.app')

@section('title', $user->name . ' Profile - ' . config('admin-dashboard.title', 'Admin Dashboard'))

@section('sidebar')
    <ul class="admin-sidebar-nav">
        <li>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        </li>
        <li class="active">
            <a href="{{ route('admin.users.index') }}">Users</a>
        </li>
    </ul>
@endsection

@section('content')
    {{-- Impersonation active banner --}}
    @if(session()->has('impersonator_id'))
        <div class="impersonation-banner">
            <span>You are currently impersonating a user.</span>
            <form action="{{ route('admin.users.stop-impersonating') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit">Return to Admin</button>
            </form>
        </div>
    @endif

    <div class="admin-header">
        <h1>User Profile: {{ $user->name }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="admin-btn admin-btn--primary">
                Edit Profile
            </a>
            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn--secondary">
                Back to List
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(method_exists($user, 'isSuspended') && $user->isSuspended())
        <div class="admin-alert admin-alert--error" style="margin-bottom: 2rem;">
            <strong>Account Suspended:</strong> This user account was suspended on {{ $user->suspended_at->format('M d, Y H:i') }}.
            <div style="margin-top: 0.25rem;">Reason: <em>{{ $user->suspension_reason }}</em></div>
        </div>
    @endif

    <div class="admin-form-row" style="align-items: start; grid-template-columns: 2fr 1fr;">
        {{-- Profile Content Panel --}}
        <div>
            {{-- Main User Info Card --}}
            <div class="admin-card">
                <div class="user-profile-header">
                    @php
                        $photoUrl = $user->profile_photo_path 
                            ? Storage::disk(config('admin-dashboard.uploads.disk', 'public'))->url($user->profile_photo_path)
                            : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp';
                    @endphp
                    <img src="{{ $photoUrl }}" alt="{{ $user->name }}" class="admin-avatar admin-avatar--lg">
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">{{ $user->name }}</h2>
                        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">{{ $user->email }}</p>
                        
                        <div style="margin-top: 0.5rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            @if(method_exists($user, 'trashed') && $user->trashed())
                                <span class="admin-badge admin-badge--danger">Deleted</span>
                            @elseif(method_exists($user, 'isSuspended') && $user->isSuspended())
                                <span class="admin-badge admin-badge--warning">Suspended</span>
                            @else
                                <span class="admin-badge admin-badge--success">Active</span>
                            @endif

                            @if($user->two_factor_enabled)
                                <span class="admin-badge admin-badge--info">2FA Enabled</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--admin-border); padding-top: 1.5rem; margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Phone</span>
                        <span style="font-size: 0.95rem; font-weight: 500;">{{ $user->phone ?? 'Not provided' }}</span>
                    </div>
                    <div>
                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Joined At</span>
                        <span style="font-size: 0.95rem; font-weight: 500;">{{ $user->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div>
                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Last Login</span>
                        <span style="font-size: 0.95rem; font-weight: 500;">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</span>
                    </div>
                    <div>
                        <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Last Login IP</span>
                        <span style="font-size: 0.95rem; font-weight: 500;">{{ $user->last_login_ip ?? 'N/A' }}</span>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase; margin-bottom: 0.25rem;">Biography</span>
                    <p style="font-size: 0.9rem; color: #475569;">{{ $user->bio ?? 'No biography written.' }}</p>
                </div>
            </div>

            {{-- Roles and Privileges Summary --}}
            <div class="admin-card">
                <h3 style="font-size: 1.125rem; font-weight: 700; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">Roles & Privileges</h3>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 0.5rem;">Assigned Roles</h4>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        @forelse($user->roles as $role)
                            <span class="admin-badge admin-badge--neutral">
                                {{ $role->name }}
                            </span>
                        @empty
                            <span style="font-size: 0.875rem; color: #94a3b8;">No roles assigned.</span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 0.9rem; font-weight: 700; color: #475569; margin-bottom: 0.5rem;">Granted Privileges</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem;">
                        @php
                            // Fetch user de-duplicated privileges
                            $allPrivileges = method_exists($user, 'getAllPrivileges') ? $user->getAllPrivileges() : $user->privileges;
                        @endphp
                        @forelse($allPrivileges as $priv)
                            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background-color: #f8fafc; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm);">
                                <span style="font-size: 0.825rem; font-weight: 700; color: #0f172a;">{{ $priv->name }}</span>
                            </div>
                        @empty
                            <span style="font-size: 0.875rem; color: #94a3b8; grid-column: 1 / -1;">No privileges assigned.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Activity Log Timeline --}}
            <div class="admin-card">
                <h3 style="font-size: 1.125rem; font-weight: 700; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">Activity Timeline</h3>

                <div class="admin-timeline">
                    @forelse($activityLogs as $log)
                        <div class="admin-timeline__item">
                            <div class="admin-timeline__icon"></div>
                            <div class="admin-timeline__title">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                            <div class="admin-timeline__desc">{{ $log->description }}</div>
                            <div class="admin-timeline__date">{{ $log->performed_at->format('M d, Y H:i:s') }}</div>
                        </div>
                    @empty
                        <div style="text-align: center; color: #94a3b8; padding: 2rem 0;">
                            No recent activity found for this user.
                        </div>
                    @endforelse
                </div>

                {{-- Activity Pagination --}}
                @if($activityLogs->hasPages())
                    <div style="margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1rem;">
                        {!! $activityLogs->links() !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Actions Sidebar Panel --}}
        <div>
            <div class="admin-card">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">Account Status Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    
                    {{-- User Impersonation --}}
                    @if(config('admin-dashboard.features.user_impersonation', false) && auth()->id() !== $user->id && (!method_exists($user, 'isSuspended') || !$user->isSuspended()))
                        <button type="button" onclick="triggerImpersonation()" class="admin-btn admin-btn--primary" style="width: 100%; justify-content: center; background-color: var(--admin-info); color: #fff;">
                            Impersonate User
                        </button>
                    @endif

                    {{-- Suspension Toggles --}}
                    @if(auth()->id() !== $user->id)
                        @if(method_exists($user, 'isSuspended') && $user->isSuspended())
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" style="width: 100%;">
                                @csrf
                                <button type="submit" class="admin-btn admin-btn--success" style="width: 100%; justify-content: center;">
                                    Lift Suspension
                                </button>
                            </form>
                        @else
                            <button type="button" onclick="triggerSuspension()" class="admin-btn admin-btn--warning" style="width: 100%; justify-content: center;">
                                Suspend Account
                            </button>
                        @endif
                    @endif

                    {{-- Soft Delete & Restore --}}
                    @if(auth()->id() !== $user->id)
                        @if(method_exists($user, 'trashed') && $user->trashed())
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" style="width: 100%;">
                                @csrf
                                <button type="submit" class="admin-btn admin-btn--success" style="width: 100%; justify-content: center;">
                                    Restore Deleted Account
                                </button>
                            </form>
                        @else
                            <button type="button" onclick="triggerDelete()" class="admin-btn admin-btn--danger" style="width: 100%; justify-content: center;">
                                Delete Account
                            </button>
                        @endif
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Impersonate Confirmation Modal --}}
    <div id="impersonate-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('impersonate-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Confirm Impersonation</h3>
                <button type="button" onclick="closeModal('impersonate-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to log in as user <strong>{{ $user->name }}</strong>? Your active admin session will be saved, allowing you to return later.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('impersonate-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form method="POST" action="{{ route('admin.users.impersonate', $user->id) }}">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn--primary">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Suspension Details Modal --}}
    <div id="suspension-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('suspension-modal')"></div>
        <div class="admin-modal__content">
            <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}">
                @csrf
                <div class="admin-modal__header">
                    <h3>Suspend Account</h3>
                    <button type="button" onclick="closeModal('suspension-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
                </div>
                <div class="admin-modal__body">
                    <p style="margin-bottom: 1rem;">Provide a reason for suspending this user's access to the application.</p>
                    <div class="admin-form-group">
                        <label for="reason" class="admin-label">Reason</label>
                        <input type="text" name="reason" id="reason" placeholder="Violation of terms, pending review..." class="admin-input" required>
                    </div>
                </div>
                <div class="admin-modal__footer">
                    <button type="button" onclick="closeModal('suspension-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn--warning">Suspend</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Account Modal --}}
    <div id="delete-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('delete-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Delete Account</h3>
                <button type="button" onclick="closeModal('delete-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to delete the user account for <strong>{{ $user->name }}</strong>? This will restrict their access. If soft-delete is enabled, it can be restored later.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('delete-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn--danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Modal helpers
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        // Action Triggers
        function triggerImpersonation() {
            openModal('impersonate-modal');
        }
        function triggerSuspension() {
            openModal('suspension-modal');
        }
        function triggerDelete() {
            openModal('delete-modal');
        }
    </script>
    @endpush
@endsection
