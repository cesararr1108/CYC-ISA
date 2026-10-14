const link_ = '../models/rptListasPrecios.php';

const listarAlmacenes = async () => {
    try {
        const resp = await bodegasUsuarios();
        let options = `<option value="0">Todas las bodegas</option>`;
        resp.sort((a, b) => a.descripcion.localeCompare(b.descripcion));
        resp.forEach((d) => {
            options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
        });
        $("#bodegas").html(options);
    } catch (e) {
        console.error(e);
        Swal.fire("Error", "Error al cargar las bodegas", "error");
    }
};

const listarMarcas = async () => {
    const resp = await enviarPeticion({
        link: '../models/Marcas.php',
        op: 'listar',
    });
    let p = '<option value="0">Todas</option>';
    resp.data.forEach(element => {
        p += `<option value="${element.id}">${element.descripcion}</option>`;
    });
    $("#marcas").html(p);
};

const llenarSelectListas = async () => {
    let listas = await listasPrecios();
    if (!Array.isArray(listas)) {
        console.warn("El parámetro listas no es un array");
        return;
    }
    const options = listas.map(({ id, Descripcion }) =>
        `<option value="${id}">${id} - ${Descripcion}</option>`
    );
    $("#listas").html(options.join(""));
};

const limpiar = () => {
    $("#resultado").html("");
    $("#resumen-lista").remove();
    $("#bodegas").val(0);
    $("#marcas").val(0);
};

const consultarListaPrecios = async () => {
    showLoadingWithTetrisLogo('Consultando lista de precios', 'ISA Software')
    try {
        const data = {
            link: link_,
            op: 's_lista_precios',
            lista: $("#listas").val(),
            bodega: $("#bodegas").val(),
            marca: $("#marcas").val()
        };

        const resp = await enviarPeticion(data);
        console.log({ resp });

        // ===== Cálculos de resumen ponderados por stock =====
        let costoTotal = 0;           // Σ (costo_unitario * stock)
        let costoPromedioTotal = 0;   // Σ (costo_promedio * stock)
        let valorVentaTotal = 0;      // Σ (precio * stock)
        let rentabilidadValorTotal = 0; // Σ (utilidad unitaria * stock)

        resp.forEach(item => {
            const costoUnitario = parseFloat(item.costo_unitario) || 0;
            const costoPromedio = parseFloat(item.costo_promedio) || 0;
            const precio = parseFloat(item.precion) || 0;
            const stock = parseFloat(item.total_stock) || 0;

            const utilidadUnitaria = precio - costoUnitario;

            costoTotal += costoUnitario * stock;
            costoPromedioTotal += costoPromedio * stock;
            valorVentaTotal += precio * stock;
            rentabilidadValorTotal += utilidadUnitaria * stock;
        });

        // % Rentabilidad = Utilidad / Costo * 100
        const rentabilidadPorcentajeTotal = costoTotal > 0
            ? (rentabilidadValorTotal / costoTotal) * 100
            : 0;

        // Remover resumen anterior si existe
        $("#resumen-lista").remove();

        const fmt = (n) => n.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const resumenHtml = `
            <div id="resumen-lista" class="row mb-3">
                <div class="col-6 col-md-3 mb-2">
                    <div class="card text-center border-primary h-100">
                        <div class="card-body p-2">
                            <h6 class="card-title text-muted mb-1">Costo Total (Stock)</h6>
                            <p class="card-text fw-bold mb-0">$${fmt(costoTotal)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card text-center border-info h-100">
                        <div class="card-body p-2">
                            <h6 class="card-title text-muted mb-1">Costo Promedio Total (Stock)</h6>
                            <p class="card-text fw-bold mb-0">$${fmt(costoPromedioTotal)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card text-center border-success h-100">
                        <div class="card-body p-2">
                            <h6 class="card-title text-muted mb-1">Rentabilidad Total $</h6>
                            <p class="card-text fw-bold mb-0">$${fmt(rentabilidadValorTotal)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-2">
                    <div class="card text-center border-warning h-100">
                        <div class="card-body p-2">
                            <h6 class="card-title text-muted mb-1">Rentabilidad %</h6>
                            <p class="card-text fw-bold mb-0">${rentabilidadPorcentajeTotal.toFixed(2)}%</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("#resultado").html(`
            ${resumenHtml}
            <table id="tb_lista_precios" class="table table-sm table-borderless table-hover table-striped w-100">
                <thead class="table-primary"></thead>
                <tbody></tbody>
            </table>
        `);

        createDataTable(
            'tb_lista_precios',
            resp,
            ['codigo', 'descripcion', 'descripcion_larga', 'tipo_lista', 'porcentaje_iva', 'costo_promedio', 'costo_unitario', 'precion', 'total_stock', 'rentabilidad_valor', 'rentabilidad_porcentaje'],
            ['Código', 'Descripción', 'Desc. Larga', 'Lista', 'IVA%', 'CostoProm.', 'CostoUnit.', 'Precio', 'Stock', 'Rent.$', 'Rent.%'],
            '', '', false, '', '',
            true,
            ['costo_promedio', 'costo_unitario', 'precion', 'rentabilidad_valor']
        );

        dissminSwal();
    } catch (e) {
        console.error("Error en consultarListaPrecios:", e);
        $("#resumen-lista").remove();
        $("#resultado").html(ShowAlert("Error interno de la aplicación.", "danger"));
        dissminSwal();
    }
};

$(function () {
    listarAlmacenes();
    llenarSelectListas();
    listarMarcas();

    dissminSwal();

    $("#btn-busqueda").click(function () {
        const lista = $("#listas").val();
        if (!lista) {
            Swal.fire("Advertencia", "Debe seleccionar una lista de precios", "warning");
            return;
        }
        consultarListaPrecios();
    });

    $("#btn-limpiar").click(function () {
        limpiar();
    });
    dissminSwal();
});
