const link_ = '../models/rptCierresCajas.php';

/* =====================================================================
 * CONFIGURACIÓN
 * Diferencia = valor REGISTRADO por el cajero − valor calculado por el SISTEMA
 *   negativa → faltante | positiva → sobrante
 * ===================================================================== */
const CC_CFG = {
    // Columnas del SP que NO son medios de pago del sistema
    camposFijos: [
        'id_apertura', 'id_bodega', 'usuario', 'nombres', 'fecha_apertura', 'fecha_cierre',
        'base', 'efectivo_usuario', 'bancos_usuario', 'otro_usuario', 'observaciones',
        'egresos', 'ingresos', 'total'
    ],

    // Columnas con lo que registró el cajero al cerrar
    registrado: { efectivo: 'efectivo_usuario', bancos: 'bancos_usuario', otros: 'otro_usuario' },

    // Efectivo esperado = ventas en efectivo (+ ingresos) (− egresos)
    // La base es un valor fijo de apertura: se muestra como referencia pero NO entra en las diferencias.
    efectivoEsperado: { sumarBase: false, sumarIngresos: true, restarEgresos: true },

    // Asignación manual de un medio de pago a un grupo. La clave es el nombre de la columna
    // tal como sale del SP (en minúscula). Tiene prioridad sobre los patrones.
    // Ejemplo: { 'nequi': 'bancos', 'bono regalo': 'otros' }
    mapaMedios: {},

    // Si un medio no está en mapaMedios, se clasifica por su nombre. Lo que no coincide va a "otros".
    patrones: {
        bancos: /tarjeta|d[eé]bito|banc|transfer|consign|nequi|daviplata|pse|\bqr\b|dat[aá]fono|bold|wompi|redeban|credibanco/i,
        efectivo: /efectivo|contado|cash/i
    },

    // Diferencias con valor absoluto menor o igual a esto se consideran cuadradas (pesos)
    tolerancia: 1000
};

const GRUPOS = ['efectivo', 'bancos', 'otros'];

const CC_GRUPOS = {
    efectivo: { txt: 'Efectivo', icon: 'fa-money-bill-wave' },
    bancos: { txt: 'Bancos / tarjetas', icon: 'fa-building-columns' },
    otros: { txt: 'Otros', icon: 'fa-wallet' }
};

const CC_ESTADOS = {
    faltante: { txt: 'Faltante', cls: 'danger', icon: 'fa-circle-exclamation', prio: 0 },
    sobrante: { txt: 'Sobrante', cls: 'warning', icon: 'fa-triangle-exclamation', prio: 1 },
    cruzado: { txt: 'Cruce de medios', cls: 'info', icon: 'fa-right-left', prio: 2 },
    abierto: { txt: 'Abierta', cls: 'secondary', icon: 'fa-lock-open', prio: 3 },
    ok: { txt: 'Cuadrado', cls: 'success', icon: 'fa-circle-check', prio: 4 }
};

const ccState = {
    data: [],
    medios: [],
    bodegas: new Map(),
    filtro: 'todos',
    orden: 'alerta',
    busqueda: '',
    cajero: null,        // clave del cajero seleccionado en "Diferencias por cajero"
    cajeroNombre: '',
    vista: 'cards',
    detalleActual: null,
    movCache: new Map()
};

/* =====================================================================
 * UTILIDADES
 * ===================================================================== */
const toNumber = (value) => {
    if (value === null || value === undefined || value === '') return 0;
    if (typeof value === 'number') return Number.isFinite(value) ? value : 0;

    let s = String(value).trim().replace(/[$\s]/g, '');
    if (/^-?\d+(\.\d+)?$/.test(s)) return parseFloat(s);       // formato BD: 12345.67
    s = s.replace(/\./g, '').replace(',', '.');                   // formato es-CO: 12.345,67
    const n = parseFloat(s);
    return Number.isFinite(n) ? n : 0;
};

const money = (n) => new Intl.NumberFormat('es-CO', {
    style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0
}).format(Math.round(n || 0));

// Diferencia con signo explícito: +$ 5.000 / -$ 5.000
const moneyDif = (n) => {
    const v = Math.round(n || 0);
    if (Math.abs(v) <= 0) return money(0);
    return (v > 0 ? '+' : '−') + money(Math.abs(v));
};

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[c]));

const formatLabel = (key) => String(key || '')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase()
    .replace(/(^|\s)\S/g, (c) => c.toUpperCase());

const parseFecha = (v) => {
    if (!v || String(v).startsWith('0000')) return null;
    const d = new Date(String(v).replace(' ', 'T'));
    return isNaN(d.getTime()) ? null : d;
};

const fmtFecha = (d) => d
    ? d.toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
    : '—';

const duracion = (a, b) => {
    if (!a || !b) return 'en curso';
    const min = Math.max(0, Math.round((b - a) / 60000));
    const h = Math.floor(min / 60);
    return h ? `${h} h ${min % 60} min` : `${min} min`;
};

const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

const debounce = (fn, ms = 250) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
};

const esMovil = () => window.matchMedia('(max-width: 767.98px)').matches;

const nombreBodega = (id) => ccState.bodegas.get(String(id)) || `Bodega ${id ?? ''}`;

const difClase = (dif) => {
    if (Math.abs(dif) <= CC_CFG.tolerancia) return 'ok';
    return dif < 0 ? 'neg' : 'pos';
};

const difTexto = (dif) => {
    const c = difClase(dif);
    return c === 'ok' ? 'Cuadrado' : (c === 'neg' ? 'Faltante' : 'Sobrante');
};

const grupoMedio = (key) => {
    const k = String(key).toLowerCase();
    if (CC_CFG.mapaMedios[k]) return CC_CFG.mapaMedios[k];
    if (CC_CFG.patrones.bancos.test(k)) return 'bancos';
    if (CC_CFG.patrones.efectivo.test(k)) return 'efectivo';
    return 'otros';
};

/* =====================================================================
 * PREPARACIÓN DE DATOS
 * ===================================================================== */
