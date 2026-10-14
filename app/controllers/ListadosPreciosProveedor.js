/**
 * Controlador de Listados de Precios - Módulo Sugerido de Compras
 * Adaptado para funcionar con sugerido_compras.php 
 */

let proveedorSeleccionadoSugerido = null;
let datosPreviewSugerido = [];

$(function () {
  cargarProveedoresSugerido();
  configurarEventosSugerido();
});

/**
 * Carga la lista de proveedores disponibles con autocompletación
 */
function cargarProveedoresSugerido() {
  $('#selectProveedorSugerido').autocomplete({
    source: async function (request, response) {
      const data = {
        link: '../models/Terceros.php',
        filtro: $.trim(request.term),
        tipo_tercero: 2,
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
      console.log('Proveedor seleccionado:', item);

      // Validar que sea proveedor
      if (ConfModulos.SoloTercerosProveedores == 'S') {
        if (item.es_proveedor != 'S') {
          Swal.fire("Error", "El tercero no es un proveedor", "error");
          $('#selectProveedorSugerido').val('');
          return false;
        }
      }

      // Validar condición de pago
      if (item.id_condicion_prov == 0) {
        Swal.fire("Error", "El tercero no tiene condición de pago para proveedor", "error");
        $('#selectProveedorSugerido').val('');
        return false;
      }

      // Llenar los campos
      $("#idProveedorSugerido").val(item.id);
      $("#idTerceroSugerido").val(item.id);
      $("#inputNitSugerido").val(item.nit);
      $("#inputNombreProveedorSugerido").val(item.razon_comercial);

      // Guardar datos del proveedor
      proveedorSeleccionadoSugerido = {
        id: item.id,
        nit: item.nit,
        nombre: item.razon_comercial,
        es_proveedor: item.es_proveedor
      };

      // Habilitar botón de procesar
      $('#btnProcesarArchivoSugerido').prop('disabled', true);
    }
  });
}

/**
 * Configura los eventos de la interfaz
 */
function configurarEventosSugerido() {
  // Al cambiar el archivo
  $('#inputArchivoSugerido').on('change', function (e) {
    validarArchivoSeleccionadoSugerido(e);
  });

  // Botón para cargar archivo
  $('#btnCargarArchivoSugerido').on('click', function () {
    $('#inputArchivoSugerido').click();
  });

  // Botón para procesar preview
  $('#btnProcesarArchivoSugerido').on('click', function () {
    procesarPreviewSugerido();
  });

  // Botón para guardar
  $('#btnGuardarListadoSugerido').on('click', function () {
    guardarListadoSugerido();
  });

  // Botón para descargar plantilla
  $('#btnDescargarPlantillaSugerido').on('click', function () {
    descargarPlantillaSugerido();
  });

  // Botón para limpiar
  $('#btnLimpiarFormSugerido').on('click', function () {
    limpiarFormularioSugerido();
  });
}

/**
 * Obtiene los datos del proveedor
 */
function obtenerDatosProveedorSugerido(nit) {
  $.ajax({
    url: '../models/ListadosPreciosProveedor.php',
    type: 'POST',
    data: { op: 'obtener_proveedor_nit', nit: nit },
    dataType: 'json',
    success: function (response) {
      if (response.exito) {
        proveedorSeleccionadoSugerido = response.proveedor;
        $('#inputNitSugerido').val(response.proveedor.nit);
        $('#inputNombreProveedorSugerido').val(response.proveedor.nombre);
        Swal.fire('Éxito', 'Proveedor seleccionado correctamente', 'success');
      } else {
        Swal.fire('Error', response.mensaje, 'error');
        $('#selectProveedorSugerido').val('');
        $('#inputNitSugerido').val('');
      }
    },
    error: function (error) {
      Swal.fire('Error', 'Error al obtener datos del proveedor', 'error');
    }
  });
}

/**
 * Valida el archivo seleccionado
 */
function validarArchivoSeleccionadoSugerido(e) {
  let archivo = e.target.files[0];
  if (!archivo) return;

  let extension = archivo.name.split('.').pop().toLowerCase();
  let extensionesPermitidas = ['csv', 'xlsx', 'xls'];

  if (!extensionesPermitidas.includes(extension)) {
    Swal.fire('Error', 'Solo se permiten archivos CSV, XLSX o XLS', 'error');
    $('#inputArchivoSugerido').val('');
    return;
  }

  let tamanoMB = archivo.size / (1024 * 1024);
  if (tamanoMB > 10) {
    Swal.fire('Error', 'El archivo no debe superar 10 MB', 'error');
    $('#inputArchivoSugerido').val('');
    return;
  }

  $('#labelArchivoSugerido').val(archivo.name);
  $('#btnProcesarArchivoSugerido').prop('disabled', false);
}

/**
 * Procesa la previsualización del archivo
 */
function procesarPreviewSugerido() {
  if (!proveedorSeleccionadoSugerido || !proveedorSeleccionadoSugerido.nit) {
    Swal.fire('Error', 'Por favor selecciona un proveedor primero', 'error');
    return;
  }

  let archivo = $('#inputArchivoSugerido')[0].files[0];
  if (!archivo) {
    Swal.fire('Error', 'Por favor selecciona un archivo', 'error');
    return;
  }

  let formData = new FormData();
  formData.append('op', 'procesar_preview');
  formData.append('archivo', archivo);
  formData.append('nit_proveedor', proveedorSeleccionadoSugerido.nit);

  Swal.fire({
    title: 'Procesando archivo',
    html: '<i class="fa-solid fa-spinner text-primary fa-spin-pulse"></i><br>Por favor espere...',
    allowOutsideClick: false,
    didOpen: (toast) => {
      Swal.showLoading();
    }
  });

  $.ajax({
    url: '../shared/servicios/procesarListadoPrecios.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (response) {
      console.log('Respuesta del servidor:', response);
      Swal.close();

      if (response.error) {
        Swal.fire('Error', response.message, 'error');
        return;
      }

      datosPreviewSugerido = response.datos;
      mostrarPreviewSugerido(response);
    },
    error: function (error) {
      console.error('Error en la solicitud:', error);
      Swal.close();
      Swal.fire('Error', 'Error al procesar el archivo', 'error');
    }
  });
}

