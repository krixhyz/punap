@php
    $title = 'Page Not Found';
    $code = '404';
    $heading = 'Page Not Found';
    $message = 'The page you requested does not exist or may have been moved.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
