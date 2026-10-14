const LINK_NOMINA_CONTRATOS = '../models/nominaContratos.php';
const LINK_NOMINA_PARAMETROS = '../models/nominaCalculos.php';

let cacheParametrosNomina = [];

const notificarError = (mensaje) => {
  Swal.fire('Error', mensaje || 'Se genero un error interno de la aplicacion', 'error');
};

const limpiarEmpleadoContrato = () => {
  $('#txtIdTerceroContrato').val('0');
  $('#txtDocumentoContrato').val('');
  $('#txtPrimerNombreContrato').val('');
  $('#txtSegundoNombreContrato').val('');
  $('#txtPrimerApellidoContrato').val('');
  $('#txtSegundoApellidoContrato').val('');
};



const cargarEntidadesAfiliacion = async () => {
  const configuraciones = [
    { selector: '#slcEntidadSaludContrato', tipo: 'SALUD' },
    { selector: '#slcEntidadPensionContrato', tipo: 'PENSION' },
    { selector: '#slcEntidadCesantiasContrato', tipo: 'FONDO_CESANTIAS' },
    { selector: '#slcEntidadCajaContrato', tipo: 'CAJA_COMPENSACION' },
    { selector: '#slcEntidadArlContrato', tipo: 'ARL' }
  ];

  try {
    const respuestas = await Promise.all(configuraciones.map((config) => enviarPeticion({
      link: LINK_NOMINA_CONTRATOS,
      op: 'combos_entidades_afiliacion',
      tipo_entidad: config.tipo
    })));

    configuraciones.forEach((config, index) => {
      let html = config.tipo === 'SALUD' ? '<option value="">Seleccione...</option>' : '<option value="0">No aplica</option>';
      const items = respuestas[index].data || [];
      items.forEach((item) => {
        html += `<option value="${item.id}">${item.codigo_entidad} - ${item.nombre}</option>`;
      });
      $(config.selector).html(html);
    });
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar las entidades de afiliacion');
  }
};

const cargarTiposIdentificacionContrato = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/tipoIdentificaciones.php',
      op: 'listar'
    });

    let html = '<option value="">Seleccione...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
        const codigo = item.tipo_identificacion || '';
        const descripcion = item.descripcion || '';
        html += `<option value="${item.id}" data-codigo="${codigo}">${codigo} - ${descripcion}</option>`;
      });
    }

    $('#slcTipoDocContrato').html(html);
  } catch (e) {
    console.error(e);
  }
};

const cargarTiposContratos = async () => {
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_CONTRATOS,
      op: 'combos_tipos_contratos'
    });

    let html = '<option value="">Seleccione...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
        const codigo = item.codigo || '';
        const descripcion = item.descripcion || '';
        html += `<option value="${item.id}">${codigo} - ${descripcion}</option>`;
      });
    }

    $('#slcTipoContratoContrato').html(html);
  } catch (e) {
    console.error(e);
  }
};

const cargarPerfilesContables = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/nominaPerfilContable.php',
      op: 'combos_perfiles'
    });

    let html = '<option value="">Seleccione...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
          html += `<option value="${item.id}">${item.codigo} - ${item.descripcion}</option>`;
      });
    }

    $('#slcPerfilContable').html(html);
  } catch (e) {
    console.error(e);
  }
};

const aplicarEmpleadoContrato = async (item) => {
  if (!item) {
    return;
  }

  $('#txtIdTerceroContrato').val(item.id || '0');
  $('#txtDocumentoContrato').val(item.documento || '');
  $('#txtPrimerNombreContrato').val(item.primer_nombre || '');
  $('#txtSegundoNombreContrato').val(item.segundo_nombre || '');
  $('#txtPrimerApellidoContrato').val(item.primer_apellido || '');
  $('#txtSegundoApellidoContrato').val(item.segundo_apellido || '');

  if (item.id_tipo_identificacion) {
    $('#slcTipoDocContrato').val(item.id_tipo_identificacion);
  }

  const nombre = (
    (item.primer_nombre || '') + ' ' +
    (item.segundo_nombre || '') + ' ' +
    (item.primer_apellido || '') + ' ' +
    (item.segundo_apellido || '')
  ).trim();

  $('#txtBusquedaEmpleadoContrato').val(nombre || item.value || '');

  // Verificar si el empleado ya tiene un contrato
  try {
    const documento = item.documento || '';
    const tipoDoc = item.id_tipo_identificacion || 0;
    
    if (documento && tipoDoc > 0) {
      const resp = await enviarPeticion({
        link: LINK_NOMINA_CONTRATOS,
        op: 'contratos_listar',
        documento
      });

      const activos = (resp.data || []).filter((c) => String(c.estado).toUpperCase() === 'ACTIVO');
      if (activos.length > 0) {
        // El empleado ya tiene un contrato activo
        const contrato = activos[0];
        Swal.fire({
          title: 'Contrato Existente',
          text: `${nombre} ya tiene un contrato activo. ¿Desea editarlo? Para crear uno nuevo, primero inactive o finalice el actual.`,
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Editar',
          cancelButtonText: 'Crear Nuevo',
          allowOutsideClick: false,
          allowEscapeKey: false
        }).then((result) => {
          if (result.isConfirmed) {
            // Editar el contrato existente
            editarContrato(contrato);
          } else {
            // Crear nuevo contrato para el mismo empleado
            mostrarTabCrearContrato();
          }
        });
        return;
      }
    }
  } catch (e) {
    console.error('Error al verificar contrato existente:', e);
  }

  // Si no hay contrato, mostrar tab de creación
  mostrarTabCrearContrato();
};

