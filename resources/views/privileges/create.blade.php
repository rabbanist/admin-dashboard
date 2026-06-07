@extends('admin-dashboard::layouts.app')

@section('title', 'Add Privilege - ' . config('admin-dashboard.title', 'Admin Dashboard'))

@section('sidebar')
    <ul class="admin-sidebar-nav">
        <li>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        </li>
        <li>
            <a href="{{ route('admin.users.index') }}">Users</a>
        </li>
        <li>
            <a href="{{ route('admin.roles.index') }}">Roles</a>
        </li>
        <li class="active">
            <a href="{{ route('admin.privileges.index') }}">Privileges</a>
        </li>
    </ul>
@endsection

@section('content')
    <div class="admin-header">
        <h1>Add Privilege</h1>
        <a href="{{ route('admin.privileges.index') }}" class="admin-btn admin-btn--secondary">
            Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert--error" style="margin-bottom: 1.5rem;">
            <ul style="list-style: none;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="admin-controls-panel" style="padding: 0.5rem; justify-content: flex-start; gap: 0.5rem;">
        <button type="button" id="tab-single-btn" onclick="switchTab('single')" class="admin-btn admin-btn--primary admin-btn--sm">Single Privilege</button>
        <button type="button" id="tab-crud-btn" onclick="switchTab('crud')" class="admin-btn admin-btn--secondary admin-btn--sm">Generate CRUD Privileges</button>
    </div>

    {{-- Form Single Privilege --}}
    <div id="form-single" class="admin-card">
        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem;">Create Single Privilege</h3>
        
        <form method="POST" action="{{ route('admin.privileges.store') }}">
            @csrf
            <input type="hidden" name="generate_crud" value="0">

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="name" class="admin-label">Privilege Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Export Users" class="admin-input" required autocomplete="off">
                </div>

                <div class="admin-form-group">
                    <label for="slug" class="admin-label">Privilege Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="export.users" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Format: action.resource (e.g., export.users).</span>
                </div>
            </div>

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="resource_type" class="admin-label">Resource Type</label>
                    <input type="text" name="resource_type" id="resource_type" value="{{ old('resource_type') }}" placeholder="users" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Lowercased plural resource identifier (e.g. users, roles).</span>
                </div>

                <div class="admin-form-group">
                    <label for="module" class="admin-label">Module / Area</label>
                    <input type="text" name="module" id="module" value="{{ old('module', 'core') }}" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Categorization group (e.g., core, system, content).</span>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="description" class="admin-label">Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Explain the permission granted by this privilege..." class="admin-textarea">{{ old('description') }}</textarea>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <a href="{{ route('admin.privileges.index') }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Create Privilege</button>
            </div>
        </form>
    </div>

    {{-- Form Generate CRUD Privileges --}}
    <div id="form-crud" class="admin-card" style="display: none;">
        <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1.25rem;">Generate CRUD Privileges</h3>
        <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
            This will automatically generate a standard set of 4 privileges for the specified resource type: <strong>view</strong>, <strong>create</strong>, <strong>update</strong>, and <strong>delete</strong>.
        </p>

        <form method="POST" action="{{ route('admin.privileges.store') }}">
            @csrf
            <input type="hidden" name="generate_crud" value="1">

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="crud_resource_type" class="admin-label">Resource Type</label>
                    <input type="text" name="resource_type" id="crud_resource_type" placeholder="posts" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Lowercased plural resource identifier (e.g., posts, products, pages).</span>
                </div>

                <div class="admin-form-group">
                    <label for="crud_module" class="admin-label">Module / Area</label>
                    <input type="text" name="module" id="crud_module" value="core" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Categorization group (e.g., core, system, content).</span>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <a href="{{ route('admin.privileges.index') }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Generate Privileges</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Tab switcher
        function switchTab(type) {
            const singleBtn = document.getElementById('tab-single-btn');
            const crudBtn = document.getElementById('tab-crud-btn');
            const singleForm = document.getElementById('form-single');
            const crudForm = document.getElementById('form-crud');

            if (type === 'single') {
                singleBtn.className = 'admin-btn admin-btn--primary admin-btn--sm';
                crudBtn.className = 'admin-btn admin-btn--secondary admin-btn--sm';
                singleForm.style.display = 'block';
                crudForm.style.display = 'none';
            } else {
                singleBtn.className = 'admin-btn admin-btn--secondary admin-btn--sm';
                crudBtn.className = 'admin-btn admin-btn--primary admin-btn--sm';
                singleForm.style.display = 'none';
                crudForm.style.display = 'block';
            }
        }

        // Automatic slug generation from privilege name
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        const resourceInput = document.getElementById('resource_type');
        let userEditedSlug = false;

        slugInput.addEventListener('input', function() {
            userEditedSlug = true;
        });

        nameInput.addEventListener('input', function() {
            if (!userEditedSlug) {
                // Generate slug like action.resource
                const words = nameInput.value.toLowerCase().split(' ');
                if (words.length >= 2) {
                    const action = words[0];
                    const resource = words.slice(1).join('-').replace(/[^a-z0-9-]/g, '');
                    slugInput.value = `${action}.${resource}`;
                    resourceInput.value = resource;
                } else {
                    slugInput.value = nameInput.value.toLowerCase().replace(/[^a-z0-9]/g, '');
                }
            }
        });
    </script>
    @endpush
@endsection
