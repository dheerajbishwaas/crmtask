<div>
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <h4 style="margin:0;font-size:1.05rem;">Master: {{ $master->name }}</h4>
            <p style="margin:0.25rem 0 0;font-size:0.9rem;color:#475569;">This contact will remain active.</p>
        </div>
        <div>
            <h4 style="margin:0;font-size:1.05rem;">Secondary: {{ $secondary->name }}</h4>
            <p style="margin:0.25rem 0 0;font-size:0.9rem;color:#475569;">Data from this contact will be merged.</p>
        </div>
    </div>

    <hr style="margin:1rem 0;border:none;border-top:1px solid #e2e8f0;">

    <div class="grid-two">
        <div>
            <h5 style="margin:0 0 0.5rem;font-size:0.95rem;color:#0f172a;">Master Values Kept</h5>
            @forelse ($plan['attributes_retained'] ?? [] as $attribute => $details)
                <div style="margin-bottom:0.45rem;background:#f1f5f9;border-radius:8px;padding:0.45rem 0.6rem;">
                    <div style="font-weight:600;text-transform:capitalize;">{{ $attribute }}</div>
                    <div style="font-size:0.85rem;color:#475569;">Master: {{ $details['master_value'] ?? '—' }}</div>
                    @if (!empty($details['secondary_value']))
                        <div style="font-size:0.8rem;color:#94a3b8;">Secondary: {{ $details['secondary_value'] }}</div>
                    @endif
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.9rem;">No overlaps detected.</p>
            @endforelse
        </div>
        <div>
            <h5 style="margin:0 0 0.5rem;font-size:0.95rem;color:#0f172a;">Attributes To Fill</h5>
            @forelse ($plan['attributes_to_fill'] ?? [] as $attribute => $details)
                <div style="margin-bottom:0.45rem;background:#ecfeff;border-radius:8px;padding:0.45rem 0.6rem;">
                    <div style="font-weight:600;text-transform:capitalize;">{{ $attribute }}</div>
                    <div style="font-size:0.85rem;color:#0f172a;">Value: {{ $details['value'] }}</div>
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.9rem;">Master already has all attributes.</p>
            @endforelse
        </div>
    </div>

    <div class="grid-two" style="margin-top:1rem;">
        <div>
            <h5 style="margin:0 0 0.5rem;font-size:0.95rem;color:#0f172a;">Emails To Append</h5>
            @forelse ($plan['emails_to_append'] ?? [] as $email)
                <span class="tag" style="background:#dbeafe;color:#1d4ed8;">{{ $email['email'] }}</span>
            @empty
                <p style="color:#94a3b8;font-size:0.9rem;">No additional emails.</p>
            @endforelse
        </div>
        <div>
            <h5 style="margin:0 0 0.5rem;font-size:0.95rem;color:#0f172a;">Phones To Append</h5>
            @forelse ($plan['phones_to_append'] ?? [] as $phone)
                <span class="tag" style="background:#fef9c3;color:#0f172a;">{{ $phone['phone'] }}</span>
            @empty
                <p style="color:#94a3b8;font-size:0.9rem;">No additional phone numbers.</p>
            @endforelse
        </div>
    </div>

    <div style="margin-top:1rem;">
        <h5 style="margin:0 0 0.5rem;font-size:0.95rem;color:#0f172a;">Custom Field Actions</h5>
        @forelse ($plan['custom_fields'] ?? [] as $item)
            <div style="margin-bottom:0.45rem;padding:0.45rem 0.6rem;border-radius:8px;background:{{ $item['action'] === 'adopt' ? '#dcfce7' : '#f1f5f9' }};">
                <strong>{{ $item['field_name'] }}</strong>
                <div style="font-size:0.85rem;color:#1f2937;">Value: {{ $item['value'] }}</div>
                <div style="font-size:0.75rem;color:#475569;text-transform:uppercase;">{{ $item['action'] === 'adopt' ? 'Master will adopt this value' : 'Secondary value recorded as additional context' }}</div>
            </div>
        @empty
            <p style="color:#94a3b8;font-size:0.9rem;">No custom field updates required.</p>
        @endforelse
    </div>

    <div style="margin-top:1rem;display:flex;gap:1rem;flex-wrap:wrap;">
        @foreach (($plan['files'] ?? []) as $type => $info)
            <div style="flex:1 1 220px;background:#f8fafc;border-radius:8px;padding:0.75rem;">
                <div style="font-weight:600;text-transform:capitalize;">{{ str_replace('_', ' ', $type) }}</div>
                <div style="font-size:0.85rem;color:#475569;">Action: {{ $info['action'] }}</div>
                @if (!empty($info['secondary_path']) && $info['action'] === 'adopt_secondary')
                    <div style="margin-top:0.35rem;font-size:0.8rem;">
                        Secondary file retained. Ensure storage link exists to access the asset.
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
