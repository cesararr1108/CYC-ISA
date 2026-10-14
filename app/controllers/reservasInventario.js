const id_modulo = '29';

// Encapsular todo en IIFE para evitar conflictos de scope global
(function () {
    let detallesReserva = [];
    let ConfGeneral = {};
    let datosTablaReservas = []; // Almacenar datos para filtrado local

    const cargarReferenciaLote = async (ob) => {
        const element = await JSON.parse($(ob).attr("data-element"));
        cargarReferencia(element);
        $("#modalFechaLotes").modal("hide");
    }

    const cargarReferencia = (data) => {
        if (data.total_stock <= 0) {
            initToast('bg-danger', 'Oops', 'No hay stock disponible para esta referencia', 'danger')
            return;
        }
        $("#txtIdReferencia").val(data.id);
        $("#txtBusquedaReferencia").val(data.descripcion);
        $("#txtIdLote").val(data.id_lote)
        $("#txtLote").val(`${data.lote} / ${data.vencimiento}`);
        $("#txtStock").val(data.total_stock);
    }

    const listarAlmacenes = async () => {
        try {
            const resp = await bodegasUsuarios();
            if (resp.length == 0) {
                initToast('bg-warning', 'Advertencia', 'No se encontraron resultados', 'warning')
                return;
            }
            let options = ``;
            resp.forEach((d) => {
                options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
            });
            $("#bodegas").html(options);
            $("#bodegas").val($("#id_bodega_session").val()).attr("disabled", true);
        } catch (e) {
            console.error(e)
            initToast('bg-danger', 'Error', 'Error interno de la aplicación', 'danger')
        }
    }

    const crearReserva = async () => {
        if (!$("#id_tercero").val() || $("#id_tercero").val() === '0') {
            initToast('bg-warning', 'Validación', 'Debe seleccionar un tercero válido', 'warning')
            return;
        }

        if (detallesReserva.length == 0) {
            initToast('bg-warning', 'Validación', 'Debe agregar al menos un detalle a la reserva', 'warning')
            return;
        }

        try {
            const resp = await enviarPeticion({
                link: '../models/reservasInventario.php',
                op: 'crear-reserva',
                id_bodega: $("#bodegas").val(),
                id_tercero: $("#id_tercero").val(),
                notas: $("#notas").val(),
                detalles: JSON.stringify(detallesReserva)
            });
            console.log(resp)
            if (resp.estado) {
                limpiarFormulario();
                detallesReserva = [];
                consultarReservados();
                initToast('bg-success', 'Éxito', resp.message || 'Reserva creada correctamente', 'success')
            } else {
                initToast('bg-danger', 'Error', resp.message || 'Error al crear la reserva', 'danger')
            }
        } catch (e) {
            console.error(e)
            initToast('bg-danger', 'Error', 'Error al crear la reserva', 'danger')
        }


        /*
        const id_referencia = $("#txtIdReferencia").val();
        const id_lote = $("#txtIdLote").val();
        const cantidad = parseInt($("#txtReservado").val());
        const stock = parseInt($("#txtStock").val());
        if (id_referencia.length == 0) {
            initToast('bg-danger', 'Ups', 'Debe seleccionar una referencia', 'danger')
            return;
        }
        if (isNaN(cantidad) || cantidad <= 0) {
            initToast('bg-danger', 'Ups', 'Cantidad a reservar debe ser mayor a 0', 'danger')
            return;
        }
        if (cantidad > stock) {
            initToast('bg-danger', 'Ups', 'Cantidad a reservar no puede ser mayor al stock disponible', 'danger')
            return;
        }
        try {
            const resp = await enviarPeticion({
                link: '../models/reservasInventario.php',
                op: 'crear-reserva',
                id_referencia,
                id_lote,
                cantidad,
                id_bodega: $("#bodegas").val()
            });
            if (resp.estado) {
                limpiarFormulario();
                consultarReservados();
                initToast('bg-success', 'Éxito', resp.message || 'Reserva creada correctamente', 'success')
            } else {
                initToast('bg-danger', 'Error', resp.message || 'Error al crear la reserva', 'danger')
            }
        } catch (e) {
            console.error(e)
            initToast('bg-danger', 'Error', 'Error al crear la reserva', 'danger')
        }*/
    }

    const cargarLote = async (data) => {
        try {
            const resp = await enviarPeticion({
                link: '../shared/servicios/servicios.php',
                op: 'cargar-lotes',
                id_referencia: data.id,
                bodega: $("#bodegas").val(),
                id_pedido: 0
            });

            const disponibles = resp.filter(p =>
                (p.stock - p.comprometidas - p.comp_scan) > 0
            );

            if (disponibles.length == 0) {
                data.id_lote = 0;
                data.lote = 0;
                data.vencimiento = '',
                    cargarReferencia(data);
                return;
            }

            if (disponibles.length == 1) {
                const item_lotes = resp[0];
                data.id_lote = item_lotes.id_lote;
                data.lote = item_lotes.lote;
                data.vencimiento = item_lotes.vencimiento,
                    data.total_stock = item_lotes.stock - item_lotes.comprometidas - item_lotes.comp_scan
                cargarReferencia(data);
                return;
            }
            $("#modalFechaLotes").modal("show");

            let tb = `<table class="table table-sm data-lotes">
               <thead>
                 <tr>
                   <th>Lote</th>
                   <th>Vencimiento</th>
                   <th>Comprometidas</th>
                   <th>Stock</th>
                 </tr>
               </thead>
               <tbody>
               `
            let tab = 300;

            disponibles.forEach(item => {
                data.id_lote = item.id_lote;
                data.lote = item.lote;
                data.vencimiento = item.vencimiento,
                    data.total_stock = item.stock

                tb += `<tr tabindex="${tab}"  onclick="cargarReferenciaLote(this)" data-element='${JSON.stringify(data)}' >
                   <td>${item.lote}</td>
                   <td>${item.vencimiento}</td>
                   <td>${item.comprometidas}</td>
                   <td>${item.stock}</td>
                 </tr>`
                tab++;
            });

            tb += `</tbody></table>`

            $("#result-lotes").html(tb)
            setTimeout(function () {
                $(".data-lotes tbody tr:first").addClass("focus").focus()
            }, 600)

        } catch (e) {
            console.error(e)
            initToast('bg-danger', 'Error', 'Error al cargar los lotes', 'danger')
        }
    }

    const limpiarFormulario = () => {
        $("#txtIdReferencia").val('');
        $("#txtBusquedaReferencia").val('');
        $("#txtIdLote").val('');
        $("#txtLote").val('');
        $("#txtVencimiento").val('');
        $("#txtStock").val('');
        $("#txtReservado").val('');
        $("#id_tercero").val('');
        $("#txtBusquedaTercero").val('');
        $("#notas").val('');
        $("#detalleReserva").html(`
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="bx bx-info-circle"></i> No hay referencias agregadas
                </td>
            </tr>
        `);
        $("#nit").val('');
        $("#tratamiento").val('');
        $("#razon_comercial").val('');
        $("#txtBusquedaTercero").focus();
        $("#txtBusquedaReferencia").val('');
    };

    const consultarReservados = async () => {
        try {
            const filtros = {
                fecha_desde: $("#filtroFechaDesde").val(),
                fecha_hasta: $("#filtroFechaHasta").val(),
                estado: $("#filtroEstado").val()
            };
            showLoadingWithTetrisLogo('Cargando reservas...', 'Por favor espere');

            const id_bodega = $("#bodegas").val();
            if (!id_bodega) {
                dissminSwal();
                initToast('bg-warning', 'Validación', 'Debe seleccionar una bodega', 'warning');
                return;
            }

            const datos = {
                link: '../models/reservasInventario.php',
                op: 'listar-reservas',
                id_bodega: id_bodega,
                fecha_desde: filtros.fecha_desde || '',
                fecha_hasta: filtros.fecha_hasta || '',
                estado: filtros.estado !== undefined ? filtros.estado : ''
            };

            const resp = await enviarPeticion(datos);

            dissminSwal();

            if (resp.error) {
                initToast('bg-danger', 'Error', resp.message || 'Error al consultar reservas', 'danger');
                return;
            }

            cargarTablaReservas(resp.data || []);
        } catch (e) {
            dissminSwal();
            console.error(e);
            initToast('bg-danger', 'Error', e.message || 'Error al consultar reservas', 'danger');
        }
    };

    const cargarTablaReservas = (datos) => {
        datosTablaReservas = datos; // Guardar datos para filtrado
        const tbody = $("#tablaReservas tbody");
        if (!datos || datos.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bx bx-inbox"></i> No hay reservas para estos filtros
                    </td>
                </tr>
            `);
            return;
        }

        renderizarTabla(datos);
    };
    const renderizarTabla = (datos) => {

        const tbody = $("#tablaReservas tbody");
        let html = '';

        const agrupado = {};

        datos.forEach(item => {

            if (!agrupado[item.id_reserva]) {
                agrupado[item.id_reserva] = {
                    encabezado: item,
                    detalles: []
                };
            }

            agrupado[item.id_reserva].detalles.push(item);
        });

        Object.values(agrupado).forEach(grupo => {

            const enc = grupo.encabezado;

            let estado = `<span class="badge bg-secondary">Inactivo</span>`;
            let btnDisabled = ' disabled';
            let btnTitle = 'Reserva inactiva';
            let btnInactivarClass = 'btn-outline-secondary';

            if (enc.estado == 1) {
                estado = `<span class="badge bg-success">Activo</span>`;
                btnDisabled = '';
                btnTitle = 'Ver / Inactivar reserva';
                btnInactivarClass = 'btn-outline-success';
            }

            html += `
            <tr>
                <td>${enc.bodega_desc || 'N/A'}</td>
                <td>${enc.tercero_nombre || 'N/A'}</td>
                <td>${enc.usuario_nombre || 'N/A'}</td>
                <td>${formatarFecha(enc.fecha_reserva)}</td>

                <td class="text-center">${estado}</td>

                <td class="text-center">

                    <button class="btn btn-sm btn-outline-primary btnVerDetalle"
                        data-id="${enc.id_reserva}"
                        data-detalle='${JSON.stringify(grupo.detalles).replace(/'/g, "&apos;")}'
                        data-notas="${enc.notas || ''}">
                        <i class="bx bx-show"></i>
                    </button>

                    <button class="btn btn-sm ${btnInactivarClass} btnInactivarReserva"
                        data-id="${enc.id_reserva}"
                        data-estado="${enc.estado}"
                        title="${btnTitle}"
                        ${btnDisabled}>
                        <i class="bx bx-power-off"></i>
                    </button>

                    <button class="btn btn-sm btn-outline-danger btnGenerarPDF" data-id="${enc.id_reserva}" title="Generar PDF">
                        <i class="bx bxs-file-pdf"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        tbody.html(html);
        // ver PDF en un modal
        $(".btnGenerarPDF").on("click", function () {
            const id = $(this).data("id");
            const url = `../pdf/reservas-inventario.php?id=${id}`;
            const $body = $("#modal-pdf .modal-body");
            $body.html("");
            $("<iframe>", {
                src: url,
                style: "width:100%;height:80vh;border:none;"
            }).appendTo($body);
            $("#modal-pdf").modal("show");
        });

        // Ver detalle
        $(".btnVerDetalle").on("click", function () {
            const detalles = JSON.parse($(this).attr("data-detalle"));
            const notas = $(this).data("notas") || '';
            abrirModalDetalle(detalles, notas);
        });
        // Inactivar
        $(".btnInactivarReserva").on("click", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: "¿Inactivar reserva?",
                text: "La reserva será marcada como inactiva.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, inactivar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarReserva(id, total = 1);
                }
            });
        });
    };
    const abrirModalDetalle = (detalles, notas) => {
        let html = '';
        detalles.forEach(d => {
            let estado = `<span class="badge bg-secondary">Inactivo</span>`;
            let btnDisabled = ' disabled';
            let btnTitle = 'Reserva inactiva';
            let btnInactivarClass = 'btn-outline-secondary';
            if (d.estado_referencia == 1) {
                estado = `<span class="badge bg-success">Activo</span>`;
                btnDisabled = '';
                btnTitle = 'Inactivar reserva';
                btnInactivarClass = 'btn-outline-success';
            }
            html += `
            <tr>
                <td>${d.codigo || 'N/A'}</td>
                <td>${d.referencia_desc || 'N/A'}</td>
                <td>${d.lote || 'N/A'}</td>
                <td>${d.vencimiento || 'N/A'}</td>
                <td>${parseFloat(d.cantidad_reservada || 0)}</td>
                <td class="text-center">${estado}</td>
                <td class="text-center">
                    <button class="btn btn-sm ${btnInactivarClass} btnInactivarReservaDetalle" data-id="${d.id_detalle}" data-estado="${d.estado}" title="${btnTitle}" ${btnDisabled}>
                        <i class="bx bx-power-off"></i>
                    </button>
                </td>
            </tr>
        `;
        });

        $("#tablaDetalleReserva").html(html);
        $("#notasDetalleReserva").text(notas);
        $(".btnInactivarReservaDetalle").on("click", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: "¿Inactivar reserva?",
                text: "La reserva será marcada como inactiva.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sí, inactivar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarReserva(id);
                }
            });
        });
        $("#modalDetalleReserva").modal("show");
    };
    const eliminarReserva = async (id, total = 0) => {
        try {
            showLoadingWithTetrisLogo('Eliminando reserva...', 'Por favor espere');
            let op = '';
            if (total !== 0) {
                op = 'eliminar-reserva-total';
            } else {
                op = 'eliminar-reserva';
            }
            const resp = await enviarPeticion({
                link: '../models/reservasInventario.php',
                op: op,
                id: id
            });

            dissminSwal();
            if (resp.estado) {
                initToast('bg-success', 'Éxito', resp.message || 'Reserva eliminada correctamente', 'success');
                consultarReservados();
            } else {
                initToast('bg-danger', 'Error', resp.message || 'Error al eliminar la reserva', 'danger');
            }
        } catch (e) {
            dissminSwal();
            console.error(e);
            initToast('bg-danger', 'Error', e.message || 'Error al eliminar la reserva', 'danger');
        }
    };
    const formatarFecha = (fecha) => {
        if (!fecha) return 'N/A';
        try {
            const d = new Date(fecha);
            return d.toLocaleDateString('es-CO', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return fecha;
        }
    };
    const cargarTercero = async (item) => {
        $("#id_tercero").val(item.id);
        $("#nit").val(item.nit);
        $("#tratamiento").val(item.tratamiento);
        $("#razon_comercial").val(item.razon_comercial);
    }
    // Inicialización
    $(async function () {
        listarAlmacenes();
        limpiarFormulario();
        const tercero = await serviceTerceroEmpresa();
        if (tercero.length > 0) {
            const data = {
                link: '../models/Terceros.php',
                filtro: $.trim(tercero[0].nit),
                tipo_tercero: '',
                estado: 'A',
                op: 's_tercero'
            };

            const resp = await enviarPeticion(data);
            let datos = resp.data;

            await cargarTercero(datos[0])
        }

        fechaActual([[
            'filtroFechaDesde',
            'filtroFechaHasta'
        ]]);
        const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
            if (ConfGeneral.ManejaFechaLote == 'S') {
                cargarLote(rowData);
            } else {
                cargarReferencia(rowData);
            }
        }, {
            check_stock: false
        });

        $("#txtBusquedaReferencia").on('keyup', async function (e) {
            const filtro = $.trim($(this).val())
            if (filtro.length == 0) {
                return;
            }
            if (e.key == 'Enter') {
                const resp = await modalBusquedaRef.getRecords({
                    data: filtro,
                    cod_barras: $("#cod_barras").is(":checked")
                });
                const nregistros = modalBusquedaRef.getNrecords()

                if (nregistros > 0) {
                    if (nregistros == 1) {
                        await cargarLote(resp[0]);
                    } else {
                        modalBusquedaRef.show();
                    }
                }
            }
        });

        // Validar que txtReservado solo acepte números
        $("#txtReservado").on('keypress', function (e) {
            const char = String.fromCharCode(e.which);
            // Permitir solo números (0-9) y punto decimal
            if (!/[0-9.]/.test(char)) {
                e.preventDefault();
            }
        }).on('input', function () {
            // Remover cualquier carácter no numérico que haya podido ingresarse
            let value = $(this).val();
            value = value.replace(/[^0-9.]/g, '');
            // Permitir solo un punto decimal
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            $(this).val(value);
        });

        const { general, modulos } = await cargarConfiguraciones();
        ConfGeneral = general;

        // Eventos de botones
        $("#btnCrearReserva").on("click", crearReserva);
        $("#btnNuevo").on("click", limpiarFormulario);

        // Eventos de filtros
        $("#btnAplicarFiltros").on("click", function () {
            consultarReservados();
        });

        // Evento de limpiar búsqueda
        $("#btnLimpiarBusqueda").on("click", function () {
            renderizarTabla(datosTablaReservas);
        });

        //nuevo

        $('#txtBusquedaTercero').autocomplete({
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
                const item = ui.item;
                $("#id_tercero").val(item.id);
                $("#nit").val(item.nit);
                $("#tratamiento").val(item.tratamiento);
                $("#razon_comercial").val(item.razon_comercial);
            }
        });

        $("#btnAgregarDetalle").on("click", function () {

            let idTercero = $("#id_tercero").val();

            if (!idTercero || idTercero === '0') {
                initToast('bg-warning', 'Validación', 'Debe seleccionar un tercero válido', 'warning');
                return;
            }

            let idReferencia = $("#txtIdReferencia").val().trim();
            let referencia = $("#txtBusquedaReferencia").val().trim();
            let idLote = $("#txtIdLote").val().trim();
            let lote = $("#txtLote").val().trim();
            let cantidad = parseFloat($("#txtReservado").val());

            if (!idReferencia || !idLote) {
                initToast('bg-warning', 'Validación', 'Debe seleccionar una referencia y lote válidos', 'warning');
                return;
            }

            if (isNaN(cantidad) || cantidad <= 0) {
                initToast('bg-warning', 'Validación', 'La cantidad a reservar debe ser mayor a 0', 'warning');
                return;
            }

            const existe = detallesReserva.some(item =>
                String(item.idReferencia) === String(idReferencia)
            );

            if (existe) {
                initToast('bg-warning', 'Validación', 'La referencia ya fue agregada al detalle', 'warning');
                return;
            }

            const detalle = {
                idReferencia: String(idReferencia),
                idLote: String(idLote),
                cantidad: cantidad
            };

            detallesReserva.push(detalle);

            if ($("#detalleReserva tr td[colspan]").length > 0) {
                $("#detalleReserva").empty();
            }

            $("#detalleReserva").append(`
        <tr data-idreferencia="${idReferencia}">
            <td>${referencia}</td>
            <td>${lote}</td>
            <td class="text-end">${cantidad}</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-detalle">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        </tr>
    `);

            $("#txtIdReferencia").val('');
            $("#txtBusquedaReferencia").val('');
            $("#txtIdLote").val('');
            $("#txtLote").val('');
            $("#txtStock").val('');
            $("#txtReservado").val('');

            console.log("Después de agregar:", detallesReserva);
        });


        // Evento único para eliminar
        $(document).on("click", ".btn-eliminar-detalle", function () {

            const fila = $(this).closest("tr");
            const idRef = String(fila.attr("data-idreferencia"));

            console.log("Eliminar:", idRef);
            console.log("Antes:", detallesReserva);

            detallesReserva = detallesReserva.filter(item =>
                String(item.idReferencia) !== idRef
            );

            console.log("Después:", detallesReserva);

            fila.remove();

            if (detallesReserva.length === 0) {
                $("#detalleReserva").html(`
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="bx bx-info-circle"></i> No hay referencias agregadas
                </td>
            </tr>
        `);
            }
        });
        $("#btn-video-tutorial").click(function () {
            const videoURL = "https://youtu.be/mOCLWRY4qiA";
            const videoID = videoURL.split('/').pop().split('?')[0];
            const embedURL = `https://www.youtube.com/embed/${videoID}`;

            $("#videoFrameTutorial").attr("src", embedURL);

            const modal = new bootstrap.Modal(document.getElementById('modalVideoTutorial'));
            modal.show();
        });

        // Detener video al cerrar el modal
        $("#modalVideoTutorial").on('hidden.bs.modal', function () {
            $("#videoFrameTutorial").attr("src", "");
        });
        dissminSwal();
    });

    // Exponer funciones globales si es necesario
    window.cargarReferenciaLote = cargarReferenciaLote;
})();