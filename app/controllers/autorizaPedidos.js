const formatDateTime = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    return date.toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

let currentPedido = null;

const confirmAutorizarRechazar = async (tipo) => {
    if (!currentPedido || currentPedido.estado !== 'P') {
        Swal.fire('No disponible', 'Solo las solicitudes pendientes pueden ser autorizadas o rechazadas.', 'warning');
        return;
    }

    const isAutorizar = tipo === 'A';
    const title = isAutorizar ? 'Autorizar pedido' : 'Rechazar pedido';
    const confirmButtonText = isAutorizar ? 'Sí, autorizar' : 'Sí, rechazar';

    const wasModalOpen = $('#modalOpciones').hasClass('show');
    if (wasModalOpen) {
        $('#modalOpciones').modal('hide');
    }

    const result = await Swal.fire({
        title,
        html: `
            <textarea id="swalObservaciones" placeholder="Agrega una observación (opcional)" style="min-height: 140px; width: 100%;"></textarea>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false,
        zIndex: 999999999,
        didOpen: () => {
            const textarea = Swal.getPopup().querySelector('#swalObservaciones');
            if (textarea) textarea.focus();
        },
        preConfirm: () => {
            const textarea = Swal.getPopup().querySelector('#swalObservaciones');
            const value = textarea ? textarea.value.trim() : '';
            if (!isAutorizar && !value) {
                Swal.showValidationMessage('Se requiere una observación para rechazar el pedido.');
                return false;
            }
            return value;
        }
    });

    if (!result.isConfirmed) {
        if (wasModalOpen) {
            $('#modalOpciones').modal('show');
        }
        return;
    }

    if (!result.isConfirmed) {
        return;
    }

    const observaciones = result.value || '';

    const op = isAutorizar ? 'autorizar_pedido' : 'rechazar_pedido';
    showLoadingWithTetrisLogo(isAutorizar ? 'Autorizando pedido...' : 'Rechazando pedido...', 'ISA Software');

    try {
        const resp = await enviarPeticion({
            link: '../models/autorizaPedidos.php',
            op,
            id: currentPedido.id,
            observaciones: observaciones || ''
        });
        dissminSwal();
        console.log({ resp });
        if (!resp || resp.ok !== true) {
            Swal.fire('Error', resp?.mensaje || 'No fue posible procesar la solicitud.', 'error');
            return;
        }

        Swal.fire('Listo', resp.mensaje, 'success');
        $('#modalOpciones').modal('hide');
        await consultarPedidos();
    } catch (error) {
        dissminSwal();
        Swal.fire('Error', error?.message || 'Error en la petición.', 'error');
    }
};

const renderPedidos = (pedidos) => {
    if (!Array.isArray(pedidos) || pedidos.length === 0) {
        $("#result").html(ShowAlert('No se encontraron pedidos', 'warning'));
        return;
    }

    let rows = '';
    const dataPedidos = {};

    pedidos.forEach((item) => {

        // Guardar objeto completo
        dataPedidos[item.id] = item;

        let estado = '';

        switch (item.estado) {
            case "P":
                estado = `
                    <span class="badge bg-warning text-dark px-3">
                        <i class="fa-solid fa-clock me-1"></i>
                        Pendiente
                    </span>`;
                break;

            case "A":
                estado = `
                    <span class="badge bg-success px-3">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        Aprobada
                    </span>`;
                break;

            case "R":
                estado = `
                    <span class="badge bg-danger px-3">
                        <i class="fa-solid fa-circle-xmark me-1"></i>
                        Rechazada
                    </span>`;
                break;

            default:
                estado = `
                    <span class="badge bg-secondary px-3">
                        <i class="fa-solid fa-question me-1"></i>
                        Desconocido
                    </span>`;
                break;
        }

        rows += `
            <tr data-id="${item.id}">
                <td>${item.id || ''}</td>
                <td>${formatDateTime(item.fecha_solicitud)}</td>
                <td>${item.tratamiento || ''}
                    <br>
                    <small class="text-primary">
                        ${item.razon_comercial || ''}
                    </small>
                </td>
                <td class="text-end">
                    ${formatNum(item.valor_total, '$')}
                </td>
                <td class="text-center">
                    ${estado}
                </td>
                <td class="text-center">
                    <button 
                        class="btn btn-sm btn-primary btn-opciones"
                        title="Opciones">
                        <i class="fa-solid fa-sliders"></i>
                    </button>
                </td>
            </tr>
        `;
    });


    $("#result").html(`
        <div class="table-responsive">
            <table id="autorizaPedidosTable" 
                   class="table table-sm table-bordered table-hover table-striped align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Id</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th class="text-end">Valor total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `);


    // Evento botón opciones
    $("#autorizaPedidosTable tbody")
        .off("click", ".btn-opciones")
        .on("click", ".btn-opciones", function () {
            const id = $(this).closest("tr").data("id");
            opciones(dataPedidos[id]);

        });

};

const carteraTercero = async (id_tercero) => {
    try {
        showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
        const data = {
            link: '../models/erm.php',
            op: 's_documentos',
            id_tercero,
            estado: 'S',
            anio: ''
        };

        var resp = await enviarPeticion(data);
        if (resp.length == 0) {
            dissminSwal()
            $("#mdl-cartera-pendiente").html(`<div class="alert alert-danger" role="alert">
                            <i class="fa-solid fa-bell fa-shake"></i> Sin resultados para las condiciones seleccionadas.
                          </div>`);
            return;
        }
        const tb = ` <table id="cartera-detalle" class="table table-sm table-borderless table-hover table-striped w-100">
                    <thead class="table-primary"></thead> 
                    <tbody></tbody>
                  </table>`
        $("#mdl-cartera-pendiente").html(tb);

        createDataTable(
            'cartera-detalle',
            resp,
            ['tipo', 'numero', 'fecha', 'vencimiento', 'dias', 'total', 'total_aplicado', 'total_vencida', 'total_sin_vencer', 'c_1_30', 'c_31_60', 'c_61_90', 'c_91_120'],
            ['Tipo', 'Numero', 'Fecha', 'Vencimiento', 'Dias', 'Total', 'Aplicado', 'Vencido', 'Sin Vencer', '1_30', '31_60', '61_90', '90_120'],
            null,
            null,
            true,
            `
        <button class="btn btn-sm btn-outline-primary other-btn" title="Imprimir">
          <i class="fa-solid fa-print"></i>
        </button>
      `,
            imprimir,
            true,
            ['total_vencida', 'total_sin_vencer', 'total', 'total_aplicado', 'c_1_30', 'c_31_60', 'c_61_90', 'c_91_120'],
            'COP',
            [],
            []
        );

        dissminSwal();
    } catch (e) {
        console.log(e);
        dissminSwal();
    }
};

const imprimir = (data) => {
    ImprimirDocumento(data.sw, data.id);
};

const opciones = async (data) => {
    console.log({ data });
    currentPedido = data;
    $("#mdl-fecha-solicitud").text(formatDateTime(data.fecha_solicitud));
    $("#mdl-usuario-solicita").text(data.usuario_solicita || '');
    $("#mdl-observaciones").text(data.observaciones || '');
    $("#mdl-observaciones-autoriza").text(data.observaciones_autoriza || '');
    $("#mdl-fecha-autorizacion").text(formatDateTime(data.fecha_autorizacion));
    $("#mdl-usuario-autoriza").text(data.usuario_autoriza || '');
    $("#mdl-observaciones-autorizacion").text(data.observaciones_autorizacion || '');
    $("#mdl-id-pedido").text(data.id_pedido || '');
    $("#mdl-id-solicitud").text(data.id || '');
    $("#mdl-cliente").text(data.razon_comercial || '');

    const isPending = data.estado === 'P';
    $("#btn-autorizar").prop('disabled', !isPending);
    $("#btn-rechazar").prop('disabled', !isPending);

    await carteraTercero(data.id_tercero);
    $("#modalOpciones").modal("show");
}

const consultarPedidos = async () => {
    showLoadingWithTetrisLogo('Consultando pedidos', 'ISA Software');
    try {
        const fecha_inicial = $("#fecha_inicial").val();
        const fecha_final = $("#fecha_final").val();
        const estado = $("#estado_autorizacion").val();

        const data = {
            link: '../models/autorizaPedidos.php',
            op: 'consultar_pedidos',
            fecha_inicial,
            fecha_final,
            estado
        };

        const resp = await enviarPeticion(data);
        console.log({ resp });

        if (!resp || (Array.isArray(resp) && resp.length === 0)) {
            $("#result").html(ShowAlert('No se encontraron pedidos', 'warning'));
            dissminSwal();
            return;
        }

        renderPedidos(resp);
        dissminSwal();
    } catch (err) {
        console.error(err);
        $("#result").html(ShowAlert('Error interno de la aplicación', 'error'));
        dissminSwal();
    }
};

$(function () {
    fechaActual([['fecha_inicial', 'fecha_final']]);

    $("#btnBuscarPedidos").click(function () {
        consultarPedidos();
    });

    $("#btnLimpiarPedidos").click(function () {
        $("#fecha_inicial").val('');
        $("#fecha_final").val('');
        $("#estado_autorizacion").val('');
        $("#result").html('');
    });

    $("#btn-autorizar").click(() => confirmAutorizarRechazar('A'));
    $("#btn-rechazar").click(() => confirmAutorizarRechazar('R'));

    dissminSwal();
});