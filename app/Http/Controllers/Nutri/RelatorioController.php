<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\Paciente;
use App\Models\Nutri\PlanoAlimentar;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    use ResolveNutri;

    /** Relatório de evolução do paciente (versão para impressão / PDF). */
    public function paciente(int $id)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($id);
        $paciente->load(['antropometrias', 'anamneses.modelo', 'consultas']);
        $planoAtivo = $paciente->planoAtivo();

        return view('nutri.relatorios.paciente', compact('nutri', 'paciente', 'planoAtivo'));
    }

    /** Portabilidade (anti-lock-in): exporta todos os pacientes em CSV. */
    public function exportarPacientes(): StreamedResponse
    {
        $nutri = $this->nutri();
        $pacientes = Paciente::where('personal_id', $nutri->id)->orderBy('nome')->get();

        return $this->csv('pacientes.csv',
            ['ID', 'Nome', 'E-mail', 'WhatsApp', 'Nascimento', 'Sexo', 'Objetivo', 'Altura(cm)', 'Ativo'],
            $pacientes->map(fn ($p) => [
                $p->id, $p->nome, $p->email, $p->whatsapp,
                optional($p->data_nascimento)->format('Y-m-d'), $p->sexo,
                $p->objetivo, $p->altura_cm, $p->ativo ? 'sim' : 'não',
            ])
        );
    }

    /** Exporta os planos (com refeições/itens) em CSV para migração. */
    public function exportarPlanos(): StreamedResponse
    {
        $nutri = $this->nutri();
        $planos = PlanoAlimentar::where('personal_id', $nutri->id)
            ->with('refeicoes.itens', 'paciente')->get();

        $linhas = collect();
        foreach ($planos as $plano) {
            foreach ($plano->refeicoes as $ref) {
                foreach ($ref->itens as $item) {
                    $linhas->push([
                        $plano->id, $plano->nome, $plano->paciente->nome ?? '(modelo)',
                        $ref->nome, $ref->horario, $item->descricao, $item->quantidade_g,
                        $item->kcal, $item->carbo_g, $item->proteina_g, $item->gordura_g,
                    ]);
                }
            }
        }

        return $this->csv('planos.csv',
            ['Plano ID', 'Plano', 'Paciente', 'Refeição', 'Horário', 'Alimento', 'Qtd(g)', 'Kcal', 'Carbo(g)', 'Proteína(g)', 'Gordura(g)'],
            $linhas
        );
    }

    private function csv(string $nome, array $cabecalho, $linhas): StreamedResponse
    {
        return response()->streamDownload(function () use ($cabecalho, $linhas) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM p/ acentos no Excel
            fputcsv($out, $cabecalho, ';');
            foreach ($linhas as $linha) {
                fputcsv($out, $linha, ';');
            }
            fclose($out);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
