<?php
session_start();

$titulo = 'Centros';
$cssPagina = [
  'https://unpkg.com/dropzone@5/dist/min/dropzone.min.css'
];
include '../dependencies/header.php';
?>
<style>
  .swal2-container {
    z-index: 20000 !important;
    font-size: 1.5rem !important;
  }

  /* Estilo para el área de Dropzone */
  .dropzone {
    border: 2px dashed #007bff;
    padding: 20px;
    border-radius: 10px;
    background-color: #f8f9fa;
    text-align: center;
    font-family: Arial, sans-serif;
  }

  /* Mensaje predeterminado */
  .dropzone .dz-default {
    color: #6c757d;
    font-size: 18px;
    font-weight: bold;
  }

  /* Mensajes de error */
  .dropzone .dz-error-message {
    color: #dc3545;
    /* Rojo para los mensajes de error */
    font-size: 14px;
    font-weight: bold;
    margin-top: 10px;
  }

  /* Estilo para el botón de "Eliminar archivo" */
  .dropzone .dz-remove {
    color: #007bff;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 5px;
  }

  /* Estilo para el botón de "Cancelar carga" */
  .dropzone .dz-cancel {
    color: #007bff;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 5px;
  }

  /* Estilo para el archivo en progreso */
  .dropzone .dz-progress {
    background-color: #007bff;
    height: 5px;
  }

  /* Estilo para el área de carga cuando no se está arrastrando archivos */
  .dropzone.dz-drag-hover {
    border-color: #28a745;
    /* Verde cuando el usuario está arrastrando archivos */
    background-color: #e9f7ee;
  }

  /* Estilo para el texto cuando un archivo es demasiado grande */
  .dropzone .dz-error-message {
    font-size: 16px;
    font-weight: normal;
    color: #dc3545;
  }

  .dropzone .dz-preview .dz-image img {
    width: 100px;
    height: 100px;
  }
