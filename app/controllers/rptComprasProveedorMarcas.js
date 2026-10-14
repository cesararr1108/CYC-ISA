const cargarMarcas = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../models/Marcas.php',
            op: 'listar',
            filtro: ''
        });

        const marcas = Array.isArray(resp.data) ? resp.data : [];
        let options = `<option value="">Todas las marcas</option>`;

        marcas.forEach((item) => {
            options += `<option value="${item.id}">${item.descripcion}</option>`;
        });

        $("#marcas").html(options).select2({
            placeholder: 'Todas las marcas',
            allowClear: true,
            width: '100%'
        });
    } catch (e) {
        console.error(e);
    }
};

const consultar = async () => {

    showLoadingWithTetrisLogo(
        'Buscando información',
        'ISA Software'
    );

    try {

        const resp = await enviarPeticion({
            link: '../models/rptComprasProveedorMarcas.php',
            id_tercero: $("#id_tercero").val(),
            id_marca: $("#marcas").val(),
            fecha_inicial: $("#fecha_inicial").val(),
            fecha_final: $("#fecha_final").val(),
            op: 'consolidado'
        });

        /* =====================================================
           SIN RESULTADOS
           ===================================================== */

        if (!resp || resp.length === 0) {

            dissminSwal();

            $("#result").html('');

            Swal.fire(
                "Ups",
                "No se encontraron resultados",
                "info"
            );

            return;
        }


        /* =====================================================
           CALCULAR TOTALES
           ===================================================== */

        const totalSinIva = resp.reduce(
            (total, item) =>
                total + parseFloat(item.total_sin_iva || 0),
            0
        );

        const totalConIva = resp.reduce(
            (total, item) =>
                total + parseFloat(item.total_con_iva || 0),
            0
        );

        const totalCostoSinIva = resp.reduce(
            (total, item) =>
                total + parseFloat(item.total_costo_sin_iva || 0),
            0
        );

        const totalCostoConIva = resp.reduce(
            (total, item) =>
                total + parseFloat(item.total_costo_con_iva || 0),
            0
        );


        /* =====================================================
           FORMATEADOR MONEDA
           ===================================================== */

        const formatoCOP = new Intl.NumberFormat(
            'es-CO',
            {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }
        );


        /* =====================================================
           CARDS DE TOTALES
           ===================================================== */

        const cards = `

        <div class="row g-2 mb-3">

            <!-- Total sin IVA -->
            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body py-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="rounded-circle bg-opacity-10
                                       d-flex align-items-center
                                       justify-content-center me-3"
                                style="width:42px;height:42px;"
                            >
                                <i class="bx bx-receipt  text-primary fs-4"></i>
                            </div>

                            <div>

                                <div class="text-muted small">
                                    Total sin IVA
                                </div>

                                <div class="fw-bold fs-5">
                                    ${formatoCOP.format(totalSinIva)}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Total con IVA -->
            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body py-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="rounded-circle bg-opacity-10
                                        d-flex align-items-center
                                       justify-content-center me-3"
                                style="width:42px;height:42px;"
                            >
                                <i class="bx bx-dollar-circle text-success fs-4"></i>
                            </div>

                            <div>

                                <div class="text-muted small">
                                    Total con IVA
                                </div>

                                <div class="fw-bold fs-5">
                                    ${formatoCOP.format(totalConIva)}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Costo sin IVA -->
            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body py-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="rounded-circle bg-opacity-10
                                        d-flex align-items-center
                                       justify-content-center me-3"
                                style="width:42px;height:42px;"
                            >
                                <i class="bx bx-calculator text-warning fs-4"></i>
                            </div>

                            <div>

                                <div class="text-muted small">
                                    Costo sin IVA
                                </div>

                                <div class="fw-bold fs-5">
                                    ${formatoCOP.format(totalCostoSinIva)}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Costo con IVA -->
            <div class="col-12 col-sm-6 col-xl-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body py-3">

                        <div class="d-flex align-items-center">

                            <div
                                class="rounded-circle bg-opacity-10
                                      d-flex align-items-center
                                       justify-content-center me-3"
                                style="width:42px;height:42px;"
                            >
                                <i class="bx bx-wallet text-danger fs-4"></i>
                            </div>

                            <div>

                                <div class="text-muted small">
                                    Costo con IVA
                                </div>

                                <div class="fw-bold fs-5">
                                    ${formatoCOP.format(totalCostoConIva)}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        `;


        /* =====================================================
           TABLA
           ===================================================== */

        const tb = `

        <div class="table-responsive">

            <table
                class="table table-sm table-borderless
                       table-hover table-striped w-100"
                id="compras"
            >

                <thead class="table-primary"></thead>

                <tbody></tbody>

                <tfoot></tfoot>

            </table>

        </div>

        `;


        /* =====================================================
           MOSTRAR CARDS + TABLA
           ===================================================== */

        $("#result").html(
            cards + tb
        );


        /* =====================================================
           DATATABLE
           ===================================================== */

        createDataTable(
            'compras',
            resp,

            [
                'nit',
                'razon_comercial',
                'marca',
                'total_sin_iva',
                'total_con_iva',
                'total_costo_sin_iva',
                'total_costo_con_iva'
            ],

            [
                'Nit',
                'Proveedor',
                'Marca',
                'Total sin IVA',
                'Total con IVA',
                'Total costo sin IVA',
                'Total costo con IVA'
            ],

            '',
            '',
            false,
            '',
            '',
            true,

            [
                'total_sin_iva',
                'total_con_iva',
                'total_costo_sin_iva',
                'total_costo_con_iva'
            ],

            'COP',

            [],

            [
                'total_sin_iva',
                'total_con_iva',
                'total_costo_sin_iva',
                'total_costo_con_iva'
            ]
        );


        /* =====================================================
           AJUSTAR DATATABLE
           ===================================================== */

        setTimeout(() => {

            if ($.fn.DataTable.isDataTable('#compras')) {

                $('#compras')
                    .DataTable()
                    .columns
                    .adjust()
                    .draw(false);

            }

        }, 100);


        /* =====================================================
           OCULTAR LOADING
           ===================================================== */

        dissminSwal();


    } catch (error) {

        dissminSwal();

        console.error(error);

        Swal.fire(
            "Error",
            "Error al obtener la información",
            "error"
        );
    }
};


