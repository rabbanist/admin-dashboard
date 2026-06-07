@extends('admin-dashboard::layouts.app')

@section('title', ($config['title'] ?? 'Resource') . ' - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
        {{-- List current resource as active --}}
        <li class="active">
            <a href="{{ route('admin.resources.index', $resourceName) }}">{{ $config['title'] ?? 'Records' }}</a>
        </li>
    </ul>
@endsection

@section('content')
    <div class="admin-header">
        <h1>{{ $config['title'] ?? 'Records' }}</h1>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.resources.export', [$resourceName, 'search' => request('search')]) }}" class="admin-btn admin-btn--secondary">
                Export to CSV
            </a>
            <a href="{{ route('admin.resources.create', $resourceName) }}" class="admin-btn admin-btn--primary">
                Add Record
            </a>
        </div>
    </div>

    {{-- Filters Panel --}}
    <div class="admin-controls-panel">
        <form method="GET" action="{{ route('admin.resources.index', $resourceName) }}" class="admin-search-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search across columns..." class="admin-input">
            <button type="submit" class="admin-btn admin-btn--secondary">Search</button>
            @if(request('search') || collect(request()->all())->keys()->contains(fn($k) => str_starts_with($k, 'filter_')))
                <a href="{{ route('admin.resources.index', $resourceName) }}" class="admin-btn admin-btn--secondary">Clear</a>
            @endif
        </form>

        {{-- Dynamic Column-Specific Filters --}}
        <form method="GET" action="{{ route('admin.resources.index', $resourceName) }}" class="admin-filter-group">
            @foreach($columns as $colName => $options)
                @if(isset($options['filterable']) && $options['filterable'])
                    @if($options['type'] === 'boolean')
                        <select name="filter_{{ $colName }}" onchange="this.form.submit()" class="admin-select">
                            <option value="">Filter {{ ucwords(str_replace('_', ' ', $colName)) }}</option>
                            <option value="1" {{ request("filter_{$colName}") === '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ request("filter_{$colName}") === '0' ? 'selected' : '' }}>No</option>
                        </select>
                    @endif
                @endif
            @endforeach
        </form>
    </div>

    {{-- Table Form for bulk actions --}}
    <form id="bulk-action-form" method="POST" action="{{ route('admin.resources.bulk', $resourceName) }}">
        @csrf
        <div class="admin-controls-panel" style="margin-bottom: 1rem; padding: 0.75rem 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <select name="action" id="bulk-action-select" class="admin-select" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    @if(method_exists($records->first(), 'restore'))
                        <option value="restore">Restore Selected</option>
                    @endif
                </select>
                <button type="button" onclick="confirmBulkAction()" class="admin-btn admin-btn--secondary admin-btn--sm">Apply</button>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="select-all-checkbox" class="admin-checkbox">
                        </th>
                        <th>ID</th>
                        @foreach($columns as $colName => $options)
                            @if(isset($options['sortable']) && $options['sortable'])
                                <th class="sortable" onclick="window.location.href='{{ request()->fullUrlWithQuery(['sort' => $colName, 'direction' => request('sort') === $colName && request('direction') === 'asc' ? 'desc' : 'asc']) }}'">
                                    {{ $options['label'] ?? ucwords(str_replace('_', ' ', $colName)) }}
                                    @if(request('sort') === $colName)
                                        {!! request('direction') === 'asc' ? '&#8593;' : '&#8595;' !!}
                                    @endif
                                </th>
                            @else
                                <th>{{ $options['label'] ?? ucwords(str_replace('_', ' ', $colName)) }}</th>
                            @endif
                        @endforeach
                        <th width="180" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>
                                <input type="checkbox" name="record_ids[]" value="{{ $record->id }}" class="admin-checkbox record-checkbox">
                            </td>
                            <td style="font-weight: 700; color: #64748b;">#{{ $record->id }}</td>
                            @foreach($columns as $colName => $options)
                                <td>
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
                                                <img src="{{ $url }}" alt="Upload Preview" style="width: 2.5rem; height: 2.5rem; object-fit: cover; border-radius: 4px;">
                                            @else
                                                <a href="{{ $url }}" target="_blank" style="font-size: 0.75rem; font-weight: 700;">Download</a>
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
                                        <span style="font-weight: 600;">{{ $relationValue ?? $record->{$colName} ?? 'None' }}</span>
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
                                        {{ \Illuminate\Support\Str::limit((string) $record->{$colName}, 50) }}
                                    @endif
                                </td>
                            @endforeach
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="{{ route('admin.resources.show', [$resourceName, $record->id]) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="View details">
                                        View
                                    </a>
                                    <a href="{{ route('admin.resources.edit', [$resourceName, $record->id]) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="Edit record">
                                        Edit
                                    </a>
                                    <button type="button" onclick="triggerCloneRecord({{ $record->id }})" class="admin-btn admin-btn--secondary admin-btn--sm" style="background-color: var(--admin-info-light); color: #155e75; border-color: transparent;">
                                        Clone
                                    </button>
                                    <button type="button" onclick="triggerDeleteRecord({{ $record->id }})" class="admin-btn admin-btn--danger admin-btn--sm" title="Delete record">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 3 }}" style="text-align: center; color: #cbd5e1; padding: 4rem 0;">
                                No records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($records->hasPages())
                <div class="admin-pagination-wrapper">
                    <div style="font-size: 0.875rem; color: #64748b;">
                        Showing {{ $records->firstItem() }} to {{ $records->lastItem() }} of {{ $records->total() }} records
                    </div>
                    <div>
                        {!! $records->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </form>

    {{-- Modals --}}
    
    {{-- Delete Modal --}}
    <div id="delete-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('delete-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Delete Record</h3>
                <button type="button" onclick="closeModal('delete-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to delete this record? This action will remove the record. Deleted files will be retained or removed according to configuration.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('delete-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form id="delete-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn--danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Clone Modal --}}
    <div id="clone-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('clone-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Clone Record</h3>
                <button type="button" onclick="closeModal('clone-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to duplicate this record? A copy will be created with duplicate values immediately.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('clone-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form id="clone-form" method="POST" action="">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn--primary">Clone</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Action Confirmation Modal --}}
    <div id="bulk-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('bulk-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Confirm Bulk Action</h3>
                <button type="button" onclick="closeModal('bulk-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to perform bulk <strong id="bulk-action-text"></strong> on all selected records?</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('bulk-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <button type="button" onclick="submitBulkForm()" class="admin-btn admin-btn--danger">Execute</button>
            </div>
        </div>
    </div>

    {{-- Toast Notification container --}}
    <div class="toast-container" id="toast-container"></div>

    @push('scripts')
    <script>
        // Select All checkboxes
        document.getElementById('select-all-checkbox').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.record-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Trigger Delete
        function triggerDeleteRecord(id) {
            const form = document.getElementById('delete-form');
            form.action = `{{ url(config('admin-dashboard.route_prefix', 'admin') . '/resources/' . $resourceName) }}/${id}`;
            openModal('delete-modal');
        }

        // Trigger Clone
        function triggerCloneRecord(id) {
            const form = document.getElementById('clone-form');
            form.action = `{{ url(config('admin-dashboard.route_prefix', 'admin') . '/resources/' . $resourceName) }}/${id}/clone`;
            openModal('clone-modal');
        }

        // Trigger Bulk
        function confirmBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            const selected = document.querySelectorAll('.record-checkbox:checked').length;
            
            if (!action) {
                showToast('Please select a bulk action.', 'error');
                return;
            }
            if (selected === 0) {
                showToast('Please select at least one record.', 'error');
                return;
            }

            document.getElementById('bulk-action-text').innerText = `${action} (${selected} record${selected > 1 ? 's' : ''})`;
            openModal('bulk-modal');
        }

        function submitBulkForm() {
            document.getElementById('bulk-action-form').submit();
        }

        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `admin-toast admin-toast--${type}`;
            toast.innerHTML = `<span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif
    </script>
    @endpush
@endsection