const enriquecer = (rows) => {
    const keys = Object.keys(rows[0]);
    ccState.medios = keys
        .filter((k) => !CC_CFG.camposFijos.includes(k))
        .map((k) => ({ key: k, label: formatLabel(k), grupo: grupoMedio(k) }));

    const fx = CC_CFG.efectivoEsperado;

    return rows.map((r) => {
        const base = toNumber(r.base);
        const ingresos = toNumber(r.ingresos);
        const egresos = toNumber(r.egresos);
        const apertura = parseFecha(r.fecha_apertura);
        const cierre = parseFecha(r.fecha_cierre);

        const medios = ccState.medios.map((m) => ({ ...m, valor: toNumber(r[m.key]) }));

        // Sistema por grupo
        const ventas = { efectivo: 0, bancos: 0, otros: 0 };
        medios.forEach((m) => { ventas[m.grupo] += m.valor; });

        const sistema = { ...ventas };
        if (fx.sumarBase) sistema.efectivo += base;
        if (fx.sumarIngresos) sistema.efectivo += ingresos;
        if (fx.restarEgresos) sistema.efectivo -= egresos;

        const grupos = GRUPOS.map((g) => {
            const registrado = toNumber(r[CC_CFG.registrado[g]]);
            return {
                key: g,
                ...CC_GRUPOS[g],
                registrado,
                sistema: sistema[g],
                dif: registrado - sistema[g]
            };
        });

        const totalRegistrado = grupos.reduce((s, g) => s + g.registrado, 0);
        const totalSistema = grupos.reduce((s, g) => s + g.sistema, 0);
        const dif = totalRegistrado - totalSistema;

        let estado;
        if (!cierre) estado = 'abierto';
        else if (dif < -CC_CFG.tolerancia) estado = 'faltante';
        else if (dif > CC_CFG.tolerancia) estado = 'sobrante';
        else if (grupos.some((g) => Math.abs(g.dif) > CC_CFG.tolerancia)) estado = 'cruzado';
        else estado = 'ok';

        return {
            raw: r,
            id: r.id_apertura,
            bodega: r.id_bodega,
            usuario: r.usuario,
            nombres: r.nombres,
            observaciones: String(r.observaciones ?? '').trim(),
            apertura,
            cierre,
            abierto: !cierre,
            base,
            ingresos,
            egresos,
            ventas,
            medios,
            grupos,
            totalRegistrado,
            totalSistema,
            dif,
            estado
        };
    });
};

const ORDENES = {
    alerta: (a, b) =>
        CC_ESTADOS[a.estado].prio - CC_ESTADOS[b.estado].prio ||
        Math.abs(b.dif) - Math.abs(a.dif) ||
        Math.max(...b.grupos.map((g) => Math.abs(g.dif))) - Math.max(...a.grupos.map((g) => Math.abs(g.dif))),
    dif_asc: (a, b) => a.dif - b.dif,
    dif_desc: (a, b) => b.dif - a.dif,
    fecha_desc: (a, b) => (b.apertura?.getTime() || 0) - (a.apertura?.getTime() || 0),
    fecha_asc: (a, b) => (a.apertura?.getTime() || 0) - (b.apertura?.getTime() || 0)
};

const claveCajero = (x) => String(x.usuario ?? x.nombres ?? '');

const datosFiltrados = () => {
    let d = [...ccState.data];
    const q = ccState.busqueda.toLowerCase().trim();

    if (ccState.cajero !== null) {
        d = d.filter((x) => claveCajero(x) === ccState.cajero);
    }

    if (q) {
        d = d.filter((x) => [x.id, x.usuario, x.nombres, x.bodega, nombreBodega(x.bodega)]
            .join(' ').toLowerCase().includes(q));
    }

    const f = ccState.filtro;
    if (f === 'revisar') d = d.filter((x) => x.estado !== 'ok');
    else if (f !== 'todos') d = d.filter((x) => x.estado === f);

    return d.sort(ORDENES[ccState.orden] || ORDENES.alerta);
};

/* =====================================================================
 * RENDER: KPIs
 * ===================================================================== */
const renderKpis = () => {
    const data = ccState.data;
    const sum = (arr, k) => arr.reduce((s, d) => s + d[k], 0);
    const faltantes = data.filter((d) => d.estado === 'faltante');
    const sobrantes = data.filter((d) => d.estado === 'sobrante');
    const cuadrados = data.filter((d) => d.estado === 'ok').length;
    const difNeta = sum(data, 'dif');
    const cNeta = difClase(difNeta);

    const kpis = [
        {
            t: 'Cierres', v: data.length, icon: 'fa-cash-register', cls: 'primary',
            sub: `${cuadrados} cuadrado(s)`, accion: 'todos'
        },
        {
            t: 'Diferencia neta', v: moneyDif(difNeta), icon: 'fa-scale-balanced',
            cls: cNeta === 'ok' ? 'success' : (cNeta === 'neg' ? 'danger' : 'warning'),
            sub: difTexto(difNeta), destacar: true
        },
        {
            t: 'Faltantes', v: faltantes.length, icon: 'fa-circle-exclamation', cls: 'danger',
            sub: faltantes.length ? moneyDif(sum(faltantes, 'dif')) : 'Ninguno', accion: faltantes.length ? 'faltante' : null
        },
        {
            t: 'Sobrantes', v: sobrantes.length, icon: 'fa-triangle-exclamation', cls: 'warning',
            sub: sobrantes.length ? moneyDif(sum(sobrantes, 'dif')) : 'Ninguno', accion: sobrantes.length ? 'sobrante' : null
        },
        { t: 'Registrado cajeros', v: money(sum(data, 'totalRegistrado')), icon: 'fa-user-pen', cls: 'info', sub: 'Lo que reportaron' },
        { t: 'Calculado sistema', v: money(sum(data, 'totalSistema')), icon: 'fa-server', cls: 'secondary', sub: 'Lo que debía haber' }
    ];

    $('#cc-kpis').html(kpis.map((k) => `
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card cc-kpi h-100 ${k.accion ? 'cc-clickable' : ''} ${k.destacar ? `cc-kpi-destacado border-${k.cls}` : ''}"
                 ${k.accion ? `data-accion="${k.accion}" role="button" tabindex="0"` : ''}>
                <div class="card-body p-2 p-md-3 d-flex gap-2 align-items-start">
                    <span class="cc-kpi-icon bg-label-${k.cls}"><i class="fa-solid ${k.icon}"></i></span>
                    <div class="min-w-0">
                        <div class="cc-kpi-title">${k.t}</div>
                        <div class="cc-kpi-value ${k.destacar ? `text-${k.cls}` : ''}">${k.v}</div>
                        <div class="cc-kpi-sub">${k.sub}</div>
                    </div>
                </div>
            </div>
        </div>`).join(''));
};

/* =====================================================================
 * RENDER: paneles (por grupo y por cajero)
 * ===================================================================== */
