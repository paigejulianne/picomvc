@extends('layout')

@section('content')
<h1>{{ $title }}</h1>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') @endphp
            @foreach ($users as $user)
            <tr>
                <td>{{ $user['id'] }}</td>
                <td>{{ $user['name'] }}</td>
                <td>{{ $user['email'] }}</td>
                <td>
                    <a href="{{ $baseUrl }}/users/{{ $user['id'] }}" class="btn">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
