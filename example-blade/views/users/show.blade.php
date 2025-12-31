@extends('layout')

@section('content')
<h1>{{ $title }}</h1>

<div class="card">
    <h2>{{ $user['name'] }}</h2>

    <table>
        <tr>
            <th>ID</th>
            <td>{{ $user['id'] }}</td>
        </tr>
        <tr>
            <th>Name</th>
            <td>{{ $user['name'] }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $user['email'] }}</td>
        </tr>
    </table>

    @php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') @endphp
    <p style="margin-top: 1rem;">
        <a href="{{ $baseUrl }}/users" class="btn">Back to Users</a>
    </p>
</div>
@endsection
