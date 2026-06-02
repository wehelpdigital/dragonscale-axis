<div class="critical-rule-row" data-id="{{ $rule->id }}" data-sort-order="{{ (int) $rule->sortOrder }}" draggable="true">
    <div class="critical-rule-handle" title="Drag to reorder"><i class="bx bx-grid-vertical"></i></div>
    <div class="critical-rule-text">{{ $rule->ruleText }}</div>
    <div class="critical-rule-actions">
        <button type="button" class="btn btn-sm btn-outline-primary edit-critical-rule-btn"
                data-id="{{ $rule->id }}"
                data-text="{{ $rule->ruleText }}"
                title="Edit"><i class="bx bx-edit-alt"></i></button>
        <button type="button" class="btn btn-sm btn-outline-danger delete-critical-rule-btn"
                data-id="{{ $rule->id }}"
                title="Delete"><i class="bx bx-trash"></i></button>
    </div>
</div>
