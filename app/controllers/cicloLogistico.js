const consultarPedidos = async () => {
    const fecha_inicial = $.trim($("#fhIni").val());
    const fecha_final = $.trim($("#fhFin").val());

    const resp = await enviarPeticion({
        op: "listar_pedidos",
        link: "../models/Pedidos.php",
        fecha_inicial,
        fecha_final,
        nit: '',
        sw: 8,
        estado: "",
        logistico: "S"
    });
    actualizarTimelinePedidos(resp.data);
    listarPedidos(resp.data);

}
function obtenerEstadoPedido(pedido) {

    // ENTREGADO
    if (pedido.estado_despacho === 'E') {
        return `
            <span class="badge text-bg-success">
                <i class="fa-solid fa-circle-check me-1"></i>
                Entregado
            </span>
        `;
    }

    // DESPACHADO
    if (pedido.estado_despacho === 'D') {
        return `
            <span class="badge text-bg-primary">
                <i class="fa-solid fa-truck-fast me-1"></i>
                Despachado
            </span>
        `;
    }

    // FACTURADO
    if (
        pedido.id_factura !== null &&
        Number(pedido.id_factura) > 0
    ) {
        return `
            <span class="badge text-bg-success">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i>
                Facturado
            </span>
        `;
    }

    // SEPARADO
    if (
        pedido.inicio_separacion !== null &&
        pedido.fin_separacion !== null
    ) {
        return `
            <span class="badge text-bg-info">
                <i class="fa-solid fa-boxes-stacked me-1"></i>
                Separado
            </span>
        `;
    }

    // AUTORIZACIÓN PENDIENTE
    if (pedido.estado_autorizacion === 'P') {
        return `
            <span class="badge text-bg-warning">
                <i class="fa-solid fa-user-lock me-1"></i>
                Autorización pendiente
            </span>
        `;
    }

    // AUTORIZACIÓN RECHAZADA
    if (pedido.estado_autorizacion === 'R') {
        return `
            <span class="badge text-bg-danger">
                <i class="fa-solid fa-circle-xmark me-1"></i>
                Rechazado
            </span>
        `;
    }

    // CONFIRMADO
    if (pedido.estado === 'C') {
        return `
            <span class="badge text-bg-warning">
                <i class="fa-solid fa-circle-check me-1"></i>
                Confirmado
            </span>
        `;
    }

    // TEMPORAL
    if (pedido.estado === 'T') {
        return `
            <span class="badge text-bg-secondary">
                <i class="fa-regular fa-file me-1"></i>
                Temporal
            </span>
        `;
    }

    return `
        <span class="badge text-bg-secondary">
            Sin estado
        </span>
    `;
}

// =========================================================
// OBTENER ESTADO ACTUAL DEL PEDIDO
// =========================================================
function obtenerEstadoActual(pedido) {

    // -----------------------------------------------------
    // 1. DESPACHADO / ENTREGADO
    // -----------------------------------------------------
    if (pedido.estado_despacho === 'E') {
        return {
            texto: 'Entregado',
            clase: 'bg-success',
            icono: 'fa-solid fa-circle-check'
        };
    }

    if (pedido.estado_despacho === 'D') {
        return {
            texto: 'Despachado',
            clase: 'bg-primary',
            icono: 'fa-solid fa-truck-fast'
        };
    }

    // -----------------------------------------------------
    // 2. FACTURADO
    // -----------------------------------------------------
    if (
        pedido.id_factura &&
        Number(pedido.id_factura) > 0
    ) {
        return {
            texto: 'Facturado',
            clase: 'bg-success',
            icono: 'fa-solid fa-file-invoice-dollar'
        };
    }

    // -----------------------------------------------------
    // 3. SEPARADO
    // -----------------------------------------------------
    if (pedido.fin_separacion) {
        return {
            texto: 'Separado',
            clase: 'bg-info',
            icono: 'fa-solid fa-boxes-stacked'
        };
    }

    // -----------------------------------------------------
    // 4. AUTORIZACIÓN
    // -----------------------------------------------------
    if (pedido.estado_autorizacion === 'P') {
        return {
            texto: 'Aut. Pendiente',
            clase: 'bg-warning',
            icono: 'fa-solid fa-user-lock'
        };
    }

    if (pedido.estado_autorizacion === 'R') {
        return {
            texto: 'Aut. rechazada',
            clase: 'bg-danger',
            icono: 'fa-solid fa-user-lock'
        };
    }

    // -----------------------------------------------------
    // 5. CONFIRMADO
    // -----------------------------------------------------
    if (pedido.estado === 'C') {
        return {
            texto: 'Confirmado',
            clase: 'bg-warning',
            icono: 'fa-solid fa-circle-check'
        };
    }

    // -----------------------------------------------------
    // 6. TEMPORAL
    // -----------------------------------------------------
    if (pedido.estado === 'T') {
        return {
            texto: 'Temporal',
            clase: 'bg-secondary',
            icono: 'fa-regular fa-file'
        };
    }

    // -----------------------------------------------------
    // ESTADO DESCONOCIDO
    // -----------------------------------------------------
    return {
        texto: 'Sin estado',
        clase: 'bg-secondary',
        icono: 'fa-solid fa-circle-question'
    };
}


