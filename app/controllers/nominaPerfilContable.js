const LINK_NOMINA_PERFILES = '../models/nominaPerfilContable.php';

const notificarError = (mensaje) => {
  Swal.fire('Error', mensaje || 'Se genero un error interno de la aplicacion', 'error');
};

const serializarForm = (formSelector) => {
  return formatearArrayRequest($(formSelector).serializeArray());
};

const inicializarSelect2Nomina = () => {
  if (!$.fn.select2) {
    return;
  }

  $('.nomina-cuenta').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    placeholder: 'Escoja una cuenta',
    language: {
      noResults: function () {
        return 'No se encontraron resultados';
      }
    }
  });
};

const cargarCuentasPerfilContable = async () => {
  showLoadingWithTetrisLogo('Cargando plan de cuentas', 'ISA Software');

  try {
    const resp = await enviarPeticion({
      link: '../models/cuentasContables.php',
      op: 'listar-todos'
    });

    if (!resp.data || resp.data.length === 0) {
      dissminSwal();
      Swal.fire('Imposible continuar', 'No hay un plan de cuentas cargado para asociar el perfil contable.', 'info');
      return;
    }

    let html = '<option value=""></option>';
    resp.data.forEach((item) => {
      html += `<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion}</option>`;
    });

    $('.nomina-cuenta').html(html).trigger('change');
    inicializarSelect2Nomina();
    dissminSwal();
  } catch (e) {
    console.error(e);
    dissminSwal();
    notificarError('No se pudo cargar el plan de cuentas');
  }
};

const editarPerfil = (data) => {
  limpiarFormulario('FormPerfiles');
  ShowDataFrom(data, 'FormPerfiles');
  setTimeout(() => {
    $('.nomina-cuenta').trigger('change.select2');
  }, 0);
  const btn = document.querySelector('[data-bs-target="#tabPerfilCrear"]');
  if (btn && typeof bootstrap !== 'undefined') {
    bootstrap.Tab.getOrCreateInstance(btn).show();
  }
};

// Oculta los campos cuya columna aún no existe (migración pendiente) para no perder datos al guardar
const ajustarCamposPerfil = async () => {
  try {
    const resp = await enviarPeticion({ link: LINK_NOMINA_PERFILES, op: 'campos_perfil' });
    const columnas = Array.isArray(resp.data) ? resp.data : [];
    if (!columnas.length) return;
    $('#FormPerfiles .nomina-cuenta').each(function () {
      if (!columnas.includes($(this).attr('name'))) {
        $(this).closest('[class*="col-"]').addClass('d-none');
        $(this).prop('disabled', true);
      }
    });
  } catch (e) {
    console.error(e);
  }
};

const eliminarPerfil = async (data) => {
  try {
    const confirmed = await showConfirmation();
    if (!confirmed) {
      return;
    }

    const resp = await enviarPeticion({
      link: LINK_NOMINA_PERFILES,
      op: 'perfiles_delete',
      id: data.id
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await listarPerfiles();
  } catch (e) {
    console.error(e);
    notificarError('No se pudo eliminar el registro');
  }
};

const listarPerfiles = async () => {
  // Solo se consulta cuando la pestaña "Perfiles registrados" está visible
  if (!$('#tabPerfilLista').hasClass('active')) {
    return;
  }
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_PERFILES,
      op: 'perfiles_listar',
      filtro: $('#filtroPerfiles').val()
    });

    if (!resp.data || resp.data.length === 0) {
      $('#resultPerfiles').html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $('#resultPerfiles').html('<table id="tablaPerfiles" class="table table-sm table-borderless table-hover"><thead class="table-primary"></thead></table>');

    createDataTable(
      'tablaPerfiles',
      resp.data,
      ['id', 'codigo', 'descripcion', 'cuenta_gasto_sueldo', 'cuenta_salario_por_pagar', 'cuenta_pasivo_salud', 'cuenta_pasivo_pension', 'cuenta_gasto_cesantias', 'cuenta_gasto_prima', 'contratos', 'estado'],
      ['Id', 'Código', 'Descripción', 'Gasto sueldo', 'Salario x pagar', 'Pasivo salud', 'Pasivo pensión', 'Gasto cesantías', 'Gasto prima', 'Contratos', 'Estado'],
      editarPerfil,
      eliminarPerfil
    );

    dissminSwal();
  } catch (e) {
    console.error(e);
    $('#resultPerfiles').html(ShowAlert('Error interno de la aplicacion', 'error'));
    dissminSwal();
  }
};

const guardarPerfil = async () => {
  try {
    showLoadingWithTetrisLogo('Guardando registro', 'ISA Software');

    const data = serializarForm('#FormPerfiles');
    data.link = LINK_NOMINA_PERFILES;
    data.op = ($('#txtIdPerfil').val() && $('#txtIdPerfil').val() !== '0') ? 'perfiles_update' : 'perfiles_agregar';

    const resp = await enviarPeticion(data);
    dissminSwal();

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    limpiarFormulario('FormPerfiles');
    $('#txtIdPerfil').val('0');
    $('.nomina-cuenta').val('').trigger('change');

    await listarPerfiles();
  } catch (e) {
    console.error(e);
    dissminSwal();
    notificarError('No se pudo guardar el registro');
  }
};

$(function () {
  cargarCuentasPerfilContable().then(ajustarCamposPerfil);

  const form = document.querySelector('#FormPerfiles');
  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (!form.checkValidity()) {
      event.stopPropagation();
      form.classList.add('was-validated');
      return;
    }

    await guardarPerfil();
  }, false);

  $('#btnNuevoperfiles').click(function () {
    limpiarFormulario('FormPerfiles');
    $('#txtIdPerfil').val('0');
    $('.nomina-cuenta').val('').trigger('change');
  });

  $('#btnBuscarperfiles').click(function () {
    listarPerfiles();
  });

  // Los perfiles se cargan al entrar a la pestaña de perfiles registrados
  $('button[data-bs-target="#tabPerfilLista"]').on('shown.bs.tab', listarPerfiles);
  $('#filtroPerfiles').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      listarPerfiles();
    }
  });
  dissminSwal();
});
