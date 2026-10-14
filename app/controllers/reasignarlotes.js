let datosLoteActual = {};

/**
 * Buscar lote por referencia y/o lote
 */
const buscarLote = async () => {
    const idReferencia = $("#txtIdReferenciaActual").val().trim();
    const lote = $("#txtBusquedaLote").val().trim().toUpperCase();

    if (!idReferencia && !lote) {
        Swal.fire('Validación', 'Ingrese una referencia o lote a buscar', 'warning');
        return;
    }

    try {
        const dataSend = {
            link: '../models/reasignarlotes.php',
            op: 'buscar_lote',
            id_referencia: idReferencia,
            lote: lote
        };

        const resp = await enviarPeticion(dataSend);

        if (resp.ok && resp.data.length > 0) {
            mostrarResultados(resp.data);
            Swal.fire('Éxito', `Se encontraron ${resp.count} resultado(s)`, 'success');
        } else {
            Swal.fire('Información', resp.message || 'No se encontraron resultados', 'info');
            $("#divResultados").hide();
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Un error ha ocurrido al buscar', 'error');
    }
};

/**
 * Mostrar resultados en tabla
 */
const mostrarResultados = (datos) => {
    const tbody = $("#tblResultados tbody");
    tbody.html('');

    // Ocultar formulario de reasignación si es que estaba visible
    $("#formReasignar").hide();

    datos.forEach(item => {
        const row = `<tr>
            <td>${item.id}</td>
            <td><strong>${item.id_referencia}</strong></td>
            <td>${item.lote}</td>
            <td>${item.vencimiento}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="seleccionarLote(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    <i class="fa-solid fa-check"></i> Seleccionar
                </button>
            </td>
        </tr>`;
        tbody.append(row);
    });

    $("#divResultados").show();
};

/**
 * Seleccionar un lote y cargar sus datos
 */
const seleccionarLote = async (lote) => {
    datosLoteActual = lote;

    // Cargar transacciones
    await cargarTransacciones(lote.id, lote.id_referencia, lote.lote);

};

/**
 * Cargar transacciones del lote
 */
const cargarTransacciones = async (id_lote, id_referencia, lote) => {
    console.log({
        id_lote,
        id_referencia,
        lote
    })

    try {
        const dataSend = {
            link: '../models/reasignarlotes.php',
            op: 'obtener_transacciones',
            id_lote: id_lote,
            id_referencia: id_referencia,
            lote: lote
        };

        const resp = await enviarPeticion(dataSend);
        console.log({
            resp
        })
        if (resp.ok) {
            const modalTransacciones = new bootstrap.Modal(document.getElementById('modalTransacciones'));
            modalTransacciones.show();
            mostrarTransaccionesModal(resp.data, lote, datosLoteActual.vencimiento, id_referencia);
        } else {
            Swal.fire('Información', 'No se encontraron transacciones', 'info');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'No se pudieron cargar las transacciones', 'error');
    }
};

/**
 * Mostrar transacciones en modal
 */
const mostrarTransaccionesModal = (datos, lote, vencimiento, id_referencia) => {
    // Llenar datos del header del modal
    $("#modLote").text(lote);
    $("#modVencimiento").text(vencimiento);
    $("#modIdReferencia").text(id_referencia);
    $("#modTotalMovimientos").text(datos.length);

    // Llenar tabla
    const tbody = $("#tblTransaccionesDetalle tbody");
    tbody.html('');

    if (datos.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">Sin movimientos registrados</td></tr>');
        return;
    }

    datos.forEach(item => {
        const row = `<tr>
            <td>${item.bodega}</td>
            <td><span class="badge bg-secondary">${item.tipo}</span></td>
            <td>${item.transaccion}</td>
            <td>${new Date(item.fecha_hora).toLocaleDateString('es-CO')}</td>
            <td class="text-end">${parseFloat(item.cantidad)}</td>
            <td>${item.und}</td>
            <td><strong>${item.lote}</strong></td>
            <td>${item.vencimiento}</td>
        </tr>`;
        tbody.append(row);
    });
};

/**
 * Mostrar transacciones en tabla del formulario
 */
const mostrarTransacciones = (datos) => {
    const tbody = $("#tblTransacciones tbody");
    tbody.html('');

    if (datos.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted">Sin movimientos registrados</td></tr>');
        return;
    }

    datos.forEach(item => {
        const row = `<tr>
            <td>${item.id_referencia}</td>
            <td>${item.lote}</td>
            <td>${item.vcmto}</td>
            <td>${item.id_lote}</td>
        </tr>`;
        tbody.append(row);
    });
};

/**
 * Guardar reasignación de lote
 */
const guardarReasignacion = async (e) => {
    e.preventDefault();

    const nuevoLote = $("#txtNuevoLote").val().trim();
    const nuevoVencimiento = $("#txtNuevoVencimiento").val().trim();

    if (!nuevoLote) {
        Swal.fire('Validación', 'Ingrese el nuevo lote', 'warning');
        return;
    }

    if (!nuevoVencimiento) {
        Swal.fire('Validación', 'Ingrese el nuevo vencimiento', 'warning');
        return;
    }

    // Confirmación
    const result = await Swal.fire({
        title: '¿Confirmar cambios?',
        html: `<p>Lote: <strong>${datosLoteActual.lote}</strong> → <strong>${nuevoLote}</strong></p>
               <p>Vencimiento: <strong>${datosLoteActual.vencimiento}</strong> → <strong>${nuevoVencimiento}</strong></p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const dataSend = {
            link: '../models/reasignarlotes.php',
            op: 'guardar_reasignacion',
            id_referencia: datosLoteActual.id_referencia,
            id_lote: datosLoteActual.id,
            lote_actual: datosLoteActual.lote,
            vencimiento_actual: datosLoteActual.vencimiento,
            nuevo_lote: nuevoLote,
            nuevo_vencimiento: nuevoVencimiento
        };

        const resp = await enviarPeticion(dataSend);

        if (resp.ok) {
            Swal.fire('Éxito', resp.message, 'success');
            limpiar();
            // Recargar historial
            cargarHistorial();
        } else {
            Swal.fire('Error', resp.message, 'error');
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Un error ha ocurrido al guardar los cambios', 'error');
    }
};

/**
 * Limpiar formulario
 */
const limpiar = () => {
    $("#txtBusquedaLote").val('');
    $("#txtBusquedaReferencia").val('');
    $("#formReasignar").hide();
    $("#divResultados").hide();
    $("#tblResultados tbody").html('');
    $("#tblTransacciones tbody").html('');
    datosLoteActual = {};
};

/**
 * Cargar historial de auditoría
 */
const cargarHistorial = async (filtro = '') => {
    try {
        const dataSend = {
            link: '../models/reasignarlotes.php',
            op: 'obtener_historial',
            filtro: filtro
        };

        const resp = await enviarPeticion(dataSend);
        console.log({
            resp
        })
        if (resp.ok) {
            mostrarHistorial(resp.data);
        }
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'No se pudo cargar el historial', 'error');
    }
};

/**
 * Mostrar historial en tabla
 */
const mostrarHistorial = (datos) => {
    const tbody = $("#tblHistorial tbody");
    tbody.html('');

    if (datos.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center text-muted">Sin registros de auditoría</td></tr>');
        return;
    }

    datos.forEach(item => {
        // Formatear detalle
        let detalleHTML = '';
        if (item.detalle_obj) {
            detalleHTML = '<ul style="margin: 0; padding-left: 20px; list-style: none;">';
            for (let key in item.detalle_obj) {
                detalleHTML += `<li><strong>${key}:</strong> ${item.detalle_obj[key]}</li>`;
            }
            detalleHTML += '</ul>';
        }

        const fecha = new Date(item.fecha).toLocaleString('es-CO');

        const row = `<tr>
            <td>${fecha}</td>
            <td>${item.usuario_nombre || 'N/A'}</td>
            <td><span class="badge bg-warning">${item.accion}</span></td>
            <td><code>${item.tabla_afectada}</code></td>
            <td>
              
                ${detalleHTML}
            </td>
        </tr>`;
        tbody.append(row);
    });
};

/**
 * Mostrar detalle en modal
 */
const mostrarDetalle = (ob) => {
    try {
        const detalle = $(ob).data('element');

        //const obj = JSON.parse(detalle);

        let html = '<table class="table table-sm">';
        for (let key in detalle) {
            html += `<tr><td><strong>${key}:</strong></td><td>${detalle[key]}</td></tr>`;
        }
        html += '</table>';

        Swal.fire({
            title: 'Detalle de la Auditoría',
            html: html,
            icon: 'info',
            width: '500px'
        });
    } catch (e) {
        console.error(e);
        Swal.fire('Detalle', e.message, 'info');
    }
};
const cargarReferenciasHistorial = async (data) => {
    $("#textIdReferenciaHistorial").val(data.id);
    $("#txtFiltroHistorial").val(data.descripcion);
}
const cargarReferencia = (data) => {
    $("#txtIdReferenciaActual").val(data.id);
    $("#txtBusquedaReferencia").val(data.descripcion);
}

/**
 * Obtener el ID del tab activo
 */
const getTabActivo = () => {
    return $(".tab-pane.show.active").attr('id');
};

/**
 * Event Listeners
 */
$(document).ready(() => {

    // Botón buscar lote
    $("#btnBuscarLote").on('click', buscarLote);

    const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
        //validar que pestaña está seleccionada
        const tabActivo = getTabActivo();

        if (tabActivo === 'navs-busqueda') {
            cargarReferencia(rowData);
        } else if (tabActivo === 'navs-historial') {
            cargarReferenciasHistorial(rowData);
        }
    }, {
        check_stock: false
    });


    $("#txtBusquedaReferencia").on('keyup', async function (e) {

        const idInput = $(this).attr('id');
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
                    await cargarReferencia(resp[0]);
                } else {
                    modalBusquedaRef.show();
                }
            }
        }
    });

    $("#txtBusquedaReferencia").on('keyup', function () {
        if ($(this).val().length == 0) {
            $("#divResultados").hide();
            $("#formReasignar").hide();
            $("#tblResultados tbody").html('');
            $("#tblTransacciones tbody").html('');
            $("#txtIdReferenciaActual").val('');
            $("#txtBusquedaLote").val('')
        }

    });
/*
    $("#txtFiltroHistorial").on('keyup', async function (e) {

        const idInput = $(this).attr('id');
        let filtro = $.trim($(this).val())
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
                    await cargarReferenciasHistorial(resp[0]);
                } else {
                    modalBusquedaRef.show();
                }
            }
        }
    });*/

    $("#txtBusquedaReferenciaHistorial").on('keyup', function () {
        if ($(this).val().length == 0) {
            $("#textIdReferenciaHistorial").val('');
        }

    });

    $("#txtBusquedaLote").on('keypress', (e) => {
        if (e.which === 13) {
            buscarLote();
            return false;
        }
    });

    // Botón confirmar transacciones en modal
    $("#btnConfirmarTransacciones").on('click', () => {
        // Cerrar modal
        bootstrap.Modal.getInstance(document.getElementById('modalTransacciones')).hide();

        // Rellenar formulario con datos actuales
        $("#txtIdReferencia").val(datosLoteActual.id_referencia);
        $("#txtIdLote").val(datosLoteActual.id);
        $("#txtLoteActual").val(datosLoteActual.lote);
        $("#txtVencimientoActual").val(datosLoteActual.vencimiento);

        // Limpiar campos nuevos
        $("#txtNuevoLote").val('');
        $("#txtNuevoVencimiento").val('');

        // Ocultar tabla de resultados
        $("#divResultados").hide();

        // Mostrar formulario
        $("#formReasignar").show();

        // Scroll al formulario
        $('html, body').animate({
            scrollTop: $("#formReasignar").offset().top - 50
        }, 500);
    });

    // Envío del formulario
    $("#formReasignar").on('submit', guardarReasignacion);

    // Botón limpiar
    $("#btnLimpiar").on('click', limpiar);

    // Botón actualizar historial
    $("#btnActualizarHistorial").on('click', () => {
        let filtro = $("#txtFiltroHistorial").val();
        if ($("#textIdReferenciaHistorial").val() > 0) {
            filtro = $("#textIdReferenciaHistorial").val();
        }
        cargarHistorial(filtro);
    });

    // Filtro en tiempo real
    $("#txtFiltroHistorial").on('keyup', (e) => {
        if (e.which === 13) {
            const filtro = $("#txtFiltroHistorial").val();
            cargarHistorial(filtro);
            return false;
        }
    });
    dissminSwal();

});
