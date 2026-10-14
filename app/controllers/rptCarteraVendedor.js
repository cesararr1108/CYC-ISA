const listarVendedores = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../models/RptVentasVendedor.php',
            op: 'consultar_vendedores'
        });
        $("#slcVendedores").append(`<option value="0">Todos</option>`);
        resp.data.forEach(usuario => {
            $("#slcVendedores").append(`<option value="${usuario.id}">${usuario.nit} - ${usuario.razon_comercial} </option>`)
        });
    } catch (error) {
        console.error(error)
        Swal.fire("Error", "Error al obtener la información", "error")
    }
}

const listarZonas = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../models/RptVentasVendedor.php',
            op: 'consultar_zonas'
        });
        $("#slcZonas").append(`<option value="0">Todos</option>`);
        resp.forEach(zona => {
            $("#slcZonas").append(`<option value="${zona.id}">${zona.codigo_zona} - ${zona.descripcion}</option>`)
        });
    } catch (error) {
        console.error(error)
        Swal.fire("Error", "Error al obtener la información", "error")
    }
}

const cargaAnios = async (ob) => {
    var anio_actual = new Date().getFullYear();
    var option = '';
    for (var i = anio_actual; i >= 2008; i--) {
        if (anio_actual == i) {
            option += "<option value='" + i + "' selected='selected'>" + i + "</option>";
        } else {
            option += '<option value="' + i + '">' + i + '</option>';
        }
    }
    $("#" + ob).html(option);
}

const consultar = async () => {
    showLoadingWithTetrisLogo('Generando reporte', 'ISA Software')

    try {

        const tipo_vista = $('input[name="tipo_vista"]:checked').val();

        const resp = await enviarPeticion({
            link: '../models/rptCarteraVendedor.php',
            op: 'consultar',
            tipo: $("#Stipo").val(),
            id_vendedor: $("#slcVendedores").val(),
            id_zona: $("#slcZonas").val(),
            anio: $("#SAno").val()
        })


        const filas = resp;

        if (filas.length == 0) {
            dissminSwal()
            $("#result").html('<div class="alert alert-warning">Sin resultados para mostrar</div>')
            return
        }

        // Dentro de tu función consultar, cuando ya tienes filas:
        const tb = `
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportarAExcel()">
                                <i class="fas fa-file-excel"></i> Exportar a Excel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" id="ventas">
                                <thead class="table-primary"></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    `;
        $("#result").html(tb)

        renderTotales(filas)

        if (tipo_vista === "consolidada") {
            renderVistaConsolidada(filas);
        } else {
            renderVistaDetallada(filas);
        }

        dissminSwal()

    } catch (error) {

        dissminSwal()
        console.error(error)
        Swal.fire("Error", "Error al obtener la información", "error")

    }

}

