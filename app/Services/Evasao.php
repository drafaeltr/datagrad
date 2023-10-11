<?php
namespace App\Services;

use App\Replicado\Graduacao;

class Evasao
{
    /**
     * Retorna taxa de permanência, desistência e conclusão para determinado ano de ingresso
     *
     * Cuidado: o ano corrente pode estar incompleto.
     *
     * tipencpgm is NULL, dtaini qualquer -> ainda está matriculado
     * tipencpgm = 'Conclusão', dtaini: ano do evento  -> formou
     * outros -> evadiu, dtaini: ano do evento -> evasão
     */
    public static function taxaEvasao(int $anoIngresso, int $codcur = null)
    {
        $alunos = Graduacao::listarAlunosIngressantesPorAnoIngresso($anoIngresso, $codcur);

        for ($ano = $anoIngresso; $ano <= date('Y'); $ano++) {
            $contagem[$ano]['countP'] = 0;
            $contagem[$ano]['countD'] = 0;
            $contagem[$ano]['countC'] = 0;
        }

        foreach ($alunos as $aluno) {
            switch ($aluno['tipencpgm']) {
                case '':
                    // ainda está matriculado
                    break;
                case 'Conclusão':
                    $contagem[$aluno['ano']]['countC']++;
                    break;
                default:
                    $contagem[$aluno['ano']]['countD']++;
            }
        }

        // calculando taxa de evasão por ano
        for ($ano = $anoIngresso; $ano <= date('Y'); $ano++) {
            if (isset($evasao[$ano - 1])) {
                $evasao[$ano]['permanencia'] = $evasao[$ano - 1]['permanencia'] - $contagem[$ano]['countD'] - $contagem[$ano]['countC'];
                $evasao[$ano]['desistenciaAcc'] = $evasao[$ano - 1]['desistenciaAcc'] + $contagem[$ano]['countD'];
                $evasao[$ano]['conclusaoAcc'] = $evasao[$ano - 1]['conclusaoAcc'] + $contagem[$ano]['countC'];
            } else {
                $evasao[$ano]['permanencia'] = count($alunos) - $contagem[$ano]['countD'] - $contagem[$ano]['countC'];
                $evasao[$ano]['desistenciaAcc'] = $contagem[$ano]['countD'];
                $evasao[$ano]['conclusaoAcc'] = $contagem[$ano]['countC'];
            }

            $evasao[$ano]['txPermanencia'] = round($evasao[$ano]['permanencia'] / count($alunos) * 100, 2);
            $evasao[$ano]['txDesistenciaAcc'] = round($evasao[$ano]['desistenciaAcc'] / count($alunos) * 100, 2);
            $evasao[$ano]['txConclusaoAcc'] = round($evasao[$ano]['conclusaoAcc'] / count($alunos) * 100, 2);

            // se não há mais permanência, vamos parar de computar os anos
            if ($evasao[$ano]['permanencia'] == 0) {
                break;
            }
        }

        return $evasao;
    }

}