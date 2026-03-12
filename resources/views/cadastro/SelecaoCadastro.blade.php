<main class="selecao-container">
    @yield('conteudo')
</main>

{{-- Estende o layout principal --}}
@extends('layouts.SelecaoCadastro')

@section('conteudo')
    <div class="header-text">
        <h2>Cadastro de <span>Personal</span></h2>
        <p>Preencha os dados abaixo para começar a gerenciar seus alunos.</p>
    </div>

    <div class="card" style="width: 100%; max-width: 500px; margin: 0 auto; border-color: var(--primary);">
        <form action="/salvar-personal" method="POST" style="width: 100%; display: flex; flex-direction: column; gap: 15px;">
            @csrf
            
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px;">
                <label>Nome Completo</label>
                <input type="text" name="nome" placeholder="Ex: João Silva" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #333; background: #0a0b0d; color: white;">
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px;">
                <label>CREF (Registro)</label>
                <input type="text" name="cref" placeholder="000000-G/SP" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #333; background: #0a0b0d; color: white;">
            </div>

            <button type="submit" style="background: var(--primary); color: black; font-weight: bold; padding: 15px; border: none; border-radius: 8px; cursor: pointer; margin-top: 10px; text-transform: uppercase;">
                Finalizar Cadastro
            </button>
            
            <a href="{{ route('cadastro.ir', ['tipo' => 'selecao']) }}" style="color: var(--text-dim); text-align: center; text-decoration: none; font-size: 0.9rem;">Voltar</a>
        </form>
    </div>
@endsection