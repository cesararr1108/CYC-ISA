/* ==========================================================================
   SISMED - Circular 021 de 2026 · Anexo Técnico No. 2 (ROL 2)
   ========================================================================== */

let tercero;
let datosSismed = [];
let periodoConsultado = null;

// Registro tipo 2: 25 campos (0 a 24) en el orden EXACTO del anexo técnico.
// tipo: A alfanumérico | N numérico entero | D decimal (máx 2) | F fecha
const CAMPOS_SISMED = [
    { campo: 'Tipo_de_registro',        titulo: '0 Tipo reg.',         tipo: 'N', max: 1 },
    { campo: 'Consecutivo_de_registro', titulo: '1 Consecutivo',       tipo: 'N', max: 10 },
    { campo: 'Codigo_de_habilitacion',  titulo: '2 Cód. habilitación', tipo: 'N', max: 10 },
    { campo: 'Mes_emision',             titulo: '3 Mes factura',       tipo: 'N', max: 2 },
    { campo: 'Rol_del_actor',           titulo: '4 Rol',               tipo: 'N', max: 2 },
    { campo: 'CiudadBodega',            titulo: '5 Municipio FEV',     tipo: 'A', max: 5 },
    { campo: 'CufeFev',                 titulo: '6 CUFE',              tipo: 'A', max: 96 },
    { campo: 'NumeroFactura',           titulo: '7 N° factura',        tipo: 'A', max: 20 },
    { campo: 'FechaEmision',            titulo: '8 Fecha emisión',     tipo: 'F', max: 10 },
    { campo: 'CodigoProducto',          titulo: '9 Cód. producto',     tipo: 'A', max: 50 },
    { campo: 'UnidadMedida',            titulo: '10 Unidad FEV',       tipo: 'A', max: 2 },
    { campo: 'Cantidad',                titulo: '11 Cantidad FEV',     tipo: 'N', max: 16 },
    { campo: 'PrecioUnitario',          titulo: '12 Precio unit.',     tipo: 'D', max: 16 },
    { campo: 'DocumentoSoporte',        titulo: '13 Doc. soporte',     tipo: 'A', max: 96 },
    { campo: 'Total',                   titulo: '14 Total FEV',        tipo: 'D', max: 16 },
    { campo: 'NitEntidadFacturacion',   titulo: '15 NIT cliente',      tipo: 'A', max: 9 },
    { campo: 'CodigoMunicipioEntidad',  titulo: '16 Municipio cliente',tipo: 'A', max: 5 },
    { campo: 'TipoOperacion',           titulo: '17 Tipo operación',   tipo: 'A', max: 2 },
    { campo: 'TipoTransaccion',         titulo: '18 Tipo transacción', tipo: 'A', max: 2 },
    { campo: 'IUM_1',                   titulo: '19 IUM nivel 1',      tipo: 'N', max: 8 },
    { campo: 'IUM_2',                   titulo: '20 IUM nivel 2',      tipo: 'N', max: 4 },
    { campo: 'IUM_3',                   titulo: '21 IUM nivel 3',      tipo: 'N', max: 3 },
    { campo: 'NumeroExpediente',        titulo: '22 Expediente',       tipo: 'N', max: 9 },
    { campo: 'NumeroPresentacion',      titulo: '23 Presentación',     tipo: 'N', max: 3 },
    { campo: 'TotalUnidadesMinimas',    titulo: '24 Unidades UMD',     tipo: 'N', max: 16 }
];

/* --------------------------------------------------------------------------
   UTILIDADES DE FORMATO (numeral 2 del anexo: ANSI, mayúsculas, sin tildes,
   sin comillas, sin pipes en los datos, sin relleno de ceros ni espacios)
   -------------------------------------------------------------------------- */

