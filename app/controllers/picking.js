var cronometroInterval;
var segundosTotales = 0;

const formatTime = (segundos) => {
    const h = Math.floor(segundos / 3600).toString().padStart(1, '0');
    const m = Math.floor((segundos % 3600) / 60).toString().padStart(2, '0');
    const s = (segundos % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
};

const iniciarCronometro = () => {
    detenerCronometro();
    cronometroInterval = setInterval(() => {
        segundosTotales++;
        $("#tiempo").text(formatTime(segundosTotales));
    }, 1000);
};

const detenerCronometro = () => {
    clearInterval(cronometroInterval);
    segundosTotales = 0;
    $("#tiempo").text('0:00:00');
};

const listarPendientes = async () => {

    showLoadingWithTetrisLogo('Consultando pedidos pendientes', 'ISA Software');

    $("#ContenidoPendientes").html(`
        <div class="d-flex justify-content-center align-items-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    `);
    $("#ModalPendientes").modal("show");
    try {
        const resp = await enviarPeticion({
            link: "../models/picking.php",
            op: "cargar-pedido",
            list: 0
        });
        dissminSwal();
        if (!resp || resp.length === 0) {

            $("#ContenidoPendientes").html(`
                <div class="text-center py-5">
                    <i class="fa-regular fa-calendar-check fs-1 text-muted opacity-25 mb-3"></i>
                    <h6 class="fw-bold">No hay pedidos pendientes</h6>
                    <small class="text-muted">
                        En este momento no existen pedidos para separar.
                    </small>
                </div>
            `);

            return;
        }
        let html = '<div class="list-group list-group-flush">';
        resp.forEach((d) => {
            const badgeEstado = d.estado == 1
                ? '<span class="badge bg-warning text-dark">En proceso</span>'
                : '<span class="badge bg-success">Pendiente</span>';

            html += `
                <div class="list-group-item py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">
                                    ${d.tipo} - ${d.numero}
                                </h6>

                                ${badgeEstado}
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="fa-regular fa-clock me-1"></i>
                                ${d.fecha_hora}
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="fa-solid fa-location-dot me-1 text-danger"></i>
                                ${d.ciudad}
                            </div>
                            ${d.notas
                    ? `
                                    <div class="small text-warning-emphasis">
                                        <i class="fa-solid fa-note-sticky me-1"></i>
                                        ${d.notas}
                                    </div>`
                    : ""
                }
                        </div>
                        <button
                            class="btn btn-outline-primary btn-sm ms-3"
                            onclick="AddPedido(${d.id},'${d.tipo}',${d.numero})">
                            <i class="fa-solid fa-download me-1"></i>
                            Cargar
                        </button>
                    </div>
                </div>
            `;
        });
        html += "</div>";
        $("#ContenidoPendientes").html(html);
    } catch (error) {
        dissminSwal();
        console.error(error);
        $("#ContenidoPendientes").html(`
            <div class="text-center py-5">
                <i class="fa-solid fa-triangle-exclamation fs-1 text-danger mb-3"></i>
                <h6 class="fw-bold">Error al consultar la información</h6>
                <small class="text-muted">
                    Intente nuevamente en unos minutos.
                </small>
            </div>
        `);
    }
};

const AddPedido = async (id_pedido, tipo, numero, sinFinalizar = 0) => {

    if (sinFinalizar === 0) {
        const pendientes = await validarPendientes();

        if (pendientes) {
            await Swal.fire({
                icon: 'warning',
                title: 'Pedido en curso',
                html: `
                <p class="mb-2">
                    No puedes cargar un nuevo pedido porque tienes
                    <strong>un pedido en curso</strong>.
                </p>
                <p class="mb-0">
                    Finaliza los pedidos pendientes antes de comenzar uno nuevo.
                </p>
            `,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#696cff'
            });
            return;
        }
    }
    const result = await Swal.fire({
        icon: 'question',
        title: `¿Cargar pedido ${tipo}-${numero}?`,
        text: 'Al cargarlo se iniciará el proceso de separación.',
        showCancelButton: true,
        confirmButtonText: 'Sí, cargar pedido',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    });

    if (!result.isConfirmed) {
        return;
    }

    $("#codigo").text(`${tipo} - ${numero}`);
    $("#id_pedido").val(id_pedido);
    await cargarReferencias(id_pedido);

    $("#ModalPendientes").modal('hide');
};
const listarSinFinalizar = async () => {

    showLoadingWithTetrisLogo('Consultando pedidos...', 'ISA Software');

    try {

        $("#TituloModalPendientes").text("Pedidos sin finalizar");
        $("#ContenidoPendientes").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
        `);
        $("#ModalPendientes").modal('show');

        const resp = await enviarPeticion({
            link: '../models/picking.php',
            op: 'cargar-sin-finalizar'
        });
        dissminSwal();

        let html = '';

        if (resp && resp.length > 0) {

            html = '<div class="list-group list-group-flush">';

            resp.forEach((d) => {

                html += `
                    <div class="list-group-item p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-1">
                                    Pedido #${d.tipo}-${d.numero}
                                </h6>
                                <small class="text-muted d-block">
                                    <i class="fa-regular fa-clock me-1"></i>
                                    ${d.fecha_hora}
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fa-solid fa-location-dot me-1"></i>
                                    ${d.ciudad}
                                </small>
                                ${d.inicio ? `
                                    <small class="text-warning d-block">
                                        <i class="fa-solid fa-hourglass-half me-1"></i>
                                        Iniciado: ${d.inicio}
                                    </small>
                                ` : ''}
                                ${d.notas ? `
                                    <div class="small text-warning fw-bold mt-1">
                                        <i class="fa-solid fa-note-sticky me-1"></i>
                                        ${d.notas}
                                    </div>
                                ` : ''}
                            </div>
                            <button class="btn btn-warning btn-sm rounded-pill px-3 fw-bold"
                                     onclick="AddPedido(${d.id},'${d.tipo}',${d.numero}, 1)">
                                Continuar
                            </button>
                        </div>
                    </div>`;
            });

            html += '</div>';

        } else {
            html = `
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-circle-check fs-1 mb-3 text-success"></i>
                    <h6>No existen pedidos sin finalizar.</h6>
                </div>`;
        }

        $("#ContenidoPendientes").html(html);
    } catch (error) {
        dissminSwal();
        console.error(error);
        Swal.fire(
            "Error",
            "Error al consultar la información, contacte al administrador.",
            "error"
        );
    }
};

const validarPendientes = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../models/picking.php',
            op: 'cargar-sin-finalizar'
        });

        return Array.isArray(resp) && resp.length > 0;

    } catch (error) {
        console.error('Error validando pedidos pendientes:', error);
        return false;
    }
};

const play = async () => {
    showLoadingWithTetrisLogo('Buscando pedidos pendientes', 'ISA Software');
    const pendientes = await validarPendientes();

    if (pendientes) {
        await Swal.fire({
            icon: 'warning',
            title: 'Pedido en curso',
            html: `
            <p class="mb-2">
                No puedes iniciar un nuevo pedido porque tienes
                <strong>un pedido en curso</strong>.
            </p>
            <p class="mb-0">
                Finaliza los pedidos pendientes antes de comenzar uno nuevo.
            </p>
        `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#696cff'
        });

        return;
    }
    try {
        const resp = await enviarPeticion({
            link: '../models/picking.php',
            op: 'cargar-pedido',
            list: 1
        });

        if (resp && resp.length > 0) {
            $("#codigo").text(`${resp[0].tipo} - ${resp[0].numero}`);
            $("#id_pedido").val(resp[0].id);
            await cargarReferencias(resp[0].id);
            dissminSwal();
        } else {
            Swal.fire(
                "Oops",
                "No se encontraron pedidos pendientes por separar",
                "warning"
            );
        }
    } catch (e) {
        console.log(e);
        Swal.fire(
            "Error",
            "Error al consultar la información, contacte al administrador.",
            "error"
        );
    }
};

const cargarReferencias = async (id_pedido) => {
    try {

        showLoadingWithTetrisLogo('Cargando pedido...', 'ISA Software');

        const resp = await enviarPeticion({
            link: '../models/picking.php',
            op: 'iniciar-separacion',
            id: id_pedido
        });

        dissminSwal();

        // Validaciones del servidor
        if (!resp.ok) {
            Swal.fire({
                icon: "warning",
                title: "No es posible iniciar la separación",
                text: resp.mensaje
            });
            return false;
        }
        const productos = resp.productos;
        if (!productos || productos.length === 0) {
            $("#lista").html(`
                <div class="text-center py-5">
                    <i class="fa-solid fa-box-open fs-1 text-secondary mb-3"></i>
                    <h6>No hay productos para separar.</h6>
                </div>
            `);
            return;
        }

        let html = "";
        let totalProds = 0;
        productos.forEach((d, index) => {
            totalProds++;
            html += `
            <div class="card shadow-sm border mb-2"
                onclick="Seleccionar(this)"
                data-picked="false"
                id="card-${index}"
                style="cursor:pointer;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <span class="badge bg-secondary border mb-2">
                            ${d.codigo}
                        </span>
                        <h6 class="fw-bold mb-1">
                            ${d.descripcion}
                        </h6>
                        <small class="text-muted">
                            <i class="fa-solid fa-flask text-info me-1"></i>
                            ${d.marca}
                        </small>
                    </div>
                    <div class="text-center ms-3">
                        <span class="badge bg-primary fs-6 rounded-pill px-3">
                            ${d.pendiente}
                        </span>
                        <div class="mt-2 rounded-circle border bg-light d-flex justify-content-center align-items-center"
                            style="width:35px;height:35px;">
                            <i class="fa-solid fa-check text-success"></i>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        $("#id_pedido").val(id_pedido);
        $("#lista").html(html);
        $("#empty-state").addClass("d-none");

        if (productos[0].notas && productos[0].notas.trim() !== "") {
            $("#txtNotasDisplay").text(productos[0].notas);
            $("#seccion-notas").removeClass("d-none");
        } else {
            $("#txtNotasDisplay").text("Sin notas");
            $("#seccion-notas").addClass("d-none");
        }

        $("#totalProductos").text(totalProds);
        $("#totalSeparados").text(0);
        $("#totalPendientes").text(totalProds);
        actualizarBarraProgreso();
        iniciarCronometro();
        $("#btnIniciar").addClass("d-none");
        $("#btnFinalizar").removeClass("d-none");
        return true;
    } catch (e) {
        dissminSwal();
        console.error(e);
        Swal.fire(
            "Error",
            "No fue posible cargar el pedido.",
            "error"
        );
        return false;
    }
};

const Seleccionar = (card) => {
    const $card = $(card);
    const seleccionado = $card.attr("data-picked") === "true";
    if (seleccionado) {
        if (navigator.vibrate) navigator.vibrate(50);
        $card.attr("data-picked", "false");
        $card.removeClass("border-success bg-success-subtle");
        $card.find(".rounded-circle").removeClass("bg-success border-success").addClass("bg-light");
        $card.find(".fa-check").removeClass("text-white").addClass("text-success");
        actualizarContadores(-1);
    } else {
        if (navigator.vibrate) navigator.vibrate([50, 50, 50]);
        $card.attr("data-picked", "true");
        $card.addClass("border-success bg-success-subtle");
        $card.find(".rounded-circle").removeClass("bg-light").addClass("bg-success border-success");
        $card.find(".fa-check").removeClass("text-success").addClass("text-white");
        actualizarContadores(1);
    }
};

const actualizarContadores = (change) => {
    let separados = parseInt($("#totalSeparados").text()) || 0;
    let total = parseInt($("#totalProductos").text()) || 0;
    separados += change;
    let pendientes = total - separados;

    $("#totalSeparados").text(separados);
    $("#totalPendientes").text(pendientes);

    actualizarBarraProgreso();

    if (pendientes === 0 && change > 0) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });


        Toast.fire({
            icon: 'success',
            title: '¡Pedido Completo!'
        });

    }

};
const actualizarBarraProgreso = () => {
    let total = parseInt($("#totalProductos").text()) || 0;
    let separados = parseInt($("#totalSeparados").text()) || 0;
    let porcentaje = 0;

    if (total > 0) {
        porcentaje = Math.round((separados / total) * 100);
    }

    $("#barProgreso").css("width", porcentaje + "%").attr("aria-valuenow", porcentaje);
    $("#lblProgreso").text(porcentaje + "%");

    if (porcentaje >= 100) {
        $("#barProgreso").removeClass("bg-primary").addClass("bg-success");
    } else {
        $("#barProgreso").removeClass("bg-success").addClass("bg-primary");
    }

};
const stop = async () => {
    const idPedido = $("#id_pedido").val();
    if (!idPedido) {
        Swal.fire(
            "Atención",
            "No hay ningún pedido en proceso.",
            "warning"
        );
        return;
    }
    const faltantes = parseInt($("#totalPendientes").text()) || 0;
    const result = await Swal.fire({
        title: faltantes > 0 ? "⚠️ Pedido incompleto" : "¿Finalizar separación?",
        text: faltantes > 0
            ? `Aún faltan ${faltantes} producto(s) por separar. ¿Desea finalizar de todas formas?`
            : "Se registrará la separación del pedido.",
        icon: faltantes > 0 ? "warning" : "question",
        showCancelButton: true,
        confirmButtonText: "Sí, finalizar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: faltantes > 0 ? "#f59e0b" : "#198754",
        reverseButtons: true
    });
    if (!result.isConfirmed) return;
    try {
        showLoadingWithTetrisLogo(
            "Guardando separación...",
            "ISA Software"
        );
        const resp = await enviarPeticion({
            link: "../models/picking.php",
            op: "finalizar-separacion",
            id: idPedido
        });
        dissminSwal();
        if (resp.ok) {
            await Swal.fire({
                icon: "success",
                title: "Proceso finalizado",
                text: resp.mensaje,
                timer: 2000,
                showConfirmButton: false
            });
            Limpiar();
        } else {
            Swal.fire(
                "Error",
                resp.mensaje,
                "error"
            );
        }

    } catch (e) {
        dissminSwal();
        console.error(e);
        Swal.fire(
            "Error",
            "Ocurrió un error al finalizar la separación.",
            "error"
        );

    }

};
const Limpiar = () => {
    // Pedido
    $("#id_pedido").val('');
    $("#codigo").text('---');
    $("#tiempo").text('0:00:00');

    // Contadores
    $("#totalProductos").text(0);
    $("#totalSeparados").text(0);
    $("#totalPendientes").text(0);

    // Lista
    $("#lista").empty();
    $("#empty-state").removeClass('d-none');

    // Notas
    $("#txtNotas").val('');
    $("#txtNotasDisplay").text('Sin notas');
    $("#seccion-notas").addClass('d-none');

    // Barra de progreso
    $("#barProgreso").css('width', '0%').removeClass('bg-success').addClass('bg-primary').attr('aria-valuenow', 0);

    $("#lblProgreso").text('0%');

    // Cronómetro
    detenerCronometro();

    // Botones
    $("#btnFinalizar").addClass('d-none');
    $("#btnIniciar")
        .removeClass('d-none')
        .prop('disabled', false)
        .html('<i class="fa-solid fa-play me-1"></i> INICIAR SIGUIENTE');

};
$(async function () {
    $("#btnIniciar").click(function () {
        play();
    });
    $("#btnFinalizar").click(function () {
        stop();
    });
    $("#btnPedPendientes").click(async function () {
        await listarPendientes();
    });
    $("#btnSinFinalizar").click(async function () {
        await listarSinFinalizar();
    });
    dissminSwal();
});