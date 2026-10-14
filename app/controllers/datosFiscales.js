/*
 @ Consultar rangos Dian
*/
const rangosDian = async () => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
  const tokenUser = $("#tokenUser").val();
  const tokenPassword = $("#tokenPassword").val();

  try {
    const data = {
      link: `https://thecyctechnology.com/api-fe/public/index.php/dian-service/getNumberingRange?tokenUser=${tokenUser}&tokenPassword=${tokenPassword}`,
    };

    const resp = await enviarPeticion(data, 'GET');

    console.log(resp)

    // Validar estructura base
    if (!resp || resp.estado !== "true" || !resp.Rangos) {
      $("#tablaRangosContainer").html("<div class='alert alert-warning'><i class='fa-solid fa-bell fa-shake'></i> No se encontraron rangos disponibles o la respuesta es inválida.</div>");
      $("#modalRangos").modal('show');
      dissminSwal();
      return;
    }

    // Normalizar a array si solo viene un objeto
    const rangos = Array.isArray(resp.Rangos) ? resp.Rangos : [resp.Rangos];

    if (rangos.length === 0) {
      $("#tablaRangosContainer").html("<div class='alert alert-warning'><i class='fa-solid fa-bell fa-shake'></i> No se encontraron rangos disponibles.</div>");
      $("#modalRangos").modal('show');
      dissminSwal();
      return;
    }

    // Construcción de tabla HTML
    let tablaHtml = `
      <table class="table table-sm table-striped table-hover table-bordered table-responsive-md align-middle">
        <thead class="table-info">
          <tr>
            <th>Resolución</th>
            <th>Fecha Resolución</th>
            <th>Prefijo</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Válido Desde</th>
            <th>Válido Hasta</th>
            <th>Clave Técnica</th>
            <th class="text-center">Agregar</th>
          </tr>
        </thead>
        <tbody>
    `;

    rangos.forEach((rango) => {
      tablaHtml += `
        <tr>
          <td>${rango.c_ResolutionNumber || ''}</td>
          <td>${rango.c_ResolutionDate || ''}</td>
          <td>${rango.c_Prefix || ''}</td>
          <td>${rango.c_FromNumber || ''}</td>
          <td>${rango.c_ToNumber || ''}</td>
          <td>${rango.c_ValidDateFrom || ''}</td>
          <td>${rango.c_ValidDateTo || ''}</td>
          <td style="word-break: break-all;">${rango.c_TechnicalKey || ''}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-warning" 
              onclick='asignarRangoIndividual(${JSON.stringify(rango)})' 
              title="Asignar rango">
              <i class="fa-solid fa-plus"></i>
            </button>
          </td>
        </tr>
      `;
    });

    tablaHtml += `
        </tbody>
      </table>
    `;

    // Inyectar tabla en el contenedor
    $("#tablaRangosContainer").html(tablaHtml);
    $("#modalRangos").modal('show');

  } catch (e) {
    console.error("Error al consultar rangos:", e);
    $("#tablaRangosContainer").html("<div class='alert alert-danger'>Error al consultar los rangos.</div>");
  } finally {
    dissminSwal();
  }
};
/*
 @ Asignar rangos a cajas de texto
*/
const asignarRangoIndividual = async (rangos) => {
  $("#InvoiceAuthorization").val(rangos.c_ResolutionNumber);
  $("#StartDate").val(rangos.c_ValidDateFrom);
  $("#EndDate").val(rangos.c_ValidDateTo);
  $("#Prefix").val(rangos.c_Prefix);
  $("#From").val(rangos.c_FromNumber);
  $("#To").val(rangos.c_ToNumber);
  $("#claveTecnicaRes").val(rangos.c_TechnicalKey);
  $("#modalRangos").modal('hide');
}

/*
 @ Tipos de documentos
*/
const tiposDocumentosFE = async () => {
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_tipos_documentos'
    };
    var resp = await enviarPeticion(data);
    let option = `<option value="">Seleccionar</option>`;
    resp.forEach(function (d) {
      option += `<option value="${d.Id}">${d.Descripcion.toUpperCase()}</option>`;
    });
    $("#idTipoDoc").html(option);
  } catch (e) {
    console.log(e);
  }
}

