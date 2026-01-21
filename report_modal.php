<?php
// report_modal.php
?>
<!-- Modal de Agradecimento (successModal) -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:15px;">
      <div class="modal-header">
        <h5 class="modal-title">Obrigado!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>Relatório enviado com sucesso. Obrigado por informar!</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Reporte de Instabilidade (incidentModal) -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:15px;">
      <div class="modal-header">
        <h5 class="modal-title">Ajude-nos a melhorar: reporte uma instabilidade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <form method="POST" action="index.php">
        <div class="modal-body">
          <div class="container-fluid">
            <div class="row g-3">
              <!-- Linha 1: Seu Nome e Local -->
              <div class="col-12 col-md-6">
                <label for="reported_by" class="form-label">Seu Nome</label>
                <input type="text" name="reported_by" id="reported_by" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="location" class="form-label">Local</label>
                <input type="text" name="location" id="location" class="form-control">
              </div>
              <!-- Linha 2: Tipo de Problema e Nível de Gravidade -->
              <div class="col-12 col-md-6">
                <label for="type_of_issue" class="form-label">Tipo de Problema</label>
                <input type="text" name="type_of_issue" id="type_of_issue" class="form-control" placeholder="Ex.: falha no sistema, instabilidade">
              </div>
              <div class="col-12 col-md-6">
                <label for="severity_level" class="form-label">Nível de Gravidade</label>
                <select name="severity_level" id="severity_level" class="form-select">
                  <option value="Baixo">Baixo</option>
                  <option value="Médio">Médio</option>
                  <option value="Alto">Alto</option>
                </select>
              </div>
              <!-- Linha 3: Data e Hora da Ocorrência -->
              <div class="col-12">
                <label for="occurrence_datetime" class="form-label">Data e Hora da Ocorrência</label>
                <input type="datetime-local" name="occurrence_datetime" id="occurrence_datetime" class="form-control">
                <small class="text-muted">Informe a data/hora em que ocorreu o problema (se diferente do horário atual)</small>
              </div>
              <!-- Linha 4: Descrição -->
              <div class="col-12">
                <label for="description" class="form-label">Descrição</label>
                <textarea name="description" id="description" rows="3" class="form-control" placeholder="Descreva o problema"></textarea>
              </div>
              <!-- Linha 5: Informações Adicionais -->
              <div class="col-12">
                <label for="additional_info" class="form-label">Informações Adicionais</label>
                <textarea name="additional_info" id="additional_info" rows="2" class="form-control" placeholder="Outras informações que possam ajudar"></textarea>
              </div>
              <!-- Linha 6: Aviso -->
              <div class="col-12">
                <div class="alert alert-info mt-3">
                  <strong>Aviso:</strong> Se você identificar instabilidades, por favor, reporte para que possamos melhorar o sistema!
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="incident_submit" class="btn btn-success">Enviar Relatório</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Script para exibir o modal de agradecimento uma vez e remover backdrop -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Ajuste se quiser exibir automaticamente
  // com a verificação do index
});
</script>
