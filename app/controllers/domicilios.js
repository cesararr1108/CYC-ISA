const consultarDomicilios = async () => {

    showLoadingWithTetrisLogo(
        'Consultando domicilios',
        'ISA Software'
    );

    try {

        const fecha_actual = $("#fecha_actual").val();

        const resp = await enviarPeticion({
            link: '../models/domicilios.php',
            op: 'consultar_domicilios',
            fecha: fecha_actual
        });

        // Obtener los datos independientemente de cómo venga la respuesta
        const domicilios = Array.isArray(resp)
            ? resp
            : Array.isArray(resp?.data)
                ? resp.data
                : Array.isArray(resp?.datos)
                    ? resp.datos
                    : [];

        renderizarDomicilios(domicilios);

    } catch (error) {

        console.error('Error consultando domicilios:', error);

        $("#listaDomicilios").html(`
            <div class="col-12">
                <div class="alert alert-danger mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Ocurrió un error al consultar los domicilios.
                </div>
            </div>
        `);

    } finally {

        dissminSwal();

    }
};

const renderizarDomicilios = (domicilios) => {

    const contenedor = $("#listaDomicilios");

    contenedor.empty();

    // =====================================================
    // SIN DOMICILIOS
    // =====================================================

    if (!domicilios || domicilios.length === 0) {

        contenedor.html(`
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-5">

                        <div class="text-secondary mb-3">
                            <i class="fa-solid fa-motorcycle fs-1"></i>
                        </div>

                        <h6 class="fw-bold mb-1">
                            No hay domicilios pendientes
                        </h6>

                        <small class="text-muted">
                            No se encontraron domicilios para la fecha seleccionada.
                        </small>

                    </div>
                </div>
            </div>
        `);

        $("#totalPendientes").text(0);
        $("#totalEnCamino").text(0);
        $("#totalEntregados").text(0);

        return;
    }

    // =====================================================
    // CONTADORES
    // =====================================================

    let pendientes = 0;
    let enCamino = 0;
    let entregados = 0;

    // =====================================================
    // RECORRER DOMICILIOS
    // =====================================================

    domicilios.forEach((domicilio) => {

        const estado = domicilio.estado || "P";

        // =================================================
        // CONFIGURACIÓN DEL ESTADO
        // =================================================

        let estadoTexto = "PENDIENTE";
        let estadoColor = "warning";
        let estadoIcono = "fa-solid fa-clock-rotate-left text-white";

        switch (estado) {

            case "P":

                estadoTexto = "PENDIENTE";
                estadoColor = "warning";
                estadoIcono = "fa-solid fa-clock-rotate-left text-white";

                pendientes++;

                break;

            case "D":

                estadoTexto = "EN CAMINO";
                estadoColor = "primary";
                estadoIcono = "fa-solid fa-motorcycle text-white";

                enCamino++;

                break;

            case "E":

                estadoTexto = "ENTREGADO";
                estadoColor = "success";
                estadoIcono = "fa-solid fa-house-circle-check text-white";

                entregados++;

                break;

            default:

                estadoTexto = "PENDIENTE";
                estadoColor = "warning";
                estadoIcono = "fa-solid fa-clock-rotate-left text-white";

                pendientes++;

                break;
        }

        // =================================================
        // DATOS
        // =================================================

        const cliente = domicilio.cliente || "Cliente sin nombre";
        const nit = domicilio.nit
            ? `CC ${domicilio.nit}`
            : "";
        const celular = domicilio.celular || "";
        const direccion = domicilio.direccion || "Sin dirección";
        const municipio = domicilio.municipio || "";
        const departamento = domicilio.departamento || "";
        const mensajero = domicilio.mensajero || "Sin asignar";
        const notas = domicilio.notas || "";
        const tipo = domicilio.tipo || "";
        const numero = domicilio.numero || "";
        const valor = Number(
            domicilio.valor_total || 0
        ).toLocaleString(
            "es-CO",
            {
                style: "currency",
                currency: "COP",
                maximumFractionDigits: 0
            }
        );

        // =================================================
        // ACCIONES SEGÚN ESTADO
        // =================================================

        let acciones = "";

        // -------------------------------------------------
        // PENDIENTE
        // -------------------------------------------------

        if (estado === "P") {

            acciones = `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-fill"
                    onclick="ImprimirDocumento(
                        5,
                        ${domicilio.id_factura},
                        'Impresión de documento',
                        false
                    )">
                    <i class="fa-solid fa-print me-1"></i>
                    Factura
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary flex-fill"
                    onclick="asignarMensajero(
                        '${domicilio.id_factura}',
                        '${domicilio.tipo}',
                        '${domicilio.numero}'
                    )">
                    <i class="fa-solid fa-motorcycle me-1"></i>
                    Asignar Mensajero
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary flex-fill"
                    onclick="verDetalleDomicilio('${domicilio.id_factura}')">
                    <i class="fa-solid fa-list"></i>
                    Detalles
                </button>
            `;

        }

        // -------------------------------------------------
        // EN CAMINO / DESPACHADO
        // -------------------------------------------------

        else if (estado === "D") {

            acciones = `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-fill"
                    onclick="ImprimirDocumento(
                        5,
                        ${domicilio.id_factura},
                        'Impresión de documento',
                        false
                    )">
                    <i class="fa-solid fa-print me-1"></i>
                    Factura
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger flex-fill"
                    onclick="liberarDomicilio('${domicilio.id}')">
                    <i class="fa-solid fa-unlock me-1"></i>
                    Liberar
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-success flex-fill"
                    onclick="entregarDomicilio('${domicilio.id}')">
                    <i class="fa-solid fa-house-circle-check me-1"></i>
                    Entregar
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary flex-fill"
                    onclick="verDetalleDomicilio('${domicilio.id_factura}')">
                    <i class="fa-solid fa-list"></i>
                    Detalles
                </button>
            `;

        }

        // -------------------------------------------------
        // ENTREGADO
        // -------------------------------------------------

        else if (estado === "E") {

            acciones = `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-fill"
                    onclick="ImprimirDocumento(
                        5,
                        ${domicilio.id_factura},
                        'Impresión de documento',
                        false
                    )">
                    <i class="fa-solid fa-print me-1"></i>
                    Factura
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary flex-fill"
                    onclick="verDetalleDomicilio('${domicilio.id_factura}')">
                    <i class="fa-solid fa-list"></i>
                    Detalles
                </button>
            `;

        }

        // -------------------------------------------------
        // OTRO ESTADO
        // -------------------------------------------------

        else {

            acciones = `
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary flex-fill"
                    onclick="ImprimirDocumento(
                        5,
                        ${domicilio.id_factura},
                        'Impresión de documento',
                        false
                    )">
                    <i class="fa-solid fa-print me-1"></i>
                    Factura
                </button>
            `;
        }

        // =================================================
        // TARJETA
        // =================================================

        const textoBusqueda = `
                                ${cliente}
                                ${nit}
                                ${celular}
                                ${direccion}
                                ${municipio}
                                ${departamento}
                                ${mensajero}
                                ${notas}
                                ${tipo}
                                ${numero}
                                ${domicilio.estado || ""}
                                ${estadoTexto}
                                ${domicilio.valor_total || ""}
                            `.toLowerCase();

        const tarjeta = `   
             <div class="col-md-6 card-domicilio"  data-busqueda="${textoBusqueda.replace(/"/g, '&quot;')}">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <!-- CABECERA -->
                    <div class="card-body p-2 pb-1">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <span class="badge bg-${estadoColor}">
                                    <i class="${estadoIcono} me-1"></i>
                                    ${estadoTexto}
                                </span>
                            </div>
                        </div>
                        <!-- CLIENTE -->
                        <div class="fw-bold text-secondary">
                            ${cliente}
                        </div>
                        <!-- CONTACTO -->
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 11px;" >
                            ${nit ? `
                                    <span>
                                        <i class="fa-solid fa-id-card me-1"></i>
                                        ${nit}
                                    </span>
                                    ` : ""}
                            ${celular ? `
                                    <span>
                                        <i class="fa-solid fa-phone me-1"></i>
                                        ${celular}
                                    </span>
                                    ` : ""}
                        </div>
                        <!-- INFORMACIÓN -->
                        <div class="mt-2">
                            <!-- FACTURA -->
                            <div class="d-flex align-items-start mb-1">
                                <div class="text-muted me-2" style="width: 75px; font-size: 11px;" >
                                    Factura:
                                </div>
                                <div class="fw-semibold text-secondary" style="font-size: 11px;" >
                                    ${tipo} | ${numero}
                                </div>
                            </div>
                            <!-- DIRECCIÓN -->
                            <div class="d-flex align-items-start mb-1">
                                <div class="text-muted me-2" style="width: 75px; font-size: 11px;">
                                    Entregar en:
                                </div>
                                <div class="fw-semibold text-secondary" style="font-size: 11px;" >
                                    ${direccion}
                                    ${municipio ? ` | ${municipio}` : ""}
                                    ${departamento ? ` | ${departamento}` : ""}
                                </div>
                            </div>
                            <!-- MENSAJERO -->
                            <div class="d-flex align-items-start mb-1">
                                <div class="text-muted me-2" style="width: 75px; font-size: 11px;">
                                    Mensajero:
                                </div>
                                <div class="fw-semibold text-secondary" style="font-size: 11px;" >
                                    <i class="fa-solid fa-motorcycle text-primary me-1"></i>
                                    ${mensajero}
                                </div>
                            </div>
                            <!-- TOTAL -->
                            <div class="d-flex align-items-start">
                                <div class="text-muted me-2" style="width: 75px; font-size: 11px;" >
                                    Total:
                                </div>
                                <div class="fw-bold text-success" style="font-size: 11px;">
                                    ${valor}
                                </div>
                            </div>
                        </div>
                        <!-- NOTAS -->
                        ${notas ? `
                                <div class="alert alert-warning py-1 px-2 mt-2 mb-0" style="font-size: 10px;">
                                    <i class="fa-solid fa-note-sticky me-1"></i>
                                    ${notas}
                                </div>
                                ` : ""
            }
                    </div>
                    <!-- ACCIONES -->
                    <div class="border-top px-2 py-1">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                            ${acciones}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Agregar tarjeta
        contenedor.append(tarjeta);

    });

    // =====================================================
    // ACTUALIZAR CONTADORES
    // =====================================================

    $("#totalPendientes").text(pendientes);
    $("#totalEnCamino").text(enCamino);
    $("#totalEntregados").text(entregados);

};

const verDetalleDomicilio = async (id_factura) => {
    const data = {
        link: "../models/domicilios.php",
        op: "detalle_domicilio",
        id_factura: id_factura
    };
    const resp = await enviarPeticion(data);
    renderizarTimeline(resp[0]);
    $("#mdl-detalles").modal("show");
};

const renderizarTimeline = (data) => {
    if (!data) {
        return;
    }

    // =====================================================
    // INFORMACIÓN DE FACTURA
    // =====================================================

    $("#timeline-tipo").text(
        data.tipo || "-"
    );

    $("#timeline-numero").text(
        data.numero || "-"
    );

    $("#timeline-fecha-factura").text(
        data.fecha_factura || "-"
    );


    // =====================================================
    // TIEMPO FACTURA -> DESPACHO
    // =====================================================

    $("#timeline-tiempo-factura-despacho").text(
        data.duracion_factura_despacho ||
        formatearMinutos(data.tiempo_factura_despacho)
    );


    // =====================================================
    // INFORMACIÓN DEL DESPACHO
    // =====================================================

    if (data.inicio) {

        $("#timeline-inicio").text(
            data.inicio
        );

        $("#timeline-mensajero").text(
            data.mensajero || "Sin asignar"
        );

        $("#timeline-usuario").text(
            data.usuario_despacho_nombre || "-"
        );

        $("#timeline-flete").text(
            Number(data.flete || 0).toLocaleString(
                "es-CO",
                {
                    style: "currency",
                    currency: "COP",
                    maximumFractionDigits: 0
                }
            )
        );

        // Mostrar duración despacho -> entrega
        $("#timeline-tiempo-despacho-entrega").text(
            data.duracion_despacho_entrega ||
            formatearMinutos(data.tiempo_despacho_entrega)
        );

    } else {

        $("#timeline-inicio").text(
            "Pendiente de despacho"
        );

        $("#timeline-mensajero").text(
            "Sin asignar"
        );

        $("#timeline-usuario").text(
            "-"
        );

        $("#timeline-flete").text(
            Number(data.flete || 0).toLocaleString(
                "es-CO",
                {
                    style: "currency",
                    currency: "COP",
                    maximumFractionDigits: 0
                }
            )
        );

        $("#timeline-tiempo-despacho-entrega").text(
            "-"
        );
    }


    // =====================================================
    // ENTREGA
    // =====================================================

    if (data.fin) {

        // Fecha de entrega
        $("#timeline-fin").text(
            data.fin
        );

        // Icono
        $("#timeline-icon-entrega")
            .removeClass("bg-warning bg-secondary")
            .addClass("bg-success");

        // Tiempo despacho -> entrega
        $("#timeline-tiempo").text(
            data.duracion_despacho_entrega ||
            formatearMinutos(data.tiempo_despacho_entrega)
        );

        // Tiempo total
        $("#timeline-tiempo-total").text(
            data.duracion_total ||
            formatearMinutos(data.tiempo_total)
        );

        $("#timeline-tiempo-container")
            .removeClass("alert-warning")
            .addClass("alert-success");

    } else {

        // Todavía no entregado
        $("#timeline-fin").text(
            "Pendiente de entrega"
        );

        $("#timeline-icon-entrega")
            .removeClass("bg-success bg-secondary")
            .addClass("bg-warning");

        $("#timeline-tiempo").text(
            "En curso"
        );

        $("#timeline-tiempo-total").text(
            data.duracion_total ||
            formatearMinutos(data.tiempo_total)
        );

        $("#timeline-tiempo-container")
            .removeClass("alert-success")
            .addClass("alert-warning");
    }


    // =====================================================
    // TIEMPO TOTAL
    // =====================================================

    $("#timeline-tiempo-total").text(
        data.duracion_total ||
        formatearMinutos(data.tiempo_total)
    );
};

const formatearMinutos = (minutos) => {

    if (
        minutos === null ||
        minutos === undefined ||
        minutos === ""
    ) {
        return "-";
    }

    minutos = parseInt(minutos);

    if (isNaN(minutos)) {
        return "-";
    }

    if (minutos < 60) {
        return `${minutos} min`;
    }

    const horas = Math.floor(minutos / 60);
    const minutosRestantes = minutos % 60;

    if (minutosRestantes === 0) {
        return `${horas} h`;
    }

    return `${horas} h ${minutosRestantes} min`;
};

const listarTrasportadores = async () => {
    try {
        const data = {
            link: "../models/Despachos.php",
            op: "s_transportadores",
            tipo: 'M'
        }
        const resp = await enviarPeticion(data);
        if (resp.data.length > 0) {
            resp.data.forEach((d) => {
                $('#mdl-mensajero').append(`<option value="${d.id}">${d.nombres} - ${d.razon_social}</option>`);
            })
        } else {
            $('#mdl-mensajero').html('');
        }
    } catch (e) {
        console.log(e);
    }
};

const liberarDomicilio = async (id) => {
    try {
        const data = {
            link: "../models/Despachos.php",
            op: "d_despacho_det",
            id: id
        }
        Swal.fire({
            title: "Liberar domicilio",
            text: "Esta seguro de liberar este domicilio, esta operación no se puede reversar?",
            icon: "question",
            html: '',
            showCancelButton: true,
            confirmButtonText: "Liberar!",
            cancelButtonText: "Cancelar",
            showCancelButton: true,
        }).then(async (result) => {
            if (result.isConfirmed) {
                const resp = await enviarPeticion(data);
                console.log(resp)
                if (resp.ok) {
                    Swal.fire("Excelente", resp.mensaje, "success");
                    consultarDomicilios();
                } else {
                    Swal.fire("Error", resp.mensaje, "error");
                }
            }
        });
    } catch (e) {
        console.log(e);
    }
};

const entregarDomicilio = async (id) => {
    try {
        const data = {
            link: "../models/Despachos.php",
            op: "u_despacho_det",
            id: id
        }
        Swal.fire({
            title: "Entregar domicilio",
            text: "Esta seguro de entregar este domicilio, esta operación no se puede reversar?",
            icon: "question",
            html: '',
            showCancelButton: true,
            confirmButtonText: "Guardar!",
            cancelButtonText: "Cancelar",
            closeOnConfirm: false,
            closeOnCancel: false
        }).then(async (result) => {
            if (result.isConfirmed) {
                const resp = await enviarPeticion(data);
                if (resp.ok) {
                    Swal.fire("Excelente", resp.mensaje, "success");
                    consultarDomicilios();
                } else {
                    Swal.fire("Error", resp.mensaje, "error");
                }

            }
        });
    } catch (e) {
        console.log(e);
    }
};

const asignarMensajero = (idFactura, tipo, numero) => {
    $("#mdl-tipo-factura").val(tipo);
    $("#mdl-numero-factura").val(numero);
    $("#mdl-id-factura").val(idFactura);
    listarTrasportadores();
    $("#mdl-asignar-mensajero").modal("show");
};

const consultarHisDomicilios = async () => {

    showLoadingWithTetrisLogo(
        'Consultando domicilios',
        'ISA Software'
    );

    try {

        const fecha_ini_his = $("#fecha_ini_his").val();
        const fecha_fin_his = $("#fecha_fin_his").val();

        const resp = await enviarPeticion({
            link: '../models/domicilios.php',
            op: 'historial_domicilios',
            fecha_ini: fecha_ini_his,
            fecha_fin: fecha_fin_his
        });

        console.log(resp);

        // Validar respuesta
        if (!Array.isArray(resp)) {
            console.error('La respuesta no es un array:', resp);
            return;
        }

        const datos = resp.map(item => ({
            ...item,
            tiempo_total: formatearMinutos(item.tiempo_total)
        }));
        $("#his_result").html(`<table id="tabla_his_domicilios" class="table table-sm table-borderless table-hover table-striped" width="100%">
                          <thead class="table-primary"></thead> 
                      </table>`)
        // Crear DataTable
        createDataTable(
            'tabla_his_domicilios',
            datos,
            // dataNames
            [
                'tipo',
                'numero',
                'fecha_factura',
                'estado',
                'inicio',
                'fin',
                'flete',
                'mensajero',
                'tiempo_total'
            ],

            // columnNames
            [
                'Tipo',
                'Número',
                'Fecha factura',
                'Estado',
                'Inicio despacho',
                'Entrega',
                'Flete',
                'Mensajero',
                'Tiempo total'
            ],

            null, // editCallback
            null, // deleteCallback
            false, // otherButton
            '',
            '',
            true, // btnExport

            // currencyColumns
            ['flete']
        );
        dissminSwal();
    } catch (error) {
        dissminSwal();
        swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al consultar el historial de domicilios.',
        });
        console.error('Error consultando historial de domicilios:', error);

    }
};

$(async function () {
    fechaActual([['fecha_ini_his', 'fecha_fin_his'], 'fecha_actual']);
    await consultarDomicilios();

    $("#fecha_actual").change(async function () {
        await consultarDomicilios();
    });
    dissminSwal();
    $("#btn-guardar-mensajero").on("click", async function () {

        const $btn = $(this);

        // Obtener valores
        const idFactura = $("#mdl-id-factura").val();
        const tipoFactura = $("#mdl-tipo-factura").val();
        const numeroFactura = $("#mdl-numero-factura").val();
        const idMensajero = $("#mdl-mensajero").val();
        const vlrDomicilio = $("#mdl-valor-domicilio").val();
        const notas = '';

        // Validar mensajero
        if (!idMensajero) {
            Swal.fire({
                icon: "warning",
                title: "Seleccione un mensajero",
                showConfirmButton: false,
                timer: 1500
            });
            return;
        }

        // Validar valor domicilio
        if (!vlrDomicilio || Number(vlrDomicilio) < 0) {
            Swal.fire({
                icon: "warning",
                title: "Ingrese el valor del domicilio",
                showConfirmButton: false,
                timer: 1500
            });

            $("#mdl-valor-domicilio").focus();
            return;
        }

        // Evitar doble clic
        if ($btn.prop("disabled")) {
            return;
        }

        try {

            $btn.prop("disabled", true);

            $btn.html(`
            <span class="spinner-border spinner-border-sm me-1"></span>
            Guardando...
        `);

            const datos = [{
                tipo: tipoFactura,
                numero: numeroFactura,
                cajas: 0,
                flete: unformatNum(vlrDomicilio),
                guia: 0,
                nota: notas
            }];

            const data = {
                link: "../models/Despachos.php",
                op: "g_despacho",
                datos: JSON.stringify(datos),
                transportador: idMensajero
            };

            const resp = await enviarPeticion(data);

            if (resp?.ok === true) {

                await Swal.fire({
                    icon: "success",
                    title: "¡Asignación exitosa!",
                    text: resp.mensaje || "El mensajero fue asignado correctamente.",
                    showConfirmButton: false,
                    timer: 1500
                });

                $("#mdl-asignar-mensajero").modal("hide");

                $("#mdl-mensajero").val("").trigger("change");
                $("#mdl-valor-domicilio").val("");
                await consultarDomicilios();

            } else {

                Swal.fire({
                    icon: "error",
                    title: "No se pudo asignar el mensajero",
                    text: resp?.mensaje || "Ocurrió un error al guardar la información.",
                    confirmButtonText: "Aceptar"
                });
            }

        } catch (error) {

            console.error("Error al asignar mensajero:", error);

            Swal.fire({
                icon: "error",
                title: "Error inesperado",
                text: "No fue posible completar la asignación.",
                confirmButtonText: "Aceptar"
            });

        } finally {

            $btn.prop("disabled", false);

            $btn.html(`
            <i class="fa-solid fa-check me-1"></i>
            Asignar
        `);
        }
    });
    $("#filtroDomicilios").on("input", function () {
        const texto = $(this).val().toLowerCase().trim();
        let encontrados = 0;
        $(".card-domicilio").each(function () {
            const datos = $(this)
                .attr("data-busqueda")
                .toLowerCase();
            if (datos.includes(texto)) {
                $(this).show();
                encontrados++;
            } else {
                $(this).hide();
            }
        });

        // Mostrar mensaje cuando no hay resultados
        $("#sinResultadosDomicilios").remove();

        if (encontrados === 0 && texto !== "") {

            $("#listaDomicilios").append(`
            <div id="sinResultadosDomicilios" class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-4">

                        <i class="fa-solid fa-magnifying-glass text-secondary fs-2 mb-2"></i>

                        <h6 class="fw-bold mb-1">
                            No se encontraron resultados
                        </h6>

                        <small class="text-muted">
                            No hay domicilios que coincidan con
                            "<strong>${texto}</strong>".
                        </small>

                    </div>
                </div>
            </div>
        `);
        }
    });
    $("#btn-limpiar-filtro").on("click", function () {
        $("#filtroDomicilios").val("").trigger("input");
        $("#filtroDomicilios").focus();
    });

    $("#btn-consultar-fecha").on("click", async function () {
        await consultarDomicilios();
    });
    $("#btn_consultar_his").on("click", async function () {
        await consultarHisDomicilios();
    });

});