const configurarBusquedaEmpleadoContrato = () => {
  $('#txtBusquedaEmpleadoContrato').autocomplete({
    minLength: 2,
    source: async function (request, response) {
      try {
        const resp = await enviarPeticion({
          link: LINK_NOMINA_CONTRATOS,
          op: 'buscar_terceros_empleado',
          filtro: request.term
        });
        response(resp.data || []);
      } catch (e) {
        console.error(e);
        response([]);
      }
    },
    select: async function (_event, ui) {
      await aplicarEmpleadoContrato(ui.item);
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
      .append(`<div>${item.documento || ''} - ${nombre || item.razon_comercial || ''}</div>`)
      .appendTo(ul);
  };

  $('#txtBusquedaEmpleadoContrato').on('keyup', function () {
    if ($.trim($(this).val()) === '') {
      limpiarEmpleadoContrato();
    }
  });
};

const serializarForm = (formSelector) => {
  return formatearArrayRequest($(formSelector).serializeArray());
};

const formatearDecimalContrato = (valor) => {
  if (valor === null || valor === undefined || String(valor).trim() === '') {
    return '';
  }

  const numero = (typeof unformatNum === 'function') ? unformatNum(String(valor)) : parseFloat(valor);
  if (Number.isNaN(Number(numero))) {
    return valor;
  }

  return (typeof formatNum === 'function')
    ? formatNum(numero, '', typeof ConfGeneral !== 'undefined' ? ConfGeneral : undefined)
    : Number(numero).toFixed(2);
};

const activarFormatoDecimalesContrato = () => {
  ['#txtSalarioBasicoContrato', '#txtAuxTransporteContrato'].forEach((selector) => {
    $(selector).off('.decimalContrato');

    $(selector).on('focus.decimalContrato', function () {
      const valor = $(this).val();
      if (String(valor || '').trim() !== '' && typeof unformatNum === 'function') {
        $(this).val(unformatNum(String(valor)));
      }
    });

    $(selector).on('blur.decimalContrato', function () {
      $(this).val(formatearDecimalContrato($(this).val()));
    });
  });
};

const obtenerParametroConfiguradoNomina = async (clave) => {
  const anio = new Date().getFullYear();
  const resp = await enviarPeticion({
    link: LINK_NOMINA_PARAMETROS,
    op: 'listar_parametros',
    anio
  });

  if (resp.error) {
    throw new Error(resp.message || 'No se pudo consultar la configuracion de nomina');
  }

  const parametros = Array.isArray(resp.data?.parametros) ? resp.data.parametros : [];
  return parametros.find((item) => String(item.clave || '').toLowerCase() === String(clave).toLowerCase()
    && String(item.estado || 'ACTIVO').toUpperCase() === 'ACTIVO');
};

const aplicarParametroEnCampoContrato = async (clave, selector, etiqueta) => {
  try {
    const parametro = await obtenerParametroConfiguradoNomina(clave);
    if (!parametro) {
      notificarError(`No existe un parametro activo con la clave ${clave} para el año actual`);
      return;
    }

    $(selector).val(formatearDecimalContrato(parametro.valor || 0)).trigger('change');
  } catch (e) {
    console.error(e);
    notificarError(e.message || `No se pudo cargar ${etiqueta.toLowerCase()} desde la configuracion`);
  }
};

const mostrarTabCrearContrato = () => {
  const btnTabCrear = document.querySelector('[data-bs-target="#tabContratoCrear"]');
  if (btnTabCrear && typeof bootstrap !== 'undefined') {
    bootstrap.Tab.getOrCreateInstance(btnTabCrear).show();
  }
};

const editarContrato = (data) => {
  limpiarFormulario('FormContratos');
  ShowDataFrom(data, 'FormContratos');

  $('#slcSubtipoTrabajadorContrato').val(String(data.subtipo_trabajador || '00'));
  $('#chkAltoRiesgoPension').prop('checked', String(data.alto_riesgo || '0') === '1');
  $('#chkSalarioIntegral').prop('checked', String(data.salario_integral || '0') === '1');
  $('#slcClaseRiesgoArl').val(String(data.clase_riesgo_arl || '1'));
  $('#slcEntidadArlContrato').val(String(data.id_entidad_arl || '0'));
  ['salud', 'pension', 'cesantias', 'caja'].forEach((t) => {
    const sel = { salud: '#slcEntidadSaludContrato', pension: '#slcEntidadPensionContrato', cesantias: '#slcEntidadCesantiasContrato', caja: '#slcEntidadCajaContrato' }[t];
    $(sel).val(String(data['id_entidad_' + t] || (t === 'salud' ? '' : '0')));
  });
  $('#txtSalarioBasicoContrato').val(formatearDecimalContrato(data.salario_basico));
  $('#txtAuxTransporteContrato').val(data.aux_transporte === null || data.aux_transporte === undefined ? '' : formatearDecimalContrato(data.aux_transporte));

  const nombre = (
    (data.primer_nombre || '') + ' ' +
    (data.segundo_nombre || '') + ' ' +
    (data.primer_apellido || '') + ' ' +
    (data.segundo_apellido || '')
  ).trim();
  $("#txtDocumentoContrato").val(data.empleado_documento || '');
  $('#txtBusquedaEmpleadoContrato').val(nombre || '');
  $('#txtIdContrato').val(data.id || '0');
  $('#txtIdTerceroContrato').val(data.id_tercero || '0');
  $("#cargo").val(data.cargo || '');
  $("#txtPrimerNombreContrato").val(data.primer_nombre || '');
  $("#txtSegundoNombreContrato").val(data.segundo_nombre || '');
  $("#txtPrimerApellidoContrato").val(data.primer_apellido || '');
  $("#txtSegundoApellidoContrato").val(data.segundo_apellido || '');

  // Cargar datos de pago
  $('#slcMetodoPagoContrato').val(data.metodo_pago || '');
  $('#txtBancoContrato').val(data.banco || '');
  $('#slcTipoCuentaContrato').val(data.tipo_cuenta || '');
  $('#txtNumeroCuentaContrato').val(data.numero_cuenta || '');

  // Trigger change en método de pago para actualizar visibilidad de campos bancarios
  $('#slcMetodoPagoContrato').trigger('change');

  mostrarTabCrearContrato();
};
 
const eliminarContrato = async (data) => {
  try {
    const confirmed = await showConfirmation();
    if (!confirmed) {
      return;
    }

    const resp = await enviarPeticion({
      link: LINK_NOMINA_CONTRATOS,
      op: 'contratos_delete',
      id: data.id
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await listarContratos();
  } catch (e) {
    console.error(e);
    notificarError('No se pudo eliminar el registro');
  }
};

const listarContratos = async () => {
  // Verificar si el tab de lista está activo antes de crear la tabla
  const tabLista = document.querySelector('#tabContratoLista');
  const tabActivo = tabLista?.classList.contains('active');
  
  if (!tabActivo) {
    // Si no está en el tab de lista, solo actualizar cuando se navegue a él
    return;
  }

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');  
  
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_CONTRATOS,
      op: 'contratos_listar',
      filtro: $('#filtroContratos').val()
    });

    if (!resp.data || resp.data.length === 0) {
      $('#resultContratos').html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $('#resultContratos').html('<table id="tablaContratos" class="table table-sm table-borderless table-hover"><thead class="table-primary"></thead></table>');

    const dataTable = (resp.data || []).map((item) => ({
      ...item,
      alto_riesgo_des: String(item.alto_riesgo || '0') === '1' ? 'SI' : 'NO',
      subtipo_trabajador_des: item.subtipo_trabajador || '00'
    }));

    createDataTable(
      'tablaContratos',
      dataTable,
      ['id', 'empleado_documento', 'primer_nombre', 'primer_apellido', 'cargo', 'tipo_contrato_descripcion', 'perfil_contable_codigo', 'eps_nombre', 'salario_basico', 'estado'],
      ['Id', 'Documento', 'Primer nombre', 'Primer apellido', 'Cargo', 'Tipo contrato', 'Perfil', 'EPS', 'Salario', 'Estado'],
      editarContrato,
      eliminarContrato,
      false,
      '',
      '',
      false,
      ['salario_basico']
    );

    dissminSwal();
  } catch (e) {
    console.error(e);
    $('#resultContratos').html(ShowAlert('Error interno de la aplicacion', 'error'));
    dissminSwal();
  }
};

const limpiarFormularioParametroNomina = () => {
  const anioActual = new Date().getFullYear();
  $('#FormParametroNomina')[0]?.reset();
  $('#txtIdParametroNomina').val('0');
  $('#txtAnioParametroNomina').val($('#slcAnioParametrosNomina').val() || anioActual).prop('readonly', false);
  $('#slcTipoParametroNomina').val('VALOR_PESOS');
  $('#slcEstadoParametroNomina').val('ACTIVO');
  $('#txtClaveParametroNomina').prop('readonly', false);
  $('#FormParametroNomina').removeClass('was-validated');
};

const llenarFormularioParametroNomina = (item) => {
  if (!item) {
    return;
  }

  $('#txtIdParametroNomina').val(item.id || '0');
  $('#txtAnioParametroNomina').val(item.anio || new Date().getFullYear()).prop('readonly', true);
  $('#txtClaveParametroNomina').val(item.clave || '').prop('readonly', true);
  $('#txtNombreParametroNomina').val(item.nombre || '');
  $('#txtValorParametroNomina').val(item.valor || '0');
  $('#slcTipoParametroNomina').val(item.tipo || 'VALOR_PESOS');
  $('#slcEstadoParametroNomina').val(item.estado || 'ACTIVO');
  $('#txtDescripcionParametroNomina').val(item.descripcion || '');
};

const renderizarTablaParametrosNomina = (items) => {
  if (!items || items.length === 0) {
    $('#resultParametrosNomina').html(ShowAlert('No hay parametros registrados para el año seleccionado', 'warning'));
    return;
  }

  const filas = items.map((item, index) => `
    <tr>
      <td>${item.anio || ''}</td>
      <td>${item.clave || ''}</td>
      <td>${item.nombre || ''}</td>
      <td class="text-end">${item.valor || '0'}</td>
      <td>${item.tipo || ''}</td>
      <td>${item.estado || ''}</td>
      <td>${item.descripcion || ''}</td>
      <td class="text-nowrap text-center">
        <button type="button" class="btn btn-sm btn-outline-primary btnEditarParametroNomina" data-index="${index}">Editar</button>
        <button type="button" class="btn btn-sm btn-outline-danger btnEliminarParametroNomina" data-id="${item.id || 0}">Inactivar</button>
      </td>
    </tr>
  `).join('');

  $('#resultParametrosNomina').html(`
    <table class="table table-sm table-hover table-bordered align-middle">
      <thead class="table-primary">
        <tr>
          <th>Año</th>
          <th>Clave</th>
          <th>Nombre</th>
          <th>Valor</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th>Descripcion</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>${filas}</tbody>
    </table>
  `);
};

const cargarParametrosNomina = async (anio = null) => {
  const anioConsulta = anio || $('#slcAnioParametrosNomina').val() || new Date().getFullYear();

  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_PARAMETROS,
      op: 'listar_parametros',
      anio: anioConsulta
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    const data = resp.data || {};
    const anios = Array.isArray(data.anios) ? data.anios : [];
    cacheParametrosNomina = Array.isArray(data.parametros) ? data.parametros : [];

    let opciones = '';
    const idsAnios = new Set();

    anios.forEach((row) => {
      const valor = String(row.anio || '').trim();
      if (valor !== '' && !idsAnios.has(valor)) {
        idsAnios.add(valor);
        opciones += `<option value="${valor}">${valor}</option>`;
      }
    });

    if (!idsAnios.has(String(anioConsulta))) {
      opciones += `<option value="${anioConsulta}">${anioConsulta}</option>`;
    }

    $('#slcAnioParametrosNomina').html(opciones);
    $('#slcAnioParametrosNomina').val(String(anioConsulta));
    renderizarTablaParametrosNomina(cacheParametrosNomina);
    const faltantes = Array.isArray(data.faltantes) ? data.faltantes : [];
    if (faltantes.length) {
      $('#resultParametrosNomina').prepend(`<div class="alert alert-warning small py-2">
        <i class="bx bx-error me-1"></i><strong>Faltan parámetros obligatorios para ${anioConsulta}:</strong>
        ${faltantes.join(', ')}. Sin ellos no se puede liquidar ni contabilizar.</div>`);
    }
    limpiarFormularioParametroNomina();
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar los parametros de nomina');
  }
};

const guardarParametroNomina = async () => {
  const form = document.querySelector('#FormParametroNomina');
  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    return;
  }

  try {
    const data = serializarForm('#FormParametroNomina');
    data.link = LINK_NOMINA_PARAMETROS;
    data.op = 'guardar_parametro';

    const resp = await enviarPeticion(data);
    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await cargarParametrosNomina($('#txtAnioParametroNomina').val());
  } catch (e) {
    console.error(e);
    notificarError('No se pudo guardar el parametro');
  }
};