const cargarClaseDocumento = async (tipo) => {
  try {
    if (!tipo) {
      $("#ClaseDocumento").html('<option value="">Seleccionar</option>');
      return;
    }

    const data = {
      link: '../models/datosFiscales.php',
      op: 's_clase_documento_fe',
      tipo
    };

    const resp = await enviarPeticion(data);

    let option = '';

    if (Array.isArray(resp)) {
      option += `<option value="">Todos los documentos</option>`;
      resp.forEach(function (d) {
        option += `<option value="${d.tipo}">${d.tipo} - ${d.desc_tipo} (${d.desc_sw})</option>`;
      });
    }

    $("#ClaseDocumento").html(option);
  } catch (e) {
    console.log(e);
    $("#ClaseDocumento").html('<option value="">Seleccionar</option>');
  }
}
/*
 @ Regimenes
*/
const regimen = async () => {
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_regimenes'
    };
    var resp = await enviarPeticion(data); // console.log(resp);
    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.regimen.toUpperCase()}</option>`;
    });
    $("#regimen").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Perfiles tributarios
*/
const perfilTributario = async () => {
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_perfil_tributario'
    };
    var resp = await enviarPeticion(data);

    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.tipo}">${d.tipo} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#perfilTributario").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Responsabilidades
*/
const responsabilidadesTributarias = async () => {
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_responsabilidades'
    };
    const resp = await enviarPeticion(data);
    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.codigo}">${d.codigo} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#responsabilidades").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Impuestos
*/
const impuestos = async () => {
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_impuestos'
    };
    const resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.codigo}">${d.codigo} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#impuestos").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Unique ID
*/
const uniqueID = async () => {
  let Id = '';
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 'uniqId'
    };
    const resp = await enviarPeticion(data);
    Id = resp.data;
  } catch (e) {
    console.log(e);
  }
  return Id;
}
/*
 @ Validar NIT
*/
const validarNIT = async (nit) => {
  let Id = '';
  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_facturador_electronico',
      nit
    };
    const resp = await enviarPeticion(data);
    if (resp.length > 0) { //console.log(resp[0]);
      llenarFormulario(resp[0], 'form-datos-fiscales');
    }

  } catch (e) {
    console.log(e);
  }
  return Id;
}
/*
 @llenarFormulario
*/
const llenarFormulario = async (data, form) => {
  try {
    await ShowDataFrom(data, form);

    if (form === 'form-datos-fiscales') {
      let estatus = '';
      let estatusNomina = '';
      switch (data.ProfileExecutionID) {
        case '1':
          estatus = '#63E6BE';
          break;
        case '2':
          estatus = '#f00f1b';
          break;
        default:
          estatus = '#dedede';
          break;
      }

      switch (data.ProfileExecutionIDNomina || data.ProfileExecutionID) {
        case '1':
          estatusNomina = '#63E6BE';
          break;
        case '2':
          estatusNomina = '#f00f1b';
          break;
        default:
          estatusNomina = '#dedede';
          break;
      }

      $("#labelProfile").css('color', estatus);
      $("#labelProfileNomina").css('color', estatusNomina);
      $("#idTercero").val(data.Id);
      $('#responsabilidades').val(data.responsabilidades).trigger('change');
      $('#impuestos').val(data.impuestos).trigger('change');
      listarResoluciones(data.resoluciones);
      $('[data-bs-target="#navs-secuenciales"]').removeClass('disabled').removeAttr('disabled');

      // Desactivar temporalmente el evento 'change' de #codigoDpto
      const codigoDptoElement = $("#codigoDpto");
      const codigoCiudadElement = $("#codigoCiudad");

      codigoDptoElement.off('change');

      let pais = $("#codigoPais").val();
      let dpto = codigoDptoElement.val();

      await municipios(pais, dpto, 'codigoCiudad');
      codigoCiudadElement.val(data.codigoCiudad);

      // Reactivar evento change
      codigoDptoElement.on('change', async function () {
        let pais = $("#codigoPais").val();
        let dpto = $(this).val();
        await municipios(pais, dpto, 'codigoCiudad');
      });

      // Mostrar preview del logo si existe
      if (data.logoEmpresa && data.logoEmpresa !== '') {
        let urlLogo = `../../../../assets/logos-empresas/${data.logoEmpresa}?rand()`;
        $("#preview").attr("src", urlLogo).show();
        $("#previewContainer").show();
      } else {
        let urlLogo = `../../../../assets/logos-empresas/default.png?rand()`;
        $("#preview").attr("src", urlLogo).show();
        $("#previewContainer").show();
      }

    } else {
      $("#idResolucion").val(data.Id);
    }
  } catch (e) {
    console.log(e);
  }
};
/*
@listarResoluciones
*/
const listarResoluciones = async (data) => {
  try {
    if (!data || data.length === 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $("#result").html(` <table id="tb-resoluciones" class="table table-sm table-borderless" style="width: 100%; white-space: nowrap;">
                          <thead class="table-primary"></thead> 
                        </table> `);

    createDataTable(
      'tb-resoluciones',
      data,
      ['Id', 'InvoiceAuthorization', 'idTipoDoc', 'descTipoDoc', 'StartDate', 'EndDate', 'Prefix', 'From', 'To', 'claveTecnicaRes'],
      ['Id', 'Resolución', 'IdTipo', 'Tipo', 'Inicio', 'Fin', 'Prefijo', 'Desde', 'Hasta', 'claveTecnica'],
      editResolucion,
      deleteResolucion,
      false,
      false,
      false,
      false
    );

    dissminSwal();
  } catch (e) {
    console.error(e);
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'));
    dissminSwal();
  }
};
/*
@eliminar Resoluciones
*/
const deleteResolucion = async (data) => {
  try {
    const confirmar = await showConfirmation("¿Estás seguro de eliminar esta resolución?", "¡No podrás revertir esto!", "question");
    if (confirmar) {
      const resp = await enviarPeticion({
        link: '../models/datosFiscales.php',
        op: 'd_resolucion',
        id: data.Id
      });

      if (!resp.ok) {
        Swal.fire("¡Error!", resp.message, "error");
        return;
      }
      const datos = await enviarPeticion({
        link: '../models/datosFiscales.php',
        op: 's_resoluciones',
        Id: data.id_datos_fiscales
      });
      await listarResoluciones(datos);
      Swal.fire("¡Correcto!", resp.message, "success");
      return;
    }
  } catch (e) {
    console.log(e);
    Swal.fire("Error", "Se genero un error interno en el sistema " + e, "error");
  }
}
/*
@editar Resoluciones
*/
const editResolucion = (data) => {
  $("#idResolucion").val(data.Id);
  llenarFormulario(data, 'form-datos-resoluciones');
}
/*
@limpiar
*/
const limpiar = async () => {
  $("#result").html('');
  $("#idTercero").val(0);
  $("#idResolucion").val(0);
  $('#responsabilidades').val('').trigger('change');
  $('#impuestos').val('').trigger('change');
  limpiarFormulario('form-datos-fiscales');
  limpiarFormulario('form-datos-resoluciones');
  $('[data-bs-target="#navs-secuenciales"]').addClass('disabled').removeAttr('disabled');
  $("#labelProfile").css('color', '#dedede');
  $("#labelProfileNomina").css('color', '#dedede');
  $("#tokenUser").val(await uniqueID());
  $("#tokenPassword").val(await uniqueID());
  $("#nit").focus();
  $('#logoEmpresa').val('');
  $('#preview').attr('src', '').hide();
  $('#previewContainer').hide();
  fechaActual([[
    'Fecha', 'FechaFin'
  ], [
    'FechaIniR', 'FechaFinR'
  ]]);

};
const validarCampoSelect = (campoId) => {
  const selectedValue = $(campoId).val();
  // Si el campo está vacío (array con una cadena vacía o un array vacío)
  if (selectedValue.length === 1 && selectedValue[0] === "") {
    $(campoId).addClass('is-invalid');
    return false; // Retorna falso si el campo es inválido
  } else {
    $(campoId).removeClass('is-invalid');
    return true; // Retorna verdadero si el campo es válido
  }
};
/*
 @Guardar generales
*/
const guardar = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software')
  try {
    const resp = await enviarPeticion(data);
    console.log(resp);
    if (resp.ok) {
      Swal.fire('Correcto!', resp.message, 'success');
      limpiar();
    } else {
      Swal.fire('Error!', resp.message, 'danger');
    }
  } catch (e) {
    console.log(e);
    dissminSwal();
    Swal.fire('Error!', `Un error ha ocurrido, por favor notifique a su administrador. ${e}`, 'error');
  }
};

/*
 @Guardar Resoluciones
*/
const guardarResoluciones = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software')
  try {
    const resp = await enviarPeticion(data);
    if (resp.ok) {
      const datos = await enviarPeticion({
        link: '../models/datosFiscales.php',
        op: 's_resoluciones',
        Id: data.get('id_datos_fiscales')
      });
      $("#idResolucion").val(0);
      limpiarFormulario('form-datos-resoluciones');
      await listarResoluciones(datos);
      Swal.fire('Correcto!', resp.message, 'success');
    } else {
      Swal.fire('Error!', resp.message, 'error');
    }
  } catch (e) {
    console.log(e);
    dissminSwal();
    Swal.fire('Error!', `Un error ha ocurrido, por favor notifique a su administrador. ${e}`, 'error');
  }
};

const validarTipoUsuario = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/datosFiscales.php',
      op: 's_tipo_usuario'
    });
    if (resp.nit) {
      const valor = resp.nit;
      if ($("#nit").length) $("#nit").val(valor);
      const dv = calcularDigitoVerificacion(valor);
      if ($("#nitDv").length) $("#nitDv").val(dv);
      validarNIT(valor);
    }
  } catch (e) {
    console.error("Error en validarTipoUsuario:", e);
    Swal.fire("Error", "Se generó un error interno en el sistema", "error");
  }
};

const aplicarATodos = () => {
  // Obtener datos de la primera card (Recibo de Factura)
  const tipoDocumento = $('#usrReciboTipo').val();
  const numeroDoc = $('#usrReciboDoc').val();
  const digito = $('#usrReciboDv').val();
  const nombres = $('#usrReciboNombre').val();
  const apellidos = $('#usrReciboApellido').val();

  // Aplicar a la segunda card (Aceptación)
  $('#usrAceptTipo').val(tipoDocumento);
  $('#usrAceptboDoc').val(numeroDoc);
  $('#usrAceptDv').val(digito);
  $('#usrAceptNombre').val(nombres);
  $('#usrAceptApellido').val(apellidos);

  // Aplicar a la tercera card (Reclamo)
  $('#usrRechazaTipo').val(tipoDocumento);
  $('#usrRechazaDoc').val(numeroDoc);
  $('#usrRechazaDv').val(digito);
  $('#usrRechazaNombre').val(nombres);
  $('#usrRechazaApellido').val(apellidos);

  // Mostrar notificación de éxito
  toastr.success('Datos aplicados correctamente a todas las secciones', 'Éxito', {
    positionClass: "toast-top-right",
    timeOut: 3000
  });
}

const habilitarDian = async () => {

  try {
    showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

    const tokenUser = $("#tokenUser").val();
    const tokenPassword = $("#tokenPassword").val();

    let resp = await enviarPeticion({
      link: '../models/Fe/Controllers/habilitarDian.php',
      tokenUser: tokenUser,
      tokenPassword: tokenPassword
    });
    console.log("RESPUESTA CRUD:", resp);

    if (!resp) {
      Swal.fire("Error", "Respuesta vacía del servidor", "error");
      return;
    }

    if (!resp.ok) {
      Swal.fire("Error", resp.message, "error");
      return;
    }

    resp = resp.message;

    if (!resp.ok) {
      Swal.fire("Error", resp.message, "error");
      return;
    }

    // Construir mensaje
    let htmlMessage = `<strong>Respuesta recibida</strong>`;

    // Si viene data
    if (resp.data) {

      htmlMessage += `<br><br><strong>Datos:</strong><br>`;
      htmlMessage += `
        <b>IsValid:</b> ${resp.data.IsValid}<br>
        <b>StatusCode:</b> ${resp.data.StatusCode}<br>
        <b>StatusDescription:</b> ${resp.data.StatusDescription}<br>
        <b>XmlFileName:</b> ${resp.data.XmlFileName}<br>
        <b>TypeDocument:</b> ${resp.data.TypeDocument}<br>
        <b>Fecha Envío:</b> ${resp.data.fechaEnvio}<br>
        <b>Fecha Validación:</b> ${resp.data.fechaValidacion}<br>
      `;

      // Si vienen errores en array
      if (Array.isArray(resp.data.ErrorMessage) && resp.data.ErrorMessage.length > 0) {
        htmlMessage += `<br><strong>Errores:</strong><ul>`;
        resp.data.ErrorMessage.forEach(err => {
          htmlMessage += `<li>${err}</li>`;
        });
        htmlMessage += `</ul>`;
      }
    }

    Swal.fire({
      icon: resp.data.IsValid ? "success" : "warning",
      title: resp.data.IsValid ? "Operación exitosa" : "Aviso",
      html: htmlMessage,
      width: 700
    });

  } catch (e) {
    console.error("Error en habilitarDian:", e);
    Swal.fire("Error", "Se generó un error interno en el sistema", "error");
  }
};

const renderResumenDocumentosFE = (enviados, pendientes) => {
  return `
    <div id="resumen-documentos-fe" class="row mb-2">
      <div class="col-md-6">
        <div class="alert alert-success py-2 mb-2">
          <i class="fa-solid fa-check-circle"></i>
          Enviados: <strong>${enviados}</strong>
        </div>
      </div>
      <div class="col-md-6">
        <div class="alert alert-warning py-2 mb-2">
          <i class="fa-solid fa-clock"></i>
          Pendientes por enviar: <strong>${pendientes}</strong>
        </div>
      </div>
    </div>`;
};

const actualizarResumenDocumentosFE = () => {
  const $tabla = $("#listado_documentos table");
  if ($tabla.length === 0) {
    return;
  }

  let enviados = 0;
  let pendientes = 0;

  $tabla.find("tbody tr").each(function () {
    const txtEstado = $(this).find(".estado-fe").text().toLowerCase();
    if (txtEstado.includes('no enviado')) {
      pendientes += 1;
    } else if (txtEstado.includes('enviado')) {
      enviados += 1;
    }
  });

  const resumenHtml = renderResumenDocumentosFE(enviados, pendientes);
  const $resumenActual = $("#resumen-documentos-fe");

  if ($resumenActual.length > 0) {
    $resumenActual.replaceWith(resumenHtml);
  } else {
    $("#listado_documentos").prepend(resumenHtml);
  }
};

const ConsultarDocumentos = async () => {
  const fhIni = $("#Fecha").val();
  const fhFin = $("#FechaFin").val();
  const sw = $("#TipoDocumento").val();
  const tipo = $("#ClaseDocumento").val();
  const estado = $("#estado").val();

  if (!fhIni || !fhFin) {
    Swal.fire('Oops', 'Debe seleccionar un rango de fechas.', 'warning');
    return;
  }
  if (!sw) {
    Swal.fire('Oops', 'Debe seleccionar una clase de documento.', 'warning');
    return;
  }

  showLoadingWithTetrisLogo('Consultando documentos', 'ISA Software');

  try {
    const data = {
      link: '../models/datosFiscales.php',
      op: 's_documentos_fe',
      fhIni,
      fhFin,
      sw,
      tipo,
      estado
    };

    const resp = await enviarPeticion(data);
    dissminSwal();

    if (!resp || !Array.isArray(resp.data) || resp.data.length === 0) {
      $("#listado_documentos").html(`
        <div class="alert alert-warning">
          <i class="fa-solid fa-circle-exclamation"></i> No se encontraron documentos para los filtros seleccionados.
        </div>`);
      return;
    }

    let enviados = 0;
    let pendientes = 0;

    const rows = resp.data.map(d => {
      const enviado = d.cufe && d.cufe !== '';
      if (enviado) {
        enviados += 1;
      } else {
        pendientes += 1;
      }
      const estadoCell = enviado
        ? `<span class="badge bg-success"><i class="fa-solid fa-check"></i> Enviado</span>`
        : `<span class="badge bg-danger me-1"><i class="fa-solid fa-xmark"></i> No enviado</span>
           <button class="btn btn-xs btn-outline-primary py-0 px-1" title="Enviar FE"
             onclick="enviarFEDocumento(${d.id}, ${sw}, this)">
             <i class="fa-solid fa-paper-plane"></i>
           </button>`;

      return `<tr id="row-fe-${d.id}">
        <td>${d.id}</td>
        <td>${d.tipo} / ${d.numero}</td>
        <td>${Number(d.valor_total).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td>${d.nit}</td>
        <td>${d.cliente}</td>
        <td>${d.fecha}</td>
        <td>${d.email}</td>
        <td class="text-center estado-fe">${estadoCell}</td>
        <td class="text-center text-nowrap">

            <button class="btn btn-sm btn-outline-primary"
                    onclick="verDIAN('${d.cufe}')"
                    title="Ver en DIAN">
                <i class="fa-solid fa-magnifying-glass"
                  style="color: #63E6BE;"></i>
                DIAN
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-danger btnDescargarPDF"
                    data-prefijo="${d.tipo}"
                    data-consecutivo="${d.numero}"
                    title="Descargar PDF">
                <i class="fa-solid fa-file-pdf"></i>
                PDF
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-success btnDescargarXML"
                    data-prefijo="${d.tipo}"
                    data-consecutivo="${d.numero}"
                    title="Descargar XML">
                <i class="fa-solid fa-file-code"></i>
                XML
            </button>
        </td>
      </tr>`;
    }).join('');

    const resumen = renderResumenDocumentosFE(enviados, pendientes);

    const tabla = `
      ${resumen}
      <table class="table table-sm table-striped table-hover table-bordered table-responsive-md">
        <thead class="cabecera table-primary">
          <tr>
            <th>Id</th>
            <th>Tipo / Número</th>
            <th>Valor</th>
            <th>NIT</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Email</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>`;

    $("#listado_documentos").html(tabla);
    $(document).on('click', '.btnDescargarPDF', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const prefijo = $(this).data('prefijo');
      const consecutivo = $(this).data('consecutivo');
      window.open(
        `https://www.thecyctechnology.com/api-fe/public/index.php/dian-service/getDocumentFile?ext=pdf&tipo=${encodeURIComponent(prefijo)}&numero=${encodeURIComponent(consecutivo)}`,
        '_blank'
      );
    });
    $(document).on('click', '.btnDescargarXML', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const prefijo = $(this).data('prefijo');
      const consecutivo = $(this).data('consecutivo');
      window.open(
        `https://www.thecyctechnology.com/api-fe/public/index.php/dian-service/getDocumentFile?ext=xml&tipo=${encodeURIComponent(prefijo)}&numero=${encodeURIComponent(consecutivo)}`,
        '_blank'
      );
    });

  } catch (e) {
    dissminSwal();
    console.error(e);
    $("#listado_documentos").html(`<div class="alert alert-danger">Error al consultar: ${e.message || e}</div>`);
  }
};


