@extends('layout')

@section('content')
<h1>{{ $title }}</h1>

<div class="card">
    <h2>About This Example</h2>
    <p>This example shows how to use NanoMVC with Laravel's Blade templating engine.</p>

    <h3 style="margin-top: 1.5rem;">Installation</h3>
    <p>To use Blade templates, install the jenssegers/blade package:</p>
    <pre style="background: #f5f5f5; padding: 1rem; margin: 1rem 0; border-radius: 4px;"><code>composer require jenssegers/blade</code></pre>

    <h3 style="margin-top: 1.5rem;">Configuration</h3>
    <p>Set the template engine in your <code>.config</code> file:</p>
    <pre style="background: #f5f5f5; padding: 1rem; margin: 1rem 0; border-radius: 4px;"><code>[views]
engine=blade
path=views
cache=cache</code></pre>
</div>
@endsection