const limpiarTexto = (valor) => String(valor ?? '')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')   // quita tildes
    .replace(/[|"'\r\n\t]/g, ' ')                        // pipe, comillas, saltos
    .replace(/[^\x20-\x7E]/g, '')                        // solo ASCII (compatible ANSI)
    .replace(/\s+/g, ' ')
    .trim()
    .toUpperCase();

const formatearDecimal = (valor) => {
    const n = Number(valor);
    if (valor === null || valor === '' || !isFinite(n)) return '';
    return (Math.round(n * 100) / 100).toFixed(2).replace(/\.?0+$/, '');
};

const formatearEntero = (valor) => {
    const n = Number(valor);
    if (valor === null || valor === '' || !isFinite(n)) return '';
    return Number.isInteger(n) ? String(n) : String(valor);
};

const formatearCampo = (def, valor) => {
    if (def.tipo === 'D') return formatearDecimal(valor);
    if (def.tipo === 'N') return formatearEntero(valor);
    return limpiarTexto(valor);
};

const esPrimerDiaMes = (f) => /^\d{4}-\d{2}-01$/.test(f);

const esUltimoDiaMes = (f) => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(f)) return false;
    const [a, m, d] = f.split('-').map(Number);
    return new Date(a, m, 0).getDate() === d;
};

const obtenerNitEmpresa = () => {
    const t = Array.isArray(tercero) ? tercero[0] : tercero;
    return String(t?.nit ?? '').split('-')[0].replace(/\D/g, '');
};

/* --------------------------------------------------------------------------
   PERIODO (Rol 2 = trimestral)
   -------------------------------------------------------------------------- */

const TRIMESTRES = {
    1: ['01-01', '03-31'],
    2: ['04-01', '06-30'],
    3: ['07-01', '09-30'],
    4: ['10-01', '12-31']
};

const aplicarTrimestre = () => {
    const anio = $('#sel_anio').val();
    const [ini, fin] = TRIMESTRES[$('#sel_trimestre').val()];
    $('#fe_fecha_inicial').val(`${anio}-${ini}`);
    $('#fe_fecha_final').val(`${anio}-${fin}`);
    limpiarResultado();
};

const cargarAnios = () => {
    const actual = new Date().getFullYear();
    let html = '';
    for (let a = actual; a >= actual - 3; a--) html += `<option value="${a}">${a}</option>`;
    $('#sel_anio').html(html);
};

const ObtenerTrimestre = async () => {
    try {
        const data = await enviarPeticion({ link: '../models/RptSismed.php', op: 'trimestres' });
        $('#sel_anio').val(String(data.anio));
        $('#sel_trimestre').val(String(data.trimestre));
        $('#fe_fecha_inicial').val(data.fecha_inicial);
        $('#fe_fecha_final').val(data.fecha_final);
    } catch (error) {
        console.error({ error });
    }
};

const limpiarResultado = () => {
    datosSismed = [];
    periodoConsultado = null;
    $('#result').html('');
    $('#resumen').html('');
    $('#btn-exportar').prop('disabled', true);
};

const validarPeriodo = (fi, ff) => {
    if (!fi || !ff) return 'La fecha inicial y final son obligatorias';
    if (fi > ff) return 'La fecha inicial no puede ser mayor a la final';
    if (!esPrimerDiaMes(fi)) return 'La fecha inicial debe ser el primer día del mes';
    if (!esUltimoDiaMes(ff)) return 'La fecha final debe ser el último día del mes (fecha de corte)';
    return '';
};

/* --------------------------------------------------------------------------
   CONSULTA
   -------------------------------------------------------------------------- */