const copiarParametrosNomina = async () => {
  const anioOrigen = $('#slcAnioParametrosNomina').val() || new Date().getFullYear();
  const { value: anioDestino } = await Swal.fire({
    title: 'Copiar parametros de año',
    input: 'number',
    inputLabel: 'Año destino',
    inputValue: Number(anioOrigen) + 1,
    showCancelButton: true,
    confirmButtonText: 'Copiar',
    cancelButtonText: 'Cancelar'
  });

  if (!anioDestino) {
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_PARAMETROS,
      op: 'copiar_parametros_anio',
      anio_origen: anioOrigen,
      anio_destino: anioDestino
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await cargarParametrosNomina(String(anioDestino));
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron copiar los parametros');
  }
};

const inactivarParametroNomina = async (id) => {
  if (!id) {
    return;
  }

  try {
    const confirmed = await showConfirmation();
    if (!confirmed) {
      return;
    }

    const resp = await enviarPeticion({
      link: LINK_NOMINA_PARAMETROS,
      op: 'eliminar_parametro',
      id
    });

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    await cargarParametrosNomina($('#slcAnioParametrosNomina').val());
  } catch (e) {
    console.error(e);
    notificarError('No se pudo inactivar el parametro');
  }
};

const guardarContrato = async () => {
  try {
    const idTercero = parseInt($('#txtIdTerceroContrato').val() || '0', 10);
    if (isNaN(idTercero) || idTercero <= 0) {
      notificarError('Debe seleccionar un tercero creado como empleado para registrar el contrato');
      return;
    }

    // Validación: Método de pago y datos bancarios
    const metodoPago = $('#slcMetodoPagoContrato').val() || '';
    const requireBankData = ['30', '31', '42', '45'].includes(metodoPago);
    
    if (requireBankData) {
      const banco = $.trim($('#txtBancoContrato').val() || '');
      const tipoCuenta = $('#slcTipoCuentaContrato').val() || '';
      const numeroCuenta = $.trim($('#txtNumeroCuentaContrato').val() || '');
      
      if (banco === '') {
        notificarError('Banco es obligatorio para transferencias bancarias (Normativa DIAN)');
        $('#txtBancoContrato').focus();
        return;
      }
      
      if (tipoCuenta === '') {
        notificarError('Tipo de cuenta es obligatorio para transferencias bancarias (Normativa DIAN)');
        $('#slcTipoCuentaContrato').focus();
        return;
      }
      
      if (numeroCuenta === '') {
        notificarError('Número de cuenta es obligatorio para transferencias bancarias (Normativa DIAN)');
        $('#txtNumeroCuentaContrato').focus();
        return;
      }
      
      if (numeroCuenta.length < 8 || numeroCuenta.length > 30) {
        notificarError('Número de cuenta inválido: debe tener entre 8 y 30 dígitos (sin espacios ni guiones)');
        $('#txtNumeroCuentaContrato').focus();
        return;
      }
      
      if (!/^\d+$/.test(numeroCuenta)) {
        notificarError('Número de cuenta inválido: solo se permiten dígitos (sin espacios ni guiones)');
        $('#txtNumeroCuentaContrato').focus();
        return;
      }
    }

    if (!$('#slcEntidadSaludContrato').val()) {
      notificarError('Seleccione la EPS del empleado');
      return;
    }

   showLoadingWithTetrisLogo('Guardando información', 'ISA Software');

    const data = serializarForm('#FormContratos');
    data.link = LINK_NOMINA_CONTRATOS;
    data.op = ($('#txtIdContrato').val() && $('#txtIdContrato').val() !== '0') ? 'contratos_update' : 'contratos_agregar';
    data.subtipo_trabajador = $('#slcSubtipoTrabajadorContrato').val() || '00';
    data.alto_riesgo = $('#chkAltoRiesgoPension').is(':checked') ? 1 : 0;
    data.salario_integral = $('#chkSalarioIntegral').is(':checked') ? 1 : 0;
    data.salario_basico = (typeof unformatNum === 'function') ? unformatNum(String(data.salario_basico || '0')) : data.salario_basico;
    // Vacío = se usa el auxilio legal del año; 0 = no recibe auxilio
    data.aux_transporte = String(data.aux_transporte ?? '').trim() === ''
      ? ''
      : ((typeof unformatNum === 'function') ? unformatNum(String(data.aux_transporte)) : data.aux_transporte);

    const resp = await enviarPeticion(data);
    dissminSwal();

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    limpiarFormulario('FormContratos');
    $('#txtIdContrato').val('0');
    $('#txtBusquedaEmpleadoContrato').val('');
    limpiarEmpleadoContrato();
    $('#slcTipoDocContrato').val('');
    $('#slcEntidadSaludContrato').val('');
    $('#slcEntidadPensionContrato').val('');
    $('#slcEntidadCesantiasContrato').val('');
    $('#slcEntidadCajaContrato').val('0');
    $('#slcEntidadArlContrato').val('0');
    $('#slcClaseRiesgoArl').val('1');
    $('#slcSubtipoTrabajadorContrato').val('00');
    $('#chkAltoRiesgoPension').prop('checked', false);
    $('#chkSalarioIntegral').prop('checked', false);
    $('#slcMetodoPagoContrato').val('');
    $('#txtBancoContrato').val('');
    $('#slcTipoCuentaContrato').val('');
    $('#txtNumeroCuentaContrato').val('');

    await listarContratos();
  } catch (e) {
    console.error(e);
    dissminSwal();
    notificarError('No se pudo guardar el registro');
  }
};

