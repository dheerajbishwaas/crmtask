@php
    $options = $field->options ?? [];
@endphp

<tr data-field-id="{{ $field->id }}">
    <td>
        <div style="font-weight:600;">{{ $field->name }}</div>
        <div style="font-size:0.8rem;color:#94a3b8;">Slug: {{ $field->slug }}</div>
    </td>
    <td>{{ ucfirst($field->field_type) }}</td>
    <td>
        <label style="display:inline-flex;align-items:center;gap:0.35rem;">
            <input type="checkbox" data-action="toggle-required" {{ $field->is_required ? 'checked' : '' }}>
            Required
        </label>
    </td>
    <td>
        <label style="display:inline-flex;align-items:center;gap:0.35rem;">
            <input type="checkbox" data-action="toggle-active" {{ $field->is_active ? 'checked' : '' }}>
            Active
        </label>
    </td>
    <td>
        @if (!empty($options))
            @foreach ($options as $option)
                <span class="tag">{{ $option }}</span>
            @endforeach
        @else
            <span style="color:#94a3b8;">—</span>
        @endif
    </td>
    <td>
        <button class="btn btn-danger" type="button" data-action="delete">Delete</button>
    </td>
</tr>
