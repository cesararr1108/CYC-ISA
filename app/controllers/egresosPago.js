/**
 * controllers/egresosPago.js
 *
 * Dependencias globales esperadas (las mismas que ya usaba el módulo):
 * jQuery, jQuery UI (autocomplete), Bootstrap, SweetAlert2, enviarPeticion,
 * permisosUsuario, condicionesPago, fechaActual, showLoadingWithTetrisLogo,
 * dissminSwal, ImprimirDocumento, generarContabilizacion.
 */
(() => {
  'use strict';

  // ---------------------------------------------------------------------------
  // Configuración
  // ---------------------------------------------------------------------------
  const ID_MODULO = 44;
  const API = '../models/egresosPago.php';
  const SW_EGRESO = 9;
  const SW_NEGATIVOS = [7, 13]; // t_sw: devoluciones en compras y notas crédito proveedor restan
  const SW_SUMAN_DETALLE = [1, 14]; // signo "+" en el detalle del trámite
  const TOLERANCIA_AJUSTE = 1000; // ajuste al peso permitido
  const EPS = 0.005;

  // ---------------------------------------------------------------------------
  // Estado
  // ---------------------------------------------------------------------------
  const ArrayDocumentos = [];
  const ArrayPagos = [];
  const docsCache = new Map(); // key -> documento devuelto por el servidor
  const docsValidados = new Set(); // keys ya verificados contra otros preliminares
  const cajas = new Map(); // id -> {modelo, desc, cuenta}
  const recibos = new Map(); // id_rc -> fila del listado de preliminares

  let permisos = {};
  let proveedorLabel = '';
  let docModalKey = null;
  let guardando = false;
  let tokenDocs = 0;
  let tokenTramite = 0;
  let ultimoEstado = 'T';
  let reciboActual = null;
  let tipoPorDefecto = '';

  // ---------------------------------------------------------------------------
  // Utilidades
  // ---------------------------------------------------------------------------
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[c]));

  const toNum = (v) => {
    if (typeof v === 'number') return Number.isFinite(v) ? v : 0;
    const n = parseFloat(String(v ?? '').replace(/[^0-9.\-]/g, ''));
    return Number.isFinite(n) ? n : 0;
  };

  const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

  const fmt = (n) => Number(toNum(n)).toLocaleString('en', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });

  const fechaLocal = (d) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

  const debounce = (fn, ms) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  const aviso = (title, icon = 'warning') => Swal.fire({
    toast: true,
    position: 'top-end',
    icon,
    title,
    showConfirmButton: false,
    timer: 2800
  });

  const docKey = (d) => `${d.sw}|${d.tipo}|${d.numero}`;
  const api = (op, extra = {}, link = API) => enviarPeticion({ link, op, ...extra });
  const spinner = (msg) => `<div class="p-3 text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>${esc(msg)}</div>`;
  const alerta = (msg, tipo = 'danger') => `<div class="alert alert-${tipo}" role="alert"><i class="fa-solid fa-bell"></i> ${esc(msg)}</div>`;

  // ---------------------------------------------------------------------------
  // Totales (única fuente de verdad: los arreglos, no el texto de los inputs)
  // ---------------------------------------------------------------------------
  const netoDoc = (d) => {
    const n = d.valor - d.dcto - d.retFuente - d.retIva - d.retIca;
    return SW_NEGATIVOS.includes(Number(d.sw)) ? -n : n;
  };

  const calcularTotales = () => {
    const documentos = r2(ArrayDocumentos.reduce((a, d) => a + netoDoc(d), 0));
    const abonos = r2(ArrayPagos.reduce((a, p) => a + p.valor, 0));
    return { documentos, abonos, sinAsignar: r2(abonos - documentos) };
  };

  // Evalúa la operación con las mismas reglas del servidor.
  // tipo: 'cruce' (docs sin pagos) | 'cobro' (docs con pagos) | 'anticipo' (solo pagos) | 'vacio'
  const evaluarOperacion = () => {
    const t = calcularTotales();
    const haySuman = ArrayDocumentos.some((d) => !SW_NEGATIVOS.includes(Number(d.sw)));
    const hayRestan = ArrayDocumentos.some((d) => SW_NEGATIVOS.includes(Number(d.sw)));
    const hayDocs = ArrayDocumentos.length > 0;
    const hayPagos = ArrayPagos.length > 0;
    const r = { t, tipo: 'vacio', ok: false, ajuste: 0, saldoFavor: 0, msg: '', nivel: 'muted' };

    if (!hayDocs && !hayPagos) {
      r.msg = 'Agregue documentos para cruzar o al menos un pago.';
      return r;
    }

    if (hayDocs && !hayPagos) {
      r.tipo = 'cruce';
      const dif = r2(-t.documentos); // pagos (0) - documentos
      if (!haySuman || !hayRestan) {
        r.msg = 'Un cruce sin pagos requiere al menos un documento que suma (compra o nota débito) y uno que resta (devolución o nota crédito).';
        r.nivel = 'warning';
        return r;
      }
      if (Math.abs(dif) > TOLERANCIA_AJUSTE) {
        r.msg = t.documentos > 0
          ? `Cruce desbalanceado: faltan ${fmt(Math.abs(dif))} por cubrir. Agregue un pago o reduzca el abono de los documentos que suman.`
          : `Cruce desbalanceado: los documentos que restan exceden en ${fmt(Math.abs(dif))}. Reduzca su abono (el saldo queda disponible para otro cruce).`;
        r.nivel = 'danger';
        return r;
      }
      r.ok = true;
      if (Math.abs(dif) > EPS) {
        r.ajuste = dif;
        r.msg = `Cruce con ajuste al peso de ${fmt(dif)}.`;
        r.nivel = 'warning';
      } else {
        r.msg = 'Cruce balanceado: no requiere pago.';
        r.nivel = 'success';
      }
      return r;
    }

    if (hayDocs) {
      r.tipo = 'cobro';
      if (t.documentos <= 0) {
        r.msg = 'El valor neto de los documentos debe ser mayor a cero.';
        r.nivel = 'danger';
        return r;
      }
      if (t.sinAsignar < -TOLERANCIA_AJUSTE) {
        r.msg = `Los pagos (${fmt(t.abonos)}) no cubren los documentos (${fmt(t.documentos)}). Diferencia demasiado grande para compensar.`;
        r.nivel = 'danger';
        return r;
      }
      r.ok = true;
      if (Math.abs(t.sinAsignar) <= TOLERANCIA_AJUSTE) {
        if (Math.abs(t.sinAsignar) > EPS) {
          r.ajuste = t.sinAsignar;
          r.msg = `Ajuste al peso de ${fmt(t.sinAsignar)}.`;
          r.nivel = 'warning';
        } else {
          r.msg = 'Pago balanceado.';
          r.nivel = 'success';
        }
      } else {
        r.saldoFavor = t.sinAsignar;
        r.msg = `Se generará un saldo a favor de ${fmt(t.sinAsignar)}.`;
        r.nivel = 'info';
      }
      return r;
    }

    r.tipo = 'anticipo';
    if (t.abonos <= TOLERANCIA_AJUSTE) {
      r.msg = `Sin documentos, el pago debe ser mayor a ${fmt(TOLERANCIA_AJUSTE)} para generar saldo a favor.`;
      r.nivel = 'danger';
      return r;
    }
    r.ok = true;
    r.saldoFavor = r2(t.abonos);
    r.msg = `Anticipo: se generará un saldo a favor de ${fmt(t.abonos)}.`;
    r.nivel = 'info';
    return r;
  };

  const pintarTotales = () => {
    const t = calcularTotales();
    const ev = evaluarOperacion();
    $('#hintCruce')
      .attr('class', `small text-end text-${ev.nivel === 'muted' ? 'muted' : ev.nivel}`)
      .text(ev.msg);
    $('#txt_Documentos').val(fmt(t.documentos));
    $('#txt_Abonos').val(fmt(t.abonos));
    $('#txt_SinAsignar')
      .val(fmt(t.sinAsignar))
      .toggleClass('text-success fw-bold', Math.abs(t.sinAsignar) <= EPS && (t.abonos > 0 || t.documentos > 0))
      .toggleClass('text-danger fw-bold', t.sinAsignar < -EPS);
    $('#badgeDocs').text(ArrayDocumentos.length);
    $('#badgePagos').text(ArrayPagos.length);
    return t;
  };

  const hayDatosSinGuardar = () => ArrayDocumentos.length > 0 || ArrayPagos.length > 0;

  // ---------------------------------------------------------------------------
  // Catálogos (se cargan una sola vez)
  // ---------------------------------------------------------------------------
  const cargarCatalogos = async () => {
    const [rTipos, rCajas] = await Promise.allSettled([
      enviarPeticion({ link: API, op: 'listar-tipo-documento' }),
      enviarPeticion({ link: '../models/gestionCajas.php', op: 'listar-caja-banco' })
    ]);

    const $tipo = $('#txt_tipo_doc').empty().append('<option value="">Seleccione un documento</option>');
    const tipos = rTipos.status === 'fulfilled' && Array.isArray(rTipos.value) ? rTipos.value : [];
    tipos.forEach((item) => {
      $tipo.append($('<option>', {
        value: JSON.stringify({ id: item.id, sw: item.sw }),
        text: `${item.tipo} - ${item.descripcion}`
      }));
    });
    tipoPorDefecto = tipos.length === 1 ? $tipo.find('option').eq(1).val() : '';
    if (!tipos.length) aviso('No fue posible cargar los tipos de documento');

    const $caja = $('#txt_tipo_pago').empty().append('<option value="">Seleccione una caja / banco</option>');
    const lista = rCajas.status === 'fulfilled' && Array.isArray(rCajas.value?.data) ? rCajas.value.data : [];
    cajas.clear();
    lista.forEach((item) => {
      const id = String(item.id);
      cajas.set(id, { modelo: id, desc: item.nombre, cuenta: item.cuenta });
      $caja.append($('<option>', { value: id, text: `${item.cuenta} - ${item.nombre}` }));
    });
    if (!lista.length) aviso('No fue posible cargar las cajas / bancos');
  };

  // ---------------------------------------------------------------------------
  // Proveedor
  // ---------------------------------------------------------------------------
  const cargarDirecciones = async (idTercero) => {
    const $dir = $('#id_tercero_dir').empty();
    try {
      const resp = await enviarPeticion({ link: '../models/Pedidos.php', op: 'listarDirecciones', id_tercero: idTercero });
      if (!Array.isArray(resp)) return;
      resp.forEach((item) => {
        $dir.append($('<option>', { value: item.id, text: item.direccion, selected: Number(item.principal) === 1 }));
      });
    } catch (e) {
      console.error(e);
    }
  };

  const renderDocumentosVacio = (msg = 'Seleccione un proveedor para ver sus documentos pendientes...') => {
    $('#dvDocumentos').html(`<div class="p-3 text-center text-muted">${esc(msg)}</div>`);
  };

  const resetDocumentos = () => {
    ArrayDocumentos.length = 0;
    docsCache.clear();
    docsValidados.clear();
    pintarTotales();
  };

  const resetProveedor = () => {
    proveedorLabel = '';
    $('#txt_cliente_id, #txt_cliente_nit, #txt_razon_social').val('');
    $('#id_tercero_dir').empty();
    tokenDocs++;
    resetDocumentos();
    renderDocumentosVacio();
  };

  const iniciarAutocomplete = () => {
    $('#txt_cliente').autocomplete({
      minLength: 3,
      source: async (request, response) => {
        const tipoDoc = ($('#txt_tipo_doc').val() || '').trim();
        if (!tipoDoc) {
          aviso('Seleccione primero un tipo de documento');
          return response([]);
        }
        try {
          const resp = await enviarPeticion({
            link: '../models/Terceros.php',
            filtro: $.trim(request.term),
            tipo_tercero: '',
            estado: 'A',
            tipoDoc,
            op: 's_tercero'
          });
          response(Array.isArray(resp?.data) ? resp.data : []);
        } catch (error) {
          console.error('Error en la búsqueda de proveedor:', error);
          response([]);
        }
      },
      select: async (_e, ui) => {
        const item = ui.item;
        if (String($('#txt_cliente_id').val()) !== String(item.id)) {
          resetDocumentos();
        }
        proveedorLabel = item.value;
        $('#txt_cliente_nit').val(item.nit);
        $('#txt_razon_social').val(item.razon_comercial);
        $('#txt_cliente_id').val(item.id);
        await cargarDirecciones(item.id);
        listarDocumentos();
      }
    });

    // Si el usuario edita el texto después de elegir, se descarta el proveedor anterior
    $('#txt_cliente').on('input', function () {
      if ($('#txt_cliente_id').val() && this.value !== proveedorLabel) {
        resetProveedor();
      }
    });
  };

  // ---------------------------------------------------------------------------
  // Documentos pendientes del proveedor
  // ---------------------------------------------------------------------------
  const filaDocumento = (d) => {
    const key = docKey(d);
    const enMora = toNum(d.dias_transcurridos) >= 0 && toNum(d.saldo) !== 0;
    const mora = `<i class="fa-solid fa-circle" style="color:${enMora ? '#ee2b48' : '#1ee66b'};"></i>`;
    return `
      <tr data-key="${esc(key)}" title="Doble clic para agregar documento">
        <td>${esc(d.sw)}</td>
        <td>
          <button type="button" class="btn btn-link btn-sm p-0 ms-1 js-ver-doc" data-sw="${esc(d.sw)}" data-id="${esc(d.id_transaccion)}" title="Ver documento">
            <i class="fa-solid fa-file-lines"></i>
          </button>
          ${esc(d.tipo)}/${esc(d.numero)}
        </td>
        <td>${esc(d.documento)}</td>
        <td class="text-end">${fmt(d.valor_total)}</td>
        <td class="text-end">${fmt(d.iva)}</td>
        <td class="text-end">${fmt(d.valor_aplicado)}</td>
        <td class="text-end">${fmt(d.saldo)}</td>
        <td>${esc(d.fecha)}</td>
        <td>${esc(d.vencimiento)}</td>
        <td>${esc(d.desc_condicion)}</td>
        <td>${mora} ${esc(d.dias_transcurridos)}</td>
        <td class="text-end" data-col="ab">${fmt(0)}</td>
        <td class="text-end" data-col="de">${fmt(0)}</td>
        <td class="text-end" data-col="ri">${fmt(0)}</td>
        <td class="text-end" data-col="rc">${fmt(0)}</td>
        <td class="text-end" data-col="rf">${fmt(0)}</td>
        <td>
          <button type="button" class="btn btn-outline-primary btn-sm js-add-doc" title="Agregar documento">
            <i class="fa-solid fa-circle-plus"></i>
          </button>
        </td>
      </tr>`;
  };

  const listarDocumentos = async () => {
    const idTercero = $('#txt_cliente_id').val();
    if (!idTercero) {
      renderDocumentosVacio();
      return;
    }
    const token = ++tokenDocs;
    $('#dvDocumentos').html(spinner('Consultando documentos pendientes...'));

    try {
      const resp = await enviarPeticion({ link: API, op: 'listar-documentos', id_tercero: idTercero });
      if (token !== tokenDocs) return; // llegó una respuesta vieja

      if (resp?.error) {
        $('#dvDocumentos').html(alerta(resp.message || 'No fue posible consultar los documentos'));
        return;
      }
      if (!Array.isArray(resp) || resp.length === 0) {
        $('#dvDocumentos').html(alerta('El proveedor no tiene documentos con saldo pendiente'));
        return;
      }

      docsCache.clear();
      resp.forEach((d) => docsCache.set(docKey(d), d));

      $('#dvDocumentos').html(`
        <table class="table table-sm table-documentos table-striped table-hover table-bordered">
          <thead class="cabecera table-primary sticky-top">
            <tr>
              <th>Sw</th><th>Tipo/Número</th><th>Documento</th><th>ValorTotal</th><th>IVA</th>
              <th>Aplicado</th><th>Saldo</th><th>Fecha</th><th>Vcto</th><th>Condición</th><th>Mora</th>
              <th>Abono</th><th>Descuento</th><th>ReteIVA</th><th>ReteICA</th><th>ReteFuente</th><th>Add</th>
            </tr>
          </thead>
          <tbody>${resp.map(filaDocumento).join('')}</tbody>
        </table>`);
    } catch (error) {
      console.error(error);
      if (token === tokenDocs) $('#dvDocumentos').html(alerta('Ocurrió un error consultando los documentos'));
    }
  };

  const filaPorKey = (key) => $('#dvDocumentos tbody tr').filter((_, el) => el.dataset.key === key);

  const actualizarFilaDoc = (key) => {
    const sel = ArrayDocumentos.find((d) => docKey(d) === key);
    const $tr = filaPorKey(key);
    const v = sel || { valor: 0, dcto: 0, retIva: 0, retIca: 0, retFuente: 0 };
    $tr.find('[data-col="ab"]').text(fmt(v.valor));
    $tr.find('[data-col="de"]').text(fmt(v.dcto));
    $tr.find('[data-col="ri"]').text(fmt(v.retIva));
    $tr.find('[data-col="rc"]').text(fmt(v.retIca));
    $tr.find('[data-col="rf"]').text(fmt(v.retFuente));
    $tr.find('td').toggleClass('table-success', !!sel);
  };

  // ---------------------------------------------------------------------------
  // Modal "Agregar documento"
  // ---------------------------------------------------------------------------
  const ceroModal = () => {
    ['#txtAddDcto', '#txtAddReteFuente', '#txtAddReteIVA', '#txtAddReteICA',
      '#txtAddIvaValor', '#txtAddFuenteValor', '#txtAddIcaValor'].forEach((s) => $(s).val(fmt(0)));
  };

  const abrirModalDocumento = (key) => {
    const doc = docsCache.get(key);
    if (!doc) return;
    docModalKey = key;

    const total = toNum(doc.valor_total);
    const iva = toNum(doc.iva);
    const saldo = toNum(doc.saldo);
    const pct = toNum(doc.dcto_calculado);
    const sel = ArrayDocumentos.find((d) => docKey(d) === key);

    $('#txtAddSw').val(doc.sw);
    $('#txtAddTipo').val(doc.tipo);
    $('#txtAddNumero').val(doc.numero);
    $('#txtAddValor').val(fmt(saldo));
    $('#txtAddValorTotal').val(fmt(total));
    $('#txtAddIva').val(fmt(iva));

    $('#txtAddCondicion').val(doc.id_condicion);
    $('#txtAddCondicionDesc').val(doc.desc_condicion);
    $('#txtAddDiasDoc').val(doc.dias);
    $('#txtAddDiasGracia').val(doc.dias_gracia);

    $('#txtAddIvaBase').val(fmt(iva));
    $('#txtAddFuenteBase, #txtAddIcaBase').val(fmt(total - iva));
    $('#txtAddDescuento').val(pct);
    $('#txtAddDescuentoValor').val(fmt(Math.round((total - iva) * (pct / 100))));

    ceroModal();
    $('#txtAddAbono').val(fmt(sel ? sel.valor : 0));
    if (sel) {
      $('#txtAddDcto').val(fmt(sel.dcto));
      $('#txtAddReteIVA').val(fmt(sel.retIva));
      $('#txtAddReteICA').val(fmt(sel.retIca));
      $('#txtAddReteFuente').val(fmt(sel.retFuente));
    }
    $('#txtAddRIVA').val(0);
    $('#txtAddFuente').val(0);
    $('#txtAddRICA').val(0);

    $('#ModalAddDocumento').modal('show');
  };

  const calcRetencion = (selPct, selBase, selVal) => {
    const base = toNum($(selBase).val());
    const pct = toNum($(selPct).val());
    $(selVal).val(fmt(base > 0 && pct > 0 ? Math.round(base * (pct / 100)) : 0));
  };

  const aplicarRetencion = (tipo) => {
    const mapa = {
      I: ['#txtAddIvaValor', '#txtAddReteIVA'],
      F: ['#txtAddFuenteValor', '#txtAddReteFuente'],
      C: ['#txtAddIcaValor', '#txtAddReteICA']
    }[tipo];
    if (mapa) $(mapa[1]).val(fmt(toNum($(mapa[0]).val())));
  };

  const aplicarDescuento = () => {
    const abono = toNum($('#txtAddAbono').val());
    const saldo = toNum($('#txtAddValor').val());
    if (Math.abs(saldo - abono) <= EPS) {
      $('#txtAddDcto').val(fmt(toNum($('#txtAddDescuentoValor').val())));
    } else {
      Swal.fire('Error', 'Solo es posible aplicar descuento cuando se cancela totalmente el documento.', 'error');
    }
  };

  const abonarSaldoTotal = () => {
    $('#txtAddAbono').val($('#txtAddValor').val());
    ['#txtAddDcto', '#txtAddReteFuente', '#txtAddReteIVA', '#txtAddReteICA'].forEach((s) => $(s).val(fmt(0)));
  };

  const validarDocumento = async (tipo, numero) => {
    try {
      const resp = await enviarPeticion({ link: API, op: 'valida_documento', tipo, numero });
      return resp?.data?.length > 0 ? resp.data[0].id_rc : 0;
    } catch (error) {
      console.error(error);
      return 0; // el servidor vuelve a validar al guardar
    }
  };

  const agregarDocumento = async () => {
    const doc = docsCache.get(docModalKey);
    if (!doc) return;

    const key = docModalKey;
    const saldo = toNum($('#txtAddValor').val());
    const abono = toNum($('#txtAddAbono').val());
    const dcto = toNum($('#txtAddDcto').val());
    const retIva = toNum($('#txtAddReteIVA').val());
    const retIca = toNum($('#txtAddReteICA').val());
    const retFuente = toNum($('#txtAddReteFuente').val());

    if (abono < 0 || dcto < 0) {
      return Swal.fire('Error', 'Los valores no pueden ser negativos.', 'error');
    }
    if (abono - dcto < -EPS) {
      return Swal.fire('Error', 'El descuento no puede ser mayor al abono.', 'error');
    }
    if (abono - dcto > saldo + EPS) {
      return Swal.fire('Error', 'El valor del abono no puede ser superior al saldo del documento.', 'error');
    }
    if (abono - dcto - retIva - retIca - retFuente < -EPS) {
      return Swal.fire('Error', 'El descuento y las retenciones superan el valor del abono.', 'error');
    }

    const $btn = $('#btnAddDoc').prop('disabled', true);
    try {
      if (abono > 0 && !docsValidados.has(key)) {
        const idRc = await validarDocumento(doc.tipo, doc.numero);
        if (idRc) {
          return Swal.fire('Error', `Este documento ya está asignado en el preliminar #${idRc}, no puede ser aplicado.`, 'error');
        }
        docsValidados.add(key);
      }

      const idx = ArrayDocumentos.findIndex((d) => docKey(d) === key);
      if (idx !== -1) ArrayDocumentos.splice(idx, 1);
      if (abono > 0) {
        ArrayDocumentos.push({
          tipo: String(doc.tipo),
          numero: String(doc.numero),
          sw: String(doc.sw),
          valor: abono,
          dcto,
          retIva,
          retIca,
          retFuente
        });
      }

      actualizarFilaDoc(key);
      pintarTotales();
      $('#ModalAddDocumento').modal('hide');
    } finally {
      $btn.prop('disabled', false);
    }
  };

  // ---------------------------------------------------------------------------
  // Pagos
  // ---------------------------------------------------------------------------
  const renderPagos = () => {
    const $tb = $('#tdAddPagos');
    if (!ArrayPagos.length) {
      $tb.html('<tr><td colspan="5" class="text-center text-muted">Sin pagos agregados</td></tr>');
      return;
    }
    $tb.html(ArrayPagos.map((p, i) => `
      <tr data-index="${i}">
        <td>${esc(p.modelo)}</td>
        <td>${esc(p.desc)}</td>
        <td class="text-end">${fmt(p.valor)}</td>
        <td>${esc(p.cuenta)}</td>
        <td class="text-center">
          <button type="button" class="btn btn-outline-primary btn-sm js-del-pago" title="Eliminar pago">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </td>
      </tr>`).join(''));
  };

  const pagarPendiente = () => {
    const t = calcularTotales();
    const pendiente = r2(t.documentos - t.abonos);
    if (pendiente <= EPS) {
      return aviso('No hay valor pendiente por asignar', 'info');
    }
    $('#txt_valor_pago').val(fmt(pendiente)).trigger('focus');
  };

  const agregarPago = () => {
    const caja = cajas.get(String($('#txt_tipo_pago').val() || ''));
    const valor = toNum($('#txt_valor_pago').val());

    if (!caja) return Swal.fire('Error', 'Debe seleccionar una caja / banco.', 'error');
    if (!$('#txt_cliente_nit').val()) return Swal.fire('Error', 'Debe seleccionar un proveedor primero.', 'error');
    if (valor <= 0) return Swal.fire('Error', 'El valor del pago debe ser mayor a cero.', 'error');

    ArrayPagos.push({ modelo: caja.modelo, desc: caja.desc, valor, cuenta: caja.cuenta });
    renderPagos();
    pintarTotales();
    $('#txt_valor_pago').val('').trigger('focus');
  };

  const eliminarPago = async (index) => {
    const pago = ArrayPagos[index];
    if (!pago) return;
    const r = await Swal.fire({
      title: '¿Realmente desea eliminar este pago?',
      text: `${pago.desc} - ${fmt(pago.valor)}`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      cancelButtonColor: '#d33'
    });
    if (!r.isConfirmed) return;
    ArrayPagos.splice(index, 1);
    renderPagos();
    pintarTotales();
  };

  // ---------------------------------------------------------------------------
  // Nuevo / limpiar
  // ---------------------------------------------------------------------------
  const Limpiar = () => {
    ArrayDocumentos.length = 0;
    ArrayPagos.length = 0;
    docsCache.clear();
    docsValidados.clear();
    tokenDocs++;
    proveedorLabel = '';

    $('#txt_cliente, #txt_cliente_id, #txt_cliente_nit, #txt_razon_social').val('');
    $('#txt_Documento, #txt_Referencia, #txt_notas_rc, #txt_valor_pago').val('');
    $('#id_tercero_dir').empty();
    $('#txt_tipo_doc').val(tipoPorDefecto);
    $('#txt_tipo_pago').val('');

    renderPagos();
    renderDocumentosVacio();
    ceroModal();
    $('#txtAddRIVA, #txtAddFuente').val(0);
    $('#txtAddRICA').val(0);
    pintarTotales();

    const hoy = new Date();
    const min = new Date();
    min.setDate(hoy.getDate() - 7);
    $('#txt_fecha_rc').val(fechaLocal(hoy)).attr('min', fechaLocal(min));
    fechaActual([['txt_fecha_ini', 'txt_fecha_fin']]);

    $('#txt_cliente').trigger('focus');
  };

  const nuevo = async () => {
    if (hayDatosSinGuardar()) {
      const r = await Swal.fire({
        title: 'Hay datos sin guardar',
        text: 'Si continúa se perderán los documentos y pagos agregados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
      });
      if (!r.isConfirmed) return;
    }
    Limpiar();
  };

  // ---------------------------------------------------------------------------
  // Guardar preliminar
  // ---------------------------------------------------------------------------
  const setGuardando = (v) => {
    guardando = v;
    $('#btnGuardar').prop('disabled', v);
  };

  const guardarPreliminar = async () => {
    if (guardando) return;

    const nit = $('#txt_cliente_nit').val();
    const idTercero = $('#txt_cliente_id').val();

    let tipoDoc = null;
    try {
      tipoDoc = JSON.parse($('#txt_tipo_doc').val() || 'null');
    } catch (_) {
      tipoDoc = null;
    }

    if (!tipoDoc) return Swal.fire('Oops', 'Debe seleccionar un tipo de documento.', 'warning');
    if (!nit || !idTercero) return Swal.fire('Oops', 'Debe seleccionar un proveedor.', 'warning');

    const ev = evaluarOperacion();
    if (!ev.ok) return Swal.fire('Oops', ev.msg, 'warning');

    const { ajuste, saldoFavor } = ev;
    let titulo;
    if (ev.tipo === 'cruce') {
      titulo = ajuste
        ? `Se cruzarán los documentos sin pago, con un ajuste al peso de ${fmt(ajuste)}. ¿Desea continuar?`
        : '¿Está seguro de generar el cruce de documentos (sin pago)?';
    } else if (ajuste) {
      titulo = `Se detectó un ajuste al peso de ${fmt(ajuste)}, ¿desea continuar?`;
    } else if (saldoFavor) {
      titulo = `Se generará un saldo a favor de ${fmt(saldoFavor)}, ¿desea continuar?`;
    } else {
      titulo = '¿Está seguro de generar el recibo de pago?';
    }

    const conf = await Swal.fire({
      title: titulo,
      icon: 'question',
      text: 'Después de aceptar no podrá reversar la operación!',
      showCancelButton: true,
      confirmButtonText: 'Guardar!',
      cancelButtonText: 'Cancelar'
    });
    if (!conf.isConfirmed) return;

    setGuardando(true);
    try {
      const resp = await enviarPeticion({
        link: API,
        op: 'guardar-preliminar',
        ArrayPagos: JSON.stringify(ArrayPagos),
        ArrayDocumentos: JSON.stringify(ArrayDocumentos),
        nit,
        id_tercero: idTercero,
        id_tipo: tipoDoc.id,
        sw: tipoDoc.sw,
        referencia_doc: $.trim($('#txt_Documento').val()),
        referencia_ref: $.trim($('#txt_Referencia').val()),
        notas: $.trim($('#txt_notas_rc').val()),
        adjunto: 0,
        ajuste,
        saldoFavor,
        id_tercero_dir: $('#id_tercero_dir').val(),
        id_condicion: $('#id_condicion').val()
      });

      if (resp?.error) {
        return Swal.fire('Error', `No fue posible crear el documento. ${resp.message || ''}`, 'error');
      }

      if (permisos?.ContabilizacionCrucesPagos) {
        const r = await Swal.fire({
          title: `Preliminar #${resp.Id} creado`,
          text: '¿Desea generar la contabilización de inmediato?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, generar',
          cancelButtonText: 'No, después',
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#6c757d'
        });
        Limpiar();
        if (r.isConfirmed) await aplicarPreliminar(resp.Id);
      } else {
        Limpiar();
        await Swal.fire('Correcto', `El documento fue creado correctamente (preliminar #${resp.Id}).`, 'success');
      }
    } catch (error) {
      console.error(error);
      Swal.fire('Error', 'No fue posible crear el documento. Verifique su conexión e intente nuevamente.', 'error');
    } finally {
      setGuardando(false);
    }
  };

  // ---------------------------------------------------------------------------
  // Preliminares: consulta, trámite, eliminar y contabilizar
  // ---------------------------------------------------------------------------
  const aplicarFiltroRecibos = () => {
    const q = ($('#txt_filtro_rc').val() || '').toLowerCase().trim();
    $('#tablaRecibos tbody tr').each(function () {
      this.style.display = !q || (this.dataset.q || '').includes(q) ? '' : 'none';
    });
  };

  const consultarRecibos = async (estado = 'T') => {
    const fhIni = $('#txt_fecha_ini').val();
    const fhFin = $('#txt_fecha_fin').val();

    if (!fhIni || !fhFin) {
      $('#resultRecibos').html(alerta('Debe seleccionar un rango de fechas.', 'warning'));
      return;
    }
    if (fhIni > fhFin) {
      $('#resultRecibos').html(alerta('La fecha inicial no puede ser mayor a la final.', 'warning'));
      return;
    }

    ultimoEstado = estado;
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

    try {
      const resp = await enviarPeticion({ link: API, op: 'seleccionar-documentos', fhIni, fhFin, estado });
      const registros = Array.isArray(resp?.data) ? resp.data : [];

      recibos.clear();
      if (!registros.length) {
        $('#resultRecibos').html(alerta('No existen resultados para las condiciones seleccionadas.'));
        return;
      }

      const filas = registros.map((d) => {
        recibos.set(String(d.id_rc), d);
        const pendiente = d.estado === 'P';
        const q = [d.id_rc, d.usuario, d.nit, d.nombres, d.razon_comercial, d.tipo, d.numero, d.referencia_doc, d.referencia_ref]
          .join(' ').toLowerCase();
        const iconoEstado = pendiente
          ? '<span class="badge bg-label-danger"><i class="fa-solid fa-file-circle-exclamation"></i> Pendiente</span>'
          : '<span class="badge bg-label-success"><i class="fa-solid fa-file-circle-check"></i> Aplicado</span>';
        const pdf = d.id_transaccion
          ? `<button type="button" class="btn btn-outline-danger btn-sm js-pdf" data-id="${esc(d.id_rc)}" title="Ver PDF"><i class="fa-regular fa-file-pdf"></i></button>`
          : '<span class="text-muted">—</span>';
        return `
          <tr data-q="${esc(q)}">
            <td>${esc(d.id_rc)}</td>
            <td><span>${esc(d.fecha)}</span><br><span>${esc(d.hora)}</span></td>
            <td>${esc(d.usuario)}</td>
            <td>${esc(d.nit)}</td>
            <td>${esc(d.nombres)}<br><span class="text-info">${esc(d.razon_comercial)}</span></td>
            <td>${esc(d.tipo)} - ${esc(d.numero)}${(Number(d.n_pagos) === 0 && Number(d.n_docs) > 0) ? ' <span class="badge bg-label-info">Cruce</span>' : ''}</td>
            <td class="text-end">${fmt(d.valor)}</td>
            <td class="text-end">${fmt(d.descuento)}</td>
            <td class="text-center">${iconoEstado}</td>
            <td class="text-center">
              <button type="button" class="btn btn-outline-primary btn-sm js-tramitar" data-id="${esc(d.id_rc)}" title="Tramitar">
                <i class="fa-solid fa-bars-progress"></i>
              </button>
            </td>
            <td class="text-center">${pdf}</td>
          </tr>`;
      }).join('');

      $('#resultRecibos').html(`
        <table id="tablaRecibos" class="table table-sm table-striped table-hover table-bordered table-responsive-md data-preliminares">
          <thead class="cabecera table-primary sticky-top">
            <tr>
              <th>Id</th><th>Fecha</th><th>Usuario</th><th>NIT</th><th>Nombres</th><th>Tipo | Número</th>
              <th>Valor</th><th>Descuento</th><th>Estado</th><th>Tramitar</th><th>PDF</th>
            </tr>
          </thead>
          <tbody>${filas}</tbody>
        </table>`);
      aplicarFiltroRecibos();
    } catch (error) {
      console.error('Error consultarRecibos:', error);
      $('#resultRecibos').html(alerta(`Ocurrió un error: ${error?.message || error}`));
    } finally {
      dissminSwal();
    }
  };

  const renderDetalleTramite = (detalles) => {
    let vTotal = 0, dTotal = 0, rIva = 0, rIca = 0, rFte = 0;

    const filas = detalles.map((d) => {
      const suma = SW_SUMAN_DETALLE.includes(Number(d.sw));
      const icon = suma
        ? '<i class="fa-solid fa-circle-plus text-success"></i>'
        : '<i class="fa-solid fa-circle-minus text-danger"></i>';
      const valor = suma ? toNum(d.valor) : -toNum(d.valor);

      vTotal += valor;
      dTotal += toNum(d.descuento);
      rIva += toNum(d.ret_IVA);
      rIca += toNum(d.ret_ICA);
      rFte += toNum(d.ret_FTE);

      return `
        <tr>
          <td>${esc(d.cuenta)}</td><td>${esc(d.modelo)}</td><td>${esc(d.descripcion)}</td><td>${icon}</td>
          <td class="text-end">${fmt(valor)}</td><td class="text-end">${fmt(d.descuento)}</td>
          <td class="text-end">${fmt(d.ret_IVA)}</td><td class="text-end">${fmt(d.ret_ICA)}</td><td class="text-end">${fmt(d.ret_FTE)}</td>
          <td>${esc(d.tipo)}</td><td>${esc(d.numero)}</td><td>${esc(d.sw)}</td><td>${esc(d.tipo_doc)}</td>
        </tr>`;
    }).join('');

    const diferencia = vTotal - dTotal - rIva - rIca - rFte;

    return `
      <div class="table-responsive">
      <table class="table data-tramite table-sm table-hover table-bordered">
        <thead class="cabecera table-primary">
          <tr>
            <th>Cuenta</th><th>Modelo</th><th>Descripción</th><th>Tipo</th><th>Valor</th>
            <th>Descuento</th><th>Ret.IVA</th><th>Ret.ICA</th><th>Ret.Fte</th>
            <th>Tipo</th><th>Número</th><th>Sw</th><th>TipoDoc</th>
          </tr>
        </thead>
        <tbody>
          ${filas}
          <tr class="cabecera table-primary">
            <th colspan="3"></th><th>Total</th>
            <th class="text-end">${fmt(vTotal)}</th><th class="text-end">${fmt(dTotal)}</th>
            <th class="text-end">${fmt(rIva)}</th><th class="text-end">${fmt(rIca)}</th><th class="text-end">${fmt(rFte)}</th>
          </tr>
          <tr class="cabecera table-primary">
            <th colspan="3"></th><th>Diferencia</th><th class="text-end">${fmt(diferencia)}</th>
          </tr>
        </tbody>
      </table>
      </div>`;
  };

  const tramitarRecibo = async (id) => {
    const r = recibos.get(String(id));
    if (!r) return;
    reciboActual = r;
    const token = ++tokenTramite;

    $('#txtTramiteSw').val(r.sw);
    $('#txtTramiteTipo').val(r.tipo);
    $('#txtTramiteNumero').val(r.numero);
    $('#txtTramiteNombres').val(r.nombres);
    $('#txtTramiteNit').val(r.nit);
    $('#txtTramiteId').val(r.id_rc);
    $('#txt_tramiteNotas_rc').val(r.notas);
    $('#txtTramiteRef').val(r.referencia_ref);
    $('#txtTramiteDoc').val(r.referencia_doc);
    $('#txtTramiteUsuAuto').val(r.usuario_autoriza);
    $('#txtTramiteUsu').val(r.usuario);
    $('#txtTramiteRazon').val(r.razon_comercial);

    const pendiente = r.estado === 'P';
    $('#btnContabilizar, #btnEliminar').toggle(pendiente && !!permisos?.ContabilizacionCrucesPagos);
    $('#btnVerRecibo').toggle(!pendiente && !!r.id_transaccion);

    $('#resultDetalle').html(spinner('Cargando detalle...'));
    $('#ModalTramitar').modal('show');

    try {
      const resp = await enviarPeticion({ link: API, op: 'seleccionar-documentos-detalle', id: r.id_rc });
      if (token !== tokenTramite) return;
      const detalles = Array.isArray(resp?.data) ? resp.data : [];
      $('#resultDetalle').html(detalles.length
        ? renderDetalleTramite(detalles)
        : alerta('No existen resultados para las condiciones seleccionadas'));
    } catch (error) {
      console.error(error);
      if (token === tokenTramite) $('#resultDetalle').html(alerta('No fue posible cargar el detalle'));
    }
  };

  const eliminarPreliminar = async () => {
    const id = parseInt($('#txtTramiteId').val(), 10);
    if (!id || id <= 0) {
      return Swal.fire('Validación', 'No existe un preliminar válido para eliminar', 'warning');
    }

    const conf = await Swal.fire({
      title: 'Eliminar preliminar',
      text: `¿Está seguro de eliminar el preliminar #${id}? Esta acción no se puede reversar.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d'
    });
    if (!conf.isConfirmed) return;

    try {
      const resp = await enviarPeticion({ link: API, op: 'd_recibos_pago', id });
      if (resp?.error === false || resp?.ok) {
        $('#ModalTramitar').modal('hide');
        Swal.fire('Excelente', 'Preliminar eliminado correctamente', 'success');
        consultarRecibos(ultimoEstado);
      } else {
        Swal.fire('Error', resp?.message || 'No fue posible eliminar el preliminar', 'error');
      }
    } catch (error) {
      console.error(error);
      Swal.fire('Error inesperado', 'Ocurrió un problema al comunicarse con el servidor', 'error');
    }
  };

  const aplicarPreliminar = async (id, refrescar = false) => {
    showLoadingWithTetrisLogo('Generando contabilización', 'ISA Software');

    let resp;
    try {
      resp = await enviarPeticion({ link: API, op: 'contabiliza_egresos_pago', recibo_id: id });
    } catch (error) {
      console.error(error);
      Swal.fire('Error', 'Ocurrió un error al contabilizar el preliminar.', 'error');
      return false;
    }

    if (!resp?.success) {
      Swal.fire('Error', resp?.message || 'No fue posible contabilizar el preliminar.', 'error');
      return false;
    }

    const idTransaccion = resp.data?.transaccion_id;
    let detalle = '';
    let icono = 'success';
    try {
      const respCont = await generarContabilizacion(idTransaccion);
      detalle = respCont?.message ?? '';
    } catch (error) {
      // La transacción ya existe; solo falló la generación de asientos
      console.error(error);
      icono = 'warning';
      detalle = `El documento se creó (transacción #${esc(idTransaccion)}) pero falló la generación de la contabilización. Notifique a soporte.`;
    }

    const r = await Swal.fire({
      title: icono === 'success' ? 'Correcto' : 'Atención',
      icon: icono,
      html: `<p>${icono === 'success' ? 'El documento fue contabilizado correctamente.' : 'Contabilización incompleta.'}</p>
             <p><strong>Resultado de la contabilización</strong></p><p>${detalle}</p>`,
      showCancelButton: true,
      confirmButtonText: 'Aceptar',
      cancelButtonText: 'Imprimir',
      reverseButtons: true
    });

    if (r.dismiss === Swal.DismissReason.cancel) {
      await ImprimirDocumento(SW_EGRESO, idTransaccion, 'Impresión de comprobante de egreso', true);
    }
    if (refrescar) {
      await consultarRecibos(ultimoEstado);
      $('#ModalTramitar').modal('hide');
    }
    return true;
  };

  const contabilizar = async () => {
    const id = $('#txtTramiteId').val();
    if (!id || Number(id) <= 0) return;

    const r = await Swal.fire({
      title: 'Contabilizar preliminar',
      text: `¿Desea contabilizar el preliminar #${id}? Esta acción no se puede reversar.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, contabilizar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#6c757d'
    });
    if (!r.isConfirmed) return;
    await aplicarPreliminar(id, true);
  };

  // ---------------------------------------------------------------------------
  // Inicialización
  // ---------------------------------------------------------------------------
  $(async () => {
    try {
      permisos = (await permisosUsuario(ID_MODULO)) || {};
    } catch (e) {
      console.error(e);
      permisos = {};
    }

    $('#btnContabilizar').hide();

    await Promise.all([
      cargarCatalogos(),
      Promise.resolve(condicionesPago('', 'id_condicion')).catch((e) => console.error(e))
    ]);

    iniciarAutocomplete();

    // --- Botones principales
    $('#btnGuardar').on('click', guardarPreliminar);
    $('#btnLimpiar').on('click', nuevo);
    $('#btnEliminar').on('click', eliminarPreliminar);
    $('#btnContabilizar').on('click', contabilizar);
    $('#btnVerRecibo').on('click', () => {
      if (reciboActual?.id_transaccion) {
        ImprimirDocumento(SW_EGRESO, reciboActual.id_transaccion, 'Impresión de comprobante de egreso', true);
      }
    });

    // --- Documentos del proveedor (delegado: una sola suscripción)
    $('#dvDocumentos')
      .on('dblclick', 'tbody tr[data-key]', function () {
        abrirModalDocumento(this.dataset.key);
      })
      .on('click', '.js-add-doc', function () {
        abrirModalDocumento($(this).closest('tr').data('key'));
      })
      .on('click', '.js-ver-doc', function (e) {
        e.stopPropagation();
        ImprimirDocumento(Number(this.dataset.sw), Number(this.dataset.id), 'Impresión de documento', true);
      });

    // --- Modal agregar documento
    $('#btnAddDoc').on('click', agregarDocumento);
    $('#btnSaldoTotal').on('click', abonarSaldoTotal);
    $('#btnAplicarDcto').on('click', aplicarDescuento);
    $('.js-aplicar-ret').on('click', function () {
      aplicarRetencion(this.dataset.ret);
    });
    $('#txtAddRIVA').on('change', () => calcRetencion('#txtAddRIVA', '#txtAddIvaBase', '#txtAddIvaValor'));
    $('#txtAddFuente').on('change', () => calcRetencion('#txtAddFuente', '#txtAddFuenteBase', '#txtAddFuenteValor'));
    $('#txtAddRICA').on('change', () => calcRetencion('#txtAddRICA', '#txtAddIcaBase', '#txtAddIcaValor'));
    $('#txtAddAbono').on('change', () => {
      ['#txtAddDcto', '#txtAddReteFuente', '#txtAddReteIVA', '#txtAddReteICA'].forEach((s) => $(s).val(fmt(0)));
    });
    $('#txtAddAbono').on('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        $('#txtAddAbono').trigger('change').trigger('blur');
        agregarDocumento();
      }
    });
    $('#ModalAddDocumento').on('shown.bs.modal', () => $('#txtAddAbono').trigger('focus').trigger('select'));

    // --- Pagos
    $('#btnPagarTotal').on('click', pagarPendiente);
    $('#btnAgregarPago').on('click', agregarPago);
    $('#txt_valor_pago').on('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        agregarPago();
      }
    });
    $('#tdAddPagos').on('click', '.js-del-pago', function () {
      eliminarPago(Number($(this).closest('tr').data('index')));
    });

    // --- Preliminares
    $('.js-consultar').on('click', function () {
      consultarRecibos(this.dataset.estado || 'T');
    });
    $('#txt_filtro_rc').on('input', debounce(aplicarFiltroRecibos, 150));
    $('#resultRecibos')
      .on('click', '.js-tramitar', function () {
        tramitarRecibo(this.dataset.id);
      })
      .on('click', '.js-pdf', function () {
        const r = recibos.get(String(this.dataset.id));
        if (r?.id_transaccion) {
          ImprimirDocumento(SW_EGRESO, r.id_transaccion, 'Impresión de comprobante de egreso', true);
        }
      });

    // --- Inputs numéricos (reemplaza onKeyPress/onBlur inline)
    $(document).on('input', '.js-money, .js-decimal', function () {
      const limpio = this.value.replace(/[^0-9.,]/g, '');
      if (limpio !== this.value) this.value = limpio;
    });
    $(document).on('focusout', '.js-money', function () {
      this.value = fmt(toNum(this.value));
    });

    // --- Aviso al salir con datos sin guardar
    window.addEventListener('beforeunload', (e) => {
      if (hayDatosSinGuardar()) {
        e.preventDefault();
        e.returnValue = '';
      }
    });

    Limpiar();
    dissminSwal();
  });
})();