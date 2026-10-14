const listarAlmacenes = async () => {
    try {
        const resp = await bodegasUsuarios();
        let options = `<option value="0">Todas las bodegas</option>`;
        // ordenar el arrya por bodega 
        resp.sort((a, b) => a.bodega.localeCompare(b.bodega));
        resp.forEach((d) => {
            options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
        });
        $("#bodegas").html(options);
    } catch (e) {
        console.error(e)
        Swal.fire("Error", "Error al cargar las bodegas", "error");
    }
};

const limpiar = () => {
    $("#resultado").html("");
    fechaActual([[
        'fe_fecha_inicial',
        'fe_fecha_final'
    ]]);
    $("#bodegas").val(0);
    $("#naturaleza").val('T');
};

const consultarMovimientos = async (fechaInicio, fechaFin) => {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
    try {
        const data = {
            link: '../models/rptConciliaciones.php',
            op: 'consultar',
            fecha_inicio: fechaInicio,
            fecha_fin: fechaFin,
            naturaleza: $("#naturaleza").val(),
            id_bodega: $("#bodegas").val()
        };

        const resp = await enviarPeticion(data);
        console.log({ resp })
        dissminSwal();

        if (resp.error) {
            Swal.fire("Error", resp.mensaje, "error");
            return;
        }
        if (resp.data.length == 0) {
            $("#resultado").html(ShowAlert("No se encontraron resultados", "warning"));
            return;
        }

        // Preparar datos para DataTable
        crearCards();
        mostrarTablaMovimientos(resp.data, fechaInicio, fechaFin);

    } catch (e) {
        console.error(e);
        dissminSwal();
        Swal.fire("Error", "Error interno de la aplicación", "error");
    }
}

const crearCards = () => {
    $("#resultado").html(`<div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white py-1 px-2 d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-cash-coin me-1"></i> Efectivo</span>
                                        <span id="totalEfectivo" class="fw-bold">$0</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <ul class="list-group list-group-flush small">
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Ventas</span>
                                                <span id="efectivoVentas" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Devoluciones</span>
                                                <span id="efectivoDevoluciones" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Ingresos</span>
                                                <span id="efectivoIngresos" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Egresos</span>
                                                <span id="efectivoEgresos" class="fw-semibold">$0</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white py-1 px-2 d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-bank me-1"></i> Bancos</span>
                                        <span id="totalBancos" class="fw-bold">$0</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <ul class="list-group list-group-flush small">
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Ventas</span>
                                                <span id="bancosVentas" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Devoluciones</span>
                                                <span id="bancosDevoluciones" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Ingresos</span>
                                                <span id="bancosIngresos" class="fw-semibold">$0</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between py-1 px-2">
                                                <span>Egresos</span>
                                                <span id="bancosEgresos" class="fw-semibold">$0</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="tablaMovimientos" class="table table-striped table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Movimiento</th>
                                    <th>Documento</th>
                                    <th>Usuario</th>
                                    <th>Medio de Pago</th>
                                    <th>Cuenta</th>
                                    <th>Naturaleza</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>`);
}

/**
 * Renderiza la tabla de movimientos usando createDataTable
 * y dispara la actualización de las cards de resumen (Efectivo/Bancos).
 * @param {Array} movimientos - Array de movimientos (ventas, devoluciones, ingresos, egresos)
 */
function mostrarTablaMovimientos(movimientos) {
    movimientos = movimientos || [];

    // Pre-formateo porque createDataTable no soporta render por columna,
    // solo mapea {title, data} directo del objeto.
    const datosFormateados = movimientos
        .slice()
        .sort((a, b) => new Date(a.fecha_hora) - new Date(b.fecha_hora))
        .map(function (mov) {
            let valor = parseFloat(mov.valor);
            let valorConciliacion = parseFloat(mov.valor_conciliacion);

            return {
                fecha_hora_fmt: formatearFechaHora(mov.fecha_hora),
                badge_tipo_mov: obtenerBadgeTipoMov(mov.tipo_mov),
                documento: `${mov.tipo}-${mov.numero}`,
                usuario: mov.usuario,
                medio_pago: mov.medio_pago,
                cuenta: mov.cuenta,
                badge_naturaleza: obtenerBadgeNaturaleza(mov.naturaleza),
                valor_fmt: `<span class="${valor < 0 ? 'text-primary fw-semibold' : ''}">${formatearMoneda(valor)}</span>`,
                valor_conciliacion_fmt: `<span class="fw-semibold">${formatearMoneda(valorConciliacion)}</span>`,
                voucher: mov.voucher || ''
            };
        });

    const dataNames = [
        'fecha_hora_fmt', 'badge_tipo_mov', 'documento', 'usuario',
        'medio_pago', 'cuenta', 'badge_naturaleza', 'valor_fmt', 'voucher'
    ];

    const columnNames = [
        'Fecha/Hora', 'Movimiento', 'Documento', 'Usuario',
        'Medio de Pago', 'Cuenta', 'Naturaleza', 'Valor', 'voucher'
    ];

    // Si ya existe una instancia (ej. al recargar el reporte con otro rango de fechas), se destruye primero
    if ($.fn.DataTable.isDataTable('#tablaMovimientos')) {
        $('#tablaMovimientos').DataTable().destroy();
        $('#tablaMovimientos').empty();
    }

    createDataTable(
        'tablaMovimientos',
        datosFormateados,
        dataNames,
        columnNames,
        false,   // sin botón editar: es un reporte de solo lectura
        false,   // sin botón eliminar
        false,   // sin botón adicional
        '',
        '',
        true    // sin exportar (cámbialo a true si lo necesitas)
    );

    actualizarCardsConciliacion(movimientos);
}

