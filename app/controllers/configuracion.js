
const link_ = '../models/configuracion.php';
let configData = [];
let modulosData = [];
let usuarioAutorizadoGlobal = false;  // Variable global para almacenar estado de autorización

// ============ CRUD OPERATIONS ============

const listarConfiguraciones = async () => {
  try {
    showLoadingWithTetrisLogo('Cargando configuraciones', 'ISA Software')
    const resp = await enviarPeticion({
      link: link_,
      op: 'listar'
    });

    configData = Array.isArray(resp.data) ? resp.data : [];

    if (configData.length === 0) {
      $("#result").html(ShowAlert('No hay configuraciones registradas', 'info'));
      dissminSwal();
      return;
    }

    renderTablaConfiguraciones(configData);
    dissminSwal();
  } catch (e) {
    console.error(e);
    $("#result").html(ShowAlert('Error al cargar configuraciones', 'error'));
    dissminSwal();
  }
};

const renderTablaConfiguraciones = (datos) => {
  if (!datos || datos.length === 0) {
    $("#result").html(ShowAlert('No hay datos para mostrar', 'info'));
    return;
  }

  // Formatear datos para mostrar "Global" cuando id_modulo = 0
  const datosFormateados = datos.map(item => ({
    ...item,
    modulo: item.id_modulo === 0 || item.id_modulo === '0' ? '🌐 Global' : item.nombre_modulo || item.id_modulo
  }));

  $("#result").html(`
    <table id="tbConfig" class="table table-sm table-borderless table-hover table-striped" width="100%">
      <thead class="table-primary"></thead>
    </table>
  `);

  createDataTable(
    'tbConfig',
    datosFormateados,
    ['id', 'id_modulo', 'modulo', 'descripcion', 'Clave', 'valor'],
    ['ID', 'ID_MODULO', 'Módulo', 'Descripción', 'Clave', 'Valor'],
    editCallback,
    deleteCallback,
    false,
    false,
    false,
    true
  );
};

const editCallback = (data) => {
  $("#idConfig").val(data.id);
  // Convertir a string para asegurar que "0" sea seleccionado correctamente
  $("#idModuloSelect").val(String(data.id_modulo)).trigger('change');
  $("#descripcionConfig").val(data.descripcion);
  $("#claveConfig").val(data.Clave);
  $("#valorConfig").val(data.valor);
  $("#validacionConfig").val(data.validacion || '');
  $("#formTitulo").text(`Editar Configuración`);

  // Habilitar/deshabilitar campos según autorización
  if (usuarioAutorizadoGlobal) {
    // Usuario autorizado - puede editar todos los campos
    $("#idModuloSelect").prop('disabled', false);
    $("#descripcionConfig").prop('disabled', false);
    $("#claveConfig").prop('disabled', false);
    $("#valorConfig").prop('disabled', false);
    $("#validacionConfig").prop('disabled', false);
    $("#idModuloSelect").removeAttr('title');
    $("#descripcionConfig").removeAttr('title');
    $("#claveConfig").removeAttr('title');
  } else {
    // Usuario no autorizado - solo puede editar valorConfig
    $("#idModuloSelect").prop('disabled', true).attr('title', 'Solo usuarios autorizados pueden editar este campo');
    $("#descripcionConfig").prop('disabled', true).attr('title', 'Solo usuarios autorizados pueden editar este campo');
    $("#claveConfig").prop('disabled', true).attr('title', 'Solo usuarios autorizados pueden editar este campo');
    $("#validacionConfig").prop('disabled', true).attr('title', 'Solo usuarios autorizados pueden editar este campo');
    $("#valorConfig").prop('disabled', false).removeAttr('title');
    $("#divValidacionConfig").hide();
  }

  // Mostrar modal
  const modal = new bootstrap.Modal(document.getElementById('modalConfiguracion'));
  modal.show();
};

