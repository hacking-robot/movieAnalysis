@extends('layouts.master')

@section('content')
    @each('movie._view', $movies, 'movie')
@endsection
