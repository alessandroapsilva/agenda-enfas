# SIGH FAR — Farmácia Hospitalar

O SIGH FAR conecta prescrição, estoque, dispensação, enfermagem e custos.

## Fluxo

1. Prescrição é liberada.
2. Itens entram na fila da farmácia.
3. Farmacêutico valida.
4. Sistema seleciona lotes por FEFO.
5. Dispensação baixa lote e vincula paciente, prescrição, usuário e horário.
6. Enfermagem recebe/checa quando aplicável.
7. Devoluções geram movimento compensatório.
8. Consumo alimenta custos e indicadores.

## Recursos

- farmácia central e satélites;
- cadastro e padronização de medicamentos;
- lote, validade e custo;
- FEFO;
- estoque mínimo/máximo;
- entrada, saída, transferência e devolução;
- requisição por setor;
- dispensação individual/coletiva;
- dose unitária;
- kits/carrinhos de emergência;
- inventário;
- perdas, quebras e vencimentos;
- curva ABC/XYZ;
- consumo médio mensal;
- alertas de ruptura e validade;
- auditoria de todas as movimentações.

## Regras críticas

Nunca permitir saldo negativo; bloquear lote vencido; nunca apagar movimento; ajustes precisam de motivo; transações de estoque usam bloqueio de linha; cancelamentos geram estorno; prescrições alteradas precisam de reconciliação.
