     <?php
      session_start();

      $titulo = 'FE - Datos fiscales';
      include '../dependencies/header.php';
      ?>
     <style>
       /* Estilo para mostrar el error */
       .is-invalid {
         border: 1px solid red;
       }

       .btn-xxs {
         padding: 0.15rem 0.3rem;
         font-size: 0.7rem;
         line-height: 1;
         border-radius: 0.15rem;
       }
     </style>
     <div class="card">
       <div class="card-body">
         <h5 class="fw-bold py-0 mb-2"><span class="text-muted fw-light">Financiero /</span> FE - Datos fiscales</h5>
         <div class="nav-align-top">
           <ul class="nav nav-tabs nav-tabs-bordered" id="borderedTab" role="tablist">
             <li class="nav-item" role="presentation">
               <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#navs-creacion" type="button" role="tab" aria-controls="home" aria-selected="true"> <i class="fa-solid fa-file-circle-plus"></i>&nbsp;Creación</button>
             </li>
             <li class="nav-item" role="presentation">
               <button class="nav-link disabled" data-bs-toggle="tab" data-bs-target="#navs-secuenciales" type="button" role="tab" aria-controls="navs-secuenciales" aria-selected="false"><i class="fa-solid fa-diagram-successor"></i>&nbsp;Secuenciales</button>
             </li>
             <li class="nav-item" role="presentation">
               <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-facturacion-electronica" type="button" role="tab" aria-controls="navs-facturacion-electronica" aria-selected="false"><i class="fa-solid fa-file-invoice-dollar"></i>&nbsp;Facturación Electrónica</button>
             </li>
             <li class="nav-item" role="presentation">
               <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-recepcion-electronica" type="button" role="tab" aria-controls="navs-recepcion-electronica" aria-selected="false"><i class="fa-solid fa-inbox"></i>&nbsp;Recepción Electrónica</button>
             </li>
           </ul>
           <div class="tab-content">
             <div class="tab-pane fade show active" id="navs-creacion" role="tabpanel">
               <input type="hidden" class="form-control form-control-sm" name="idTercero" id="idTercero" disabled readonly>
               <form id="form-datos-fiscales" class="needs-validation form-basico" enctype="multipart/form-data" novalidate>
                 <div class="row">
                   <!-- Datos del contribuyente -->
                   <div class="col-md-12">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Datos del contribuyente</h5>
                       <div class="card-body row g-2">
                         <div class="col-md-3">
                           <label for="nit" class="form-label">NIT</label>
                           <input type="text" class="form-control form-control-sm" name="nit" id="nit" maxlength="20" placeholder="NIT" autocomplete="off" onKeyPress="return vnumeros(event)" required>
                         </div>
                         <div class="col-md-1">
                           <label for="nitDv" class="form-label">DV</label>
                           <input type="text" class="form-control form-control-sm" name="nitDv" id="nitDv" maxlength="2" readonly value="0" required>
                         </div>
                         <div class="col-md-4">
                           <label for="razonSocial" class="form-label">Razón social</label>
                           <input type="text" class="form-control form-control-sm text-uppercase" name="razonSocial" id="razonSocial" autocomplete="off" required>
                         </div>
                         <div class="col-md-4">
                           <label for="nombreComercial" class="form-label">Nombre Comercial</label>
                           <input type="text" class="form-control form-control-sm text-uppercase" name="nombreComercial" id="nombreComercial" autocomplete="off" required>
                         </div>
                         <div class="col-md-3">
                           <label for="perfilTributario" class="form-label">Perfil tributario</label>
                           <select id="perfilTributario" name="perfilTributario" class="form-select form-select-sm" required>
                           </select>
                         </div>
                         <div class="col-md-3">
                           <label for="regimen" class="form-label">Régimen</label>
                           <select id="regimen" name="regimen" class="form-select form-select-sm" required>
                           </select>
                         </div>

                         <div class="col-md-3 d-flex align-items-end" id="dvButtons">
                           <button class="btn btn-sm btn-outline-secondary me-2" type="button" id="btnNuevoFiscales"><i class="fa-regular fa-file"></i> Nuevo</button>
                           <button class="btn btn-sm btn-outline-primary me-2" type="submit" id="btnGuardarFiscales"><i class="fa-regular fa-floppy-disk"></i> Guardar</button>
                         </div>
                       </div>
                     </div>
                   </div>

                   <!-- Consumos y folios -->

                   <div class="col-md-12">
                     <div class="card mb-3 border-0">
                       <h5 class="card-header text-primary form-label">Consumos y folios</h5>
                       <div class="card-body text-center">
                         <!-- Cards Folios -->
                         <div class="row g-2 mt-3">
                           <!-- Card Vigencia del Certificado -->
                           <div class="col-md-3">
                             <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                               <div class="card-body text-center p-2">
                                 <div class="mb-1">
                                   <i class="fa-solid fa-certificate text-primary" style="font-size: 1.2rem;"></i>
                                 </div>
                                 <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Vigencia Firma</h6>
                                 <div class="fw-bold text-primary" id="dias-valor" style="font-size: 1.5rem;">-</div>
                                 <small class="text-muted d-block" style="font-size: 0.7rem;">Días restantes</small>
                               </div>
                             </div>
                           </div>

                           <!-- Card Disponibles -->
                           <div class="col-md-3">
                             <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                               <div class="card-body text-center p-2">
                                 <div class="mb-1">
                                   <i class="fa-solid fa-file-invoice text-success" style="font-size: 1.2rem;"></i>
                                 </div>
                                 <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Disponibles</h6>
                                 <div class="fw-bold text-success" id="folios-disponibles-value" style="font-size: 1.5rem;">-</div>
                                 <small class="text-muted d-block" id="folios-disponibles-pct" style="font-size: 0.7rem;">0% del total</small>
                               </div>
                             </div>
                           </div>

                           <!-- Card Consumidos -->
                           <div class="col-md-3">
                             <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                               <div class="card-body text-center p-2">
                                 <div class="mb-1">
                                   <i class="fa-solid fa-fire text-info" style="font-size: 1.2rem;"></i>
                                 </div>
                                 <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Consumidos</h6>
                                 <div class="fw-bold text-info" id="folios-consumidos-value" style="font-size: 1.5rem;">-</div>
                                 <small class="text-muted d-block" id="folios-consumidos-pct" style="font-size: 0.7rem;">0% de uso</small>
                               </div>
                             </div>
                           </div>

                           <!-- Card Comprados -->
                           <div class="col-md-3">
                             <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                               <div class="card-body text-center p-2">
                                 <div class="mb-1">
                                   <i class="fa-solid fa-inbox text-secondary" style="font-size: 1.2rem;"></i>
                                 </div>
                                 <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Comprados</h6>
                                 <div class="fw-bold text-secondary" id="folios-comprados-value" style="font-size: 1.5rem;">-</div>
                                 <small class="text-muted d-block" style="font-size: 0.7rem;">Total adquirido</small>
                               </div>
                             </div>
                           </div>
                         </div>

                         <!-- Consumo por aspectos -->
                         <div class="row mt-4">
                           <div class="col-md-12">
                             <div class="row g-2">
                               <!-- Card Facturación -->
                               <div class="col-md-4">
                                 <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                                   <div class="card-body text-center p-2">
                                     <div class="mb-1">
                                       <i class="fa-solid fa-file-invoice-dollar" id="consumo-facturacion-icon" style="font-size: 1.2rem; color: #696cff;"></i>
                                     </div>
                                     <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Facturación</h6>
                                     <div class="fw-bold" id="consumo-facturacion-value" style="font-size: 1.5rem; color: #696cff;">-</div>
                                     <small class="text-muted d-block" id="consumo-facturacion-label" style="font-size: 0.7rem;">-</small>
                                   </div>
                                 </div>
                               </div>

                               <!-- Card Nómina -->
                               <div class="col-md-4">
                                 <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                                   <div class="card-body text-center p-2">
                                     <div class="mb-1">
                                       <i class="fa-solid fa-file-contract" id="consumo-nomina-icon" style="font-size: 1.2rem; color: #28a745;"></i>
                                     </div>
                                     <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Nómina</h6>
                                     <div class="fw-bold" id="consumo-nomina-value" style="font-size: 1.5rem; color: #28a745;">-</div>
                                     <small class="text-muted d-block" id="consumo-nomina-label" style="font-size: 0.7rem;">-</small>
                                   </div>
                                 </div>
                               </div>

                               <!-- Card Recepción -->
                               <div class="col-md-4">
                                 <div class="card border-2 shadow-sm" style="font-size: 0.9rem;">
                                   <div class="card-body text-center p-2">
                                     <div class="mb-1">
                                       <i class="fa-solid fa-inbox" id="consumo-recepcion-icon" style="font-size: 1.2rem; color: #ffc107;"></i>
                                     </div>
                                     <h6 class="card-title text-muted mb-1" style="font-size: 0.75rem;">Recepción</h6>
                                     <div class="fw-bold" id="consumo-recepcion-value" style="font-size: 1.5rem; color: #ffc107;">-</div>
                                     <small class="text-muted d-block" id="consumo-recepcion-label" style="font-size: 0.7rem;">-</small>
                                   </div>
                                 </div>
                               </div>
                             </div>
                           </div>
                         </div>

                         <!-- Alertas -->
                         <div class="row mt-4">
                           <div class="col-md-12">
                             <div id="folios-alerta"></div>
                           </div>
                         </div>


                       </div>
                     </div>


                   </div>

                   <!-- Logo -->
                   <div class="col-md-6">
                     <div class="card mb-3 border-0">
                       <h5 class="card-header text-primary form-label">Logo de la empresa</h5>
                       <div class="card-body text-center">
                         <label for="logoEmpresa" class="form-label mb-2">Archivo de logo (PNG/JPG)</label>

                         <!-- Vista previa del logo -->
                         <div id="previewContainer" class="mb-3" style="display: none;"> <img id="preview" src="#" alt="Vista previa del logo" class="img-thumbnail rounded shadow-sm" style="max-height: 150px;"> </div>

                         <!-- Input de carga -->
                         <input type="file" class="form-control form-control-sm mx-auto" id="logoEmpresa" name="logoEmpresa" accept="image/png, image/jpeg">
                       </div>
                     </div>
                   </div>

                   <!-- Certificado Digital y Seguridad -->
                   <div class="col-md-6">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Certificado Digital y Seguridad</h5>
                       <div class="card-body row g-2">
                         <div class="col-md-6">
                           <label for="certificadoNombre" class="form-label">Nombre</label>
                           <input type="text" name="certificadoNombre" class="form-control form-control-sm" id="certificadoNombre" required>
                         </div>
                         <div class="col-md-6">
                           <label for="certificadoClave" class="form-label">Clave</label>
                           <input type="text" class="form-control form-control-sm" id="certificadoClave" name="certificadoClave" required>
                         </div>
                         <div class="col-md-6">
                           <label for="tokenUser" class="form-label">Token User</label>
                           <input type="text" name="tokenUser" class="form-control form-control-sm" id="tokenUser" required readonly>
                         </div>
                         <div class="col-md-6">
                           <label for="tokenPassword" class="form-label">Token Password</label>
                           <input type="text" class="form-control form-control-sm" id="tokenPassword" name="tokenPassword" required readonly>
                         </div>
                       </div>
                     </div>
                   </div>

                   <!-- Domicilio Fiscal y DIAN -->
                   <div class="col-md-4">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Domicilio Fiscal</h5>
                       <div class="card-body">
                         <label for="codigoPais" class="form-label">País</label>
                         <select id="codigoPais" name="codigoPais" class="form-select form-select-sm">
                         </select>
                         <label for="codigoDpto" class="form-label">Departamento</label>
                         <select id="codigoDpto" name="codigoDpto" class="form-select form-select-sm">
                         </select>
                         <label for="codigoCiudad" class="form-label">Ciudad</label>
                         <select id="codigoCiudad" name="codigoCiudad" class="form-select form-select-sm">
                         </select>
                         <label for="direccion" class="form-label">Dirección</label>
                         <input type="text" name="direccion" class="form-control form-control-sm" id="direccion" required>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-4">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">DIAN (Factura Electrónica)</h5>
                       <div class="card-body">
                         <label for="SoftwareID" class="form-label">Software ID</label>
                         <input type="text" name="SoftwareID" class="form-control form-control-sm" id="SoftwareID" required>
                         <label for="claveTecnica" class="form-label">Clave Técnica</label>
                         <input type="text" name="claveTecnica" class="form-control form-control-sm" id="claveTecnica" required>
                         <label for="testSetId" class="form-label">Test Set ID</label>
                         <input type="text" name="testSetId" class="form-control form-control-sm" id="testSetId" required>
                         <label for="pinSoftware" class="form-label">PIN Software</label>
                         <input type="text" name="pinSoftware" class="form-control form-control-sm" id="pinSoftware" required>
                         <label for="ProfileExecutionID" class="form-label">Estatus</label>
                         <div class="input-group input-group-sm"> <span class="input-group-text"><i id="labelProfile" class="fa-solid fa-check-to-slot"></i></span>
                           <select id="ProfileExecutionID" name="ProfileExecutionID" class="form-select form-select-sm" required>
                             <option value="">Seleccionar</option>
                             <option value="2">EN PRUEBAS</option>
                             <option value="1">EN PRODUCCIÓN</option>
                           </select>
                         </div>
                         <button class="btn btn-sm btn-outline-success mt-3" type="button" id="btnHabilitar"><i class="fa-solid fa-user-check"></i> Habilitar</button>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-4">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">DIAN (Nomina Electrónica)</h5>
                       <div class="card-body">
                         <label for="SoftwareIDNomina" class="form-label">Software ID</label>
                         <input type="text" name="SoftwareIDNomina" class="form-control form-control-sm" id="SoftwareIDNomina" required>
                         <label for="testSetIdNomina" class="form-label">Test Set ID</label>
                         <input type="text" name="testSetIdNomina" class="form-control form-control-sm" id="testSetIdNomina" required>
                         <label for="pinSoftwareNomina" class="form-label">PIN Software</label>
                         <input type="text" name="pinSoftwareNomina" class="form-control form-control-sm" id="pinSoftwareNomina" required>
                         <label for="ProfileExecutionIDNomina" class="form-label">Estatus</label>
                         <div class="input-group input-group-sm"> <span class="input-group-text"><i id="labelProfileNomina" class="fa-solid fa-check-to-slot"></i></span>
                           <select id="ProfileExecutionIDNomina" name="ProfileExecutionIDNomina" class="form-select form-select-sm" required>
                             <option value="">Seleccionar</option>
                             <option value="2">EN PRUEBAS</option>
                             <option value="1">EN PRODUCCIÓN</option>
                           </select>
                         </div>
                       </div>
                     </div>
                   </div>

                   <!-- Información de recepción -->
                   <div class="col-md-12">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Recepción de documentos electrónicos</h5>
                       <div class="card-body">
                         <div class="row">
                           <div class="col-md-3">
                             <label for="servicioAcuse" class="form-label">Servicio Contratado</label>
                             <select name="servicioAcuse" id="servicioAcuse" class="form-select form-select-sm" required>
                               <option value="">Seleccionar</option>
                               <option value="S">Sí</option>
                               <option value="N">No</option>
                             </select>
                           </div>
                           <div class="col-md-3">
                             <label for="imapProveedor" class="form-label">IMAP</label>
                             <input type="text" name="imapProveedor" class="form-control form-control-sm" id="imapProveedor">
                           </div>
                           <div class="col-md-3">
                             <label for="imapMail" class="form-label">Correo</label>
                             <input type="email" name="imapMail" class="form-control form-control-sm" id="imapMail">
                           </div>
                           <div class="col-md-3">
                             <label for="imapClave" class="form-label">Clave</label>
                             <input type="password" name="imapClave" class="form-control form-control-sm" id="imapClave">
                           </div>
                         </div>
                         <div class="row mt-4">
                           <div class="col-md-4">
                             <div class="card mb-3">
                               <h5 class="card-header text-primary form-label">Usuario de recibo de Factura &nbsp;&nbsp;&nbsp;&nbsp;
                                 <button id="btnAlluser" class="btn btn-xxs btn-outline-success border-1 py-0 px-1"> <i class="fas fa-check fs-7 me-1"></i> <span class="small">APLICAR A TODOS</span> </button>
                               </h5>
                               <div class="card-body">
                                 <select id="usrReciboTipo" name="usrReciboTipo" class="form-select form-select-sm mt-3">
                                   <option value="13">Persona Natural</option>
                                   <option value="31">Persona Juridica</option>
                                 </select>
                                 <div class="row mt-2">
                                   <div class="col-md-9">
                                     <input type="text" name="usrReciboDoc" class="form-control form-control-sm" placeholder="Número documento" id="usrReciboDoc">
                                   </div>
                                   <div class="col-md-3">
                                     <input type="text" name="usrReciboDv" class="form-control form-control-sm" placeholder="Dígito" id="usrReciboDv">
                                   </div>
                                 </div>
                                 <input type="text" name="usrReciboNombre" class="form-control form-control-sm  mt-2" placeholder="Nombres" id="usrReciboNombre">
                                 <input type="text" name="usrReciboApellido" class="form-control form-control-sm  mt-2" placeholder="Apellidos" id="usrReciboApellido">
                               </div>
                             </div>
                           </div>
                           <div class="col-md-4">
                             <div class="card mb-3">
                               <h5 class="card-header text-primary form-label">Usuario de Aceptación del bien/servicio </h5>
                               <div class="card-body">
                                 <select id="usrAceptTipo" name="usrAceptTipo" class="form-select form-select-sm mt-3">
                                   <option value="13">Persona Natural</option>
                                   <option value="31">Persona Juridica</option>
                                 </select>
                                 <div class="row mt-2">
                                   <div class="col-md-9">
                                     <input type="text" name="usrAceptboDoc" class="form-control form-control-sm" placeholder="Número documento" id="usrAceptboDoc">
                                   </div>
                                   <div class="col-md-3">
                                     <input type="text" name="usrAceptDv" class="form-control form-control-sm" placeholder="Dígito" id="usrAceptDv">
                                   </div>
                                 </div>
                                 <input type="text" name="usrAceptNombre" class="form-control form-control-sm  mt-2" placeholder="Nombres" id="usrAceptNombre">
                                 <input type="text" name="usrAceptApellido" class="form-control form-control-sm  mt-2" placeholder="Apellidos" id="usrAceptApellido">
                               </div>
                             </div>
                           </div>
                           <div class="col-md-4">
                             <div class="card mb-3">
                               <h5 class="card-header text-primary form-label">Usuario de reclamo de la Factura </h5>
                               <div class="card-body">
                                 <select id="usrRechazaTipo" name="usrRechazaTipo" class="form-select form-select-sm mt-3">
                                   <option value="13">Persona Natural</option>
                                   <option value="31">Persona Juridica</option>
                                 </select>
                                 <div class="row mt-2">
                                   <div class="col-md-9">
                                     <input type="text" name="usrRechazaDoc" class="form-control form-control-sm" placeholder="Número documento" id="usrRechazaDoc">
                                   </div>
                                   <div class="col-md-3">
                                     <input type="text" name="usrRechazaDv" class="form-control form-control-sm" placeholder="Dígito" id="usrRechazaDv">
                                   </div>
                                 </div>
                                 <input type="text" name="usrRechazaNombre" class="form-control form-control-sm  mt-2" placeholder="Nombres" id="usrRechazaNombre">
                                 <input type="text" name="usrRechazaApellido" class="form-control form-control-sm  mt-2" placeholder="Apellidos" id="usrRechazaApellido">
                               </div>
                             </div>
                           </div>
                         </div>
                       </div>
                     </div>
                   </div>
                   <!-- Contacto y Adicionales -->
                   <div class="col-md-6">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Contacto</h5>
                       <div class="card-body">
                         <label for="telefono" class="form-label">Teléfono</label>
                         <input type="text" name="telefono" class="form-control form-control-sm" id="telefono" onKeyPress="return vnumeros(event)" required>
                         <label for="email" class="form-label">Email</label>
                         <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                       </div>
                     </div>
                   </div>
                   <div class="col-md-6">
                     <div class="card mb-3">
                       <h5 class="card-header text-primary form-label">Adicionales</h5>
                       <div class="card-body">
                         <label for="responsabilidades" class="form-label">Responsabilidades</label>
                         <select id="responsabilidades" name="responsabilidades" class="form-select form-select-sm" multiple required>
                         </select>
                         <label for="impuestos" class="form-label">Impuestos</label>
                         <select id="impuestos" name="impuestos" class="form-select form-select-sm" multiple required>
                         </select>
                       </div>
                     </div>
                   </div>
                 </div>
               </form>
             </div>
             <div class="tab-pane fade" id="navs-secuenciales" role="tabpanel">
               <div class="card mb-4">
                 <h5 class="card-header text-primary form-label">Resoluciones</h5>
                 <div class="card-body">
                   <input type="hidden" class="form-control" id="idResolucion" readonly disabled>
                   <form id="form-datos-resoluciones" class="needs-validation form-basico" novalidate>
                     <div class="row">
                       <div class="col-md-3">
                         <label for="InvoiceAuthorization" class="form-label">Tipo documento</label>
                         <select class="form-select form-select-sm" id="idTipoDoc" name="idTipoDoc" required>
                         </select>
                       </div>
                       <div class="col-md-3">
                         <label for="InvoiceAuthorization" class="form-label">Resolucion</label>
                         <input type="text" name="InvoiceAuthorization" class="form-control form-control-sm" id="InvoiceAuthorization" placeholder="N° Resolución" onKeyPress="return vnumeros(event)" autocomplete="off" required>
                       </div>
                       <div class="col-md-3">
                         <label for="Prefix" class="form-label">Prefijo</label>
                         <input type="text" name="Prefix" class="form-control form-control-sm" autocomplete="off" id="Prefix" placeholder="Prefijo" required>
                       </div>
                       <div class="col-md-3">
                         <label for="From" class="form-label">Rango desde</label>
                         <input type="text" name="From" class="form-control form-control-sm" id="From" autocomplete="off" placeholder="Desde" onKeyPress="return vnumeros(event)" required>
                       </div>
                     </div>
                     <div class="row">
                       <div class="col-md-3">
                         <label for="To" class="form-label">Rango Hasta</label>
                         <input type="text" name="To" class="form-control form-control-sm" id="To" autocomplete="off" placeholder="Hasta" onKeyPress="return vnumeros(event)" required>
                       </div>
                       <div class="col-md-3">
                         <label for="StartDate" class="form-label">Fecha inicial</label>
                         <div class="input-group input-group-merge"> <span id="" class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                           <input type="date" class="form-control form-control-sm" name="StartDate" id="StartDate" aria-describedby="StartDate" required>
                         </div>
                       </div>
                       <div class="col-md-3">
                         <label for="EndDate" class="form-label">Fecha final</label>
                         <div class="input-group input-group-merge"> <span id="" class="input-group-text"><i class="fa-regular fa-calendar-check"></i></span>
                           <input type="date" class="form-control form-control-sm" name="EndDate" id="EndDate" aria-describedby="EndDate" required>
                         </div>
                       </div>
                       <div class="col-md-3">
                         <label for="EndDate" class="form-label">Clave Tecnica</label>
                         <div class="input-group input-group-merge"> <span id="" class="input-group-text"><i class="fa-solid fa-key"></i></span>
                           <input type="text" class="form-control form-control-sm" autocomplete="off" name="claveTecnicaRes" id="claveTecnicaRes" aria-describedby="claveTecnicaRes">
                         </div>
                       </div>
                     </div>
                     <div class="row mt-3">
                       <div class="col-md-12">
                         <button class="btn btn-sm btn-outline-secondary" type="button" id="btnNuevoResoluciones"><i class="fa-regular fa-file"></i>&nbsp;Nuevo</button>
                         <button class="btn btn-sm btn-outline-primary" type="submit" id="btnGuardarResoluciones"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Guardar</button>
                         <button class="btn btn-sm btn-outline-success" type="button" id="btnConsultarResoluciones"><i class="fa-solid fa-rotate"></i>&nbsp;Consultar Resoluciones</button>
                       </div>
                     </div>
                   </form>
                   <div class="text-nowrap table-responsive mt-3" id="result"></div>
                 </div>
               </div>
             </div>
             <div class="tab-pane fade" id="navs-facturacion-electronica" role="tabpanel">
               <div class="row">
                 <div class="col-md-3">
                   <label class="form-label">Fecha Inicial</label>
                   <input type="date" id="Fecha" name="Fecha" class="form-control form-control-sm bg-light" />
                 </div>
                 <div class="col-md-3">
                   <label class="form-label">Fecha Final</label>
                   <input type="date" id="FechaFin" name="FechaFin" class="form-control form-control-sm bg-light" />
                 </div>
                 <div class="col-md-2">
                   <label class="form-label">Clase de documento</label>
                   <select class="form-select form-select-sm" id="TipoDocumento">
                     <option value="5">Ventas </option>
                     <option value="11">Notas crédito (deudores / clientes)</option>
                     <option value="14">Notas débito documento soporte (proveedores / acreedores)</option>
                     <option value="4">Devoluciones en ventas</option>
                   </select>
                 </div>
                 <div class="col-md-2 ">
                   <label class="form-label">Tipo de documento</label>
                   <select class="form-select form-select-sm" id="ClaseDocumento">
                   </select>
                 </div>
                 <div class="col-md-2">
                   <label class="form-label">Estado</label>
                   <select id="estado" name="estado" class="form-select form-select-sm" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
                     <option value="0" selected>TODOS</option>
                     <option value="S">EXITOSO</option>
                     <option value="N">NO ENVIADO</option>
                   </select>
                 </div>
               </div>
               <div class="row mt-3">
                 <div class="col-md-12">
                   <button class="btn btn-outline-primary m-1 btn-sm" id="Consultar" onClick="ConsultarDocumentos();"> <span class="fa-solid fa-magnifying-glass" aria-hidden="true"></span> Consultar </button>
                   <button class="btn btn-outline-primary m-1 btn-sm" id="EnviarTodos" onClick="EnviarTodosDocumentos();"> <span class="fa-solid fa-paper-plane" aria-hidden="true"></span> Enviar todos </button>
                 </div>
               </div>
               <br>
               <div class="row">
                 <div class="col-sm-12 mt-2">
                   <div id="listado_documentos" class="table-responsive text-nowrap"></div>
                 </div>
               </div>
             </div>
             <div class="tab-pane fade" id="navs-recepcion-electronica" role="tabpanel">
               <div class="card mb-4">
                 <h5 class="card-header text-primary py-2 fs-6">Recepción Electrónica</h5>
                 <div class="card-body">
                   <div class="row">
                     <div class="col-md-6">
                       <label class="form-label m-100">Fecha Inicial</label>
                       <input type="date" id="FechaIniR" name="FechaIniR" class="form-control form-control-sm" />
                     </div>
                     <div class="col-md-6">
                       <label class="form-label m-100">Fecha Final</label>
                       <input type="date" id="FechaFinR" name="FechaFinR" class="form-control form-control-sm" />
                     </div>
                   </div>
                   <div class="row mt-3">
                     <div class="col-md-12">
                       <button class="btn btn-outline-primary m-1 btn-sm" id="Consultar" onClick="consultarAcuses();">
                         <span class="fa-solid fa-magnifying-glass" aria-hidden="true"></span> Consultar
                       </button>
                       <button class="btn btn-outline-success m-1 btn-sm" id="acusesManuales" data-bs-toggle="modal" data-bs-target="#modalAcusesManuales">
                         <span class="fa-solid fa-check-double" aria-hidden="true"></span> Acuses manuales
                       </button>
                     </div>
                   </div>
                   <div class="row mt-3">
                     <div class="col-sm-12 mt-2">
                       <div id="listado_documentos_recibidos" class="shadow-sm table-responsive-md"></div>
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
     <!---- Modal Rangos -->
     <div class="modal" id="modalRangos" tabindex="-1">
       <div class="modal-dialog modal-fullscreen">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title">Resoluciones</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
             <div id="tablaRangosContainer"></div>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">cerrar</button>
           </div>
         </div>
       </div>
     </div>
     <!--Modal acuses manuales-->
     <div class="modal" id="modalAcusesManuales" tabindex="-1">
       <div class="modal-dialog modal-xl">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title">Acuses Manuales</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
             <div class="row mt-3">
               <div class="col-md-12">
                 <label for="nit" class="form-label">CUFE</label>
                 <input type="text" class="form-control form-control-sm " value="" id="bCufe" placeholder="CUFE de factura asociada a la compra" autocomplete="off">
               </div>
             </div>
             <div class="row mt-3">
               <div class="col-md-12">
                 <button class="btn btn-sm btn-outline-primary" type="button" id="btnA"><i class="fa-solid fa-receipt"></i>&nbsp;Recibo de factura</button>
                 <button class="btn btn-sm btn-outline-info" type="button" id="btnB"><i class="fa-solid fa-dolly"></i>&nbsp;Recibo del bien y/o servicio</button>
                 <button class="btn btn-sm btn-outline-success" type="button" id="btnC"><i class="fa-solid fa-check-double"></i>&nbsp;Aceptación expresa</button>
                 <div class="btn-group" role="group">
                   <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-triangle-exclamation"></i>&nbsp; Reclamo de la Factura</button>
                   <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                     <li><a class="dropdown-item" href="#" data-codigo="031" data-id="01">Documento con inconsistencias</a></li>
                     <li><a class="dropdown-item" href="#" data-codigo="031" data-id="02">Mercancía no entregada totalmente</a></li>
                     <li><a class="dropdown-item" href="#" data-codigo="031" data-id="03">Mercancía no entregada parcialmente</a></li>
                     <li><a class="dropdown-item" href="#" data-codigo="031" data-id="04">Servicio no prestado</a></li>
                   </ul>
                 </div>

               </div>
             </div>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">cerrar</button>
           </div>
         </div>
       </div>
     </div>

     <?php

      $scriptsPagina = [
        '../controllers/datosFiscales.js?' . rand()
      ];

      include '../dependencies/footer.php';

      ?>