// ============================================
// CENTROS CONTROLLER - Refactorizado
// ============================================

const link_ = 'api/centros';
let usuarioActual = null;

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Ejecuta una acción de CRUD
 */
const ejecutarAccion = async (data, accion = 'crear') => {
  try {
    data.link = `${url_api}${link_}/${accion}`;
    const resp = await enviarPeticion(data);

    if (resp.error) {
      Swal.fire("Error", resp.message || "Se generó un error al guardar", "error");
      return false;
    }

    Swal.fire("Éxito", resp.message || "Operación completada", "success");
    return true;
  } catch (error) {
    console.error(`Error en ${accion}:`, error);
    Swal.fire("Error", "Error interno de la aplicación", "error");
    return false;
  }
};

/**
 * Elimina un registro con confirmación
 */
const eliminarRegistro = async (id) => {
  try {
    const confirmed = await showConfirmation();
    if (!confirmed) return false;

    const resp = await enviarPeticion({
      link: `${url_api}${link_}/eliminar`,
      id: id
    });

    if (resp.error) {
      Swal.fire("Error", "No se pudo eliminar el registro", "error");
      return false;
    }

    Swal.fire("Éxito", "Se eliminó correctamente el registro", "success");
    return true;
  } catch (error) {
    console.error('Error al eliminar:', error);
    Swal.fire("Error", "Se generó un error interno en el sistema", "error");
    return false;
  }
};

// ============================================
// CALLBACKS DEL DATATABLE
// ============================================

const editCallback = (data) => {
  if (!data || !data.id) return;
  
  limpiarFormulario('FormCrear');
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
};

const deleteCallback = async (data) => {
  if (!data || !data.id) return;

  const eliminado = await eliminarRegistro(data.id);
  if (eliminado) {
    await listarCentros();
  }
};

// ============================================
// LISTAR CENTROS
// ============================================

const listarCentros = async () => {
  try {
    showLoadingWithTetrisLogo('Buscando información...', 'ISA Software');

    const data = {
      link: `${url_api}${link_}/buscarcriterios`,
      filtro: $("#filtro").val() || ''
    };

    const resp = await enviarPeticion(data);

    if (!resp || !resp.data || resp.data.length === 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    const columnas = ['centro', 'titulo', 'descripcion', 'fecha_creacion', 'inactivo_des'];
    const encabezados = ['Centro', 'Título', 'Descripción', 'Fecha Creación', 'Estado'];

    $("#result").html(`<table id="centros" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`);

    createDataTable('centros', resp.data, columnas, encabezados, editCallback, deleteCallback);
    dissminSwal();

  } catch (error) {
    console.error('Error al listar centros:', error);
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'));
    dissminSwal();
  }
};

// ============================================
// CREAR/EDITAR CENTROS
// ============================================

const CrearCentros = async (data) => {
  try {
    const accion = $("#txtId").val() && $("#txtId").val() !== '0' ? 'editar' : 'crear';
    data.usuario_creacion = usuarioActual?.data?.id || 0;

    const exitoso = await ejecutarAccion(data, accion);

    if (exitoso) {
      limpiarFormulario('FormCrear');
      await listarCentros();
    }
  } catch (error) {
    console.error('Error al crear/editar centro:', error);
    Swal.fire("Error", "Error al procesar la solicitud", "error");
  }
};

// ============================================
// BUSCAR CENTRO
// ============================================

const buscarCentro = async (centro) => {
  if (!centro || centro.trim() === '') {
    limpiarFormulario('FormCrear');
    return;
  }

  try {
    const data = {
      centro: centro.trim(),
      link: `${url_api}${link_}/buscarcentro`
    };

    const resp = await enviarPeticion(data);

    if (resp && resp.data && resp.data.length > 0) {
      limpiarFormulario('FormCrear');
      ShowDataFrom(resp.data[0], 'FormCrear');
    } else {
      limpiarFormulario('FormCrear');
      $("#txtCentro").val(centro);
    }
  } catch (error) {
    console.error('Error al buscar centro:', error);
  }
};

// ============================================
// INICIALIZACIÓN
// ============================================

const inicializarFormulario = () => {
  const forms = document.querySelectorAll('.needs-validation');

  Array.from(forms).forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      form.classList.remove('was-validated');
      const data = formatearArrayRequest($(form).serializeArray());
      await CrearCentros(data);
    });
  });
};

const vincularEventos = () => {
  $("#buscar").on('click', listarCentros);
  $("#btnNuevo").on('click', () => limpiarFormulario('FormCrear'));
  $("#txtCentro").on('focusout', function () {
    buscarCentro($(this).val());
  });
};

// ============================================
// EJECUTAR AL CARGAR
// ============================================

(async function () {
  try {
    // Obtener usuario actual
    usuarioActual = await GetDataUser();
    
    if (!usuarioActual) {
      console.warn('Usuario no disponible');
    }

    // Inicializar formulario y eventos
    inicializarFormulario();
    vincularEventos();

    // Establecer foco inicial
    $("#txtTipo").focus();

    // Cerrar loader
    dissminSwal();

  } catch (error) {
    console.error('Error al inicializar:', error);
    Swal.fire("Error", "Error al inicializar la página", "error");
    dissminSwal();
  }
})();