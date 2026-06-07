@php
    $selectedIds = isset($selected) ? collect($selected)->pluck('id')->toArray() : [];
    $groupedPrivileges = $privileges->groupBy('resource_type');
@endphp

<div class="privilege-picker-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
    @foreach($groupedPrivileges as $resource => $resourcePrivileges)
        <div class="admin-card" style="padding: 1.25rem; margin-bottom: 0; border-top: 3px solid var(--admin-primary);">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem; margin-bottom: 0.75rem;">
                <h4 style="text-transform: uppercase; font-size: 0.8rem; font-weight: 700; color: #475569;">
                    {{ str_replace('_', ' ', $resource) }}
                </h4>
                <label class="admin-checkbox-label" style="font-size: 0.75rem; color: var(--admin-primary); font-weight: 700;">
                    <input type="checkbox" class="admin-checkbox select-all-resource" data-resource="{{ $resource }}" style="width: 0.85rem; height: 0.85rem;">
                    Select All
                </label>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                @foreach($resourcePrivileges as $privilege)
                    <label class="admin-checkbox-label" style="font-size: 0.85rem; font-weight: 500;">
                        <input type="checkbox" name="privileges[]" value="{{ $privilege->id }}" 
                            class="admin-checkbox privilege-checkbox-{{ $resource }}" 
                            {{ in_array($privilege->id, $selectedIds) ? 'checked' : '' }}>
                        <div>
                            <span>{{ $privilege->name }}</span>
                            @if($privilege->description)
                                <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 400;">{{ $privilege->description }}</div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all for each resource group
        document.querySelectorAll('.select-all-resource').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const resource = this.dataset.resource;
                const checked = this.checked;
                document.querySelectorAll('.privilege-checkbox-' + resource).forEach(function(privCb) {
                    privCb.checked = checked;
                });
            });

            // Set initial state of "Select All" checkbox
            const resource = checkbox.dataset.resource;
            const total = document.querySelectorAll('.privilege-checkbox-' + resource).length;
            const checkedCount = document.querySelectorAll('.privilege-checkbox-' + resource + ':checked').length;
            if (total === checkedCount && total > 0) {
                checkbox.checked = true;
            }
        });

        // Listen for individual privilege checkbox changes to update "Select All" checkboxes
        document.querySelectorAll('[class^="admin-checkbox privilege-checkbox-"]').forEach(function(privCb) {
            privCb.addEventListener('change', function() {
                const classList = this.className.split(' ');
                const resourceClass = classList.find(c => c.startsWith('privilege-checkbox-'));
                if (resourceClass) {
                    const resource = resourceClass.replace('privilege-checkbox-', '');
                    const selectAllCb = document.querySelector('.select-all-resource[data-resource="' + resource + '"]');
                    if (selectAllCb) {
                        const total = document.querySelectorAll('.privilege-checkbox-' + resource).length;
                        const checkedCount = document.querySelectorAll('.privilege-checkbox-' + resource + ':checked').length;
                        selectAllCb.checked = (total === checkedCount);
                    }
                }
            });
        });
    });
</script>
