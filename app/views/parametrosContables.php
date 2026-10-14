<?php
session_start();
$titulo = 'Parámetros contables';
include '../dependencies/header.php';
?>
<div class="card">
  <div class="card-body">
    <h5 class="fw-bold py-0 mb-3"><span class="text-muted fw-light">Financiero /</span> Parámetros contables</h5>
    <form id="form-parametros-contables" class="needs-validation form-basico" novalidate>
      <div class="card">
        <div class="card-body">
          <div class="col-12 mb-3">
            <div class="demo-inline-spacing d-flex justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-configurar-tributarios"><i class="fa fa-sliders"></i> Parametros tributarios</button>
              <button type="submit" class="btn btn-sm btn-outline-primary" id="btn-guardar"><i class="fa-regular fa-floppy-disk"></i> Guardar </button>
            </div>
          </div>
          <div class="alert alert-primary" role="alert"><b>Ventas</b> - Configura las cuentas contables de ingresos por defecto para el registro de documentos de venta y devoluciones de tus clientes. En caso de estar configurados los perfiles contables las cuentas se tomarán de dichos perfiles</div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_ingresos" class="form-label">Ingresos</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_ingresos" name="cu_ingresos" required>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">IVA generado</label>
              <select class="form-select form-select-sm" id="cu_iva_ventas" name="cu_iva_ventas"></select>
            </div>
            <div class="col-md-3">
              <label for="cu_dev_ventas" class="form-label">Devoluciones en ventas</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_dev_ventas" name="cu_dev_ventas" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_deviva_ventas" class="form-label">Devolución del IVA</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_deviva_ventas" name="cu_deviva_ventas" required>
              </select>
            </div>
          </div>
          <div class="alert alert-primary" role="alert"><b>Inventario</b> - Parametriza la cuenta que recibirá el movimiento de tus transacciones con clientes.</div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_inventario" class="form-label">Inventarios</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_inventario" name="cu_inventario" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_costo_inventario" class="form-label">Costo de inventario</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_costo_inventario" name="cu_costo_inventario" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_aju_inventario_entradas" class="form-label">Ajuste de inventarios</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_aju_inventario_entradas" name="cu_aju_inventario_entradas" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_aju_inventario_salidas" class="form-label">Ajuste de inventarios (Salidas)</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_aju_inventario_salidas" name="cu_aju_inventario_salidas" required>
              </select>
            </div>
          </div>
          <div class="alert alert-primary" role="alert"><b>Clientes</b> - Parametriza la cuenta que recibirá el movimiento de tus transacciones con clientes.</div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_cxc_clientes" class="form-label">Cuenta por cobrar clientes</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_cxc_clientes" name="cu_cxc_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_dev_clientes" class="form-label">Devoluciones de clientes</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_dev_clientes" name="cu_dev_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_retefuente_clientes" class="form-label">ReteFuente</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_retefuente_clientes" name="cu_retefuente_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_reteiva_clientes" class="form-label">ReteIVA</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_reteiva_clientes" name="cu_reteiva_clientes" required>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_descuento_clientes" class="form-label">Descuentos Clientes</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_descuento_clientes" name="cu_descuento_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_reteica_clientes" class="form-label">ReteICA</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_reteica_clientes" name="cu_reteica_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_retecree_clientes" class="form-label">ReteCree</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_retecree_clientes" name="cu_retecree_clientes" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_cxp_clientes" class="form-label">Cuentas por pagar a clientes</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_cxp_clientes" name="cu_cxp_clientes" required>
              </select>
            </div>
          </div>
          <div class="alert alert-primary" role="alert"><b>Proveedores</b> - Parametriza la cuenta que recibirá el movimiento de tus transacciones con proveedores.</div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_cxp_proveedores" class="form-label">Cuentas por pagar a proveedores</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_cxp_proveedores" name="cu_cxp_proveedores" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_dev_proveedores" class="form-label">Devoluciones de proveedores</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_dev_proveedores" name="cu_dev_proveedores" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_deviva_compras" class="form-label">Devolución del IVA en compras</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_deviva_compras" name="cu_deviva_compras" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_retefuente_proveedores" class="form-label">ReteFuente</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_retefuente_proveedores" name="cu_retefuente_proveedores" required>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_iva_compras" class="form-label">IVA Descontable</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_iva_compras" name="cu_iva_compras" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_reteiva_proveedores" class="form-label">ReteIVA</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_reteiva_proveedores" name="cu_reteiva_proveedores" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_reteica_proveedores" class="form-label">ReteICA</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_reteica_proveedores" name="cu_reteica_proveedores" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_retecree_proveedores" class="form-label">ReteCree</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_retecree_proveedores" name="cu_retecree_proveedores" required>
              </select>
            </div>
          </div>
          <div class="row mb-3">
             <div class="col-md-3">
              <label for="cu_descuento_proveedores" class="form-label">Descuento proveedores</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_descuento_proveedores" name="cu_descuento_proveedores" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_cxc_proveedores" class="form-label">Cuentas por cobrar a proveedores</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_cxc_proveedores" name="cu_cxc_proveedores" required>
              </select>
            </div>
          </div>
          <!-- ========================= -->
          <!-- GASTOS -->
          <!-- ========================= -->

          <div class="alert alert-primary">
            <b>Gastos</b> - Cuentas para registrar egresos.
          </div>

          <div class="row mb-3">

            <div class="col-md-3">
              <label class="form-label">Gastos administrativos</label>
              <select class="form-select form-select-sm" id="cu_gastos_admin" name="cu_gastos_admin"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Gastos de ventas</label>
              <select class="form-select form-select-sm" id="cu_gastos_ventas" name="cu_gastos_ventas"></select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Gastos financieros</label>
              <select class="form-select form-select-sm" id="cu_gastos_financieros" name="cu_gastos_financieros"></select>
            </div>

          </div>

          <div class="alert alert-primary" role="alert">
            <b>Patrimonio</b> - Configura la cuenta en la que se van a registrar los resultados de la empresa y los ajustes por saldos iniciales.
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_ganancia_acumulada" class="form-label">Ganancia acumulada</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_ganancia_acumulada" name="cu_ganancia_acumulada" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_utilidad_ejercicio" class="form-label">Utilidad del ejercicio</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_utilidad_ejercicio" name="cu_utilidad_ejercicio" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_perdida_ejercicio" class="form-label">Pérdida del ejercicio</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_perdida_ejercicio" name="cu_perdida_ejercicio" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_ajuste_saldos_bancos" class="form-label">Ajustes por saldos iniciales en bancos</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_ajuste_saldos_bancos" name="cu_ajuste_saldos_bancos" required>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="cu_ajuste_saldos_inventario" class="form-label">Ajustes por saldos iniciales en inventarios</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_ajuste_saldos_inventario" name="cu_ajuste_saldos_inventario" required>
              </select>
            </div>
          </div>
          <div class="alert alert-primary" role="alert">
            <b>Ajustes y aproximaciones</b> - Configura las cuentas en las que se van a registrar las diferencias generadas por variaciones en tasas de cambio y uso de decimales.
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="cu_ganancia_difcambio" class="form-label">Ganancias por diferencia en cambio</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_ganancia_difcambio" name="cu_ganancia_difcambio" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_perdida_difcambio" class="form-label">Pérdidas por diferencia en cambio</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_perdida_difcambio" name="cu_perdida_difcambio" required>
              </select>
            </div>
            <div class="col-md-3">
              <label for="cu_aprox_decimales" class="form-label">Ajustes por aproximaciones decimales</label>
              <select class="form-select form-select-sm" data-placeholder="Escoja una cuenta" id="cu_aprox_decimales" name="cu_aprox_decimales" required>
              </select>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalCuentasTributarias" tabindex="-1" aria-labelledby="modalCuentasTributariasLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCuentasTributariasLabel">Configurar parametros tributarios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ct_id">
        <div class="border border-info rounded py-2 px-3 mt-3 mb-2" role="note">
          <i class="fa-solid text-info fa-circle-info me-1"></i>
          Antes de generar informes tributarios, asegúrese de configurar correctamente los parámetros tributarios conforme a sus perfiles contables.
          Una configuración incorrecta puede afectar la conciliación de la información y el cálculo de impuestos y retenciones.
        </div>
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label" for="ct_tipo">Tipo</label>
            <select class="form-select form-select-sm" id="ct_tipo">
              <option value="">Seleccione...</option>
              <option value="IVA_GENERADO">IVA Generado</option>
              <option value="IVA_DESCONTABLE">IVA Descontable</option>
              <option value="RETEFUENTE_COMPRA">Retefuente Compra</option>
              <option value="RETEFUENTE_VENTA">Retefuente Venta</option>
              <option value="RETEIVA_COMPRA">ReteIVA Compra</option>
              <option value="RETEIVA_VENTA">ReteIVA Venta</option>
              <option value="RETEICA_COMPRA">ReteICA Compra</option>
              <option value="RETEICA_VENTA">ReteICA Venta</option>
            </select>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="ct_cuenta_busqueda">Cuenta contable</label>
            <select class="form-select form-select-sm" id="ct_cuenta_busqueda"></select>
            <small class="text-muted">Debe seleccionar una cuenta de la lista.</small>
          </div>
        </div>
        <hr>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-striped align-middle" id="tbl-cuentas-tributarias">
            <thead class="table-primary">
              <tr>
                <th>Tipo</th>
                <th>Cuenta Contable</th>
                <th style="width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" id="btn-cancelar-cuenta-tributaria">Cancelar</button>
        <button type="button" class="btn btn-sm btn-primary" id="btn-guardar-cuenta-tributaria">Guardar</button>
      </div>
    </div>
  </div>
</div>
<?php
$scriptsPagina = [
  '../controllers/parametrosContables.js?' . rand()
];
include '../dependencies/footer.php';
?>