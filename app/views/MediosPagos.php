     <?php
      session_start();

      $titulo = 'Medios de Pagos';
      include '../dependencies/header.php';
      ?>
     <div class="card">
       <div class="card-body">
         <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Medios de Pagos</h5>
         <div class="nav-align-top ">
           <ul class="nav nav-tabs" role="tablist" id="tabs">
             <li class="nav-item">
               <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-creacion" aria-controls="navs-creacion" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i> &nbsp;Creación </button>
             </li>
             <li class="nav-item">
               <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-listado" aria-controls="navs-listado" aria-selected="false"> <i class="fa-solid fa-list-ol"></i> &nbsp;Listado </button>
             </li>
           </ul>
           <div class="tab-content">
             <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
               <form id="FormCrear" class="needs-validation  form-basico" novalidate>
                 <div class="row">
                   <div class="col-md-12">
                     <div class="mb-1" bis_skin_checked="1">
                       <div class="row">
                         <div class="col-12">
                           <div class="demo-inline-spacing d-flex justify-content-end">
                             <button class="btn btn-sm btn-light border-1 border border-light" type="button" id="btnNuevo">Nuevo</button>
                             <button class="btn btn-sm btn-primary  border-1 border border-primary" type="submit" id="btnGuardar">Guardar</button>
                           </div>
                         </div>
                       </div>
                       <div class="row">
                         <input type="hidden" value="0" id="txtId" name="id" autocomplete="off" readonly>
                         <div class="mb-2 col-4">
                           <label for="slcNaturaleza" class="form-label">Naturaleza <span class="text-danger">*</span></label>
                           <select class="form-select form-select-sm" name="naturaleza" id="slcNaturaleza" required>
                             <option value="">Seleccionar...</option>
                             <option value="E">Efectivo</option>
                             <option value="B">Banco</option>
                           </select>
                         </div>
                         <div class="mb-2 col-4">
                           <label for="chkEditable" class="form-label">Editable</label>
                           <br>
                           <input class="form-check-input" type="checkbox" name="editable" value="S" id="chkEditable" checked />
                         </div>
                       </div>
                       <div class="row">
                         <div class="mb-2 col-6">
                           <label for="txtDescripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                           <input type="text" class="form-control form-control-sm" name="descripcion" id="txtDescripcion" maxlength="50" autocomplete="off" required>
                         </div>
                         <div class="mb-2 col-6">
                           <label for="slcCuenta" class="form-label">Cuenta Contable <span class="text-danger">*</span></label>
                           <select class="form-select form-select-sm" name="cuenta" id="slcCuenta" required></select>
                         </div>
                       </div>
                       <div class="row">
                         <div class="mb-2 col-6">
                           <label for="slcInactivo" class="form-label">Inactivo</label>
                           <select class="form-select form-select-sm" name="inactivo" id="slcInactivo">
                             <option value="0">No</option>
                             <option value="1">Si</option>
                           </select>
                         </div>
                       </div>
                     </div>
                   </div>
                 </div>
               </form>
             </div>
             <div class="tab-pane fade " id="navs-listado" role="tabpanel">
               <div class="row">
                 <div class="col-12">
                   <div class="row mb-3">
                     <div class="input-group input-group-merge"> <span class="input-group-text"><i class="bx bx-search"></i></span>
                       <input name="" id="filtro" type="text" class="form-control form-control-sm" placeholder="Descripción, Cuenta..." aria-label="Buscar..." autocomplete="off">
                       <button class="btn btn-outline-primary btn-sm" id="buscar">Buscar</button>
                     </div>
                   </div>
                   <div class="row">
                     <div class="col-12">
                       <div id="result"></div>
                     </div>
                   </div>
                 </div>
               </div>
             </div>
           </div>
           <!--    <div class="tab-content">  -->
         </div>
       </div>
     </div>

     <?php

      $scriptsPagina = [
        '../controllers/MediosPagos.js?' . rand()
      ];

      include '../dependencies/footer.php';

      ?>