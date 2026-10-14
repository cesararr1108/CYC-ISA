const linkHomologacion = '../models/HomologacionCodigos.php';

let homologacionesPreview = [];
let filaBusquedaActiva = null;
let modalBusquedaReferencia = null;
let buscarReferenciaBackendArr = [];
let filtroPreview = 'todos';

const parsearNumero = (valor) => {
  const limpio = String(valor ?? '').trim().replace(/[$\s]/g, '');
  if (!limpio) {
    return null;
  }

  if (limpio.includes(',') && !limpio.includes('.')) {
    return parseFloat(limpio.replace(',', '.'));
  }

  return parseFloat(limpio.replace(/,/g, ''));
};

const formatearNumero = (valor) => {
  const numero = parseFloat(String(valor ?? '').replace(/[^0-9,.-]/g, '').replace(',', '.'));
  if (Number.isNaN(numero)) {
    return '';
  }

  return new Intl.NumberFormat('es-CO', {
    maximumFractionDigits: 2
  }).format(numero);
};

const parsearLineaCsv = (linea, separador) => {
  const resultado = [];
  let actual = '';
  let entreComillas = false;

  for (let i = 0; i < linea.length; i++) {
    const caracter = linea[i];

    if (caracter === '"') {
      if (entreComillas && linea[i + 1] === '"') {
        actual += '"';
        i++;
      } else {
        entreComillas = !entreComillas;
      }
      continue;
    }

    if (caracter === separador && !entreComillas) {
      resultado.push(actual.trim());
      actual = '';
      continue;
    }

    actual += caracter;
  }

  resultado.push(actual.trim());
  return resultado;
};

const detectarSeparador = (linea) => {
  const comas = (linea.match(/,/g) || []).length;
  const puntosYComas = (linea.match(/;/g) || []).length;
  return puntosYComas > comas ? ';' : ',';
};

const esEncabezado = (fila) => {
  const texto = fila.join(' ').toLowerCase();
  return texto.includes('codigo') || texto.includes('código') || texto.includes('nit') || texto.includes('precio') || texto.includes('costo');
};

const normalizarFilaCsv = (fila) => {
  const columnas = fila.map((item) => String(item ?? '').trim());

  if (columnas.length < 4) {
    return null;
  }

  const codigoBarras = columnas[0] || '';
  const codigoProveedor = columnas[1] || '';
  const nitTercero = columnas[2] || '';
  let stock = null;
  let precio = null;

  if (columnas.length >= 5) {
    stock = columnas[3] !== '' ? parsearNumero(columnas[3]) : null;
    precio = columnas[4] !== '' ? parsearNumero(columnas[4]) : null;
  } else {
    precio = columnas[3] !== '' ? parsearNumero(columnas[3]) : null;
  }

  return {
    codigo_barras: codigoBarras,
    codigo_tercero: codigoProveedor,
    nit_tercero: nitTercero,
    stock,
    precio,
    id_referencia: 0,
    codigo_sistema: '',
    descripcion_sistema: '',
    estado: 'pendiente',
    mensaje: 'Sin asociar'
  };
};

const buscarReferenciaBackend = async () => {
    try {
        const resp = await enviarPeticion({
            link: linkHomologacion,
            op: 'buscar_referencia'
        });
        buscarReferenciaBackendArr = resp.data || [];        
    } catch (error) {
        console.error({error}); 
    }

};

