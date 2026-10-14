
const link_ = '../models/config.php';
let configData = [];
let modulosData = [];

// ============ CRUD OPERATIONS ============

const listarConfiguraciones = async () => {
  try {
    showLoadingSwalAlert2('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Cargando configuraciones', '<span class="text-primary">Por favor espere!</span>', false);
    
    const resp = await enviarPeticion({
      link: link_,
      op: 'listar'
    });

    configData = Array.isArray(resp) ? resp : [];
    
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

  $("#result").html(`
    <table id="tbConfig" class="table table-sm table-borderless table-hover table-striped" width="100%">
      <thead class="table-primary"></thead>
    </table>
  `);

  createDataTable(
    'tbConfig',
    datos,
    ['id', 'id_modulo', 'descripcion', 'Clave', 'valor'],
    ['ID', 'Módulo', 'Descripción', 'Clave', 'Valor'],
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
  $("#idModuloSelect").val(data.id_modulo).trigger('change');
  $("#descripcionConfig").val(data.descripcion);
  $("#claveConfig").val(data.Clave);
  $("#valorConfig").val(data.valor);
  
  $("#formTitulo").text(`Editar Configuración - ${data.descripcion}`);
  $("#formularioContainer").slideDown(300);
  
  // Scroll al formulario
  $('html, body').animate({
    scrollTop: $("#formularioContainer").offset().top - 100
  }, 500);
};

const deleteCallback = async (data) => {
  const confirmacion = await showConfirmation(
    `¿Eliminar la configuración "${data.descripcion}"?`,
    'Esta acción no se puede deshacer',
    'question'
  );

  if (confirmacion) {
    try {
      showLoadingSwalAlert2('Eliminando...', '', false);
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
        dissminSwal();
      }
    } catch (e) {
      Swal.fire('Error', 'Ocurrió un error al eliminar', 'error');
      dissminSwal();
    }
  }
};

// ============ FORM OPERATIONS ============

const guardarConfiguracion = async (formData) => {
  try {
    showLoadingSwalAlert2('<i class="fa-solid fa-spinner fa-spin-pulse"></i> Guardando configuración', '<span class="text-primary">Por favor espere!</span>', false);
    
    const idConfig = $("#idConfig").val();
    formData.link = link_;
    formData.op = idConfig == 0 ? 'g_config' : 'u_config';
    
    if (idConfig != 0) {
      formData.id = idConfig;
    }

    const resp = await enviarPeticion(formData);

    if (resp && !resp.error) {
      initToast('bg-success', 'Éxito', resp.message || 'Configuración guardada correctamente', 'success');
      limpiarFormulario();
      listarConfiguraciones();
    } else {
      initToast('bg-danger', 'Error', resp?.message || 'No se pudo guardar', 'danger');
      dissminSwal();
    }
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Ocurrió un error al guardar', 'error');
    dissminSwal();
  }
};

const limpiarFormulario = () => {
  $("#formConfig").removeClass('was-validated');
  $("#idConfig").val(0);
  $("#idModuloSelect").val('').trigger('change');
  $("#descripcionConfig").val('');
  $("#claveConfig").val('');
  $("#valorConfig").val('');
  $("#formTitulo").text('Nueva Configuración');
  $("#formularioContainer").slideUp(300);
};

// ============ LOAD DATA ============

const cargarModulos = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Modulos.php',
      op: 'listar'
    });

    modulosData = Array.isArray(resp) ? resp : [];

    let options = '<option value="">Seleccionar módulo</option>';
    modulosData.forEach(mod => {
      options += `<option value="${mod.id}">${mod.titulo}</option>`;
    });

    $("#idModuloSelect").html(options);

    // Cargar filtro de módulos
    let filterOptions = '<option value="">Todos los módulos</option>';
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
    const cumpleFiltroModulo = !moduloFilter || String(item.id_modulo) === moduloFilter;
    const cumpleFiltroTexto = !busquedaFilter || 
      String(item.descripcion).toLowerCase().includes(busquedaFilter) ||
      String(item.Clave).toLowerCase().includes(busquedaFilter);

    return cumpleFiltroModulo && cumpleFiltroTexto;
  });

  renderTablaConfiguraciones(datosFiltrados);
};

// ============ EVENTS ============

$(async function () {
  await cargarModulos();
  await listarConfiguraciones();

  // Botón Nueva
  $("#btnNueva").on('click', function () {
    limpiarFormulario();
    $("#formularioContainer").slideDown(300);
  });

  // Botón Cancelar
  $("#btnCancelar").on('click', function () {
    limpiarFormulario();
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

    const data = formatearArrayRequest($(this).serializeArray());
    guardarConfiguracion(data);
  });

  // Filtros
  $("#filtroModulo, #filtroBusqueda").on('change input', function () {
    aplicarFiltros();
  });
});