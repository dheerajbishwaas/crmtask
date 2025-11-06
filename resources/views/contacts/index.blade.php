@extends('layouts.app')

@section('content')
    <div class="card" id="contacts-board">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
            <div>
                <h2 style="margin:0;font-size:1.25rem;">Contact Directory</h2>
                <p style="margin:0.25rem 0 0;color:#64748b;">Manage contacts, custom fields, and duplicates without leaving this screen.</p>
            </div>
            <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                <button class="btn btn-secondary" id="refresh-list-btn">Refresh</button>
                <button class="btn btn-primary" id="create-contact-btn">Add Contact</button>
                <button class="btn btn-secondary" id="merge-selected-btn" disabled>Merge Selected</button>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <span class="label">Active Contacts</span>
                <span class="value">{{ number_format($stats['active'] ?? 0) }}</span>
            </div>
            <div class="stat-card">
                <span class="label">Total Contacts</span>
                <span class="value">{{ number_format($stats['total'] ?? 0) }}</span>
            </div>
            <div class="stat-card">
                <span class="label">Merged Records</span>
                <span class="value">{{ number_format($stats['merged'] ?? 0) }}</span>
            </div>
            <div class="stat-card">
                <span class="label">Custom Fields</span>
                <span class="value">{{ number_format($stats['custom_fields'] ?? 0) }}</span>
            </div>
        </div>

        <form id="filter-form" class="filters" style="margin-top:1.5rem;">
            <div class="filter-chip">
                <label for="filter-name">Name</label>
                <input class="input" type="text" id="filter-name" name="name" placeholder="Search by name">
            </div>
            <div class="filter-chip">
                <label for="filter-email">Email</label>
                <input class="input" type="text" id="filter-email" name="email" placeholder="Search by email">
            </div>
            <div class="filter-chip">
                <label for="filter-gender">Gender</label>
                <select class="input" id="filter-gender" name="gender">
                    <option value="">Any</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="filter-chip">
                <label for="filter-custom-field">Custom Field</label>
                <select class="input" id="filter-custom-field" name="custom_field_id">
                    <option value="">Any</option>
                    @foreach ($customFields as $field)
                        <option value="{{ $field->id }}">{{ $field->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-chip">
                <label for="filter-custom-value">Custom Value</label>
                <input class="input" type="text" id="filter-custom-value" name="custom_field_value" placeholder="Matches value">
            </div>
            <div class="filter-actions">
                <button class="btn btn-secondary" type="submit">Apply Filters</button>
                <button class="btn btn-ghost" type="button" id="reset-filters-btn">Reset</button>
            </div>
        </form>

        <div class="table-wrapper" style="overflow-x:auto;">
            <table class="table" id="contacts-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="select-all"></th>
                        <th>Name</th>
                        <th>Emails</th>
                        <th>Phones</th>
                        <th>Gender</th>
                        <th>Custom Fields</th>
                        <th>Files</th>
                        <th>Status</th>
                        <th style="width:180px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="contact-table-body">
                    @include('contacts.partials.table_rows', ['contacts' => $contacts])
                </tbody>
            </table>
        </div>

        <div id="merge-summary-container" style="margin-top:2rem;display:none;"></div>
    </div>
@endsection

@push('modals')
    <div class="modal-backdrop" id="contact-modal-backdrop"></div>
    <div class="modal" id="contact-modal">
        <div class="modal-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                <div>
                    <h3 id="contact-modal-title" style="margin:0;font-size:1.2rem;">Add Contact</h3>
                    <p style="margin:0.25rem 0 0;color:#64748b;">Standard fields plus dynamic custom fields appear below.</p>
                </div>
                <button class="btn" type="button" id="close-contact-modal" style="background:#f1f5f9;color:#0f172a;">Close</button>
            </div>

            <form id="contact-form" enctype="multipart/form-data" style="margin-top:1.25rem;display:flex;flex-direction:column;gap:1.2rem;">
                <input type="hidden" id="contact-id" name="contact_id">
                <div class="grid-two">
                    <div class="form-group">
                        <label class="form-label" for="contact-name">Name</label>
                        <input class="input" type="text" id="contact-name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-email">Email</label>
                        <input class="input" type="email" id="contact-email" name="email" placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-phone">Phone</label>
                        <input class="input" type="text" id="contact-phone" name="phone" placeholder="+62 ...">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-gender">Gender</label>
                        <select class="input" id="contact-gender" name="gender">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-profile-image">Profile Image</label>
                        <div class="file-upload">
                            <label class="file-upload-button">
                                Choose file
                                <input class="file-input-control" type="file" id="contact-profile-image" name="profile_image" accept="image/*">
                            </label>
                            <span class="file-upload-name" data-placeholder="No file chosen">No file chosen</span>
                        </div>
                        <div id="existing-profile-image" style="font-size:0.85rem;color:#475569;margin-top:0.35rem;"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-document">Additional File</label>
                        <div class="file-upload">
                            <label class="file-upload-button">
                                Choose file
                                <input class="file-input-control" type="file" id="contact-document" name="document">
                            </label>
                            <span class="file-upload-name" data-placeholder="No file chosen">No file chosen</span>
                        </div>
                        <div id="existing-document" style="font-size:0.85rem;color:#475569;margin-top:0.35rem;"></div>
                    </div>
                </div>

                <div>
                    <h4 style="margin:0;font-size:1.05rem;">Custom Fields</h4>
                    <p style="margin:0.25rem 0 0;color:#94a3b8;font-size:0.9rem;">Add extra context captured from your configured custom fields.</p>
                    <div id="custom-fields-container" style="margin-top:0.75rem;display:grid;gap:0.85rem;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
                        @foreach ($customFields as $field)
                            <div class="form-group" data-custom-field-id="{{ $field->id }}" data-custom-field-type="{{ $field->field_type }}">
                                <label class="form-label">{{ $field->name }}@if($field->is_required)<span style="color:#dc2626;">*</span>@endif</label>
                                @if ($field->field_type === 'select')
                                    <select class="input" name="custom_fields[{{ $field->id }}]">
                                        <option value="">Select option</option>
                                        @foreach ($field->options ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field->field_type === 'date')
                                    <input class="input" type="date" name="custom_fields[{{ $field->id }}]">
                                @elseif($field->field_type === 'number')
                                    <input class="input" type="number" step="any" name="custom_fields[{{ $field->id }}]">
                                @else
                                    <input class="input" type="text" name="custom_fields[{{ $field->id }}]">
                                @endif
                            </div>
                        @endforeach
                        @if ($customFields->isEmpty())
                            <p style="grid-column:1/-1;color:#94a3b8;font-size:0.9rem;">No custom fields yet. Visit the Custom Fields tab to add one.</p>
                        @endif
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:0.6rem;">
                    <button class="btn" type="button" id="cancel-contact-btn" style="background:#f1f5f9;color:#0f172a;">Cancel</button>
                    <button class="btn btn-primary" type="submit" id="submit-contact-btn">Save Contact</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-backdrop" id="merge-modal-backdrop"></div>
    <div class="modal" id="merge-modal">
        <div class="modal-card" style="width:min(720px,100% - 2rem);">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                <div>
                    <h3 style="margin:0;font-size:1.2rem;">Merge Contacts</h3>
                    <p style="margin:0.25rem 0 0;color:#64748b;">Choose which contact remains as the master, preview the merge, then confirm.</p>
                </div>
                <button class="btn" type="button" id="close-merge-modal" style="background:#f1f5f9;color:#0f172a;">Close</button>
            </div>

            <div id="merge-selection" style="margin-top:1rem;display:flex;flex-direction:column;gap:0.75rem;"></div>

            <div id="merge-preview" class="merge-summary" style="display:none;"></div>

            <div style="display:flex;justify-content:flex-end;gap:0.6rem;margin-top:1.25rem;">
                <button class="btn" type="button" id="cancel-merge-btn" style="background:#f1f5f9;color:#0f172a;">Cancel</button>
                <button class="btn btn-primary" type="button" id="confirm-merge-btn" disabled>Confirm Merge</button>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const endpoints = {
            list: '{{ route('contacts.list') }}',
            store: '{{ route('contacts.store') }}',
            update: (id) => `{{ url('/contacts') }}/${id}`,
            destroy: (id) => `{{ url('/contacts') }}/${id}`,
            mergePreview: '{{ route('contacts.merge.preview') }}',
            mergeStore: '{{ route('contacts.merge.store') }}'
        };

        const contactModal = document.getElementById('contact-modal');
        const contactBackdrop = document.getElementById('contact-modal-backdrop');
        const mergeModal = document.getElementById('merge-modal');
        const mergeBackdrop = document.getElementById('merge-modal-backdrop');
        const mergeSelection = document.getElementById('merge-selection');
        const mergePreviewContainer = document.getElementById('merge-preview');
        const mergeSummaryContainer = document.getElementById('merge-summary-container');
        const confirmMergeBtn = document.getElementById('confirm-merge-btn');
        const flashMessageEl = document.getElementById('flash-message');

        const contactForm = document.getElementById('contact-form');
        const contactModalTitle = document.getElementById('contact-modal-title');
        const submitContactBtn = document.getElementById('submit-contact-btn');
        const contactIdInput = document.getElementById('contact-id');
        const contactTableBody = document.getElementById('contact-table-body');
        const selectAllCheckbox = document.getElementById('select-all');
        const mergeButton = document.getElementById('merge-selected-btn');

        const profileImageInfo = document.getElementById('existing-profile-image');
        const documentInfo = document.getElementById('existing-document');

        let currentMergePair = [];
        let selectedMasterId = null;

        function refreshFileUploadLabels() {
            document.querySelectorAll('.file-upload').forEach((wrapper) => {
                const input = wrapper.querySelector('input[type="file"]');
                const label = wrapper.querySelector('.file-upload-name');
                if (!wrapper.dataset.bound) {
                    wrapper.dataset.bound = 'true';
                    input.addEventListener('change', () => {
                        const names = input.files && input.files.length
                            ? Array.from(input.files).map((file) => file.name).join(', ')
                            : (label.dataset.placeholder || 'No file chosen');
                        label.textContent = names;
                    });
                }
                label.textContent = label.dataset.placeholder || 'No file chosen';
            });
        }

        function openModal(modal, backdrop) {
            modal.classList.add('active');
            backdrop.classList.add('active');
        }

        function closeModal(modal, backdrop) {
            modal.classList.remove('active');
            backdrop.classList.remove('active');
        }

        function resetContactForm() {
            contactForm.reset();
            contactIdInput.value = '';
            contactForm.dataset.mode = 'create';
            contactModalTitle.textContent = 'Add Contact';
            submitContactBtn.textContent = 'Save Contact';
            profileImageInfo.textContent = '';
            documentInfo.textContent = '';
            refreshFileUploadLabels();
        }

        function setFlash(message, type = 'success') {
            flashMessageEl.textContent = message;
            flashMessageEl.className = `message ${type}`;
        }

        function clearFlash() {
            flashMessageEl.textContent = '';
            flashMessageEl.className = 'message';
        }

        document.getElementById('create-contact-btn').addEventListener('click', () => {
            resetContactForm();
            openModal(contactModal, contactBackdrop);
        });

        document.getElementById('close-contact-modal').addEventListener('click', () => {
            closeModal(contactModal, contactBackdrop);
        });

        document.getElementById('cancel-contact-btn').addEventListener('click', () => {
            closeModal(contactModal, contactBackdrop);
        });

        document.getElementById('refresh-list-btn').addEventListener('click', () => {
            applyFilters();
        });

        document.getElementById('filter-form').addEventListener('submit', (event) => {
            event.preventDefault();
            applyFilters();
        });

        document.getElementById('reset-filters-btn').addEventListener('click', () => {
            const form = document.getElementById('filter-form');
            form.reset();
            applyFilters();
        });

        function applyFilters() {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            formData.forEach((value, key) => {
                if (value) {
                    params.append(key, value);
                }
            });

            fetch(`${endpoints.list}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch contacts');
                    }
                    return response.json();
                })
                .then((data) => {
                    contactTableBody.innerHTML = data.html;
                    attachRowListeners();
                    updateMergeButtonState();
                    clearFlash();
                })
                .catch((error) => {
                    setFlash(error.message, 'error');
                });
        }

        function buildFormData(form) {
            const formData = new FormData(form);
            return formData;
        }

        contactForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitContact();
        });

        function submitContact() {
            const mode = contactForm.dataset.mode || 'create';
            const contactId = contactIdInput.value;
            const formData = buildFormData(contactForm);

            let url = endpoints.store;
            let method = 'POST';

            if (mode === 'edit' && contactId) {
                url = endpoints.update(contactId);
                formData.append('_method', 'PUT');
            }

            submitContactBtn.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
                .then(async (response) => {
                    submitContactBtn.disabled = false;
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        const message = data.message || 'Unable to save contact.';
                        throw new Error(message);
                    }
                    return response.json();
                })
                .then((data) => {
                    updateContactRow(contactId, data.row, mode);
                    closeModal(contactModal, contactBackdrop);
                    setFlash(data.message, 'success');
                })
                .catch((error) => {
                    submitContactBtn.disabled = false;
                    setFlash(error.message, 'error');
                });
        }

        function updateContactRow(contactId, rowHtml, mode) {
            const temp = document.createElement('tbody');
            temp.innerHTML = rowHtml.trim();
            const newRow = temp.firstElementChild;
            if (!newRow) {
                return;
            }
            const rowId = newRow.getAttribute('data-contact-id');

            if (mode === 'edit' && contactId) {
                const existingRow = contactTableBody.querySelector(`tr[data-contact-id="${rowId}"]`);
                if (existingRow) {
                    existingRow.replaceWith(newRow);
                } else {
                    contactTableBody.prepend(newRow);
                }
            } else {
                contactTableBody.prepend(newRow);
            }

            attachListenersToRow(newRow);
            updateMergeButtonState();
        }

        function attachRowListeners() {
            contactTableBody.querySelectorAll('tr').forEach((row) => attachListenersToRow(row));
        }

        function attachListenersToRow(row) {
            const editBtn = row.querySelector('[data-action="edit"]');
            const deleteBtn = row.querySelector('[data-action="delete"]');
            const checkbox = row.querySelector('input[type="checkbox"][data-select-contact]');

            if (editBtn) {
                editBtn.addEventListener('click', () => openEditModal(row));
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => deleteContact(row));
            }

            if (checkbox) {
                checkbox.addEventListener('change', updateMergeButtonState);
            }
        }

        function openEditModal(row) {
            resetContactForm();
            const payload = row.getAttribute('data-contact');
            if (!payload) {
                return;
            }

            const data = JSON.parse(payload);

            contactForm.dataset.mode = 'edit';
            contactModalTitle.textContent = 'Edit Contact';
            submitContactBtn.textContent = 'Update Contact';

            contactIdInput.value = data.id;
            document.getElementById('contact-name').value = data.name || '';
            document.getElementById('contact-email').value = data.email || '';
            document.getElementById('contact-phone').value = data.phone || '';
            document.getElementById('contact-gender').value = data.gender || '';

            if (data.profile_image_url) {
                profileImageInfo.innerHTML = `<span>Existing image: <a href="${data.profile_image_url}" target="_blank">view</a></span>`;
            }

            if (data.document_url) {
                documentInfo.innerHTML = `<span>Existing file: <a href="${data.document_url}" target="_blank">download</a></span>`;
            }

            refreshFileUploadLabels();

            const customValues = data.custom_fields || {};
            document.querySelectorAll('#custom-fields-container [data-custom-field-id]').forEach((wrapper) => {
                const fieldId = wrapper.getAttribute('data-custom-field-id');
                const input = wrapper.querySelector('input, select, textarea');
                if (!input) {
                    return;
                }
                input.value = customValues[fieldId] ?? '';
            });

            openModal(contactModal, contactBackdrop);
        }

        function deleteContact(row) {
            const contactId = row.getAttribute('data-contact-id');
            if (!contactId) {
                return;
            }

            if (!confirm('Archive this contact? The record can be restored from the database if needed.')) {
                return;
            }

            fetch(endpoints.destroy(contactId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ _method: 'DELETE' }),
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Unable to archive contact.');
                    }
                    return response.json();
                })
                .then((data) => {
                    row.remove();
                    updateMergeButtonState();
                    setFlash(data.message, 'success');
                })
                .catch((error) => {
                    setFlash(error.message, 'error');
                });
        }

        selectAllCheckbox.addEventListener('change', (event) => {
            const checked = event.target.checked;
            document.querySelectorAll('input[data-select-contact]').forEach((checkbox) => {
                checkbox.checked = checked;
            });
            updateMergeButtonState();
        });

        function getSelectedContacts() {
            const selected = [];
            document.querySelectorAll('input[data-select-contact]:checked').forEach((checkbox) => {
                selected.push(checkbox.getAttribute('data-contact-id'));
            });
            return selected;
        }

        function updateMergeButtonState() {
            const selected = getSelectedContacts();
            mergeButton.disabled = selected.length !== 2;
            selectAllCheckbox.checked = selected.length > 0 && selected.length === document.querySelectorAll('input[data-select-contact]').length;
        }

        mergeButton.addEventListener('click', () => {
            const selected = getSelectedContacts();
            if (selected.length !== 2) {
                return;
            }

            currentMergePair = selected;
            selectedMasterId = null;
            confirmMergeBtn.disabled = true;
            mergePreviewContainer.style.display = 'none';
            mergePreviewContainer.innerHTML = '';

            buildMergeSelection();
            openModal(mergeModal, mergeBackdrop);
        });

        function buildMergeSelection() {
            mergeSelection.innerHTML = '';
            currentMergePair.forEach((contactId) => {
                const row = document.querySelector(`tr[data-contact-id="${contactId}"]`);
                if (!row) {
                    return;
                }
                const payload = JSON.parse(row.getAttribute('data-contact'));
                const radioId = `merge-master-${contactId}`;

                const wrapper = document.createElement('label');
                wrapper.style.display = 'flex';
                wrapper.style.alignItems = 'center';
                wrapper.style.gap = '0.75rem';
                wrapper.style.padding = '0.75rem 1rem';
                wrapper.style.border = '1px solid #e2e8f0';
                wrapper.style.borderRadius = '10px';
                wrapper.style.cursor = 'pointer';

                wrapper.innerHTML = `
                    <input type="radio" name="merge-master" value="${contactId}" id="${radioId}" ${selectedMasterId === contactId ? 'checked' : ''}>
                    <div>
                        <div style="font-weight:600;">${payload.name}</div>
                        <div style="font-size:0.85rem;color:#64748b;">${payload.email || '—'} · ${payload.phone || '—'}</div>
                    </div>
                `;

                const radio = wrapper.querySelector('input');
                radio.addEventListener('change', () => {
                    selectedMasterId = contactId;
                    loadMergePreview();
                });

                mergeSelection.appendChild(wrapper);
            });
        }

        function loadMergePreview() {
            if (!selectedMasterId) {
                confirmMergeBtn.disabled = true;
                return;
            }
            const [first, second] = currentMergePair;
            const secondaryId = selectedMasterId === first ? second : first;

            fetch(endpoints.mergePreview, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    master_contact_id: selectedMasterId,
                    secondary_contact_id: secondaryId,
                }),
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Unable to load merge preview.');
                    }
                    return response.json();
                })
                .then((data) => {
                    mergePreviewContainer.innerHTML = data.modal;
                    mergePreviewContainer.style.display = 'block';
                    confirmMergeBtn.disabled = false;
                })
                .catch((error) => {
                    mergePreviewContainer.innerHTML = `<p style="color:#b91c1c;">${error.message}</p>`;
                    mergePreviewContainer.style.display = 'block';
                    confirmMergeBtn.disabled = true;
                });
        }

        confirmMergeBtn.addEventListener('click', () => {
            if (!selectedMasterId) {
                return;
            }

            const [first, second] = currentMergePair;
            const secondaryId = selectedMasterId === first ? second : first;

            confirmMergeBtn.disabled = true;

            fetch(endpoints.mergeStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    master_contact_id: selectedMasterId,
                    secondary_contact_id: secondaryId,
                }),
            })
                .then(async (response) => {
                    confirmMergeBtn.disabled = false;
                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(data.message || 'Merge failed.');
                    }
                    return response.json();
                })
                .then((data) => {
                    const temp = document.createElement('tbody');
                    temp.innerHTML = data.row.trim();
                    const updatedRow = temp.firstElementChild;
                    const masterRow = document.querySelector(`tr[data-contact-id="${updatedRow.getAttribute('data-contact-id')}"]`);
                    if (masterRow) {
                        masterRow.replaceWith(updatedRow);
                    } else {
                        contactTableBody.prepend(updatedRow);
                    }
                    attachListenersToRow(updatedRow);

                    const secondaryRow = document.querySelector(`tr[data-contact-id="${secondaryId}"]`);
                    if (secondaryRow) {
                        secondaryRow.remove();
                    }

                    mergeSummaryContainer.style.display = 'block';
                    mergeSummaryContainer.innerHTML = data.summary;

                    closeModal(mergeModal, mergeBackdrop);
                    updateMergeButtonState();
                    setFlash(data.message, 'success');
                })
                .catch((error) => {
                    setFlash(error.message, 'error');
                });
        });

        document.getElementById('close-merge-modal').addEventListener('click', () => {
            closeModal(mergeModal, mergeBackdrop);
        });

        document.getElementById('cancel-merge-btn').addEventListener('click', () => {
            closeModal(mergeModal, mergeBackdrop);
        });

        attachRowListeners();
        refreshFileUploadLabels();
    </script>
@endpush