const enviarFEDocumento = async (id, sw, btn) => {
  const $btn = $(btn);
  const $row = $btn.closest('tr');
  const $estado = $row.find('.estado-fe');
  const $cufe = $row.find('.cufe-fe');

  $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

  try {
    const resp = await enviarDocumentoElctronico(id, sw);
    console.log("RESPUESTA ENVÍO:", resp);
    const data = resp?.data ?? {};
    if (resp && resp.ok && data.IsValid === true) {
      // Documento aceptado y válido por la DIAN
      $estado.html(`<span class="badge bg-success"><i class="fa-solid fa-check"></i> Enviado</span>`);
      $cufe.text(data.UUID ?? '');
      actualizarResumenDocumentosFE();
    } else if (resp && resp.ok && data.IsValid === false) {
      // Enviado pero rechazado/con errores por la DIAN
      const errores = Array.isArray(data.ErrorMessage) && data.ErrorMessage.length
        ? data.ErrorMessage.map(e => `<li>${e}</li>`).join('')
        : '';
      const descripcion = data.StatusDescription ?? resp.message ?? 'La DIAN rechazó el documento.';
      $estado.html(`
        <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation"></i> Rechazado DIAN</span>
        <button class="btn btn-xs btn-outline-primary py-0 px-1 ms-1" title="Reintentar"
          onclick="enviarFEDocumento(${id}, ${sw}, this)">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
        <br><small class="text-danger">${descripcion}${errores ? `<ul class="mb-0 ps-3">${errores}</ul>` : ''}</small>`);
      actualizarResumenDocumentosFE();
    } else {
      const mensaje = resp?.message ?? 'No fue posible enviar el documento.';
      $estado.html(`
        <span class="badge bg-danger"><i class="fa-solid fa-xmark"></i> No enviado</span>
        <button class="btn btn-xs btn-outline-primary py-0 px-1 ms-1" title="Reintentar"
          onclick="enviarFEDocumento(${id}, ${sw}, this)">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
        <br><small class="text-danger">${mensaje}</small>`);
      actualizarResumenDocumentosFE();
    }
  } catch (e) {
    console.error(e);
    $estado.html(`
      <span class="badge bg-danger"><i class="fa-solid fa-xmark"></i> No enviado</span>
      <button class="btn btn-xs btn-outline-primary py-0 px-1 ms-1" title="Reintentar"
        onclick="enviarFEDocumento(${id}, ${sw}, this)">
        <i class="fa-solid fa-paper-plane"></i>
      </button>
      <br><small class="text-danger">${e.message ?? 'Error inesperado'}</small>`);
    actualizarResumenDocumentosFE();
  }
};

