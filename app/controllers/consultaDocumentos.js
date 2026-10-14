/* =====================================================================
   CONSULTA DOCUMENTOS — controlador
   ===================================================================== */
const CD_MODEL = '../models/consultaDocumentos.php';
const CD_MARCA = 'ISA Software';
const CD_SW_PAGOS = ['9', '10'];
const CD_CONF_CONTABLE = { NumeroDecimales: 2 };

const CD = {
  doc: null,
  cruces: [],
  crucesHistoricos: false,
  cargando: false,
  cacheTipos: new Map(),
  cacheMedios: null,
  cacheCuentasBanco: null
};

/* =========================
   UTILIDADES
========================== */
const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));
const num = (v) => {
  const n = parseFloat(v);
  return Number.isFinite(n) ? n : 0;
};
const money = (v, conf) => (conf ? formatNum(num(v), '$', conf) : formatNum(num(v), '$'));
const cant = (v) => num(v).toLocaleString('es-CO', { maximumFractionDigits: 2 });
const loader = (msg) => showLoadingWithTetrisLogo(msg, CD_MARCA);

const alerta = (msg, tipo = 'warning', icono = 'fa-regular fa-bell') =>
  `<div class="alert alert-${tipo} py-2 mt-3 mb-0 small" role="alert"><i class="${icono} me-2"></i>${msg}</div>`;

const vacio = (msg) =>
  `<div class="text-center text-muted small py-4">
     <i class="fa-regular fa-file-lines fa-lg d-block mb-2"></i>${msg}
   </div>`;

const mostrarTab = (target) => {
  const btn = document.querySelector(`#tabs button[data-bs-target="#${target}"]`);
  if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
};

const setContador = (clave, n) => $(`#cnt-${clave}`).text(n || '').toggleClass('d-none', !n);

const setBadge = (sel, clase = '', html = '') =>
  $(sel).attr('class', `badge ${clase}`).toggleClass('d-none', !html).html(html);

const setAcciones = () => {
  const hay = !!CD.doc;
  const anulado = hay && CD.doc.anulado == 1;
  $('#btn-print, #btn-whatsapp, #btn-correo').prop('disabled', !hay);
  $('#btn-anular').prop('disabled', !hay || anulado);
};

/* =========================
   CATÁLOGOS
========================== */
const cargarSw = async () => {
  const resp = await enviarPeticion({ link: '../shared/servicios/servicios.php', op: 'cargar_sw' });
  const data = resp?.data || [];
  $('#sw').html(data.map((e) => `<option value="${esc(e.sw)}">${esc(e.sw)} - ${esc(e.descripcion)}</option>`).join(''));
};

const listarAlmacenes = async () => {
  const resp = await enviarPeticion({ link: '../models/almacenes.php', filtro: '', op: 'listar' });
  const data = resp?.data || [];
  if (!data.length) {
    ShowAlert('No se encontraron bodegas', 'warning');
    return;
  }
  $('#bodega').html(data.map((d) => `<option value="${esc(d.id)}">${esc(d.almacen)} - ${esc(d.descripcion)}</option>`).join(''));
};

/** Carga tipos con caché por sw|bodega. Si se indica tipoDeseado lo deja seleccionado. */
const cargarTipos = async (tipoDeseado = null) => {
  const sw = $('#sw').val();
  const bodega = $('#bodega').val();
  if (!sw || !bodega) {
    $('#tipo').html('');
    return;
  }
  const key = `${sw}|${bodega}`;
  let data = CD.cacheTipos.get(key);
  if (!data) {
    const resp = await enviarPeticion({ link: '../models/tiposDocumentos.php', op: 'listar_sw', sw, bodega });
    data = resp?.data || [];
    CD.cacheTipos.set(key, data);
  }
  const actual = tipoDeseado ?? $('#tipo').val();
  $('#tipo').html(data.map((e) => `<option value="${esc(e.tipo)}">${esc(e.tipo)} - ${esc(e.descripcion)}</option>`).join(''));

  if (actual && data.some((e) => String(e.tipo) === String(actual))) {
    $('#tipo').val(String(actual));
  } else if (tipoDeseado) {
    $('#tipo').append(`<option value="${esc(tipoDeseado)}">${esc(tipoDeseado)}</option>`).val(String(tipoDeseado));
  }
};

/** Deja bodega / sw / tipo / número alineados con el documento cargado. */
const sincronizarFiltros = async (d) => {
  if (d.id_bodega != null && $(`#bodega option[value="${d.id_bodega}"]`).length) $('#bodega').val(String(d.id_bodega));
  if (d.sw != null && $(`#sw option[value="${d.sw}"]`).length) $('#sw').val(String(d.sw));
  await cargarTipos(d.tipo);
  $('#numero').val(d.numero);
};

