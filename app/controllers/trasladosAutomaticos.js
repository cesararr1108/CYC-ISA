// Variable global para almacenar los datos recibidos con su estado de selección
let listaSugeridos = [];

const consultarSugerido = async () => {
    showLoadingWithTetrisLogo('Consultando sugerido...', 'Isa Software');
    try {
        const data = {
            link: '../models/trasladosAutomaticos.php',
            op: 'consultar',
            dias_rot: $("#filtro_meses").val(),
            dias_inv: $("#filtro_dias").val(),
            id_bodega_origen: $("#bodegas").val(),
            id_bodega_destino: $("#id_bodega_destino").val()
        };

        const resp = await enviarPeticion(data);

        if (resp && Array.isArray(resp) && resp.length > 0) {
            // Inicializamos cada item asegurando la propiedad 'seleccionado'
            // Por defecto, se marcan automáticamente solo los que tengan stock en origen > 0
            listaSugeridos = resp.map(item => ({
                ...item,
                seleccionado: Number(item.stock_origen) > 0
            }));
            $("#btn-procesar-traslado, #btn-solo-disponible").removeClass('d-none');

            let htmlTabla = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small">Total de registros: ${listaSugeridos.length}</span>
                </div>
                <div class="table-responsive text-nowrap" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-primary sticky-top">
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="check_todos" class="form-check-input" onchange="toggleMarcarTodosArray(this)">
                                </th>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Rot.Diaria Promedio</th>
                                <th>Dias inventario</th>
                                <th>Stock Destino</th>
                                <th>Stock Origen</th>
                                <th>Stock Mín.</th>
                                <th>Sugerido Traslado</th>
                                <th>Comprar Restante</th>
                                <th>Costo Prom.</th>
                                <th>Subtotal Comprar</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            listaSugeridos.forEach((item, index) => {
                let tieneStockOrigen = Number(item.stock_origen) > 0;
                let isDisabled = !tieneStockOrigen ? 'disabled' : '';
                let rowClass = !tieneStockOrigen ? 'table-warning text-muted' : '';
                let isChecked = item.seleccionado ? 'checked' : '';

                htmlTabla += `
                    <tr class="${rowClass}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input row-checkbox" data-index="${index}" ${isChecked} ${isDisabled} onchange="actualizarSeleccionItem(${index}, this)">
                        </td>
                        <td>${item.codigo}</td>
                        <td>${item.producto} <div class="text-primary fw-bold">${item.marca}</div></td>
                        <td>${item.rotacion_diaria_prom}</td>
                        <td>${item.stock_dias_inventario}</td>
                        <td class="text-center">${item.stock_actual}</td>
                        <td class="text-center fw-bold ${tieneStockOrigen ? 'text-primary' : 'text-danger'}">${item.stock_origen}</td>
                        <td class="text-center">${item.stock_min}</td>
                        <td class="text-center fw-bold text-success">${item.sugerido_traslado}</td>
                        <td class="text-center fw-bold text-danger">${item.comprar_restante}</td>
                        <td class="text-end">${formatNum(item.costo_promedio, '$', ConfGeneral)}</td>
                        <td class="text-end">${formatNum(item.sub_total_comprar, '$', ConfGeneral)}</td>
                    </tr>
                `;
            });

            htmlTabla += `
                        </tbody>
                    </table>
                </div>
            `;

            $("#resultado_sugerido").html(htmlTabla);

            // Sincronizar el estado inicial del checkbox "Marcar todos"
            actualizarEstadoCheckboxTodos();
            dissminSwal();

        } else {
            listaSugeridos = [];
            limpiarSugerido();
            $("#resultado_sugerido").html(`
                <div class="alert alert-info text-center" role="alert">
                    No se encontraron productos que requieran traslado o compra según los filtros seleccionados.
                </div>
            `);
            dissminSwal();
        }

    } catch (e) {
        dissminSwal();
        console.log("Error en la petición:", e);
    }
}

// Actualiza el estado de un producto específico en el array global
const actualizarSeleccionItem = (index, element) => {
    if (listaSugeridos[index]) {
        listaSugeridos[index].seleccionado = element.checked;
    }
    actualizarEstadoCheckboxTodos();
}

// Marca o desmarca todos los elementos habilitados en el array global
const toggleMarcarTodosArray = (source) => {
    let estado = source.checked;
    listaSugeridos.forEach(item => {
        if (Number(item.stock_origen) > 0) {
            item.seleccionado = estado;
        }
    });
    // Actualizamos visualmente los checkboxes de la tabla
    $('.row-checkbox:not(:disabled)').prop('checked', estado);
}

// Selecciona únicamente los que tienen stock en origen dentro del array global
const seleccionarSoloDisponiblesEnArray = () => {
    listaSugeridos.forEach((item, index) => {
        let tieneStock = Number(item.stock_origen) > 0;
        item.seleccionado = tieneStock;

        // Reflejar en el DOM de manera directa
        $(`.row-checkbox[data-index="${index}"]`).prop('checked', tieneStock);
    });
    actualizarEstadoCheckboxTodos();
}

