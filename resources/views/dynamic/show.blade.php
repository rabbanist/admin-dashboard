@extends('admin-dashboard::layouts.app')

@section('title', ($config['title'] ?? 'Record') . ' #' . $record->id . ' - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
        <h1>{{ \Illuminate\Support\Str::singular($config['title'] ?? 'Record') }} #{{ $record->id }} Details</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.resources.edit', [$resourceName, $record->id]) }}" class="admin-btn admin-btn--primary">
                Edit Record
            </a>
            <a href="{{ route('admin.resources.index', $resourceName) }}" class="admin-btn admin-btn--secondary">
                Back to List
            </a>
        </div>
    </div>

    <div class="admin-card">
        <h3 style="font-size: 1.125rem; font-weight: 700; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.75rem; margin-bottom: 1.5rem;">Attributes Overview</h3>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div>
                <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Record ID</span>
                <span style="font-size: 1rem; font-weight: 700; color: #0f172a;">#{{ $record->id }}</span>
            </div>

            @foreach($columns as $colName => $options)
                <div>
                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">
                        {{ $options['label'] ?? ucwords(str_replace('_', ' ', $colName)) }}
                    </span>

                    <div style="font-size: 0.95rem; font-weight: 500; margin-top: 0.25rem;">
                        @if($options['type'] === 'boolean')
                            @if($record->{$colName})
                                <span class="admin-badge admin-badge--success">Yes</span>
                            @else
                                <span class="admin-badge admin-badge--danger">No</span>
                            @endif
                        @elseif($options['type'] === 'file')
                            @if($record->{$colName})
                                @php
                                    $url = Storage::disk($options['disk'] ?? 'public')->url($record->{$colName});
                                    $isImage = in_array(pathinfo($record->{$colName}, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                @endphp
                                @if($isImage)
                                    <img src="{{ $url }}" alt="Preview" style="max-height: 12rem; border-radius: var(--admin-radius); box-shadow: var(--admin-shadow); margin-top: 0.25rem; display: block;">
                                @else
                                    <a href="{{ $url }}" target="_blank" class="admin-btn admin-btn--secondary admin-btn--sm" style="margin-top: 0.25rem;">
                                        Download File
                                    </a>
                                @endif
                            @else
                                <span style="color: #cbd5e1;">N/A</span>
                            @endif
                        @elseif($options['type'] === 'relationship' || isset($options['model']))
                            @php
                                $relationName = str_replace('_id', '', $colName);
                                $relationValue = null;
                                if (method_exists($record, $relationName) && $record->{$relationName}) {
                                    $relationValue = $record->{$relationName}->name ?? $record->{$relationName}->title ?? $record->{$relationName}->id;
                                }
                            @endphp
                            <span style="font-weight: 700; color: var(--admin-primary);">{{ $relationValue ?? $record->{$colName} ?? 'None' }}</span>
                        @elseif($options['type'] === 'multiselect')
                            @php
                                $val = $record->{$colName};
                                $arr = is_string($val) ? json_decode($val, true) : $val;
                            @endphp
                            @if(is_array($arr))
                                @foreach($arr as $item)
                                    <span class="admin-badge admin-badge--neutral" style="margin-right: 0.25rem;">{{ $item }}</span>
                                @endforeach
                            @else
                                <span style="color: #cbd5e1;">None</span>
                            @endif
                        @elseif($options['type'] === 'decimal')
                            ${{ number_format((float) $record->{$colName}, $options['decimals'] ?? 2) }}
                        @else
                            {!! nl2br(e((string) $record->{$colName})) !!}
                        @endif
                    </div>
                </div>
            @endforeach

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.25rem; margin-top: 0.5rem;">
                <div>
                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Created At</span>
                    <span style="font-size: 0.9rem;">{{ $record->created_at ? $record->created_at->format('M d, Y H:i:s') : 'N/A' }}</span>
                </div>
                <div>
                    <span style="font-weight: 700; font-size: 0.85rem; color: var(--admin-text-muted); display: block; text-transform: uppercase;">Last Updated At</span>
                    <span style="font-size: 0.9rem;">{{ $record->updated_at ? $record->updated_at->format('M d, Y H:i:s') : 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
