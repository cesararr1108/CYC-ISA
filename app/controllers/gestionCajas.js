const LINK = '../models/gestionCajas.php';

const convertirAMayusculas = (form) => {
  const inputs = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
  inputs.forEach((input) => {
    input.value = (input.value || '').toUpperCase();
  });
};

const notificar = (icon, title, text) => Swal.fire({ icon, title, text });

const ajustarAnchoTablas = () => {
  if (!$.fn.DataTable) {
    return;
  }
  $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
};

const cargarCuentasCajaBanco = async () => {
  const resp = await enviarPeticion({ link: LINK, op: 'listar-cuentas' });
  const options = ['<option value="">Seleccione una cuenta</option>'];
  (resp.data || []).forEach((item) => {
    options.push(`<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion}</option>`);
  });

  $('#cuenta').html(options.join('')).trigger('change');
};

const cargarCuentasConceptos = async () => {
  const resp = await enviarPeticion({ link: LINK, op: 'listar-cuentas-conceptos' });
  const options = ['<option value="">Seleccione una cuenta</option>'];
  (resp.data || []).forEach((item) => {
    options.push(`<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion}</option>`);
  });

  $('#concepto-cuenta').html(options.join('')).trigger('change');
};

const cargarBodegas = async () => {
  const resp = await enviarPeticion({ link: LINK, op: 'listar-bodegas' });
  const options = ['<option value="0">Todos los almacenes</option>'];
  (resp.data || []).forEach((item) => {
    options.push(`<option value="${item.id}">${item.descripcion}</option>`);
  });
  $('#concepto-id-bodega').html(options.join('')).val('0').trigger('change');
};

const guardarCajaBanco = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software');
  try {
    data.link = LINK;
    data.op = (!data.id || data.id === '0') ? 'guardar-caja-banco' : 'editar-caja-banco';

    const resp = await enviarFormData(data);
    dissminSwal();

    if (resp.error) {
      notificar('warning', 'Atención', resp.message);
      return;
    }

    notificar('success', 'Correcto', resp.message);
    $('#form-nueva-cuenta')[0].reset();
    $('#id').val('0');
    $('#cuenta').val('').trigger('change');
    $('#mdl-nueva-cuenta').modal('hide');
    await listarCajas();
  } catch (e) {
    dissminSwal();
    console.error(e);
    notificar('error', 'Error en la petición', e.message || 'Error inesperado');
  }
};

const listarCajas = async () => {
  try {
    const resp = await enviarPeticion({ link: LINK, op: 'listar-caja-banco' });
    if (!resp.data || resp.data.length === 0) {
      $('#result-cajas').html(ShowAlert('No se encontraron resultados', 'warning'));
      return;
    }

    $('#result-cajas').html('<table id="tabla-caja-bancos" class="table table-sm table-borderless w-100" style="width:100%"><thead class="table-primary"></thead></table>');
    createDataTable(
      'tabla-caja-bancos',
      resp.data,
      ['id', 'nombre', 'cuenta', 'descripcion', 'fecha'],
      ['Id', 'Nombre', 'Cuenta', 'Descripcion', 'Fecha'],
      editarCaja,
      eliminarCaja,
      false,
      false,
      false,
      false
    );
    ajustarAnchoTablas();
  } catch (e) {
    console.error(e);
    $('#result-cajas').html(ShowAlert('Error interno de la aplicación', 'error'));
  }
};

const editarCaja = (data) => {
  $('#nombre').val(data.nombre || '');
  $('#cuenta').val(data.cuenta || '').trigger('change');
  $("#form-nueva-cuenta [name='saldo_inicial']").val(data.saldo_inicial || 0);
  $('#descripcion').val(data.descripcion || '');
  $('#id').val(data.id || '0');
  $('#mdl-nueva-cuenta .modal-title').text('Editar cuenta');
  $('#mdl-nueva-cuenta button[type="submit"]').text('Actualizar');
  $('#mdl-nueva-cuenta').modal('show');
};

const eliminarCaja = (data) => {
  showConfirmation().then(async (confirmed) => {
    if (!confirmed) {
      return;
    }
    const resp = await enviarPeticion({
      link: LINK,
      op: 'eliminar-caja-banco',
      id: data.id,
      cuenta: data.cuenta
    });

    if (resp.error) {
      notificar('error', 'Error', resp.message);
      return;
    }

    await listarCajas();
    notificar('success', 'Ok', resp.message);
  }).catch(() => {
    notificar('error', 'Error', 'Se generó un error interno en el sistema');
  });
};