</style>
<div class="card">
  <div class="card-body">
    <h4 class="fw-bold py-3 mb-2"><span class="text-muted fw-light">Maestra /</span> Empresas</h4>
    <div class="nav-align-top ">
      <ul class="nav nav-tabs" role="tablist" id="tabs">
        <li class="nav-item">
          <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación /Modificación </button>
        </li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
          <form id="formEmpresas" class="needs-validation form-basico" novalidate>
            <input type="hidden" class="form-control form-control-sm text-uppercase" name="id" id="id" autocomplete="off" readonly required>
            <div class="row">
              <div class="col-md-8">
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label">Generales</h5>
                  <div class="card-body">
                    <label for="nombres" class="form-label campo-obligatorio">Representante legal</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="representante_legal" id="representante_legal" autocomplete="off" required placeholder="Representante legal">
                    <label for="txtRazon" class="form-label campo-obligatorio">Razón Comercial</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" name="razon_comercial" id="razon_comercial" autocomplete="off" placeholder="Razón social/comercial" required>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-4" id="btnGuardar"> <i class="fa-regular fa-floppy-disk"></i> Guardar/Actualizar</button>
                  </div>
                </div>
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label">Perfil tributario</h5>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-5">
                        <label for="id_tipo_identificacion campo-obligatorio" class="form-label">Tipo identificación</label>
                        <select id="id_tipo_identificacion" name="id_tipo_identificacion" class="form-select form-select-sm" required>
                        </select>
                      </div>
                      <div class="col-md-5">
                        <label for="nit" class="form-label campo-obligatorio">NIT</label>
                        <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" required>
                      </div>
                      <div class="col-md-2">
                        <label for="nit_digito" class="form-label">DV</label>
                        <input type="text" class="form-control form-control-sm" name="nit_digito" value="0" readonly id="nit_digito" maxlength="2" size="3" placeholder="Dígito" autocomplete="off" required>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-4">
                        <label for="id_perfil_tributario" class="form-label"> Perfil tributario </label>
                        <div class="input-group input-group-sm">
                          <select id="id_perfil_tributario" name="id_perfil_tributario" class="form-select">
                            <option value="">Seleccione...</option>
                            <option value="1">Perfil General</option>
                          </select>
                          <button class="btn btn-outline-secondary" type="button" id="btnPerfilModal"> + </button>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <label for="id_regimen" class="form-label">Régimen</label>
                        <select id="id_regimen" name="id_regimen" class="form-select form-select-sm">
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label for="email_fe" class="form-label campo-obligatorio">Email FE</label>
                        <input type="email" class="form-control form-control-sm" name="correo_fe" id="correo_fe" placeholder="Email factura electrónica" autocomplete="off" required>
                      </div>
                    </div>
                    <small class="text-light fw-semibold d-block"></small>
                    <div class="form-check form-check-inline mt-3">
                      <input class="form-check-input" type="checkbox" id="autorretenedor" name="autorretenedor" />
                      <label class="form-check-label" for="autorretenedor">Autorretenedor</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="checkbox" id="agente_retenedor" name="agente_retenedor" />
                      <label class="form-check-label" for="agente_retenedor">Agente retenedor</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="checkbox" id="gran_contribuyente" name="gran_contribuyente" />
                      <label class="form-check-label" for="gran_contribuyente">Gran contribuyente</label>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="card mb-4">
                      <h5 class="card-header text-primary form-label">Integración EDI</h5>
                      <div class="card-body">
                        <label for="sftp_edi" class="form-label">SFTP</label>
                        <input type="text" class="form-control form-control-sm" name="sftp_edi" id="sftp_edi" autocomplete="off" placeholder="SFTP Server">
                        <label for="usuario_edi" class="form-label">Usuario</label>
                        <input type="text" class="form-control form-control-sm" name="usuario_edi" id="usuario_edi" autocomplete="off" placeholder="Usuario">
                        <label for="pass_edi" class="form-label">Contraseña</label>
                        <input type="text" class="form-control form-control-sm" name="pass_edi" id="pass_edi" autocomplete="off" placeholder="Contraseña">
                        <label for="sftp_edi" class="form-label">EAN Empresa</label>
                        <input type="text" class="form-control form-control-sm" name="ean_edi" id="ean_edi" autocomplete="off" placeholder="EAN Empresa">
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="card mb-4">
                      <h5 class="card-header text-primary form-label">Nómina</h5>
                      <div class="card-body">
                        <label for="id_frecuencia_pago" class="form-label">Frecuencia de pago</label>
                        <select id="id_frecuencia_pago" name="id_frecuencia_pago" class="form-select form-select-sm">
                          <option value="">Seleccione...</option>
                          <option value="1">Quincenal</option>
                          <option value="2">Mensual</option>
                        </select>
                        <label for="id_arl" class="form-label">ARL</label>
                        <select id="id_arl" name="id_arl" class="form-select form-select-sm">
                        </select>
                        <label for="id_metodo_pago" class="form-label">Metodo de pago</label>
                        <select class="form-select form-select-sm" id="id_metodo_pago" name="id_metodo_pago">
                          <option value="">Seleccione...</option>
                          <option value="10">10 - Efectivo</option>
                          <option value="30">30 - Transferencia Débito</option>
                          <option value="31">31 - Transferencia Crédito</option>
                          <option value="42">42 - Consignación bancaria</option>
                          <option value="45">45 - Transferencia Crédito Bancario</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card mb-4">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title text-primary form-label">Logo</h5>
                    <h6 class="card-subtitle text-muted">Se aplica a todos los documentos</h6>
                  </div>
                  <div class="card-body">
                    <div class="form-group">
                      <div class="dropzone" id="myDropzone">
                        <div class="dz-default dz-message">
                          <button class="dz-button" type="button"> <i class="fas fa-cloud-upload-alt fa-3x" style="color: #B197FC;"></i><br>
                            <span>Arrastra tus archivos aquí o haz clic para cargar</span> </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card mb-4">
                  <h5 class="card-header text-primary form-label">Dirección</h5>
                  <div class="card-body">
                    <label for="codigo_pais" class="form-label campo-obligatorio">Pais</label>
                    <select id="codigo_pais" name="codigo_pais" class="form-select form-select-sm">
                    </select>
                    <label for="codigo_dpto" class="form-label campo-obligatorio">Departamento</label>
                    <select id="codigo_dpto" name="codigo_dpto" class="form-select form-select-sm">
                    </select>
                    <label for="codigo_mpio" class="form-label campo-obligatorio">Ciudad</label>
                    <select id="codigo_mpio" name="codigo_mpio" class="form-select form-select-sm">
                    </select>
                    <label for="direccion" class="form-label campo-obligatorio">Dirección</label>
                    <input type="text" name="direccion" class="form-control form-control-sm" id="direccion" placeholder="direccion" required>
                    <label for="telefono" class="form-label campo-obligatorio">Teléfono </label>
                    <input type="text" name="telefono" class="form-control form-control-sm" id="telefono" placeholder="telefono" autocomplete="off" onKeyPress="return vnumeros(event)" required>
                    <label for="email" class="form-label campo-obligatorio">Email</label>
                    <input type="email" class="form-control form-control-sm" id="correo" name="correo" placeholder="Email" autocomplete="off" required>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Perfil Modal -->