/* =========================
   CARGA DEL DOCUMENTO (1 sola petición)
========================== */
const cargarDocumento = async (tipo, numero, { mostrarBasicos = true } = {}) => {
  tipo = String(tipo ?? '').trim();
  numero = String(numero ?? '').trim();

  if (!tipo || !numero) {
    Swal.fire('Campos vacíos', 'Seleccione el tipo de documento e ingrese el número.', 'warning');
    return;
  }
  if (!/^\d+$/.test(numero)) {
    Swal.fire('Número inválido', 'El número del documento debe ser numérico.', 'warning');
    return;
  }
  if (CD.cargando) return;

  CD.cargando = true;
  loader('Cargando documento');

  try {
    const resp = await enviarPeticion({ link: CD_MODEL, op: 'documento-completo', tipo, numero });

    if (!resp?.success) {
      Swal.fire('Sin resultados', resp?.message || 'No hay registros para los datos seleccionados.', 'info');
      return;
    }

    const { encabezado: d, detalle, contable, medios_pago, cruces, cruces_historicos } = resp.data;
    CD.doc = d;
    CD.cruces = cruces || [];
    CD.crucesHistoricos = !!cruces_historicos;

    await sincronizarFiltros(d);
    renderEncabezado(d);
    renderValores(d);
    renderDetalle(detalle || []);
    renderContable(contable || []);
    renderMediosPago(medios_pago || []);
    renderCruces();

    $('.insearch').prop('disabled', true);
    setAcciones();
    if (mostrarBasicos) mostrarTab('navs-basicos');

    dissminSwal();
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Ocurrió un problema al cargar el documento.', 'error');
  } finally {
    CD.cargando = false;
  }
};

/* =========================
   BÁSICOS
========================== */
const renderEncabezado = (d) => {
  $('#doc-id').val(d.id);
  $('#doc-nit').val(d.nit);
  $('#doc-nombres').val(d.tratamiento);
  $('#doc-razon').val(d.razon_comercial);
  $('#doc-email').val(d.email_fe || '');
  $('#doc-celular').val(d.celular || '');
  $('#doc-fecha').val(d.fecha_hora);
  $('#doc-usuario').val(d.usuario);
  $('#doc-referencia').val(d.documento || '');
  $('#doc-valor').val(money(d.valor_total));
  $('#doc-valor-aplicado').val(money(d.valor_aplicado));
  $('#doc-vencimiento').val(d.fecha_vencimiento || '');
  $('#doc-condicion').val([d.condicion, d.descripcion].filter((x) => x !== null && x !== '').join(' - '));
  $('#doc-condicion-dias').val(d.dias ?? '');
  $('#doc-condicion-dias-gracia').val(d.dias_gracia ?? '');
  $('#doc-notas').val(d.notas || '');
  renderEstados(d);
};

const renderEstados = (d) => {
  setBadge('#status-pago');
  setBadge('#status-vencimiento');
  $('#txt-status').text('');

  if (d.anulado == 1) {
    setBadge('#status', 'bg-danger', '<i class="fa-solid fa-ban me-1"></i> Anulado');
    let txt = `Anulado el ${d.fecha_anulacion || '-'}`;
    if (d.usuario_anulacion) txt += ` por ${d.usuario_anulacion}`;
    if (d.motivo_anulacion) txt += ` · Motivo: ${d.motivo_anulacion}`;
    $('#txt-status').text(txt);
    return;
  }

  setBadge('#status', 'bg-success', '<i class="fa-solid fa-circle-check me-1"></i> Activo');
  if (d.nomina === 'S') {
    $('#txt-status').text(String(d.sw) === '9'
      ? 'Egreso de pago de nómina: su contabilización la genera el módulo de nómina.'
      : 'Causación de nómina: su contabilización la genera el módulo de nómina.');
  }

  // Recibos / egresos: lo relevante es si están cruzados
  if (CD_SW_PAGOS.includes(String(d.sw))) {
    const n = CD.cruces.length;
    setBadge(
      '#status-pago',
      n ? 'bg-label-primary' : 'bg-label-secondary',
      n ? `<i class="fa-solid fa-arrow-right-arrow-left me-1"></i> ${n} cruce(s)` : 'Sin cruces'
    );
    return;
  }

  const total = num(d.valor_total);
  const aplicado = num(d.valor_aplicado);
  if (total <= 0) return;

  if (aplicado <= 0) {
    setBadge('#status-pago', 'bg-danger', '<i class="fa-solid fa-circle-xmark me-1"></i> Pendiente de pago');
  } else if (aplicado < total) {
    setBadge('#status-pago', 'bg-warning', '<i class="fa-solid fa-circle-exclamation me-1"></i> Pago parcial');
  } else {
    setBadge('#status-pago', 'bg-success', '<i class="fa-solid fa-circle-check me-1"></i> Pagado');
    setBadge('#status-vencimiento', 'bg-label-success', 'Al día');
    return;
  }

  if (d.dias_para_vencer === null || d.dias_para_vencer === undefined || d.dias_para_vencer === '') return;
  const dias = parseInt(d.dias_para_vencer, 10);

  if (dias < 0) {
    setBadge('#status-vencimiento', 'bg-danger', `<i class="fa-solid fa-triangle-exclamation me-1"></i> Vencido hace ${Math.abs(dias)} día(s)`);
  } else if (dias === 0) {
    setBadge('#status-vencimiento', 'bg-warning', '<i class="fa-solid fa-clock me-1"></i> Vence hoy');
  } else {
    setBadge('#status-vencimiento', 'bg-label-primary', `<i class="fa-solid fa-calendar-days me-1"></i> Vence en ${dias} día(s)`);
  }
};