const guardarConceptoCaja = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software');
  try {
    data.link = LINK;
    data.op = (!data.id || data.id === '0') ? 'guardar-concepto-caja' : 'editar-concepto-caja';

    const resp = await enviarFormData(data);
    dissminSwal();
    if (resp.error) {
      notificar('warning', 'Atención', resp.message);
      return;
    }

    notificar('success', 'Correcto', resp.message);
    $('#form-concepto-caja')[0].reset();
    $('#concepto-id').val('0');
    $('#concepto-id-bodega').val('0').trigger('change');
    $('#concepto-cuenta').val('').trigger('change');
    $('#mdl-concepto-caja').modal('hide');
    await listarConceptosCajas();
  } catch (e) {
    dissminSwal();
    console.error(e);
    notificar('error', 'Error en la petición', e.message || 'Error inesperado');
  }
};

const listarConceptosCajas = async () => {
  try {
    const resp = await enviarPeticion({
      link: LINK,
      op: 'listar-conceptos-cajas',
      filtro: $('#filtro-conceptos').val() || ''
    });

    if (!resp.data || resp.data.length === 0) {
      $('#result-conceptos').html(ShowAlert('No se encontraron resultados', 'warning'));
      return;
    }

    $('#result-conceptos').html('<table id="tabla-conceptos-cajas" class="table table-sm table-borderless w-100" style="width:100%"><thead class="table-primary"></thead></table>');
    createDataTable(
      'tabla-conceptos-cajas',
      resp.data,
      ['id', 'bodega', 'cuenta', 'concepto', 'descripcion', 'tipo'],
      ['Id', 'Bodega', 'Cuenta', 'Concepto', 'Descripcion', 'Tipo'],
      editarConcepto,
      eliminarConcepto,
      false,
      false,
      false,
      false
    );
    ajustarAnchoTablas();
  } catch (e) {
    console.error(e);
    $('#result-conceptos').html(ShowAlert('Error interno de la aplicación', 'error'));
  }
};

const editarConcepto = (data) => {
  $('#concepto-id').val(data.id || '0');
  $('#concepto-id-bodega').val(String(data.id_bodega ?? '0')).trigger('change');
  $('#concepto-cuenta').val(data.cuenta || '').trigger('change');
  $('#concepto-nombre').val(data.concepto || '');
  $('#concepto-descripcion').val(data.descripcion || '');
  $('#concepto-tipo').val(data.tipo || 'I');
  $('#mdl-concepto-caja .modal-title').text('Editar concepto');
  $('#mdl-concepto-caja button[type="submit"]').text('Actualizar');
  $('#mdl-concepto-caja').modal('show');
};

const eliminarConcepto = (data) => {
  showConfirmation().then(async (confirmed) => {
    if (!confirmed) {
      return;
    }
    const resp = await enviarPeticion({
      link: LINK,
      op: 'eliminar-concepto-caja',
      id: data.id
    });

    if (resp.error) {
      notificar('error', 'Error', resp.message);
      return;
    }

    await listarConceptosCajas();
    notificar('success', 'Ok', resp.message);
  }).catch(() => {
    notificar('error', 'Error', 'Se generó un error interno en el sistema');
  });
};

$(async function () {
  try {
    await cargarCuentasCajaBanco();
    await cargarCuentasConceptos();
    await cargarBodegas();
    dissminSwal();
  } catch (e) {
    dissminSwal();
    notificar('error', 'Error', 'No se pudieron cargar las listas iniciales.');
  }

  $('#cuenta').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    dropdownParent: $('#mdl-nueva-cuenta')
  });

  $('#concepto-id-bodega, #concepto-cuenta').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    dropdownParent: $('#mdl-concepto-caja')
  });

  const forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach((form) => {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      convertirAMayusculas(form);
      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      const formId = String(form.getAttribute('id') || '');
      const data = formatearArrayRequest($(form).serializeArray());

      if (formId === 'form-nueva-cuenta') {
        guardarCajaBanco(data);
      } else if (formId === 'form-concepto-caja') {
        guardarConceptoCaja(data);
      }
    }, false);
  });

  $('#btn-add-caja').click(function () {
    $('#form-nueva-cuenta')[0].reset();
    $('#id').val('0');
    $('#cuenta').val('').trigger('change');
    $('#mdl-nueva-cuenta button[type="submit"]').text('Crear');
    $('#mdl-nueva-cuenta .modal-title').text('Crear cuenta');
    $('#mdl-nueva-cuenta').modal('show');
  });

  $('#btn-add-concepto').click(function () {
    $('#form-concepto-caja')[0].reset();
    $('#concepto-id').val('0');
    $('#concepto-id-bodega').val('0').trigger('change');
    $('#concepto-cuenta').val('').trigger('change');
    $('#concepto-tipo').val('I');
    $('#mdl-concepto-caja button[type="submit"]').text('Crear');
    $('#mdl-concepto-caja .modal-title').text('Crear concepto');
    $('#mdl-concepto-caja').modal('show');
  });

  $('#btn-buscar-conceptos').click(function () {
    listarConceptosCajas();
  });

  $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    setTimeout(ajustarAnchoTablas, 0);
  });

  $(window).on('resize', function () {
    ajustarAnchoTablas();
  });

  await listarCajas();
  await listarConceptosCajas();
  dissminSwal();
});
