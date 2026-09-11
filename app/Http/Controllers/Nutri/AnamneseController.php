<?php

namespace App\Http\Controllers\Nutri;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Nutri\Concerns\ResolveNutri;
use App\Models\Nutri\AnamneseModelo;
use App\Models\Nutri\AnamneseResposta;
use Illuminate\Http\Request;

class AnamneseController extends Controller
{
    use ResolveNutri;

    /** Modelos de anamnese customizáveis do nutricionista. */
    public function modelos()
    {
        $nutri = $this->nutri();
        $modelos = AnamneseModelo::where('personal_id', $nutri->id)->latest()->get();

        // Semeia modelos padrão na primeira visita (editáveis depois).
        if ($modelos->isEmpty()) {
            foreach ($this->modelosSemente() as $m) {
                AnamneseModelo::create(array_merge($m, ['personal_id' => $nutri->id]));
            }
            $modelos = AnamneseModelo::where('personal_id', $nutri->id)->latest()->get();
        }

        return view('nutri.anamnese.modelos', compact('nutri', 'modelos'));
    }

    public function salvarModelo(Request $request)
    {
        $nutri = $this->nutri();
        $dados = $request->validate([
            'id' => 'nullable|integer',
            'nome' => 'required|string|max:255',
            'perfil' => 'required|string|in:'.implode(',', array_keys(AnamneseModelo::PERFIS)),
            'campos' => 'required|array|min:1',
            'campos.*.label' => 'required|string|max:255',
            'campos.*.tipo' => 'required|string|in:'.implode(',', array_keys(AnamneseModelo::TIPOS)),
            'campos.*.secao' => 'nullable|string|max:60',
            'campos.*.ajuda' => 'nullable|string|max:255',
            'campos.*.opcoes' => 'nullable|array',
            'campos.*.opcoes.*' => 'string|max:120',
            'uso_portal' => 'nullable|boolean',
        ]);

        $modelo = $dados['id']
            ? AnamneseModelo::where('id', $dados['id'])->where('personal_id', $nutri->id)->firstOrFail()
            : new AnamneseModelo(['personal_id' => $nutri->id]);

        $usoPortal = $request->boolean('uso_portal');

        $modelo->fill([
            'nome' => $dados['nome'],
            'perfil' => $dados['perfil'],
            'campos' => $dados['campos'],
            'uso_portal' => $usoPortal,
        ])->save();

        // Só um modelo pode ser o do portal: marcar este desmarca os outros,
        // senão o paciente receberia um questionário imprevisível.
        if ($usoPortal) {
            AnamneseModelo::where('personal_id', $nutri->id)
                ->where('id', '!=', $modelo->id)
                ->update(['uso_portal' => false]);
        }

        return back()->with('success', 'Modelo de anamnese salvo.'
            .($usoPortal ? ' Ele passa a ser o questionário que o paciente responde no portal.' : ''));
    }

    public function deletarModelo(int $id)
    {
        $nutri = $this->nutri();
        AnamneseModelo::where('id', $id)->where('personal_id', $nutri->id)->firstOrFail()->delete();

        return back()->with('success', 'Modelo removido.');
    }

    /** Formulário de anamnese de um paciente (escolhe um modelo). */
    public function form(int $pacienteId, Request $request)
    {
        $nutri = $this->nutri();
        $paciente = $this->pacienteDoNutri($pacienteId);
        $modelos = AnamneseModelo::where('personal_id', $nutri->id)->get();

        $modeloId = $request->query('modelo', $modelos->first()->id ?? null);
        $modelo = $modelos->firstWhere('id', (int) $modeloId);

        // Anamnese de retorno: reabre com as respostas da última para o
        // profissional atualizar o que mudou em vez de redigitar tudo. `?limpar=1`
        // força o formulário em branco. A anterior nunca é sobrescrita — salvar
        // cria um registro novo, preservando o histórico.
        $anterior = $request->boolean('limpar')
            ? null
            : $paciente->anamneses()->where('modelo_id', $modelo->id ?? null)->first();

        $respostas = $anterior->respostas ?? [];

        return view('nutri.anamnese.form', compact('nutri', 'paciente', 'modelos', 'modelo', 'anterior', 'respostas'));
    }