function listarPedidos(datos) {

    const $contenedor = $("#resultado-pedidos");

    if (!Array.isArray(datos) || datos.length === 0) {
        $contenedor.html(`
            <div class="text-center text-muted py-4">
                <i class="fa-regular fa-folder-open fa-2x mb-2"></i>
                <div>No hay pedidos para mostrar</div>
            </div>
        `);
        return;
    }



    // =========================================================
    // TABLA
    // =========================================================

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Fecha</th>
                        <th>Autorizado</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Notas</th>
                        <th class="text-center">Estado actual</th>
                    </tr>
                </thead>

                <tbody>
    `;


    datos.forEach(function (pedido) {

        // =====================================================
        // DATOS BÁSICOS
        // =====================================================

        const pedidoNumero =
            pedido.numero ??
            pedido.id_pedido ??
            '';

        const cliente =
            pedido.razon_comercial ||
            pedido.nombres ||
            'Sin cliente';

        const fechaHora = pedido.fecha_hora || '';
        const [fecha, hora] = fechaHora.split(' ');

        const fechaAutorizacionHora = pedido.fecha_autorizacion || '';
        const [fechaAutorizacion, horaAutorizacion] =
            fechaAutorizacionHora.split(' ');

        // =====================================================
        // ESTADO ACTUAL
        // =====================================================

        const estadoActual = obtenerEstadoActual(pedido);


        // =====================================================
        // FILA
        // =====================================================

        html += `
            <tr data-id-pedido="${pedido.id_pedido}">

                <td>
                    <div class="fw-semibold">
                        ${fecha || ''}
                    </div>

                    <small class="text-muted d-block">
                        <i class="fa-regular fa-clock me-1"></i>
                        ${hora || ''}
                    </small>
                </td>
                <td>
                    <div class="fw-semibold">
                        ${fechaAutorizacion || ''}
                    </div>

                    ${horaAutorizacion
                ? `
                                <small class="text-muted d-block">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    ${horaAutorizacion}
                                </small>
                            `
                : ''
            }
                </td>
                <td>
                    <div class="fw-semibold">
                        ${pedido.tipo || 'PED'}-${pedidoNumero}
                    </div>

                    <small class="text-muted">
                        ID ${pedido.id_pedido}
                    </small>
                </td>

                <td>
                    <div class="fw-semibold">
                        ${cliente}
                    </div>

                    <small class="text-muted d-block">
                        NIT: ${pedido.nit || ''}
                    </small>

                    <small class="text-muted d-block">
                        CIUDAD: ${pedido.departamento || ''} | ${pedido.municipio || ''}
                    </small>
                </td>
                <td>
                    <small>
                        ${pedido.notas || 'Sin notas'}
                    </small>
                </td>


                <td class="text-center">
                  <span class="d-inline-flex flex-column align-items-center mt-2">
                        <span class="badge ${estadoActual.clase} rounded-pill">
                            <i class="${estadoActual.icono}"></i>
                        </span>

                        <span class="small fw-semibold mt-1 text-center">
                            ${estadoActual.texto}
                        </span>
                    </span>
                </td>

            </tr>
        `;
    });


    html += `
                </tbody>
            </table>
        </div>
    `;

    $contenedor.html(html);
}
function actualizarTimelinePedidos(datos) {

    if (!Array.isArray(datos)) {
        datos = [];
    }

    // =========================================================
    // CONTADORES DE ESTADO ACTUAL
    // =========================================================

    let temporales = 0;
    let autorizacionPendiente = 0;
    let autorizacionAutorizada = 0;
    let autorizacionRechazada = 0;

    let confirmados = 0;
    let separados = 0;
    let facturados = 0;
    let despachados = 0;

    // =========================================================
    // RECORRER PEDIDOS
    // =========================================================

    datos.forEach(function (pedido) {

        let estadoActual = '';

        // =====================================================
        // 1. ENTREGADO
        // =====================================================

        if (pedido.estado_despacho === 'E') {

            estadoActual = 'E';

            // =====================================================
            // 2. DESPACHADO
            // =====================================================

        } else if (pedido.estado_despacho === 'D') {

            estadoActual = 'D';

            // =====================================================
            // 3. FACTURADO
            // =====================================================

        } else if (
            pedido.id_factura !== null &&
            Number(pedido.id_factura) > 0
        ) {

            estadoActual = 'F';

            // =====================================================
            // 4. SEPARADO
            // =====================================================

        } else if (
            pedido.inicio_separacion !== null &&
            pedido.fin_separacion !== null
        ) {

            estadoActual = 'S';

            // =====================================================
            // 5. AUTORIZACIÓN
            // =====================================================

        } else if (pedido.estado_autorizacion === 'P') {

            estadoActual = 'AP';

        } else if (pedido.estado_autorizacion === 'R') {

            estadoActual = 'AR';

        } else if (pedido.estado_autorizacion === 'A') {

            /*
             * Si ya está autorizada pero todavía no ha pasado
             * a confirmado, la consideramos autorización.
             */
            if (pedido.estado !== 'C') {
                estadoActual = 'AA';
            } else {
                estadoActual = 'C';
            }

            // =====================================================
            // 6. CONFIRMADO
            // =====================================================

        } else if (pedido.estado === 'C') {

            estadoActual = 'C';

            // =====================================================
            // 7. TEMPORAL
            // =====================================================

        } else if (pedido.estado === 'T') {

            estadoActual = 'T';
        }


        // =====================================================
        // CONTABILIZAR SOLAMENTE EL ESTADO ACTUAL
        // =====================================================

        switch (estadoActual) {

            case 'T':
                temporales++;
                break;

            case 'AP':
                autorizacionPendiente++;
                break;

            case 'AA':
                autorizacionAutorizada++;
                break;

            case 'AR':
                autorizacionRechazada++;
                break;

            case 'C':
                confirmados++;
                break;

            case 'S':
                separados++;
                break;

            case 'F':
                facturados++;
                break;

            case 'D':
            case 'E':
                despachados++;
                break;
        }

    });


    // =========================================================
    // ACTUALIZAR BADGES
    // =========================================================

    $("#p_temporales").text(temporales);

    $("#p_confirmados").text(confirmados);

    $("#p_separados").text(separados);

    $("#p_facturados").text(facturados);

    $("#p_despachados").text(despachados);


    // =========================================================
    // AUTORIZACIONES
    // =========================================================

    const totalAutorizaciones =
        autorizacionPendiente +
        autorizacionAutorizada +
        autorizacionRechazada;

    $("#p_autorizacion").text(totalAutorizaciones);


    // =========================================================
    // ESTADO VISUAL DE AUTORIZACIÓN
    // =========================================================

    const $circle = $("#circle_autorizacion");
    const $icon = $("#icon_autorizacion");
    const $badge = $("#p_autorizacion");

    // Limpiar
    $circle.removeClass(
        "bg-secondary bg-warning bg-success bg-danger"
    );

    $badge.removeClass(
        "bg-secondary bg-warning bg-success bg-danger"
    );

    $icon.removeClass(
        "fa-user-lock fa-clock fa-check fa-circle-xmark"
    );


    /*
     * Prioridad visual:
     *
     * 1. Pendientes
     * 2. Rechazadas
     * 3. Autorizadas
     * 4. Sin autorizaciones
     */

    if (autorizacionPendiente > 0) {

        $circle.addClass("bg-warning");
        $badge.addClass("bg-warning");

        $icon.addClass("fa-clock");

        $("#btn_autorizacion").attr(
            "title",
            `${autorizacionPendiente} autorización(es) pendiente(s)`
        );

    } else if (autorizacionRechazada > 0) {

        $circle.addClass("bg-danger");
        $badge.addClass("bg-danger");

        $icon.addClass("fa-circle-xmark");

        $("#btn_autorizacion").attr(
            "title",
            `${autorizacionRechazada} autorización(es) rechazada(s)`
        );

    } else if (autorizacionAutorizada > 0) {

        $circle.addClass("bg-success");
        $badge.addClass("bg-success");

        $icon.addClass("fa-check");

        $("#btn_autorizacion").attr(
            "title",
            `${autorizacionAutorizada} autorización(es) autorizada(s)`
        );

    } else {

        $circle.addClass("bg-secondary");
        $badge.addClass("bg-secondary");

        $icon.addClass("fa-user-lock");

        $("#btn_autorizacion").attr(
            "title",
            "Sin solicitudes de autorización"
        );
    }


    // =========================================================
    // TOOLTIP
    // =========================================================

    const tooltipElement =
        document.getElementById("btn_autorizacion");

    if (tooltipElement) {

        const tooltip =
            bootstrap.Tooltip.getInstance(tooltipElement);

        if (tooltip) {
            tooltip.dispose();
        }

        new bootstrap.Tooltip(tooltipElement);
    }
}
$(async function () {

    fechaActual(['fhIni', 'fhFin']);


    await consultarPedidos();

    $('#fhIni, #fhFin').on('change', async function () {
        await consultarPedidos();
    });

    setInterval(async () => {
        await consultarPedidos();
    }, 20000);
    dissminSwal();
});