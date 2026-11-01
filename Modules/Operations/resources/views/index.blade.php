@extends('operations::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('operations.name') !!}</p>
@endsection