    public function salvar(int $pacienteId, Request $request)
    {
        $paciente = $this->pacienteDoNutri($pacienteId);

        $dados = $request->validate([
            'modelo_id' => 'nullable|integer',
            'respostas' => 'required|array',
        ]);

        AnamneseResposta::create([
            'paciente_id' => $paciente->id,
            'modelo_id' => $dados['modelo_id'] ?? null,
            'respostas' => AnamneseResposta::limpar($dados['respostas']),
            'origem' => 'nutri',
            'preenchida_em' => now(),
        ]);

        return redirect()->route('nutri.pacientes.show', $paciente->id)
            ->with('success', 'Anamnese registrada.');
    }

    /**
     * Campos-semente por perfil, agrupados em seções.
     *
     * O tronco comum cobre o que toda consulta nutricional investiga; cada perfil
     * acrescenta as seções da sua especialidade. São modelos de partida: tudo é
     * editável e removível no gerenciador de modelos.
     */
    private function modelosSemente(): array
    {
        return [
            [
                'nome' => 'Anamnese Clínica Completa',
                'perfil' => 'clinica',
                'is_padrao' => true,
                'campos' => array_merge($this->camposComuns(), $this->camposClinicos()),
            ],
            [
                'nome' => 'Anamnese Esportiva Completa',
                'perfil' => 'esportiva',
                'is_padrao' => false,
                'campos' => array_merge($this->camposComuns(), $this->camposEsportivos()),
            ],
            [
                'nome' => 'Anamnese Materno-Infantil',
                'perfil' => 'materno_infantil',
                'is_padrao' => false,
                'campos' => array_merge($this->camposComuns(), $this->camposMaternoInfantil()),
            ],
            [
                // Este é o que vai ao portal: curto o bastante para o paciente
                // responder sozinho no celular antes da consulta.
                'nome' => 'Triagem rápida (primeira consulta)',
                'perfil' => 'geral',
                'is_padrao' => false,
                'uso_portal' => true,
                'campos' => $this->camposTriagem(),
            ],
        ];
    }