const renderizarPreview = () => {
  let html = '';
  let totalAsociadas = 0;
  let totalPendientes = 0;
  const nits = new Set();
  let totalVisibles = 0;
  console.log({
    homologacionesPreview,
    filtroPreview
  })
  homologacionesPreview.forEach((item, index) => {
    if (item.nit_tercero) {
      nits.add(item.nit_tercero);
    }

    if (item.id_referencia > 0) {
      totalAsociadas++;
    } else {
      totalPendientes++;
    }

    const estadoClass = item.id_referencia > 0 ? 'preview-ok' : 'preview-error';
    const badgeClass = item.id_referencia > 0 ? 'bg-success' : 'bg-danger';
    const badgeText = item.id_referencia > 0 ? 'Asociada' : 'Pendiente';

    const esPendiente = item.id_referencia <= 0;
    if (filtroPreview === 'pendientes' && !esPendiente) {
      return;
    }
    if (filtroPreview === 'asociados' && esPendiente) {
      return;
    }

    totalVisibles++;

    html += `
      <tr class="${estadoClass}">
        <td>${index + 1}</td>
        <td>${item.codigo_barras || ''}</td>
        <td>${item.codigo_tercero || ''}</td>
        <td>${item.nit_tercero || ''}</td>
        <td style="min-width:110px;">
          <input type="text" class="form-control form-control-sm input-stock" data-index="${index}" value="${item.stock ?? ''}">
        </td>
        <td style="min-width:150px;">
          <input type="text" class="form-control form-control-sm input-precio" data-index="${index}" value="${item.precio ?? ''}">
        </td>
        <td>
          <div class="small fw-semibold">${item.codigo_sistema || 'Sin asociar'}</div>
          <div class="text-muted small">${item.descripcion_sistema || ''}</div>
        </td>
        <td><span class="badge ${badgeClass} preview-badge">${badgeText}</span></td>
        <td class="text-nowrap">
          <button type="button" class="btn btn-outline-primary btn-sm btn-buscar-fila" data-index="${index}"><i class="fa-solid fa-magnifying-glass"></i></button>
          <button type="button" class="btn btn-outline-secondary btn-sm btn-limpiar-fila" data-index="${index}"><i class="fa-regular fa-circle-xmark"></i></button>
        </td>
      </tr>
    `;
  });

  $('#tbodyPreviewHomologacion').html(html || '<tr><td colspan="9" class="text-center text-muted">No hay datos para previsualizar</td></tr>');
  $('#lblTotalFilas').text(homologacionesPreview.length);
  $('#lblTotalAsociadas').text(totalAsociadas);
  $('#lblTotalPendientes').text(totalPendientes);
  $('#lblTotalNits').text(nits.size);

  if (!homologacionesPreview.length) {
    $('#lblEstadoArchivo').text('Sin archivo cargado');
  } else {
    $('#lblEstadoArchivo').text(`Mostrando ${totalVisibles} de ${homologacionesPreview.length} filas`);
  }
};

const asociarFila = (index, referencia) => {
  homologacionesPreview[index] = {
    ...homologacionesPreview[index],
    id_referencia: parseInt(referencia.id, 10) || 0,
    codigo_sistema: referencia.codigo || '',
    descripcion_sistema: referencia.descripcion || '',
    estado: 'asociado',
    mensaje: 'Referencia encontrada'
  };

  renderizarPreview();
};

const abrirBuscador = async (index, terminoInicial = '') => {
  filaBusquedaActiva = index;
  $('#txtBuscarReferencia').val(terminoInicial || homologacionesPreview[index]?.codigo_barras || homologacionesPreview[index]?.codigo_tercero || '');
  $('#tbodyBusquedaReferencia').html('<tr><td colspan="4" class="text-center text-muted">Buscando...</td></tr>');
  modalBusquedaReferencia.show();
  await buscarReferencia();
};

const buscarReferencia = async () => {
  const filtro = $.trim($('#txtBuscarReferencia').val());

  if (!filtro) {
    $('#tbodyBusquedaReferencia').html('<tr><td colspan="4" class="text-center text-muted">Ingrese un criterio de búsqueda</td></tr>');
    return;
  }

  try {
    
    //const data = await buscarReferenciaBackend(filtro);
    console.log({
        buscarReferenciaBackendArr
    })
    const data = buscarReferenciaBackendArr.filter((item) => {
      const codigo = String(item.codigo || '').toLowerCase();
      const ean = String(item.ean || '').toLowerCase();
      const descripcion = String(item.descripcion || '').toLowerCase();

      return codigo.includes(filtro.toLowerCase()) 
      || ean.includes(filtro.toLowerCase())
      || descripcion.includes(filtro.toLowerCase());
    });
    if (!data.length) {
      $('#tbodyBusquedaReferencia').html('<tr><td colspan="4" class="text-center text-danger">No se encontraron referencias</td></tr>');
      return;
    }

    let html = '';
    data.forEach((item) => {
      html += `
        <tr>
          <td>${item.codigo || ''}</td>
          <td>${item.ean || ''}</td>
          <td>${item.descripcion || ''}</td>
          <td class="text-end">
            <button type="button" class="btn btn-outline-primary btn-sm btn-asociar-referencia"
              data-id="${item.id}"
              data-codigo="${item.codigo || ''}"
              data-descripcion="${item.descripcion || ''}">
              Asociar
            </button>
          </td>
        </tr>
      `;
    });

    $('#tbodyBusquedaReferencia').html(html);
  } catch (error) {
    console.error(error);
    $('#tbodyBusquedaReferencia').html('<tr><td colspan="4" class="text-center text-danger">Error al buscar referencias</td></tr>');
  }
};

