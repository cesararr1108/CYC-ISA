/*
 * Controlador frontend: Presupuesto de ventas mensual
 *
 * Ingreso: se elige el período (año, mes, bodega) y el tipo (zona, vendedor, usuario)
 * y aparece una tabla con TODAS las zonas / vendedores / usuarios para escribir el
 * valor de cada uno. Los cambios se acumulan y se guardan juntos con "Guardar cambios".
 *
 * Carga desde archivo plano (.csv / .txt): se lee en el navegador, se muestra una vista
 * previa con lo que coincide y lo que tiene error, y los valores válidos pasan a la tabla
 * para revisarlos antes de guardar.
 */

/* ======================= Constantes y referencias ======================= */
const URL_SERVICIO = '../models/presupuestoVentas.php';
const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const ETIQUETA = { zona: 'Zona', vendedor: 'Vendedor', usuario: 'Usuario' };
const PLURAL = { zona: 'zonas', vendedor: 'vendedores', usuario: 'usuarios' };

const $anio = $('#selAnio'), $mes = $('#selMes'), $bodega = $('#selBodega');
const $anioC = $('#selAnioC'), $mesC = $('#selMesC'), $bodegaC = $('#selBodegaC');
const $tbGrilla = $('#tblGrilla tbody');

/* Estado de la grilla */
const estado = {
    periodo: null,   // { anio, mes, bodega, tipo } que está cargado en la tabla
    filas: [],       // [{ id, nombre, original, actual, anterior }]
    porId: new Map()
};

/* Resultado de la lectura del archivo */
let importacion = [];

/* ======================= Utilidades ======================= */
const fmt = n => new Intl.NumberFormat('es-CO', {
    style: 'currency', currency: 'COP', maximumFractionDigits: 0
}).format(n || 0);

const fmtNum = n => n === null || n === undefined ? '' :
    new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(n);

const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

// Normaliza un texto para comparar nombres: minúsculas, sin tildes ni espacios dobles
const normalizar = s => String(s ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/\s+/g, ' ').trim();

/*
 * Convierte un valor escrito por el usuario o leído del archivo a número.
 * Acepta "1500000", "1.500.000", "1,500,000", "1.500.000,50", "$ 1.500.000".
 * Devuelve null si está vacío y NaN si no es un número válido o es negativo.
 */
