let movimientos = [];
const listarCuentas = async () => {
    try {
        const data = {
            link: '../models/cuentasContables.php',
            op: 'listar-todos'
        }
        const resp = await enviarPeticion(data);
        if (resp.data.length == 0) {
            Swal.fire({
                title: 'Imposible continuar!',
                text: 'No hay un plan de cuentas cargado, por favor validar.',
                icon: 'info',
                showCloseButton: false,
                showConfirmButton: false,
                allowOutsideClick: false
            })
            return null;
        }
        return resp.data;
    } catch (e) {
        console.error(e)
        $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
        return null;
    }
}
function limpiarMovimiento() {
    $("#cuenta").val('').trigger("change");;
    $("#txtBusquedaTercero").val('');
    $("#centro_costo").val('');
    $("#detalle").val('');
    $("#debito,#credito").val('0').prop("disabled", false);
    $("#cuenta").focus();
    $("#id_tercero").val(0);
    $("#nit").val(0);
    $("#totalCredito").text(formatear(0));
    $("#totalDebito").text(formatear(0));
    $("#diferencia").text(formatear(0));
    $("#tblMovimientos tbody").html('');
    $("#tfootDebito").text(formatear(0));
    $("#tfootCredito").text(formatear(0));
    $("#observacion").val('');

}
function formatear(valor) {
    return parseFloat(valor || 0)
        .toLocaleString('es-CO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
}
function actualizarTotales() {
    let totalDebito = 0;
    let totalCredito = 0;
    const nuevomov = movimientos.filter(i => i.eliminar == false)
    nuevomov.forEach(item => {
        console.error({ item })
        totalDebito += item.debito;
        totalCredito += item.credito;
    });
    let diferencia = totalDebito - totalCredito;
    $("#totalDebito").text(formatear(totalDebito));
    $("#totalCredito").text(formatear(totalCredito));
    $("#diferencia").text(formatear(Math.abs(diferencia)));
    $("#tfootDebito").text(formatear(totalDebito));
    $("#tfootCredito").text(formatear(totalCredito));
    if (Math.abs(diferencia) < 0.01) {
        $("#badgeCuadrado")
            .removeClass("d-none");
        $("#badgeDescuadrado")
            .addClass("d-none");
        $("#btnGuardar")
            .prop("disabled", false);
    } else {
        $("#badgeCuadrado")
            .addClass("d-none");
        $("#badgeDescuadrado")
            .removeClass("d-none");
        $("#btnGuardar")
            .prop("disabled", true);
    }
}
function renderTabla() {
    let html = '';
    movimientos.forEach((item, index) => {
        console.log({ elim: item.eliminar })
        if (item?.eliminar === false || item?.eliminar == undefined) {

            html += `
                        <tr data-id=${item.id}>
                            <td>${item.cuenta}</td>
                            <td>${item.nombreCuenta}</td>
                            <td>${item.tercero}</td>
                            <td>${item.nombreCentroCosto}</td>
                            <td>${item.detalle}</td>
                            <td class="text-end">
                                ${formatear(item.debito)}
                            </td>
                            <td class="text-end">
                                ${formatear(item.credito)}
                            </td>
                            <td class="text-center">
                                <button
                                    class="btn btn-sm btn-danger btnEliminar"
                                    data-index="${index}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
            `;
        }

    });
    console.log({ movimientos })

    $("#tblMovimientos tbody").html(html);
    actualizarTotales();
}
const listarTipoDocumentos = async (sw) => {
    let bodega = $("#id_bodega_session").val();
    try {
        const data = {
            link: '../models/tiposDocumentos.php',
            sw: sw,
            op: 'listar_sw',
            bodega: bodega
        }
        const resp = await enviarPeticion(data);
        if (resp.data.lenght == 0) {
            return;
        }

        resp.data.forEach((d) => {
            $("#tipo").append(
                `<option value="${d.id}">${d.tipo} - ${d.descripcion}</option>`
            );
        });

    } catch (e) {
        console.error(e)
        $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    }
}
const llenarSelectCuentas = (cuentas) => {
    const $selects = $('#cuenta');
    $selects.each(function () {
        const fragment = document.createDocumentFragment();

        const emptyOption = new Option('', '', false, false);
        fragment.appendChild(emptyOption);

        cuentas.forEach(item => {
            const option = new Option(item.cuenta + ' - ' + item.descripcion, item.cuenta, false, false);
            fragment.appendChild(option);
        });

        this.appendChild(fragment);
    });
};
const listarCentros = async () => {
    try {
        const data = {
            link: '../models/centros.php',
            op: 'listar'
        }
        const resp = await enviarPeticion(data);
        if (resp.data.length == 0) {
            return;
        }
        resp.data.forEach((d) => {
            $("#centro_costo").append(
                `<option value="${d.centro}">${d.centro} - ${d.descripcion}</option>`
            );
        });
    } catch (e) {
        console.error(e)
        $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
        return null;
    }

}
function controlarDebitoCredito() {
    $("#debito").on("input", function () {
        let valor = parseFloat($(this).val()) || 0;
        if (valor > 0) {
            $("#credito")
                .val("0")
                .prop("disabled", true);
        } else {
            $("#credito")
                .prop("disabled", false);
        }
    });
    $("#credito").on("input", function () {
        let valor = parseFloat($(this).val()) || 0;
        if (valor > 0) {
            $("#debito")
                .val("0")
                .prop("disabled", true);
        } else {
            $("#debito")
                .prop("disabled", false);
        }
    });
}


const consultarComprobante = async () => {
    try {
        const data = {
            link: '../models/comprobanteContable.php',
            op: 'listar-movimientos',
            fecha_inicial: $("#fecha_inicial").val(),
            fecha_final: $("#fecha_final").val()
        }
        const resp = await enviarPeticion(data);
        console.log({ resp })
        if (resp.length == 0) {
            dissminSwal();
            Swal.fire("Información", "No se encontraron movimientos", "info");
            return;
        }

        table = `<table class="table table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th>Tipo</th>
                            <th>Numero</th>
                            <th>Fecha</th>
                            <th>Tercero</th>
                            <th>Debito</th>
                            <th>Credito</th>
                            <th>Usuario</th>
                            <th>Editar</th>
                        </tr>
                    </thead>
                    <tbody>
        `

        resp.forEach((d) => {

            table += `<tr data-element='${JSON.stringify(d.movimientos)}'>
                        <td>${d.tipo}</td>
                        <td>${d.numero}</td>
                        <td>${d.fecha}</td>
                        <td>${d.nit}</td>
                        <td>${d.debito}</td>
                        <td>${d.credito}</td>
                        <td><i class="fa fa-user mr-2"></i>${d.usuario}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary editarComprobante" 
                            data-id="${d.id}" 
                            data-tipo="${d.id_tipo_documento}"
                            data-observacion="${d.observacion}"
                            
                        ><i class="fa fa-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger imprimirComprobante" 
                            data-id="${d.id}"
                        ><i class="fa fa-print"></i></button>
                        </td>
                    </tr>`

        });
        table += `
                </tbody>
            </table>`
        $("#lista-comprobantes").html(table);

        $(".editarComprobante").on('click', function () {
            movimientos = [];
            limpiarMovimiento();

            showLoadingWithTetrisLogo('Cargando Pedido...', 'ISA Software')

            const data = JSON.parse($(this).parent().parent().attr("data-element"))
            const id_transaccion = $(this).data("id")
            const id_tipo_documento = $(this).data("tipo")
            const observacion = $(this).data("observacion")
            console.log({ data, id_transaccion, id_tipo_documento, observacion })
            $("#id_transaccion").val(id_transaccion)
            $("#tipo").val(id_tipo_documento).attr("disabled", true)
            $("#observacion").val(observacion)
            // activo el tab
            $('#tabs button[data-bs-target="#navs-crear"]').tab('show')
            $('#tabs button[data-bs-target="#navs-crear"]').attr('disabled', false)
            let diferencia = 0;
            let totalDebito = 0
            let totalCredito = 0
            data.forEach(d => {
                movimientos.push({
                    cuenta: d.cuenta,
                    nombreCuenta: d.centroCosto + ' ' + d.nombreCuenta,
                    tercero: d.nit == '0' ? '' : d.nit + ' ' + d.tercero,
                    centroCosto: d.centroCosto,
                    nombreCentroCosto: d.centroCosto == '' ? '' : d.centroCosto + ' ' + d.nombreCentroCosto,
                    detalle: d.detalle,
                    debito: parseFloat(d.debito),
                    credito: parseFloat(d.credito),
                    id_tercero: d.id_tercero,
                    nit: d.nit,
                    id: d.id,
                    eliminar: false
                });
                totalDebito += parseFloat(d.debito)
                totalCredito += parseFloat(d.credito)
            })

            console.log({ totalDebito, totalCredito, diferencia });
            diferencia = totalDebito - totalCredito;

            if (Math.abs(diferencia) < 0.01) {
                $("#badgeCuadrado")
                    .removeClass("d-none");
                $("#badgeDescuadrado")
                    .addClass("d-none");
                $("#btnGuardar")
                    .prop("disabled", false);
            } else {
                $("#badgeCuadrado")
                    .addClass("d-none");
                $("#badgeDescuadrado")
                    .removeClass("d-none");
                $("#btnGuardar")
                    .prop("disabled", true);
            }

            renderTabla();

            dissminSwal();
        });

        $(".imprimirComprobante").on('click', function () {
            const id = $(this).data("id")
            ImprimirDocumento(0, id, 'Impresión de documento', true);
        })

    } catch (e) {
        console.error(e)
        $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
        return null;
    }

}

$(async function () {
    fechaActual([["fecha_inicial", "fecha_final"], "fecha"]);
    actualizarTotales();
    await listarTipoDocumentos(0);
    await listarCentros();
    controlarDebitoCredito();
    try {
        // Ejecutar ambas consultas en paralelo
        const cuentas = await listarCuentas();

        if (!cuentas) {
            dissminSwal();
            return;
        }

        // Llenar el select con todas las cuentas de una vez
        llenarSelectCuentas(cuentas);

        // Inicializar Select2 después de agregar todas las opciones
        $('#cuenta').select2({
            theme: 'bootstrap-5',
            allowClear: true,
            language: {
                noResults: function () {
                    return "No se encontraron resultados";
                }
            }
        });
    } catch (e) {
        console.error(e);
        dissminSwal();
        Swal.fire("Error", "Error al cargar los datos", "error");
    }

    $("#btnConsultar").on('click', function () {
        consultarComprobante();
    });

    $("#btnAgregarMovimiento").click(function () {
        let cuenta = $("#cuenta").val().trim();
        let nombreCuenta = $("#cuenta option:selected").text() || '';
        let tercero = $("#txtBusquedaTercero").val().trim();
        let centroCosto = $("#centro_costo").val();
        let nombreCentroCosto = $("#centro_costo").val() ? $("#centro_costo option:selected").text() : '';
        let detalle = $("#detalle").val().trim();
        let id_tercero = $("#id_tercero").val();
        let nit = $("#nit").val();

        let debito = parseFloat(
            ($("#debito").val() || '0')
                .replace(/,/g, '')
        );

        let credito = parseFloat(
            ($("#credito").val() || '0')
                .replace(/,/g, '')
        );

        if (cuenta === '') {
            Swal.fire(
                'Atención',
                'Debe seleccionar una cuenta contable',
                'warning'
            );
            return;
        }

        if (debito <= 0 && credito <= 0) {
            Swal.fire(
                'Atención',
                'Debe registrar un débito o un crédito',
                'warning'
            );
            return;
        }

        if (debito > 0 && credito > 0) {
            Swal.fire(
                'Atención',
                'No puede registrar débito y crédito en la misma línea',
                'warning'
            );
            return;
        }

        movimientos.push({
            cuenta,
            nombreCuenta: nombreCuenta.replace(/-/g, ''),
            tercero: tercero.replace(/-/g, ''),
            centroCosto,
            nombreCentroCosto: nombreCentroCosto.replace(/-/g, ''),
            detalle,
            debito,
            credito,
            id_tercero,
            nit,
            eliminar: false,
            id: 0
        });
        limpiarMovimiento();

        renderTabla();

    });
    $(document).on("click", ".btnEliminar", function () {
        // valido si hay id_transacion 
        let index = $(this).data("index");
        if ($("#id_transaccion").val() > 0) {
            const id = $(this).data("id");
            // consigo el indice en movimiento
            movimientos.forEach(function (mov, i) {
                if (mov.id === id) {
                    index = i;
                    return;
                }
            });
            if (movimientos[index].id == 0) {
                movimientos.splice(index, 1);
            } else {
                movimientos[index].eliminar = true;
            }


        } else {
            movimientos.splice(index, 1);
        }

        renderTabla();

    });

    $("#btnLimpiar").on('click', function () {
        limpiarMovimiento();
        $("#lista-comprobantes").html('');
        $("#id_transaccion").val(0)
        $("#tipo").val('').attr("disabled", false)
        movimientos = [];

    });

    $("#btnGuardar").click(function () {
        if (movimientos.length === 0) {
            Swal.fire(
                'Atención',
                'Debe agregar movimientos',
                'warning'
            );
            return;
        }
        /*
        let totalDebito = movimientos.reduce(
            (a, b) => a + b.debito,
            0
        );

        let totalCredito = movimientos.reduce(
            (a, b) => a + b.credito,
            0
        );*/
        let totalDebito = movimientos.reduce(
            (total, mov) => mov.eliminar === false
                ? total + Number(mov.debito)
                : total,
            0
        );

        let totalCredito = movimientos.reduce(
            (total, mov) => mov.eliminar === false
                ? total + Number(mov.credito)
                : total,
            0
        );

        if (Math.abs(totalDebito - totalCredito) > 0.01) {
            Swal.fire(
                'Atención',
                'El comprobante no está cuadrado',
                'warning'
            );
            return;
        }

        let comprobante = {
            link: '../models/comprobanteContable.php',
            op: 'guardar_comprobante',
            id_tipo_documento: $("#tipo").val(),
            fecha: $("#fecha").val(),
            observacion: $("#observacion").val(),
            movimientos: JSON.stringify(movimientos),
            id_transaccion: $("#id_transaccion").val() !== '' ? $("#id_transaccion").val() : 0
        };


        Swal.fire({
            title: "Esta seguro de guardar el comprobante?",
            text: "Esta acción no puede reversarse!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si"
        }).then(async (result) => {
            if (result.isConfirmed) {
                enviarPeticion(comprobante).then(item => {
                    console.log({ item })
                    if (!item.ok) {
                        Swal.fire("Info", item.message, "error");
                    } else {
                        Swal.fire("Éxito", item.message, "success");
                        ImprimirDocumento(0, item.id_transaccion, 'Impresión de documento', true);
                        limpiarMovimiento();
                        $("#lista-comprobantes").html('');
                        $("#id_transaccion").val(0)
                        $("#tipo").val('').attr("disabled", false)
                        movimientos = [];

                    }
                }).catch(error => {
                    console.error({ error })
                })
            }
        })
    });

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
        }
    });
    $('#txtBusquedaTercero').on('input', function () {
        if (!this.value.trim()) {
            $("#id_tercero").val(0);
            $("#nit").val(0);
        }
    });
    /*   $("#btnConsultar").click(function () {
           let fecha_inicial = $("#fecha_inicial").val();
           let fecha_final = $("#fecha_final").val();
           const data = {
               link: '../models/comprobanteContable.php',
               op: 'consultar_comprobantes',
               fecha_inicial,
               fecha_final
           };
           enviarPeticion(data).then(resp => {
               if (!resp.ok) {
                   Swal.fire("Info", resp.message, "info");
               } else {
                   let html = '';
                   resp.data.forEach(item => {
                       html += `
                       <tr>
                           <td>${item.id}</td>
                           <td>${item.tipo}</td>
                           <td>${item.numero}</td>
                           <td>${item.fecha}</td>
                       </tr>
                       `;
                   });
                   $("#lista-comprobantes").html(html);
               }
           }).catch(error => {
               console.error({ error });
           });*/
    dissminSwal();
});