const LINK_NOMINA_NOVEDADES = '../models/nominaNovedades.php';

const notificarError = (mensaje) => {
  Swal.fire('Error', mensaje || 'Se genero un error interno de la aplicacion', 'error');
};

const generarOpcionesAnios = () => {
  const anioActual = new Date().getFullYear();
  let html = '<option value="">Seleccione...</option>';
  
  for (let i = anioActual - 2; i <= anioActual + 2; i++) {
    html += `<option value="${i}" ${i === anioActual ? 'selected' : ''}>${i}</option>`;
  }
  
  $('#slcAnioNovedad').html(html);
};

const cargarPeriodosNovedad = async (anio) => {
  if (!anio) {
    $('#slcPeriodoNovedad').html('<option value="">Seleccione período...</option>');
    return;
  }

  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'combos_periodos',
      anio: anio
    });

    let html = '<option value="">Seleccione período...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((periodo) => {
        html += `<option value="${periodo.id}" data-inicio="${periodo.fecha_inicio}" data-fin="${periodo.fecha_fin}">${periodo.label}</option>`;
      });
    }

    $('#slcPeriodoNovedad').html(html);
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar los períodos');
  }
};

const calcularTotalNovedad = () => {
  const cantidad = parseFloat($('#txtCantidadNovedad').val() || '0');
  const valorUnitario = parseFloat($('#txtValorUnitarioNovedad').val() || '0');
  const total = cantidad * valorUnitario;

  const porcentajeAplicado = parseFloat($('#txtPorcentajehNovedad').val() || '0');
  let valorFinal = total;

  // El porcentaje es el recargo: en horas extra se paga hora + recargo (25 % ⇒ 125 %);
  // en recargos nocturno/dominical solo el recargo.
  if (porcentajeAplicado > 0) {
    const subtipo = String($('#slcConceptoNovedad option:selected').data('subtipo') || '').toUpperCase();
    const factor = subtipo === 'EXTRA' ? 1 + porcentajeAplicado / 100 : porcentajeAplicado / 100;
    valorFinal = total * factor;
  }

  $('#txtTotalNovedad').val(total.toFixed(2));
  $('#txtValorFinalNovedad').val(valorFinal.toFixed(2));
};

// Sugerencia del valor unitario según el concepto (hora ordinaria o día de salario)
const sugerirValorUnitario = async () => {
  const subtipo = String($('#slcConceptoNovedad option:selected').data('subtipo') || '').toUpperCase();
  const idContrato = $('#slcContratoNovedad').val();
  const $ayuda = $('#ayudaValorUnitario');
  $ayuda.text('');
  if (!idContrato || !subtipo) return;
  const porHora = /EXTRA|RECARGO/.test(subtipo);
  const porDia = /INCAPACIDAD|LICENCIA|VACACION/.test(subtipo);
  if (!porHora && !porDia) return;
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES, op: 'valor_hora',
      id_contrato: idContrato, periodo: $('#slcPeriodoNovedad').val() || ''
    });
    if (resp.error) return;
    const valor = porHora ? resp.data.valor_hora : resp.data.valor_dia;
    const fmt = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 2 }).format(valor);
    $ayuda.html(porHora
      ? `Hora ordinaria: <strong>${fmt}</strong> (salario / ${resp.data.horas_mes} h)`
      : `Día de salario: <strong>${fmt}</strong>. Incapacidad general: 66,67 % desde el día 3 (mín. SMLMV diario).`);
    if (!parseFloat($('#txtValorUnitarioNovedad').val())) {
      $('#txtValorUnitarioNovedad').val(valor);
      calcularTotalNovedad();
    }
  } catch (e) {
    console.error(e);
  }
};

// Limita la fecha de la novedad al rango del período seleccionado
const ajustarFechaPeriodo = () => {
  const opt = $('#slcPeriodoNovedad option:selected');
  const ini = opt.data('inicio') || '';
  const fin = opt.data('fin') || '';
  const $fecha = $('#txtFechaNovedad');
  $fecha.attr({ min: ini, max: fin });
  const actual = $fecha.val();
  if (ini && (!actual || actual < ini || actual > fin)) {
    $fecha.val(ini);
  }
};

