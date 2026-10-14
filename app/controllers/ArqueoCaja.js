const id_modulo = '43';

const validarAperturaCaja = async () => {
    try {

        const resp = await enviarPeticion({
            link: '../models/AperturaCierreCaja.php',
            op: 'info'
        });

        if (resp.id_apertura == 0) {

            let texto = "";

            if (permisos.abrirModuloApertura) {
                texto = `
          <i class="fas fa-cash-register fa-2x text-danger mb-3"></i>
          <h5 class="mb-3">No hay apertura de caja</h5>
          <p>
            Es necesario realizar la <strong>apertura de caja</strong>.
            Haz click 
            <a href="AperturaCierreCaja.php?redirect=1" class="fw-bold text-decoration-underline">
              aquí
            </a>
            para ir al módulo.
          </p>
        `;
            } else {
                texto = `
          <i class="fas fa-lock fa-2x text-danger mb-3"></i>
          <h5 class="mb-3">Caja no abierta</h5>
          <p>
            Es necesario realizar la <strong>apertura de caja</strong>.
            Solicita permisos para acceder al módulo de
            <strong>Apertura y Cierre de Caja</strong>.
          </p>
        `;
            }

            $("body").html(`
        <div class="container d-flex justify-content-center align-items-center vh-100">
          <div class="alert alert-danger text-center shadow-lg p-4" style="max-width:500px;">
            <h4 class="alert-heading">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Atención
            </h4>
            <hr>
            ${texto}
          </div>
        </div>
      `);

            return false;
        }

        $(".container-fluid").removeClass("d-none");
        return true;

    } catch (e) {
        console.error("Error validando apertura de caja:", e);
        return false;
    }
};
const bodegas = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            op: 'bodegas-usuarios'
        })
        if (resp.length == 0) {
            return;
        }
        p = '';
        resp.forEach(element => {
            p += `<option value="${element.id}">${element.descripcion}</option>`
        });
        $("#bodegas").html(p)
        $("#bodegas").val($("#bodega_session").val())

    } catch (e) {
        console.error(e)
    }

}

const datosEmpresa = async () => {

    try {
        const resp = await enviarPeticion({
            link: '../shared/servicios/servicios.php',
            op: 'datos_empresa'
        });

        if (resp.length == 0) {
            return;
        }

        const datos = resp[0];

        $("#nit").text(datos.nit)
        $("#razon_comercial").text(datos.razon_comercial)
        $("#direccion").text(datos.direccion)


    } catch (error) {
        console.error(error)
    }

}


function imprimirDiv(idDiv) {
    const contenido = document.getElementById(idDiv).innerHTML;
    const ventanaImpresion = window.open('', '', 'height=600,width=800');

    ventanaImpresion.document.write(`
        <html>
          <head>
            <title>Imprimir</title>
            <style>
              table { border-collapse: collapse; width: 100%; }
              th, td { border: 1px solid black; padding: 8px; text-align: left; }
            </style>
          </head>
          <body>
            ${contenido}
          </body>
        </html>
      `);

    ventanaImpresion.document.close();
    ventanaImpresion.focus();

    // Espera a que cargue el contenido antes de imprimir
    ventanaImpresion.onload = function () {
        ventanaImpresion.print();
        ventanaImpresion.close();
    };
}

const consultarDatos = async () => {

    try {
        showLoadingWithTetrisLogo('Consultando registro', 'ISA Software')
        const resp = await enviarPeticion({
            link: '../models/ArqueoCaja.php',
            op: 'consultar',
            fecha_inicial: $("#fecha_inicial").val(),
            fecha_final: $("#fecha_final").val(),
            bodega: $("#bodegas").val(),
            usuario_caja: $("#usuario_caja").val()
        });

        dissminSwal()
        if (resp.documentos.length == 0) {
            Swal.fire("Ups", "No se encontraron resultados", "info")
            return;
        }

        const documentos = resp.documentos;
        const pagos = resp.pagos;
        const egresos = resp.egresos[0].egresos;
        const otros_ingresos = resp.otros_ingresos[0].valor_ingresos

        const ventas_contado = documentos.reduce((total, item) => total + parseFloat((item['venta_contado'] || 0)), 0);
        const ventas_credito = documentos.reduce((total, item) => total + parseFloat((item['venta_credito'] || 0)), 0);
        const dev_contado = documentos.reduce((total, item) => total + parseFloat((item['dev_contado'] || 0)), 0);
        const dev_credito = documentos.reduce((total, item) => total + parseFloat((item['dev_credito'] || 0)), 0);

        $("#aq_contado").text(formatNum(ventas_contado, '$'));
        $("#aq_credito").text(formatNum(ventas_credito, '$'));
        $("#aq_dv_contado").text(formatNum(dev_contado, '$'));
        $("#aq_dv_credito").text(formatNum(dev_credito, '$'));
        $("#aq_egresos").text(formatNum(egresos, '$'));
        $("#aq_otros_ingresos").text(formatNum(otros_ingresos))

        $("#doc_inicial").text(documentos[0].nim_max_ven);
        $("#doc_final").text(documentos[0].num_max_ven);

        $("#fecha_inicial_result").text($("#fecha_inicial").val());
        $("#fecha_final_result").text($("#fecha_final").val());

        $("#fecha").text(documentos[0].fecha)


        let tb = `
            <table class="table table-sm">
              <thead>
                <tr>
                   <th>Medio</th>
                   <th>Valor</th>
                </tr>
              </thead>
              <tbody>
        `
        if (pagos.length > 0) {

            pagos.forEach(item => {
                tb += `<tr>
                        <td>${item.medio}</td>
                        <td>${formatNum(item.valor, '$')}</td>
                    </tr>`;
            });

            tb += `</tbody>
            </table>`

        }

        $("#aq_pagos").html(tb);


    } catch (error) {
        console.log({ error })
        dissminSwal()
        Swal.fire("Error", "Se genero un error al consultar los datos!", "error")
    }

}

const usuariosCajas = async () => {

    try {
        const usuarios = await enviarPeticion({
            link: '../models/usuarios.php',
            op: 's_usuario_activo'
        });
         console.log({usuarios})

        let p = ''
        if ($("#rol").val() == 1) {
            p += `<option value="0">Todos</option>`
        }
        usuarios.data.forEach(usuario => {
            console.log({ usuario })
            p += `<option value="${usuario.id}">${usuario.usuario}  (${usuario.nombres} ${usuario.apellidos})</option>`
        })
        $("#usuario_caja").html(p)

    } catch (error) {
        console.error(error)
    }
}

$(async function () {
    permisos = await permisosUsuario(id_modulo)
    const validCaja = await validarAperturaCaja();

    if (!validCaja) {
        return;
    }
    const hoy = new Date().toISOString().split("T")[0]; // formato: 'YYYY-MM-DD'
    const fechaInicial = document.getElementById("fecha_inicial");
    fechaInicial.value = hoy;
    const fechaFinal = document.getElementById("fecha_final");
    fechaFinal.value = hoy;
    await datosEmpresa();
    await bodegas();
    await usuariosCajas();

    $("#generar_comprobante").click(function () {
        consultarDatos();
        $("#no_comprobante").hide()
    })
    $("#imprimirDiv").click(function () {
        $(".filtros").remove()
        $("#imprimirDiv").remove()
        setTimeout(function () {
            window.print()
            $(".btn-finales").html(`<button class="btn btn-outline-danger" id="finalziar">Finalizar</button>`)
            $("#finalizar").click(function () {
                window.location.reload(true);
            })
        }, 500)

    })

    dissminSwal();
})