const renderPanelGrupos = () => {
    const filas = GRUPOS.map((g) => {
        const registrado = ccState.data.reduce((s, d) => s + d.grupos.find((x) => x.key === g).registrado, 0);
        const sistema = ccState.data.reduce((s, d) => s + d.grupos.find((x) => x.key === g).sistema, 0);
        return { key: g, ...CC_GRUPOS[g], registrado, sistema, dif: registrado - sistema };
    }).filter((g) => g.registrado !== 0 || g.sistema !== 0);

    if (!filas.length) {
        $('#cc-grupos').html('<div class="text-muted small">Sin movimientos en el periodo.</div>');
        return;
    }

    $('#cc-grupos').html(filas.map((g) => {
        const max = Math.max(Math.abs(g.registrado), Math.abs(g.sistema), 1);
        return `
        <div class="cc-grupo">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                <span class="cc-strong fw-semibold small"><i class="fa-solid ${g.icon} me-2 text-primary"></i>${g.txt}</span>
                <span class="cc-pill cc-dif-${difClase(g.dif)}">${moneyDif(g.dif)}</span>
            </div>
            <div class="cc-bar-row">
                <span class="cc-bar-lbl">Registrado</span>
                <div class="cc-bar"><span class="cc-bar-reg" style="width:${(Math.abs(g.registrado) / max * 100).toFixed(1)}%"></span></div>
                <span class="cc-bar-val">${money(g.registrado)}</span>
            </div>
            <div class="cc-bar-row">
                <span class="cc-bar-lbl">Sistema</span>
                <div class="cc-bar"><span class="cc-bar-sis" style="width:${(Math.abs(g.sistema) / max * 100).toFixed(1)}%"></span></div>
                <span class="cc-bar-val">${money(g.sistema)}</span>
            </div>
        </div>`;
    }).join(''));
};

const renderPanelCajeros = () => {
    const mapa = new Map();
    ccState.data.forEach((d) => {
        const k = claveCajero(d);
        if (!mapa.has(k)) mapa.set(k, { clave: k, usuario: d.usuario, nombres: d.nombres, cierres: 0, conDif: 0, dif: 0 });
        const c = mapa.get(k);
        c.cierres += 1;
        c.dif += d.dif;
        if (d.estado !== 'ok') c.conDif += 1;
    });

    const lista = [...mapa.values()].sort((a, b) =>
        (b.conDif > 0) - (a.conDif > 0) || Math.abs(b.dif) - Math.abs(a.dif));

    if (!lista.length) {
        $('#cc-cajeros').html('');
        return;
    }

    $('#cc-cajeros').html(lista.slice(0, 6).map((c) => `
        <div class="cc-cajero ${ccState.cajero === c.clave ? 'active' : ''}" data-cajero="${esc(c.clave)}" data-nombre="${esc(c.nombres || c.usuario)}"
             role="button" tabindex="0" aria-pressed="${ccState.cajero === c.clave}"
             title="${ccState.cajero === c.clave ? 'Quitar filtro de este cajero' : 'Ver solo los cierres de este cajero'}">
            <span class="cc-avatar">${esc(String(c.nombres || c.usuario || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase())}</span>
            <div class="flex-grow-1 min-w-0">
                <div class="small fw-semibold cc-strong text-truncate">${esc(c.nombres || c.usuario)}</div>
                <div class="small text-muted">${c.cierres} cierre(s) · ${c.conDif ? `<span class="text-danger">${c.conDif} con diferencia</span>` : 'sin novedades'}</div>
            </div>
            <span class="cc-pill cc-dif-${difClase(c.dif)}">${moneyDif(c.dif)}</span>
            ${ccState.cajero === c.clave ? '<i class="fa-solid fa-xmark text-muted" aria-hidden="true"></i>' : ''}
        </div>`).join('') +
        (lista.length > 6 ? `<div class="small text-muted text-center mt-2">y ${lista.length - 6} cajero(s) más</div>` : ''));
};

/* =====================================================================
 * RENDER: tarjeta de cierre
 * ===================================================================== */
const filaComparacion = (g) => `
    <div class="cc-cmp-row">
        <div class="cc-cmp-name"><i class="fa-solid ${g.icon}"></i>${g.txt}</div>
        <div class="cc-cmp-vals">
            <span title="Registrado por el cajero"><i class="fa-solid fa-user-pen"></i>${money(g.registrado)}</span>
            <span title="Calculado por el sistema"><i class="fa-solid fa-server"></i>${money(g.sistema)}</span>
        </div>
        <span class="cc-pill cc-dif-${difClase(g.dif)}">${moneyDif(g.dif)}</span>
    </div>`;

const cardCierre = (d) => {
    const e = CC_ESTADOS[d.estado];
    const c = difClase(d.dif);
    const grupos = d.grupos.filter((g) => g.registrado !== 0 || g.sistema !== 0);

    return `
    <div class="col-12 col-md-6 col-xxl-4">
        <div class="card cc-card h-100" data-id="${esc(d.id)}" data-estado="${d.estado}" tabindex="0" role="button" aria-label="Ver detalle del cierre ${esc(d.id)}">
            <div class="card-body p-3 d-flex flex-column gap-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="fw-semibold cc-strong text-truncate"><i class="fa-regular fa-user me-1"></i>${esc(d.nombres || d.usuario)}</div>
                        <div class="small text-muted text-truncate">#${esc(d.id)} · ${esc(nombreBodega(d.bodega))}</div>
                    </div>
                    <span class="badge bg-label-${e.cls} cc-badge"><i class="fa-solid ${e.icon} me-1"></i>${e.txt}</span>
                </div>

                <div class="small text-muted">
                    <i class="fa-regular fa-clock me-1"></i>${fmtFecha(d.apertura)} → ${d.abierto ? '<em>en curso</em>' : fmtFecha(d.cierre)}
                    <span class="text-nowrap">(${duracion(d.apertura, d.cierre)})</span>
                </div>

                <div class="cc-total cc-dif-${c}">
                    <div>
                        <div class="cc-label">Diferencia total</div>
                        <div class="cc-big">${moneyDif(d.dif)}</div>
                    </div>
                    <div class="text-end small">
                        <div>Registró <strong>${money(d.totalRegistrado)}</strong></div>
                        <div>Sistema <strong>${money(d.totalSistema)}</strong></div>
                    </div>
                </div>

                <div class="cc-cmp">
                    ${grupos.length ? grupos.map(filaComparacion).join('') : '<div class="small text-muted">Sin movimientos</div>'}
                </div>

                ${d.observaciones ? `
                    <div class="cc-obs small text-truncate" title="${esc(d.observaciones)}">
                        <i class="fa-regular fa-comment-dots me-1"></i>${esc(d.observaciones)}
                    </div>` : ''}

                <div class="small text-muted">
                    ${CC_CFG.efectivoEsperado.sumarBase
                        ? `Base ${money(d.base)}`
                        : `<span class="cc-base-ref" title="Valor fijo de apertura. No hace parte de la diferencia."><i class="fa-solid fa-circle-info me-1"></i>Base ${money(d.base)}</span>`}
                    · <a href="#" class="cc-link-tab" data-id="${esc(d.id)}" data-tab="ingresos" title="Ver ingresos">Ingresos ${money(d.ingresos)}</a>
                    · <a href="#" class="cc-link-tab" data-id="${esc(d.id)}" data-tab="egresos" title="Ver egresos">Egresos ${money(d.egresos)}</a>
                </div>

                <div class="d-flex gap-2 mt-auto pt-1">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill cc-btn-detalle" data-id="${esc(d.id)}">
                        <i class="fa-solid fa-magnifying-glass-dollar me-1"></i>Ver detalle
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary cc-btn-print" data-id="${esc(d.id)}" title="Imprimir cierre" ${d.abierto ? 'disabled' : ''}>
                        <i class="fa fa-print"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>`;
};