const cargarContratosNovedad = async () => {
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'combos_contratos'
    });
    let html = '<option value="">Seleccione contrato...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
        html += `<option value="${item.id}">${item.empleado_documento} - ${(item.empleado || '').trim()}</option>`;
      });
    }
    $('#slcContratoNovedad').html(html);
    $('#filtroContratoNovedades').html(html.replace('Seleccione contrato...', 'Todos los contratos'));
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar los contratos');
  }
};

const cargarConceptosNovedad = async (subtipo = '') => {
  try {
    const tipo = $('#slcTipoNovedad').val() || '';
    
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'combos_conceptos',
      tipo: tipo,
      subtipo: subtipo
    });
    let html = '<option value="">Seleccione concepto...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
        const badge = item.porcentaje_fijo ? ` (${item.porcentaje_fijo}%)` : '';
        const label = `${item.id} - ${item.nombre}${badge}`;
        html += `<option value="${item.id}" 
                  data-codigo="${item.clave}"
                  data-tipo="${item.tipo}" 
                  data-subtipo="${item.subtipo}"
                  data-porcentaje="${item.porcentaje_fijo || ''}"
                  data-requiere-cantidad="${item.requiere_cantidad}"
                  data-requiere-valor="${item.requiere_valor}">${label}</option>`;
      });
    }
    $('#slcConceptoNovedad').html(html);
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar los conceptos');
  }
};

const cargarSubtiposNovedad = async (tipo = '') => {
  try {
    if (!tipo) {
      $('#slcTipoConceptoCategoria').html('<option value="">Seleccione categoría...</option>');
      $('#slcConceptoNovedad').html('<option value="">Seleccione concepto...</option>');
      return;
    }
    
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'combos_subtipos',
      tipo: tipo
    });
    
    let html = '<option value="">Seleccione categoría...</option>';
    if (resp.data && resp.data.length > 0) {
      resp.data.forEach((item) => {
        html += `<option value="${item.id}">${item.nombre}</option>`;
      });
    }
    $('#slcTipoConceptoCategoria').html(html);
    $('#slcConceptoNovedad').html('<option value="">Seleccione concepto...</option>');
  } catch (e) {
    console.error(e);
    notificarError('No se pudieron cargar las categorías');
  }
};

const actualizarConceptosSegunTipo = async () => {
  const subtipo = $('#slcTipoConceptoCategoria').val();
  await cargarConceptosNovedad(subtipo);
};

const aplicarConceptoNovedad = () => {
  const selected = $('#slcConceptoNovedad option:selected');
  const id = selected.val() || 0;
  const codigo = selected.data('codigo') || '';
  const nombre = selected.text().split(' - ')[1] || '';
  const tipo = selected.data('tipo') || '';
  const subtipo = selected.data('subtipo') || '';
  const porcentaje = selected.data('porcentaje') || '';

  if (id) {
    $('#txtIdConcepto').val(id);
    $('#txtConceptoNovedad').val(codigo);
    $('#txtPorcentajehNovedad').val(porcentaje || '0');

    // Actualizar información DIAN
    const info = `<strong>Código DIAN:</strong> ${codigo} | <strong>Tipo:</strong> ${subtipo}`;
    $('#infoConceptoDIAN').html(info);

    // Cambiar label de cantidad según subtipo
    if (subtipo === 'EXTRA' || subtipo === 'INCAPACIDAD' || subtipo === 'LICENCIA') {
      $('#labelUnidadCantidad').text('(Horas/Días)');
    } else if (subtipo === 'PRESTACION') {
      $('#labelUnidadCantidad').text('(Valor)');
    } else {
      $('#labelUnidadCantidad').text('(Días)');
    }

    // Mostrar/ocultar campos específicos
    if (subtipo === 'INCAPACIDAD') {
      $('#rowIncapacidad').show();
    } else {
      $('#rowIncapacidad').hide();
    }

    if (subtipo === 'EMBARGO') {
      $('#rowEmbargo').show();
    } else {
      $('#rowEmbargo').hide();
    }

    calcularTotalNovedad();
    sugerirValorUnitario();
  }
};

