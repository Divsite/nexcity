@extends('layouts.app')
@php
    use Illuminate\Support\Str;
@endphp

@section('title', Str::headline(str_replace('-', ' ', $slug)))

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">{{ Str::headline(str_replace('-', ' ', $slug)) }}</h4>
            <p class="card-text text-muted">
                Placeholder page for module: <strong>{{ $slug }}</strong>.
            </p>
        </div>
    </div>
@endsection