/* =====================================================================
 * RENDER: tabla (vista escritorio opcional)
 * ===================================================================== */
const renderTabla = (data) => {
    $('#resultado-cierres').html(`
        <table id="tb_cierres" class="table table-sm table-borderless table-hover table-striped w-100">
            <thead class="table-primary"></thead>
            <tbody></tbody>
        </table>`);

    const filas = data.map((d) => {
        const f = {
            id_apertura: d.id,
            estado: CC_ESTADOS[d.estado].txt,
            bodega_nombre: nombreBodega(d.bodega),
            nombres: d.nombres,
            fecha_apertura: d.raw.fecha_apertura,
            fecha_cierre: d.raw.fecha_cierre,
            base: d.base,
            ingresos: d.ingresos,
            egresos: d.egresos,
            total_registrado: d.totalRegistrado,
            total_sistema: d.totalSistema,
            diferencia: d.dif,
            observaciones: d.observaciones
        };
        d.grupos.forEach((g) => {
            f[`${g.key}_reg`] = g.registrado;
            f[`${g.key}_sis`] = g.sistema;
            f[`${g.key}_dif`] = g.dif;
        });
        return f;
    });

    const camposGrupo = GRUPOS.flatMap((g) => [`${g}_reg`, `${g}_sis`, `${g}_dif`]);
    const encGrupo = GRUPOS.flatMap((g) => {
        const n = g === 'bancos' ? 'Bancos' : CC_GRUPOS[g].txt;
        return [`${n} registrado`, `${n} sistema`, `${n} diferencia`];
    });

    const campos = [
        'id_apertura', 'estado', 'bodega_nombre', 'nombres', 'fecha_apertura', 'fecha_cierre',
        'diferencia', ...camposGrupo, 'total_registrado', 'total_sistema', 'base', 'ingresos', 'egresos', 'observaciones'
    ];
    const encabezados = [
        'ID', 'Estado', 'Bodega', 'Cajero', 'Apertura', 'Cierre',
        'Diferencia total', ...encGrupo, 'Total registrado', 'Total sistema', 'Base', 'Ingresos', 'Egresos', 'Observaciones'
    ];
    const columnasFormateadas = ['diferencia', ...camposGrupo, 'total_registrado', 'total_sistema', 'base', 'ingresos', 'egresos'];

    createDataTable(
        'tb_cierres',
        filas,
        campos,
        encabezados,
        '',
        '',
        true,
        '<button class="other-btn btn btn-sm btn-outline-primary" title="Imprimir cierre"><i class="fa fa-print"></i></button>',
        imprimirCierre,
        true,
        columnasFormateadas
    );
};

/* =====================================================================
 * RENDER: resultados (según filtro, orden y vista)
 * ===================================================================== */
const actualizarConteos = () => {
    const c = { todos: ccState.data.length, revisar: 0, faltante: 0, sobrante: 0, cruzado: 0, ok: 0 };
    ccState.data.forEach((d) => {
        if (c[d.estado] !== undefined) c[d.estado] += 1;
        if (d.estado !== 'ok') c.revisar += 1;
    });
    Object.entries(c).forEach(([k, v]) => {
        $(`#cc-chips [data-count="${k}"]`).text(v);
        // Oculta chips sin registros (excepto "Todos")
        if (k !== 'todos') $(`#cc-chips [data-filtro="${k}"]`).toggleClass('d-none', v === 0 && ccState.filtro !== k);
    });
};

const marcarChip = (filtro) => {
    $('#cc-chips .cc-chip').removeClass('active').filter(`[data-filtro="${filtro}"]`).addClass('active');
};

const renderResultados = () => {
    const data = datosFiltrados();
    const vista = esMovil() ? 'cards' : ccState.vista;
    $('#cc-resultado-txt').text(`${data.length} de ${ccState.data.length} cierre(s)`);
    renderFiltrosActivos();

    if (!data.length) {
        $('#cc-lista').html('').addClass('d-none');
        $('#cc-tabla').addClass('d-none');
        $('#cc-vacio').removeClass('d-none').html(`
            <div class="cc-empty">
                <i class="fa-solid fa-filter-circle-xmark d-block"></i>
                <div class="fw-semibold cc-strong">No hay cierres con este filtro</div>
                <button type="button" class="btn btn-sm btn-link" id="cc-reset-filtros">Quitar filtros</button>
            </div>`);
        return;
    }

    $('#cc-vacio').addClass('d-none').html('');

    if (vista === 'tabla') {
        $('#cc-lista').addClass('d-none').html('');
        $('#cc-tabla').removeClass('d-none');
        renderTabla(data);
    } else {
        $('#cc-tabla').addClass('d-none');
        $('#cc-lista').removeClass('d-none').html(data.map(cardCierre).join(''));
    }
};

const renderAvisoBase = () => {
    if (CC_CFG.efectivoEsperado.sumarBase) {
        $('#cc-aviso-base').addClass('d-none').html('');
        return;
    }
    const totalBase = ccState.data.reduce((s, d) => s + d.base, 0);
    $('#cc-aviso-base').removeClass('d-none').html(`
        <div class="alert alert-info alert-dismissible d-flex align-items-start gap-2 py-2 small mb-3" role="alert">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <div>
                <strong>La base de caja no hace parte de las diferencias.</strong>
                Es un valor fijo asignado al abrir cada caja (${money(totalBase)} en total para este periodo).
                Se muestra como referencia; las diferencias comparan solo lo registrado por el cajero contra las ventas, ingresos y egresos del sistema.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>`);
};

