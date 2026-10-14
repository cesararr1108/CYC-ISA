<?php
session_start();

$titulo = 'Kardex';
include '../dependencies/header.php';
?>

<style>
  #kardex tbody tr td {
    font-size: 0.7rem;
    padding: 2px;
  }

  .badge-kardex {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .72rem;
    font-weight: 500;
    padding: .35rem .6rem;
    border-radius: .45rem;
  }

  .badge-kardex .badge {
    font-size: .65rem;
    font-weight: 600;
    padding: .18rem .45rem;
    line-height: 1;
  }
</style>

<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Reportes / Reportes de Inventarios / </span> Kardex</h5>
    <div class="nav-align-top ">
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-busqueda" role="tabpanel">
          <div class="row g-2 align-items-end">
            <!-- Producto -->
            <div class="col-lg-5 col-md-12">
              <label class="form-label mb-1">Producto</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">
                  <i class="bx bx-search"></i>
                </span>
                <input type="text"
                  class="form-control busqueda-referencias"
                  id="busqueda-descripcion"
                  placeholder="Código, descripción, descripción larga"
                  autocomplete="off">

                <button class="btn btn-primary"
                  type="button"
                  id="btnBusquedaAvanzada">
                  <i class="fa-solid fa-sliders"></i>
                </button>

                <input type="hidden" id="id_referencia">
              </div>
            </div>

            <!-- Fecha inicial -->
            <div class="col-lg-2 col-md-4">
              <label class="form-label mb-1">Fecha inicial</label>
              <input type="date"
                id="fecha_inicial"
                class="form-control form-control-sm">
            </div>

            <!-- Fecha final -->
            <div class="col-lg-2 col-md-4">
              <label class="form-label mb-1">Fecha final</label>
              <input type="date"
                id="fecha_final"
                class="form-control form-control-sm">
            </div>

            <!-- Bodega -->
            <div class="col-lg-2 col-md-3">
              <label class="form-label mb-1">Bodega</label>
              <select id="bodegas"
                class="form-select form-select-sm">
                <option value="">Todas</option>
              </select>
            </div>

            <!-- Botones -->
            <div class="col-lg-1 col-md-12">
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-primary flex-fill" id="btn-busqueda" title="Consultar">
                  <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <button class="btn btn-sm btn-outline-secondary flex-fill" id="btn-limpiar" title="Limpiar filtros">
                  <i class="fa-solid fa-eraser"></i>
                </button>
              </div>
            </div>

          </div>
          <div class="row mt-3">
            <div class="col">
              <div class="card border-0 shadow-sm">
                <div class="card-body py-2 px-3">
                  <div class="d-flex align-items-center flex-wrap gap-2">

                    <span class="fw-semibold text-secondary me-2">
                      <i class="fa-solid fa-circle-info"></i>
                      Convenciones:
                    </span>

                    <span id="badge-compras" class="badge badge-kardex bg-primary">
                      <i class="fa-solid fa-cart-shopping"></i>
                      Compras
                      <span id="badge-compras-count" class="badge  text-dark rounded-pill">0</span>
                    </span>

                    <span id="badge-entradas" class="badge badge-kardex bg-secondary">
                      <i class="fa-solid fa-arrow-down"></i>
                      Entradas
                      <span id="badge-entradas-count" class="badge  text-dark rounded-pill">0</span>
                    </span>

                    <span id="badge-dev-ventas" class="badge badge-kardex bg-danger">
                      <i class="fa-solid fa-rotate-left"></i>
                      Dev. Ventas
                      <span id="badge-dev-ventas-count" class="badge text-dark rounded-pill">0</span>
                    </span>

                    <span id="badge-ventas" class="badge badge-kardex bg-warning text-dark">
                      <i class="fa-solid fa-cart-arrow-down"></i>
                      Ventas
                      <span id="badge-ventas-count" class="badge text-dark rounded-pill">0</span>
                    </span>

                    <span id="badge-salidas" class="badge badge-kardex bg-info text-dark">
                      <i class="fa-solid fa-arrow-up"></i>
                      Salidas
                      <span id="badge-salidas-count" class="badge text-dark rounded-pill">0</span>
                    </span>

                    <span id="badge-dev-compras" class="badge badge-kardex bg-dark text-primary border border-primary">
                      <i class="fa-solid fa-rotate-right"></i>
                      Dev. Compras
                      <span id="badge-dev-compras-count" class="badge text-primary rounded-pill">0</span>
                    </span>

                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- Resultado -->
          <div id="resultado-inventario" class="mt-3"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="modal-container"></div>


<?php

$scriptsPagina = [
  '../controllers/RptKardex.js?' . rand()
];

include '../dependencies/footer.php';

?>