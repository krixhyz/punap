@php
    $title = 'Server Error';
    $code = '500';
    $heading = 'Server Error';
    $message = 'Something went wrong on our side. Please try again shortly.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
