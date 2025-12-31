@extends('layout')

@section('content')
<h1>{{ $title }}</h1>

<div class="card">
    <h2>{{ $message }}</h2>
    <p>This example demonstrates NanoMVC with the <strong>Blade</strong> templating engine.</p>

    <h3>Blade Features Used</h3>
    <ul>
        <li><code>@@extends</code> - Template inheritance</li>
        <li><code>@@section</code> / <code>@@yield</code> - Content sections</li>
        <li><code>@{{ $variable }}</code> - Escaped output</li>
        <li><code>@@foreach</code> - Loops</li>
        <li><code>@@if</code> / <code>@@else</code> - Conditionals</li>
    </ul>

    @php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') @endphp
    <p style="margin-top: 1rem;">
        <a href="{{ $baseUrl }}/users" class="btn">View Users</a>
    </p>
</div>
@endsection
