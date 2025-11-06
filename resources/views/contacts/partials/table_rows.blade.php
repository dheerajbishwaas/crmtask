@foreach ($contacts as $contact)
    @include('contacts.partials.contact_row', ['contact' => $contact])
@endforeach
