@php
    use Illuminate\Support\Facades\Storage;

    $emailsCollection = collect();
    if ($contact->email) {
        $emailsCollection->push([
            'email' => $contact->email,
            'is_primary' => true,
        ]);
    }

    foreach ($contact->emails as $email) {
        if ($emailsCollection->contains(fn ($item) => strcasecmp($item['email'], $email->email) === 0)) {
            continue;
        }
        $emailsCollection->push([
            'email' => $email->email,
            'is_primary' => (bool) $email->is_primary,
        ]);
    }

    $phonesCollection = collect();
    if ($contact->phone) {
        $phonesCollection->push([
            'phone' => $contact->phone,
            'is_primary' => true,
        ]);
    }

    foreach ($contact->phones as $phone) {
        if ($phonesCollection->contains(fn ($item) => $item['phone'] === $phone->phone)) {
            continue;
        }
        $phonesCollection->push([
            'phone' => $phone->phone,
            'is_primary' => (bool) $phone->is_primary,
        ]);
    }

    $customFieldPayload = [];
    $customFieldDisplay = [];
    foreach ($contact->customFieldValues as $value) {
        $fieldName = $value->customField->name ?? ('Field #' . $value->custom_field_id);
        if ($value->is_primary && !array_key_exists($value->custom_field_id, $customFieldPayload)) {
            $customFieldPayload[$value->custom_field_id] = $value->value;
        }

        $customFieldDisplay[$fieldName][] = [
            'value' => $value->value,
            'is_primary' => (bool) $value->is_primary,
            'origin_contact_id' => $value->origin_contact_id,
        ];
    }

    $profileUrl = $contact->profile_image_path ? Storage::disk('public')->url($contact->profile_image_path) : null;
    $documentUrl = $contact->document_path ? Storage::disk('public')->url($contact->document_path) : null;

    $initials = collect(explode(' ', (string) $contact->name))
        ->filter()
        ->map(fn ($segment) => strtoupper(mb_substr($segment, 0, 1)))
        ->take(2)
        ->implode('');

    if ($initials === '') {
        $initials = 'C';
    }

    $contactPayload = [
        'id' => $contact->id,
        'name' => $contact->name,
        'email' => $contact->email,
        'phone' => $contact->phone,
        'gender' => $contact->gender,
        'status' => $contact->status,
        'profile_image_url' => $profileUrl,
        'document_url' => $documentUrl,
        'custom_fields' => $customFieldPayload,
    ];
@endphp

<tr class="contact-row" data-contact-id="{{ $contact->id }}" data-contact='@json($contactPayload)'>
    <td>
        <input type="checkbox" data-select-contact data-contact-id="{{ $contact->id }}">
    </td>
    <td>
        <div class="name-cell">
            <div class="avatar">{{ $initials }}</div>
            <div class="meta">
                <strong>{{ $contact->name }}</strong>
                <span>ID #{{ $contact->id }} · Created {{ $contact->created_at?->format('d M Y') }}</span>
            </div>
        </div>
    </td>
    <td>
        <div class="stacked">
            @forelse ($emailsCollection as $email)
                <span class="tag" style="background: {{ $email['is_primary'] ? '#dbeafe' : '#e2e8f0' }}; color: {{ $email['is_primary'] ? '#1d4ed8' : '#1f2937' }};">
                    {{ $email['email'] }}@if($email['is_primary'])<span style="margin-left:0.3rem;font-size:0.7rem;text-transform:uppercase;">primary</span>@endif
                </span>
            @empty
                <span style="color:#94a3b8;">—</span>
            @endforelse
        </div>
    </td>
    <td>
        <div class="stacked">
            @forelse ($phonesCollection as $phone)
                <span class="tag" style="background: {{ $phone['is_primary'] ? '#fef9c3' : '#e2e8f0' }}; color:#1f2937;">
                    {{ $phone['phone'] }}@if($phone['is_primary'])<span style="margin-left:0.3rem;font-size:0.7rem;text-transform:uppercase;">primary</span>@endif
                </span>
            @empty
                <span style="color:#94a3b8;">—</span>
            @endforelse
        </div>
    </td>
    <td>{{ $contact->gender ? ucfirst($contact->gender) : '—' }}</td>
    <td>
        @forelse ($customFieldDisplay as $fieldName => $values)
            <div style="margin-bottom:0.35rem;">
                <div style="font-size:0.85rem;color:#0f172a;font-weight:600;">{{ $fieldName }}</div>
                @foreach ($values as $value)
                    <span class="tag" style="background: {{ $value['is_primary'] ? '#e0f2fe' : '#e2e8f0' }};">
                        {{ $value['value'] ?? '—' }}
                        @if (!$value['is_primary'])
                            <span style="margin-left:0.3rem;font-size:0.65rem;text-transform:uppercase;color:#475569;">secondary</span>
                        @endif
                    </span>
                @endforeach
            </div>
        @empty
            <span style="color:#94a3b8;">No custom data</span>
        @endforelse
    </td>
    <td>
        <div class="stacked">
            @if ($profileUrl)
                <a href="{{ $profileUrl }}" target="_blank">Profile image</a>
            @endif
            @if ($documentUrl)
                <a href="{{ $documentUrl }}" target="_blank">Attachment</a>
            @endif
            @if (!$profileUrl && !$documentUrl)
                <span style="color:#94a3b8;">—</span>
            @endif
        </div>
    </td>
    <td>
        @php
            $statusClass = match ($contact->status) {
                'merged' => 'status-merged',
                'archived' => 'status-archived',
                default => 'status-active',
            };
        @endphp
        <span class="status-pill {{ $statusClass }}">{{ ucfirst($contact->status) }}</span>
    </td>
    <td>
        <div class="table-actions">
            <button class="btn btn-secondary" type="button" data-action="edit">Edit</button>
            <button class="btn btn-danger" type="button" data-action="delete">Archive</button>
        </div>
    </td>
</tr>