const editarNovedad = (data) => {
  limpiarFormulario('FormNovedades');
  ShowDataFrom(data, 'FormNovedades');
  $('#slcContratoNovedad').val(String(data.id_contrato || ''));
  $('#slcEstadoNovedad').val(String(data.estado || 'ACTIVO'));
  $('#txtIdConcepto').val(String(data.id_concepto || 0));
  $('#txtPorcentajehNovedad').val(String(data.porcentaje_aplicado || 0));

  // Manejar período: extraer año y preseleccionar
  const periodo = String(data.periodo || '');
  if (periodo) {
    const anio = periodo.substring(0, 4);
    $('#slcAnioNovedad').val(anio);
    
    // Cargar períodos del año
    cargarPeriodosNovedad(anio).then(() => {
      // En quincenal el período se elige según el día de la novedad
      const fecha = String(data.fecha_novedad || '');
      const dia = parseInt(fecha.substring(8, 10), 10) || 1;
      const candidatos = [periodo, `${periodo}-${dia >= 16 ? '16' : '01'}`];
      const valor = candidatos.find((v) => $(`#slcPeriodoNovedad option[value="${v}"]`).length) || '';
      $('#slcPeriodoNovedad').val(valor);
      ajustarFechaPeriodo();
      if (fecha) $('#txtFechaNovedad').val(fecha.substring(0, 10));
    });
  }

  // Manejar tipo y subtipo
  const tipo = String(data.tipo || '');
  const subtipo = String(data.subtipo_concepto || '');
  
  if (tipo) {
    $('#slcTipoNovedad').val(tipo);
    // Cargar subtipos para el tipo
    cargarSubtiposNovedad(tipo).then(() => {
      if (subtipo) {
        $('#slcTipoConceptoCategoria').val(subtipo);
        // Cargar conceptos para el subtipo
        cargarConceptosNovedad(subtipo).then(() => {
          if (data.id_concepto) {
            $('#slcConceptoNovedad').val(data.id_concepto);
          }
        });
      }
    });
  }

  // Actualizar información DIAN si existe
  if (data.id_concepto) {
    const info = `<strong>Concepto:</strong> ${data.concepto} | <strong>Subtipo:</strong> ${data.subtipo_concepto}`;
    $('#infoConceptoDIAN').html(info);
  }

  // Mostrar/ocultar campos específicos
  if (data.subtipo_concepto === 'INCAPACIDAD') {
    $('#rowIncapacidad').show();
  } else {
    $('#rowIncapacidad').hide();
  }

  if (data.subtipo_concepto === 'EMBARGO') {
    $('#rowEmbargo').show();
  } else {
    $('#rowEmbargo').hide();
  }
  //ubicarse en la pestaña del formulario
  $('button[data-bs-target="#tabNovedadCrear"]').tab('show');
  calcularTotalNovedad();
};

const eliminarNovedad = async (data) => {
  try {
    const confirmed = await showConfirmation();
    if (!confirmed) {
      return;
    }
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'novedades_delete',
      id: data.id
    });
    if (resp.error) {
      notificarError(resp.message);
      return;
    }
    Swal.fire('Ok', resp.message, 'success');
    await listarNovedades();
  } catch (e) {
    console.error(e);
    notificarError('No se pudo eliminar el registro');
  }
};

const listarNovedades = async () => {
  // Solo cargar si la pestaña está visible
  const tabVisible = $('#tabNovedadLista').closest('.tab-pane').hasClass('active');
  if (!tabVisible) {
    return;
  }

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const resp = await enviarPeticion({
      link: LINK_NOMINA_NOVEDADES,
      op: 'novedades_listar',
      filtro: $('#filtroNovedades').val() || '',
      id_contrato: $('#filtroContratoNovedades').val() || '0',
      periodo: $('#filtroPeriodoNovedades').val() || ''
    });

    if (!resp.data || resp.data.length === 0) {
      $('#resultNovedades').html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $('#resultNovedades').html('<table id="tablaNovedades" class="table table-sm table-borderless table-hover"><thead class="table-primary"></thead></table>');

    createDataTable(
      'tablaNovedades',
      resp.data,
      ['id', 'empleado_documento', 'empleado', 'periodo', 'fecha_novedad', 'concepto_nombre', 'tipo', 'cantidad', 'valor_unitario', 'valor_final', 'estado'],
      ['Id', 'Documento', 'Empleado', 'Período', 'Fecha', 'Concepto', 'Tipo', 'Cant.', 'V. unit.', 'V. final', 'Estado'],
      editarNovedad,
      eliminarNovedad
    );

    dissminSwal();
  } catch (e) {
    console.error(e);
    $('#resultNovedades').html(ShowAlert('Error interno de la aplicación', 'error'));
    dissminSwal();
  }
};

