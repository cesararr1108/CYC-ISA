const link_ = '../models/rptComisiones.php';


const cargarBodegas = async () => {
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'bodegas'
  })
  if (resp.length > 0) {
    let p = '<option value="0">Todas las bodegas</option>';
    resp.forEach(element => {
      p += `<option value="${element.id}" data-element='${JSON.stringify(element)}'>${element.almacen} - ${element.descripcion}</option>`
    });
    $("#id_bodegas").html(p);
  }
}

const listarUsuarios = async () => {
  try {
    const resp = await serviceListarUsuarios();
    $("#id_usuario_filtro").append(`<option value="0">Todos</option>`);
    resp.data.forEach(usuario => {
      $("#id_usuario_filtro").append(`<option value="${usuario.id}">${usuario.usuario} - ${usuario.nombre_completo} </option>`)
    });


  } catch (error) {
    console.error(error)
    Swal.fire("Error", "Error al obtener la información", "error")
  }
}

const exportarComisiones = async () => {
  try {
    const fechaInicio = $("#fe_fecha_inicial").val();
    const fechaFin = $("#fe_fecha_final").val();

    if (!fechaInicio || !fechaFin) {
      Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
      return;
    }

    const bodegas = $("#id_bodegas").val() || [];

    showLoadingSwalAlert2(
      '<i class="fa-solid fa-spinner text-primary fa-spin-pulse"></i> Exportando información',
      '<span class="text-primary">Por favor espere...</span>',
      false
    );

    const idUsuario = $("#id_usuario_filtro").val();

    const resp = await enviarPeticion({
      link: link_,
      op: 'exportar_comisiones',
      fechaInicio,
      fechaFin,
      bodegas: JSON.stringify(bodegas),
      idUsuario: idUsuario
    });

    if (!resp || resp.length === 0) {
      Swal.fire("Sin datos", "No hay comisiones para exportar en el período seleccionado", "info");
      return;
    }

    // Usar la función global de exportación a Excel
    exportarDatos(resp, "Reporte_Comisiones_" + fechaInicio + "_" + fechaFin);
    dissminSwal();
  } catch (e) {
    console.error("Error en exportar:", e);
    Swal.fire("Error", "Error al exportar los datos", "error");
    dissminSwal();
  }
};

const limpiar = async () => {
  fechaActual([[
    'fe_fecha_inicial',
    'fe_fecha_final'
  ]]);

  $("#id_bodegas").val(null).trigger('change');
  $("#busqueda_usuario").val("");
  $("#id_usuario_filtro").val("0");
  $("#resultado-comisiones").html("");
  $("#resumen-comisiones").html("");
  $("#btn-exportar").hide();
};

