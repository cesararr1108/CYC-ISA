let itemsActualizar = [];
let referenciaSeleccionada = null;
const llenarSelectListas = async () => {
  let listas = await listasPrecios();

  if (!Array.isArray(listas)) {
    console.warn("El parámetro listas no es un array");
    return;
  }

  const options = [
    ...listas.map(({
      id,
      Descripcion
    }) =>
      `<option value="${id}">${id} - ${Descripcion}</option>`
    )
  ];

  $("#lista_precios").html(options.join(""));
}
const tipoListas = async () => {
  let options = `<option value="PF">PF - Precio Fijo</option>
			  	  <option value="CP">CP - Costo Promedio</option>
				  <option value="CU">CU - Costo Ultimo</option>`;
  $('#slcTipoLista').html(options);
};

const cargarItemGrid = async (data) => {

  const lista = parseInt($("#lista_precios").val()) || 0;
  const tipoLista = $("#slcTipoLista").val();

  const resp = await enviarPeticion({
    link: '../models/ActualizacionPrecios.php',
    op: "consultar_productos_lista",
    lista: lista,
    tipo_lista: tipoLista,
    codigo: data.codigo
  });

  Swal.close();

  if (resp.error) {
    Swal.fire("Error", resp.message, "error");
    return;
  }

  if (!resp.data || resp.data.length === 0) {
    referenciaSeleccionada = null;
    $("#descripcion_grid").val('');
    $("#laboratorio_grid").val('');
    $("#precio_grid").val('');
    return;
  }

  const item = resp.data[0];
  referenciaSeleccionada = {
    codigo: item.codigo,
    descripcion: item.descripcion,
    laboratorio: item.laboratorio || '',
    precio_actual: parseFloat(item.precio_actual) || 0
  };

  $("#txt-referencia").val(item.codigo);
  $("#descripcion_grid").val(item.descripcion);
  $("#laboratorio_grid").val(item.laboratorio);
  $("#precio_grid").val(formatNum(item.precio_actual, '$ '));
}