$(function () {
  'use strict';

  cargarTiposIdentificacionContrato();
  cargarTiposContratos();
  cargarEntidadesAfiliacion();
  configurarBusquedaEmpleadoContrato();
  activarFormatoDecimalesContrato();
  cargarPerfilesContables();

  const form = document.querySelector('#FormContratos');
  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (!form.checkValidity()) {
      event.stopPropagation();
      form.classList.add('was-validated');
      return;
    }

    await guardarContrato();
  }, false);

  $('#btnNuevocontratos').click(function () {
    limpiarFormulario('FormContratos');
    $('#txtIdContrato').val('0');
    $('#txtBusquedaEmpleadoContrato').val('');
    limpiarEmpleadoContrato();
    $('#slcTipoDocContrato').val('');
    $('#slcEntidadSaludContrato').val('');
    $('#slcEntidadPensionContrato').val('');
    $('#slcEntidadCesantiasContrato').val('');
    $('#slcEntidadCajaContrato').val('0');
    $('#slcEntidadArlContrato').val('0');
    $('#slcClaseRiesgoArl').val('1');
    $('#slcSubtipoTrabajadorContrato').val('00');
    $('#chkAltoRiesgoPension').prop('checked', false);
    $('#chkSalarioIntegral').prop('checked', false);
    $('#FormContratos').removeClass('was-validated');
  });

  $('#btnBuscarcontratos').click(function () {
    listarContratos();
  });

  // Cargar contratos cuando se navegue al tab de lista
  const btnTabLista = document.querySelector('[data-bs-target="#tabContratoLista"]');
  if (btnTabLista) {
    btnTabLista.addEventListener('shown.bs.tab', function () {
      listarContratos();
    });
  }

  $('#btnTraerSalarioConfiguracion').click(function () {
    aplicarParametroEnCampoContrato('smlv', '#txtSalarioBasicoContrato', 'Salario basico');
  });

  $('#btnTraerAuxConfiguracion').click(function () {
    aplicarParametroEnCampoContrato('aux_transporte', '#txtAuxTransporteContrato', 'Aux transporte');
  });

  // Validación dinámica: Mostrar/ocultar campos bancarios requeridos según método de pago
  const actualizarVisibilidadCamposBancarios = () => {
    const metodoPago = $('#slcMetodoPagoContrato').val() || '';
    const requireBankData = ['30', '31', '42', '45'].includes(metodoPago);
    
    $('#spanBancoRequerido, #spanTipoCuentaRequerido, #spanNumeroCuentaRequerido').toggleClass('d-none', !requireBankData);
    
    if (requireBankData) {
      $('#txtBancoContrato, #slcTipoCuentaContrato, #txtNumeroCuentaContrato').prop('required', true);
    } else {
      $('#txtBancoContrato, #slcTipoCuentaContrato, #txtNumeroCuentaContrato').prop('required', false);
    }
  };

  $('#slcMetodoPagoContrato').on('change', actualizarVisibilidadCamposBancarios);

  // Inicializar visibilidad de campos bancarios
  actualizarVisibilidadCamposBancarios();

  $('#btnConfigurarParametrosNomina').click(async function () {
    await cargarParametrosNomina();
    const modal = document.getElementById('modalParametrosNomina');
    if (modal && typeof bootstrap !== 'undefined') {
      bootstrap.Modal.getOrCreateInstance(modal).show();
    }
  });

  $('#btnRecargarParametrosNomina').click(function () {
    cargarParametrosNomina($('#slcAnioParametrosNomina').val());
  });

  $('#slcAnioParametrosNomina').change(function () {
    cargarParametrosNomina($(this).val());
  });

  $('#btnNuevoParametroNomina, #btnCancelarParametroNomina').click(function () {
    limpiarFormularioParametroNomina();
  });

  $('#btnCopiarParametrosNomina').click(function () {
    copiarParametrosNomina();
  });

  $('#FormParametroNomina').on('submit', async function (event) {
    event.preventDefault();
    await guardarParametroNomina();
  });

  $(document).on('click', '.btnEditarParametroNomina', function () {
    const index = Number($(this).data('index'));
    if (!Number.isNaN(index) && cacheParametrosNomina[index]) {
      llenarFormularioParametroNomina(cacheParametrosNomina[index]);
    }
  });

  $(document).on('click', '.btnEliminarParametroNomina', function () {
    const id = Number($(this).data('id') || 0);
    inactivarParametroNomina(id);
  });

  dissminSwal();
});