const EnviarTodosDocumentos = async () => {
  const $btnAll = $("#EnviarTodos");
  const botonesPendientes = $("#listado_documentos .estado-fe button").toArray();

  if (botonesPendientes.length === 0) {
    Swal.fire('Información', 'No hay documentos pendientes por enviar en pantalla.', 'info');
    return;
  }

  const confirmacion = await Swal.fire({
    title: 'Confirmar envío masivo',
    text: `Se enviarán ${botonesPendientes.length} documento(s) pendientes. ¿Desea continuar?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, enviar',
    cancelButtonText: 'Cancelar'
  });

  if (!confirmacion.isConfirmed) {
    return;
  }

  const sw = $("#TipoDocumento").val() || 5;
  $btnAll.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Enviando...');

  let exitosos = 0;
  let fallidos = 0;

  try {
    for (const btn of botonesPendientes) {
      const $row = $(btn).closest('tr');
      const idText = $.trim($row.find('td').eq(0).text());
      const id = parseInt(idText, 10);

      if (!id || Number.isNaN(id)) {
        fallidos += 1;
        continue;
      }

      const antes = $row.find('.estado-fe').text().toLowerCase();
      await enviarFEDocumento(id, sw, btn);
      const despues = $row.find('.estado-fe').text().toLowerCase();

      if (antes.includes('no enviado') && despues.includes('enviado') && !despues.includes('no enviado')) {
        exitosos += 1;
      } else if (despues.includes('no enviado')) {
        fallidos += 1;
      }
    }
    await obtenerFolios();

    Swal.fire('Proceso finalizado', `Enviados: ${exitosos} | Con novedad: ${fallidos}`, fallidos > 0 ? 'warning' : 'success');
  } catch (e) {
    console.error(e);
    Swal.fire('Error', 'Ocurrió un problema en el envío masivo.', 'error');
  } finally {
    $btnAll.prop('disabled', false).html('<span class="fa-solid fa-paper-plane" aria-hidden="true"></span> Enviar todos');
  }
};

//Obtener folios 
const obtenerFolios = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/Fe/Controllers/ObtenerFoliosRestantes.php',
      tokenUser,
      tokenPassword
    });

    console.log("RESPUESTA FOLIOS:", resp);
    if (resp && resp.ok && resp.data) {
      const disponibles = parseInt(resp.data.foliosDisponibles);
      const comprados = parseInt(resp.data.foliosComprados);
      const consumidos = parseInt(resp.data.foliosConsumidos);

      // Validar que comprados sea mayor a 0 para evitar NaN e Infinity
      const tieneComprados = comprados > 0;

      const porcentajeUso = tieneComprados ? ((consumidos / comprados) * 100).toFixed(1) : 0;
      const porcentajeDisponible = tieneComprados ? ((disponibles / comprados) * 100).toFixed(1) : 0;

      // Validar que los porcentajes sean números válidos
      const pUso = isNaN(porcentajeUso) ? 0 : parseFloat(porcentajeUso);
      const pDisponible = isNaN(porcentajeDisponible) ? 0 : parseFloat(porcentajeDisponible);

      // Actualizar cards de folios
      $("#folios-disponibles-value").text(disponibles.toLocaleString());
      $("#folios-disponibles-pct").text(`${pDisponible}% del total`);

      $("#folios-consumidos-value").text(consumidos.toLocaleString());
      $("#folios-consumidos-pct").text(`${pUso}% de uso`);

      $("#folios-comprados-value").text(comprados.toLocaleString());

      // Actualizar cards de consumo por aspecto
      const aspectosArray = [
        {
          id: 'facturacion',
          consumo: parseInt(resp.data.consumo_facturacion || 0),
          ilimitado: resp.data.facturacionIlimitada === 'S'
        },
        {
          id: 'nomina',
          consumo: parseInt(resp.data.consumo_nomina || 0),
          ilimitado: resp.data.nominaIlimitada === 'S'
        },
        {
          id: 'recepcion',
          consumo: parseInt(resp.data.consumo_recepcion || 0),
          ilimitado: resp.data.recepcionIlimitada === 'S'
        }
      ];

      aspectosArray.forEach(aspecto => {
        const valueElement = $(`#consumo-${aspecto.id}-value`);
        const labelElement = $(`#consumo-${aspecto.id}-label`);

        if (aspecto.ilimitado) {
          valueElement.html(`<i class="fa-solid fa-infinity" style="color: #28a745; margin-right: 5px;"></i>${aspecto.consumo.toLocaleString()}`);
          labelElement.text('Ilimitado');
        } else {
          valueElement.text(aspecto.consumo.toLocaleString());
          labelElement.text('Consumido');
        }
      });

      // Mostrar alerta si folios bajos o sin comprados
      let alertHtml = '';
      if (!tieneComprados) {
        alertHtml = `
          <div class="alert alert-warning p-2 d-flex align-items-center" role="alert" style="font-size: 0.85rem;">
            <i class="fa-solid fa-triangle-exclamation me-2" style="font-size: 0.9rem;"></i>
            <div>
              <strong style="font-size: 0.9rem;">Sin folios disponibles</strong>
              <p class="mb-0" style="font-size: 0.75rem;">No hay folios comprados. Adquiere folios para continuar.</p>
            </div>
          </div>
        `;
      } else if (disponibles <= 600) {
        alertHtml = `
          <div class="alert ${disponibles <= 300 ? 'alert-danger' : 'alert-warning'} p-2 d-flex align-items-center" role="alert" style="font-size: 0.85rem;">
            <i class="fa-solid fa-triangle-exclamation me-2" style="font-size: 0.9rem;"></i>
            <div>
              <strong style="font-size: 0.9rem;">${disponibles <= 300 ? '¡CRÍTICO!' : '⚠️ Aviso'}</strong>
              <p class="mb-0" style="font-size: 0.75rem;">Solo <strong>${disponibles.toLocaleString()}</strong> folios disponibles. 
              ${disponibles <= 300 ? 'Adquiere más folios de inmediato.' : 'Considera adquirir más pronto.'}</p>
            </div>
          </div>
        `;
      }
      $("#folios-alerta").html(alertHtml);

    } else {
      $("#folios-alerta").html(`
        <div class="alert alert-danger p-2 d-flex align-items-center" role="alert" style="font-size: 0.85rem;">
          <i class="fa-solid fa-circle-exclamation me-2"></i>
          <div>
            <strong>Error al cargar folios</strong>
            <p class="mb-0 small">No se pudo obtener la información. Verifica tu conexión.</p>
          </div>
        </div>
      `);
    }
  } catch (e) {
    console.log(e);
    $("#folios-alerta").html(`
      <div class="alert alert-danger p-2 d-flex align-items-center" role="alert" style="font-size: 0.85rem;">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <div>
          <strong>Error</strong>
          <p class="mb-0 small">Hubo un error al cargar la información de folios.</p>
        </div>
      </div>
    `);
  }
};

