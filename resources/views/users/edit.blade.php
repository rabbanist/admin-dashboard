@extends('admin-dashboard::layouts.app')

@section('title', 'Edit User - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
    <div class="admin-header">
        <h1>Edit User: {{ $user->name }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.users.show', $user->id) }}" class="admin-btn admin-btn--secondary">
                View Profile
            </a>
            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn--secondary">
                Back to List
            </a>
        </div>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Profile photo preview block --}}
            <div class="user-profile-header">
                @php
                    $photoUrl = $user->profile_photo_path 
                        ? Storage::disk(config('admin-dashboard.uploads.disk', 'public'))->url($user->profile_photo_path)
                        : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp';
                @endphp
                <div style="position: relative;">
                    <img id="photo-preview-element" src="{{ $photoUrl }}" alt="{{ $user->name }}" class="admin-avatar admin-avatar--lg">
                </div>
                <div>
                    <label class="admin-label">Profile Image</label>
                    <input type="file" name="profile_photo" id="profile_photo_input" class="admin-input" accept="image/*" style="padding: 0.35rem 0.5rem;">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Max size: 2MB. Format: JPG, PNG, GIF, WebP.</span>
                </div>
            </div>

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="name" class="admin-label">Full Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="admin-input" required autocomplete="name">
                </div>

                <div class="admin-form-group">
                    <label for="email" class="admin-label">Email Address</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="admin-input" required autocomplete="email">
                </div>
            </div>

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="password" class="admin-label">Password <span style="font-size: 0.75rem; font-weight: 500; color: #94a3b8;">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" id="password" placeholder="Change password..." class="admin-input" autocomplete="new-password">
                    
                    {{-- Password Strength Indicator --}}
                    <div class="password-strength-container">
                        <span id="password-strength-label" style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Strength: Empty</span>
                        <div class="password-strength-bar">
                            <div id="password-strength-fill" class="password-strength-fill"></div>
                        </div>
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="password_confirmation" class="admin-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Verify new password..." class="admin-input" autocomplete="new-password">
                </div>
            </div>

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="phone" class="admin-label">Phone Number</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 000-0000" class="admin-input">
                </div>

                <div class="admin-form-group">
                    <label class="admin-label">Security Settings</label>
                    <div style="margin-top: 0.75rem;">
                        <label class="admin-checkbox-label">
                            <input type="checkbox" name="two_factor_enabled" value="1" {{ old('two_factor_enabled', $user->two_factor_enabled ?? false) ? 'checked' : '' }} class="admin-checkbox">
                            Enable Two-Factor Authentication (2FA)
                        </label>
                    </div>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="bio" class="admin-label">Short Biography</label>
                <textarea name="bio" id="bio" rows="4" placeholder="Tell us about this user..." class="admin-textarea">{{ old('bio', $user->bio) }}</textarea>
            </div>

            {{-- Roles Multi-checkbox --}}
            <div class="admin-form-group">
                <label class="admin-label">Assign Roles</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
                    @php
                        $userRoles = $user->roles->pluck('id')->toArray();
                    @endphp
                    @foreach($roles as $role)
                        <label class="admin-checkbox-label">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }} class="admin-checkbox">
                            <div>
                                <span style="font-weight: 700;">{{ $role->name }}</span>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $role->description }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
                <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Save Changes</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Photo preview handling
        document.getElementById('profile_photo_input').addEventListener('change', function(event) {
            const preview = document.getElementById('photo-preview-element');
            const file = event.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        });

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('password-strength-fill');
        const strengthLabel = document.getElementById('password-strength-label');

        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let score = 0;

            if (password.length >= 8) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            // Reset classes
            strengthBar.className = 'password-strength-fill';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthLabel.innerText = 'Strength: Empty';
                strengthLabel.style.color = '#64748b';
            } else if (password.length < 8) {
                strengthBar.style.width = '15%';
                strengthBar.classList.add('strength-weak');
                strengthLabel.innerText = 'Strength: Too Short';
                strengthLabel.style.color = 'var(--admin-danger)';
            } else if (score <= 2) {
                strengthBar.style.width = '50%';
                strengthBar.classList.add('strength-weak');
                strengthLabel.innerText = 'Strength: Weak';
                strengthLabel.style.color = 'var(--admin-danger)';
            } else if (score === 3) {
                strengthBar.style.width = '75%';
                strengthBar.classList.add('strength-medium');
                strengthLabel.innerText = 'Strength: Medium';
                strengthLabel.style.color = 'var(--admin-warning)';
            } else if (score === 4) {
                strengthBar.style.width = '100%';
                strengthBar.classList.add('strength-strong');
                strengthLabel.innerText = 'Strength: Strong';
                strengthLabel.style.color = 'var(--admin-success)';
            }
        });
    </script>
    @endpush
@endsection