    /** Tronco comum a todos os perfis. */
    private function camposComuns(): array
    {
        return [
            // ── Motivo e histórico ──────────────────────────────────────────
            ['secao' => 'Motivo da consulta', 'label' => 'Objetivo principal', 'tipo' => 'textarea', 'ajuda' => 'Nas palavras do paciente.'],
            ['secao' => 'Motivo da consulta', 'label' => 'Já fez acompanhamento nutricional antes?', 'tipo' => 'sim_nao'],
            ['secao' => 'Motivo da consulta', 'label' => 'O que funcionou e o que não funcionou antes', 'tipo' => 'textarea'],
            ['secao' => 'Motivo da consulta', 'label' => 'Expectativa de prazo', 'tipo' => 'texto'],
            ['secao' => 'Motivo da consulta', 'label' => 'Nível de disposição para mudar a rotina', 'tipo' => 'escala', 'ajuda' => '0 = nenhuma, 10 = total.'],

            // ── Histórico de peso ───────────────────────────────────────────
            ['secao' => 'Histórico de peso', 'label' => 'Peso habitual (kg)', 'tipo' => 'numero'],
            ['secao' => 'Histórico de peso', 'label' => 'Maior peso que já teve (kg)', 'tipo' => 'numero'],
            ['secao' => 'Histórico de peso', 'label' => 'Menor peso na vida adulta (kg)', 'tipo' => 'numero'],
            ['secao' => 'Histórico de peso', 'label' => 'Variação de peso nos últimos 6 meses', 'tipo' => 'opcoes', 'opcoes' => ['Estável', 'Ganhou', 'Perdeu', 'Oscilou muito']],
            ['secao' => 'Histórico de peso', 'label' => 'A perda de peso foi intencional?', 'tipo' => 'sim_nao', 'ajuda' => 'Perda não intencional exige investigação clínica.'],
            ['secao' => 'Histórico de peso', 'label' => 'Já fez dietas restritivas? Quais', 'tipo' => 'textarea'],

            // ── História clínica ────────────────────────────────────────────
            ['secao' => 'História clínica', 'label' => 'Condições de saúde', 'tipo' => 'multipla', 'opcoes' => ['Hipertensão', 'Diabetes tipo 1', 'Diabetes tipo 2', 'Pré-diabetes', 'Dislipidemia', 'Hipotireoidismo', 'Hipertireoidismo', 'SOP', 'Gastrite', 'Refluxo', 'Doença celíaca', 'Doença renal', 'Doença hepática', 'Anemia', 'Nenhuma']],
            ['secao' => 'História clínica', 'label' => 'Outras condições / detalhes', 'tipo' => 'textarea'],
            ['secao' => 'História clínica', 'label' => 'Cirurgias realizadas', 'tipo' => 'textarea', 'ajuda' => 'Inclua bariátrica, com data e técnica.'],
            ['secao' => 'História clínica', 'label' => 'Histórico familiar', 'tipo' => 'multipla', 'opcoes' => ['Obesidade', 'Diabetes', 'Hipertensão', 'Câncer', 'Doença cardiovascular', 'Doença tireoidiana', 'Nenhum']],
            ['secao' => 'História clínica', 'label' => 'Fuma?', 'tipo' => 'opcoes', 'opcoes' => ['Não', 'Sim', 'Ex-fumante']],
            ['secao' => 'História clínica', 'label' => 'Consumo de álcool', 'tipo' => 'opcoes', 'opcoes' => ['Não bebe', 'Socialmente', 'Semanalmente', 'Diariamente']],

            // ── Medicamentos e suplementos ──────────────────────────────────
            ['secao' => 'Medicamentos e suplementos', 'label' => 'Medicamentos em uso (nome, dose, horário)', 'tipo' => 'textarea'],
            ['secao' => 'Medicamentos e suplementos', 'label' => 'Suplementos em uso', 'tipo' => 'textarea'],
            ['secao' => 'Medicamentos e suplementos', 'label' => 'Fitoterápicos / chás', 'tipo' => 'textarea'],
            ['secao' => 'Medicamentos e suplementos', 'label' => 'Usa laxante ou diurético?', 'tipo' => 'sim_nao'],

            // ── Alergias e restrições ───────────────────────────────────────
            ['secao' => 'Alergias e restrições', 'label' => 'Alergias alimentares', 'tipo' => 'textarea'],
            ['secao' => 'Alergias e restrições', 'label' => 'Intolerâncias', 'tipo' => 'multipla', 'opcoes' => ['Lactose', 'Glúten', 'Frutose', 'FODMAPs', 'Nenhuma']],
            ['secao' => 'Alergias e restrições', 'label' => 'Restrição por escolha ou religião', 'tipo' => 'opcoes', 'opcoes' => ['Nenhuma', 'Vegetariano', 'Vegano', 'Ovolactovegetariano', 'Halal', 'Kosher', 'Outra']],

            // ── Sinais e sintomas ───────────────────────────────────────────
            ['secao' => 'Sinais e sintomas', 'label' => 'Sintomas gastrointestinais', 'tipo' => 'multipla', 'opcoes' => ['Azia', 'Refluxo', 'Distensão abdominal', 'Gases', 'Náusea', 'Dor abdominal', 'Nenhum']],
            ['secao' => 'Sinais e sintomas', 'label' => 'Frequência evacuatória', 'tipo' => 'opcoes', 'opcoes' => ['3+ por dia', '1–2 por dia', 'Dia sim, dia não', '2–3 por semana', 'Menos de 2 por semana']],
            ['secao' => 'Sinais e sintomas', 'label' => 'Consistência das fezes (escala de Bristol 1–7)', 'tipo' => 'opcoes', 'opcoes' => ['1 — bolinhas duras', '2 — grumosa', '3 — rachaduras', '4 — lisa e macia', '5 — pedaços macios', '6 — pastosa', '7 — líquida'], 'ajuda' => '3 a 5 é o esperado.'],
            ['secao' => 'Sinais e sintomas', 'label' => 'Outros sintomas', 'tipo' => 'multipla', 'opcoes' => ['Fadiga', 'Queda de cabelo', 'Unhas fracas', 'Dor de cabeça', 'Tontura', 'Alteração de humor', 'Câimbras', 'Nenhum']],
            ['secao' => 'Sinais e sintomas', 'label' => 'Nível de energia no dia a dia', 'tipo' => 'escala'],

            // ── Sono e rotina ───────────────────────────────────────────────
            ['secao' => 'Sono e rotina', 'label' => 'Horas de sono por noite', 'tipo' => 'numero'],
            ['secao' => 'Sono e rotina', 'label' => 'Qualidade do sono', 'tipo' => 'opcoes', 'opcoes' => ['Boa', 'Regular', 'Ruim']],
            ['secao' => 'Sono e rotina', 'label' => 'Acorda durante a noite?', 'tipo' => 'sim_nao'],
            ['secao' => 'Sono e rotina', 'label' => 'Horário que acorda e que dorme', 'tipo' => 'texto'],
            ['secao' => 'Sono e rotina', 'label' => 'Nível de estresse percebido', 'tipo' => 'escala'],
            ['secao' => 'Sono e rotina', 'label' => 'Ocupação e carga horária', 'tipo' => 'texto'],
            ['secao' => 'Sono e rotina', 'label' => 'Quem cozinha em casa', 'tipo' => 'opcoes', 'opcoes' => ['O próprio paciente', 'Outra pessoa da casa', 'Marmita / delivery', 'Varia']],

            // ── Atividade física ────────────────────────────────────────────
            ['secao' => 'Atividade física', 'label' => 'Pratica atividade física?', 'tipo' => 'sim_nao'],
            ['secao' => 'Atividade física', 'label' => 'Modalidades', 'tipo' => 'textarea'],
            ['secao' => 'Atividade física', 'label' => 'Frequência (dias por semana)', 'tipo' => 'numero'],
            ['secao' => 'Atividade física', 'label' => 'Duração média por sessão (min)', 'tipo' => 'numero'],
            ['secao' => 'Atividade física', 'label' => 'Horário do treino', 'tipo' => 'texto'],
            ['secao' => 'Atividade física', 'label' => 'Nível de atividade fora do treino', 'tipo' => 'opcoes', 'opcoes' => ['Sedentário', 'Levemente ativo', 'Moderadamente ativo', 'Muito ativo']],

            // ── Hidratação e consumo ────────────────────────────────────────
            ['secao' => 'Hidratação e consumo', 'label' => 'Consumo de água (litros/dia)', 'tipo' => 'numero'],
            ['secao' => 'Hidratação e consumo', 'label' => 'Café (xícaras/dia)', 'tipo' => 'numero'],
            ['secao' => 'Hidratação e consumo', 'label' => 'Refrigerante / suco industrializado', 'tipo' => 'opcoes', 'opcoes' => ['Não consome', 'Raramente', 'Semanalmente', 'Diariamente']],
            ['secao' => 'Hidratação e consumo', 'label' => 'Adoçante', 'tipo' => 'sim_nao'],

            // ── Comportamento alimentar ─────────────────────────────────────
            ['secao' => 'Comportamento alimentar', 'label' => 'Número de refeições por dia', 'tipo' => 'numero'],
            ['secao' => 'Comportamento alimentar', 'label' => 'Pula alguma refeição? Qual', 'tipo' => 'texto'],
            ['secao' => 'Comportamento alimentar', 'label' => 'Velocidade ao comer', 'tipo' => 'opcoes', 'opcoes' => ['Devagar', 'Normal', 'Rápido']],
            ['secao' => 'Comportamento alimentar', 'label' => 'Come com distração (TV, celular)?', 'tipo' => 'sim_nao'],
            ['secao' => 'Comportamento alimentar', 'label' => 'Episódios de compulsão', 'tipo' => 'opcoes', 'opcoes' => ['Nunca', 'Raramente', 'Semanalmente', 'Quase diariamente']],
            ['secao' => 'Comportamento alimentar', 'label' => 'Beliscar fora das refeições', 'tipo' => 'opcoes', 'opcoes' => ['Nunca', 'Às vezes', 'Frequentemente']],
            ['secao' => 'Comportamento alimentar', 'label' => 'Gatilhos para comer fora do plano', 'tipo' => 'multipla', 'opcoes' => ['Ansiedade', 'Tédio', 'Estresse', 'Eventos sociais', 'Fim de semana', 'Madrugada', 'Nenhum']],
            ['secao' => 'Comportamento alimentar', 'label' => 'Fome ao acordar', 'tipo' => 'sim_nao'],

            // ── Rotina alimentar ────────────────────────────────────────────
            ['secao' => 'Rotina alimentar', 'label' => 'Recordatório de 24 horas', 'tipo' => 'textarea', 'ajuda' => 'Tudo que comeu e bebeu ontem, com horários.'],
            ['secao' => 'Rotina alimentar', 'label' => 'Refeições feitas fora de casa (por semana)', 'tipo' => 'numero'],
            ['secao' => 'Rotina alimentar', 'label' => 'Alimentos que não abre mão', 'tipo' => 'textarea'],
            ['secao' => 'Rotina alimentar', 'label' => 'Alimentos que não gosta ou não come', 'tipo' => 'textarea'],
            ['secao' => 'Rotina alimentar', 'label' => 'Frequência de ultraprocessados', 'tipo' => 'opcoes', 'opcoes' => ['Raramente', '1–2x por semana', '3–5x por semana', 'Diariamente']],
            ['secao' => 'Rotina alimentar', 'label' => 'Orçamento mensal para alimentação (R$)', 'tipo' => 'numero'],
            ['secao' => 'Rotina alimentar', 'label' => 'Tem tempo para cozinhar?', 'tipo' => 'opcoes', 'opcoes' => ['Sim, todos os dias', 'Só no fim de semana', 'Quase nunca']],
        ];
    }