const renderDashboard = () => {
    renderAvisoBase();
    renderKpis();
    renderPanelGrupos();
    renderPanelCajeros();
    actualizarConteos();
    $('#cc-paneles, #cc-toolbar').removeClass('d-none');
    renderResultados();
};

const limpiarResultados = (mensaje = '') => {
    ccState.data = [];
    $('#cc-kpis, #cc-grupos, #cc-cajeros, #cc-lista, #resultado-cierres, #cc-aviso-base').html('');
    $('#cc-paneles, #cc-toolbar, #cc-tabla, #cc-aviso-base').addClass('d-none');
    $('#cc-filtros-activos').addClass('d-none').html('');
    if (mensaje) $('#cc-vacio').removeClass('d-none').html(mensaje);
    else $('#cc-vacio').addClass('d-none').html('');
};

/* =====================================================================
 * DETALLE
 * ===================================================================== */
const explicacionEstado = (d) => {
    switch (d.estado) {
        case 'faltante':
            return `El cajero registró <strong>${money(Math.abs(d.dif))}</strong> menos de lo que calcula el sistema.`;
        case 'sobrante':
            return `El cajero registró <strong>${money(d.dif)}</strong> más de lo que calcula el sistema.`;
        case 'cruzado': {
            const g = d.grupos.filter((x) => Math.abs(x.dif) > CC_CFG.tolerancia).map((x) => x.txt.toLowerCase());
            return `El total cuadra, pero hay diferencias entre ${g.join(' y ')}. Posiblemente un pago se registró en un medio distinto.`;
        }
        case 'abierto': return 'La caja aún no se ha cerrado.';
        default: return '';
    }
};

/* ---------- Pestañas de movimientos ---------- */
const TABS_MOV = [
    { key: 'ingresos', txt: 'Ingresos', icon: 'fa-arrow-trend-up', cls: 'success', vacio: 'No hubo ingresos de caja en este turno.' },
    { key: 'egresos', txt: 'Egresos', icon: 'fa-arrow-trend-down', cls: 'danger', vacio: 'No hubo egresos de caja en este turno.' },
    { key: 'ventas', txt: 'Ventas', icon: 'fa-cart-shopping', cls: 'primary', vacio: 'No hubo ventas en este turno.' },
    { key: 'devoluciones', txt: 'Devoluciones', icon: 'fa-rotate-left', cls: 'warning', vacio: 'No hubo devoluciones en este turno.' }
];

const valorMovimiento = (row, tab) => {
    // Ventas y devoluciones: lo pagado en el turno (igual que el reporte). Ingresos/egresos: valor_total.
    if ((tab === 'ventas' || tab === 'devoluciones') && row.valor_pagado !== undefined) return toNumber(row.valor_pagado);
    return toNumber(row.valor_total);
};

const htmlMovimientos = (lista, tab) => {
    const def = TABS_MOV.find((t) => t.key === tab);
    if (!lista.length) {
        return `<div class="cc-empty py-4"><i class="fa-solid ${def.icon} d-block"></i><div class="small">${def.vacio}</div></div>`;
    }

    const total = lista.reduce((s, r) => s + valorMovimiento(r, tab), 0);

    // Resumen por medio de pago (clave para saber si un ingreso o egreso fue en efectivo)
    const porMedio = {};
    lista.forEach((r) => (r.medios || []).forEach((m) => {
        const k = formatLabel(m.medio);
        porMedio[k] = (porMedio[k] || 0) + toNumber(m.valor);
    }));
    const resumenMedios = Object.entries(porMedio).sort((a, b) => b[1] - a[1]);

    const items = lista.map((r) => {
        const fecha = parseFecha(r.fecha_hora);
        const hora = fecha ? fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
        const doc = `${r.tipo ? esc(r.tipo) + ' ' : ''}${esc(r.numero ?? r.id)}`;
        const nit = r.nit && String(r.nit) !== '0' ? `NIT ${esc(r.nit)}` : '';
        const ref = r.documento ? `Ref. ${esc(r.documento)}` : '';
        const tercero = [nit, ref].filter(Boolean).join(' · ');
        const detalle = String(r.notas ?? '').trim();

        return `
        <div class="cc-mov">
            <div class="cc-mov-main">
                <div class="d-flex justify-content-between gap-2">
                    <span class="fw-semibold cc-strong text-truncate">${doc}</span>
                    <span class="fw-bold text-${def.cls} text-nowrap">${money(valorMovimiento(r, tab))}</span>
                </div>
                <div class="small text-muted text-truncate">
                    <i class="fa-regular fa-clock me-1"></i>${hora}${tercero ? ` · ${esc(tercero)}` : ''}
                </div>
                ${detalle ? `<div class="small cc-mov-det">${esc(detalle)}</div>` : ''}
                ${(r.medios || []).length ? `
                    <div class="cc-mov-medios">
                        ${r.medios.map((m) => `<span class="cc-medio-chip cc-g-${grupoMedio(m.medio)}">${esc(formatLabel(m.medio))} ${money(m.valor)}</span>`).join('')}
                    </div>` : ''}
            </div>
        </div>`;
    }).join('');

    return `
        <div class="cc-mov-resumen">
            <div><span>${lista.length} documento(s)</span><strong class="text-${def.cls}">${money(total)}</strong></div>
            ${resumenMedios.length ? `
                <div class="cc-mov-medios mt-1">
                    ${resumenMedios.map(([k, v]) => `<span class="cc-medio-chip cc-g-${grupoMedio(k)}">${esc(k)} ${money(v)}</span>`).join('')}
                </div>` : ''}
        </div>
        <div class="cc-mov-list">${items}</div>`;
};

