@php
    $title = 'Service Unavailable';
    $code = '503';
    $heading = 'Service Unavailable';
    $message = 'The service is temporarily unavailable. Please try again in a few minutes.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
