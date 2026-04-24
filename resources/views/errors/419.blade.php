@php
    $title = 'Session Expired';
    $code = '419';
    $heading = 'Session Expired';
    $message = 'Your session has expired. Please go back and try the action again.';
@endphp
@include('errors.minimal', compact('title', 'code', 'heading', 'message'))