/**
 * Muestra la previsualización de los datos
 */
function mostrarPreviewSugerido(response) {
  let html = '';

  // Resumen
  html += `
    <div class="alert alert-info">
      <strong>Resumen:</strong><br>
      Total de filas: ${response.total_filas}<br>
      Filas válidas (encontradas): <span class="text-success">${response.filas_validas}</span><br>
      Filas con problemas: <span class="text-danger">${response.filas_problematicas}</span>
    </div>
  `;

  // Tabla de datos
  html += '<div class="table-responsive">';
  html += '<table class="table table-sm table-striped" id="tablePreview">';
  html += '<thead class="table-light">';
  html += '<tr>';
  html += '<th>Línea</th>';
  html += '<th>EAN</th>';
  html += '<th>Código</th>';
  html += '<th>Descripción</th>';
  html += '<th>Precio</th>';
  html += '<th>Precio IVA</th>';
  html += '<th>Estado</th>';
  html += '</tr>';
  html += '</thead>';
  html += '<tbody>';

  response.datos.forEach(function (item) {
    let estadoClass = item.estado === 'encontrado' ? 'table-success' : 'table-danger';
    let estadoTexto = item.estado === 'encontrado' ? 'Encontrado ✓' : 'No encontrado ✗';
    let codigo = item.codigo || '-';
    let descripcion = item.descripcion || (item.error || '-');

    html += `
      <tr class="${estadoClass}">
        <td>${item.linea}</td>
        <td>${item.ean}</td>
        <td>${codigo}</td>
        <td>${descripcion}</td>
        <td>${item.precio}</td>
        <td>${item.precio_iva}</td>
        <td>${estadoTexto}</td>
      </tr>
    `;
  });

  html += '</tbody>';
  html += '</table>';
  html += '</div>';

  // Mensajes de error si existen
  if (response.errores.length > 0) {
    html += '<div class="alert alert-warning">';
    html += '<strong>Advertencias:</strong><br>';
    response.errores.forEach(function (error) {
      html += `<small>${error}</small><br>`;
    });
    html += '</div>';
  }

  // Mostrar en modal
  $('#previewContenidoSugerido').html(html);

  // Si hay filas válidas, habilitar botón guardar
  if (response.filas_validas > 0) {
    $('#btnGuardarListadoSugerido').prop('disabled', false);
  } else {
    $('#btnGuardarListadoSugerido').prop('disabled', true);
  }

  // Mostrar modal
  new bootstrap.Modal(document.getElementById('modalPreviewSugerido')).show();
}

