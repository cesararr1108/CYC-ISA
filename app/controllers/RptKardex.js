const link_ = '../models/RptKardex.php';
id_modulo = 70;

const colorSw = (sw) => {
  let color = ''
  switch (parseInt(sw)) {
    case 1: color = 'primary'; break;
    case 2: color = 'secondary'; break;
    case 3: color = 'success'; break;
    case 4: color = 'danger'; break;
    case 5: color = 'warning'; break;
    case 6: color = 'info'; break;
    case 7: color = 'light'; break;
    case 15: color = 'dark'; break;
  }
  console.log(color);
  return color;
}

const formatDateTime = (value) => {
  if (!value) return '';
  const parseDate = (raw) => {
    const d = new Date(raw);
    if (!isNaN(d.getTime())) return d;
    const normalized = raw.replace(/-/g, '/').replace('T', ' ').replace(/\.(\d+)$/, '');
    const d2 = new Date(normalized);
    return isNaN(d2.getTime()) ? null : d2;
  };
  const date = parseDate(value);
  if (!date) return value;
  return date.toLocaleString('es-CO', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  });
}

const listarAlmacenes = async () => {
  try {
    const data = await getRol();
    const resp = await bodegasUsuarios();
    let options = '';
    let id_rol = parseInt(data.rol);
    console.log({ id_rol })
    if (id_rol == 4) {
      options = `<option value="0">Todas las bodegas</option>`;
    }
    resp.sort((a, b) => a.bodega.localeCompare(b.bodega));
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
  } catch (e) {
    console.error(e)
    Swal.fire("Error", "Error al cargar las bodegas", "error");
  }
}
const consultarKardex = async () => {
  showLoadingWithTetrisLogo('Consultando kardex', 'ISA Software')
  try {
    // 🔹 Capturar filtros
    const data = {
      link: '../models/RptKardex.php',
      id_referencia: $("#id_referencia").val(),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      id_bodega: $("#bodegas").val()
    };
    // 🔹 Ejecutar petición
    const resp = await enviarPeticion(data);
    console.log({ resp })

    // 🔹 Validar datos
    if (!resp || resp.length === 0) {
      $("#resultado-inventario").html(
        ShowAlert("No se encontraron resultados", "warning")
      );
      updateKardexBadgeCounts({});
      dissminSwal();
      return;
    }

    const counters = {
      compras: 0,
      entradas: 0,
      traslados: 0,
      devVentas: 0,
      ventas: 0,
      salidas: 0,
      devCompras: 0,
      ajustes: 0
    };

    const totals = {
      entradas: 0,
      salidas: 0,
      movimientos: resp.length,
      saldoInicial: resp[0]?.saldo_inicial ?? 0,
      saldoFinal: resp[resp.length - 1]?.saldo_acumulado ?? 0
    };

    resp.forEach((element) => {
      const cantidad = parseFloat(element.cantidad) || 0;

      if (cantidad > 0) {
        totals.entradas += cantidad;
      } else {
        totals.salidas += Math.abs(cantidad);
      }

      switch (parseInt(element.sw, 10)) {
        case 1:
          counters.compras += 1;
          break;
        case 2:
          counters.entradas += 1;
          break;
        case 3:
          counters.traslados += 1;
          break;
        case 4:
          counters.devVentas += 1;
          break;
        case 5:
          counters.ventas += 1;
          break;
        case 6:
          counters.salidas += 1;
          break;
        case 7:
          counters.ajustes += 1;
          break;
        case 15:
          counters.devCompras += 1;
          break;
      }
    });

    updateKardexBadgeCounts(counters);

    $("#resultado-inventario").html(`
      <div id="kardex-summary" class="row g-3 mb-3">
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <h6 class="text-uppercase text-muted small mb-2">Saldo inicial</h6>
              <p class="mb-0 fs-5 fw-bold">${totals.saldoInicial}</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <h6 class="text-uppercase text-muted small mb-2">Saldo final</h6>
              <p class="mb-0 fs-5 fw-bold">${totals.saldoFinal}</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <h6 class="text-uppercase text-muted small mb-2">Total entradas</h6>
              <p class="mb-0 fs-5 fw-bold">${totals.entradas}</p>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
              <h6 class="text-uppercase text-muted small mb-2">Total salidas</h6>
              <p class="mb-0 fs-5 fw-bold">${totals.salidas}</p>
            </div>
          </div>
        </div>
      </div>
      <div class="table-responsive text-nowrap">
        <table id="kardex" class="table table-sm table-bordered table-hover table-striped" style="min-width: 1300px;">
          <thead class="table-primary">
            <tr>
              <th>Codigo</th>
              <th>Inicial</th>
              <th>Fecha / Hora</th>
              <th>Movimiento</th>
              <th>Cantidad</th>
              <th>Saldo</th>
              <th>Costo</th>
              <th>Precio</th>
              <th>Vencimiento / Lote</th>
              <th>Tipo / Numero</th>
              <th>Nit</th>
              <th>Tercero</th>
              <th>Usuario</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    `);

    let tr = '';
    resp.forEach((element) => {
      const cantidad = parseFloat(element.cantidad) || 0;
      const color_cantidad = cantidad > 0 ? 'text-success' : 'text-danger';
      tr += `<tr class="table-${colorSw(element.sw)}">
            <td>${element.codigo}</td>
            <td>${element.saldo_inicial}</td>
            <td>${formatDateTime(element.fecha)}</td>
            <td>${element.movimiento}</td>
            <td class="${color_cantidad} text-center">${element.cantidad}</td>
            <td class="text-primary text-center"><b>${element.saldo_acumulado}</b></td>
            <td>${formatNum(element.costo, '$')}</td>
            <td>${formatNum(element.precio, '$')}</td>
            <td>${element.vmcto} / ${element.lote}</td>
            <td>${element.tipo} / ${element.numero}</td>
            <td>${element.nit}</td>
            <td>${element.tercero}</td>
            <td>${element.usuario}</td>
      </tr>`;
    });
    $("#kardex tbody").html(tr);
    $("#kardex").DataTable({
      destroy: true,
      ordering: false,
      pageLength: 100,
      responsive: true,
      scrollX: true,
      language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
    dissminSwal();
  } catch (e) {
    console.error("❌ Error en consultarInventario:", e);
    $("#resultado-inventario").html(
      ShowAlert("Error interno de la aplicación", "error")
    );
    updateKardexBadgeCounts({});
    dissminSwal();
  }
};

const updateKardexBadgeCounts = (counts) => {
  $("#badge-compras-count").text(counts.compras || 0);
  $("#badge-entradas-count").text(counts.entradas || 0);
  $("#badge-traslados-count").text(counts.traslados || 0);
  $("#badge-dev-ventas-count").text(counts.devVentas || 0);
  $("#badge-ventas-count").text(counts.ventas || 0);
  $("#badge-salidas-count").text(counts.salidas || 0);
  $("#badge-dev-compras-count").text(counts.devCompras || 0);
  $("#badge-ajustes-count").text(counts.ajustes || 0);
};
const cargarItemGrid = async (item) => {
  $("#busqueda-descripcion").val(item.descripcion + ' ' + item.laboratorio)
  $("#id_referencia").val(item.id)
}


$(async function () {
  await listarAlmacenes();

  fechaActual([[
    'fecha_inicial',
    'fecha_final'
  ]]);

  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];


  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    cargarItemGrid(rowData);
  }, { check_stock: true, cod_barras: false });



  $("#busqueda-descripcion").on('keyup', async function (e) {
    const filtro = $.trim($(this).val())
    if (filtro.length == 0) {
      $("#id_referencia").val('');
      return;
    }
    if (e.key == 'Enter') {
      const resp = await modalBusquedaRef.getRecords({ data: filtro, cod_barras: false, con_stock: true });
      const nregistros = modalBusquedaRef.getNrecords();
      if (nregistros > 0) {
        if (nregistros == 1) {
          await cargarItemGrid(resp[0]);
          $("#modalBusquedaRef").modal("hide")
        } else {
          modalBusquedaRef.show();
        }
      }
    }
  });

  $("#btnBusquedaAvanzada").click(function () {
    modalBusquedaRef.show();
  });

  $("#btn-busqueda").click(function () {
    if (isNaN(parseInt($("#id_referencia").val()))) {
      Swal.fire("Error", "Debe seleccionar una referencia", "info")
      return;
    }

    if ($("#fecha_inicial").val() == '' || $("#fecha_final").val() == '') {
      Swal.fire("Error", "Debe seleccionar un rango de fecha", "info")
      return;
    }
    consultarKardex();
  })

  $("#busqueda-descripcion").on("input", function () {
    if ($(this).val().trim() === "") {
      $("#id_referencia").val("");
      $("#resultado-inventario").html("");
      updateKardexBadgeCounts({});
    }
  });

  $("#btn-limpiar").click(function () {
    $("#busqueda-descripcion").val('')
    $("#id_referencia").val('')
    $("#resultado-inventario").html('');
    updateKardexBadgeCounts({});
    $("#busqueda-descripcion").focus();
  })
  dissminSwal();
});

