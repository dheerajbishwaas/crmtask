@foreach ($fields as $field)
    @include('custom-fields.partials.field_row', ['field' => $field])
@endforeach