/**
 * Guarda el listado de precios
 */
function guardarListadoSugerido() {
  if (!proveedorSeleccionadoSugerido || !datosPreviewSugerido.length) {
    Swal.fire('Error', 'No hay datos para guardar', 'error');
    return;
  }
  //este modal con el z idex elevado por encima de los modales
  let modal = bootstrap.Modal.getInstance(document.getElementById('modalPreviewSugerido'));
  if (modal) {
    modal.hide();
  }
  Swal.fire({
    title: '¿Confirmar guardado?',
    text: `Se guardarán ${datosPreviewSugerido.filter(d => d.estado === 'encontrado').length} registros`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      realizarGuardadoSugerido();
    }
  });
}

/**
 * Realiza el guardado de los datos
 */
function realizarGuardadoSugerido() {
  // Filtrar solo los datos encontrados
  let datosAGuardar = datosPreviewSugerido.filter(d => d.estado === 'encontrado');

  let formData = new FormData();
  formData.append('op', 'guardar_listado');
  formData.append('datos', JSON.stringify(datosAGuardar));
  formData.append('nit_proveedor', proveedorSeleccionadoSugerido.nit);

  // Mostrar modal de actualización
  new bootstrap.Modal(document.getElementById('modalActualizandoSugerido')).show();

  $.ajax({
    url: '../shared/servicios/procesarListadoPrecios.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (response) {
      console.log({ response });
      bootstrap.Modal.getInstance(document.getElementById('modalActualizandoSugerido')).hide();

      if (response.error) {
        Swal.fire('Error', response.message, 'error');
        return;
      }

      Swal.fire('Éxito', response.message, 'success').then(() => {
        // Cerrar modal de preview
        bootstrap.Modal.getInstance(document.getElementById('modalPreviewSugerido')).hide();
        limpiarFormularioSugerido();
      });
    },
    error: function (error) {
      console.log({ error });
      bootstrap.Modal.getInstance(document.getElementById('modalActualizandoSugerido')).hide();
      Swal.fire('Error', 'Error al guardar los datos', 'error');
    }
  });
}

/**
 * Descarga una plantilla de ejemplo
 */
function descargarPlantillaSugerido() {
  window.location.href = '../shared/servicios/procesarListadoPrecios.php?op=descargar_plantilla';
}

/**
 * Limpia el formulario
 */
function limpiarFormularioSugerido() {
  $('#selectProveedorSugerido').val('');
  $('#idProveedorSugerido').val('');
  $('#idTerceroSugerido').val('');
  $('#inputNitSugerido').val('');
  $('#inputNombreProveedorSugerido').val('');
  $('#inputArchivoSugerido').val('');
  $('#labelArchivoSugerido').val('');
  $('#btnProcesarArchivoSugerido').prop('disabled', true);
  $('#btnGuardarListadoSugerido').prop('disabled', true);
  datosPreviewSugerido = [];
  proveedorSeleccionadoSugerido = null;
}
