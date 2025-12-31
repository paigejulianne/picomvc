@extends('layout')

@section('content')
<h1>404 - Page Not Found</h1>

<div class="card">
    <p>The page you're looking for doesn't exist.</p>
    @php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') @endphp
    <p style="margin-top: 1rem;">
        <a href="{{ $baseUrl }}/" class="btn">Go Home</a>
    </p>
</div>
@endsection
