@php
    $title = 'Method Not Allowed';
    $code = '405';
    $heading = 'Method Not Allowed';
    $message = 'That action is not allowed on this URL. Try going back and using the page controls.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
