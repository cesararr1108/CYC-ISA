/* Todo el módulo va encapsulado para no chocar con funciones globales de shared.js */
(() => {
  const id_modulo = '47';
  const URL_MODELO = '../models/gestionEfectivo.php';
  const URL_SERVICIOS = '../shared/servicios/servicios.php';

  /* =========================================================
   * Estado del módulo
   * ======================================================= */
  const estado = {
    saldo: 0,
    procesando: false,
    historialDesactualizado: true,
    movimientos: [],
    documentosPendientes: [], // facturas (sw 1) y notas débito proveedor (sw 14) del tercero
    cacheCatalogos: {} // { INGRESO: {tipos, conceptos}, EGRESO: {...} }
  };

  /* =========================================================
   * Utilidades
   * ======================================================= */
  const fmt = (v) => formatNum(Number(v) || 0, '$');
  const tipoMov = () => $('input[name="btnradio"]:checked').val();
  const swMov = (tipo = tipoMov()) => (tipo === 'EGRESO' ? 9 : 10);
  const esMovil = () => window.matchMedia('(max-width: 767.98px)').matches;
  const num = (v) => {
    const n = parseFloat(String(v ?? '').replace(/[^\d.-]/g, ''));
    return isNaN(n) ? 0 : n;
  };
  const escapeHtml = (txt) => String(txt ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

  const alerta = (text, icon = 'error', title = '') =>
    Swal.fire({ title: title || (icon === 'error' ? 'Atención' : ''), text, icon, confirmButtonText: 'OK' });

  const llenarSelect = ($sel, lista, placeholder, mapTexto, extraAttrs = null) => {
    const opciones = [`<option value="">${placeholder}</option>`];
    (lista || []).forEach((item) => {
      const attrs = extraAttrs ? extraAttrs(item) : '';
      opciones.push(`<option value="${escapeHtml(item.id)}" ${attrs}>${escapeHtml(mapTexto(item))}</option>`);
    });
    $sel.html(opciones.join(''));
    // Si solo hay una opción real, se selecciona automáticamente
    if ((lista || []).length === 1) $sel.val(lista[0].id);
  };

  /* =========================================================
   * Apertura de caja
   * ======================================================= */
  const validarAperturaCaja = async () => {
    try {
      const resp = await enviarPeticion({ link: '../models/AperturaCierreCaja.php', op: 'info' });

      if (resp.id_apertura == 0) {
        const texto = window.permisos?.abrirModuloApertura
          ? `<div class="avatar avatar-lg mx-auto mb-3">
               <span class="avatar-initial rounded-circle bg-label-primary"><i class="fas fa-cash-register fs-3"></i></span>
             </div>
             <h5 class="mb-2">No hay apertura de caja</h5>
             <p class="text-muted">Para registrar ingresos o egresos primero debes realizar la apertura de caja.</p>
             <a href="AperturaCierreCaja.php?redirect=1" class="btn btn-primary">Ir a apertura de caja</a>`
          : `<div class="avatar avatar-lg mx-auto mb-3">
               <span class="avatar-initial rounded-circle bg-label-secondary"><i class="fas fa-lock fs-3"></i></span>
             </div>
             <h5 class="mb-2">Caja no abierta</h5>
             <p class="text-muted mb-0">Es necesario realizar la apertura de caja. Solicita permisos para acceder al módulo de
             <strong>Apertura y Cierre de Caja</strong>.</p>`;

        $('body').html(`
          <div class="container d-flex justify-content-center align-items-center vh-100 px-3">
            <div class="card shadow text-center p-4 w-100" style="max-width:460px;">${texto}</div>
          </div>`);
        return false;
      }

      $('.container-fluid').removeClass('d-none');
      return true;
    } catch (e) {
      console.error('Error validando apertura de caja:', e);
      return false;
    }
  };

  /* =========================================================
   * Carga de datos
   * ======================================================= */
  const pintarCatalogos = ({ tipos = [], conceptos = [] }) => {
    llenarSelect($('#id_tipo_documento'), tipos, 'Seleccione un documento',
      (i) => `${i.tipo} - ${i.descripcion}`,
      (i) => `data-tipo="${escapeHtml(i.tipo)}"`);

    llenarSelect($('#id_concepto_caja'), conceptos, 'Seleccione un concepto',
      (i) => (i.id_bodega == 0 ? i.concepto : `${i.concepto} - ${i.bodega}`),
      (i) => `data-concepto="${escapeHtml(i.concepto)}"`);

    refrescarResumen();
  };

  // Una sola petición trae usuario + tipos + conceptos del movimiento por defecto
  const cargarInicial = async () => {
    const resp = await enviarPeticion({ link: URL_MODELO, op: 'init', tipoMovimiento: tipoMov() });

    $('#id_usuario').val(resp.usuario.id);
    $('#usuario').text(resp.usuario.usuario);
    $('#id_bodega').val(resp.usuario.id_bodega);
    $('#bodega').text(resp.usuario.bodega_desc);

    estado.cacheCatalogos[tipoMov()] = resp.catalogos;
    pintarCatalogos(resp.catalogos);
  };

  // Tipos y conceptos por movimiento, con caché (no se vuelve a consultar al alternar)
  const cargarCatalogosMovimiento = async (tipo) => {
    if (!estado.cacheCatalogos[tipo]) {
      estado.cacheCatalogos[tipo] = await enviarPeticion({
        link: URL_MODELO,
        op: 'catalogos-movimiento',
        tipoMovimiento: tipo
      });
    }
    pintarCatalogos(estado.cacheCatalogos[tipo]);
  };

  const listarCajas = async () => {
    try {
      const resp = await enviarPeticion({ link: '../models/gestionCajas.php', op: 'listar-caja-banco' });
      llenarSelect($('#cuenta_caja'), resp.data, 'Seleccione una caja', (i) => `${i.cuenta} - ${i.nombre}`);
    } catch (e) {
      console.error('Error al listar cajas:', e.message);
    }
  };

  const saldoCaja = async () => {
    try {
      const resp = await enviarPeticion({ link: URL_SERVICIOS, op: 'valor-caja-turno' });
      estado.saldo = num(resp?.[0]?.saldo);
    } catch (e) {
      console.error('Error consultando saldo:', e);
    }

    $('#saldo-caja-txt, #saldo-actual').text(fmt(estado.saldo));

    const sinSaldo = estado.saldo <= 0;
    $('#btnradio2').prop('disabled', sinSaldo);
    $('#egreso-sin-saldo').toggleClass('d-none', !sinSaldo).toggleClass('d-block', sinSaldo);
    if (sinSaldo && tipoMov() === 'EGRESO') {
      $('#btnradio1').prop('checked', true).trigger('change');
    }
    refrescarResumen();
  };

  /* =========================================================
   * Lista de cajas
   * ======================================================= */
  const filasCajas = () => $('#tabla-cajas tbody tr[data-id]');

  const obtenerCajas = () => filasCajas().map(function () {
    return { idCaja: $(this).data('id'), valor: num($(this).attr('data-valor')) };
  }).get();

  /* =========================================================
   * Pago de documentos pendientes (solo egresos, opcional)
   * ======================================================= */
  const filasDocumentos = () => $('#tabla-documentos tbody tr[data-id]');

  // Documentos marcados con valor a pagar mayor a cero
  const obtenerDocumentos = () => filasDocumentos().filter(function () {
    return $(this).find('.chk-doc').is(':checked');
  }).map(function () {
    const $tr = $(this);
    return {
      id: $tr.data('id'),
      valor: num($tr.find('.input-pagar').val()),
      etiqueta: $tr.data('etiqueta')
    };
  }).get().filter((d) => d.valor > 0);

  const puedePagarDocumentos = () => tipoMov() === 'EGRESO' && !!$('#id_tercero').val();

  const reiniciarDocumentos = () => {
    estado.documentosPendientes = [];
    $('#filtro-documentos').val('');
    $('#chk-pagar-documentos, #chk-todos-docs').prop('checked', false);
    $('#panel-documentos').addClass('d-none');
    $('#tabla-documentos tbody').empty();
    $('#seccion-documentos').toggleClass('d-none', !puedePagarDocumentos());
  };

  const pintarDocumentos = (lista) => {
    $('#filtro-documentos').val('');
    $('#contador-documentos').text(`${lista.length} pendiente${lista.length === 1 ? '' : 's'}`);
    if (!lista.length) {
      $('#tabla-documentos tbody').html(`
        <tr><td colspan="6" class="text-center text-muted py-3">El tercero no tiene documentos pendientes por pagar</td></tr>`);
      return;
    }
    $('#tabla-documentos tbody').html(lista.map((d) => {
      const etiqueta = `${d.tipo} ${d.numero}`;
      return `
        <tr data-id="${escapeHtml(d.id)}" data-saldo="${num(d.saldo)}" data-etiqueta="${escapeHtml(etiqueta)}">
          <td><input class="form-check-input chk-doc" type="checkbox"></td>
          <td>
            <span class="fw-semibold">${escapeHtml(etiqueta)}</span>
            <small class="d-block text-muted">${d.sw == 14 ? 'Nota débito' : 'Compra'}</small>
          </td>
          <td class="d-none d-md-table-cell text-nowrap">${escapeHtml(d.fecha)}</td>
          <td class="text-end d-none d-sm-table-cell text-nowrap">${fmt(d.valor_total)}</td>
          <td class="text-end fw-semibold text-nowrap">${fmt(d.saldo)}</td>
          <td>
            <input type="number" class="form-control form-control-sm text-end input-pagar" min="0" step="any"
              inputmode="decimal" max="${num(d.saldo)}" disabled>
          </td>
        </tr>`;
    }).join(''));
  };

  const cargarDocumentosPendientes = async () => {
    showLoadingWithTetrisLogo('Consultando documentos pendientes...', 'ISA Software');
    try {
      const resp = await enviarPeticion({
        link: URL_MODELO,
        op: 'documentos-pendientes',
        id_tercero: $('#id_tercero').val()
      });
      if (resp?.error) throw new Error(resp.message);
      estado.documentosPendientes = Array.isArray(resp) ? resp : [];
      pintarDocumentos(estado.documentosPendientes);
      $('#panel-documentos').removeClass('d-none');
    } catch (e) {
      console.error('Error consultando documentos pendientes:', e);
      $('#chk-pagar-documentos').prop('checked', false);
      alerta('No se pudieron consultar los documentos pendientes del tercero.');
    } finally {
      dissminSwal();
      refrescarResumen();
    }
  };

  // Lleva el total aplicado a la caja del egreso
  const usarTotalAplicado = () => {
    const totalDocs = obtenerDocumentos().reduce((s, d) => s + d.valor, 0);
    if (totalDocs <= 0) return alerta('Seleccione al menos un documento a pagar.', 'info');
    if (totalDocs > estado.saldo) {
      return alerta(`El total a pagar (${fmt(totalDocs)}) supera el saldo en caja (${fmt(estado.saldo)}).`);
    }
    const $filas = filasCajas();
    if ($filas.length === 1) {
      $filas.attr('data-valor', totalDocs).find('.valor-caja').text(fmt(totalDocs));
      refrescarResumen();
    } else {
      $('#cant_efectivo').val(totalDocs);
      if (!$('#cuenta_caja').val()) $('#cuenta_caja').trigger('focus');
      else agregarCaja();
    }
  };

  /* =========================================================
   * Resumen lateral (total, saldo después y datos seleccionados)
   * ======================================================= */
  const refrescarResumen = () => {
    const tipo = tipoMov();
    const esEgreso = tipo === 'EGRESO';
    const cajas = obtenerCajas();
    const total = cajas.reduce((s, c) => s + c.valor, 0);
    const saldoDespues = esEgreso ? estado.saldo - total : estado.saldo + total;
    const excede = esEgreso && total > estado.saldo;

    $('.total-movimiento').text(fmt(total));
    $('#saldo-despues').text(fmt(saldoDespues)).toggleClass('text-danger', saldoDespues < 0);

    $('#res-movimiento')
      .text(esEgreso ? 'Egreso' : 'Ingreso')
      .toggleClass('bg-label-success', !esEgreso)
      .toggleClass('bg-label-danger', esEgreso);

    const $doc = $('#id_tipo_documento option:selected');
    const concepto = $('#id_concepto_caja option:selected').data('concepto');
    $('#res-documento').text($('#id_tipo_documento').val() ? $doc.text() : '—');
    $('#res-concepto').text(concepto || '—');
    $('#res-tercero').text($('#id_tercero').val() ? $('#tercero-nombre').text() : '—');
    $('#res-cajas').text(cajas.length);

    $('#cajas-vacio').toggleClass('d-none', cajas.length > 0);
    $('#msg-saldo-insuf').toggleClass('d-none', !excede);

    // Documentos aplicados: el valor en cajas debe ser igual al total aplicado
    const docs = esEgreso ? obtenerDocumentos() : [];
    const totalDocs = docs.reduce((s, d) => s + d.valor, 0);
    const diferencia = docs.length > 0 && Math.abs(totalDocs - total) > 0.009;

    $('#total-aplicado').text(fmt(totalDocs));
    $('#li-res-documentos').toggleClass('d-none', docs.length === 0);
    $('#res-documentos').text(`${docs.length} · ${fmt(totalDocs)}`);
    $('#msg-diferencia-docs')
      .toggleClass('d-none', !diferencia)
      .html(diferencia
        ? `<i class="fa-solid fa-circle-exclamation me-1"></i>El valor en cajas (${fmt(total)}) debe ser igual al total aplicado (${fmt(totalDocs)}).`
        : '');

    $('.btn-guardar').prop('disabled', excede || diferencia || estado.procesando);

    return total;
  };

  const agregarCaja = () => {
    const valor = num($('#cant_efectivo').val());
    const idCaja = $('#cuenta_caja').val();
    const textoCaja = $('#cuenta_caja option:selected').text();
    const esEgreso = tipoMov() === 'EGRESO';

    if (!idCaja) return alerta('Seleccione una caja.');
    if (valor <= 0) {
      $('#cant_efectivo').trigger('focus');
      return alerta('Ingrese un valor mayor a cero.');
    }

    const $existente = filasCajas().filter(`[data-id="${idCaja}"]`);

    if (esEgreso && filasCajas().length > 0 && $existente.length === 0) {
      return alerta('En un egreso solo se puede usar una caja. Elimine la actual para cambiarla.');
    }
    if (esEgreso && valor > estado.saldo) {
      return alerta(`El egreso no puede superar el saldo en caja (${fmt(estado.saldo)}).`);
    }

    if ($existente.length) {
      // Misma caja: se reemplaza el valor
      $existente.attr('data-valor', valor);
      $existente.find('.valor-caja').text(fmt(valor));
    } else {
      $('#tabla-cajas tbody').append(`
        <tr data-id="${escapeHtml(idCaja)}" data-valor="${valor}">
          <td class="text-truncate" style="max-width: 220px;">${escapeHtml(textoCaja)}</td>
          <td class="text-end fw-semibold text-nowrap valor-caja">${fmt(valor)}</td>
          <td class="text-center">
            <button type="button" class="btn btn-icon btn-xs btn-text-secondary btn-delete" title="Quitar">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </td>
        </tr>`);
    }

    $('#cant_efectivo').val('');
    // Si solo hay una caja se deja seleccionada para agilizar
    if ($('#cuenta_caja option[value!=""]').length > 1) $('#cuenta_caja').val('');
    refrescarResumen();
    if (!esMovil()) $('#cant_efectivo').trigger('focus');
  };

  /* =========================================================
   * Tercero
   * ======================================================= */
  const limpiarTercero = () => {
    $('#id_tercero, #id_direccion, #id_condicion, #zona_ventas, #zona_descripcion').val('');
    $('#txtBusquedaTercero').val('');
    $('#tercero-seleccionado').addClass('d-none').removeClass('d-flex');
    $('#grp-busqueda-tercero').removeClass('d-none');
    reiniciarDocumentos();
    refrescarResumen();
  };

  const seleccionarTercero = (item) => {
    $('#id_tercero').val(item.id);
    $('#id_direccion').val(item.id_direccion);
    $('#id_condicion').val(item.id_condicion);
    $('#zona_ventas').val(item.codigo_zona);
    $('#zona_descripcion').val(item.zona_descripcion);

    $('#tercero-nombre').text(item.label || item.razon_comercial || item.value || '');
    $('#tercero-nit').text(item.nit ? `NIT ${item.nit}` : '');
    $('#grp-busqueda-tercero').addClass('d-none');
    $('#tercero-seleccionado').removeClass('d-none').addClass('d-flex');
    reiniciarDocumentos();
    refrescarResumen();
  };

  /* =========================================================
   * Formulario
   * ======================================================= */
  const limpiarMovimientoCaja = ({ conservarDocumento = true } = {}) => {
    $('#FormCrear').removeClass('was-validated');

    filasCajas().remove();
    $('#cant_efectivo, #observaciones').val('');
    $('#obs-contador').text('0');

    // Se vuelven a pintar los catálogos desde caché (sin consultar el servidor)
    const cat = estado.cacheCatalogos[tipoMov()];
    const docActual = $('#id_tipo_documento').val();
    if (cat) pintarCatalogos(cat);
    if (conservarDocumento && docActual) $('#id_tipo_documento').val(docActual);

    if ($('#cuenta_caja option[value!=""]').length > 1) $('#cuenta_caja').val('');
    limpiarTercero(); // también refresca el resumen
  };

  const limpiarTemporales = async (idTransaccion) => {
    try {
      await Promise.all([
        enviarPeticion({ link: URL_SERVICIOS, op: 'delete_transaccion', id_transaccion: idTransaccion }),
        enviarPeticion({ link: URL_MODELO, op: 'delete_transaccion_caja_tmp', id_transaccion: idTransaccion })
      ]);
    } catch (e) {
      console.error('Error limpiando temporales:', e);
    }
  };

  const validarAntesDeGuardar = (data, cajas, total) => {
    if (!data.id_tercero) return 'Busque y seleccione un tercero de la lista.';
    if (!data.id_direccion) return 'El tercero seleccionado no tiene dirección asignada. Asígnele una dirección para continuar.';
    if (!$('#id_concepto_caja option:selected').data('concepto')) return 'Debe seleccionar un concepto de caja.';
    if (cajas.length === 0) return 'Agregue al menos una caja con su valor.';
    if (total <= 0) return 'El total del movimiento debe ser mayor a cero.';
    if (tipoMov() === 'EGRESO' && total > estado.saldo) {
      return `El egreso (${fmt(total)}) supera el saldo en caja (${fmt(estado.saldo)}).`;
    }
    if ($('#chk-pagar-documentos').is(':checked')) {
      const docs = obtenerDocumentos();
      const totalDocs = docs.reduce((s, d) => s + d.valor, 0);
      if (docs.length === 0) return 'Seleccione los documentos a pagar o desactive la opción "Pagar documentos pendientes".';
      if (Math.abs(totalDocs - total) > 0.009) {
        return `El valor en cajas (${fmt(total)}) debe ser igual al total aplicado a documentos (${fmt(totalDocs)}).`;
      }
    }
    return null;
  };

  const guardarMovimiento = async (data) => {
    if (estado.procesando) return;

    const tipoMovimiento = tipoMov();
    const esEgreso = tipoMovimiento === 'EGRESO';
    const sw = swMov(tipoMovimiento);
    const cajas = obtenerCajas();
    const total = cajas.reduce((s, c) => s + c.valor, 0);

    const error = validarAntesDeGuardar(data, cajas, total);
    if (error) return alerta(error);

    const concepto = $.trim($('#id_concepto_caja option:selected').data('concepto'));
    const tipoDoc = $('#id_tipo_documento option:selected').data('tipo')
      || $.trim($('#id_tipo_documento option:selected').text().split('-')[0]);
    const tercero = $('#tercero-nombre').text();
    const documentos = esEgreso && $('#chk-pagar-documentos').is(':checked') ? obtenerDocumentos() : [];
    const totalDocs = documentos.reduce((s, d) => s + d.valor, 0);
    const filaRes = (lbl, val) => `
      <div class="d-flex justify-content-between gap-3 border-bottom py-2">
        <span class="text-muted">${lbl}</span><b class="text-end">${escapeHtml(val)}</b>
      </div>`;

    // 1) Confirmar ANTES de crear registros temporales
    const confirm = await Swal.fire({
      title: `¿Registrar ${esEgreso ? 'egreso' : 'ingreso'}?`,
      html: `
        <div class="text-start small">
          ${filaRes('Tercero', tercero)}
          ${filaRes('Concepto', concepto)}
          ${documentos.length ? filaRes('Documentos', `${documentos.map((d) => d.etiqueta).join(', ')} · ${fmt(totalDocs)}`) : ''}
          ${filaRes('Cajas', cajas.length)}
          <div class="d-flex justify-content-between align-items-center pt-3">
            <span>Total</span><span class="fs-4 fw-bold text-primary">${fmt(total)}</span>
          </div>
        </div>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, registrar',
      cancelButtonText: 'Revisar',
      reverseButtons: true,
      focusConfirm: true
    });
    if (!confirm.isConfirmed) return;

    estado.procesando = true;
    $('.btn-guardar').prop('disabled', true);
    let idTmp = null;

    try {
      showLoadingWithTetrisLogo('Validando caja...', 'ISA Software');

      // 2) Revalidar apertura antes de escribir nada
      if (!(await validarAperturaCaja())) return;

      // 2.1) Revalidar saldos de los documentos antes de escribir nada
      if (documentos.length) {
        showLoadingWithTetrisLogo('Validando documentos...', 'ISA Software');
        const val = await enviarPeticion({
          link: URL_MODELO,
          op: 'validar-documentos',
          id_tercero: data.id_tercero,
          documentos: JSON.stringify(documentos)
        });
        if (val.error) {
          alerta(val.message || 'Los documentos seleccionados ya no tienen saldo suficiente.');
          if ($('#chk-pagar-documentos').is(':checked')) cargarDocumentosPendientes();
          return;
        }
      }

      showLoadingWithTetrisLogo('Guardando movimiento...', 'ISA Software');

      const docsTxt = documentos.length ? ` PAGO DE: ${documentos.map((d) => d.etiqueta).join(', ')}.` : '';
      const observaciones = `CONCEPTO: ${concepto}.${docsTxt} ${data.observaciones || ''}`.trim();

      // 3) Transacción temporal
      const datos = await enviarPeticion({
        link: URL_SERVICIOS,
        op: 'add_transacciones_tmp',
        id_tipo_documento: data.id_tipo_documento,
        id_tercero: data.id_tercero,
        id_bodega: data.id_bodega,
        notas: observaciones,
        valor_total: total,
        iva: 0,
        descuento: 0,
        sw,
        id_tercero_dir: data.id_direccion,
        id_condicion: data.id_condicion,
        zona_ventas: data.zona_ventas,
        zona_descripcion: data.zona_descripcion,
        id_concepto_caja: data.id_concepto_caja
      });

      if (!(datos.data?.numero && datos.data?.id)) {
        alerta('No se pudo generar la transacción temporal.');
        return;
      }
      idTmp = datos.data.id;

      // 4) Cajas y pagos asociados
      const respCaja = await enviarPeticion({
        link: URL_MODELO,
        op: 'add_transacciones_caja_tmp',
        id_transaccion: idTmp,
        numero: datos.data.numero,
        tipo: tipoDoc,
        cajas: JSON.stringify(cajas),
        sw
      });

      if (respCaja.error) {
        await limpiarTemporales(idTmp);
        idTmp = null;
        alerta(respCaja.message || 'Error al registrar las cajas.');
        return;
      }

      // 5) Documento definitivo
      showLoadingWithTetrisLogo('Generando documento...', 'ISA Software');
      const resp = await enviarPeticion({ link: URL_SERVICIOS, op: 'volcar_documento', id_transaccion: idTmp });

      if (resp.error) {
        alerta(resp.message || 'Ocurrió un error al generar el documento.');
        return;
      }
      idTmp = null; // ya no es temporal

      // 5.1) Aplicar el pago a los documentos (antes de contabilizar)
      let avisoDocs = '';
      if (documentos.length) {
        showLoadingWithTetrisLogo('Aplicando pago a documentos...', 'ISA Software');
        try {
          const respDocs = await enviarPeticion({
            link: URL_MODELO,
            op: 'aplicar-documentos',
            id_transaccion: resp.id,
            documentos: JSON.stringify(documentos)
          });
          if (respDocs.error) avisoDocs = respDocs.message || 'No se pudo aplicar el pago a los documentos.';
        } catch (e) {
          console.error('Error aplicando documentos:', e);
          avisoDocs = 'No se pudo aplicar el pago a los documentos.';
        }
      }

      // 6) Contabilización
      let txtCont = '';
      try {
        const respCont = await generarContabilizacion(resp.id);
        txtCont = respCont?.message ?? '';
      } catch (e) {
        txtCont = 'No se pudo contabilizar automáticamente. Revise el documento.';
        console.error('Error contabilizando:', e);
      }

      limpiarMovimientoCaja();
      estado.historialDesactualizado = true;
      saldoCaja();

      const r = await Swal.fire({
        title: `${esEgreso ? 'Egreso' : 'Ingreso'} registrado`,
        html: `
          <p class="mb-1">Documento <b>#${escapeHtml(resp.id)}</b> por <b>${fmt(total)}</b></p>
          ${documentos.length && !avisoDocs ? `<p class="small mb-1">Pago aplicado a: <b>${escapeHtml(documentos.map((d) => d.etiqueta).join(', '))}</b></p>` : ''}
          ${avisoDocs ? `<div class="alert alert-warning small py-1 px-2 text-start">${escapeHtml(avisoDocs)} El egreso quedó registrado sin cruzar; aplíquelo desde Egresos y Pagos.</div>` : ''}
          ${txtCont ? `<p class="small text-muted mb-0"><b>Contabilización:</b> ${escapeHtml(txtCont)}</p>` : ''}`,
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-print me-1"></i> Imprimir',
        cancelButtonText: 'Cerrar',
        reverseButtons: true
      });
      if (r.isConfirmed) await ImprimirDocumento(sw, resp.id, 'Impresión de documento', false);

    } catch (e) {
      console.error('Error guardando movimiento:', e);
      if (idTmp) await limpiarTemporales(idTmp);
      alerta('Ocurrió un problema al procesar la transacción.');
    } finally {
      estado.procesando = false;
      refrescarResumen();
    }
  };

  /* =========================================================
   * Historial
   * ======================================================= */
  const pintarKpis = (lista) => {
    let ingresos = 0, egresos = 0, nIng = 0, nEgr = 0;
    lista.forEach((m) => {
      const v = Math.abs(num(m.valor_total));
      if (m.sw == 10) { ingresos += v; nIng++; } else { egresos += v; nEgr++; }
    });
    $('#kpi-ingresos').text(fmt(ingresos));
    $('#kpi-egresos').text(fmt(egresos));
    $('#kpi-neto').text(fmt(ingresos - egresos));
    $('#kpi-cant-ingresos').text(`${nIng} mov.`);
    $('#kpi-cant-egresos').text(`${nEgr} mov.`);
    $('#kpi-cantidad').text(lista.length);
    $('#kpis-historial').removeClass('d-none');
    $('#badge-resultados').text(lista.length).removeClass('d-none');
  };

  const pintarTarjetasMovil = (lista) => {
    const html = lista.map((m, i) => {
      const ingreso = m.sw == 10;
      return `
        <div class="card shadow-none border mb-2">
          <div class="card-body p-2 px-3 small">
            <div class="d-flex align-items-start gap-2">
              <div class="avatar avatar-sm flex-shrink-0">
                <span class="avatar-initial rounded ${ingreso ? 'bg-label-success' : 'bg-label-danger'}">
                  <i class="fa-solid ${ingreso ? 'fa-arrow-down' : 'fa-arrow-up'}"></i>
                </span>
              </div>
              <div class="flex-grow-1 text-truncate">
                <span class="fw-semibold d-block text-truncate">${escapeHtml(m.tercero)}</span>
                <small class="text-muted d-block">${escapeHtml(m.tipo)} #${escapeHtml(m.numero)}</small>
                <small class="text-muted d-block">${escapeHtml(m.fecha_hora)}</small>
              </div>
              <div class="text-end flex-shrink-0">
                <span class="fw-bold d-block text-nowrap ${ingreso ? 'text-success' : 'text-danger'}">
                  ${ingreso ? '+' : '-'}${fmt(Math.abs(num(m.valor_total)))}
                </span>
                <button type="button" class="btn btn-icon btn-xs btn-outline-primary mt-1 btn-imprimir-card" data-index="${i}" title="Imprimir">
                  <i class="fa fa-print"></i>
                </button>
              </div>
            </div>
            ${m.notas ? `<small class="d-block text-muted mt-1 text-truncate">${escapeHtml(m.notas)}</small>` : ''}
          </div>
        </div>`;
    }).join('');
    $('#result').html(html);
  };

  const pintarTablaEscritorio = (lista) => {
    $('#result').html(`
      <div class="table-responsive">
        <table id="tabla-ingresos-egresos" class="table table-sm table-hover">
          <thead class="table-primary"></thead>
        </table>
      </div>`);

    createDataTable(
      'tabla-ingresos-egresos',
      lista,
      ['id_transaccion', 'fecha_hora', 'movimiento', 'tipo', 'numero', 'tercero', 'valor_total'],
      ['Id', 'Fecha', 'Movimiento', 'Tipo', 'Número', 'Tercero', 'Valor'],
      false,
      false,
      true,
      '<button class="other-btn btn btn-xs btn-icon btn-outline-primary" title="Imprimir"><i class="fa fa-print"></i></button>',
      imprimirFila,
      true,
      ['valor_total']
    );
  };

  const listarMovimientos = async () => {
    const fecha_inicial = $('#fecha_inicial').val();
    const fecha_final = $('#fecha_final').val();

    if (!fecha_inicial || !fecha_final) return alerta('Seleccione el rango de fechas.', 'info');
    if (fecha_inicial > fecha_final) return alerta('La fecha inicial no puede ser mayor a la final.', 'info');

    showLoadingWithTetrisLogo('Consultando movimientos...', 'ISA Software');
    try {
      const resp = await enviarPeticion({
        link: URL_MODELO,
        op: 'listar-ingresos-egresos',
        fecha_inicial,
        fecha_final,
        sw: $('#filtro_tipo').val()
      });

      estado.movimientos = (Array.isArray(resp) ? resp : []).map((m) => ({
        ...m,
        movimiento: m.sw == 10 ? 'INGRESO' : 'EGRESO'
      }));
      estado.historialDesactualizado = false;

      if (estado.movimientos.length === 0) {
        $('#kpis-historial, #badge-resultados').addClass('d-none');
        $('#result').html(ShowAlert('No se encontraron movimientos en el rango seleccionado', 'warning'));
        return;
      }

      pintarKpis(estado.movimientos);
      esMovil() ? pintarTarjetasMovil(estado.movimientos) : pintarTablaEscritorio(estado.movimientos);
    } catch (e) {
      console.error(e);
      $('#result').html(ShowAlert('Error interno de la aplicación', 'error'));
    } finally {
      dissminSwal();
    }
  };

  const imprimirFila = async (data) => {
    await ImprimirDocumento(data.sw, data.id_transaccion, 'Impresión de documento', false);
  };

  /* =========================================================
   * Eventos
   * ======================================================= */
  const registrarEventos = () => {
    // Cambio de tipo de movimiento: catálogos desde caché
    $('input[name="btnradio"]').on('change', async function () {
      filasCajas().remove();
      reiniciarDocumentos();
      refrescarResumen();
      try {
        await cargarCatalogosMovimiento(tipoMov());
      } catch (e) {
        console.error('Error cargando catálogos:', e);
      }
    });

    $('#id_tipo_documento, #id_concepto_caja').on('change', refrescarResumen);
    $('.btn-limpiar').on('click', () => limpiarMovimientoCaja({ conservarDocumento: false }));
    $('#btn-refrescar-saldo').on('click', saldoCaja);

    // Historial
    $('#btn-buscar').on('click', listarMovimientos);
    $('#tab-historial').on('shown.bs.tab', () => {
      if (estado.historialDesactualizado) listarMovimientos();
    });
    $('#result').on('click', '.btn-imprimir-card', function () {
      const mov = estado.movimientos[$(this).data('index')];
      if (mov) imprimirFila(mov);
    });

    // Cajas
    $('#btn-add-caja').on('click', agregarCaja);
    $('#cant_efectivo').on('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); agregarCaja(); }
    });
    $('#montos-rapidos').on('click', '[data-monto]', function () {
      const actual = num($('#cant_efectivo').val());
      $('#cant_efectivo').val(actual + num($(this).data('monto')));
    });
    $('#tabla-cajas').on('click', '.btn-delete', function () {
      $(this).closest('[data-id]').remove();
      refrescarResumen();
    });

    // Documentos pendientes (egresos)
    $('#chk-pagar-documentos').on('change', function () {
      if (this.checked) {
        cargarDocumentosPendientes();
      } else {
        $('#panel-documentos').addClass('d-none');
        $('#tabla-documentos tbody').empty();
        $('#chk-todos-docs').prop('checked', false);
        refrescarResumen();
      }
    });
    $('#tabla-documentos').on('change', '.chk-doc', function () {
      const $tr = $(this).closest('tr');
      const $input = $tr.find('.input-pagar');
      $input.prop('disabled', !this.checked).val(this.checked ? num($tr.data('saldo')) : '');
      $('#chk-todos-docs').prop('checked', filasDocumentos().length > 0 && filasDocumentos().find('.chk-doc:not(:checked)').length === 0);
      refrescarResumen();
    });
    $('#chk-todos-docs').on('change', function () {
      filasDocumentos().not('.d-none').find('.chk-doc').prop('checked', this.checked).trigger('change');
    });
    $('#tabla-documentos').on('input', '.input-pagar', refrescarResumen);
    $('#tabla-documentos').on('change', '.input-pagar', function () {
      const saldo = num($(this).closest('tr').data('saldo'));
      const valor = num(this.value);
      if (valor > saldo) {
        this.value = saldo;
        alerta(`El valor a pagar no puede superar el saldo del documento (${fmt(saldo)}).`, 'info');
      } else if (valor <= 0) {
        $(this).closest('tr').find('.chk-doc').prop('checked', false).trigger('change');
        return;
      }
      refrescarResumen();
    });
    $('#btn-usar-aplicado').on('click', usarTotalAplicado);

    // Filtro rápido de documentos (no afecta los ya marcados)
    $('#filtro-documentos').on('input', function () {
      const txt = $.trim(this.value).toUpperCase();
      filasDocumentos().each(function () {
        const coincide = !txt || String($(this).data('etiqueta')).toUpperCase().includes(txt);
        $(this).toggleClass('d-none', !coincide);
      });
    });

    // Tercero
    $('#txtBusquedaTercero').autocomplete({
      minLength: 3,
      delay: 350,
      source: async (request, response) => {
        try {
          const resp = await enviarPeticion({
            link: '../models/Terceros.php',
            filtro: $.trim(request.term),
            tipo_tercero: '',
            estado: 'A',
            op: 's_tercero'
          });
          response(Array.isArray(resp.data) ? resp.data : []);
        } catch (error) {
          console.error('Error buscando tercero:', error);
          response([]);
        }
      },
      select: (event, ui) => {
        event.preventDefault();
        const item = ui.item;
        if (!item.id_direccion) {
          alerta('El tercero seleccionado no tiene dirección asignada. Asígnele una dirección para continuar.');
          limpiarTercero();
          return;
        }
        seleccionarTercero(item);
        if (!esMovil()) $('#cuenta_caja').trigger('focus');
      }
    });
    $('#btn-quitar-tercero').on('click', () => {
      limpiarTercero();
      $('#txtBusquedaTercero').trigger('focus');
    });

    // Observaciones
    $('#observaciones').on('input', function () {
      $('#obs-contador').text(this.value.length);
    });

    // Atajo F9 = guardar
    $(document).on('keydown', (e) => {
      if (e.key === 'F9' && $('#navs-creacion').hasClass('active')) {
        e.preventDefault();
        $('#FormCrear').trigger('submit');
      }
    });

    // Envío del formulario
    $('#FormCrear').on('submit', function (event) {
      event.preventDefault();
      const form = this;

      $('#observaciones').val($.trim($('#observaciones').val()).toUpperCase());

      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        const $primero = $(form).find(':invalid').first();
        $primero.trigger('focus');
        $primero[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      const data = formatearArrayRequest($(form).serializeArray());
      guardarMovimiento(data);
    });
  };

  /* =========================================================
   * Inicio
   * ======================================================= */
  $(async function () {
    try {
      window.permisos = await permisosUsuario(id_modulo);
      if (!(await validarAperturaCaja())) return;

      fechaActual([['fecha_inicial', 'fecha_final']]);
      registrarEventos();

      // Peticiones independientes en paralelo
      await Promise.all([saldoCaja(), cargarInicial(), listarCajas()]);
    } catch (e) {
      console.error('Error iniciando el módulo:', e);
      alerta('No se pudo cargar la información del módulo. Recargue la página.');
    } finally {
      dissminSwal();
    }
  });
})();