const consultarAcuses = async () => {
  let fechaIni = $("#FechaIniR").val();
  let fechaFin = $("#FechaFinR").val();
  try {

    try {
      showLoadingWithTetrisLogo('Consultando documentos', 'ISA Software');
      const resp = await enviarPeticion({
        link: '../models/Fe/Controllers/ListarEventosAcuse.php',
        tokenUser,
        tokenPassword,
        fechaIni,
        fechaFin
      });
      if (resp.data.length > 0) {
        tabla = `<table class="table table-sm data-prod table-striped table-hover table-bordered table-responsive-md">
					    <thead class="cabecera table-primary">
						  <tr>
							<th data-breakpoints="xs">Nit</th>
							<th data-breakpoints="xs">Nombres</th>
							<th data-breakpoints="xs">Prefijo</th>
							<th data-breakpoints="xs">Numero</th>
							<th data-breakpoints="xs">Fecha</th>
							<th data-breakpoints="xs">DIAN</th>
                            <th data-breakpoints="xs">Recibo del bien/servicio</th>
                            <th data-breakpoints="xs">Reclamo</th>
						  </tr>
						</thead>
				<tbody>`;
        var cont = 0;
        resp.data.forEach((datos, indice) => {

          cont++;
          tabla += `<tr> 
                    <td>${datos.customerNit}</td>
                    <td>${datos.customerName}</td>
                    <td>${datos.Prefix}</td>
                    <td>${datos.Number}</td>
                    <td>${datos.fechaEnvio}</td>
                    <td align="center">
                    <button class="btn btn-sm btn-outline-primary" onclick="verDIAN('${datos.UUIDReceipt}')" title="Ver en DIAN">
                        <i class="fa-solid fa-magnifying-glass" style="color: #63E6BE;"></i> DIAN
                    </button>
                    </td>
                    <td align="center"><button class="btn btn-sm btn-outline-info" type="button" id="btnB" onclick="generarEvento('032','${datos.UUIDReceipt}')"><i class="fa-solid fa-dolly"></i>&nbsp;Recibo del bien y/o servicio</button></td>
                    <td align="center">
                      <div class="btn-group" role="group">
                        <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-triangle-exclamation"></i>&nbsp; Reclamo de la Factura</button>
                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                          <li><a class="dropdown-item" href="#" data-codigo="031" data-id="01" onclick="generarEvento('031','${datos.UUIDReceipt}','01')">Documento con inconsistencias</a></li>
                          <li><a class="dropdown-item" href="#" data-codigo="031" data-id="02" onclick="generarEvento('031','${datos.UUIDReceipt}','02')">Mercancía no entregada totalmente</a></li>
                          <li><a class="dropdown-item" href="#" data-codigo="031" data-id="03" onclick="generarEvento('031','${datos.UUIDReceipt}','03')">Mercancía no entregada parcialmente</a></li>
                          <li><a class="dropdown-item" href="#" data-codigo="031" data-id="04" onclick="generarEvento('031','${datos.UUIDReceipt}','04')">Servicio no prestado</a></li>
                        </ul>
                      </div>
                    </td>
                  </tr>`;
        });
        tabla += `</tbody></table>`

        $("#listado_documentos_recibidos").html(tabla);

      } else {
        $("#listado_documentos_recibidos").html(`<div class="alert alert-danger">
                                         <i class="fa-solid fa-bell fa-shake"></i> No se encontraron resultados para esta consulta</b>
                                        </div>`);


      }
      dissminSwal();
    } catch (error) {
      console.log(error);
      $("#listado_documentos_recibidos").html(`<div class="alert alert-danger">
									 <i class="fa-solid fa-bug fa-beat"></i> Error al enviar petición <b>${error}</b>
									</div>`);
      dissminSwal();

    }
  } catch (e) {
    console.log(e);
    dissminSwal();
    $("#listado_documentos_recibidos").html("<div class='alert alert-danger'>Error al enviar petición.</div>");
  }
};