    /** Específicos do perfil clínico. */
    private function camposClinicos(): array
    {
        return [
            ['secao' => 'Exames laboratoriais', 'label' => 'Data do último exame', 'tipo' => 'data'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Glicemia de jejum (mg/dL)', 'tipo' => 'numero'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Hemoglobina glicada (%)', 'tipo' => 'numero'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Colesterol total / HDL / LDL / triglicerídeos', 'tipo' => 'texto'],
            ['secao' => 'Exames laboratoriais', 'label' => 'TSH e T4 livre', 'tipo' => 'texto'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Vitamina D (ng/mL)', 'tipo' => 'numero'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Ferritina / hemoglobina', 'tipo' => 'texto'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Vitamina B12', 'tipo' => 'texto'],
            ['secao' => 'Exames laboratoriais', 'label' => 'Outros marcadores alterados', 'tipo' => 'textarea'],
            ['secao' => 'Conduta', 'label' => 'Diagnóstico nutricional', 'tipo' => 'textarea'],
            ['secao' => 'Conduta', 'label' => 'Conduta e orientações desta consulta', 'tipo' => 'textarea'],
            ['secao' => 'Conduta', 'label' => 'Encaminhamentos', 'tipo' => 'textarea', 'ajuda' => 'Outro profissional, exames a solicitar.'],
            ['secao' => 'Conduta', 'label' => 'Data do retorno', 'tipo' => 'data'],
        ];
    }

    /** Específicos do perfil esportivo. */
    private function camposEsportivos(): array
    {
        return [
            ['secao' => 'Treino', 'label' => 'Modalidade principal', 'tipo' => 'texto'],
            ['secao' => 'Treino', 'label' => 'Tempo de prática', 'tipo' => 'texto'],
            ['secao' => 'Treino', 'label' => 'Nível', 'tipo' => 'opcoes', 'opcoes' => ['Iniciante', 'Intermediário', 'Avançado', 'Atleta competitivo']],
            ['secao' => 'Treino', 'label' => 'Fase da periodização', 'tipo' => 'opcoes', 'opcoes' => ['Base', 'Específico', 'Pré-competitivo', 'Competição', 'Transição / off']],
            ['secao' => 'Treino', 'label' => 'Próxima competição', 'tipo' => 'data'],
            ['secao' => 'Treino', 'label' => 'Há exigência de categoria de peso?', 'tipo' => 'sim_nao'],
            ['secao' => 'Treino', 'label' => 'Volume semanal (horas)', 'tipo' => 'numero'],
            ['secao' => 'Treino', 'label' => 'Percepção de recuperação entre treinos', 'tipo' => 'escala'],
            ['secao' => 'Nutrição no treino', 'label' => 'O que come antes do treino', 'tipo' => 'textarea'],
            ['secao' => 'Nutrição no treino', 'label' => 'O que consome durante o treino', 'tipo' => 'textarea'],
            ['secao' => 'Nutrição no treino', 'label' => 'O que come depois do treino', 'tipo' => 'textarea'],
            ['secao' => 'Nutrição no treino', 'label' => 'Hidratação durante o treino (ml)', 'tipo' => 'numero'],
            ['secao' => 'Nutrição no treino', 'label' => 'Sintomas gastrointestinais no treino', 'tipo' => 'textarea'],
            ['secao' => 'Nutrição no treino', 'label' => 'Recursos ergogênicos em uso', 'tipo' => 'multipla', 'opcoes' => ['Creatina', 'Cafeína', 'Beta-alanina', 'Bicarbonato', 'Nitrato / beterraba', 'Whey', 'Maltodextrina', 'Nenhum']],
            ['secao' => 'Nutrição no treino', 'label' => 'Já teve lesão relacionada a treino?', 'tipo' => 'textarea'],
        ];
    }

    /** Específicos do perfil materno-infantil. */
    private function camposMaternoInfantil(): array
    {
        return [
            ['secao' => 'Gestação', 'label' => 'Situação atual', 'tipo' => 'opcoes', 'opcoes' => ['Gestante', 'Puérpera / lactante', 'Tentante', 'Acompanhamento infantil']],
            ['secao' => 'Gestação', 'label' => 'Data da última menstruação (DUM)', 'tipo' => 'data'],
            ['secao' => 'Gestação', 'label' => 'Idade gestacional (semanas)', 'tipo' => 'numero'],
            ['secao' => 'Gestação', 'label' => 'Peso pré-gestacional (kg)', 'tipo' => 'numero'],
            ['secao' => 'Gestação', 'label' => 'Gestações anteriores / partos', 'tipo' => 'texto'],
            ['secao' => 'Gestação', 'label' => 'Intercorrências', 'tipo' => 'multipla', 'opcoes' => ['Diabetes gestacional', 'Hipertensão / pré-eclâmpsia', 'Anemia', 'Náusea intensa', 'Nenhuma']],
            ['secao' => 'Gestação', 'label' => 'Suplementação prescrita no pré-natal', 'tipo' => 'textarea'],
            ['secao' => 'Amamentação', 'label' => 'Está amamentando?', 'tipo' => 'sim_nao'],
            ['secao' => 'Amamentação', 'label' => 'Tipo de aleitamento', 'tipo' => 'opcoes', 'opcoes' => ['Exclusivo', 'Misto', 'Fórmula']],
            ['secao' => 'Amamentação', 'label' => 'Dificuldades na amamentação', 'tipo' => 'textarea'],
            ['secao' => 'Criança', 'label' => 'Data de nascimento', 'tipo' => 'data'],
            ['secao' => 'Criança', 'label' => 'Peso e comprimento ao nascer', 'tipo' => 'texto'],
            ['secao' => 'Criança', 'label' => 'Idade de introdução alimentar (meses)', 'tipo' => 'numero'],
            ['secao' => 'Criança', 'label' => 'Aceitação dos alimentos', 'tipo' => 'opcoes', 'opcoes' => ['Boa', 'Seletiva', 'Recusa frequente']],
            ['secao' => 'Criança', 'label' => 'Consumo de ultraprocessados e açúcar', 'tipo' => 'textarea'],
            ['secao' => 'Criança', 'label' => 'Marcos de crescimento / caderneta', 'tipo' => 'textarea'],
        ];
    }

    /** Modelo enxuto, para primeira consulta ou triagem em grupo. */
    private function camposTriagem(): array
    {
        return [
            ['secao' => 'Triagem', 'label' => 'Objetivo principal', 'tipo' => 'textarea'],
            ['secao' => 'Triagem', 'label' => 'Condições de saúde relevantes', 'tipo' => 'textarea'],
            ['secao' => 'Triagem', 'label' => 'Medicamentos em uso', 'tipo' => 'textarea'],
            ['secao' => 'Triagem', 'label' => 'Alergias e intolerâncias', 'tipo' => 'textarea'],
            ['secao' => 'Triagem', 'label' => 'Número de refeições por dia', 'tipo' => 'numero'],
            ['secao' => 'Triagem', 'label' => 'Consumo de água (litros/dia)', 'tipo' => 'numero'],
            ['secao' => 'Triagem', 'label' => 'Pratica atividade física?', 'tipo' => 'sim_nao'],
            ['secao' => 'Triagem', 'label' => 'Horas de sono por noite', 'tipo' => 'numero'],
            ['secao' => 'Triagem', 'label' => 'Recordatório de 24 horas', 'tipo' => 'textarea'],
        ];
    }
}