const renderTablaItems = () => {

  let html = '';

  itemsActualizar.forEach((item, index) => {

    html += `
      <tr>
        <td>${item.codigo}</td>
        <td>${item.descripcion}</td>
        <td>${formatNum(item.precio_actual, '$ ')}</td>
        <td>${item.lista}</td>
        <td>${item.tipo_lista}</td>
        <td>
          <input type="number" 
                class="form-control form-control-sm nuevo-precio"
                value="${item.nuevo_precio}"
                data-codigo="${item.codigo}">
        </td>
        <td>
          <button class="btn btn-sm btn-danger btn-eliminar" data-index="${index}">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  });

  $("#tablaItems tbody").html(html);
};

const limpiarCampos = () => {
  $("#txt-referencia").val('');
  $("#descripcion_grid").val('');
  $("#laboratorio_grid").val('');
  $("#precio_grid").val('');
  $("#txtRentabildad").val('');
  referenciaSeleccionada = null;
};


const procesarExcel = async (datosExcel) => {

  console.warn("Datos procesados del Excel:", datosExcel);

  const lista = parseInt($("#lista_precios").val()) || 0;
  const tipoLista = $("#slcTipoLista").val();

  if (!lista) {
    Swal.fire("Error", "Debe seleccionar una lista antes de subir el Excel", "error");
    return;
  }

  if (!tipoLista) {
    Swal.fire("Error", "Debe seleccionar el tipo de lista", "error");
    return;
  }

  try {

    showLoadingWithTetrisLogo('Procesando archivo', 'ISA Software')
    const resp = await enviarPeticion({
      link: '../models/ActualizacionPrecios.php',
      op: "consultar_productos_excel",
      lista: lista,
      tipo_lista: tipoLista, // 👈 lo enviamos también
      datos: JSON.stringify(datosExcel)
    });

    Swal.close();

    if (resp.error) {
      Swal.fire("Error", resp.message, "error");
      return;
    }

    resp.data.forEach(item => {

      const existe = itemsActualizar.find(i => i.codigo === item.codigo);
      if (existe) return;

      itemsActualizar.push({
        ...item,
        lista,
        tipo_lista: tipoLista
      });

    });

    renderTablaItems();

    Swal.fire("OK", "Productos cargados correctamente", "success");

  } catch (error) {
    Swal.close();
    console.error(error);
    Swal.fire("Error", "Error procesando el archivo", "error");
  }

};

const limpiar = () => {
  // Limpiar array de items
  itemsActualizar = [];

  // Limpiar tabla
  renderTablaItems();

  // Limpiar inputs del formulario
  limpiarCampos();
};


$(async function () {
  await llenarSelectListas();
  await tipoListas();


  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    cargarItemGrid(rowData);
  }, { check_stock: false, cod_barras: false });

  let buscando = false;

  $("#txt-referencia").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    e.preventDefault();
    if (buscando) return; // evita doble ejecución rápida
    const filtro = $.trim(this.value);
    if (!filtro) return;
    try {
      buscando = true;
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: false
      });
      const nregistros = modalBusquedaRef.getNrecords();
      console.warn({ resp, nregistros });
      if (!nregistros) return;
      if (nregistros === 1) {
        await cargarItemGrid(resp[0]);
      } else {
        modalBusquedaRef.show();
      }
    } catch (error) {
      console.error("Error en búsqueda:", error);
    } finally {
      buscando = false;
    }

  });

  $("#txt-referencia").on("input", function () {
    const codigoDigitado = $.trim($(this).val());
    if (!referenciaSeleccionada) {
      return;
    }

    if (codigoDigitado !== referenciaSeleccionada.codigo) {
      referenciaSeleccionada = null;
      $("#descripcion_grid").val('');
      $("#laboratorio_grid").val('');
      $("#precio_grid").val('');
    }
  });

  $("#btnBusquedaAvanzada").on("click", function () {
    modalBusquedaRef.show();
    $("#busqueda-descripcion").val($("#txt-referencia").val());
    setTimeout(() => {
      $("#busqueda-descripcion").focus();
    }, 200);
  });



  $("#btnAddLista").on("click", function () {

    const codigo = $.trim($("#txt-referencia").val());
    const lista = $("#lista_precios").val();
    const tipo_lista = $("#slcTipoLista").val();
    const nuevo_precio_raw = $("#txtRentabildad").val();
    const nuevo_precio = parseFloat(nuevo_precio_raw);

    // Validación
    if (nuevo_precio_raw === "" || isNaN(nuevo_precio)) {
      Swal.fire("Error", "Debe ingresar un precio o rentabilidad", "error");
      return;
    }

    if (!codigo || !referenciaSeleccionada || referenciaSeleccionada.codigo !== codigo) {
      Swal.fire("Error", "Debe cargar una referencia válida (Enter/F2) antes de agregar.", "error");
      return;
    }

    const descripcion = referenciaSeleccionada.descripcion;
    const precio_actual = referenciaSeleccionada.precio_actual;

    if (!descripcion) {
      Swal.fire("Error", "No hay referencia cargada para agregar.", "error");
      return;
    }


    // Evitar duplicados
    const existe = itemsActualizar.find(i => i.codigo === codigo && i.lista === lista);
    if (existe) {
      Swal.fire("Aviso", "Este producto ya fue agregado", "warning");
      return;
    }
    const item = {
      codigo,
      descripcion,
      precio_actual,
      tipo_lista,
      lista,
      nuevo_precio
    };

    itemsActualizar.push(item);

    renderTablaItems();

    limpiarCampos();
  });
  $("#tablaItems").on("click", ".btn-eliminar", function () {
    const index = $(this).data("index");
    itemsActualizar.splice(index, 1);
    renderTablaItems();
  });
  $("#btnGuardar").on("click", async function () {

    if (!itemsActualizar.length) {
      Swal.fire("Error", "No hay productos para actualizar", "error");
      return;
    }

    // 🔎 Productos con nuevo precio menor al actual
    const productosMenorPrecio = itemsActualizar.filter(item =>
      parseFloat(item.nuevo_precio) < parseFloat(item.precio_actual)
    );

    // ⚠️ Si existen productos con precio menor
    if (productosMenorPrecio.length > 0) {

      // Construir tabla dentro de un div con scroll
      let htmlTabla = `
      <div style="max-height:300px; overflow-y:auto; text-align:left;">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Precio Actual</th>
              <th>Nuevo Precio</th>
            </tr>
          </thead>
          <tbody>
    `;

      productosMenorPrecio.forEach(p => {
        htmlTabla += `
        <tr>
          <td>${p.codigo}</td>
          <td>${formatNum(p.precio_actual, '$ ')}</td>
          <td>${formatNum(p.nuevo_precio, '$ ')}</td>
        </tr>
      `;
      });

      htmlTabla += `
          </tbody>
        </table>
      </div>
    `;

      // Mostrar modal de confirmación con tabla
      const confirmacion = await Swal.fire({
        title: "⚠ Precios menores detectados",
        html: `
        <p>Los siguientes productos tienen un precio menor al actual:</p>
        ${htmlTabla}
        <br>¿Desea continuar de todas formas?
      `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, continuar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33",
        width: '600px',
        scrollbarPadding: false
      });

      if (!confirmacion.isConfirmed) {
        return; // 🚫 cancela el guardado
      }
    }

    // 💾 Guardar si todo está OK o si confirmó continuar
    try {

      const resp = await enviarPeticion({
        link: '../models/ActualizacionPrecios.php',
        op: "guardar_actualizacion_masiva",
        lista: $("#lista_precios").val(),
        items: JSON.stringify(itemsActualizar)
      });
      if (resp.error) {
        Swal.fire("Error", resp.message, "error");
        return;
      }

      // ✅ Mostrar mensaje de éxito y errores parciales si existen
      let mensaje = resp.message || "Precios actualizados correctamente";

      if (resp.detalles_errores && resp.detalles_errores.length > 0) {

        let htmlErrores = `<div style="max-height:200px; overflow-y:auto; text-align:left;"><ul>`;
        resp.detalles_errores.forEach(err => {
          htmlErrores += `<li>${err}</li>`;
        });
        htmlErrores += `</ul></div>`;

        mensaje += `<br><br><b>Algunos errores se presentaron:</b>${htmlErrores}`;
      }

      Swal.fire({
        title: "Actualización completada",
        html: mensaje,
        icon: "success",
        width: '600px'
      });

      limpiar();

    } catch (error) {
      console.error(error);
      Swal.fire("Error", "No se pudieron guardar los cambios", "error");
    }

  });
  $("#tablaItems").on("input", ".nuevo-precio", function () {

    const codigo = $(this).attr("data-codigo");
    const valor = parseFloat($(this).val()) || 0;

    const item = itemsActualizar.find(i => i.codigo === codigo);

    if (item) {
      item.nuevo_precio = valor;
    }

  });

  $("#btn-csv").on("click", function () {
    const modal = new bootstrap.Modal(document.getElementById("modalExcel"));
    modal.show();
  });
  $("#inputExcel").on("change", async function (e) {
    $("#modalExcel").modal('hide'); // Cerrar el modal al seleccionar el archivo
    const file = e.target.files[0];
    if (!file) return;

    var reader = new FileReader();
    reader.onload = function (event) {
      var data = new Uint8Array(event.target.result);
      var workbook = XLSX.read(data, { type: 'array' });
      var sheetName = workbook.SheetNames[0];
      var worksheet = workbook.Sheets[sheetName];
      var jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false });

      if (!jsonData.length) {
        Swal.fire("Error", "El archivo está vacío", "error");
        return;
      }

      let datas = [];

      // Saltar encabezado y procesar filas
      jsonData.slice(1).forEach(row => {
        const codigo = row[0] ? String(row[0]).trim() : null;
        const nuevoPrecio = row[1] ? parseFloat(row[1]) : null;
        if (codigo && nuevoPrecio !== null) {
          datas.push({ codigo, nuevo_precio: nuevoPrecio });
        }
      });

      procesarExcel(datas);
    };

    reader.readAsArrayBuffer(file);
  });

  // Botón "Nuevo"
  $("#btnLimpiar").on("click", function () {
    limpiar();
  });
  dissminSwal();
});