const consultar = async () => {
    const fi = $('#fe_fecha_inicial').val();
    const ff = $('#fe_fecha_final').val();

    const errorPeriodo = validarPeriodo(fi, ff);
    if (errorPeriodo) {
        Swal.fire('Ups', errorPeriodo, 'error');
        return;
    }

    try {
        showLoadingWithTetrisLogo();

        const resp = await enviarPeticion({
            link: '../models/RptSismed.php',
            fecha_inicial: fi,
            fecha_final: ff,
            op: 'consultar'
        });

        if (resp?.error) {
            Swal.fire('Ups', resp.error, 'error');
            return;
        }

        if (!Array.isArray(resp) || resp.length === 0) {
            limpiarResultado();
            Swal.fire('Ups', 'No se encontraron ventas de medicamentos con CUM en el periodo', 'error');
            return;
        }

        datosSismed = resp;
        periodoConsultado = { fi, ff };

        $('#result').html(`
            <table id="sismed" class="table table-sm table-borderless table-hover table-striped w-100">
                <thead class="table-primary"></thead>
                <tbody></tbody>
            </table>`);

        createDataTable('sismed', resp,
            CAMPOS_SISMED.map(c => c.campo),
            CAMPOS_SISMED.map(c => c.titulo),
            null, null, false, false, '', false, [], 'COP', [], []);

        const total = resp.reduce((s, r) => s + (Number(r.Total) || 0), 0);
        const errores = validarRegistros(resp);
        $('#resumen').html(`
            <b>${resp.length}</b> registros ·
            Total facturado: <b>${total.toLocaleString('es-CO', { style: 'currency', currency: 'COP' })}</b>
            ${errores.length
                ? ` · <span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> ${errores.length} inconsistencias</span>`
                : ' · <span class="text-success"><i class="fa-solid fa-circle-check"></i> Sin inconsistencias</span>'}`);

        $('#btn-exportar').prop('disabled', false);
        dissminSwal();

    } catch (error) {
        console.error({ error });
        Swal.fire('Error', 'Error al generar los registros', 'error');
    }
};

/* --------------------------------------------------------------------------
   VALIDACIONES DE CONTENIDO (las mismas que rechazaría PISIS)
   -------------------------------------------------------------------------- */

const validarRegistros = (filas) => {
    const errores = [];

    filas.forEach((f, i) => {
        const fila = i + 1;
        const fac = f.NumeroFactura || '';
        const add = (msg) => errores.push(`Fila ${fila} (${fac} / ${f.CodigoProducto}): ${msg}`);

        if (!String(f.CufeFev || '').trim()) add('sin CUFE');
        if (!/^\d{1,9}$/.test(String(f.NitEntidadFacturacion || ''))) add('NIT del cliente vacío o mayor a 9 dígitos');
        if (!/^\d{5}$/.test(String(f.CiudadBodega || ''))) add('municipio FEV (bodega) no es DIVIPOLA de 5 dígitos');
        if (!/^\d{5}$/.test(String(f.CodigoMunicipioEntidad || ''))) add('municipio del cliente no es DIVIPOLA de 5 dígitos');
        if (!Number.isInteger(Number(f.Cantidad))) add(`cantidad FEV no entera (${f.Cantidad})`);
        if (!Number.isInteger(Number(f.TotalUnidadesMinimas)) || Number(f.TotalUnidadesMinimas) <= 0) add('unidades mínimas de dispensación inválidas');

        const sinIUM = Number(f.IUM_1) === 0 && Number(f.IUM_2) === 0 && Number(f.IUM_3) === 0;
        if (sinIUM) {
            if (!/^\d{1,9}$/.test(String(f.NumeroExpediente || '')) || Number(f.NumeroExpediente) === 0) add('número de expediente CUM inválido');
            if (!/^\d{1,3}$/.test(String(f.NumeroPresentacion || '')) || Number(f.NumeroPresentacion) === 0) add('número de presentación CUM inválido');
        }

        CAMPOS_SISMED.forEach(def => {
            const v = formatearCampo(def, f[def.campo]);
            if (v.length > def.max) add(`campo ${def.titulo} excede ${def.max} caracteres`);
        });
    });

    return errores;
};

/* --------------------------------------------------------------------------
   EXPORTACIÓN TXT
   -------------------------------------------------------------------------- */

