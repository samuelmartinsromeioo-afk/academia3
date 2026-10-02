<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Academia as Academia;
use App\Models\Cadastro\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoController extends Controller
{
    // Upload de foto para personal (responde JSON para AJAX)
    public function storePersonal(Request $request)
    {
        $request->validate([
            'foto'    => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'legenda' => 'nullable|string|max:255',
        ]);

        $personal = Personal::findOrFail(session('personal_id'));

        if ($personal->fotos()->count() >= 9) {
            return response()->json(['erro' => 'Limite de 9 fotos atingido.'], 422);
        }

        $path = $request->file('foto')->store('galeria/personals', 'public');

        if (!$path) {
            return response()->json(['erro' => 'Falha ao salvar o arquivo. Verifique as permissões do servidor.'], 500);
        }

        $foto = $personal->fotos()->create([
            'path'    => $path,
            'legenda' => $request->legenda,
        ]);

        return response()->json([
            'sucesso' => true,
            'foto' => [
                'id'      => $foto->id,
                'url'     => asset('storage/' . $path),
                'legenda' => $foto->legenda,
            ],
            'total' => $personal->fotos()->count(),
        ]);
    }

    // Upload de foto para academia
    public function storeAcademia(Request $request)
    {
        $request->validate([
            'foto'    => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'legenda' => 'nullable|string|max:255',
        ]);

        $academia = Academia::findOrFail(session('academia_id'));

        if ($academia->fotos()->count() >= 5) {
            return redirect()->back()->with('error', 'Limite de 5 fotos atingido. Remova uma antes de adicionar outra.');
        }

        $path = $request->file('foto')->store('galeria/academias', 'public');

        $academia->fotos()->create([
            'path'    => $path,
            'legenda' => $request->legenda,
        ]);

        return redirect()->back()->with('success', 'Foto adicionada com sucesso!');
    }

    // Upload de foto para studio
    public function storeStudio(Request $request)
    {
        $request->validate([
            'foto'    => 'required|file|mimes:jpeg,jpg,png,gif,webp,heic,heif|max:10240',
            'legenda' => 'nullable|string|max:255',
        ]);

        $studio = Studio::findOrFail(session('studio_id'));

        if ($studio->fotos()->count() >= 5) {
            return redirect()->back()->with('error', 'Limite de 5 fotos atingido. Remova uma antes de adicionar outra.');
        }

        $path = $request->file('foto')->store('galeria/studios', 'public');

        $studio->fotos()->create([
            'path'    => $path,
            'legenda' => $request->legenda,
        ]);

        return redirect()->back()->with('success', 'Foto adicionada com sucesso!');
    }

    // Delete de foto — responde JSON para AJAX
    public function destroy($id)
    {
        $foto = Foto::findOrFail($id);

        /*
         * A01 — a checagem de dono compara o `fotavel_type` gravado com o
         * getMorphClass() REAL de cada model, em vez de strings escritas à mão.
         *
         * Antes as strings estavam com a caixa errada ('App\Models\cadastro\Personal'
         * e '...\cadastro\academia', ambas minúsculas, contra o namespace real
         * 'App\Models\Cadastro\...'), então as comparações de personal e academia
         * nunca davam verdadeiro: o dono legítimo tomava 403 ao apagar a própria
         * foto. Falhava fechado, mas authz por string literal quebra em silêncio a
         * cada renomeação de namespace — e numa próxima edição poderia falhar
         * ABERTO. Derivar do model elimina a classe de erro.
         */
        $donoPorSessao = [
            'personal_id' => \App\Models\Cadastro\Personal::class,
            'academia_id' => \App\Models\Cadastro\Academia::class,
            'studio_id'   => \App\Models\Cadastro\Studio::class,
            'loja_id'     => \App\Models\Cadastro\Loja::class,
        ];

        $ehDono = false;
        foreach ($donoPorSessao as $chaveSessao => $classe) {
            $id_ = session($chaveSessao);

            if ($id_
                && $foto->fotavel_type === (new $classe)->getMorphClass()
                && (string) $foto->fotavel_id === (string) $id_) {
                $ehDono = true;
                break;
            }
        }

        if (! $ehDono) {
            return response()->json(['erro' => 'Ação não permitida.'], 403);
        }

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        return response()->json(['sucesso' => true]);
    }
}