const exportarAExcel = () => {
    const table = document.getElementById('ventas');

    if (!table || table.rows.length === 0) {
        Swal.fire("Atención", "No hay datos para exportar", "warning");
        return;
    }

    let csv = [];
    const rows = table.querySelectorAll('tr');

    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');

        for (let j = 0; j < cols.length; j++) {
            // Limpiamos saltos de línea y espacios excesivos
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').trim();
            // Reemplazamos comillas dobles internas y envolvemos en comillas
            row.push('"' + data.replace(/"/g, '""') + '"');
        }
        // Usamos punto y coma (;) para que Excel en español separe bien las columnas
        csv.push(row.join(';'));
    }

    // Crear el archivo Blob con codificación UTF-8 para que reconozca tildes y símbolos de moneda ($)
    const csvFile = new Blob(["\ufeff" + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });

    // Crear un enlace temporal para forzar la descarga
    const downloadLink = document.createElement('a');
    downloadLink.href = window.URL.createObjectURL(csvFile);

    // Nombrar el archivo con la fecha actual
    const fechaActual = new Date().toISOString().slice(0, 10);
    downloadLink.download = `reporte_cartera_${fechaActual}.csv`;

    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
};

// --- 1. RENDERIZAR CARDS DE TOTALES ---
const renderTotales = (filas) => {
    // Calculamos valores acumulados
    let totalCartera = 0;
    let totalVencida = 0;
    let totalSinVencer = 0;
    let c1_30 = 0, c31_60 = 0, c61_90 = 0, c91_120 = 0;

    // Usamos un Set para contar clientes únicos (puedes cambiar 'id_tercero' por el campo correspondiente si lo tienes en la fila)
    const clientesUnicos = new Set();

    filas.forEach(f => {
        const pendiente = parseFloat(f.total || 0);
        totalCartera += pendiente;
        totalVencida += parseFloat(f.total_vencida || 0);
        totalSinVencer += parseFloat(f.total_sin_vencer || 0);

        c1_30 += parseFloat(f.c_1_30 || 0);
        c31_60 += parseFloat(f.c_31_60 || 0);
        c61_90 += parseFloat(f.c_61_90 || 0);
        c91_120 += parseFloat(f.c_91_120 || 0);

        if (f.id_tercero) clientesUnicos.add(f.id_tercero);
    });

    const formatoMoneda = (val) => '$ ' + val.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const htmlCards = `
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <h6 class="card-title">Total Clientes / Registros</h6>
                        <h5>${clientesUnicos.size > 0 ? clientesUnicos.size : filas.length}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <h6 class="card-title">Total Vencido</h6>
                        <h5>${formatoMoneda(totalVencida)}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card  mb-3">
                    <div class="card-body py-2">
                        <h6 class="card-title">Total Sin Vencer</h6>
                        <h5>${formatoMoneda(totalSinVencer)}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <h6 class="card-title">Cartera Total Pendiente</h6>
                        <h5>${formatoMoneda(totalCartera)}</h5>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Insertamos las cards antes de la tabla de resultados
    $("#result").prepend(htmlCards);
};


// --- 2. RENDERIZAR VISTA CONSOLIDADA (Agrupado por Vendedor y Zona) ---
const renderVistaConsolidada = (filas) => {
    const agrupado = {};

    filas.forEach(f => {
        const key = `${f.id_vendedor}_${f.id_zona}`;

        if (!agrupado[key]) {
            agrupado[key] = {
                vendedor: f.nombres_vendedor || 'SIN VENDEDOR ASIGNADO',
                zona: f.des_zona || 'SIN ZONA ASIGNADA',
                total: 0,
                vencida: 0,
                sin_vencer: 0,
                c_1_30: 0,
                c_31_60: 0,
                c_61_90: 0,
                c_91_120: 0
            };
        }

        agrupado[key].total += parseFloat(f.total || 0);
        agrupado[key].vencida += parseFloat(f.total_vencida || 0);
        agrupado[key].sin_vencer += parseFloat(f.total_sin_vencer || 0);
        agrupado[key].c_1_30 += parseFloat(f.c_1_30 || 0);
        agrupado[key].c_31_60 += parseFloat(f.c_31_60 || 0);
        agrupado[key].c_61_90 += parseFloat(f.c_61_90 || 0);
        agrupado[key].c_91_120 += parseFloat(f.c_91_120 || 0);
    });

    const formato = (v) => v.toLocaleString('es-CO', { minimumFractionDigits: 2 });

    let thead = `<tr>
        <th>Vendedor</th>
        <th>Zona</th>
        <th>Sin Vencer</th>
        <th>1 - 30</th>
        <th>31 - 60</th>
        <th>61 - 90</th>
        <th>91 - 120</th>
        <th>Total Vencido</th>
        <th>Total Cartera</th>
    </tr>`;

    let tbody = '';
    Object.values(agrupado).forEach(item => {
        const totalVencidoItem = item.c_1_30 + item.c_31_60 + item.c_61_90 + item.c_91_120;
        tbody += `
            <tr>
                <td>${item.vendedor}</td>
                <td>${item.zona}</td>
                <td>$ ${formato(item.sin_vencer)}</td>
                <td>$ ${formato(item.c_1_30)}</td>
                <td>$ ${formato(item.c_31_60)}</td>
                <td>$ ${formato(item.c_61_90)}</td>
                <td>$ ${formato(item.c_91_120)}</td>
                <td>$ ${formato(totalVencidoItem)}</td>
                <td><strong>$ ${formato(item.total)}</strong></td>
            </tr>
        `;
    });

    $("#ventas thead").html(thead);
    $("#ventas tbody").html(tbody);
};


// --- 3. RENDERIZAR VISTA DETALLADA (Todas las filas) ---
const renderVistaDetallada = (filas) => {
    const formato = (v) => v.toLocaleString('es-CO', { minimumFractionDigits: 2 });

    let thead = `<tr>
        <th>Tipo/Nro</th>
        <th>Cliente</th>
        <th>Vendedor</th>
        <th>Zona</th>
        <th>Fecha</th>
        <th>Vencimiento</th>
        <th>Días</th>
        <th>Sin Vencer</th>
        <th>Vencido</th>
        <th>Total</th>
    </tr>`;

    let tbody = '';
    filas.forEach(f => {
        let clase = 'badge bg-success';
        if (f.dias > 0) {
            clase = 'badge bg-danger';
        }

        tbody += `
            <tr>
                <td>${f.tipo} - ${f.numero}</td>
                <td>${f.nombres_tercero} <div class="mt-1 text-muted">${f.razon_comercial} </div></td>
                <td>${f.nombres_vendedor}</td>
                <td>${f.des_zona}</td>
                <td>${f.fecha}</td>
                <td>${f.vencimiento || 'N/A'}</td>
                <td><span class="${clase}">${f.dias}</span></td>
                <td>$ ${formato(parseFloat(f.total_sin_vencer || 0))}</td>
                <td>$ ${formato(parseFloat(f.total_vencida || 0))}</td>
                <td><strong>$ ${formato(parseFloat(f.total || 0))}</strong></td>
            </tr>
        `;
    });

    $("#ventas thead").html(thead);
    $("#ventas tbody").html(tbody);
};

$(async function () {
    await listarVendedores();
    await listarZonas();
    await cargaAnios('SAno');

    $("#btn-busqueda").click(function () {
        if ($("#fecha_inicial").val() == '' || $("#fecha_final").val() == '') {
            Swal.fire("Ups", "La fecha inicial y final es obligatoria", "error")
            return;
        }
        consultar();
    });
    dissminSwal();
})