// ---------------------------------------------------------------------------
// Incremento salarial anual
// ---------------------------------------------------------------------------
const LINK_INCREMENTO = '../models/nominaIncrementoSalarial.php';
let incFilas = [];

const incMoney = (v) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v || 0);
const incEsc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const incDatos = () => ({
  anio: $('#txtIncAnio').val(),
  fecha_efectiva: $('#txtIncFecha').val(),
  modo: $('#slcIncModo').val(),
  porcentaje: $('#txtIncPorcentaje').val() || 0,
  redondeo: $('#slcIncRedondeo').val()
});

const incLimpiarResultado = () => {
  incFilas = [];
  $('#incResumen').addClass('d-none');
  $('#incAvisos').html('');
  $('#incResultado').html('<div class="text-center text-muted small py-4">Indique el año y pulse <strong>Previsualizar</strong>.</div>');
  $('#btnIncAplicar').prop('disabled', true);
};

const incCargarSugerencia = async (anio = null) => {
  const resp = await enviarPeticion({ link: LINK_INCREMENTO, op: 'sugerencia', ...(anio ? { anio } : {}) });
  if (resp.error) {
    notificarError(resp.message);
    return;
  }
  const d = resp.data;
  if (!anio) {
    $('#txtIncAnio').val(d.anio);
    $('#txtIncFecha').val(`${d.anio}-01-01`);
  }
  if (!d.smlv_nuevo) {
    $('#lblIncSmlv').html(`<span class="text-danger">No hay SMLMV registrado para ${d.anio}. Regístrelo en Parámetros de nómina (use "Copiar año").</span>`);
  } else if (!d.smlv_anterior) {
    $('#lblIncSmlv').html(`<span class="text-danger">No hay SMLMV registrado para ${d.anio - 1}: regístrelo en Parámetros de nómina para identificar
      quién devengaba el mínimo, o elija otro año.</span>`);
  } else {
    $('#lblIncSmlv').html(`SMLMV ${d.anio - 1}: <strong>${incMoney(d.smlv_anterior)}</strong> → ${d.anio}: <strong>${incMoney(d.smlv_nuevo)}</strong> (incremento ${d.incremento_smlv} %).`);
  }
  if (d.incremento_smlv !== null && !$('#txtIncPorcentaje').val()) {
    $('#txtIncPorcentaje').val(d.incremento_smlv);
  }
  const hist = d.historial || [];
  $('#incHistorial').html(!d.historial_disponible
    ? '<div class="alert alert-warning small py-2 mb-0">Ejecute el script <code>sql/nomina_incremento_salarial.sql</code> para poder aplicar y revertir incrementos.</div>'
    : hist.length ? `
      <h6 class="fw-bold small mb-2">Incrementos aplicados</h6>
      <table class="table table-sm align-middle mb-0">
        <thead class="table-primary"><tr><th>Año</th><th>Fecha efectiva</th><th class="text-end">Contratos</th><th>Registrado</th><th></th></tr></thead>
        <tbody>${hist.map((h) => `<tr>
          <td>${incEsc(h.anio)}</td><td>${incEsc(h.fecha_efectiva)}</td>
          <td class="text-end">${incEsc(h.contratos)}${Number(h.revertidos) ? ` <small class="text-muted">(${h.revertidos} revertidos)</small>` : ''}</td>
          <td><small>${incEsc(h.fecha_registro)}</small></td>
          <td class="text-end">${Number(h.revertidos) < Number(h.contratos)
            ? `<button type="button" class="btn btn-sm btn-outline-danger btnIncRevertir" data-anio="${incEsc(h.anio)}"><i class="bx bx-undo me-1"></i>Revertir</button>` : ''}</td>
        </tr>`).join('')}</tbody>
      </table>` : '');
};