const leerArchivoCsv = async (file) => {
  if (!file) {
    Swal.fire('Alerta', 'Debe seleccionar un archivo CSV', 'warning');
    return;
  }

  const contenido = await file.text();
  const lineas = contenido.split(/\r?\n/).map((linea) => linea.trim()).filter(Boolean);
  console.log('Contenido del archivo CSV:', lineas);
  if (!lineas.length) {
    Swal.fire('Alerta', 'El archivo está vacío', 'warning');
    return;
  }

  const separador = detectarSeparador(lineas[0]);
  let filas = lineas.map((linea) => parsearLineaCsv(linea, separador));

  if (filas.length && esEncabezado(filas[0])) {
    filas = filas.slice(1);
  }

  homologacionesPreview = filas
    .map(normalizarFilaCsv)
    .filter(Boolean)
    .filter((fila) => fila.codigo_barras || fila.codigo_tercero || fila.nit_tercero);
  console.log('Filas normalizadas para previsualización:', homologacionesPreview);
  renderizarPreview();
  await autoasociarFilas();
};

const autoasociarFilas = async () => {
  if (!homologacionesPreview.length) {
    dissminSwal();
    return;
  }


  //console.log('Iniciando autoasociación de filas...',homologacionesPreview);
  showLoadingWithTetrisLogo('Buscando coincidencias automáticas...', 'ISA Software');

  try {
    for (let i = 0; i < homologacionesPreview.length; i++) {
      const fila = homologacionesPreview[i];
      if (fila.id_referencia > 0) {
        continue;
      }
      //console.log(`Buscando coincidencias para fila ${i + 1}:`, fila);
      const candidatos = [fila.codigo_barras, fila.codigo_tercero].filter((valor) => $.trim(valor || '') !== '');
      let encontrado = null;

      for (const candidato of candidatos) {
        console.log(`Buscando coincidencias para candidato: ${candidato}`);
       // const data = await buscarReferenciaBackend(candidato);
           const data = buscarReferenciaBackendArr.filter((item) => {
                const codigo = String(item.codigo || '').toLowerCase();
                const ean = String(item.ean || '').toLowerCase();
                const descripcion = String(item.descripcion || '').toLowerCase();

                return codigo.includes(candidato.toLowerCase()) 
                || ean.includes(candidato.toLowerCase()) ;
            });

        if (data.length) {
          encontrado = data[0];
          break;
        }
      }

      if (encontrado) {
            homologacionesPreview[i] = {
            ...fila,
            id_referencia: parseInt(encontrado.id, 10) || 0,
            codigo_sistema: encontrado.codigo || '',
            descripcion_sistema: encontrado.descripcion || '',
            estado: 'asociado',
            mensaje: 'Coincidencia automática'
            };
      }
    }
    dissminSwal();
  } catch (error) {
    console.error(error);
  } finally {
    dissminSwal();
    renderizarPreview();
  }
};

