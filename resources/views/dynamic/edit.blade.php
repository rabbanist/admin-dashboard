@extends('admin-dashboard::layouts.app')

@section('title', 'Edit ' . ($config['title'] ?? 'Record') . ' #' . $record->id . ' - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
            <a href="{{ route('admin.resources.index', $resourceName) }}">{{ $config['title'] ?? 'Records' }}</a>
        </li>
    </ul>
@endsection

@section('content')
    <div class="admin-header">
        <h1>Edit {{ \Illuminate\Support\Str::singular($config['title'] ?? 'Record') }} #{{ $record->id }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.resources.show', [$resourceName, $record->id]) }}" class="admin-btn admin-btn--secondary">
                View Record
            </a>
            <a href="{{ route('admin.resources.index', $resourceName) }}" class="admin-btn admin-btn--secondary">
                Back to List
            </a>
        </div>
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

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.resources.update', [$resourceName, $record->id]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                @foreach($fields as $name => $field)
                    <div class="admin-form-group">
                        <label for="{{ $name }}" class="admin-label">
                            {{ $field['label'] }}
                            @if($field['required'])
                                <span style="color: var(--admin-danger);">*</span>
                            @endif
                        </label>

                        @if($field['type'] === 'textarea')
                            <textarea name="{{ $name }}" id="{{ $name }}" rows="5" class="admin-textarea" placeholder="{{ $field['placeholder'] }}" {{ $field['required'] ? 'required' : '' }}>{{ old($name, $field['value']) }}</textarea>
                        
                        @elseif($field['type'] === 'boolean')
                            <div style="margin-top: 0.5rem;">
                                <label class="admin-checkbox-label">
                                    <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" {{ old($name, $field['value']) ? 'checked' : '' }} class="admin-checkbox">
                                    Enabled
                                </label>
                            </div>

                        @elseif($field['type'] === 'select' || $field['type'] === 'relationship')
                            <select name="{{ $name }}" id="{{ $name }}" class="admin-select" {{ $field['required'] ? 'required' : '' }}>
                                <option value="">Select {{ $field['label'] }}...</option>
                                @foreach($field['options'] as $optionId => $optionLabel)
                                    <option value="{{ $optionId }}" {{ old($name, $field['value']) == $optionId ? 'selected' : '' }}>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>

                        @elseif($field['type'] === 'multiselect')
                            <select name="{{ $name }}[]" id="{{ $name }}" class="admin-select" multiple style="height: 120px;" {{ $field['required'] ? 'required' : '' }}>
                                @php
                                    $oldVals = old($name, $field['value'] ?? []);
                                    $oldVals = is_array($oldVals) ? $oldVals : [];
                                @endphp
                                @foreach($field['options'] as $optionId => $optionLabel)
                                    <option value="{{ $optionId }}" {{ in_array($optionId, $oldVals) ? 'selected' : '' }}>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <span style="font-size: 0.75rem; color: #94a3b8;">Hold Ctrl (Windows) or Cmd (Mac) to select multiple values.</span>

                        @elseif($field['type'] === 'file')
                            @if($field['value'])
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    @php
                                        $url = Storage::disk($field['disk'])->url($field['value']);
                                        $isImage = in_array(pathinfo($field['value'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ $url }}" alt="Preview" style="max-height: 5rem; border-radius: 4px;">
                                    @else
                                        <a href="{{ $url }}" target="_blank" style="font-size: 0.85rem; font-weight: 700;">Download current file</a>
                                    @endif
                                </div>
                            @endif
                            <input type="file" name="{{ $name }}" id="{{ $name }}" class="admin-input" style="padding: 0.35rem 0.5rem;">
                            <span style="font-size: 0.75rem; color: #94a3b8;">Choose a new file to replace the existing one.</span>

                        @elseif($field['type'] === 'number' || $field['type'] === 'decimal')
                            <input type="number" step="any" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $field['value']) }}" placeholder="{{ $field['placeholder'] }}" class="admin-input" {{ $field['required'] ? 'required' : '' }}>

                        @elseif($field['type'] === 'date')
                            <input type="date" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $field['value'] ? substr($field['value'], 0, 10) : '') }}" class="admin-input" {{ $field['required'] ? 'required' : '' }}>

                        @elseif($field['type'] === 'datetime')
                            <input type="datetime-local" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $field['value'] ? str_replace(' ', 'T', substr($field['value'], 0, 16)) : '') }}" class="admin-input" {{ $field['required'] ? 'required' : '' }}>

                        @else
                            {{-- Text, Email, password etc. --}}
                            <input type="{{ $field['type'] === 'email' ? 'email' : 'text' }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $field['value']) }}" placeholder="{{ $field['placeholder'] }}" class="admin-input" {{ $field['required'] ? 'required' : '' }}>
                        @endif
                    </div>
                @endforeach
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <a href="{{ route('admin.resources.index', $resourceName) }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Save Changes</button>
            </div>
        </form>
    </div>
@endsection