// Función auxiliar para mantener coherencia en el checkbox principal "Todos"
const actualizarEstadoCheckboxTodos = () => {
    let habilitados = listaSugeridos.filter(item => Number(item.stock_origen) > 0);
    let seleccionados = habilitados.filter(item => item.seleccionado);

    if (habilitados.length > 0 && habilitados.length === seleccionados.length) {
        $('#check_todos').prop('checked', true);
        $('#check_todos').prop('indeterminate', false);
    } else if (seleccionados.length > 0) {
        $('#check_todos').prop('checked', false);
        $('#check_todos').prop('indeterminate', true); // Estado mixto visual
    } else {
        $('#check_todos').prop('checked', false);
        $('#check_todos').prop('indeterminate', false);
    }
}

const enviarProcesoTraslados = async () => {
    // Filtrar directamente del array global los elementos marcados
    let productosSeleccionados = listaSugeridos.filter(item => item.seleccionado);

    if (productosSeleccionados.length === 0) {
        Swal.fire("Oops", "Por favor, selecciona al menos un producto para procesar.", "info");
        return;
    }

    // --- NUEVA CONFIRMACIÓN ---
    const confirmacion = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Se procesarán ${productosSeleccionados.length} producto(s) para el traslado.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmacion.isConfirmed) {
        return; // Si el usuario cancela, detenemos la ejecución aquí
    }
    // --------------------------

    showLoadingWithTetrisLogo('Creando traslado temporal...', 'Isa Software');

    try {
        const payload = {
            link: '../models/trasladosAutomaticos.php',
            op: 'procesar_traslados',
            id_bodega_origen: $("#bodegas").val(),
            id_bodega_destino: $("#id_bodega_destino").val(),
            productos: JSON.stringify(productosSeleccionados),
            // Datos encabezado
            id_tipo_documento: $("#tipos").val(),
            id_tercero: $("#id_tercero").val(),
            id_bodega: $("#bodegas").val(),
            id_bodega_destino: $("#id_bodega_destino").val(),
            notas: $("#notas").val(),
            id_tercero_dir: $("#id_tercero_dir").val(),
            id_condicion: $("#id_condicion").val(),
            zona_ventas: $.trim($("#info_zona_venta").text()),
            zona_descripcion: $.trim($("#info_zona_descripcion").text())
        };

        const resp = await enviarPeticion(payload);

        if (resp && resp.ok) {
            limpiarSugerido();
            $("#modalTrasladosAuto").modal('hide');
            await recuperarPedido(resp.data);
            Swal.fire('Excelente!', 'Proceso terminado con éxito!', 'success');
        } else {
            Swal.fire('Error!', 'El proceso finalizó con observaciones, revisa la consola', 'error');
        }

    } catch (e) {
        console.error("Error al ejecutar los traslados:", e);
        Swal.fire('Error!', 'Ocurrió un error inesperado al procesar los traslados.', 'error');
    }
}

const limpiarSugerido = () => {
    listaSugeridos = [];
    $("#resultado_sugerido").html('');
    $("#filtro_dias").val(30);
    $("#filtro_meses").val(3);
    $("#btn-procesar-traslado, #btn-solo-disponible").addClass('d-none');
}

const exportarSugeridosExcel = () => {
    if (!listaSugeridos || listaSugeridos.length === 0) {
        Swal.fire("Oops", "No hay datos para exportar.", "info");
        return;
    }

    // 1. Definir los encabezados de las columnas para el archivo Excel/CSV
    let csvContent = "\uFEFF"; // BOM para asegurar que Excel reconozca los caracteres UTF-8 (tildes, eñes)

    const headers = [
        "Código",
        "Producto",
        "Marca",
        "Stock Destino",
        "Stock Origen",
        "Stock Mínimo",
        "Sugerido Traslado",
        "Comprar Restante",
        "Costo Promedio",
        "Subtotal Comprar",
        "Seleccionado"
    ];

    csvContent += headers.join(";") + "\n"; // Usamos punto y coma (;) que es el estándar que Excel prefiere en español

    // 2. Recorrer el array y armar las filas
    listaSugeridos.forEach(item => {
        // Limpiamos comillas dobles en textos por si acaso y envolvemos en comillas para evitar rupturas por espacios/comas
        const limpiarCampo = (texto) => `"${String(texto || '').replace(/"/g, '""')}"`;

        const row = [
            limpiarCampo(item.codigo),
            limpiarCampo(item.producto),
            limpiarCampo(item.marca),
            item.stock_actual,
            item.stock_origen,
            item.stock_min,
            item.sugerido_traslado,
            item.comprar_restante,
            item.costo_promedio,
            item.sub_total_comprar,
            item.seleccionado ? "SÍ" : "NO"
        ];

        csvContent += row.join(";") + "\n";
    });

    // 3. Crear un Blob con el contenido codificado
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);

    // 4. Crear un enlace temporal para forzar la descarga en el navegador
    const link = document.createElement("a");
    link.setAttribute("href", url);

    // Nombre del archivo con fecha actual para mayor orden
    const fechaActual = new Date().toISOString().slice(0, 10);
    link.setAttribute("download", `traslados_sugeridos_${fechaActual}.csv`);

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}