const cargarListado = async () => {
  try {
    const resp = await enviarPeticion({
      link: linkHomologacion,
      op: 'listar_homologacion',
      filtro: $('#txtFiltroListado').val() || ''
    });
      console.log('Listado de homologaciones:', resp);
    const data = resp.data || [];
    
    if(!data.length) {
        dissminSwal();
      $('#tbodyListadoHomologacion').html('<tr><td colspan="10" class="text-center text-muted">No hay homologaciones registradas</td></tr>');
      return;
    }

      dissminSwal();
    let html = `               
              <table class="table table-sm table-striped table-hover align-middle table-listado" id="tblListadoHomologacion">
                  <thead class="table-primary">
                    <tr>
                      <th>ID</th>
                      <th>Código barras</th>
                      <th>Código proveedor</th>
                      <th>NIT</th>
                      <th>Referencia</th>
                      <th>Descripción</th>
                      <th>Stock</th>
                      <th>Precio/Costo</th>
                      <th>Tipo</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody >`;
    data.forEach((item) => {
      html += `
        <tr data-id="${item.id}">
          <td>${item.id}</td>
          <td>${item.codigo_barras || ''}</td>
          <td>${item.codigo_tercero || ''}</td>
          <td>${item.nit_tercero || ''}</td>
          <td>${item.codigo_sistema || ''}</td>
          <td>${item.descripcion_sistema || ''}</td>
          <td>${item.stock ?? ''}</td>
          <td>${formatearNumero(item.precio ?? '')}</td>
          <td>${item.tipo_listado || ''}</td>
          <td class="text-nowrap">
            <button type="button" 
            class="btn btn-outline-danger btn-sm btn-eliminar-homologacion" data-id="${item.id}"
              onclick ="eliminarHomologacion(${item.id})"
            >
            <i class="fa-regular fa-trash-can"></i>
            </button>
          </td>
        </tr>
      `;
    });
    html += `
                  </tbody>
              </table>
          `;
    

    $('#DivListadoHomologacion').html(html || '<tr><td colspan="10" class="text-center text-muted">No hay homologaciones registradas</td></tr>');
/*
    $(".btn-eliminar-homologacion").off('click').on('click', async function () {
      const id = parseInt($(this).data('id'), 10);
      console.log('Eliminando homologación con ID:', id);
      if (id > 0) {
        await eliminarHomologacion(id);
      }
    });*/

    if ($.fn.DataTable.isDataTable('#tblListadoHomologacion')) {
      $('#tblListadoHomologacion').DataTable().destroy();
    }

    $('#tblListadoHomologacion').DataTable({
      pageLength: 10,
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      }
    });
    dissminSwal();
  } catch (error) {
    dissminSwal();
    console.error({error});
    Swal.fire('Error', 'No fue posible cargar el listado', 'error');
  }
};

const guardarHomologacion = async () => {
  if (!homologacionesPreview.length) {
    Swal.fire('Alerta', 'No hay datos para guardar', 'warning');
    return;
  }

  const asociados = homologacionesPreview.filter((item) => item.id_referencia > 0);
  const omitidos = homologacionesPreview.length - asociados.length;

  if (!asociados.length) {
    Swal.fire('Alerta', 'No hay filas asociadas para guardar', 'warning');
    return;
  }

  const tipoListado = $('#slcTipoListado').val();
  const items = asociados.map((item) => ({
    codigo_barras: item.codigo_barras,
    codigo_tercero: item.codigo_tercero,
    nit_tercero: item.nit_tercero,
    stock: item.stock,
    precio: item.precio,
    id_referencia: item.id_referencia
  }));

  try {
    showLoadingWithTetrisLogo('Guardando homologaciones...', 'ISA Software');
    const resp = await enviarPeticion({
      link: linkHomologacion,
      op: 'guardar_homologaciones',
      tipo_listado: tipoListado,
      items: JSON.stringify(items)
    });

    dissminSwal();

    if (resp.error) {
      const errorHtml = (resp.errores || []).map((item) => `<li>${item}</li>`).join('');
      Swal.fire('Error', `${resp.message || 'No fue posible guardar'}${errorHtml ? `<ul class="text-start mt-2">${errorHtml}</ul>` : ''}`, 'error');
      return;
    }

    let mensajeOk = resp.message || 'Homologación guardada';
    if (omitidos > 0) {
      mensajeOk += `. Se omitieron ${omitidos} fila(s) no asociadas.`;
    }
    if ((resp.omitidos || 0) > 0) {
      mensajeOk += ` El servidor omitió ${resp.omitidos} fila(s) incompletas.`;
    }

    Swal.fire('Correcto', mensajeOk, 'success');
    await cargarListado();
    limpiarFormularioCodigos();
  } catch (error) {
    dissminSwal();
    console.error(error);
    Swal.fire('Error', 'Error al guardar la homologación', 'error');
  }
};