const incPrevisualizar = async () => {
  const datos = incDatos();
  if (!datos.anio) {
    notificarError('Indique el año del incremento');
    return;
  }
  showLoadingWithTetrisLogo('Calculando incremento salarial', 'ISA Software');
  try {
    const resp = await enviarPeticion({ link: LINK_INCREMENTO, op: 'previsualizar', ...datos });
    dissminSwal();
    if (resp.error) {
      incLimpiarResultado();
      notificarError(resp.message);
      return;
    }
    const d = resp.data;
    incFilas = d.filas || [];
    $('#incAvisos').html((d.avisos || []).map((a) => `<div class="alert alert-warning small py-2">${incEsc(a)}</div>`).join(''));
    const r = d.resumen;
    $('#incResContratos').text(r.contratos + (r.ya_aplicados ? ` (${r.ya_aplicados} ya actualizados)` : ''));
    $('#incResTipos').text(`${r.minimos} / ${r.porcentaje} / ${r.integrales}`);
    $('#incResActual').text(incMoney(r.nomina_actual));
    $('#incResNueva').text(`${incMoney(r.nomina_nueva)} (+${incMoney(r.nomina_nueva - r.nomina_actual)})`);
    $('#incResumen').removeClass('d-none');

    if (!incFilas.length) {
      $('#incResultado').html('<div class="alert alert-info small py-2 mb-0">No hay contratos por actualizar con estos criterios.</div>');
      $('#btnIncAplicar').prop('disabled', true);
      return;
    }
    $('#incResultado').html(`
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-primary">
          <tr>
            <th><input type="checkbox" class="form-check-input" id="chkIncTodos" checked></th>
            <th>Empleado</th><th>Cargo</th><th>Motivo</th>
            <th class="text-end">Salario actual</th><th class="text-end">Salario nuevo</th><th class="text-end">%</th>
            <th class="text-end">Aux. contrato</th>
          </tr>
        </thead>
        <tbody>${incFilas.map((f) => `<tr>
          <td><input type="checkbox" class="form-check-input chkInc" value="${f.id}" checked></td>
          <td>${incEsc(f.empleado)}<br><small class="text-muted">${incEsc(f.documento)}${f.jornada === 'PARCIAL' ? ' · parcial' : ''}</small></td>
          <td><small>${incEsc(f.cargo)}</small></td>
          <td><small>${incEsc(f.motivo)}</small></td>
          <td class="text-end">${incMoney(f.salario_actual)}</td>
          <td class="text-end fw-semibold text-primary">${incMoney(f.salario_nuevo)}</td>
          <td class="text-end">${f.variacion}</td>
          <td class="text-end"><small>${f.aux_actual === null ? 'Legal' : (f.aux_actual !== f.aux_nuevo ? `${incMoney(f.aux_actual)} → ${incMoney(f.aux_nuevo)}` : incMoney(f.aux_actual))}</small></td>
        </tr>`).join('')}</tbody>
      </table>`);
    $('#btnIncAplicar').prop('disabled', false);
  } catch (e) {
    dissminSwal();
    console.error(e);
    notificarError('No se pudo calcular el incremento');
  }
};