const cargarMovimientos = async (d) => {
    const pintar = (mov) => {
        TABS_MOV.forEach((t) => {
            const lista = mov[t.key] || [];
            $(`#cc-tabcount-${t.key}`).text(lista.length);
            $(`#cc-pane-${t.key}`).html(htmlMovimientos(lista, t.key));
        });
    };

    if (ccState.movCache.has(String(d.id))) {
        pintar(ccState.movCache.get(String(d.id)));
        return;
    }

    const cargando = `<div class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Cargando movimientos…</div>`;
    TABS_MOV.forEach((t) => $(`#cc-pane-${t.key}`).html(cargando));

    try {
        const resp = await enviarPeticion({ link: link_, op: 's_detalle_cierre', idApertura: d.id });
        if (!ccState.detalleActual || String(ccState.detalleActual.id) !== String(d.id)) return; // se abrió otro cierre

        if (!resp || resp.error) {
            const msg = `<div class="alert alert-danger small py-2 m-0">${esc(resp?.message || 'No fue posible cargar los movimientos')}</div>`;
            TABS_MOV.forEach((t) => $(`#cc-pane-${t.key}`).html(msg));
            return;
        }

        ccState.movCache.set(String(d.id), resp);
        pintar(resp);
    } catch (e) {
        console.error('Error cargando movimientos del cierre:', e);
        TABS_MOV.forEach((t) => $(`#cc-pane-${t.key}`).html('<div class="alert alert-danger small py-2 m-0">Error interno de la aplicación</div>'));
    }
};

const mostrarTab = (tab) => {
    const btn = document.querySelector(`#cc-detalle-tabs [data-bs-target="#cc-pane-${tab}"]`);
    if (btn && window.bootstrap) bootstrap.Tab.getOrCreateInstance(btn).show();
};

const abrirDetalle = (id, tabInicial = 'resumen') => {
    const d = ccState.data.find((x) => String(x.id) === String(id));
    if (!d) return;
    ccState.detalleActual = d;

    const e = CC_ESTADOS[d.estado];
    const fx = CC_CFG.efectivoEsperado;
    const gEf = d.grupos.find((g) => g.key === 'efectivo');

    const filasCmp = d.grupos.map((g) => `
        <tr>
            <td><i class="fa-solid ${g.icon} me-2 text-primary"></i>${g.txt}</td>
            <td class="text-end">${money(g.registrado)}</td>
            <td class="text-end">${money(g.sistema)}</td>
            <td class="text-end"><span class="cc-pill cc-dif-${difClase(g.dif)}">${moneyDif(g.dif)}</span></td>
        </tr>`).join('');

    const filasMedios = GRUPOS.map((g) => {
        const lista = d.medios.filter((m) => m.grupo === g && m.valor !== 0);
        if (!lista.length) return '';
        return `
            <tr class="cc-tr-grupo"><td colspan="2">${CC_GRUPOS[g].txt}</td></tr>
            ${lista.map((m) => `
                <tr>
                    <td class="ps-3">${esc(m.label)}</td>
                    <td class="text-end">${money(m.valor)}</td>
                </tr>`).join('')}`;
    }).join('');

    const resumen = `
        ${d.estado !== 'ok' ? `<div class="alert alert-${e.cls} py-2 small mb-3"><i class="fa-solid ${e.icon} me-1"></i>${explicacionEstado(d)}</div>` : ''}

        <div class="cc-total cc-dif-${difClase(d.dif)} mb-3">
            <div>
                <div class="cc-label">Diferencia total</div>
                <div class="cc-big">${moneyDif(d.dif)}</div>
            </div>
            <div class="text-end small">
                <div>Registró <strong>${money(d.totalRegistrado)}</strong></div>
                <div>Sistema <strong>${money(d.totalSistema)}</strong></div>
            </div>
        </div>

        <h6 class="mb-2">Registrado vs sistema</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th></th><th class="text-end">Cajero</th><th class="text-end">Sistema</th><th class="text-end">Diferencia</th></tr></thead>
                <tbody>${filasCmp}</tbody>
            </table>
        </div>

        <h6 class="mb-2">¿Cómo se calcula el efectivo esperado?</h6>
        <div class="cc-calc small mb-3">
            <div><a href="#" class="cc-ir-tab" data-tab="ventas">Ventas en efectivo <i class="fa-solid fa-chevron-right small"></i></a><span>${money(d.ventas.efectivo)}</span></div>
            ${fx.sumarBase ? `<div><span>+ Base</span><span>${money(d.base)}</span></div>` : ''}
            ${fx.sumarIngresos ? `<div><a href="#" class="cc-ir-tab" data-tab="ingresos">+ Ingresos de caja <i class="fa-solid fa-chevron-right small"></i></a><span class="text-success">${money(d.ingresos)}</span></div>` : ''}
            ${fx.restarEgresos ? `<div><a href="#" class="cc-ir-tab" data-tab="egresos">− Egresos de caja <i class="fa-solid fa-chevron-right small"></i></a><span class="text-danger">${money(d.egresos)}</span></div>` : ''}
            <div class="border-top pt-2"><strong>= Efectivo esperado</strong><strong class="cc-strong">${money(gEf.sistema)}</strong></div>
            <div><span>Efectivo registrado por el cajero</span><strong class="cc-strong">${money(gEf.registrado)}</strong></div>
        </div>
        ${!fx.sumarBase ? `
        <div class="alert alert-info py-2 small mb-3">
            <i class="fa-solid fa-circle-info me-1"></i>
            <strong>Base de caja: ${money(d.base)}.</strong> Es un valor fijo asignado al abrir la caja; se muestra como referencia y no hace parte del cálculo de diferencias.
        </div>` : ''}

        <h6 class="mb-2">Medios de pago según el sistema</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm mb-0">
                <tbody>${filasMedios || '<tr><td class="text-muted">Sin ventas registradas</td></tr>'}</tbody>
            </table>
        </div>

        <div class="cc-info-grid mb-3">
            <div><span>Cajero</span><strong>${esc(d.nombres || '')}${d.usuario ? ` <small class="text-muted">(${esc(d.usuario)})</small>` : ''}</strong></div>
            <div><span>Bodega</span><strong>${esc(nombreBodega(d.bodega))}</strong></div>
            <div><span>Apertura</span><strong>${fmtFecha(d.apertura)}</strong></div>
            <div><span>Cierre</span><strong>${d.abierto ? 'En curso' : fmtFecha(d.cierre)}</strong></div>
        </div>

        <h6 class="mb-1">Observaciones del cajero</h6>
        <div class="small p-2 rounded cc-obs-box">${d.observaciones ? esc(d.observaciones) : '<span class="text-muted">Sin observaciones</span>'}</div>`;

    const tabs = [{ key: 'resumen', txt: 'Resumen', icon: 'fa-scale-balanced' }, ...TABS_MOV];

    $('#cc-detalle-titulo').html(`
        <span class="d-block">${esc(d.nombres || d.usuario)} <span class="badge bg-label-${e.cls} ms-1 align-middle">${e.txt}</span></span>
        <small class="text-muted fw-normal">Cierre #${esc(d.id)} · ${esc(nombreBodega(d.bodega))} · ${fmtFecha(d.apertura)} → ${d.abierto ? 'en curso' : fmtFecha(d.cierre)}</small>`);

    $('#cc-detalle-body').html(`
        <ul class="nav nav-pills cc-tabs mb-3" id="cc-detalle-tabs" role="tablist">
            ${tabs.map((t) => `
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link ${t.key === 'resumen' ? 'active' : ''}" data-bs-toggle="pill"
                            data-bs-target="#cc-pane-${t.key}" role="tab" aria-controls="cc-pane-${t.key}">
                        <i class="fa-solid ${t.icon} me-1"></i>${t.txt}
                        ${t.key !== 'resumen' ? `<span class="badge rounded-pill bg-label-${t.cls} ms-1" id="cc-tabcount-${t.key}">…</span>` : ''}
                    </button>
                </li>`).join('')}
        </ul>
        <div class="tab-content p-0">
            <div class="tab-pane fade show active" id="cc-pane-resumen" role="tabpanel">${resumen}</div>
            ${TABS_MOV.map((t) => `<div class="tab-pane fade" id="cc-pane-${t.key}" role="tabpanel"></div>`).join('')}
        </div>`);

    $('#cc-detalle-print').prop('disabled', d.abierto);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('cc-modal-detalle')).show();

    if (tabInicial !== 'resumen') mostrarTab(tabInicial);
    cargarMovimientos(d);
};