const eliminarHomologacion = async (id) => {
  const confirm = await Swal.fire({
    title: '¿Eliminar homologación?',
    text: 'Esta acción no se puede deshacer',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (!confirm.isConfirmed) {
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: linkHomologacion,
      op: 'eliminar_homologacion',
      id
    });

    if (resp.error) {
      Swal.fire('Error', resp.message || 'No fue posible eliminar', 'error');
      return;
    }

    Swal.fire('Correcto', resp.message || 'Registro eliminado', 'success');
    await cargarListado();
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'Error al eliminar la homologación', 'error');
  }
};

const limpiarFormularioCodigos = () => {
  homologacionesPreview = [];
  $('#txtArchivoHomologacion').val('');
  $('#txtNombreArchivo').val('');
  $('#tbodyPreviewHomologacion').html('');
  $('#lblTotalFilas').text('0');
  $('#lblTotalAsociadas').text('0');
  $('#lblTotalPendientes').text('0');
  $('#lblTotalNits').text('0');
  $('#lblEstadoArchivo').text('Sin archivo cargado');
  buscarReferenciaBackendArr = [];
};

$(async function () {
  modalBusquedaReferencia = new bootstrap.Modal(document.getElementById('modalBuscarReferencia'));
  await cargarListado();
  renderizarPreview();
  await buscarReferenciaBackend();

  $('#btnSeleccionarArchivo').on('click', () => $('#txtArchivoHomologacion').trigger('click'));

  $('#txtArchivoHomologacion').on('change', async function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    $('#txtNombreArchivo').val(file ? file.name : '');
    if (file) {
      console.log('Archivo seleccionado:', file);
      await leerArchivoCsv(file);
    }
  });

  $('#btnProcesarArchivo').on('click', async () => {
    const file = $('#txtArchivoHomologacion')[0].files[0];
    await leerArchivoCsv(file);
  });

  $('#btnLimpiarArchivo, #btnLimpiarHomologacion').on('click', async () => {
    limpiarFormularioCodigos();
     await buscarReferenciaBackend();
  });

  $('#btnGuardarHomologacion').on('click', guardarHomologacion);

  $('#slcFiltroPreview').on('change', function () {
    filtroPreview = $(this).val() || 'todos';
    renderizarPreview();
  });

  $('#btnBuscarListado').on('click', cargarListado);

  $('#txtFiltroListado').on('keydown', async function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      await cargarListado();
    }
  });

  $('#btnBuscarReferencia').on('click', buscarReferencia);

  $('#txtBuscarReferencia').on('keydown', async function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      await buscarReferencia();
    }
  });

  $('#tbodyPreviewHomologacion').on('click', '.btn-buscar-fila', function () {
    const index = parseInt($(this).data('index'), 10);
    abrirBuscador(index);
  });

  $('#tbodyPreviewHomologacion').on('click', '.btn-limpiar-fila', function () {
    const index = parseInt($(this).data('index'), 10);
    homologacionesPreview[index] = {
      ...homologacionesPreview[index],
      id_referencia: 0,
      codigo_sistema: '',
      descripcion_sistema: '',
      estado: 'pendiente',
      mensaje: 'Sin asociar'
    };
    renderizarPreview();
  });

  $('#tbodyPreviewHomologacion').on('input', '.input-stock', function () {
    const index = parseInt($(this).data('index'), 10);
    homologacionesPreview[index].stock = parsearNumero($(this).val());
  });

  $('#tbodyPreviewHomologacion').on('input', '.input-precio', function () {
    const index = parseInt($(this).data('index'), 10);
    homologacionesPreview[index].precio = parsearNumero($(this).val());
  });

  $('#tbodyBusquedaReferencia').on('click', '.btn-asociar-referencia', function () {
    const referencia = {
      id: $(this).data('id'),
      codigo: $(this).data('codigo'),
      descripcion: $(this).data('descripcion')
    };

    if (filaBusquedaActiva === null || filaBusquedaActiva === undefined) {
      return;
    }

    asociarFila(filaBusquedaActiva, referencia);
    modalBusquedaReferencia.hide();
  });
  dissminSwal();
});