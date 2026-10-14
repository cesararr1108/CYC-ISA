// Encapsular en IIFE para evitar conflictos de scope
(function () {
    let datosSeleccionables = [];
    let datosSeleccionablesOriginal = [];
    let tipoSeleccion = '1'; // 1=todas, 2=referencias, 3=marcas

    const listarAlmacenes = async () => {
        try {
            const resp = await bodegasUsuarios();
            if (resp.length == 0) {
                initToast('bg-warning', 'Advertencia', 'No se encontraron resultados', 'warning')
                return;
            }
            let options = `<option value="0">Todas las bodegas</option>`;
            resp.forEach((d) => {
                options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
            });
            $("#bodegas").html(options);
        } catch (e) {
            console.error(e)
            initToast('bg-danger', 'Error', 'Error interno de la aplicación', 'danger')
        }
    }

    /*
     @ Listas de precios
    */
    const llenarSelectListas = async () => {
        let listas = await listasPrecios();

        if (!Array.isArray(listas)) {
            console.warn("El parámetro listas no es un array");
            return;
        }

        const options = [
            `<option value="0" selected>Todas las listas</option>`,
            ...listas.map(({ id, Descripcion }) =>
                `<option value="${id}">${id} - ${Descripcion}</option>`
            )
        ];

        $("#lista_precios").html(options.join(""));
    }

    // Cargar referencias para selección
    const cargarReferencias = async () => {
        showLoadingWithTetrisLogo('Cargando referencias por favor espere...', 'ISA Software')
        try {
            const resp = await enviarPeticion({
                link: '../models/promociones.php',
                op: 'listar-referencias-activas'
            });
            console.log(resp);
            if (Array.isArray(resp.data)) {
                datosSeleccionablesOriginal = resp.data.map(item => ({
                    id: item.id,
                    nombre: item.descripcion,
                    codigo: item.codigo
                }));
                datosSeleccionables = [...datosSeleccionablesOriginal];
                renderizarTablaSeleccion();
                dissminSwal();
            } else {
                dissminSwal();
                initToast('bg-warning', 'Aviso', 'No hay referencias disponibles', 'warning');
            }
        } catch (e) {
            dissminSwal();
            console.error(e);
            initToast('bg-danger', 'Error', 'Error al cargar referencias', 'danger');
        }
    }

    // Cargar marcas para selección
    const cargarMarcas = async () => {
        showLoadingWithTetrisLogo('Cargando marcas por favor espere...', 'ISA Software')
        try {
            const resp = await enviarPeticion({
                link: '../models/promociones.php',
                op: 'listar-marcas-activas'
            });
            if (Array.isArray(resp.data)) {
                datosSeleccionablesOriginal = resp.data.map(item => ({
                    id: item.id,
                    nombre: item.descripcion
                }));
                datosSeleccionables = [...datosSeleccionablesOriginal];
                renderizarTablaSeleccion();
                dissminSwal();
            } else {
                dissminSwal();
                initToast('bg-warning', 'Aviso', 'No hay marcas disponibles', 'warning');
            }
        } catch (e) {
            dissminSwal();
            console.error(e);
            initToast('bg-danger', 'Error', 'Error al cargar marcas', 'danger');
        }
    }

    // Renderizar tabla de selección
    const renderizarTablaSeleccion = (datos = datosSeleccionables) => {
        const tbody = $("#bodyTablaSeleccion");

        if (!datos || datos.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="2" class="text-center text-muted py-3">
                        <i class="bx bx-inbox"></i> No hay elementos disponibles
                    </td>
                </tr>
            `);
            actualizarBadgeSeleccionados();
            return;
        }

        let html = '';
        datos.forEach(item => {
            const displayText = tipoSeleccion === '2'
                ? `(${item.codigo || 'N/A'}) ${item.nombre} `
                : item.nombre;

            html += `
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="chkItemSeleccion" value="${item.id}" data-nombre="${item.nombre}">
                    </td>
                    <td>${displayText}</td>
                </tr>
            `;
        });

        tbody.html(html);
        actualizarBadgeSeleccionados();
    }

    // Filtrar tabla de selección
    const filtrarTablaSeleccion = (termino) => {
        const terminoBusqueda = termino.toLowerCase().trim();

        if (terminoBusqueda.length === 0) {
            datosSeleccionables = [...datosSeleccionablesOriginal];
            renderizarTablaSeleccion();
            return;
        }

        datosSeleccionables = datosSeleccionablesOriginal.filter(item => {
            const textoColumnas = `${item.nombre} ${item.codigo || ''}`.toLowerCase();
            return textoColumnas.includes(terminoBusqueda);
        });

        renderizarTablaSeleccion();
    }

    // Manejador de cambio en aplicar_a
    const manejarCambioAplicarA = async (valor) => {
        tipoSeleccion = valor;
        const contenedor = $("#contenedorTablaSeleccion");
        const label = $("#labelTablaSeleccion");
        const header = $("#headerSeleccion");
        const chkTodos = $("#chkSeleccionarTodos");

        if (valor === '1') {
            // Opción: Todas las referencias
            contenedor.hide();
            chkTodos.prop('checked', false);
        } else if (valor === '2') {
            // Opción: Referencias específicas
            label.text('Referencias a aplicar');
            header.text(tipoSeleccion === '2' ? 'Referencias' : 'Marcas');
            contenedor.show();
            chkTodos.prop('checked', false);
            await cargarReferencias();
        } else if (valor === '3') {
            // Opción: Marcas específicas
            label.text('Marcas a aplicar');
            header.text(tipoSeleccion === '2' ? 'Referencias' : 'Marcas');
            contenedor.show();
            chkTodos.prop('checked', false);
            await cargarMarcas();
        }

        $("#filtroSeleccion").val('');
    }

    // Obtener elementos seleccionados
    const obtenerSeleccionados = () => {
        const seleccionados = [];
        $(".chkItemSeleccion:checked").each(function () {
            seleccionados.push({
                id: $(this).val(),
                nombre: $(this).data('nombre')
            });
        });
        return seleccionados;
    }

    // Actualizar badge de seleccionados
    const actualizarBadgeSeleccionados = () => {
        const cantidad = $(".chkItemSeleccion:checked").length;
        $("#badgeSeleccionados").text(cantidad);
    }

    // Mostrar seleccionados en modal
    const mostrarSeleccionados = () => {
        const seleccionados = obtenerSeleccionados();
        const contenido = $("#contenidoSeleccionados");

        if (seleccionados.length === 0) {
            contenido.html(`
                <div class="alert alert-info text-center">
                    <i class="bx bx-info-circle me-2"></i>
                    No hay elementos seleccionados
                </div>
            `);
        } else {
            let html = `
                <div class="alert alert-success">
                    <strong><i class="bx bx-check me-1"></i>${seleccionados.length} elemento(s) seleccionado(s)</strong>
                </div>
                <div class="list-group">
            `;

            seleccionados.forEach(item => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>${item.nombre}</span>
                            <small class="text-muted">ID: ${item.id}</small>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            contenido.html(html);
        }

        const modal = new bootstrap.Modal(document.getElementById('modalVerSeleccionados'));
        modal.show();
    }

    // Cargar y mostrar promociones
    const cargarTablaPromociones = async () => {
        showLoadingWithTetrisLogo('Cargando promociones, por favor espere...', 'ISA Software');
        try {
            const idBodega = $("#filtro_bodega").val() || 0;
            const fechaDesde = $("#filtroFechaDesde").val() || '';
            const fechaHasta = $("#filtroFechaHasta").val() || '';
            const estado = $("#filtro_estado").val() !== '' ? $("#filtro_estado").val() : '';

            const resp = await enviarPeticion({
                link: '../models/promociones.php',
                op: 'listar-promociones',
                id_bodega: idBodega,
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                estado: estado
            });
            console.log(resp);

            dissminSwal();

            if (!resp.error && Array.isArray(resp.data)) {
                renderizarTablaPromociones(resp.data);
                initToast('bg-success', 'Éxito', resp.message, 'success');
            } else {
                initToast('bg-warning', 'Aviso', 'No hay promociones para mostrar', 'warning');
                renderizarTablaPromociones([]);
            }
        } catch (e) {
            dissminSwal();
            console.error(e);
            initToast('bg-danger', 'Error', 'Error al cargar las promociones', 'danger');
        }
    }

    // Renderizar tabla de promociones
    const renderizarTablaPromociones = (datos) => {
        const tbody = $("#bodyTablaPromociones");

        if (!datos || datos.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">
                        <i class="bx bx-inbox"></i> No hay promociones disponibles
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        datos.forEach(item => {
            const estadoBadge = item.estado == 1
                ? '<span class="badge bg-success">Activa</span>'
                : '<span class="badge bg-secondary">Inactiva</span>';

            const tipoAplicacion = item.tipo_aplicacion === '1' ? 'Todas'
                : item.tipo_aplicacion === '2' ? `Referencias (${item.cantidad_referencias})`
                    : `Marcas (${item.cantidad_marcas})`;

            html += `
                <tr>
                    <td>${item.bodega_desc}</td>
                    <td>${item.nombre}</td>                    
                    <td>${item.id_lista_precios} - ${item.desc_lista}</td>
                    <td class="text-center"><strong>${item.porcentaje_descuento}%</strong></td>
                    <td class="text-center">${tipoAplicacion}</td>
                    <td>${item.fecha_inicio} / ${item.fecha_final}</td>
                    <td>${item.usuario_nombre || 'N/A'}</td>
                    <td class="text-center">${estadoBadge}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-info btnEditarPromo" data-id="${item.id}" title="Editar">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger btnEliminarPromo" data-id="${item.id}" title="Inactivar">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
    }

    // Filtrar tabla (búsqueda)
    const filtrarTablaPromociones = (termino) => {
        const filtro = termino.toLowerCase();
        $("#bodyTablaPromociones tr").each(function () {
            const texto = $(this).text().toLowerCase();
            $(this).toggle(texto.includes(filtro));
        });
    }

    const limpiarFormulario = () => {
        $("#bodegas").val(0);
        $("#lista_precios").val(0);
        $("#fecha_inicial").val('');
        $("#fecha_final").val('');
        $("#nombre_promocion").val('');
        $("#porcentaje_desc").val('');
        $("#aplicar_a").val('1');
        $("#contenedorTablaSeleccion").hide();
        $("#chkSeleccionarTodos").prop('checked', false);
        $(".chkItemSeleccion").prop('checked', false);
        $("#filtroSeleccion").val('');
        $("#badgeSeleccionados").text('0');
        $("#btnCrearReserva").removeAttr('data-id').html('<i class="bx bx-plus me-1"></i>Crear Promoción');
        datosSeleccionables = [];
        datosSeleccionablesOriginal = [];
        fechaActual([['fecha_inicial', 'fecha_final']]);
    }

    // Inicialización
    $(async function () {
        await llenarSelectListas();
        await listarAlmacenes();
        fechaActual([['fecha_inicial', 'fecha_final']]);
        fechaActual([['filtroFechaDesde', 'filtroFechaHasta']]);

        // Cargar bodegas en filtro de consulta
        try {
            const resp = await bodegasUsuarios();
            let options = `<option value="0">Todas las bodegas</option>`;
            resp.forEach((d) => {
                options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
            });
            $("#filtro_bodega").html(options);
        } catch (e) {
            console.error(e);
        }

        // Validar que porcentaje_desc solo acepte números
        $("#porcentaje_desc").on('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9.]/.test(char)) {
                e.preventDefault();
            }
        }).on('input', function () {
            let value = $(this).val();
            value = value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            $(this).val(value);
        });

        // Evento de cambio en aplicar_a
        $("#aplicar_a").on('change', function () {
            manejarCambioAplicarA($(this).val());
        });

        // Evento de filtro en tabla de selección
        $("#filtroSeleccion").on('keyup', function () {
            filtrarTablaSeleccion($(this).val());
        });

        // Click en la descripción para checkear/uncheckear
        $(document).on('click', '#bodyTablaSeleccion td:last-child', function () {
            const checkbox = $(this).closest('tr').find('.chkItemSeleccion');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Botón limpiar filtro
        $("#btnLimpiarFiltroSeleccion").on('click', function () {
            $("#filtroSeleccion").val('').focus();
            datosSeleccionables = [...datosSeleccionablesOriginal];
            renderizarTablaSeleccion();
        });

        // Seleccionar todos
        $("#chkSeleccionarTodos").on('change', function () {
            $(".chkItemSeleccion").prop('checked', $(this).is(':checked'));
            actualizarBadgeSeleccionados();
        });

        // Actualizar estado del checkbox "seleccionar todos"
        $(document).on('change', '.chkItemSeleccion', function () {
            const total = $(".chkItemSeleccion").length;
            const seleccionados = $(".chkItemSeleccion:checked").length;
            $("#chkSeleccionarTodos").prop('checked', total > 0 && total === seleccionados);
            actualizarBadgeSeleccionados();
        });

        // Botón ver seleccionados
        $("#btnVerSeleccionados").on('click', function () {
            mostrarSeleccionados();
        });

        // Botón crear promoción
        $("#btnCrearReserva").on('click', async function () {
            const aplicarA = $("#aplicar_a").val();
            const seleccionados = obtenerSeleccionados();
            const idBodega = $("#bodegas").val();
            const nombre = $("#nombre_promocion").val().trim();
            const porcentaje = $("#porcentaje_desc").val();
            const fechaInicial = $("#fecha_inicial").val();
            const fechaFinal = $("#fecha_final").val();
            const idListaPrecios = $("#lista_precios").val();
            const idPromo = $(this).attr('data-id'); // Para edición

            // Validaciones
            if (!nombre) {
                initToast('bg-warning', 'Validación', 'Debe ingresar un nombre para la promoción', 'warning');
                return;
            }

            if (idBodega === '' || idBodega === null) {
                initToast('bg-warning', 'Validación', 'Debe seleccionar una bodega o todas las bodegas', 'warning');
                return;
            }

            if (!porcentaje || parseFloat(porcentaje) <= 0 || parseFloat(porcentaje) > 100) {
                initToast('bg-warning', 'Validación', 'El porcentaje debe estar entre 1 y 100', 'warning');
                return;
            }

            if (!fechaInicial || !fechaFinal) {
                initToast('bg-warning', 'Validación', 'Debe seleccionar fechas inicial y final', 'warning');
                return;
            }

            if ((aplicarA === '2' || aplicarA === '3') && seleccionados.length === 0) {
                initToast('bg-warning', 'Validación', 'Debe seleccionar al menos un elemento', 'warning');
                return;
            }

            showLoadingWithTetrisLogo(idPromo ? 'Actualizando promoción, por favor espere...' : 'Creando promoción, por favor espere...', 'ISA Software');

            try {
                // Preparar datos
                const datosEnvio = {
                    nombre: nombre,
                    id_bodega: idBodega,
                    id_lista_precios: idListaPrecios,
                    porcentaje_descuento: porcentaje,
                    tipo_aplicacion: aplicarA,
                    fecha_inicio: fechaInicial,
                    fecha_final: fechaFinal,
                    referencias_seleccionadas: aplicarA === '2' ? JSON.stringify(seleccionados.map(s => s.id)) : '[]',
                    marcas_seleccionadas: aplicarA === '3' ? JSON.stringify(seleccionados.map(s => s.id)) : '[]'
                };

                const operacion = idPromo ? 'editar-promocion' : 'crear-promocion';
                if (idPromo) {
                    datosEnvio.id = idPromo;
                }

                const resp = await enviarPeticion({
                    link: '../models/promociones.php',
                    op: operacion,
                    ...datosEnvio
                });

                dissminSwal();

                if (resp.estado) {
                    initToast('bg-success', 'Éxito', resp.message, 'success');
                    // Limpiar formulario
                    $("#btnNuevo").click();
                } else {
                    initToast('bg-danger', 'Error', resp.message, 'danger');
                }
            } catch (e) {
                dissminSwal();
                console.error(e);
                initToast('bg-danger', 'Error', idPromo ? 'Error al actualizar la promoción' : 'Error al crear la promoción', 'danger');
            }
        });

        // Botón limpiar formulario
        $("#btnNuevo").on('click', function () {
            limpiarFormulario();
        });

        // ===== EVENTOS PESTAÑA CONSULTA =====

        // Botón aplicar filtros
        $("#btnAplicarFiltros").on('click', async function () {
            await cargarTablaPromociones();
        });

        // Búsqueda en tabla
        $("#filtroTablaPromociones").on('keyup', function () {
            filtrarTablaPromociones($(this).val());
        });

        // Botón limpiar búsqueda
        $("#btnLimpiarBusquedaPromo").on('click', function () {
            $("#filtroTablaPromociones").val('').focus();
            filtrarTablaPromociones('');
        });

        // Evento para botón editar promoción
        $(document).on('click', '.btnEditarPromo', async function () {
            const idPromo = $(this).data('id');
            showLoadingWithTetrisLogo('Cargando promoción, por favor espere...', 'ISA Software');

            try {
                const resp = await enviarPeticion({
                    link: '../models/promociones.php',
                    op: 'obtener-promocion',
                    id: idPromo
                });

                dissminSwal();

                if (!resp.error && resp.data) {
                    // Llenar formulario con datos de la promoción
                    const data = resp.data;
                    $("#bodegas").val(data.id_bodega);
                    $("#lista_precios").val(data.id_lista_precios || 0);
                    $("#fecha_inicial").val(data.fecha_inicio);
                    $("#fecha_final").val(data.fecha_final);
                    $("#nombre_promocion").val(data.nombre);
                    $("#porcentaje_desc").val(data.porcentaje_descuento);
                    $("#aplicar_a").val(data.tipo_aplicacion);

                    // Cambiar botón a actualizar
                    $("#btnCrearReserva").attr('data-id', idPromo).html('<i class="bx bx-save me-1"></i>Actualizar Promoción');

                    // Manejar selección según tipo de aplicación
                    await manejarCambioAplicarA(data.tipo_aplicacion);

                    // Marcar elementos seleccionados
                    if (data.tipo_aplicacion === '2' && resp.referencias.length > 0) {
                        resp.referencias.forEach(id => {
                            $(`.chkItemSeleccion[value="${id}"]`).prop('checked', true);
                        });
                    } else if (data.tipo_aplicacion === '3' && resp.marcas.length > 0) {
                        resp.marcas.forEach(id => {
                            $(`.chkItemSeleccion[value="${id}"]`).prop('checked', true);
                        });
                    }

                    actualizarBadgeSeleccionados();

                    // Ir a pestaña de crear
                    $("#tab-crear").click();

                    initToast('bg-success', 'Información', 'Promoción cargada para edición', 'success');
                } else {
                    initToast('bg-danger', 'Error', 'No se pudo cargar la promoción', 'danger');
                }
            } catch (e) {
                dissminSwal();
                console.error(e);
                initToast('bg-danger', 'Error', 'Error al cargar la promoción', 'danger');
            }
        });

        // Evento para botón eliminar/inactivar promoción
        $(document).on('click', '.btnEliminarPromo', function () {
            const idPromo = $(this).data('id');

            Swal.fire({
                title: '¿Confirmar?',
                text: '¿Desea inactivar esta promoción?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    showLoadingWithTetrisLogo('Inactivando promoción...', 'ISA Software');

                    try {
                        const resp = await enviarPeticion({
                            link: '../models/promociones.php',
                            op: 'eliminar-promocion',
                            id: idPromo
                        });

                        dissminSwal();

                        if (resp.estado) {
                            initToast('bg-success', 'Éxito', resp.message, 'success');
                            await cargarTablaPromociones();
                        } else {
                            initToast('bg-danger', 'Error', resp.message, 'danger');
                        }
                    } catch (e) {
                        dissminSwal();
                        console.error(e);
                        initToast('bg-danger', 'Error', 'Error al inactivar la promoción', 'danger');
                    }
                }
            });
        });

        // Cargar tabla al cambiar pestaña a consulta
        $("#tab-consulta").on('shown.bs.tab', async function () {
            await cargarTablaPromociones();
        });
    });

    // Exponer funciones globales si es necesario
    window.obtenerSeleccionados = obtenerSeleccionados;
    dissminSwal(); // Asegurar que cualquier modal de carga se cierre al cargar el script
})();