const consultarComisiones = async (fechaInicio, fechaFin) => {
  showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software');

  try {
    const bodegas = $("#id_bodegas").val() || [];

    const campos = [
      "desc_bodega",
      "fecha_documento",
      "numero",
      "razon_comercial",
      "usuario_nombre",
      "vendedor_nit",
      "vendedor_nombre",
      "codigo",
      "producto",
      "marca",
      "cantidad",
      "und",
      "valor_unitario",
      "valor_bruto",
      "pcj_descuento",
      "valor_con_descuento",
      "valor_comision",
      "lote",
      "vmcto"
    ];

    const encabezados = [
      "Bodega",
      "Fecha",
      "Documento",
      "Cliente",
      "Usuario",
      "Cd. Vendedor",
      "Vendedor",
      "Código",
      "Producto",
      "Marca",
      "Cantidad",
      "Und",
      "V. Unitario",
      "V. Bruto",
      "% Desc",
      "V. Neto",
      "Comisión",
      "Lote",
      "Vencimiento"
    ];

    const idUsuario = $("#id_usuario_filtro").val();

    const filtros = {
      op: "s_comisiones_detalle",
      fechaInicio,
      fechaFin,
      bodegas: JSON.stringify(bodegas),
      idUsuario: idUsuario
    };

    const data = {
      link: link_,
      ...filtros
    };

    console.log("Enviando petición con datos:", data);

    const resp = await enviarPeticion(data);

    console.log("Respuesta recibida:", resp);

    if (!resp || resp.length === 0) {
      $("#resultado-comisiones").html(
        ShowAlert("No se encontraron comisiones", "warning")
      );
      $("#resumen-comisiones").html("");
      dissminSwal();
      return;
    }

    // Obtener resumen
    const resumen = await enviarPeticion({
      link: link_,
      op: "s_comisiones_resumen",
      fechaInicio,
      fechaFin,
      bodegas: JSON.stringify(bodegas),
      idUsuario: idUsuario
    });

    // Obtener resumen por vendedor
    const resumenVendedor = await enviarPeticion({
      link: link_,
      op: "s_comisiones_por_vendedor",
      fechaInicio,
      fechaFin,
      bodegas: JSON.stringify(bodegas),
      idUsuario: idUsuario
    });

    // Mostrar tabla de resumen por vendedor
    if (resumenVendedor && resumenVendedor.length > 0) {
      let tablaVendedor = `
        <div class="card mt-4 mb-4 shadow-sm border-0">
          <div class="card-body p-0">
            <h6 class="mb-3 p-3 pb-0 text-primary">
              <i class="fa-solid fa-list me-1"></i>
              Resumen de Comisiones por Vendedor
            </h6>

            <table class="table table-hover table-striped mb-0 resumen-vendedor-table text-nowrap">
              <thead class="table-primary">
                <tr>
                  <th class="text-start">Vendedor</th>
                  <th class="text-end">Docs</th>
                  <th class="text-end">Items</th>
                  <th class="text-end">Venta Bruta</th>
                  <th class="text-end">Devoluciones</th>
                  <th class="text-end">Venta Neta</th>
                  <th class="text-center">% Comisión</th>
                  <th class="text-end">Comisión Total</th>
                </tr>
              </thead>
              <tbody>`;

      let totalVentasVentas = 0;
      let totalDevoluciones = 0;
      let totalVentasNeto = 0;
      let totalComisiones = 0;

      resumenVendedor.forEach((vendedor, index) => {
        const ventasVentas = parseFloat(vendedor.venta_neta_ventas) || 0;
        const ventaDevoluciones = parseFloat(vendedor.venta_neta_devoluciones) || 0;
        const ventasNeto = ventasVentas - ventaDevoluciones;
        const comisiones = parseFloat(vendedor.total_comisiones) || 0;
        const porcentaje = ventasNeto > 0 ? ((comisiones / ventasNeto) * 100).toFixed(2) : 0;

        totalVentasVentas += ventasVentas;
        totalDevoluciones += ventaDevoluciones;
        totalVentasNeto += ventasNeto;
        totalComisiones += comisiones;

        /*
         * Colores Bootstrap:
         * - Venta: success
         * - Devolución: danger
         * - Porcentaje: primary
         * - Valores neutros: secondary
         */

        const colorComision =
          comisiones > 0
            ? "text-success"
            : comisiones < 0
              ? "text-danger"
              : "text-secondary";

        const fondoComision =
          comisiones > 0
            ? "bg-success-subtle"
            : comisiones < 0
              ? "bg-danger-subtle"
              : "bg-light";

        const colorPorcentaje =
          comisiones > 0
            ? "text-primary"
            : comisiones < 0
              ? "text-danger"
              : "text-secondary";

        tablaVendedor += `
          <tr>
            <td class="fw-medium text-primary align-middle">
              ${vendedor.vendedor_nombre || "Sin definir"} <br> ${vendedor.usuario_nombre || "kjhkjhjk"}
            </td>

            <td class="text-end text-primary align-middle">
              <span class="badge bg-info">
                ${vendedor.total_transacciones}
              </span>
            </td>

            <td class="text-end text-primary align-middle">
              <span class="badge bg-secondary">
                ${vendedor.total_items}
              </span>
            </td>

            <td class="text-end fw-medium text-success align-middle">
              $${ventasVentas.toLocaleString("es-CO", {
          maximumFractionDigits: 0
        })}
            </td>

            <td class="text-end fw-medium text-danger align-middle">
              ${ventaDevoluciones > 0 ? "- " : ""}$
              ${ventaDevoluciones.toLocaleString("es-CO", {
          maximumFractionDigits: 0
        })}
            </td>

            <td class="text-end fw-semibold text-primary align-middle">
              $${ventasNeto.toLocaleString("es-CO", {
          maximumFractionDigits: 0
        })}
            </td>

            <td class="text-center fw-semibold ${colorPorcentaje} align-middle">
              <span class="${comisiones > 0
            ? "bg-primary-subtle"
            : comisiones < 0
              ? "bg-danger-subtle"
              : "bg-light"
          } px-2 py-1 rounded d-inline-block">
                ${comisiones < 0 ? "-" : ""}
                ${Math.abs(porcentaje)}%
              </span>
            </td>

            <td class="text-end fw-semibold ${colorComision}">
              <span class="${fondoComision} px-2 py-1 rounded d-inline-block">
                ${comisiones < 0 ? "- $" : "$"}
                ${Math.abs(comisiones).toLocaleString("es-CO", {
            maximumFractionDigits: 0
          })}
              </span>
            </td>
          </tr>`;
      });

      const totalVentasNettoCalc =
        totalVentasVentas - totalDevoluciones;

      const porcentajeTotal =
        totalVentasNettoCalc > 0
          ? ((totalComisiones / totalVentasNettoCalc) * 100).toFixed(2)
          : 0;

      const colorTotalComision =
        totalComisiones > 0
          ? "text-success"
          : totalComisiones < 0
            ? "text-danger"
            : "text-secondary";

      tablaVendedor += `
              <tr class="table-secondary fw-semibold">
                <td class="text-primary">TOTAL</td>
                <td class="text-end"></td>
                <td class="text-end"></td>
                <td class="text-end text-primary">
                  $${totalVentasVentas.toLocaleString("es-CO", { maximumFractionDigits: 0 })}
                </td>

                <td class="text-end text-danger">
                  ${totalDevoluciones > 0 ? "- " : ""}$
                  ${totalDevoluciones.toLocaleString("es-CO", {
        maximumFractionDigits: 0
      })}
                </td>

                <td class="text-end text-primary">
                  $${totalVentasNettoCalc.toLocaleString("es-CO", {
        maximumFractionDigits: 0
      })}
                </td>

                <td class="text-center text-primary">
                  <span class="bg-primary-subtle px-2 py-1 rounded d-inline-block">
                    ${porcentajeTotal}%
                  </span>
                </td>

                <td class="text-end ${colorTotalComision}">
                  <span class="px-2 py-1 rounded d-inline-block">
                    ${totalComisiones < 0 ? "- $" : "$"}
                    ${Math.abs(totalComisiones).toLocaleString("es-CO", {
        maximumFractionDigits: 0
      })}
                  </span>
                </td>
              </tr>
            </tbody>
            </table>
          </div>
        </div>`;

      $("#resultado-comisiones").html(tablaVendedor);

      // Agregar el detalle después del resumen
      $("#resultado-comisiones").append(`
        <div class="mt-4">
          <h6 class="mb-3 text-primary">
            <i class="fa-solid fa-list me-1"></i>
            Detalle de Comisiones
          </h6>

          <table
            id="tb_comisiones"
            class="table table-sm table-borderless table-hover table-striped text-nowrap w-100 comisiones-table"
          >
            <thead class="table-primary"></thead>
            <tbody></tbody>
          </table>
        </div>
      `);
    } else {
      // Si no hay resumen por vendedor, mostrar solo el detalle
      $("#resultado-comisiones").html(`
        <table
          id="tb_comisiones"
          class="table table-sm table-borderless table-hover table-striped text-nowrap w-100 comisiones-table"
        >
          <thead class="table-primary"></thead>
          <tbody></tbody>
        </table>
      `);
    }

    createDataTable(
      "tb_comisiones",
      resp,
      campos,
      encabezados,
      "",
      "",
      false,
      "",
      "",
      true,
      [
        "valor_unitario",
        "valor_bruto",
        "valor_con_descuento",
        "valor_comision"
      ]
    );

    // Colorizar celdas de comisión usando únicamente Bootstrap
    setTimeout(() => {
      const table = $("#tb_comisiones").DataTable();

      table.rows().every(function () {
        const rowData = this.data();

        if (rowData) {
          const comisionValue =
            parseFloat(rowData.valor_comision) || 0;

          const comisionIndex =
            campos.indexOf("valor_comision");

          if (comisionIndex >= 0) {
            const row = $(this.node());
            const cells = row.find("td");
            const comisionCell = cells.eq(comisionIndex);

            // Limpiar clases anteriores
            comisionCell.removeClass(
              "text-success text-danger text-secondary bg-success-subtle bg-danger-subtle bg-light fw-bold"
            );

            if (comisionValue < 0) {
              // Devolución
              comisionCell.addClass(
                "text-danger bg-danger-subtle fw-bold"
              );
            } else if (comisionValue > 0) {
              // Venta normal
              comisionCell.addClass(
                "text-success bg-success-subtle fw-bold"
              );
            } else {
              // Sin comisión
              comisionCell.addClass(
                "text-secondary bg-light"
              );
            }
          }
        }
      });
    }, 100);

    // Mostrar resumen
    if (resumen && resumen.length > 0) {
      const r = resumen[0];

      const totalVentasNeto =
        parseFloat(r.total_ventas_neto) || 0;

      const totalComisiones =
        parseFloat(r.total_comisiones) || 0;

      const totalItems =
        parseInt(r.total_items) || 0;

      const totalTransacciones =
        parseInt(r.total_transacciones) || 0;

      const porcentajeComision =
        totalVentasNeto > 0
          ? ((totalComisiones / totalVentasNeto) * 100).toFixed(2)
          : 0;

      $("#resumen-comisiones").html(`
        <div class="col-md-3 mb-3">
          <div class="card border-primary h-100 shadow-sm">
            <div class="card-body">
              <div class="text-primary mb-2">
                <i class="fa-solid fa-money-bill me-1"></i>
                Total Ventas
              </div>

              <div class="fs-4 fw-bold text-primary">
                $${totalVentasNeto.toLocaleString("es-CO", {
        maximumFractionDigits: 0
      })}
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card border-success h-100 shadow-sm">
            <div class="card-body">
              <div class="text-success mb-2">
                <i class="fa-solid fa-handshake me-1"></i>
                Total Comisiones
              </div>

              <div class="fs-4 fw-bold text-success">
                $${totalComisiones.toLocaleString("es-CO", {
        maximumFractionDigits: 0
      })}
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card border-warning h-100 shadow-sm">
            <div class="card-body">
              <div class="text-warning mb-2">
                <i class="fa-solid fa-percent me-1"></i>
                % Comisión
              </div>

              <div class="fs-4 fw-bold text-warning">
                ${porcentajeComision}%
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3 mb-3">
          <div class="card border-info h-100 shadow-sm">
            <div class="card-body">
              <div class="text-info mb-2">
                <i class="fa-solid fa-boxes me-1"></i>
                Ítems / Docs
              </div>

              <div class="fs-4 fw-bold text-info">
                ${totalItems} / ${totalTransacciones}
              </div>
            </div>
          </div>
        </div>
      `);
    }

    // Mostrar botón de exportar
    $("#btn-exportar").show();

    dissminSwal();

  } catch (e) {
    console.error("Error en consultarComisiones:", e);

    $("#resultado-comisiones").html(
      ShowAlert("Error interno de la aplicación", "error")
    );

    $("#resumen-comisiones").html("");

    dissminSwal();
  }
};

$(async function () {
  await limpiar();
  await cargarBodegas();
  await listarUsuarios();

  $("#btn-busqueda").click(function () {
    let fhIni = $("#fe_fecha_inicial").val();
    let fhFin = $("#fe_fecha_final").val();

    if (!fhIni || !fhFin) {
      Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
      return;
    }

    let dIni = new Date(fhIni);
    let dFin = new Date(fhFin);

    if (dFin < dIni) {
      Swal.fire("Error", "La fecha final no puede ser anterior a la fecha inicial", "error");
      return;
    }

    consultarComisiones(fhIni, fhFin);
  });

  $("#btn-limpiar").click(function () {
    limpiar();
  });

  $("#btn-exportar").click(function () {
    exportarComisiones();
  });

  dissminSwal();
});