<div class="modal fade" id="perfilModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Configuración Perfil Tributario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form>

          <!-- Bodega -->
          <div class="row mb-3 align-items-center">
            <div class="col-md-4">
              <label class="form-label">Bodega</label>
            </div>
            <div class="col-md-8">
              <select class="form-select form-select-sm" id="pBodegas">
              </select>
            </div>
          </div>

          <!-- SW -->
          <div class="row mb-3 align-items-center">
            <div class="col-md-4">
              <label class="form-label">SW</label>
            </div>
            <div class="col-md-8">
              <select class="form-select form-select-sm" id="pSw">
              </select>
            </div>
          </div>

          <!-- Perfil Tributario -->
          <div class="row mb-3 align-items-center">
            <div class="col-md-4">
              <label for="pPerfil" class="form-label"> Perfil Tributario </label>
            </div>
            <div class="col-md-8">
              <div class="input-group input-group-sm">
                <select class="form-select" id="pPerfil">
                </select>
                <button type="button" class="btn btn-outline-secondary" id="btnCrearPerfiles"> Crear Perfiles </button>
              </div>
            </div>
          </div>

          <!-- Retenciones -->
          <div class="row text-center">
            <div class="col-md-3">
              <label class="form-label">Ret. Fuente</label>
              <select class="form-select form-select-sm" id="pRetFte">
                <option value="N">N - No usar</option>
                <option value="S">S - Usar</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ret. Iva</label>
              <select class="form-select form-select-sm" id="pRetIva">
                <option value="N">N - No usar</option>
                <option value="S">S - Usar</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ret. Ica</label>
              <select class="form-select form-select-sm" id="pRetIca">
                <option value="N">N - No usar</option>
                <option value="S">S - Usar</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Ret. Cree</label>
              <select class="form-select form-select-sm" id="pRetCree">
                <option value="N">N - No usar</option>
                <option value="S">S - Usar</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"> Cerrar </button>
        <button type="button" class="btn btn-sm btn-primary" id="btnGuardarPerfilDefinicion"> Actualizar </button>
      </div>
    </div>
  </div>
</div>
<!-- Crear perfil modal -->
<!-- Modal Crear Perfiles -->
<div class="modal fade" id="crearPerfilModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-light">
        <h5 class="modal-title">Crear un nuevo perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Campos superiores -->
        <div class="row mb-3">
          <div class="col-md-3">
            <label class="form-label">Perfil Tributario</label>
          </div>
          <div class="col-md-3">
            <input type="text" class="form-control form-control-sm" id="codigoPerfil">
          </div>
        </div>

        <div class="row mb-4">
          <div class="col-md-3">
            <label class="form-label">Descripción</label>
          </div>
          <div class="col-md-9">
            <input type="text" class="form-control form-control-sm" id="descripcionPerfil">
          </div>
        </div>

        <!-- Botones -->
        <div class="row mb-4 text-center">
          <div class="col-md-4">
            <button class="btn btn-sm btn-outline-success w-75" id="btnModificar">Guardar/Modificar</button>
          </div>
          <div class="col-md-4">
            <button class="btn btn-sm btn-outline-secondary w-75" id="btnNuevo">Nuevo</button>
          </div>
          <div class="col-md-4">
            <button class="btn btn-sm btn-outline-danger w-75" id="btnBorrar">Borrar</button>
          </div>
        </div>

        <!-- Listado -->
        <div class="border p-2" style="height:250px; overflow-y:auto;">
          <ul class="list-group list-group-flush" id="listaPerfiles">
          </ul>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<?php

$scriptsPagina = [
  'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js',
  '../controllers/Empresas.js?' . rand()
];

include '../dependencies/footer.php';

?>