/* =========================
   VALORES
========================== */
const renderValores = (d) => {
  const valorDoc = num(d.valor_documento); // t.valor_total tal cual
  const descuento = num(d.descuento);
  const iva = num(d.iva);
  const impoconsumo = num(d.impoconsumo);
  const ibua = num(d.ibua);
  const icui = num(d.icui);
  const retFuente = num(d.retencion_fuente);
  const retIca = num(d.retencion_ica);
  const retIva = num(d.retencion_iva);
  const retCree = num(d.retencion_cree);

  const subtotal = valorDoc + descuento - iva - impoconsumo - ibua - icui;
  const totalRet = retFuente + retIca + retIva + retCree;

  $('#doc-subtotal').val(money(subtotal));
  $('#doc-descuento').val(money(descuento));
  $('#doc-iva').val(money(iva));
  $('#doc-impoconsumo').val(money(impoconsumo));
  $('#doc-ibua').val(money(ibua));
  $('#doc-icui').val(money(icui));
  $('#doc-total-bruto').val(money(valorDoc));

  $('#doc-ret-fuente').val(money(retFuente));
  $('#doc-ret-ica').val(money(retIca));
  $('#doc-ret-iva').val(money(retIva));
  $('#doc-ret-cree').val(money(retCree));
  $('#doc-total-ret').val(money(totalRet));
  // Mismo valor que usa el estado de pago (antes se restaban dos veces las retenciones)
  $('#doc-neto-pagar').val(money(d.valor_total));
};

/* =========================
   DETALLE
========================== */
const renderDetalle = (rows) => {
  setContador('detalle', rows.length);

  if (!rows.length) {
    $('#navs-detalle').html(alerta(
      CD.doc?.anulado == 1
        ? 'El documento está anulado; su detalle se retiró al anularlo.'
        : 'El documento no tiene detalle de productos.'
    ));
    return;
  }

  let total = 0;
  const body = rows.map((r) => {
    total += num(r.costo_total);
    return `
      <tr class="table-light">
        <td colspan="9" class="small">
          <b>${esc(r.codigo)} - ${esc(r.descripcion)}</b>
          ${r.marca ? `<span class="text-primary ms-1">· ${esc(r.marca)}</span>` : ''}
        </td>
      </tr>
      <tr>
        <td class="text-end">${money(r.costo_unitario)}</td>
        <td>${esc(r.lote)}</td>
        <td>${esc(r.vmcto)}</td>
        <td class="text-end">${cant(r.cantidad)}</td>
        <td>${esc(r.und)}</td>
        <td class="text-end">${cant(r.cantidad_und)}</td>
        <td class="text-end">${cant(r.pcj_descuento)}</td>
        <td class="text-end">${cant(r.pcj_iva)}</td>
        <td class="text-end">${money(r.costo_total)}</td>
      </tr>`;
  }).join('');

  $('#navs-detalle').html(`
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-primary">
          <tr>
            <th class="text-end">Costo unit.</th>
            <th>Lote</th>
            <th>Vencimiento</th>
            <th class="text-end">Cantidad</th>
            <th>Unidad</th>
            <th class="text-end">Cant. (Und)</th>
            <th class="text-end">% Desc.</th>
            <th class="text-end">% IVA</th>
            <th class="text-end">Costo total</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
        <tfoot class="table-primary">
          <tr>
            <th colspan="8" class="text-end">Total</th>
            <th class="text-end">${money(total)}</th>
          </tr>
        </tfoot>
      </table>
    </div>`);
};

/* =========================
   CONTABLE
========================== */
const renderContable = (rows) => {
  const anulado = CD.doc?.anulado == 1;
  setContador('contable', rows.length);

  let html = '';

  if (!rows.length) {
    html = alerta(
      anulado
        ? 'El documento está anulado: su movimiento contable fue eliminado.'
        : 'No se encontró <strong>movimiento contable</strong> para esta transacción. Puede generarlo con el botón inferior.',
      'info',
      'fa-solid fa-circle-info'
    );
  } else {
    let deb = 0;
    let cre = 0;

    const body = rows.map((r) => {
      deb += num(r.debito);
      cre += num(r.credito);
      const txt = `${r.cuenta} - ${r.cuenta_desc}`;
      const celda = anulado
        ? esc(txt)
        : `<select class="form-select form-select-sm cuenta-contable"
                   data-id="${esc(r.id)}" data-prev="${esc(r.cuenta)}" data-prev-text="${esc(txt)}">
             <option value="${esc(r.cuenta)}" selected>${esc(txt)}</option>
           </select>`;
      return `
        <tr>
          <td class="text-nowrap">${esc(r.fecha_hora)}</td>
          <td>${esc(r.tipo)}</td>
          <td>${esc(r.numero)}</td>
          <td>${esc(r.nit)}</td>
          <td>${celda}</td>
          <td class="text-end">${money(r.debito, CD_CONF_CONTABLE)}</td>
          <td class="text-end">${money(r.credito, CD_CONF_CONTABLE)}</td>
        </tr>`;
    }).join('');

    const dif = Math.round((deb - cre) * 100) / 100;
    if (dif !== 0) {
      html += alerta(
        `Movimiento descuadrado: diferencia de <strong>${money(Math.abs(dif), CD_CONF_CONTABLE)}</strong> entre débitos y créditos.`,
        'warning',
        'fa-solid fa-scale-unbalanced'
      );
    }

    html += `
      <div class="table-responsive mt-3">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-primary">
            <tr>
              <th>Fecha/Hora</th>
              <th>Tipo</th>
              <th>Número</th>
              <th>NIT</th>
              <th style="min-width:300px">Cuenta contable</th>
              <th class="text-end">Débito</th>
              <th class="text-end">Crédito</th>
            </tr>
          </thead>
          <tbody>${body}</tbody>
          <tfoot class="table-primary">
            <tr>
              <th colspan="5" class="text-end">Totales</th>
              <th class="text-end">${money(deb, CD_CONF_CONTABLE)}</th>
              <th class="text-end">${money(cre, CD_CONF_CONTABLE)}</th>
            </tr>
          </tfoot>
        </table>
      </div>`;
  }

  html += `
    <div class="mt-3 text-end">
      <button class="btn btn-sm btn-outline-primary" id="btn-generar-contable" ${anulado ? 'disabled' : ''}>
        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Generar movimiento
      </button>
    </div>`;

  $('#navs-contable').html(html);
  if (!anulado && rows.length) inicializarAutocompleteCuentas();
};

