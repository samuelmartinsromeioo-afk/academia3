<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Foto;
use App\Models\cadastro\Personal;
use App\Models\cadastro\academia as Academia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FotoController extends Controller
{
    // Upload de foto para personal
    public function storePersonal(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'legenda' => 'nullable|string|max:255',
        ]);

        $personal = Personal::findOrFail(session('personal_id'));

        if ($personal->fotos()->count() >= 5) {
            return redirect()->back()->with('error', 'Limite de 5 fotos atingido. Remova uma antes de adicionar outra.');
        }

        $path = $request->file('foto')->store('galeria/personals', 'public');

        $personal->fotos()->create([
            'path'    => $path,
            'legenda' => $request->legenda,
        ]);

        return redirect()->back()->with('success', 'Foto adicionada com sucesso!');
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

    // Delete de foto (serve para personal e academia)
    public function destroy($id)
    {
        $foto = Foto::findOrFail($id);

        // Garante que só o dono pode deletar
        $personalId = session('personal_id');
        $academiaId = session('academia_id');

        $ehDono = ($personalId && $foto->fotavel_type === 'App\\Models\\cadastro\\Personal' && $foto->fotavel_id == $personalId)
               || ($academiaId && $foto->fotavel_type === 'App\\Models\\cadastro\\academia' && $foto->fotavel_id == $academiaId);

        if (!$ehDono) {
            return redirect()->back()->with('error', 'Ação não permitida.');
        }

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        return redirect()->back()->with('success', 'Foto removida com sucesso!');
    }
}
