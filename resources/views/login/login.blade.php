@extends('layouts.login')
@section('title', 'Login')   
@section('content')

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">

            

            {{-- Erros --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <b>Ops:</b>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Cadastrar --}}
            <form action="{{ route('login.store') }}" method="POST" class="mb-4">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" placeholder="Email" value="{{ old('email') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="password" name="senha" class="form-control" placeholder="Senha" value="{{ old('senha') }}">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit">Entrar</button>
                    </div>
                </div>
            </form>
            {{-- Voltar --}}
            <a href= "{{ route('login.index') }}">
                            <button class="btn btn-primary" type="submit">Voltar</button>
            </a>
            
        </div>
    </div>
</div>

@endsection