/**
 * Consolida los movimientos en las dos cards (Efectivo / Bancos),
 * con desglose de ventas, devoluciones, ingresos y egresos.
 */
function actualizarCardsConciliacion(movimientos) {
    let resumen = {
        efectivo: { ventas: 0, devoluciones: 0, ingresos: 0, egresos: 0 },
        bancos: { ventas: 0, devoluciones: 0, ingresos: 0, egresos: 0 }
    };

    movimientos.forEach(function (mov) {
        let valorConciliacion = parseFloat(mov.valor_conciliacion);
        let grupo = mov.naturaleza === 'E' ? resumen.efectivo : resumen.bancos;

        switch (mov.tipo_mov) {
            case 'VENTA': grupo.ventas += valorConciliacion; break;
            case 'DEVOLUCION': grupo.devoluciones += valorConciliacion; break;
            case 'INGRESO': grupo.ingresos += valorConciliacion; break;
            case 'EGRESO': grupo.egresos += valorConciliacion; break; // ya viene negativo
        }
    });

    pintarCardNaturaleza('efectivo', resumen.efectivo);
    pintarCardNaturaleza('bancos', resumen.bancos);
}

function pintarCardNaturaleza(prefijo, datos) {
    let total = datos.ventas - datos.devoluciones + datos.ingresos + datos.egresos;

    $(`#total${capitalizar(prefijo)}`).text(formatearMoneda(total));
    $(`#${prefijo}Ventas`).text(formatearMoneda(datos.ventas));
    $(`#${prefijo}Devoluciones`).text(formatearMoneda(datos.devoluciones));
    $(`#${prefijo}Ingresos`).text(formatearMoneda(datos.ingresos));
    $(`#${prefijo}Egresos`).text(formatearMoneda(Math.abs(datos.egresos)));
}

function capitalizar(texto) {
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function obtenerBadgeTipoMov(tipoMov) {
    const badges = {
        'VENTA': '<span class="badge border border-primary text-primary">Venta</span>',
        'DEVOLUCION': '<span class="badge border border-danger text-danger">Devolución</span>',
        'INGRESO': '<span class="badge border border-success text-success">Ingreso</span>',
        'EGRESO': '<span class="badge border border-warning text-warning">Egreso</span>'
    };
    return badges[tipoMov] || `<span class="badge bg-secondary">${tipoMov}</span>`;
}

function obtenerBadgeNaturaleza(naturaleza) {
    return naturaleza === 'E'
        ? '<span class="badge border border-primary text-primary">Efectivo</span>'
        : '<span class="badge border border-success text-success">Bancos</span>';
}

function formatearMoneda(valor) {
    return valor.toLocaleString('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    });
}

function formatearFechaHora(fechaHora) {
    let fecha = new Date(fechaHora.replace(' ', 'T'));
    return fecha.toLocaleString('es-CO', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

const fechaHoraActual = () => {
    const ahora = new Date();

    // Función para rellenar con ceros si es necesario (ej: '9' -> '09')
    const pad = (n) => String(n).padStart(2, '0');

    const anio = ahora.getFullYear();
    const mes = pad(ahora.getMonth() + 1);
    const dia = pad(ahora.getDate());
    const hora = pad(ahora.getHours());
    const minutos = pad(ahora.getMinutes());

    // 1. Fecha inicial: Por defecto el inicio del día actual (00:00)
    const fechaInicioDefault = `${anio}-${mes}-${dia}T00:00`;

    // 2. Fecha final: Por defecto la hora y minuto exactos de ahora
    const fechaFinDefault = `${anio}-${mes}-${dia}T${hora}:${minutos}`;

    // Asignar los valores a los inputs
    const inputInicio = document.getElementById('fe_fecha_inicial');
    const inputFin = document.getElementById('fe_fecha_final');

    if (inputInicio) inputInicio.value = fechaInicioDefault;
    if (inputFin) inputFin.value = fechaFinDefault;
}
/** */
$(function () {
    listarAlmacenes();
    fechaHoraActual();
    $("#btn-busqueda").click(function () {
        let fhIni = $("#fe_fecha_inicial").val();
        let fhFin = $("#fe_fecha_final").val();
        if (!fhIni || !fhFin) {
            Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
            return;
        }
        // Convertir a objeto Date para comparar
        let dIni = new Date(fhIni);
        let dFin = new Date(fhFin);
        if (dIni > dFin) {
            Swal.fire("Advertencia", "La fecha inicial no puede ser mayor a la fecha final", "warning");
            return;
        }
        consultarMovimientos(fhIni, fhFin)
    });
    $("#btn-limpiar").click(function () {
        limpiar();
    });
    dissminSwal();
})