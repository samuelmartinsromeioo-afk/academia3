<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Models\Cadastro\Personal;
use App\Models\Cadastro\Academia as Academia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoController extends Controller
{
    // Upload de foto para personal (responde JSON para AJAX)
    public function storePersonal(Request $request)
    {
        $request->validate([
            'foto'    => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
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

    // Delete de foto — responde JSON para AJAX
    public function destroy($id)
    {
        $foto = Foto::findOrFail($id);

        $personalId = session('personal_id');
        $academiaId = session('academia_id');

        $ehDono = ($personalId && $foto->fotavel_type === 'App\\Models\\cadastro\\Personal' && $foto->fotavel_id == $personalId)
               || ($academiaId && $foto->fotavel_type === 'App\\Models\\cadastro\\academia' && $foto->fotavel_id == $academiaId);

        if (!$ehDono) {
            return response()->json(['erro' => 'Ação não permitida.'], 403);
        }

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        return response()->json(['sucesso' => true]);
    }
}
