<div class="card" style="padding:1.25rem;">
    <h3 style="margin:0;font-size:1.15rem;">Last Merge Summary</h3>
    <p style="margin:0.35rem 0 1rem;color:#64748b;font-size:0.9rem;">{{ $secondary->name }} (ID {{ $secondary->id }}) merged into {{ $master->name }} (ID {{ $master->id }}).</p>

    <div class="grid-two">
        <div>
            <h4 style="margin:0 0 0.5rem;font-size:1rem;">Attributes Filled</h4>
            @forelse ($plan['attributes_to_fill'] ?? [] as $attribute => $details)
                <div style="margin-bottom:0.4rem;background:#ecfeff;border-radius:8px;padding:0.5rem 0.6rem;">
                    <div style="font-weight:600;text-transform:capitalize;">{{ $attribute }}</div>
                    <div style="font-size:0.85rem;color:#0f172a;">{{ $details['value'] }}</div>
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.9rem;">No empty attributes required data.</p>
            @endforelse
        </div>
        <div>
            <h4 style="margin:0 0 0.5rem;font-size:1rem;">Additional Data</h4>
            <div style="margin-bottom:0.35rem;">
                <strong>Emails:</strong>
                @if (!empty($plan['emails_to_append']))
                    @foreach ($plan['emails_to_append'] as $email)
                        <span class="tag" style="background:#dbeafe;color:#1d4ed8;">{{ $email['email'] }}</span>
                    @endforeach
                @else
                    <span style="color:#94a3b8;font-size:0.9rem;">No new emails</span>
                @endif
            </div>
            <div>
                <strong>Phones:</strong>
                @if (!empty($plan['phones_to_append']))
                    @foreach ($plan['phones_to_append'] as $phone)
                        <span class="tag" style="background:#fef9c3;color:#0f172a;">{{ $phone['phone'] }}</span>
                    @endforeach
                @else
                    <span style="color:#94a3b8;font-size:0.9rem;">No new phone numbers</span>
                @endif
            </div>
        </div>
    </div>

    <div style="margin-top:1rem;">
        <h4 style="margin:0 0 0.5rem;font-size:1rem;">Custom Fields Updated</h4>
        @forelse ($plan['custom_fields'] ?? [] as $item)
            <div style="margin-bottom:0.45rem;padding:0.5rem 0.6rem;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                <div style="font-weight:600;">{{ $item['field_name'] }}</div>
                <div style="font-size:0.85rem;color:#1f2937;">Value: {{ $item['value'] }}</div>
                <div style="font-size:0.75rem;color:#475569;text-transform:uppercase;">{{ $item['action'] === 'adopt' ? 'Master adopted this value' : 'Stored as additional context' }}</div>
            </div>
        @empty
            <p style="color:#94a3b8;font-size:0.9rem;">No custom field adjustments necessary.</p>
        @endforelse
    </div>

    <div style="margin-top:1rem;">
        <h4 style="margin:0 0 0.5rem;font-size:1rem;">Files</h4>
        @foreach (($plan['files'] ?? []) as $type => $info)
            <div style="font-size:0.9rem;color:#475569;margin-bottom:0.35rem;">
                <strong>{{ str_replace('_', ' ', ucfirst($type)) }}:</strong> {{ $info['action'] }}
            </div>
        @endforeach
    </div>

    <p style="margin-top:1.25rem;font-size:0.85rem;color:#64748b;">The secondary record is now marked as merged and kept for audit history. Filters automatically exclude merged contacts by default.</p>
</div>