$(function () {
    cargarMarcas();

    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
    const formatoFecha = (fecha) => fecha.toISOString().split("T")[0];

    $("#fecha_inicial").val(formatoFecha(primerDia));
    $("#fecha_final").val(formatoFecha(ultimoDia));

    $('#proveedor').autocomplete({
        source: async function (request, response) {
            const data = {
                link: '../models/Terceros.php',
                filtro: $.trim(request.term),
                tipo_tercero: '',
                estado: 'A',
                op: 's_tercero'
            };
            try {
                const resp = await enviarPeticion(data);
                let datos = resp.data;

                if (Array.isArray(datos)) {
                    response(datos);
                } else {
                    response([]);
                }
            } catch (error) {
                console.error("Error en la solicitud:", error);
                response([]);
            }
        },
        minLength: 3,
        select: async function (event, ui) {

            item = ui.item;
            $("#id_tercero").val(item.id)
        }
    });

    $('#proveedor').keyup(function () {
        const dato = $.trim($("#proveedor").val());

        if (dato.length == 0) {
            $("#id_tercero").val(0)
            return;
        }

    })

    $("#btn-busqueda").click(function () {
        if ($("#fecha_inicial").val() == '' || $("#fecha_final").val() == '') {
            Swal.fire("Ups", "La fecha inicial y final es obligatoria", "error")
            return;
        }
        consultar();
    })
    dissminSwal();
})