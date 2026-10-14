const LINK_NOMINA_ENTIDADES = '../models/nominaEntidades.php';

const notificarError = (mensaje) => {
  Swal.fire('Error', mensaje || 'Se genero un error interno de la aplicacion', 'error');
};

const serializarForm = (formSelector) => {
  return formatearArrayRequest($(formSelector).serializeArray());
};

const limpiarEntidadTercero = () => {
  $('#txtIdTerceroEntidad').val('0');
  $('#txtNitEntidad').val('');
  $('#txtNombreEntidad').val('');
};

const aplicarEntidadTercero = (item) => {
  if (!item) {
    return;
  }

  const nombre = (
    (item.primer_nombre || '') + ' ' +
    (item.segundo_nombre || '') + ' ' +
    (item.primer_apellido || '') + ' ' +
    (item.segundo_apellido || '')
  ).trim();

  $('#txtIdTerceroEntidad').val(item.id || '0');
  $('#txtNitEntidad').val(item.nit || '');
  $('#txtNombreEntidad').val((item.razon_comercial || '').trim() !== '' ? item.razon_comercial : nombre);
  $('#txtBusquedaEntidadTercero').val(item.value || `${item.nit || ''} - ${$('#txtNombreEntidad').val()}`.trim());
};

const configurarBusquedaEntidadTercero = () => {
  $('#txtBusquedaEntidadTercero').autocomplete({
    minLength: 2,
    source: async function (request, response) {
      try {
        const resp = await enviarPeticion({
          link: LINK_NOMINA_ENTIDADES,
          op: 'buscar_terceros_general',
          filtro: request.term
        });
        response(resp.data || []);
      } catch (e) {
        console.error(e);
        response([]);
      }
    },
    select: function (_event, ui) {
      aplicarEntidadTercero(ui.item);
      return false;
    }
  }).autocomplete('instance')._renderItem = function (ul, item) {
    const nombre = (
      (item.primer_nombre || '') + ' ' +
      (item.segundo_nombre || '') + ' ' +
      (item.primer_apellido || '') + ' ' +
      (item.segundo_apellido || '')
    ).trim();

    return $('<li>')
      .append(`<div>${item.nit || ''} - ${(item.razon_comercial || '').trim() !== '' ? item.razon_comercial : nombre}</div>`)
      .appendTo(ul);
  };

  $('#txtBusquedaEntidadTercero').on('keyup', function () {
    if ($.trim($(this).val()) === '') {
      limpiarEntidadTercero();
    }
  });
};

const editarEntidad = (data) => {
  limpiarFormulario('FormEntidades');
  ShowDataFrom(data, 'FormEntidades');

  const nombre = ((data.razon_comercial || '').trim() !== '')
    ? data.razon_comercial
    : (data.tercero_nombre || '');

  $('#txtIdTerceroEntidad').val(data.id_tercero || '0');
  $('#txtNitEntidad').val(data.nit || '');
  $('#txtNombreEntidad').val(nombre);
  $('#txtBusquedaEntidadTercero').val(`${data.nit || ''} - ${nombre}`.trim());
  $('#txtIdEntidadVisual').val(data.id || 'Automático');
  mostrarTabEntidad('#tabEntidadCrear');
};

const mostrarTabEntidad = (target) => {
  const btn = document.querySelector(`[data-bs-target="${target}"]`);
  if (btn && typeof bootstrap !== 'undefined') {
    bootstrap.Tab.getOrCreateInstance(btn).show();
  }
};

const nuevaEntidad = () => {
  limpiarFormulario('FormEntidades');
  $('#txtIdEntidad').val('0');
  $('#txtIdEntidadVisual').val('Automático');
  $('#txtBusquedaEntidadTercero').val('');
  limpiarEntidadTercero();
  $('#FormEntidades').removeClass('was-validated');
};

const eliminarEntidad = async (data) => {
  try {
    const confirmed = await showConfirmation();
    if (!confirmed) {
      return;
    }

    const resp = await enviarPeticion({
      link: LINK_NOMINA_ENTIDADES,
      op: 'entidades_delete',
      id: data.id
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await listarEntidades();
  } catch (e) {
    console.error(e);
    notificarError('No se pudo eliminar el registro');
  }
};

const listarEntidades = async () => {
  // Solo se consulta cuando la pestaña "Entidades registradas" está visible
  if (!$('#tabEntidadLista').hasClass('active')) {
    return;
  }
  showLoadingWithTetrisLogo('Buscando informacion', 'ISA Software');

  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_ENTIDADES,
      op: 'entidades_listar',
      filtro: $('#filtroEntidades').val()
    });

    if (!resp.data || resp.data.length === 0) {
      $('#resultEntidades').html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $('#resultEntidades').html('<table id="tablaEntidades" class="table table-sm table-borderless table-hover"><thead class="table-primary"></thead></table>');

    createDataTable(
      'tablaEntidades',
      resp.data,
      ['id', 'tipo_entidad', 'codigo_entidad', 'nit', 'razon_comercial', 'tercero_nombre', 'contratos', 'estado'],
      ['Id', 'Tipo', 'Código', 'NIT', 'Razón comercial', 'Nombre', 'Contratos', 'Estado'],
      editarEntidad,
      eliminarEntidad
    );

    dissminSwal();
  } catch (e) {
    console.error(e);
    $('#resultEntidades').html(ShowAlert('Error interno de la aplicacion', 'error'));
    dissminSwal();
  }
};

const guardarEntidad = async () => {
  try {
    const idTerceroEntidad = parseInt($('#txtIdTerceroEntidad').val() || '0', 10);
    if (isNaN(idTerceroEntidad) || idTerceroEntidad <= 0) {
      notificarError('Debe seleccionar un tercero para registrar la entidad');
      return;
    }

    showLoadingWithTetrisLogo('Guardando registro', 'ISA Software');

    const data = serializarForm('#FormEntidades');
    data.link = LINK_NOMINA_ENTIDADES;
    data.op = ($('#txtIdEntidad').val() && $('#txtIdEntidad').val() !== '0') ? 'entidades_update' : 'entidades_agregar';

    const resp = await enviarPeticion(data);
    dissminSwal();

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    nuevaEntidad();

    await listarEntidades();
  } catch (e) {
    console.error(e);
    dissminSwal();
    notificarError('No se pudo guardar el registro');
  }
};

$(function () {
  'use strict';

  configurarBusquedaEntidadTercero();

  const form = document.querySelector('#FormEntidades');
  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (!form.checkValidity()) {
      event.stopPropagation();
      form.classList.add('was-validated');
      return;
    }

    await guardarEntidad();
  }, false);

  $('#btnNuevoentidades').click(nuevaEntidad);

  $('#btnNuevaEntidadDesdeLista').click(function () {
    nuevaEntidad();
    mostrarTabEntidad('#tabEntidadCrear');
  });

  $('#btnLimpiarFiltroEntidades').click(function () {
    $('#filtroEntidades').val('');
    listarEntidades();
  });

  $('#filtroEntidades').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      listarEntidades();
    }
  });

  $('#btnBuscarentidades').click(function () {
    listarEntidades();
  });

  // Las entidades se cargan al entrar a la pestaña de entidades registradas
  $('button[data-bs-target="#tabEntidadLista"]').on('shown.bs.tab', listarEntidades);
  dissminSwal();
});