const deleteCallback = async (data) => {
  // Validar si el usuario está autorizado
  if (!usuarioAutorizadoGlobal) {
    Swal.fire('Acceso denegado', 'Solo usuarios autorizados pueden eliminar configuraciones', 'warning');
    return;
  }

  const confirmacion = await showConfirmation(
    `¿Eliminar la configuración "${data.descripcion}"?`,
    'Esta acción no se puede deshacer',
    'warning'
  );

  if (confirmacion) {
    try {
      showLoadingWithTetrisLogo('Eliminando configuración', 'ISA Software');
      const resp = await enviarPeticion({
        link: link_,
        op: 'd_config',
        id: data.id
      });

      if (resp && !resp.error) {
        Swal.fire('Éxito', resp.message || 'Configuración eliminada', 'success');
        listarConfiguraciones();
      } else {
        Swal.fire('Error', resp?.message || 'No se pudo eliminar', 'error');
      }
    } catch (e) {
      Swal.fire('Error', 'Ocurrió un error al eliminar', 'error');
    }
  }
};

const guardarConfiguracion = async (formData) => {
  try {
    // Mostrar confirmación
    const confirmacion = await showConfirmation(
      '¿Deseas guardar esta configuración?',
      'Verifica que todos los datos sean correctos',
      'question'
    );

    if (!confirmacion) {
      return;
    }

    if ($("#validacionConfig").val() != '') {
      try {
        const validacion = JSON.parse($("#validacionConfig").val());
        const valor = $("#valorConfig").val();
        
        if (validacion.opciones && Array.isArray(validacion.opciones)) {
          // Buscar coincidencia considerando conversión de tipos
          let encontrado = false;
          
          for (let opcion of validacion.opciones) {
            // Comparar como string
            if (String(opcion) === String(valor)) {
              encontrado = true;
              break;
            }
            // Comparar como número si es posible
            if (!isNaN(opcion) && !isNaN(valor)) {
              if (Number(opcion) === Number(valor)) {
                encontrado = true;
                break;
              }
            }
          }
          
          if (!encontrado) {
            Swal.fire('Error', `El valor debe ser uno de los siguientes: ${validacion.opciones.join(', ')}`, 'error');
            return;
          }
        }
      } catch (e) {
        Swal.fire('Error', 'El campo de validación debe contener un JSON válido', 'error');
        return;
      }
    }


    showLoadingWithTetrisLogo('Guardando configuración', 'ISA Software')
    // Validar que formData no sea undefined
    if (!formData || typeof formData !== 'object') {
      Swal.fire('Error', 'Datos del formulario inválidos', 'error');
      dissminSwal();
      return;
    }

    // Mapear datos del formulario
    const idConfig = formData.idConfig || 0;
    const requestData = {
      link: link_,
      op: idConfig == 0 ? 'g_config' : 'u_config',
      idModuloSelect: formData.idModuloSelect,
      descripcionConfig: formData.descripcionConfig,
      claveConfig: formData.claveConfig,
      valorConfig: formData.valorConfig,
      validacionConfig: formData.validacionConfig
    };

    // Agregar ID si es actualización
    if (idConfig != 0) {
      requestData.id = idConfig;
    }

    const resp = await enviarPeticion(requestData);
    if (resp && !resp.error) {
      Swal.fire('Éxito', resp.message || 'Configuración guardada correctamente', 'success');
      // Cerrar modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfiguracion'));
      if (modal) {
        modal.hide();
      }
      limpiarFormularioConfiguracion();
      listarConfiguraciones();
    } else {
      Swal.fire('Error', resp?.message || 'No se pudo guardar', 'error');
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Ocurrió un error al guardar', 'error');
  }
};

const limpiarFormularioConfiguracion = () => {
  $("#formConfig").removeClass('was-validated');
  $("#idConfig").val(0);
  $("#idModuloSelect").val('0').trigger('change');
  $("#descripcionConfig").val('');
  $("#claveConfig").val('');
  $("#valorConfig").val('');
  $("#validacionConfig").val('');
  $("#formTitulo").text('Nueva Configuración');

  // Habilitar todos los campos en modo crear
  $("#idModuloSelect").prop('disabled', false).removeAttr('title');
  $("#descripcionConfig").prop('disabled', false).removeAttr('title');
  $("#claveConfig").prop('disabled', false).removeAttr('title');
  $("#valorConfig").prop('disabled', false).removeAttr('title');
  $("#validacionConfig").prop('disabled', false).removeAttr('title');

  // Cerrar modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalConfiguracion'));
  if (modal) {
    modal.hide();
  }
};

// ============ LOAD DATA ============

const cargarModulos = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/modulos.php',
      op: 'listar',
      filtro: ''
    });
    modulosData = Array.isArray(resp.data) ? resp.data : [];

    // Ordenar módulos alfabéticamente por titulo
    modulosData.sort((a, b) => (a.titulo || '').localeCompare(b.titulo || ''));

    // Opción global para todos los módulos (valor 0)
    let options = '<option value="0">🌐 Global (Todos los módulos)</option>';
    modulosData.forEach(mod => {
      options += `<option value="${mod.id}">${mod.titulo}</option>`;
    });

    $("#idModuloSelect").html(options);

    // Cargar filtro de módulos
    let filterOptions = '<option value="">Todos los módulos</option>';
    filterOptions += '<option value="0">🌐 Global (Todas)</option>';
    modulosData.forEach(mod => {
      filterOptions += `<option value="${mod.id}">${mod.titulo}</option>`;
    });
    $("#filtroModulo").html(filterOptions);

  } catch (e) {
    console.error(e);
  }
};