const parseValor = texto => {
    let s = String(texto ?? '').replace(/["$\s]/g, '').replace(/COP/i, '');
    if (s === '') return null;
    if (!/^[0-9.,]+$/.test(s)) return NaN;

    const puntos = (s.match(/\./g) || []).length, comas = (s.match(/,/g) || []).length;
    if (puntos && comas) {
        // El separador que aparece de último es el decimal
        const dec = s.lastIndexOf('.') > s.lastIndexOf(',') ? '.' : ',';
        const mil = dec === '.' ? ',' : '.';
        s = s.split(mil).join('').replace(dec, '.');
    } else if (puntos || comas) {
        const sep = puntos ? '.' : ',';
        const cant = puntos || comas;
        const despues = s.length - s.lastIndexOf(sep) - 1;
        // Varias apariciones o exactamente 3 dígitos después: separador de miles
        s = (cant > 1 || despues === 3) ? s.split(sep).join('') : s.replace(sep, '.');
    }
    const n = Number(s);
    return Number.isFinite(n) && n >= 0 ? Math.round(n * 100) / 100 : NaN;
};

/* ---------- Loader de la marca (ISA Software) ---------- */
const mostrarCarga = () => { if (typeof showLoadingWithTetrisLogo === 'function') showLoadingWithTetrisLogo(); };
const ocultarCarga = () => { if (typeof dissminSwal === 'function') dissminSwal(); };

/* ---------- Mensajes ---------- */
const avisar = (msg, ok = true) => Swal.fire({
    icon: ok ? 'success' : 'error',
    title: ok ? 'Listo' : 'Error',
    text: msg,
    confirmButtonText: 'Aceptar',
    customClass: { confirmButton: 'btn btn-primary' },
    buttonsStyling: false
});

const confirmar = (texto, textoBoton = 'Sí, continuar', icono = 'warning') => Swal.fire({
    icon: icono,
    title: 'Confirmación',
    text: texto,
    showCancelButton: true,
    confirmButtonText: textoBoton,
    cancelButtonText: 'Cancelar',
    customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-outline-secondary' },
    buttonsStyling: false
}).then(r => r.isConfirmed);

/* ---------- Petición al servidor (con loader) ---------- */
const peticion = (datos, conLoader = true) => {
    if (conLoader) mostrarCarga();
    return $.ajax({ url: URL_SERVICIO, method: 'POST', data: datos, dataType: 'json' })
        .always(() => { if (conLoader) ocultarCarga(); })
        .fail((jqXHR, textStatus, errorThrown) => {
            console.error('Error en', datos.op, {
                status: jqXHR.status, textStatus, errorThrown, respuestaPHP: jqXHR.responseText
            });
            const detalle = $('<div>').html(jqXHR.responseText || '').text().trim().slice(0, 500);
            avisar('No fue posible comunicarse con el servidor' + (detalle ? '\n\n' + detalle : ''), false);
        });
};

/* ======================= Período ======================= */
const tipoActual = () => $('input[name="tipo"]:checked').val();
const tipoCumplimiento = () => $('input[name="tipoC"]:checked').val();

const periodoSeleccionado = () => ({
    anio: $anio.val(), mes: $mes.val(), bodega: $bodega.val(), tipo: tipoActual()
});

const textoPeriodo = p =>
    `${MESES[p.mes - 1]} ${p.anio} · ${$bodega.find(`option[value="${p.bodega}"]`).text()}`;

// Año actual primero, luego el siguiente y por último el anterior
const aniosDisponibles = () => {
    const actual = new Date().getFullYear();
    return [actual, actual + 1, actual - 1];
};

const llenarAnios = $s => { $s.empty(); aniosDisponibles().forEach(a => $s.append(`<option value="${a}">${a}</option>`)); };
const llenarMeses = $s => { $s.empty(); MESES.forEach((m, i) => $s.append(`<option value="${i + 1}">${m}</option>`)); };

const cargarBodegas = () => peticion({ op: 'bodegas' }, false).done(r => {
    $bodega.empty();
    $bodegaC.html('<option value="0">Todas las bodegas</option>');
    (r || []).forEach(b => {
        $bodega.append(`<option value="${b.id}">${esc(b.nombre)}</option>`);
        $bodegaC.append(`<option value="${b.id}">${esc(b.nombre)}</option>`);
    });
    const ses = $('#hdBodegaSesion').val();
    if (ses && $bodega.find(`option[value="${ses}"]`).length) $bodega.val(ses);
});

// Restaura los controles al período cargado (cuando el usuario cancela el cambio)
const restaurarPeriodo = () => {
    const p = estado.periodo;
    if (!p) return;
    $anio.val(p.anio); $mes.val(p.mes); $bodega.val(p.bodega);
    $(`input[name="tipo"][value="${p.tipo}"]`).prop('checked', true);
};

const onCambioPeriodo = async () => {
    if (cambiosPendientes().length &&
        !(await confirmar('Hay cambios sin guardar en la tabla. Si cambia de período se perderán. ¿Continuar?', 'Sí, descartar'))) {
        restaurarPeriodo();
        return;
    }
    cargarGrilla();
};

/* ======================= Grilla editable ======================= */
const cargarGrilla = () => {
    const p = periodoSeleccionado();
    if (!p.anio || !p.mes || !p.bodega || !p.tipo) return;

    const et = ETIQUETA[p.tipo];
    $('#thReferencia').text(et);
    $('#txtBuscar').val('').attr('placeholder', `Buscar ${et.toLowerCase()}...`);
    $('#lblConValorTitulo').text(`${PLURAL[p.tipo][0].toUpperCase() + PLURAL[p.tipo].slice(1)} con presupuesto`);

    peticion({ op: 'grilla', ...p }).done(r => {
        estado.periodo = p;
        estado.filas = (r || []).map(f => ({
            id: parseInt(f.id_referencia),
            nombre: f.referencia ?? '(sin nombre)',
            original: f.valor === null ? null : parseFloat(f.valor),
            actual: f.valor === null ? null : parseFloat(f.valor),
            anterior: f.valor_anterior === null ? null : parseFloat(f.valor_anterior)
        }));
        estado.porId = new Map(estado.filas.map(f => [f.id, f]));
        renderGrilla();
    });
};

const renderGrilla = () => {
    $tbGrilla.empty();
    if (!estado.filas.length) {
        $tbGrilla.append(`<tr><td colspan="4" class="text-center text-muted py-4">
            No hay ${PLURAL[estado.periodo.tipo]} registrados</td></tr>`);
    }
    const html = estado.filas.map(f => `
        <tr data-id="${f.id}" data-busqueda="${esc(normalizar(f.id + ' ' + f.nombre))}">
            <td class="text-muted">${f.id}</td>
            <td>${esc(f.nombre)}</td>
            <td class="text-end d-none d-md-table-cell col-anterior">${f.anterior === null ? '—' : fmt(f.anterior)}</td>
            <td>
                <div class="input-group input-group-sm inp-valor">
                    <span class="input-group-text">$</span>
                    <input type="text" inputmode="decimal" class="form-control text-end inp-valor-txt"
                           value="${fmtNum(f.actual)}" placeholder="0" autocomplete="off">
                </div>
            </td>
        </tr>`).join('');
    $tbGrilla.append(html);
    actualizarResumen();
};

// Refresca una fila después de cambiar su valor por código (archivo, traer, repartir...)
const pintarFila = f => {
    const $tr = $tbGrilla.find(`tr[data-id="${f.id}"]`);
    $tr.find('.inp-valor-txt').val(fmtNum(f.actual)).removeClass('is-invalid');
    $tr.toggleClass('fila-modificada', f.actual !== f.original);
};

const cambiosPendientes = () => estado.filas.filter(f => f.actual !== f.original);

const actualizarResumen = () => {
    let total = 0, totalAnt = 0, conValor = 0;
    estado.filas.forEach(f => {
        total += f.actual || 0;
        totalAnt += f.anterior || 0;
        if (f.actual !== null) conValor++;
    });
    $('#lblTotal, #tfTotal').text(fmt(total));
    $('#lblTotalAnterior, #tfAnterior').text(fmt(totalAnt));
    $('#lblConValor').text(`${conValor} de ${estado.filas.length}`);

    const n = cambiosPendientes().length;
    $('#lblCambios').text(n);
    $('#barraCambios').toggleClass('d-none', n === 0);
};

const onEscribirValor = e => {
    const $inp = $(e.currentTarget), $tr = $inp.closest('tr');
    const f = estado.porId.get(parseInt($tr.data('id')));
    const v = parseValor($inp.val());
    const valido = !Number.isNaN(v);
    $inp.toggleClass('is-invalid', !valido);
    if (valido) f.actual = v;
    $tr.toggleClass('fila-modificada', f.actual !== f.original);
    actualizarResumen();
};

const onSalirValor = e => {
    const $inp = $(e.currentTarget);
    const f = estado.porId.get(parseInt($inp.closest('tr').data('id')));
    if (!$inp.hasClass('is-invalid')) $inp.val(fmtNum(f.actual));
};

// Enter / flechas: pasan a la fila siguiente o anterior visible
const onTeclaValor = e => {
    const dir = (e.key === 'Enter' || e.key === 'ArrowDown') ? 1 : e.key === 'ArrowUp' ? -1 : 0;
    if (!dir) return;
    e.preventDefault();
    const $inputs = $tbGrilla.find('tr:visible .inp-valor-txt');
    const i = $inputs.index(e.currentTarget) + dir;
    if (i >= 0 && i < $inputs.length) $inputs.eq(i).trigger('focus').trigger('select');
};

const onBuscar = e => {
    const q = normalizar(e.currentTarget.value);
    $tbGrilla.find('tr[data-id]').each((_, tr) => {
        $(tr).toggle(!q || String($(tr).data('busqueda')).includes(q));
    });
};

/* ---------- Guardar / descartar ---------- */
const onGuardar = async () => {
    if ($tbGrilla.find('.is-invalid').length) {
        return avisar('Hay valores con formato inválido (marcados en rojo). Corríjalos antes de guardar.', false);
    }
    const cambios = cambiosPendientes();
    if (!cambios.length) return;

    const quitados = cambios.filter(f => f.actual === null).length;
    if (quitados && !(await confirmar(
        `Se quitará el presupuesto de ${quitados} ${quitados === 1 ? 'registro' : 'registros'} que quedaron vacíos. ¿Continuar?`,
        'Sí, guardar'))) return;

    peticion({
        op: 'guardar_lote',
        ...estado.periodo,
        items: JSON.stringify(cambios.map(f => ({ id: f.id, valor: f.actual })))
    }).done(r => {
        avisar(r.msg, r.ok);
        if (r.ok) cargarGrilla();
    });
};

const onDescartar = async () => {
    if (!(await confirmar('Se descartarán los cambios que no se han guardado. ¿Continuar?', 'Sí, descartar'))) return;
    estado.filas.forEach(f => { f.actual = f.original; pintarFila(f); });
    actualizarResumen();
};

/* ---------- Acciones rápidas ---------- */
const abrirModalTraer = (mesAnterior = false) => {
    const p = estado.periodo;
    if (!p) return;
    let a = parseInt(p.anio), m = parseInt(p.mes) - 1;
    if (m < 1) { m = 12; a--; }

    const $a = $('#selAnioOrigen');
    llenarAnios($a);
    if (!$a.find(`option[value="${a}"]`).length) $a.append(`<option value="${a}">${a}</option>`);
    $a.val(a);
    llenarMeses($('#selMesOrigen'));
    $('#selMesOrigen').val(m);
    $('#txtAjuste').val(0);
    $('#chkSoloVacios').prop('checked', true);
    bootstrap.Modal.getOrCreateInstance('#modalTraer').show();
    if (mesAnterior) setTimeout(() => $('#btnConfirmarTraer').trigger('focus'), 300);
};

const onConfirmarTraer = () => {
    const p = estado.periodo;
    const origen = { anio: $('#selAnioOrigen').val(), mes: $('#selMesOrigen').val() };
    if (origen.anio == p.anio && origen.mes == p.mes) return avisar('Elija un mes diferente al que está editando', false);

    const ajuste = parseFloat($('#txtAjuste').val()) || 0;
    const soloVacios = $('#chkSoloVacios').is(':checked');

    peticion({ op: 'valores', anio: origen.anio, mes: origen.mes, bodega: p.bodega, tipo: p.tipo }).done(r => {
        const datos = r || [];
        if (!datos.length) return avisar(`${MESES[origen.mes - 1]} ${origen.anio} no tiene presupuesto registrado para esta bodega`, false);

        let aplicados = 0;
        datos.forEach(d => {
            const f = estado.porId.get(parseInt(d.id_referencia));
            if (!f || (soloVacios && f.actual !== null)) return;
            f.actual = Math.round(parseFloat(d.valor) * (1 + ajuste / 100));
            pintarFila(f);
            aplicados++;
        });
        actualizarResumen();
        bootstrap.Modal.getOrCreateInstance('#modalTraer').hide();
        avisar(`Se llenaron ${aplicados} fila(s). Revise y dé clic en "Guardar cambios".`);
    });
};

const onDistribuir = async () => {
    if (!estado.periodo || !estado.filas.length) return;
    const r = await Swal.fire({
        title: 'Repartir en partes iguales',
        text: `El total se divide entre ${PLURAL[estado.periodo.tipo]} visibles en la tabla.`,
        input: 'text',
        inputPlaceholder: 'Valor total',
        showCancelButton: true,
        confirmButtonText: 'Repartir',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-outline-secondary' },
        buttonsStyling: false,
        inputValidator: v => {
            const n = parseValor(v);
            if (n === null || Number.isNaN(n) || n <= 0) return 'Ingrese un valor mayor a cero';
        }
    });
    if (!r.isConfirmed) return;

    const ids = $tbGrilla.find('tr[data-id]:visible').map((_, tr) => parseInt($(tr).data('id'))).get();
    if (!ids.length) return;
    const total = parseValor(r.value);
    const parte = Math.floor(total / ids.length);
    ids.forEach((id, i) => {
        const f = estado.porId.get(id);
        // El residuo de la división se suma a la última fila para que el total cuadre
        f.actual = i === ids.length - 1 ? total - parte * (ids.length - 1) : parte;
        pintarFila(f);
    });
    actualizarResumen();
};

const onLimpiar = async () => {
    if (!(await confirmar('Se vaciarán todos los valores de la tabla. Se aplicará al guardar. ¿Continuar?', 'Sí, vaciar'))) return;
    estado.filas.forEach(f => { f.actual = null; pintarFila(f); });
    actualizarResumen();
};

/* ======================= Archivo plano ======================= */
const descargarPlantilla = e => {
    e?.preventDefault();
    const p = estado.periodo;
    if (!p) return;
    const lineas = ['codigo;nombre;valor'].concat(
        estado.filas.map(f => `${f.id};${String(f.nombre).replace(/[;\r\n]/g, ' ')};${f.actual ?? ''}`)
    );
    // BOM para que Excel abra bien las tildes
    const blob = new Blob(['﻿' + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `presupuesto_${p.tipo}_${p.anio}_${String(p.mes).padStart(2, '0')}_bodega${p.bodega}.csv`;
    a.click();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
};

const abrirModalImportar = () => {
    const p = estado.periodo;
    if (!p) return;
    $('#lblDestinoImport').html(
        `Los valores se cargarán en <strong>${esc(textoPeriodo(p))}</strong>, presupuesto por <strong>${ETIQUETA[p.tipo].toLowerCase()}</strong>.`);
    $('.lbl-tipo-min').text(ETIQUETA[p.tipo].toLowerCase());
    reiniciarImportacion();
    bootstrap.Modal.getOrCreateInstance('#modalImportar').show();
};

const reiniciarImportacion = () => {
    importacion = [];
    $('#fileImport').val('');
    $('#pasoArchivo').removeClass('d-none');
    $('#pasoPrevia, #btnOtroArchivo').addClass('d-none');
    $('#btnAplicarImport').prop('disabled', true).html('<i class="fa-solid fa-check"></i> Pasar a la tabla');
    $('#chkVaciarNoIncluidos').prop('checked', false);
    $('#fiTodos').prop('checked', true);
};

// Lee el archivo en UTF-8 y, si no es válido, en Windows-1252 (CSV guardado desde Excel)
const leerTexto = archivo => archivo.arrayBuffer().then(buf => {
    try { return new TextDecoder('utf-8', { fatal: true }).decode(buf); }
    catch { return new TextDecoder('windows-1252').decode(buf); }
});

const detectarSeparador = linea => {
    for (const s of [';', '\t', '|']) if (linea.includes(s)) return s;
    return ',';
};

// Divide una línea respetando comillas dobles ("Zona, norte";"1.500")
const dividirLinea = (linea, sep) => {
    const cols = [];
    let actual = '', comillas = false;
    for (let i = 0; i < linea.length; i++) {
        const c = linea[i];
        if (c === '"') {
            if (comillas && linea[i + 1] === '"') { actual += '"'; i++; }
            else comillas = !comillas;
        } else if (c === sep && !comillas) { cols.push(actual); actual = ''; }
        else actual += c;
    }
    cols.push(actual);
    return cols.map(c => c.trim());
};

const procesarArchivo = async archivo => {
    if (!archivo) return;
    if (!/\.(csv|txt)$/i.test(archivo.name)) return avisar('El archivo debe ser .csv o .txt', false);
    if (archivo.size > 2 * 1024 * 1024) return avisar('El archivo supera 2 MB', false);

    const texto = (await leerTexto(archivo)).replace(/^﻿/, '');
    const lineas = texto.split(/\r\n|\n|\r/);
    const primera = lineas.find(l => l.trim() !== '');
    if (!primera) return avisar('El archivo está vacío', false);
    const sep = detectarSeparador(primera);

    // Índice por nombre normalizado para buscar cuando no viene el código
    const porNombre = new Map();
    estado.filas.forEach(f => { if (!porNombre.has(normalizar(f.nombre))) porNombre.set(normalizar(f.nombre), f); });

    const vistos = new Set();
    let esPrimera = true;
    importacion = [];

    lineas.forEach((linea, i) => {
        if (linea.trim() === '') return;
        const cols = dividirLinea(linea, sep);
        const clave = cols[0] ?? '';
        const valorTxt = cols.length > 1 ? cols[cols.length - 1] : '';
        const valor = parseValor(valorTxt);

        // Encabezado: primera línea cuyo valor no es numérico y la clave no es un código
        if (esPrimera) {
            esPrimera = false;
            if (Number.isNaN(valor) && !/^\d+$/.test(clave)) return;
        }

        const item = { linea: i + 1, clave, valorTxt, valor, fila: null, estado: 'ok', mensaje: '' };

        if (cols.length < 2) {
            item.estado = 'error'; item.mensaje = 'Falta la columna del valor';
        } else {
            item.fila = (/^\d+$/.test(clave) && estado.porId.get(parseInt(clave))) || porNombre.get(normalizar(clave)) || null;
            if (!item.fila) { item.estado = 'error'; item.mensaje = `No existe ${ETIQUETA[estado.periodo.tipo].toLowerCase()}`; }
            else if (Number.isNaN(valor)) { item.estado = 'error'; item.mensaje = 'Valor inválido'; }
            else if (vistos.has(item.fila.id)) { item.estado = 'error'; item.mensaje = 'Repetido en el archivo (se usa la primera línea)'; }
            else {
                vistos.add(item.fila.id);
                item.mensaje = valor === null ? 'Se quitará el presupuesto' :
                    item.fila.actual === valor ? 'Sin cambio' : 'Listo';
            }
        }
        importacion.push(item);
    });

    if (!importacion.length) return avisar('El archivo no tiene filas de datos', false);
    renderPrevia();
};

const renderPrevia = () => {
    const ok = importacion.filter(x => x.estado === 'ok');
    const errores = importacion.length - ok.length;
    const total = ok.reduce((s, x) => s + (x.valor || 0), 0);

    $('#resumenImport').html(`
        <span class="badge bg-label-primary">${importacion.length} línea(s) leídas</span>
        <span class="badge bg-label-success">${ok.length} válidas</span>
        ${errores ? `<span class="badge bg-label-danger">${errores} con error</span>` : ''}
        <span class="badge bg-label-secondary">Total del archivo: ${fmt(total)}</span>`);

    const soloErrores = $('input[name="filtroImport"]:checked').val() === 'errores';
    const filas = importacion.filter(x => !soloErrores || x.estado === 'error');
    $('#tblPrevia tbody').html(filas.map(x => `
        <tr class="${x.estado === 'error' ? 'table-danger' : ''}">
            <td class="text-muted">${x.linea}</td>
            <td>${esc(x.clave)}</td>
            <td>${x.fila ? esc(x.fila.nombre) : '<span class="text-muted">—</span>'}</td>
            <td class="text-end">${x.valor === null ? '<span class="text-muted">vacío</span>' : Number.isNaN(x.valor) ? esc(x.valorTxt) : fmt(x.valor)}</td>
            <td>${x.estado === 'ok'
                ? `<span class="text-success"><i class="fa-solid fa-circle-check"></i> ${x.mensaje}</span>`
                : `<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> ${x.mensaje}</span>`}</td>
        </tr>`).join('') || '<tr><td colspan="5" class="text-center text-muted">Sin filas para mostrar</td></tr>');

    $('#pasoArchivo').addClass('d-none');
    $('#pasoPrevia, #btnOtroArchivo').removeClass('d-none');
    $('#btnAplicarImport').prop('disabled', ok.length === 0)
        .html(`<i class="fa-solid fa-check"></i> Pasar ${ok.length} fila(s) a la tabla`);
};

const onAplicarImport = async () => {
    const ok = importacion.filter(x => x.estado === 'ok');
    const errores = importacion.length - ok.length;
    if (errores && !(await confirmar(
        `${errores} línea(s) tienen error y no se cargarán. ¿Desea pasar las ${ok.length} válidas?`, 'Sí, continuar'))) return;

    const incluidos = new Set(ok.map(x => x.fila.id));
    ok.forEach(x => { x.fila.actual = x.valor; pintarFila(x.fila); });
    if ($('#chkVaciarNoIncluidos').is(':checked')) {
        estado.filas.forEach(f => { if (!incluidos.has(f.id)) { f.actual = null; pintarFila(f); } });
    }
    actualizarResumen();
    bootstrap.Modal.getOrCreateInstance('#modalImportar').hide();
    avisar(`Se pasaron ${ok.length} valor(es) a la tabla. Revise y dé clic en "Guardar cambios".`);
};

/* ======================= Cumplimiento ======================= */
// Verde desde 100 %, amarillo desde 80 %, rojo por debajo
const claseCumplimiento = pct => pct >= 100 ? 'success' : pct >= 80 ? 'warning' : 'danger';

const consultarCumplimiento = () => {
    const tipo = tipoCumplimiento();
    peticion({ op: 'cumplimiento', anio: $anioC.val(), mes: $mesC.val(), bodega: $bodegaC.val(), tipo }).done(r => {
        const datos = r || [];
        const $tb = $('#tblCumplimiento tbody').empty();
        let totalPresupuesto = 0, totalVentas = 0;

        datos.forEach(p => {
            const presupuesto = parseFloat(p.presupuesto), venta = parseFloat(p.venta);
            const diferencia = venta - presupuesto;
            const pct = p.cumplimiento === null ? null : parseFloat(p.cumplimiento);
            totalPresupuesto += presupuesto;
            totalVentas += venta;

            const celdaPct = pct === null ? '<span class="text-muted">—</span>' : `
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                        <div class="progress-bar bg-${claseCumplimiento(pct)}" style="width:${Math.min(pct, 100)}%"></div>
                    </div>
                    <span class="fw-semibold text-${claseCumplimiento(pct)}">${pct.toFixed(1)}%</span>
                </div>`;

            $tb.append(`
                <tr>
                    <td>${esc(p.referencia ?? '(sin nombre)')}</td>
                    <td class="text-end">${fmt(presupuesto)}</td>
                    <td class="text-end">${fmt(venta)}</td>
                    <td class="text-end text-${diferencia >= 0 ? 'success' : 'danger'}">${fmt(diferencia)}</td>
                    <td>${celdaPct}</td>
                </tr>`);
        });

        if (!datos.length) {
            $tb.append('<tr><td colspan="5" class="text-center text-muted py-4">No hay presupuesto registrado para esta selección</td></tr>');
        }

        const pctTotal = totalPresupuesto > 0 ? totalVentas / totalPresupuesto * 100 : null;
        $('#thReferenciaC').text(ETIQUETA[tipo]);
        $('#cPresupuesto').text(fmt(totalPresupuesto));
        $('#cVentas').text(fmt(totalVentas));
        $('#cCumplimiento')
            .text(pctTotal === null ? '—' : pctTotal.toFixed(1) + '%')
            .removeClass('text-success text-warning text-danger')
            .addClass(pctTotal === null ? '' : 'text-' + claseCumplimiento(pctTotal));
    });
};

/* ======================= Eventos y arranque ======================= */
$(function () {
    // Período de ingreso: año y mes actual por defecto
    llenarAnios($anio); llenarMeses($mes);
    $mes.val(new Date().getMonth() + 1);
    llenarAnios($anioC); llenarMeses($mesC);
    $mesC.val(new Date().getMonth() + 1);

    $anio.add($mes).add($bodega).on('change', onCambioPeriodo);
    $('input[name="tipo"]').on('change', onCambioPeriodo);

    // Grilla
    $tbGrilla
        .on('input', '.inp-valor-txt', onEscribirValor)
        .on('blur', '.inp-valor-txt', onSalirValor)
        .on('keydown', '.inp-valor-txt', onTeclaValor)
        .on('focus', '.inp-valor-txt', e => e.currentTarget.select());
    $('#txtBuscar').on('input', onBuscar);
    $('#btnGuardar').on('click', onGuardar);
    $('#btnDescartar').on('click', onDescartar);

    // Acciones
    $('#accTraerAnterior').on('click', e => { e.preventDefault(); abrirModalTraer(true); });
    $('#accTraerOtro').on('click', e => { e.preventDefault(); abrirModalTraer(); });
    $('#btnConfirmarTraer').on('click', onConfirmarTraer);
    $('#accDistribuir').on('click', e => { e.preventDefault(); onDistribuir(); });
    $('#accLimpiar').on('click', e => { e.preventDefault(); onLimpiar(); });
    $('#accPlantilla, #lnkPlantilla').on('click', descargarPlantilla);

    // Archivo plano
    $('#btnImportar').on('click', abrirModalImportar);
    $('#fileImport').on('change', e => procesarArchivo(e.currentTarget.files[0]));
    $('#zonaArchivo')
        .on('dragover dragenter', e => { e.preventDefault(); $(e.currentTarget).addClass('arrastrando'); })
        .on('dragleave drop', e => { e.preventDefault(); $(e.currentTarget).removeClass('arrastrando'); })
        .on('drop', e => procesarArchivo(e.originalEvent.dataTransfer.files[0]));
    $('#btnOtroArchivo').on('click', reiniciarImportacion);
    $('input[name="filtroImport"]').on('change', renderPrevia);
    $('#btnAplicarImport').on('click', onAplicarImport);

    // Cumplimiento: se consulta al abrir la pestaña y al cambiar cualquier filtro
    $('button[data-bs-target="#navs-cumplimiento"]').on('shown.bs.tab', consultarCumplimiento);
    $anioC.add($mesC).add($bodegaC).on('change', consultarCumplimiento);
    $('input[name="tipoC"]').on('change', consultarCumplimiento);

    // Aviso al salir de la página con cambios sin guardar
    window.addEventListener('beforeunload', e => {
        if (cambiosPendientes().length) { e.preventDefault(); e.returnValue = ''; }
    });

    cargarBodegas().done(cargarGrilla);
    if (typeof dissminSwal === 'function') dissminSwal();
});