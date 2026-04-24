@php
    $title = 'Access Denied';
    $code = '403';
    $heading = 'Access Denied';
    $message = 'You do not have permission to view this page or resource.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