const construirRegistroDetalle = (fila, consecutivo) =>
    CAMPOS_SISMED.map(def => {
        if (def.campo === 'Consecutivo_de_registro') return String(consecutivo);
        return formatearCampo(def, fila[def.campo]);
    }).join('|');

const descargarTXT = (contenido, nombreArchivo) => {
    const blob = new Blob([contenido], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = nombreArchivo;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const exportarSismed = async () => {
    try {
        if (!datosSismed.length || !periodoConsultado) {
            Swal.fire('Ups', 'Primero consulte el periodo a reportar', 'warning');
            return;
        }

        const fi = $('#fe_fecha_inicial').val();
        const ff = $('#fe_fecha_final').val();
        if (fi !== periodoConsultado.fi || ff !== periodoConsultado.ff) {
            Swal.fire('Ups', 'Cambió las fechas después de consultar. Vuelva a consultar antes de exportar.', 'warning');
            return;
        }

        const nit = obtenerNitEmpresa();
        if (!nit) {
            Swal.fire('Ups', 'No fue posible obtener el NIT de la empresa', 'error');
            return;
        }

        // Validación previa
        const errores = validarRegistros(datosSismed);
        if (errores.length) {
            const lista = errores.slice(0, 15).map(e => `<li>${e}</li>`).join('');
            const mas = errores.length > 15 ? `<li>… y ${errores.length - 15} más</li>` : '';
            const r = await Swal.fire({
                icon: 'warning',
                title: `${errores.length} inconsistencias`,
                html: `<div class="text-start small" style="max-height:260px;overflow:auto"><ul class="ps-3 mb-0">${lista}${mas}</ul></div>
                       <p class="small mt-2 mb-0">PISIS rechazará estos registros (estado CP = no cumple la obligación).</p>`,
                showCancelButton: true,
                confirmButtonText: 'Exportar de todas formas',
                cancelButtonText: 'Corregir primero'
            });
            if (!r.isConfirmed) return;
        }

        // Registro tipo 2 (consecutivo recalculado 1..n)
        const detalle = datosSismed.map((f, i) => construirRegistroDetalle(f, i + 1));

        // Registro tipo 1: NIT sin ceros a la izquierda y sin DV; total SIN relleno
        const control = ['1', 'NI', nit, fi, ff, String(detalle.length)].join('|');

        // Sin salto de línea al final del archivo (literal l del numeral 2)
        const contenido = [control, ...detalle].join('\r\n');

        // Nombre: MED101MPRE + AAAAMMDD (fecha de corte) + NI + NIT a 12 dígitos + .TXT  (36 caracteres)
        const nombreArchivo = `MED101MPRE${ff.replace(/-/g, '')}NI${nit.padStart(12, '0')}.TXT`;

        descargarTXT(contenido, nombreArchivo);

        Swal.fire('Éxito',
            `Archivo generado.<br><br><b>Registros:</b> ${detalle.length}<br><b>Archivo:</b> ${nombreArchivo}
             <br><br><small>Recuerde firmarlo digitalmente antes de cargarlo en PISIS.</small>`,
            'success');

    } catch (error) {
        console.error('ERROR EXPORTANDO TXT:', error);
        Swal.fire('Error', 'No fue posible generar el archivo TXT', 'error');
    }
};

/* --------------------------------------------------------------------------
   INICIO
   -------------------------------------------------------------------------- */

$(async function () {
    showLoadingWithTetrisLogo();

    cargarAnios();
    tercero = await serviceTerceroEmpresa();
    await ObtenerTrimestre();

    $('#sel_anio, #sel_trimestre').on('change', aplicarTrimestre);
    $('#fe_fecha_inicial, #fe_fecha_final').on('change', limpiarResultado);

    $('#btn-limpiar').on('click', async () => {
        limpiarResultado();
        await ObtenerTrimestre();
    });

    $('#btn-busqueda').on('click', consultar);
    $('#btn-exportar').on('click', exportarSismed);

    dissminSwal();
});