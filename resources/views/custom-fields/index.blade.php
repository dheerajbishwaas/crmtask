@extends('layouts.app')

@section('content')
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
            <div>
                <h2 style="margin:0;font-size:1.25rem;">Custom Field Builder</h2>
                <p style="margin:0.25rem 0 0;color:#64748b;">Define the additional data points your team needs on each contact.</p>
            </div>
            <a class="btn btn-secondary" href="{{ route('contacts.index') }}">Back to Contacts</a>
        </div>

        <form id="custom-field-form" style="margin-top:1.5rem;display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
            <div style="grid-column:1/-1;">
                <label style="font-weight:600;display:block;margin-bottom:0.35rem;" for="field-name">Field Name</label>
                <input class="input" type="text" id="field-name" name="name" placeholder="e.g. Birthday" required>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:0.35rem;" for="field-type">Field Type</label>
                <select id="field-type" name="field_type">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="select">Select</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:0.35rem;">Required?</label>
                <select id="field-required" name="is_required">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;display:block;margin-bottom:0.35rem;">Active?</label>
                <select id="field-active" name="is_active">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div style="grid-column:1/-1;">
                <label style="font-weight:600;display:block;margin-bottom:0.35rem;" for="field-options">Options (for select fields)</label>
                <textarea id="field-options" class="input" rows="3" placeholder="Enter one option per line"></textarea>
            </div>
            <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:0.6rem;">
                <button class="btn" type="reset" style="background:#f1f5f9;color:#0f172a;">Clear</button>
                <button class="btn btn-primary" type="submit">Add Field</button>
            </div>
        </form>

        <div style="margin-top:2rem;">
            <h3 style="margin:0 0 0.75rem;font-size:1.1rem;">Configured Fields</h3>
            <div class="table-wrapper" style="overflow-x:auto;">
                <table class="table" id="custom-fields-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Status</th>
                            <th>Options</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="custom-fields-body">
                        @include('custom-fields.partials.field_rows', ['fields' => $customFields])
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const fieldForm = document.getElementById('custom-field-form');
        const fieldBody = document.getElementById('custom-fields-body');
        const fieldOptions = document.getElementById('field-options');

        fieldForm.addEventListener('submit', (event) => {
            event.preventDefault();
            createCustomField();
        });

        function createCustomField() {
            const formData = new FormData(fieldForm);
            const optionsText = fieldOptions.value.trim();
            if (optionsText) {
                optionsText.split('\n').forEach((line) => {
                    const value = line.trim();
                    if (value) {
                        formData.append('options[]', value);
                    }
                });
            }

            fetch('{{ route('custom-fields.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Unable to create custom field.');
                    }
                    return response.json();
                })
                .then((data) => {
                    const temp = document.createElement('tbody');
                    temp.innerHTML = data.row.trim();
                    const newRow = temp.firstElementChild;
                    if (newRow) {
                        fieldBody.prepend(newRow);
                        attachRowActions(newRow);
                    }
                    fieldForm.reset();
                    fieldOptions.value = '';
                })
                .catch((error) => alert(error.message));
        }

        function attachRowActions(row) {
            const toggleRequired = row.querySelector('[data-action="toggle-required"]');
            const toggleActive = row.querySelector('[data-action="toggle-active"]');
            const deleteBtn = row.querySelector('[data-action="delete"]');

            if (toggleRequired) {
                toggleRequired.addEventListener('change', () => {
                    updateField(row.dataset.fieldId, { is_required: toggleRequired.checked ? 1 : 0 });
                });
            }

            if (toggleActive) {
                toggleActive.addEventListener('change', () => {
                    updateField(row.dataset.fieldId, { is_active: toggleActive.checked ? 1 : 0 });
                });
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => {
                    if (!confirm('Delete this custom field? Data already captured will remain in the database.')) {
                        return;
                    }
                    deleteField(row.dataset.fieldId, row);
                });
            }
        }

        function updateField(fieldId, payload) {
            const body = new URLSearchParams();
            Object.entries(payload).forEach(([key, value]) => body.append(key, value));
            body.append('_method', 'PATCH');

            fetch(`{{ url('/custom-fields') }}/${fieldId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Update failed.');
                    }
                })
                .catch((error) => alert(error.message));
        }

        function deleteField(fieldId, row) {
            const body = new URLSearchParams();
            body.append('_method', 'DELETE');

            fetch(`{{ url('/custom-fields') }}/${fieldId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Unable to delete field.');
                    }
                    row.remove();
                })
                .catch((error) => alert(error.message));
        }

        document.querySelectorAll('#custom-fields-body tr').forEach(attachRowActions);
    </script>
@endpush