const revertirCuenta = ($s) => {
  const opt = new Option($s.data('prev-text'), $s.data('prev'), true, true);
  $s.empty().append(opt).trigger('change.select2');
};

const inicializarAutocompleteCuentas = () => {
  const $sel = $('#navs-contable .cuenta-contable');

  $sel.select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Buscar cuenta...',
    minimumInputLength: 2,
    language: {
      inputTooShort: () => 'Ingrese al menos 2 caracteres.',
      searching: () => 'Buscando...',
      noResults: () => 'No se encontraron resultados.',
      errorLoading: () => 'No fue posible cargar los resultados.'
    },
    ajax: {
      url: '../models/cuentasContables.php',
      dataType: 'json',
      delay: 300,
      type: 'POST',
      data: (params) => ({ op: 'buscar-cuenta', buscar: params.term }),
      processResults: (data) => data
    }
  });

  $sel.on('select2:select', async function (e) {
    const $s = $(this);
    const cuenta = e.params.data.cuenta ?? e.params.data.id;
    const texto = e.params.data.text;
    try {
      const resp = await enviarPeticion({ link: CD_MODEL, op: 'update-movimiento-cuenta', id: $s.data('id'), cuenta });
      if (resp?.success) {
        $s.data('prev', cuenta).data('prev-text', resp.data?.texto || texto);
        initToast('bg-success', 'Excelente!', resp.message, 'success');
      } else {
        revertirCuenta($s);
        initToast('bg-danger', 'Error', resp?.message || 'No fue posible actualizar la cuenta.', 'error');
      }
    } catch (err) {
      console.error(err);
      revertirCuenta($s);
      initToast('bg-danger', 'Error', 'No fue posible actualizar la cuenta.', 'error');
    }
  });
};

const recargarContable = async () => {
  if (!CD.doc) return;
  const resp = await enviarPeticion({ link: CD_MODEL, op: 'documento-contable', id: CD.doc.id });
  renderContable(resp?.data || []);
};