const imprimirCierre = (data) => {
    const titulo = `Cierre de caja #${esc(data.id_apertura)}`;
    const url = `../pdf/cierre_caja.php?id=${encodeURIComponent(data.id_apertura)}`;

    $('#modalDoc').remove();
    $('body').append(`
        <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-fullscreen-sm-down" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${titulo}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe width="100%" style="height:75vh; border:none;" src="${url}"></iframe>
                    </div>
                </div>
            </div>
        </div>`);

    $('#modalDoc').modal('show');
};

/* =====================================================================
 * FILTROS DE FECHA
 * ===================================================================== */
const setRango = (rango) => {
    const hoy = new Date();
    let ini = new Date(hoy);
    let fin = new Date(hoy);

    if (rango === 'ayer') {
        ini.setDate(hoy.getDate() - 1);
        fin = new Date(ini);
    } else if (rango === '7d') {
        ini.setDate(hoy.getDate() - 6);
    } else if (rango === 'mes') {
        ini = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    }

    $('#fe_fecha_inicial').val(ymd(ini));
    $('#fe_fecha_final').val(ymd(fin));
    $('.cc-preset').removeClass('active');
    $(`.cc-preset[data-rango="${rango}"]`).addClass('active');
    actualizarTextoRango();
};

const actualizarTextoRango = () => {
    const ini = $('#fe_fecha_inicial').val();
    const fin = $('#fe_fecha_final').val();
    const preset = $('.cc-preset.active').text().trim();
    if (preset) return $('#cc-rango-txt').text(preset);
    if (!ini || !fin) return $('#cc-rango-txt').text('Filtros');
    const f = (s) => { const [, m, d] = s.split('-'); return `${d}/${m}`; };
    $('#cc-rango-txt').text(ini === fin ? f(ini) : `${f(ini)} – ${f(fin)}`);
};

const cerrarFiltrosMovil = () => {
    if (!esMovil() || !window.bootstrap) return;
    bootstrap.Collapse.getOrCreateInstance(document.getElementById('cc-filtros'), { toggle: false }).hide();
};

/* =====================================================================
 * CARGA Y CONSULTA
 * ===================================================================== */
const cargarBodegas = async () => {
    const resp = await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'bodegas'
    });

    let html = '<option value="0">Todas las bodegas</option>';
    ccState.bodegas.clear();

    if (Array.isArray(resp)) {
        resp.forEach((b) => {
            ccState.bodegas.set(String(b.almacen), b.descripcion);
            html += `<option value="${esc(b.almacen)}">${esc(b.almacen)} - ${esc(b.descripcion)}</option>`;
        });
    }

    $('#slcBodegas').html(html).select2({ width: '100%' });
};

const consultarCierres = async () => {
    const fechaInicio = $('#fe_fecha_inicial').val();
    const fechaFin = $('#fe_fecha_final').val();
    const bodegaSeleccionada = $('#slcBodegas').val() || 0;

    if (!fechaInicio || !fechaFin) {
        Swal.fire('Advertencia', 'Debe seleccionar ambas fechas', 'warning');
        return;
    }
    if (fechaFin < fechaInicio) {
        Swal.fire('Error', 'La fecha final no puede ser anterior a la fecha inicial', 'error');
        return;
    }

    showLoadingWithTetrisLogo('Buscando información', 'ISA Software');

    try {
        const resp = await enviarPeticion({
            link: link_,
            op: 's_cierres_cajas',
            fechaInicio,
            fechaFin,
            bodegaSeleccionada
        });

        if (resp && resp.error) {
            limpiarResultados(ShowAlert(resp.message || 'No fue posible consultar los cierres', 'error'));
            return;
        }

        if (!Array.isArray(resp) || resp.length === 0) {
            limpiarResultados(`
                <div class="cc-empty">
                    <i class="fa-solid fa-cash-register d-block"></i>
                    <div class="fw-semibold cc-strong">No se encontraron cierres</div>
                    <div class="small">Prueba con otro rango de fechas o bodega.</div>
                </div>`);
            return;
        }

        ccState.movCache.clear(); // cada consulta trae datos frescos
        ccState.data = enriquecer(resp);
        renderDashboard();
        cerrarFiltrosMovil();
    } catch (e) {
        console.error('Error en consultar cierres:', e);
        limpiarResultados(ShowAlert('Error interno de la aplicación', 'error'));
    } finally {
        dissminSwal();
    }
};

/* ---------- Filtros activos (cajero, búsqueda, estado) ---------- */
const hayFiltros = () => ccState.cajero !== null || ccState.busqueda.trim() !== '' || ccState.filtro !== 'todos';