// ============ FILTERS ============

const aplicarFiltros = () => {
  const moduloFilter = $("#filtroModulo").val();
  const busquedaFilter = $("#filtroBusqueda").val().toLowerCase();

  let datosFiltrados = configData.filter(item => {
    // Si filtro es vacío, no filtra por módulo. Si es "0", filtra por global
    const cumpleFiltroModulo = moduloFilter === '' ||
      (moduloFilter === '0' && (item.id_modulo === 0 || item.id_modulo === '0')) ||
      String(item.id_modulo) === moduloFilter;

    const cumpleFiltroTexto = !busquedaFilter ||
      String(item.descripcion).toLowerCase().includes(busquedaFilter) ||
      String(item.Clave).toLowerCase().includes(busquedaFilter);

    return cumpleFiltroModulo && cumpleFiltroTexto;
  });

  renderTablaConfiguraciones(datosFiltrados);
};

// ============ EVENTS ============
const usuarioAutorizado = async () => {
  try {
    const resp = await enviarPeticion({
      link: link_,
      op: 'validar_usuario'
    });
    return resp?.autorizado || false;

  } catch (e) {
    console.error(e);
  }
};

$(async function () {
  // Obtener estado de autorización una sola vez
  usuarioAutorizadoGlobal = await usuarioAutorizado();
  if (usuarioAutorizadoGlobal) {
    $("#btnNueva").removeClass('d-none');
  }

  await cargarModulos();
  await listarConfiguraciones();

  // Botón Nueva
  $("#btnNueva").on('click', function () {
    limpiarFormularioConfiguracion();
    const modal = new bootstrap.Modal(document.getElementById('modalConfiguracion'));
    modal.show();
  });

  // Evento de cierre del modal
  document.getElementById('modalConfiguracion').addEventListener('hidden.bs.modal', function () {
    limpiarFormularioConfiguracion();
  });

  // Submit del formulario
  $("#formConfig").on('submit', function (e) {
    e.preventDefault();

    const form = this;
    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add('was-validated');
      return;
    }

    // Construir objeto de datos directamente del formulario
    const data = {
      idConfig: $("#idConfig").val(),
      idModuloSelect: $("#idModuloSelect").val(),
      descripcionConfig: $("#descripcionConfig").val(),
      claveConfig: $("#claveConfig").val(),
      valorConfig: $("#valorConfig").val(),
      validacionConfig: $("#validacionConfig").val()
    };

    guardarConfiguracion(data);
  });

  // Filtros
  $("#filtroModulo, #filtroBusqueda").on('change input', function () {
    aplicarFiltros();
  });
});