const regenerarContable = async ({ confirmar = true } = {}) => {
  if (!CD.doc) return;
  if (CD.doc.anulado == 1) {
    Swal.fire({ icon: 'warning', title: 'Documento anulado', text: 'No es posible generar la contabilización de un documento anulado.' });
    return;
  }

  if (confirmar) {
    const esNomina = CD.doc.nomina === 'S';
    const c = await Swal.fire({
      icon: 'question',
      title: '¿Generar movimiento contable?',
      html: (esNomina
        ? `Documento de <strong>nómina</strong>: el movimiento se regenera con las cuentas del
           <strong>perfil contable de nómina</strong> y los datos de la liquidación${String(CD.doc.sw) === '9' ? ' y sus cruces' : ''}.`
        : `Se volverá a generar el movimiento contable con base en los
           <strong>parámetros de contabilización configurados</strong>.`) + `<br><br>
             <span class="text-danger">Si el movimiento fue modificado manualmente, esos cambios se reemplazarán.</span>`,
      showCancelButton: true,
      confirmButtonText: 'Sí, regenerar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });
    if (!c.isConfirmed) return;
  }

  loader('Generando contabilización');
  try {
    // Documentos de nómina: se llama directo al modelo para poder enviar la cuenta de banco del egreso
    let resp = CD.doc.nomina === 'S'
      ? await enviarPeticion({ link: CD_MODEL, op: 'generar-contabilidad', id: CD.doc.id })
      : await generarContabilizacion(CD.doc.id);

    if (!resp?.success && resp?.requiere_cuenta) {
      dissminSwal();
      const cuenta = await pedirCuentaBanco(resp.message);
      if (!cuenta) return;
      loader('Generando contabilización');
      resp = await enviarPeticion({ link: CD_MODEL, op: 'generar-contabilidad', id: CD.doc.id, cuenta_banco: cuenta });
    }

    if (resp?.success) {
      await recargarContable();
      Swal.fire({ icon: 'success', title: 'Contabilidad generada', text: resp.message, timer: 2500, showConfirmButton: false });
    } else {
      Swal.fire({ icon: 'warning', title: 'Atención', text: resp?.message || 'No fue posible generar la contabilización.' });
    }
  } catch (e) {
    console.error(e);
    Swal.fire({ icon: 'error', title: 'Error', text: e.message });
  }
};
/** Solicita la cuenta de caja o banco (11xx) de un egreso de nómina que no la tiene registrada */
const pedirCuentaBanco = async (mensaje) => {
  if (!CD.cacheCuentasBanco) {
    loader('Cargando cuentas de caja y bancos');
    const r = await enviarPeticion({ link: '../models/cuentasContables.php', op: 'listar-todos' });
    CD.cacheCuentasBanco = (r?.data || []).filter((c) => /^11/.test(String(c.cuenta)));
    dissminSwal();
  }
  if (!CD.cacheCuentasBanco.length) {
    Swal.fire('Sin cuentas', 'No hay cuentas de caja o bancos (11xx) en el plan de cuentas.', 'warning');
    return null;
  }
  const opciones = CD.cacheCuentasBanco
    .map((c) => `<option value="${esc(c.cuenta)}">${esc(c.cuenta)} - ${esc(c.descripcion)}</option>`).join('');
  const { value } = await Swal.fire({
    icon: 'question',
    title: 'Cuenta de pago del egreso',
    html: `<div class="text-start small mb-2">${esc(mensaje)}</div>
           <select id="scl-cuenta-banco" class="form-select form-select-sm"><option value="">Seleccione...</option>${opciones}</select>
           <div class="text-start small text-muted mt-2">Quedará guardada para las próximas regeneraciones.</div>`,
    showCancelButton: true,
    confirmButtonText: 'Regenerar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusConfirm: false,
    preConfirm: () => {
      const v = document.getElementById('scl-cuenta-banco').value;
      if (!v) {
        Swal.showValidationMessage('Seleccione la cuenta de caja o banco.');
        return false;
      }
      return v;
    }
  });
  return value || null;
};

// Alias por compatibilidad con llamadas existentes
const generarMovimientoContable = () => regenerarContable();

/* =========================
   MEDIOS DE PAGO
========================== */
const renderMediosPago = (rows) => {
  setContador('medios', rows.length);

  if (!rows.length) {
    $('#navs-medios-pago').html(alerta('El documento no registra medios de pago.'));
    return;
  }

  const anulado = CD.doc?.anulado == 1;
  let total = 0;
  const body = rows.map((r) => {
    total += num(r.valor);
    return `
      <tr>
        <td>${esc(r.id)}</td>
        <td>${esc(r.id_medio_pago)} - ${esc(r.medio_pago)}</td>
        <td>${esc(r.cuenta_medio_pago)}</td>
        <td>${esc(r.voucher)}</td>
        <td class="text-end">${money(r.valor)}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-icon btn-outline-primary js-cambiar-medio"
                  data-id="${esc(r.id)}" data-medio="${esc(r.id_medio_pago)}"
                  title="Cambiar medio de pago" ${anulado ? 'disabled' : ''}>
            <i class="fa-solid fa-pen"></i>
          </button>
        </td>
      </tr>`;
  }).join('');

  $('#navs-medios-pago').html(`
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Medio de pago</th>
            <th>Cuenta</th>
            <th>Voucher</th>
            <th class="text-end">Valor</th>
            <th class="text-center">Cambiar</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
        <tfoot class="table-primary">
          <tr>
            <th colspan="4" class="text-end">Total</th>
            <th class="text-end">${money(total)}</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>`);
};

const cambiarMedioPago = async (idPago, idMedioActual) => {
  if (!CD.doc || CD.doc.anulado == 1) return;

  try {
    if (!CD.cacheMedios) {
      loader('Cargando medios de pago');
      const r = await enviarPeticion({ link: '../models/MediosPagos.php', op: 'listar' });
      CD.cacheMedios = r?.data || [];
      dissminSwal();
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'No fue posible cargar los medios de pago.', 'error');
    return;
  }

  const opciones = CD.cacheMedios.map((m) =>
    `<option value="${esc(m.id)}" ${String(m.id) === String(idMedioActual) ? 'selected' : ''}>${esc(m.descripcion)}</option>`
  ).join('');

  const { value: medio } = await Swal.fire({
    title: 'Cambiar medio de pago',
    html: `
      <div class="text-start">
        <label for="scl-medio" class="form-label">Medio de pago</label>
        <select id="scl-medio" class="form-select form-select-sm">${opciones}</select>
      </div>`,
    showCancelButton: true,
    confirmButtonText: 'Guardar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusConfirm: false,
    preConfirm: () => {
      const v = document.getElementById('scl-medio').value;
      if (!v) {
        Swal.showValidationMessage('Seleccione un medio de pago.');
        return false;
      }
      if (String(v) === String(idMedioActual)) {
        Swal.showValidationMessage('Seleccione un medio de pago diferente al actual.');
        return false;
      }
      return v;
    }
  });
  if (!medio) return;

  loader('Guardando medio de pago');
  try {
    const resp = await enviarPeticion({ link: CD_MODEL, op: 'documento-cambiar-medio-pago', id_pago: idPago, id_medio_pago: medio });
    if (!resp?.success) {
      Swal.fire('No se pudo actualizar', resp?.message || 'Error desconocido.', 'error');
      return;
    }
    const medios = await enviarPeticion({ link: CD_MODEL, op: 'documento-medio-pago', id: CD.doc.id });
    renderMediosPago(medios?.data || []);
    dissminSwal();

    // El medio de pago define la cuenta de caja/banco: ofrecer recontabilizar
    const r = await Swal.fire({
      icon: 'success',
      title: 'Medio de pago actualizado',
      html: `¿Desea regenerar la contabilización para que refleje la cuenta del nuevo medio de pago?<br>
             <small class="text-muted">Si el movimiento contable fue ajustado manualmente, esos cambios se reemplazarán.</small>`,
      showCancelButton: true,
      confirmButtonText: 'Sí, regenerar',
      cancelButtonText: 'Ahora no',
      reverseButtons: true
    });
    if (r.isConfirmed) await regenerarContable({ confirmar: false });
  } catch (e) {
    console.error(e);
    Swal.fire('Error en la petición', e.message, 'error');
  }
};

/* =========================
   CRUCES
========================== */
const renderCruces = () => {
  const rows = CD.cruces || [];
  const hist = CD.crucesHistoricos;
  const d = CD.doc;
  setContador('cruces', rows.length);

  if (!rows.length) {
    $('#navs-cruces').html(alerta(hist ? 'El documento no tenía cruces al momento de anularlo.' : 'El documento no tiene cruces registrados.'));
    return;
  }

  let total = 0;
  const body = rows.map((r) => {
    total += num(r.valor);
    const esOrigen = String(r.tipo) === String(d.tipo) && String(r.numero) === String(d.numero);
    const tCargar = esOrigen ? r.tipo_aplica : r.tipo;
    const nCargar = esOrigen ? r.numero_aplica : r.numero;
    return `
      <tr>
        <td class="text-nowrap">${esc(r.tipo)} ${esc(r.numero)}</td>
        <td class="text-nowrap">${esc(r.tipo_aplica)} ${esc(r.numero_aplica)}</td>
        <td class="text-end">${money(r.valor)}</td>
        <td class="text-end">${money(r.descuento)}</td>
        <td class="text-end">${money(r.ret_FTE)}</td>
        <td class="text-end">${money(r.ret_IVA)}</td>
        <td class="text-end">${money(r.ret_ICA)}</td>
        <td class="text-nowrap">${esc(r.fecha)}</td>
        <td>${esc(r.usuario)}</td>
        <td class="text-center">
          <button class="btn btn-sm btn-icon btn-outline-primary js-cargar-doc"
                  data-tipo="${esc(tCargar)}" data-numero="${esc(nCargar)}"
                  title="Cargar ${esc(tCargar)} ${esc(nCargar)}">
            <i class="fa-regular fa-circle-down"></i>
          </button>
        </td>
      </tr>`;
  }).join('');

  const aviso = hist
    ? alerta(
      `Cruces liberados al anular el documento${rows[0].fecha_liberacion ? ` el ${esc(rows[0].fecha_liberacion)}` : ''}${rows[0].usuario_liberacion ? ` por ${esc(rows[0].usuario_liberacion)}` : ''}. Los documentos relacionados recuperaron su saldo.`,
      'info',
      'fa-solid fa-clock-rotate-left'
    )
    : '';

  $('#navs-cruces').html(`
    ${aviso}
    <div class="table-responsive mt-3">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-primary">
          <tr>
            <th>Documento</th>
            <th>Aplicado a</th>
            <th class="text-end">Valor</th>
            <th class="text-end">Descuento</th>
            <th class="text-end">ReteFuente</th>
            <th class="text-end">ReteIVA</th>
            <th class="text-end">ReteICA</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th class="text-center">Cargar</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
        <tfoot class="table-primary">
          <tr>
            <th colspan="2" class="text-end">Total</th>
            <th class="text-end">${money(total)}</th>
            <th colspan="7"></th>
          </tr>
        </tfoot>
      </table>
    </div>`);
};

/* =========================
   ANULACIÓN (con liberación de cruces)
========================== */
const anularDocumento = async () => {
  const d = CD.doc;
  if (!d) {
    Swal.fire('Sin documento cargado', 'Cargue un documento antes de anularlo.', 'warning');
    return;
  }
  if (d.anulado == 1) {
    Swal.fire('Documento anulado', 'Este documento ya se encuentra anulado.', 'info');
    return;
  }

  const vivos = CD.crucesHistoricos ? [] : CD.cruces;
  const esPropio = (r) => String(r.tipo) === String(d.tipo) && String(r.numero) === String(d.numero);

  // Documentos que pagaron o compensaron a este: deben anularse primero
  const pagadores = [...new Set(vivos.filter((r) => !esPropio(r)).map((r) => `${r.tipo} ${r.numero}`))];
  if (pagadores.length) {
    const r = await Swal.fire({
      icon: 'info',
      title: 'Primero anule el pago',
      html: `<div class="text-start small">
               Este documento está pagado o compensado por:
               <div class="fw-semibold my-2">${pagadores.map(esc).join(', ')}</div>
               Anule primero ese(s) documento(s); así la cartera y la contabilidad quedan cuadradas.
               Después podrá anular este.
             </div>`,
      showCancelButton: true,
      confirmButtonText: 'Ver cruces',
      cancelButtonText: 'Cerrar',
      reverseButtons: true
    });
    if (r.isConfirmed) mostrarTab('navs-cruces');
    return;
  }

  // Cruces donde este documento es el que aplicó: se liberan al anular
  const cruces = vivos.filter(esPropio);
  const filas = cruces.map((r) => `
    <tr>
      <td>${esc(r.tipo)} ${esc(r.numero)}</td>
      <td>${esc(r.tipo_aplica)} ${esc(r.numero_aplica)}</td>
      <td class="text-end">${money(r.valor)}</td>
    </tr>`).join('');

  const htmlCruces = cruces.length ? `
    <div class="alert alert-warning text-start small mb-2">
      <div class="fw-semibold mb-1">
        <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Este documento tiene ${cruces.length} cruce(s)
      </div>
      Al anularlo, los cruces se liberan y quedan guardados en el histórico:
      <ul class="mb-0 ps-3">
        <li>Los documentos a los que aplicó vuelven a quedar con saldo pendiente.</li>
        <li>La contabilidad de esos documentos no cambia; solo se elimina la de este.</li>
      </ul>
    </div>
    <div class="table-responsive mb-2" style="max-height:160px">
      <table class="table table-sm mb-0 small">
        <thead class="table-primary"><tr><th>Documento</th><th>Aplicado a</th><th class="text-end">Valor</th></tr></thead>
        <tbody>${filas}</tbody>
      </table>
    </div>
    <div class="form-check text-start small">
      <input class="form-check-input" type="checkbox" id="chk-liberar">
      <label class="form-check-label" for="chk-liberar">Entiendo que se liberarán los cruces</label>
    </div>` : '';

  const { value: motivo } = await Swal.fire({
    icon: 'warning',
    title: `¿Anular ${esc(d.tipo)} ${esc(d.numero)}?`,
    html: `${htmlCruces}<div class="text-start small text-muted mt-2">Esta acción no se puede deshacer.</div>`,
    input: 'textarea',
    inputLabel: 'Motivo de anulación',
    inputPlaceholder: 'Describa el motivo (mínimo 5 caracteres)',
    inputAttributes: { maxlength: 100 },
    width: cruces.length ? 640 : undefined,
    showCancelButton: true,
    confirmButtonText: 'Sí, anular',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    preConfirm: (v) => {
      const m = (v || '').trim();
      if (m.length < 5) {
        Swal.showValidationMessage('El motivo debe tener al menos 5 caracteres.');
        return false;
      }
      if (cruces.length && !document.getElementById('chk-liberar').checked) {
        Swal.showValidationMessage('Confirme que entiende que se liberarán los cruces.');
        return false;
      }
      return m;
    }
  });
  if (!motivo) return;

  loader('Anulando documento');
  try {
    const resp = await enviarPeticion({
      link: CD_MODEL,
      op: 'anular-documento',
      id: d.id,
      motivo,
      liberar_cruces: cruces.length ? 1 : 0
    });

    if (resp?.success) {
      await cargarDocumento(d.tipo, d.numero);
      Swal.fire({ icon: 'success', title: 'Documento anulado', text: resp.message, timer: 3000, showConfirmButton: false });
      return;
    }

    // Se registraron cruces nuevos mientras el usuario confirmaba
    if (resp?.requiere_confirmacion) {
      await cargarDocumento(d.tipo, d.numero);
      Swal.fire('Hay cruces nuevos', `${resp.message} Revise la pestaña Cruces e intente de nuevo.`, 'warning');
      return;
    }

    // Alguien aplicó un pago a este documento mientras el usuario confirmaba
    if (resp?.bloqueado_por_pagos) {
      await cargarDocumento(d.tipo, d.numero);
      Swal.fire('Primero anule el pago', resp.message, 'info');
      return;
    }

    Swal.fire('No se pudo anular', resp?.message || 'Error desconocido.', 'error');
  } catch (e) {
    console.error(e);
    Swal.fire('Error en la petición', e.message, 'error');
  }
};

/* =========================
   BÚSQUEDA AVANZADA
========================== */
const listarDocumentos = async () => {
  const fi = $('#mdl-fecha-ini').val();
  const ff = $('#mdl-fecha-fin').val();
  if (fi && ff && fi > ff) {
    Swal.fire('Fechas inválidas', 'La fecha inicial no puede ser mayor que la final.', 'warning');
    return;
  }

  $('#body-listar-documentos').html(`
    <div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin fa-2x text-primary"></i></div>`);
  $('#btn-mdl-consultar').prop('disabled', true);

  try {
    const resp = await enviarPeticion({
      link: CD_MODEL,
      op: 'listar-documentos',
      sw: $('#sw').val(),
      tipo: $('#tipo').val(),
      almacen: $('#bodega').val(),
      tercero: $('#mdl-filtro-tercero').val(),
      fecha_ini: fi,
      fecha_fin: ff
    });

    const data = resp?.data || [];
    if (!data.length) {
      $('#body-listar-documentos').html(alerta('No se encontraron resultados para los filtros seleccionados.'));
      return;
    }

    const filas = data.map((r) => {
      const anulado = r.anulado == '1';
      return `
        <tr class="${anulado ? 'text-muted' : ''}" data-tipo="${esc(r.tipo)}" data-numero="${esc(r.numero)}" style="cursor:pointer">
          <td>${anulado ? '<span class="badge bg-label-danger">Anulado</span>' : '<span class="badge bg-label-success">Activo</span>'}</td>
          <td class="text-nowrap">${esc(r.fecha_hora)}</td>
          <td>${esc(r.tipo)}</td>
          <td>${esc(r.numero)}</td>
          <td>${esc(r.nit)}</td>
          <td>${esc(r.razon_comercial)}</td>
          <td class="text-end">${money(r.valor_total)}</td>
          <td class="text-end">${money(r.valor_aplicado)}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-icon btn-outline-primary js-cargar-lista" title="Cargar documento">
              <i class="fa-regular fa-circle-down"></i>
            </button>
          </td>
        </tr>`;
    }).join('');

    $('#body-listar-documentos').html(`
      <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
        <span>${data.length} documento(s)${resp.limitado ? ` · se muestran los ${resp.limite} más recientes, ajuste los filtros` : ''}</span>
        <span>Doble clic en una fila para cargarla</span>
      </div>
      <div class="table-responsive" style="max-height:60vh;">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-primary sticky-top">
            <tr>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Número</th>
              <th>NIT</th>
              <th>Tercero</th>
              <th class="text-end">Valor total</th>
              <th class="text-end">Valor aplicado</th>
              <th class="text-center">Cargar</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>
      </div>`);
  } catch (e) {
    console.error(e);
    Swal.fire({ icon: 'error', title: 'Error en la petición', text: e.message });
  } finally {
    $('#btn-mdl-consultar').prop('disabled', false);
  }
};

const cargarDesdeLista = async (tipo, numero) => {
  $('#mdl-listar-documentos').modal('hide');
  nuevo();
  await cargarDocumento(tipo, numero);
};

/* =========================
   NUEVO / LIMPIAR
========================== */
const nuevo = () => {
  CD.doc = null;
  CD.cruces = [];
  CD.crucesHistoricos = false;

  $('#navs-basicos input, #navs-basicos textarea, #navs-valores input').val('');
  ['#status', '#status-pago', '#status-vencimiento'].forEach((s) => setBadge(s));
  $('#txt-status').text('');

  $('#navs-detalle').html(vacio('Cargue un documento para ver su detalle.'));
  $('#navs-cruces').html(vacio('Cargue un documento para ver sus cruces.'));
  $('#navs-medios-pago').html(vacio('Cargue un documento para ver sus medios de pago.'));
  $('#navs-contable').html(vacio('Cargue un documento para ver su movimiento contable.'));
  ['detalle', 'cruces', 'medios', 'contable'].forEach((k) => setContador(k, 0));

  $('#numero').val('');
  $('.insearch').prop('disabled', false);
  setAcciones();
  mostrarTab('navs-basicos');
  $('#numero').trigger('focus');
};

/* =========================
   INICIO
========================== */
$(async function () {
  nuevo();

  /* --- Filtros --- */
  $('#bodega, #sw').on('change', () => cargarTipos());

  $('#numero')
    .on('input', function () { this.value = this.value.replace(/\D/g, ''); })
    .on('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        cargarDocumento($('#tipo').val(), $('#numero').val());
      }
    });

  /* --- Acciones --- */
  $('#btn-consultar').on('click', () => cargarDocumento($('#tipo').val(), $('#numero').val()));
  $('#btn-nuevo').on('click', nuevo);
  $('#btn-anular').on('click', anularDocumento);
  $('#btn-search-doc').on('click', () => $('#mdl-listar-documentos').modal('show'));

  $('#btn-print').on('click', () => {
    if (!CD.doc) {
      Swal.fire('Sin documento cargado', 'Cargue un documento antes de imprimir.', 'warning');
      return;
    }
    const sw = String(CD.doc.sw);
    const id = CD.doc.id;
    if (['5', '9', '10'].includes(sw)) {
      Swal.fire({
        title: 'Formato de impresión',
        text: '¿Desea imprimir el documento en formato tirilla o carta?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Tirilla',
        cancelButtonText: 'Carta',
        reverseButtons: true
      }).then((r) => {
        if (r.isConfirmed) ImprimirDocumento(sw, id, 'Impresión de documento', false);
        else if (r.dismiss === Swal.DismissReason.cancel) ImprimirDocumento(sw, id, 'Impresión de documento', true);
      });
    } else {
      ImprimirDocumento(sw, id);
    }
  });

  $('#btn-whatsapp').on('click', () => {
    if (!CD.doc) return;
    enviarWhatsapp(CD.doc.id, String(CD.doc.sw), $('#doc-celular').val(), $('#doc-nombres').val());
  });
  $('#btn-correo').on('click', () => {
    if (!CD.doc) return;
    enviarEmail(CD.doc.id, String(CD.doc.sw), $('#doc-email').val(), $('#doc-nombres').val());
  });

  /* --- Delegados (contenido dinámico) --- */
  $(document).on('click', '#btn-generar-contable', () => regenerarContable());
  $(document).on('click', '.js-cambiar-medio', function () {
    cambiarMedioPago(this.dataset.id, this.dataset.medio);
  });
  $(document).on('click', '.js-cargar-doc', async function () {
    nuevo();
    await cargarDocumento(this.dataset.tipo, this.dataset.numero);
  });
  $(document).on('click', '.js-cargar-lista', function (e) {
    e.stopPropagation();
    const tr = this.closest('tr');
    cargarDesdeLista(tr.dataset.tipo, tr.dataset.numero);
  });
  $(document).on('dblclick', '#body-listar-documentos tr[data-tipo]', function () {
    cargarDesdeLista(this.dataset.tipo, this.dataset.numero);
  });

  /* --- Modal búsqueda --- */
  $('#mdl-listar-documentos').on('show.bs.modal', () => {
    const tipoTxt = $('#tipo option:selected').text();
    $('#mdl-subtitulo').text(tipoTxt ? `· ${tipoTxt}` : '');
    $('#mdl-filtro-tercero').val('');
    fechaActual([['mdl-fecha-ini', 'mdl-fecha-fin']]);
    $('#body-listar-documentos').html(`
      <div class="text-center text-muted py-4">
        <i class="fa-solid fa-filter fa-2x mb-2 d-block"></i>
        Ingrese los filtros y haga clic en <strong>Consultar</strong>
      </div>`);
  });
  $('#mdl-listar-documentos').on('shown.bs.modal', () => $('#mdl-filtro-tercero').trigger('focus'));
  $('#btn-mdl-consultar').on('click', listarDocumentos);
  $('#mdl-filtro-tercero, #mdl-fecha-ini, #mdl-fecha-fin').on('keydown', (e) => {
    if (e.key === 'Enter') listarDocumentos();
  });

  /* --- Video tutorial --- */
  $('#btn-video-tutorial').on('click', () => {
    const videoID = 'yVdJbAi3zes';
    $('#videoFrameTutorial').attr('src', `https://www.youtube.com/embed/${videoID}`);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVideoTutorial')).show();
  });
  $('#modalVideoTutorial').on('hidden.bs.modal', () => $('#videoFrameTutorial').attr('src', ''));

  /* --- Catálogos en paralelo --- */
  try {
    await Promise.all([cargarSw(), listarAlmacenes()]);
    await cargarTipos();
  } catch (e) {
    console.error(e);
    ShowAlert('Error cargando los filtros del módulo', 'error');
  }

  dissminSwal();
});