const renderFiltrosActivos = () => {
    const chips = [];
    if (ccState.cajero !== null) {
        chips.push(`<span class="cc-activo"><i class="fa-regular fa-user"></i>${esc(ccState.cajeroNombre)}
            <button type="button" class="cc-activo-x" data-quitar="cajero" aria-label="Quitar filtro de cajero">&times;</button></span>`);
    }
    if (ccState.busqueda.trim()) {
        chips.push(`<span class="cc-activo"><i class="fa fa-search"></i>“${esc(ccState.busqueda.trim())}”
            <button type="button" class="cc-activo-x" data-quitar="busqueda" aria-label="Quitar búsqueda">&times;</button></span>`);
    }
    if (ccState.filtro !== 'todos') {
        const txt = $(`#cc-chips [data-filtro="${ccState.filtro}"]`).clone().children().remove().end().text().trim();
        chips.push(`<span class="cc-activo"><i class="fa-solid fa-filter"></i>${esc(txt)}
            <button type="button" class="cc-activo-x" data-quitar="estado" aria-label="Quitar filtro de estado">&times;</button></span>`);
    }

    if (!chips.length) {
        $('#cc-filtros-activos').addClass('d-none').html('');
        return;
    }
    $('#cc-filtros-activos').removeClass('d-none').html(`
        <span class="small text-muted">Filtrando por:</span>
        ${chips.join('')}
        <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="cc-ver-todo"><i class="fa-solid fa-rotate-left me-1"></i>Ver todo</button>`);
};

const quitarFiltro = (tipo) => {
    if (tipo === 'cajero' || tipo === 'todo') { ccState.cajero = null; ccState.cajeroNombre = ''; }
    if (tipo === 'busqueda' || tipo === 'todo') { ccState.busqueda = ''; $('#cc-buscar').val(''); }
    if (tipo === 'estado' || tipo === 'todo') { ccState.filtro = 'todos'; }
    actualizarConteos();
    marcarChip(ccState.filtro);
    renderPanelCajeros();
    renderResultados();
};

const filtrarCajero = (clave, nombre) => {
    if (ccState.cajero === clave) {
        quitarFiltro('cajero');         // segundo toque sobre el mismo cajero: quita el filtro
        return;
    }
    ccState.cajero = clave;
    ccState.cajeroNombre = nombre;
    renderPanelCajeros();
    aplicarFiltroEstado(ccState.filtro);
};

const limpiar = () => {
    setRango('hoy');
    $('#slcBodegas').val('0').trigger('change');
    ccState.filtro = 'todos';
    ccState.busqueda = '';
    ccState.cajero = null;
    ccState.cajeroNombre = '';
    $('#cc-buscar').val('');
    marcarChip('todos');
    $('#cc-filtros-activos').addClass('d-none').html('');
    limpiarResultados();
};

const aplicarFiltroEstado = (filtro, desplazar = true) => {
    ccState.filtro = filtro;
    actualizarConteos();
    marcarChip(filtro);
    renderResultados();
    if (!desplazar) return;
    const top = $('#cc-toolbar').offset();
    if (top) $('html, body').animate({ scrollTop: top.top - 8 }, 250);
};

/* =====================================================================
 * EVENTOS
 * ===================================================================== */
const bindEventos = () => {
    $('#btn-busqueda').on('click', consultarCierres);
    $('#btn-limpiar').on('click', limpiar);

    $('.cc-preset').on('click', function () {
        setRango($(this).data('rango'));
        consultarCierres();
    });

    $('#fe_fecha_inicial, #fe_fecha_final').on('change', () => {
        $('.cc-preset').removeClass('active');
        actualizarTextoRango();
    }).on('keydown', (e) => { if (e.key === 'Enter') consultarCierres(); });

    $('#cc-buscar').on('input', debounce(function () {
        ccState.busqueda = $(this).val();
        renderResultados();
    }));

    $('#cc-chips').on('click', '.cc-chip', function () {
        const filtro = $(this).data('filtro');
        if (filtro === 'todos') quitarFiltro('todo');
        else aplicarFiltroEstado(filtro, false);
    });

    $('#cc-filtros-activos').on('click', '.cc-activo-x', function () {
        quitarFiltro($(this).data('quitar'));
    });
    $('#cc-filtros-activos').on('click', '#cc-ver-todo', () => quitarFiltro('todo'));

    $('#cc-orden').on('change', function () {
        ccState.orden = $(this).val();
        renderResultados();
    });

    $('[data-vista]').on('click', function () {
        ccState.vista = $(this).data('vista');
        $('[data-vista]').removeClass('active');
        $(this).addClass('active');
        renderResultados();
    });

    $('#cc-kpis').on('click keydown', '[data-accion]', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter') return;
        const accion = $(this).data('accion');
        if (accion === 'todos') quitarFiltro('todo');
        else aplicarFiltroEstado(accion);
    });

    // Cajero → filtra sus cierres
    $('#cc-cajeros').on('click keydown', '.cc-cajero', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter') return;
        filtrarCajero(String($(this).data('cajero')), String($(this).data('nombre')));
    });

    $('#cc-vacio').on('click', '#cc-reset-filtros', () => quitarFiltro('todo'));

    // Tarjeta → detalle
    $('#cc-lista').on('click keydown', '.cc-card', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter') return;
        if ($(e.target).closest('.cc-btn-print').length) return;
        const link = $(e.target).closest('.cc-link-tab');
        if (link.length) e.preventDefault();
        abrirDetalle($(this).data('id'), link.length ? link.data('tab') : 'resumen');
    });

    // Dentro del detalle: "Ingresos / Egresos / Ventas" llevan a su pestaña
    $('#cc-detalle-body').on('click', '.cc-ir-tab', function (e) {
        e.preventDefault();
        mostrarTab($(this).data('tab'));
    });

    $('#cc-lista').on('click', '.cc-btn-print', function (e) {
        e.stopPropagation();
        const d = ccState.data.find((x) => String(x.id) === String($(this).data('id')));
        if (d) imprimirCierre(d.raw);
    });

    $('#cc-detalle-print').on('click', () => {
        if (!ccState.detalleActual) return;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('cc-modal-detalle')).hide();
        imprimirCierre(ccState.detalleActual.raw);
    });

    // Al pasar de escritorio a móvil (o al revés) con la vista tabla activa, se reajusta
    let movilAnterior = esMovil();
    $(window).on('resize', debounce(() => {
        const movil = esMovil();
        if (movil !== movilAnterior && ccState.data.length && ccState.vista === 'tabla') renderResultados();
        movilAnterior = movil;
    }, 300));
};

/* =====================================================================
 * INICIO
 * ===================================================================== */
$(async function () {
    setRango('hoy');
    bindEventos();
    try {
        await cargarBodegas();
    } catch (e) {
        console.error('Error cargando bodegas:', e);
    }
    dissminSwal();
    consultarCierres(); // Muestra los cierres de hoy al entrar
});