@php
    $title = 'Too Many Requests';
    $code = '429';
    $heading = 'Too Many Requests';
    $message = 'You have made too many requests in a short time. Please wait and try again.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