const incAplicar = async () => {
  const ids = $('.chkInc:checked').map(function () { return this.value; }).get();
  if (!ids.length) {
    notificarError('Seleccione al menos un contrato');
    return;
  }
  const datos = incDatos();
  const ok = await Swal.fire({
    icon: 'question',
    title: '¿Aplicar incremento salarial?',
    html: `<div class="small">Se actualizará el salario de <strong>${ids.length}</strong> contrato(s) con efecto desde
           <strong>${incEsc(datos.fecha_efectiva)}</strong>. El cambio queda registrado y puede revertirse
           mientras no haya nóminas contabilizadas con los nuevos salarios.</div>`,
    showCancelButton: true,
    confirmButtonText: 'Sí, aplicar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  });
  if (!ok.isConfirmed) return;

  showLoadingWithTetrisLogo('Actualizando salarios', 'ISA Software');
  const resp = await enviarPeticion({ link: LINK_INCREMENTO, op: 'aplicar', ...datos, ids: ids.join(',') });
  dissminSwal();
  if (resp.error) {
    notificarError(resp.message);
    return;
  }
  Swal.fire('Listo', resp.message, 'success');
  await incCargarSugerencia(datos.anio);
  await incPrevisualizar();
  listarContratos();
};

const incRevertir = async (anio) => {
  const ok = await Swal.fire({
    icon: 'warning',
    title: `¿Revertir el incremento de ${anio}?`,
    text: 'Los contratos vuelven al salario anterior (excepto los que se modificaron a mano después del incremento).',
    showCancelButton: true,
    confirmButtonText: 'Sí, revertir',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ff3e1d',
    reverseButtons: true
  });
  if (!ok.isConfirmed) return;
  showLoadingWithTetrisLogo('Revirtiendo incremento', 'ISA Software');
  const resp = await enviarPeticion({ link: LINK_INCREMENTO, op: 'revertir', anio });
  dissminSwal();
  if (resp.error) {
    notificarError(resp.message);
    return;
  }
  Swal.fire('Listo', resp.message, 'success');
  await incCargarSugerencia($('#txtIncAnio').val());
  incLimpiarResultado();
  listarContratos();
};

const abrirIncrementoSalarial = async () => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software');
  try {
    $('#txtIncPorcentaje').val('');
    incLimpiarResultado();
    await incCargarSugerencia();
  } finally {
    dissminSwal();
  }
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalIncrementoSalarial')).show();
};

$(function () {
  $('#btnIncrementoSalarial').on('click', abrirIncrementoSalarial);
  $('#btnIncPrevisualizar').on('click', incPrevisualizar);
  $('#btnIncAplicar').on('click', incAplicar);
  $('#slcIncModo').on('change', function () {
    $('#colIncPorcentaje').toggleClass('d-none', this.value === 'SOLO_MINIMO');
    incLimpiarResultado();
  });
  $('#txtIncAnio').on('change', function () {
    const anio = this.value;
    $('#txtIncFecha').val(`${anio}-01-01`);
    $('#txtIncPorcentaje').val('');
    incLimpiarResultado();
    incCargarSugerencia(anio);
  });
  $('#txtIncFecha, #txtIncPorcentaje, #slcIncRedondeo').on('change', incLimpiarResultado);
  $(document).on('change', '#chkIncTodos', function () {
    $('.chkInc').prop('checked', this.checked);
  });
  $(document).on('click', '.btnIncRevertir', function () {
    incRevertir($(this).data('anio'));
  });
});