const limpiarFormNovedad = () => {
  limpiarFormulario('FormNovedades');
  $('#txtIdNovedad').val('0');
  $('#txtIdConcepto').val('0');
  $('#slcContratoNovedad').val('');
  $('#slcTipoNovedad').val('');
  $('#slcTipoConceptoCategoria').val('');
  $('#slcConceptoNovedad').val('');
  $('#txtConceptoNovedad').val('');
  $('#slcEstadoNovedad').val('ACTIVO');
  $('#txtPorcentajehNovedad').val('0');
  $('#txtTotalNovedad').val('0.00');
  $('#txtValorFinalNovedad').val('0.00');
  $('#infoConceptoDIAN').html('');
  $('#ayudaValorUnitario').text('');
  $('#FormNovedades').removeClass('was-validated');
  $('#rowIncapacidad').hide();
  $('#rowEmbargo').hide();
  
  // Resetear año y período al año actual
  const anioActual = new Date().getFullYear();
  $('#slcAnioNovedad').val(anioActual);
  cargarPeriodosNovedad(anioActual);
  
  // Resetear subtipos y conceptos
  $('#slcTipoConceptoCategoria').html('<option value="">Seleccione categoría...</option>');
  $('#slcConceptoNovedad').html('<option value="">Seleccione concepto...</option>');
};

const guardarNovedad = async () => {
  try {
    showLoadingWithTetrisLogo('Guardando registro', 'ISA Software');

    const data = formatearArrayRequest($('#FormNovedades').serializeArray());
    data.link = LINK_NOMINA_NOVEDADES;
    data.op = ($('#txtIdNovedad').val() && $('#txtIdNovedad').val() !== '0') ? 'novedades_update' : 'novedades_agregar';
    const resp = await enviarPeticion(data);
    dissminSwal();

    if (resp.error) {
      notificarError(resp.message);
      return;
    }

    Swal.fire('Ok', resp.message, 'success');
    limpiarFormNovedad();
    await listarNovedades();
  } catch (e) {
    console.error(e);
    dissminSwal();
    notificarError('No se pudo guardar el registro');
  }
};

$(function () {
  // Inicializar años y períodos
  generarOpcionesAnios();
  const anioInicial = $('#slcAnioNovedad').val();
  if (anioInicial) {
    cargarPeriodosNovedad(anioInicial);
  }

  cargarContratosNovedad();


  // Cargar períodos cuando cambia el año
  $('#slcAnioNovedad').on('change', function () {
    cargarPeriodosNovedad($(this).val());
  });

  $('#slcPeriodoNovedad').on('change', ajustarFechaPeriodo);
  $('#slcContratoNovedad').on('change', sugerirValorUnitario);
  $('#txtFiltroNovedadEnter, #filtroNovedades').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      listarNovedades();
    }
  });

  // Cargar subtipos cuando cambia el tipo de novedad
  $('#slcTipoNovedad').on('change', function () {
    const tipo = $(this).val();
    cargarSubtiposNovedad(tipo);
  });

  // Cargar conceptos cuando cambia la categoría
  $('#slcTipoConceptoCategoria').on('change', function () {
    actualizarConceptosSegunTipo();
  });

  // Aplicar concepto cuando selecciona uno
  $('#slcConceptoNovedad').on('change', function () {
    aplicarConceptoNovedad();
  });

  // Calcular total cuando cambian cantidad o valor
  $('#txtCantidadNovedad, #txtValorUnitarioNovedad').on('input', function () {
    calcularTotalNovedad();
  });

  // Cargar tabla cuando se muestra la pestaña
  $('button[data-bs-target="#tabNovedadLista"]').on('shown.bs.tab', function () {
    listarNovedades();
  });

  // Validación del formulario
  const form = document.querySelector('#FormNovedades');
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (!form.checkValidity()) {
      event.stopPropagation();
      form.classList.add('was-validated');
      return;
    }
    await guardarNovedad();
  }, false);

  $('#btnNuevoNovedades').click(function () {
    limpiarFormNovedad();
  });

  $('#btnBuscarNovedades').click(function () {
    listarNovedades();
  });
  dissminSwal();
});

