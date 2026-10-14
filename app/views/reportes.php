<?php
session_start();

$titulo = 'Reportes';
include '../dependencies/header.php';
?>
<style>
  .card-hover {
    transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.6);
    /* fondo base translúcido */
    backdrop-filter: blur(6px);
    /* efecto blur */
    border-radius: 12px;
  }

  .card-hover:hover {
    transform: translateY(-6px) scale(1.02);
    /* pequeño zoom */
    background: rgba(13, 110, 253, 0.1);
    /* azul translúcido */
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
    border-color: #0d6efd;
  }
</style>
<div class="card" style="height: auto;">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes /</span> Reportes por grupos</h5>
    <div class="row" id="card-container"></div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalReporte" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content" style="height:100vh;">
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary" id="title-rpt"> </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Contenido principal -->
      <div class="modal-body p-0">
        <iframe src="" style="width:100%; height:100%; border:none;" id="iframe-report"></iframe>
      </div>
    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  '../controllers/reportes.js?' . rand()
];

include '../dependencies/footer.php';

?>