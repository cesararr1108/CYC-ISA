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
    // 1. Destruir la instancia del gráfico si existe para liberar memoria y evitar errores
    if (window.estadoChart) {
        window.estadoChart.destroy();
        window.estadoChart = null;
    }

    // 2. Limpiar los contenedores HTML
    $("#resultado").html("");
    $("#chart-estados").html("");
    $("#unidades-estados").html("");
    
    // 3. Resetear selectores si aplica (asegúrate de que #bodegas y tus filtros existan)
    $("#bodegas").val(0).trigger('change'); // Si usas Select2, el .trigger('change') es vital
    $("#filtro_meses").val(3);
    $("#filtro_dias").val(30);
};

const consultar = async () => {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
    try {
        const data = {
            link: '../models/rptDiasInventario.php',
            op: 'consultar',
            dias_rot: $("#filtro_meses").val(), 
            dias_inv: $("#filtro_dias").val(),
            id_bodega: $("#bodegas").val()
        };

        const resp = await enviarPeticion(data);
        dissminSwal();

        if (resp && !resp.error && resp.data && resp.data.length > 0) {
            $("#resultado").html(`<table id="tabla-inventario" class="table table-bordered table-hover">
                                    <thead class="table-primary">
                                    </thead>
                                </table>`);
            // 1. Procesar datos para el gráfico de porcentajes
            procesarGraficoEstados(resp.data);

            // 2. Preparar los datos transformando el estado en un Badge de Bootstrap con color
            const dataFormateada = resp.data.map(item => {
                let badgeClass = 'bg-secondary';
                let rowClass = '';

                switch (item.estado) {
                    case 'Comprar':
                        badgeClass = 'bg-danger';
                        break;
                    case 'Exceso':
                        badgeClass = 'bg-warning';
                        break;
                    case 'Sin Rotación':
                        badgeClass = 'bg-dark';
                        break;
                    case 'Normal':
                        badgeClass = 'bg-success';
                        break;
                }

                return {
                    ...item,
                    estado: `<span class="badge ${badgeClass}">${item.estado}</span>`
                };
            });

            // 3. Definir columnas y nombres para tu createDataTable
            const dataNames = [
                'bodega', 'codigo', 'producto', 'marca', 
                'rotacion_diaria_prom', 'stock_min', 'stock_actual', 
                'stock_dias_inventario', 'dias_restantes_inventario', 
                'comprar', 'exceso', 'precio_prom', 'sub_total_exceso', 'estado'
            ];

            const columnNames = [
                'Bodega', 'Código', 'Producto', 'Marca', 
                'Rot. Prom', 'Stock Mín', 'Stock Actual', 
                'Stock Días', 'Días Rest.', 'Comprar', 
                'Exceso', 'Precio Prom', 'SubTotal Exceso', 'Estado'
            ];

            // 4. Llamar a tu función genérica DataTable
            createDataTable(
                'tabla-inventario',       // ID de tu tabla HTML (ej: <table id="tabla-inventario">)
                dataFormateada,
                dataNames,
                columnNames,
                null,                     // editCallback (null si no aplica)
                null,                     // deleteCallback (null si no aplica)
                false,                    // otherButton
                '',                       // boton
                '',                       // otherCallback
                true,                     // btnExport (Activa botones de Excel/PDF)
                ['precio_prom', 'sub_total_exceso'], // Columnas de moneda
                'COP',
                [],                       // customColumnClasses
                ['comprar', 'exceso', 'sub_total_exceso'], // Columnas a sumar en el tfoot
                50,                       // pageLength
                true                      // cortarTexto
            );

        } else {
            // Limpiar si no hay datos
            $("#resultado").html(ShowAlert("No se encontraron resultados", "warning"));
            if (window.estadoChart) window.estadoChart.destroy();
        }

    } catch (e) {
        dissminSwal();
        console.error("Error en la consulta:", e);
    }
};

const procesarGraficoEstados = (data) => {
    let conteo = {
        'Comprar': 0,
        'Exceso': 0,
        'Sin Rotación': 0,
        'Normal': 0
    };

    const total = data.length;

    // Contar ocurrencias por estado
    data.forEach(item => {
        if (conteo[item.estado] !== undefined) {
            conteo[item.estado]++;
        }
    });

    // Configuración visual de los estados (Badges de Bootstrap)
    const configEstados = [
        { nombre: 'Comprar', badge: 'bg-danger' },
        { nombre: 'Exceso', badge: 'bg-warning text-dark' },
        { nombre: 'Sin Rotación', badge: 'bg-dark' },
        { nombre: 'Normal', badge: 'bg-success' }
    ];

    let seriesData = [];
    let tablaHTML = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle text-center mb-0">
                <thead class="table-primary">
                    <tr>
                        <th class="text-start">Estado</th>
                        <th>Cant. Refs</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
    `;

    configEstados.forEach(est => {
        const cantidad = conteo[est.nombre];
        const porcentaje = total > 0 ? Number(((cantidad / total) * 100).toFixed(1)) : 0;
        seriesData.push(porcentaje);

        tablaHTML += `
            <tr>
                <td class="text-start">
                    <span class="badge ${est.badge}">${est.nombre}</span>
                </td>
                <td class="fw-bold">${cantidad}</td>
                <td>${porcentaje}%</td>
            </tr>
        `;
    });

    tablaHTML += `
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th class="text-start">Total</th>
                        <th class="fw-bold">${total}</th>
                        <th class="fw-bold">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;

    // Inyectar la tabla resumen en el segundo col-6
    $("#unidades-estados").html(tablaHTML);

    // Renderizar el gráfico de dona al lado
    renderizarDonutChart(seriesData);
};

const renderizarDonutChart = (seriesData) => {
    if (window.estadoChart) {
        window.estadoChart.destroy();
    }

    const options = {
        series: seriesData,
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['Comprar', 'Exceso', 'Sin Rotación', 'Normal'],
        colors: ['#dc3545', '#ffc107', '#212529', '#198754'], // Rojos, Amarillos, Oscuros, Verdes
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Refs Total',
                            formatter: function () {
                                return '100%';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            formatter: function (val) {
               return Number(val).toFixed(1) + "%"; // Muestra solo 1 decimal (ej: 25.5%)
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: { width: 200 },
                legend: { position: 'bottom' }
            }
        }]
    };

    window.estadoChart = new ApexCharts(document.querySelector("#chart-estados"), options);
    window.estadoChart.render();
};

$(function () {
    listarAlmacenes();
    fechaActual([[
        'fe_fecha_inicial',
        'fe_fecha_final'
    ]]);
    $("#btn-busqueda").click(function () {
        consultar();
    });
    $("#btn-limpiar").click(function () {
        limpiar();
    });
    dissminSwal();
})