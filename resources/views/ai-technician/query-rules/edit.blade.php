@extends('layouts.master')

@section('title') Edit Query Rule @endsection

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<style>
    .form-label-description {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 4px;
    }
    .char-counter {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .char-counter.warning {
        color: #f1b44c;
    }
    .char-counter.danger {
        color: #f46a6a;
    }
    .preview-card {
        background-color: #f8f9fa;
        border: 1px dashed #dee2e6;
    }
    .preview-content {
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        white-space: pre-wrap;
        color: #495057;
    }
    .system-rule-notice {
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('li_2') <a href="{{ route('ai-technician.query-rules') }}">Query Rules</a> @endslot
        @slot('title') Edit Rule @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($rule->isSystemRule)
        <div class="alert alert-success system-rule-notice" role="alert">
            <i class="bx bx-info-circle me-2"></i>
            <strong>System Rule:</strong> This is a default system rule. You can modify it, but it cannot be deleted.
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0 text-dark">
                        <i class="bx bx-edit text-primary me-2"></i>Edit Query Rule
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('ai-technician.query-rules.update', ['id' => $rule->id]) }}" method="POST" id="ruleForm">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="ruleName" class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('ruleName') is-invalid @enderror"
                                   id="ruleName" name="ruleName" value="{{ old('ruleName', $rule->ruleName) }}"
                                   placeholder="e.g., Use Local Currency Format" maxlength="255">
                            <div class="form-label-description">A short, descriptive name for this rule.</div>
                            @error('ruleName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="ruleCategory" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select @error('ruleCategory') is-invalid @enderror"
                                            id="ruleCategory" name="ruleCategory">
                                        @foreach($categories as $key => $label)
                                            <option value="{{ $key }}" {{ old('ruleCategory', $rule->ruleCategory) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('ruleCategory')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control @error('priority') is-invalid @enderror"
                                           id="priority" name="priority" value="{{ old('priority', $rule->priority) }}"
                                           min="0" max="1000">
                                    <div class="form-label-description">Higher = applied first (0-1000)</div>
                                    @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ruleDescription" class="form-label">Description</label>
                            <textarea class="form-control @error('ruleDescription') is-invalid @enderror"
                                      id="ruleDescription" name="ruleDescription" rows="2"
                                      placeholder="Optional: Explain what this rule does in plain language..."
                                      maxlength="1000">{{ old('ruleDescription', $rule->ruleDescription) }}</textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-label-description">A human-readable explanation of what this rule does.</div>
                                <span class="char-counter" id="descCounter">0 / 1000</span>
                            </div>
                            @error('ruleDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="rulePrompt" class="form-label">Rule Prompt <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('rulePrompt') is-invalid @enderror"
                                      id="rulePrompt" name="rulePrompt" rows="10"
                                      placeholder="Enter the instruction that will be injected into AI queries..."
                                      maxlength="5000">{{ old('rulePrompt', $rule->rulePrompt) }}</textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-label-description">The actual instruction that will be sent to the AI with each query.</div>
                                <span class="char-counter" id="promptCounter">0 / 5000</span>
                            </div>
                            @error('rulePrompt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Rule
                            </button>
                            <a href="{{ route('ai-technician.query-rules') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Preview Card -->
            <div class="card preview-card">
                <div class="card-header">
                    <h6 class="card-title mb-0 text-dark">
                        <i class="bx bx-show me-2"></i>Preview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="preview-content" id="rulePreview">
                        <span class="text-secondary">Enter a rule prompt to see how it will appear...</span>
                    </div>
                </div>
            </div>

            <!-- Rule Info Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 text-dark">
                        <i class="bx bx-info-circle me-2"></i>Rule Information
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0" style="font-size: 0.85rem;">
                        <tr>
                            <td class="text-secondary">Created</td>
                            <td class="text-dark">{{ $rule->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Updated</td>
                            <td class="text-dark">{{ $rule->updated_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Status</td>
                            <td>
                                @if($rule->isEnabled)
                                    <span class="badge bg-success">Enabled</span>
                                @else
                                    <span class="badge bg-secondary">Disabled</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Type</td>
                            <td>
                                @if($rule->isSystemRule)
                                    <span class="badge bg-info text-white">System Rule</span>
                                @else
                                    <span class="badge bg-light text-dark">Custom Rule</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 text-dark">
                        <i class="bx bx-bulb text-warning me-2"></i>Tips for Writing Rules
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.85rem;">
                        <li class="mb-2">Be specific and clear about what the AI should do</li>
                        <li class="mb-2">Use examples to illustrate the expected behavior</li>
                        <li class="mb-2">Start with action words like "Always", "Never", "When"</li>
                        <li class="mb-2">Higher priority rules take precedence over lower ones</li>
                        <li>Keep rules focused on a single behavior or preference</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Character counters
    function updateCounter(input, counter, max) {
        const len = $(input).val().length;
        $(counter).text(len + ' / ' + max);

        $(counter).removeClass('warning danger');
        if (len > max * 0.9) {
            $(counter).addClass('danger');
        } else if (len > max * 0.7) {
            $(counter).addClass('warning');
        }
    }

    $('#ruleDescription').on('input', function() {
        updateCounter(this, '#descCounter', 1000);
    }).trigger('input');

    $('#rulePrompt').on('input', function() {
        updateCounter(this, '#promptCounter', 5000);
        updatePreview();
    }).trigger('input');

    $('#ruleName').on('input', function() {
        updatePreview();
    });

    // Update preview
    function updatePreview() {
        const name = $('#ruleName').val().trim();
        const prompt = $('#rulePrompt').val().trim();

        if (!prompt) {
            $('#rulePreview').html('<span class="text-secondary">Enter a rule prompt to see how it will appear...</span>');
            return;
        }

        let preview = '';
        if (name) {
            preview += '<strong>' + escapeHtml(name) + ':</strong>\n';
        }
        preview += escapeHtml(prompt);

        $('#rulePreview').html(preview);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updatePreview();
});
</script>
@endsection