const verDIAN = (cufe) => {
  window.open(
    `https://catalogo-vpfe.dian.gov.co/User/SearchDocument?DocumentKey=${cufe}`,
    '_blank' // <- Esto abre el enlace en una nueva pestaña
  );
};

const generarEvento = async (evento, cufe, rechazo = '') => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software');

  if (tokenUser == '' || tokenPassword == '') {
    Swal.fire('Oops', 'No hay datos de tercero, verifique e intente nuevamente ', 'error');
    dissminSwal();
    return;
  }
  try {
    const resp = await enviarPeticion({
      link: '../models/Fe/Controllers/eventosAcuse.php',
      evento,
      cufe,
      rechazo
    });
    console.log("RESPUESTA EVENTO:", resp);

    dissminSwal();

    if (resp && resp.ok && resp.data) {
      if (resp.data.ok) {
        Swal.fire("Éxito", resp.data.message, "success");
      } else {
        Swal.fire("Información", resp.data.message, "warning");
      }
    } else {
      Swal.fire("Error", (resp?.data?.message || "No se pudo generar el evento"), "error");
    }
    $("#bCufe").val('');

  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire("Error", "Ocurrió un error al procesar la solicitud", "error");
  }
}

$(async function () {

  limpiar();
  obtenerFolios();
  await regimen();
  await perfilTributario();
  await responsabilidadesTributarias();
  await impuestos();
  await tiposDocumentosFE();
  await paises('codigoPais');
  let pais = $("#codigoPais").val();
  await departamentos(pais, 'codigoDpto');
  let dpto = $("#codigoDpto").val();
  await municipios(pais, dpto, 'codigoCiudad');

  if ($("#TipoDocumento").length) {
    await cargarClaseDocumento($("#TipoDocumento").val());
  }

  $("#codigoDpto").change(async function () {
    await municipios($("#codigoPais").val(), $("#codigoDpto").val(), 'codigoCiudad');
  });

  $("#TipoDocumento").change(async function () {
    await cargarClaseDocumento($(this).val());
  });
  let vigenciaCD = await obtenerVigenciaCD();
  //valida que vigenciaCD tenga data y validDay antes de asignar el valor
  if (vigenciaCD && vigenciaCD.data && vigenciaCD.data.validDay !== undefined) {
    const dias = vigenciaCD.data.validDay;
    const $diasValor = $("#dias-valor");
    const $vigenciaCard = $("#vigencia-contenedor .card");
    const $diasIcon = $("#vigencia-contenedor .fa-certificate");

    // Asignar el valor
    $diasValor.text(dias);

    // Determinar color según vigencia
    if (dias <= 30) {
      // Crítico: menos de 30 días
      $vigenciaCard.removeClass('border-primary').addClass('border-danger');
      $diasValor.removeClass('text-primary').addClass('text-danger');
      $diasIcon.removeClass('text-primary').addClass('text-danger');
    } else if (dias <= 90) {
      // Advertencia: menos de 90 días
      $vigenciaCard.removeClass('border-primary').addClass('border-warning');
      $diasValor.removeClass('text-primary').addClass('text-warning');
      $diasIcon.removeClass('text-primary').addClass('text-warning');
    }
  }
  //hide loading
  dissminSwal();
  $('#responsabilidades,#impuestos').select2({
    placeholder: "Selecciona una o más opciones",
    allowClear: true
  });
  //Calcular digito de verificacion
  $("#nit").focusout(function () {
    const valor = $(this).val();
    if (valor.trim() !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nitDv").val(dv);
      //Se valida la existencia del nit
      validarNIT(valor);
    }

  });
  //Limpiar formulario de resoluciones
  $("#btnNuevoResoluciones").click(function () {
    $("#idResolucion").val(0);
    limpiarFormulario('form-datos-resoluciones');
  });
  //Limpiar formulario general
  $("#btnNuevoFiscales").click(function () {
    limpiar();
  });

  //btnConsultarResoluciones
  $("#btnConsultarResoluciones").click(function () {
    rangosDian();
  });

  //btnHabilitar
  $("#btnHabilitar").click(async function () {
    await habilitarDian();
  });


  $('#btnAlluser').click(function (e) {
    e.preventDefault();
    aplicarATodos();
  });


  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        Swal.fire('Imposible guardar!', 'Campos obligatorios vacíos', 'error');
        return;
      }

      const selectedResp = $('#responsabilidades').val(); // array
      const selectedImp = $('#impuestos').val(); // array

      var isValid = true;
      isValid &= validarCampoSelect('#responsabilidades');
      isValid &= validarCampoSelect('#impuestos');

      if (!isValid) {
        Swal.fire('Imposible guardar!', 'Campos obligatorios vacíos', 'error');
        return;
      }

      // Crear FormData que incluye todos los campos, incluso archivos
      const formData = new FormData(form);

      // Agregar los arrays con la notación []
      if (selectedResp && Array.isArray(selectedResp)) {
        selectedResp.forEach(val => formData.append('responsabilidades[]', val));
      }

      if (selectedImp && Array.isArray(selectedImp)) {
        selectedImp.forEach(val => formData.append('impuestos[]', val));
      }

      if (form.id === 'form-datos-fiscales') {
        formData.append('link', '../models/datosFiscales.php');
        formData.append('op', ($("#idTercero").val() !== '0' && $("#idTercero").val() !== '') ? 'u_facturador_electronico' : 'g_facturador_electronico');
        formData.append('Id', $("#idTercero").val());

        guardar(formData, true); // le pasamos true para indicar que es FormData
      } else if (form.id === 'form-datos-resoluciones') {
        const idTercero = $("#idTercero").val();
        formData.append('link', '../models/datosFiscales.php');
        formData.append('op', ($("#idResolucion").val() !== '0' && $("#idResolucion").val() !== '') ? 'u_resoluciones' : 'g_resoluciones');
        formData.append('Id', $("#idResolucion").val());
        formData.append('id_datos_fiscales', idTercero);

        if (idTercero != 0 && idTercero != '') {
          guardarResoluciones(formData, true);
        } else {
          Swal.fire('Imposible guardar!', 'No se detectó un tercero cargado.', 'error');
        }
      }
    }, false);
  });


  $('#logoEmpresa').on('change', function () {
    const input = this;
    const $preview = $('#preview');
    const $container = $('#previewContainer');

    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function (e) {
        $preview.attr('src', e.target.result).show();
        $container.show();
      };
      reader.readAsDataURL(input.files[0]);
    } else {
      $preview.hide();
      $container.hide();
    }
  });

  await validarTipoUsuario();

  //RECEPCION ELECTRONICA MANUAL
  // Objeto que mapea los botones a sus códigos de evento
  const botonesEvento = {
    "#btnA": "030",
    "#btnB": "032",
    "#btnC": "033"
  };

  // Función común para manejar los clicks
  async function manejarClick(evento) {
    const cufe = $("#bCufe").val().trim();

    if (!cufe) {
      await Swal.fire('Oops', 'Debe ingresar el código CUFE.', 'error');
      return;
    }

    const codigoEvento = botonesEvento[evento.data.selector];

    // Si es el botón C (código 033), primero enviar 030, luego 032  y finalmente 033
    if (evento.data.selector === "#btnC") {
      await generarEvento("030", cufe);
      await generarEvento("032", cufe);
      await generarEvento("033", cufe);
    } else {
      await generarEvento(codigoEvento, cufe);
    }
  }

  // Asignar eventos a los botones
  Object.entries(botonesEvento).forEach(([selector, codigo]) => {
    $(selector).click({
      selector,
      codigo
    }, manejarClick);
  });


  // Asignar eventos a las opciones del dropdown
  $(".dropdown-item").click(async function () {
    const cufe = $("#bCufe").val().trim();
    const codigoEvento = $(this).data("codigo"); // Obtiene el código desde data-codigo
    const codigoRechazo = $(this).data("id"); // Obtiene el código desde data-codigo

    if (!cufe) {
      await Swal.fire('Oops', 'Debe ingresar el código CUFE.', 'error');
      return;
    }

    await generarEvento(codigoEvento, cufe, codigoRechazo);
  });
});
