const link_ = '../models/Terceros.php';
let arrayDirecciones = [];
let arrayContactos = [];
let arrayTerceros = [];
let auditoriaTerceroData = [];
let direccionEditandoId = null;
/*Autocompletar terceros */
const autocompletarTerceros = async () => {
  let identificationNumber = $("#nit").val();
  let identificationType = $("#id_tipo_identificacion option:selected").data('tipo_dian');

  // Validar que se haya ingresado el número de identificación y el tipo
  if (!identificationNumber || !identificationType) {
    Swal.fire('Alerta', 'Por favor, ingrese el número de identificación y seleccione el tipo.', 'warning');
    return;
  }

  try {
    showLoadingWithTetrisLogo('Validando tercero', 'ISA Software');

    // Primero validar si el tercero ya existe
    const validarResp = await enviarPeticion({
      link: link_,
      filtro: identificationNumber,
      estado: '',
      op: 's_tercero'
    });

    // Si el tercero ya existe, llenar los datos del formulario
    if (validarResp.data && Array.isArray(validarResp.data) && validarResp.data.length > 0) {
      dissminSwal();
      const terceroExistente = validarResp.data[0];
      llenarFormulario(terceroExistente);
      Swal.fire('Éxito', 'Datos del tercero cargados correctamente', 'success');
      return;
    }

    // Si no existe, continuar con la consulta al servicio DIAN

    const resp = await enviarPeticion({
      link: '../models/Fe/Controllers/consultarTercero.php',
      identificationType,
      identificationNumber
    });
    console.log({ resp });

    // Parsear la respuesta
    if (resp && resp.data) {
      // resp.data es un string JSON que necesita ser parseado
      const dataStr = resp.data;
      const parsedData = JSON.parse(dataStr);

      // Validar que no haya error y que statusCode sea 200
      if (!parsedData.error && parsedData.data && parsedData.data.statusCode === 200) {
        const { receiverName, receiverEmail } = parsedData.data;

        // Guardar email en variable
        let email_fe = receiverEmail;

        // Separar nombres y apellidos del receiverName
        // Limpiar espacios múltiples y dividir
        const nombreCompleto = receiverName.trim().replace(/\s+/g, ' ').split(' ');

        // Los primeros dos elementos son apellidos, el resto son nombres
        const apellidos = nombreCompleto.slice(0, 2).join(' ');
        const nombres = nombreCompleto.slice(2).join(' ');

        // Obtener primer nombre, segundo nombre, primer apellido, segundo apellido
        const nombresArray = nombres.split(' ').filter(n => n.length > 0);
        const apellidosArray = apellidos.split(' ').filter(a => a.length > 0);

        const primerApellido = apellidosArray[0] || '';
        const segundoApellido = apellidosArray[1] || '';
        const primerNombre = nombresArray[0] || '';
        const segundoNombre = nombresArray[1] || '';

        // Llenar los campos del formulario
        $("#primer_nombre").val(primerNombre);
        $("#segundo_nombre").val(segundoNombre);
        $("#primer_apellido").val(primerApellido);
        $("#segundo_apellido").val(segundoApellido);
        $("#email_fe").val(email_fe);
        $("#txtEmail").val(email_fe);
        // Actualizar tratamiento
        actualizarTratamiento();

        $("#razon_comercial").val(receiverName);

        Swal.fire('Éxito', 'Datos cargados correctamente', 'success');
      } else {
        Swal.fire('Error', parsedData.message || 'No se encontraron datos para este tercero', 'error');
      }
    } else {
      Swal.fire('Error', 'Formato de respuesta inválido', 'error');
    }

    dissminSwal();
  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire('Error!', `Un error ha ocurrido, por favor notifique a su administrador. ${e}`, 'error');
  }
}


/*
 @Guardar - Actualizar 
*/

const guardar = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software');
  data.cupo_credito = data.cupo_credito.replace(/,/g, '');
  try {
    const checkboxes = [
      "autorretenedor",
      "agente_retenedor",
      "gran_contribuyente",
      "edi",
      "controlados",
      "institucionales"
    ];
    checkboxes.forEach(id => {
      data[id] = $(`#${id}`).is(':checked') ? 'S' : 'N';
    });
    const id_tercero = $("#txtCodigo").val().trim();
    if (id_tercero == 0 || id_tercero == '') {
      data.op = 'g_tercero';
    } else {
      data.op = 'u_tercero';
      data.id = id_tercero;
    }
    data.link = link_;
    data.direcciones = JSON.stringify(arrayDirecciones);
    data.contactos = JSON.stringify(arrayContactos);
    console.log({ data });
    const resp = await enviarPeticion(data);
    console.log({ resp });
    if (!resp.error) {
      initToast('bg-success', 'Correcto!', resp.message, 'success');
      limpiar();
    } else {
      initToast('bg-danger', 'Error!', resp.message, 'danger');
    }
    dissminSwal();
  } catch (e) {
    console.log(e);
    dissminSwal();
    Swal.fire('Error!', `Un error ha ocurrido, por favor notifique a su administrador. ${e}`, 'error');
  }
}
/*
 @ Limpiar
*/
const limpiar = (focus = true) => {
  $("#txtCodigo").val('');
  $("#btnAuditoria").prop('disabled', true);
  limpiarFormulario('FormTerceroBasicos');
  arrayDirecciones = [];
  arrayContactos = [];
  direccionEditandoId = null;
  $('#tbDirecciones tbody').html('');
  $('#tbContactos tbody').html('');
  limpiarFormularioDireccion();

  // Limpiar campos de contactos
  $('#txtContactoNombres').val('');
  $('#txtContactoApellidos').val('');
  $('#txtContactoTelefono').val('');
  $('#txtContactoCelular').val('');
  $('#txtContactoEmail').val('');
}


/*
 @ Tipos de terceros
*/
const tiposTerceros = async () => {
  try {
    const data = {
      link: '../models/tiposTerceros.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);

    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.tipo_tercero.toUpperCase()} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_tercero").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Condiciones de pago
*/

/*
 @ Regimenes
*/
const regimen = async () => {
  try {
    const data = {
      link: '../models/Regimenes.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.regimen.toUpperCase()}</option>`;
    });
    $("#id_regimen").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Listas de precios
*/
const llenarSelectListas = async () => {

  let listas = await listasPrecios();

  if (!Array.isArray(listas)) {
    console.warn("El parámetro listas no es un array");
    return;
  }

  const options = [
    `<option value="0" selected>Seleccione una lista</option>`,
    ...listas.map(({ id, Descripcion }) =>
      `<option value="${id}">${id} - ${Descripcion}</option>`
    )
  ];

  $("#lista_precios").html(options.join(""));
}
/*
 @ Perfiles tributarios
*/
const perfilTributario = async () => {
  try {
    const data = {
      link: '../models/perfilesTributarios.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);

    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.tipo} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_perfil_tributario").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Zonas de ventas
*/
const zonasVentas = async () => {
  try {
    const data = {
      link: '../models/zonasVentas.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.codigo_zona} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_zona_ventas").html(option);
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Tipo de identificacion del tercero
*/
const tipoIdentificacion = async () => {
  try {
    const data = {
      link: '../models/tipoIdentificaciones.php',
      filtro: '',
      op: 'listar'
    };

    var resp = await enviarPeticion(data);
    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}" data-tipo_dian="${d.tipo_dian}">${d.tipo_dian.toUpperCase()} -${d.tipo_identificacion.toUpperCase()} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_identificacion").html(option);
  } catch (e) {
    console.log(e);
  }
}

/*
 @ Vendedores
*/
const vendedores = async () => {
  try {
    const data = {
      link: '../models/Terceros.php',
      op: 'listar_vendedores'
    };
    var resp = await enviarPeticion(data);
    let option = `<option value="0">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.tratamiento} ${d.razon_comercial}</option>`;
    });
    $("#id_vendedor_principal").html(option);
    $("#id_vendedor_secundario").html(option);
  } catch (e) {
    console.log(e);
  }
}

/*
 @ Agregar direcciones
*/
const limpiarFormularioDireccion = async () => {
  await paises('slcPais');
  let pais = $("#slcPais").val();
  await departamentos(pais, 'slcDepartamento');
  let dpto = $("#slcDepartamento").val();
  await municipios(pais, dpto, 'slcCiudad');
  $('#txtDireccion').val('');
  $('#txtTelefono').val('');
  $('#txtCelular').val('');
  $('#txtEmail').val('');
  $('#dir_principal').prop('checked', false);
  $('#btnAddDireccion').html('<i class="fa-regular fa-square-plus"></i>&nbsp; Agregar');
  direccionEditandoId = null;
};

const obtenerDatosDireccionFormulario = () => {
  const pais = $('#slcPais').val();
  const txt_pais = $('#slcPais option:selected').text();
  const dpto = $('#slcDepartamento').val();
  const txt_dpto = $('#slcDepartamento option:selected').text();
  const mpio = $('#slcCiudad').val();
  const txt_mpio = $('#slcCiudad option:selected').text();
  const dir = $('#txtDireccion').val();
  const tel = $('#txtTelefono').val();
  const cel = $('#txtCelular').val();
  const mail = $('#txtEmail').val();
  const principal = $('#dir_principal').is(':checked') ? '1' : '0';

  return {
    pais,
    dpto,
    mpio,
    dir,
    tel,
    cel,
    mail,
    txt_pais,
    txt_dpto,
    txt_mpio,
    principal
  };
};

const renderizarTablaDirecciones = () => {
  $('#tbDirecciones tbody').html('');

  arrayDirecciones.forEach((item) => {
    const checked = item.principal == 1 || item.principal === '1' || item.principal === true ? 'checked' : '';
    const esPersistida = Number(item.id) > 0;
    const deleteTitle = esPersistida
      ? 'No se puede eliminar una dirección ya registrada porque puede afectar documentos.'
      : 'Eliminar dirección';
    const deleteDisabled = esPersistida ? 'disabled' : '';

    $('#tbDirecciones tbody').append(`
      <tr data-id-reg="${item.id_reg}">
        <td>${item.txt_pais || ''}</td>
        <td>${item.txt_dpto || ''}</td>
        <td>${item.txt_mpio || ''}</td>
        <td>${item.dir || ''}</td>
        <td>${item.tel || ''}</td>
        <td>${item.cel || ''}</td>
        <td>${item.mail || ''}</td>
        <td align="center">
          <div class="d-flex gap-1 justify-content-center">
            <button class="btn btn-sm btn-outline-primary btnEditar" type="button" title="Editar dirección"><i class="fa-regular fa-pen-to-square"></i></button>
            <button class="btn btn-sm btn-outline-danger btnEliminar" type="button" title="${deleteTitle}" ${deleteDisabled}><i class="fa-regular fa-trash-can"></i></button>
          </div>
        </td>
        <td><input type="radio" ${checked} onclick="marcarPrincipal(${item.id_reg})" name="principal"></td>
      </tr>
    `);
  });
};

const cargarDireccionEnFormulario = (idReg) => {
  const item = arrayDirecciones.find((element) => element.id_reg == idReg);
  if (!item) return;

  $('#slcPais').val(item.pais || '');
  $('#slcDepartamento').val(item.dpto || '');
  $('#slcCiudad').val(item.mpio || '');
  $('#txtDireccion').val(item.dir || '');
  $('#txtTelefono').val(item.tel || '');
  $('#txtCelular').val(item.cel || '');
  $('#txtEmail').val(item.mail || '');
  $('#dir_principal').prop('checked', item.principal == 1 || item.principal === '1' || item.principal === true);
  $('#btnAddDireccion').html('<i class="fa-regular fa-floppy-disk"></i>&nbsp; Actualizar');
  direccionEditandoId = idReg;
};

const addDirecciones = () => {
  const data = obtenerDatosDireccionFormulario();

  // Verificar que los campos no estén vacíos
  if (!data.pais || !data.dpto || !data.mpio || !data.dir || !data.tel || !data.cel || !data.mail) {
    Swal.fire('Oops', 'Por favor, complete todos los campos.', 'error');
    return;
  }

  const id_reg = direccionEditandoId !== null
    ? direccionEditandoId
    : Date.now() + Math.floor(Math.random() * 1000);

  // Si es el único registro, obligatoriamente será principal
  let principalValue = data.principal;

  if (
    (direccionEditandoId === null && arrayDirecciones.length === 0) ||
    (direccionEditandoId !== null && arrayDirecciones.length === 1)
  ) {
    principalValue = '1';
  }

  if (direccionEditandoId !== null) {

    const index = arrayDirecciones.findIndex(item => item.id_reg == direccionEditandoId);

    if (index !== -1) {
      const currentItem = arrayDirecciones[index];

      if (principalValue === '1') {
        arrayDirecciones.forEach(element => {
          if (element.id_reg !== id_reg) {
            element.principal = '0';
          }
        });
      }

      arrayDirecciones[index] = {
        ...currentItem,
        ...data,
        id_reg: currentItem.id_reg,
        id: currentItem.id,
        eliminar: currentItem.eliminar,
        principal: principalValue
      };
    }

  } else {

    if (principalValue === '1') {
      arrayDirecciones.forEach(element => {
        element.principal = '0';
      });
    }

    arrayDirecciones.push({
      id_reg,
      pais: data.pais,
      dpto: data.dpto,
      mpio: data.mpio,
      dir: data.dir,
      tel: data.tel,
      cel: data.cel,
      mail: data.mail,
      txt_pais: data.txt_pais,
      txt_dpto: data.txt_dpto,
      txt_mpio: data.txt_mpio,
      id: 0,
      eliminar: '',
      principal: principalValue
    });
  }

  renderizarTablaDirecciones();
  limpiarFormularioDireccion();
};
/*
 @ Agregar contactos
*/
const addContactos = () => {
  const nombres = $('#txtContactoNombres').val();
  const apellidos = $('#txtContactoApellidos').val();
  const tel = $('#txtContactoTelefono').val();
  const cel = $('#txtContactoCelular').val();
  const mail = $('#txtContactoEmail').val();

  // Verificar que los campos no estén vacíos
  if (!nombres || !apellidos || !tel || !cel || !mail) {
    Swal.fire('Oops', 'Por favor, complete todos los campos.', 'error');
    return;
  }
  const newRow = `
      <tr>
        <td>${nombres}</td>
        <td>${apellidos}</td>
        <td>${tel}</td>
        <td>${cel}</td>
        <td>${mail}</td>
        <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar"><i class="fa-regular fa-trash-can"></i></button></td>
      </tr>
    `;
  $('#tbContactos tbody').append(newRow);
  arrayContactos.push({
    nombres: nombres,
    apellidos: apellidos,
    tel: tel,
    cel: cel,
    mail: mail,
    id: 0,
    eliminar: ''
  });
  $('#txtContactoNombres').val('');
  $('#txtContactoApellidos').val('');
  $('#txtContactoTelefono').val('');
  $('#txtContactoCelular').val('');
  $('#txtContactoEmail').val('');
}
/*
 @ Crear campo tratamiento
*/
const actualizarTratamiento = () => {
  // Obtener los valores de los campos
  var primerNombre = $('#primer_nombre').val().trim();
  var segundoNombre = $('#segundo_nombre').val().trim();
  var primerApellido = $('#primer_apellido').val().trim();
  var segundoApellido = $('#segundo_apellido').val().trim();

  // Concatenar los nombres y apellidos
  var tratamiento = '';
  if (primerNombre) {
    tratamiento += primerNombre + ' ';
  }
  if (segundoNombre) {
    tratamiento += segundoNombre + ' ';
  }
  if (primerApellido) {
    tratamiento += primerApellido + ' ';
  }
  if (segundoApellido) {
    tratamiento += segundoApellido;
  }
  // Actualizar el campo de Tratamiento
  $('#tratamiento').val(tratamiento.trim());
}
/*
 @ Convertir formulario a mayusculas
*/
const convertirAMayusculas = (form) => {
  var inputs = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
  inputs.forEach(function (input) {
    input.value = input.value.toUpperCase();
  });
}

/*
 @llenarFormulario
*/
const llenarFormulario = async (data) => {
  try {
    $("#txtCodigo").val(data.id);
    $("#btnAuditoria").prop('disabled', false);
    ShowDataFrom(data, 'FormTerceroBasicos', ['cupo_credito']);
    arrayDirecciones = [];
    arrayContactos = [];
    $('#tbDirecciones tbody').html('');
    $('#tbContactos tbody').html('');
    limpiarFormularioDireccion();
    getDirecciones(data.id);
    getContactos(data.id);
    const checkboxes = [
      "autorretenedor",
      "agente_retenedor",
      "gran_contribuyente",
      "edi",
      "controlados",
      "institucionales"
    ];

    checkboxes.forEach(id => {
      $(`#${id}`).prop('checked', data[id] === 'S');
    });

  } catch (e) {
    console.log(e);
  }
};

const marcarPrincipal = (id) => {
  arrayDirecciones.forEach((element, idx) => {
    element.principal = 0;
    if (element.id_reg == id) {
      element.principal = 1
    }
  });
}

const mapearAliasFieldTercero = {
  // Campos principales
  'id': 'ID',
  'id_tercero': 'ID Tercero',
  'primer_nombre': 'Nombre',
  'segundo_nombre': 'Segundo Nombre',
  'primer_apellido': 'Apellido',
  'segundo_apellido': 'Segundo Apellido',
  'nit': 'Número Identificación',
  'nit_digito': 'Dígito Verificación',
  'tratamiento': 'Razón Comercial',
  'email': 'Email',
  'email_fe': 'Email Facturación',
  'cupo_credito': 'Cupo Crédito',
  'estado': 'Estado',

  // Tipos y clasificaciones
  'id_tipo_tercero': 'Clasificación',
  'id_tipo_identificacion': 'Tipo Identificación',
  'id_regimen': 'Régimen Tributario',
  'id_perfil_tributario': 'Perfil Tributario',
  'id_zona_ventas': 'Zona de Ventas',
  'id_vendedor_principal': 'Vendedor Principal',
  'id_vendedor_secundario': 'Vendedor Secundario',
  'lista_precios': 'Lista Precios',

  // Indicadores booleanos
  'autorretenedor': 'Es Autorretenedor',
  'agente_retenedor': 'Es Agente Retenedor',
  'gran_contribuyente': 'Es Gran Contribuyente',
  'edi': 'Usa EDI',
  'controlados': 'Es Controlado',
  'institucionales': 'Es Institucional',

  // Dirección
  'codigo_pais': 'País',
  'codigo_dpto': 'Departamento',
  'codigo_mpio': 'Municipio',
  'direccion': 'Dirección',
  'telefono': 'Teléfono',
  'celular': 'Celular',
  'principal': 'Principal',

  // Contacto
  'nombres': 'Nombres',
  'apellidos': 'Apellidos'
};

const obtenerAliasAuditoria = (nombreCampo) => {
  if (!nombreCampo) return 'Campo';
  // Extrae solo la última parte después del punto si existe (ej: "Tercero.id_vendedor_principal" → "id_vendedor_principal")
  const campoLimpio = String(nombreCampo).split('.').pop();
  return mapearAliasFieldTercero[campoLimpio] || campoLimpio.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const parseDetalleAuditoriaTercero = (detalle) => {
  if (!detalle) return null;
  try {
    return typeof detalle === 'string' ? JSON.parse(detalle) : detalle;
  } catch (e) {
    return detalle;
  }
};

const flattenObjectAuditoriaTercero = (obj, prefix = '', out = {}) => {
  if (obj === null || obj === undefined) return out;

  Object.keys(obj).forEach((key) => {
    const value = obj[key];
    const nextKey = prefix ? `${prefix}.${key}` : key;

    if (Array.isArray(value)) {
      out[nextKey] = value;
      return;
    }

    if (value && typeof value === 'object') {
      flattenObjectAuditoriaTercero(value, nextKey, out);
      return;
    }

    out[nextKey] = value;
  });

  return out;
};

const escapeHtmlAuditoriaTercero = (text) => {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

const normalizarComparacionAuditoriaTercero = (valor) => {
  if (valor === null || valor === undefined) return '';
  if (typeof valor === 'object') {
    try {
      return JSON.stringify(valor);
    } catch (e) {
      return String(valor);
    }
  }
  return String(valor);
};

const renderizarValorAuditoriaTercero = (valor) => {
  if (valor === null || valor === undefined || valor === '') {
    return '';
  }

  if (Array.isArray(valor)) {
    if (valor.length === 0) {
      return '<span class="text-muted">[]</span>';
    }

    const primer = valor[0];
    if (primer && typeof primer === 'object' && !Array.isArray(primer)) {
      const headers = [...new Set(valor.flatMap(item => Object.keys(item || {})))];
      let rows = '';
      valor.forEach((item) => {
        rows += '<tr>';
        headers.forEach((h) => {
          rows += `<td>${escapeHtmlAuditoriaTercero(item && item[h] !== undefined ? item[h] : '')}</td>`;
        });
        rows += '</tr>';
      });

      return `
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light"><tr>${headers.map(h => `<th>${obtenerAliasAuditoria(h)}</th>`).join('')}</tr></thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      `;
    }

    let rows = '';
    valor.forEach((item, idx) => {
      rows += `<tr><td>${idx + 1}</td><td>${escapeHtmlAuditoriaTercero(item)}</td></tr>`;
    });

    return `
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light"><tr><th>#</th><th>Valor</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `;
  }

  if (typeof valor === 'object') {
    const plano = flattenObjectAuditoriaTercero(valor || {});
    let rows = '';
    Object.keys(plano).forEach((k) => {
      rows += `<tr><td>${obtenerAliasAuditoria(k)}</td><td>${escapeHtmlAuditoriaTercero(plano[k])}</td></tr>`;
    });
    return `
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light"><tr><th>Campo</th><th>Valor</th></tr></thead>
          <tbody>${rows || '<tr><td colspan="2" class="text-center">Sin datos</td></tr>'}</tbody>
        </table>
      </div>
    `;
  }

  return escapeHtmlAuditoriaTercero(valor);
};

const construirTablaCampoValorAuditoriaTercero = (titulo, data) => {
  const plano = flattenObjectAuditoriaTercero(data || {});
  let rows = '';
  Object.keys(plano).forEach((k) => {
    rows += `<tr><td>${obtenerAliasAuditoria(k)}</td><td>${renderizarValorAuditoriaTercero(plano[k])}</td></tr>`;
  });

  return `
    <div class="mb-3">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped align-middle">
          <thead class="table-light"><tr><th>Campo</th><th>Valor</th></tr></thead>
          <tbody>${rows || '<tr><td colspan="2" class="text-center">Sin datos</td></tr>'}</tbody>
        </table>
      </div>
    </div>
  `;
};

const construirDetalleAuditoriaTercero = (detalle) => {
  const parsed = parseDetalleAuditoriaTercero(detalle);
  if (!parsed) return `<div class="alert alert-warning">Sin detalle disponible</div>`;
  if (typeof parsed === 'string') return `<div class="alert alert-secondary">${escapeHtmlAuditoriaTercero(parsed)}</div>`;

  if (parsed.estado_anterior !== undefined || parsed.estado_nuevo !== undefined) {
    const anterior = flattenObjectAuditoriaTercero(parsed.estado_anterior || {});
    const nuevo = flattenObjectAuditoriaTercero(parsed.estado_nuevo || {});
    const keys = [...new Set([...Object.keys(anterior), ...Object.keys(nuevo)])].sort();

    let rows = '';
    keys.forEach((k) => {
      const oldVal = anterior[k] !== undefined ? anterior[k] : '';
      const newVal = nuevo[k] !== undefined ? nuevo[k] : '';
      const changed = normalizarComparacionAuditoriaTercero(oldVal) !== normalizarComparacionAuditoriaTercero(newVal);
      rows += `
        <tr class="${changed ? 'table-warning' : ''}">
          <td>${obtenerAliasAuditoria(k)}</td>
          <td>${renderizarValorAuditoriaTercero(oldVal)}</td>
          <td>${renderizarValorAuditoriaTercero(newVal)}</td>
        </tr>
      `;
    });

    return `
      <div class="mb-3">
        <h6 class="mb-2 text-primary">Comparativo de cambios</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped align-middle">
            <thead class="table-light"><tr><th>Campo</th><th>Estado anterior</th><th>Estado nuevo</th></tr></thead>
            <tbody>${rows || '<tr><td colspan="3" class="text-center">Sin cambios detectados</td></tr>'}</tbody>
          </table>
        </div>
      </div>
      ${construirTablaCampoValorAuditoriaTercero('Estado anterior', parsed.estado_anterior || {})}
      ${construirTablaCampoValorAuditoriaTercero('Estado nuevo', parsed.estado_nuevo || {})}
    `;
  }

  return construirTablaCampoValorAuditoriaTercero('Detalle', parsed);
};

const renderTablaAuditoriaTercero = (data) => {
  let rows = '';
  data.forEach((item, index) => {
    rows += `
      <tr>
        <td>${item.fecha || ''}</td>
        <td>${item.usuario || ''}</td>
        <td><span class="badge bg-label-primary">${item.accion || ''}</span></td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-info btnVerDetalleAuditoriaTercero" data-index="${index}">
            <i class="fa-regular fa-eye"></i> Ver
          </button>
        </td>
      </tr>
    `;
  });
  $("#tbodyAuditoriaTercero").html(rows || '<tr><td colspan="4" class="text-center">No hay registros con los filtros seleccionados.</td></tr>');
};

const aplicarFiltrosAuditoriaTercero = () => {
  const accion = ($("#filtroAuditoriaTerceroAccion").val() || '').toUpperCase();
  const usuario = ($("#filtroAuditoriaTerceroUsuario").val() || '').toLowerCase().trim();
  const texto = ($("#filtroAuditoriaTerceroTexto").val() || '').toLowerCase().trim();
  const desde = $("#filtroAuditoriaTerceroDesde").val();
  const hasta = $("#filtroAuditoriaTerceroHasta").val();

  const filtrados = auditoriaTerceroData.filter((item) => {
    if (accion && String(item.accion || '').toUpperCase() !== accion) return false;
    if (usuario && !String(item.usuario || '').toLowerCase().includes(usuario)) return false;
    if (texto && !String(item.detalle || '').toLowerCase().includes(texto)) return false;

    const fechaItem = String(item.fecha || '').substring(0, 10);
    if (desde && fechaItem < desde) return false;
    if (hasta && fechaItem > hasta) return false;
    return true;
  });

  renderTablaAuditoriaTercero(filtrados);
};

const abrirAuditoriaTercero = async () => {
  const idTercero = parseInt($("#txtCodigo").val(), 10);
  if (isNaN(idTercero) || idTercero <= 0) {
    Swal.fire('Alerta', 'Debe cargar un tercero para ver su auditoria.', 'warning');
    return;
  }

  try {
    showLoadingWithTetrisLogo('Consultando auditoria', 'ISA Software');
    const resp = await enviarPeticion({
      link: link_,
      op: 's_auditoria_tercero',
      id_tercero: idTercero
    });

    $("#auditoriaTerceroLabel").text(`#${idTercero}`);

    if (resp.error) {
      $("#alertAuditoriaTercero").html(ShowAlert(resp.message || 'Error al consultar auditoria', 'danger'));
      $("#tbodyAuditoriaTercero").html('');
      $("#modalAuditoriaTercero").modal('show');
      dissminSwal();
      return;
    }

    if (!resp.data || resp.data.length === 0) {
      $("#alertAuditoriaTercero").html(ShowAlert('No hay registros de auditoria para este tercero.', 'warning'));
      $("#tbodyAuditoriaTercero").html('');
      $("#modalAuditoriaTercero").modal('show');
      dissminSwal();
      return;
    }

    $("#alertAuditoriaTercero").html('');
    auditoriaTerceroData = resp.data;
    $("#filtroAuditoriaTerceroAccion").val('');
    $("#filtroAuditoriaTerceroDesde").val('');
    $("#filtroAuditoriaTerceroHasta").val('');
    $("#filtroAuditoriaTerceroUsuario").val('');
    $("#filtroAuditoriaTerceroTexto").val('');
    aplicarFiltrosAuditoriaTercero();
    $("#modalAuditoriaTercero").modal('show');
    dissminSwal();
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'No se pudo consultar la auditoria del tercero.', 'error');
  }
};
/*

@getDirecciones
*/

const getDirecciones = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_terceros_direcciones',
      id_tercero: id
    };
    const resp = await enviarPeticion(dataSend);
    console.log('Direcciones obtenidas:', resp.data); // Agrega un log para depuración

    if (Array.isArray(resp.data) && resp.data.length > 0) {
      let id_reg = Date.now() + 1;

      resp.data.forEach((d) => {
        arrayDirecciones.push({
          id_reg: id_reg,
          pais: d.codigo_pais,
          dpto: d.codigo_dpto,
          mpio: d.codigo_mpio,
          dir: d.direccion,
          tel: d.telefono,
          cel: d.celular,
          mail: d.email,
          txt_pais: d.nombre_pais,
          txt_dpto: d.nombre_dpto,
          txt_mpio: d.nombre_mpio,
          id: d.id,
          eliminar: '',
          principal: d.principal
        });
        id_reg++;
      });
    }

    renderizarTablaDirecciones();
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

/*
@getContactos
*/
const getContactos = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_terceros_contactos',
      id_tercero: id
    };
    const resp = await enviarPeticion(dataSend);
    $('#tbContactos tbody').html('');
    if (resp.data.length > 0) {
      resp.data.forEach((d) => {
        // Crear una nueva fila
        let newRow = `<tr>
                        <td>${d.nombres}</td>
                        <td>${d.apellidos}</td>
                        <td>${d.telefono}</td>
                        <td>${d.celular}</td>
                        <td>${d.email}</td>
                        <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar"><i class="fa-regular fa-trash-can"></i></button></td>
                      </tr>`;

        // Agregar la nueva fila al DOM
        $('#tbContactos tbody').append(newRow);
        // Agregar a arrayContactos
        arrayContactos.push({
          nombres: d.nombres,
          apellidos: d.apellidos,
          tel: d.telefono,
          cel: d.celular,
          mail: d.email,
          id: d.id,
          eliminar: ''
        });
      });
    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

/*
@eliminarTerceros
*/
const eliminarContactos = (btn) => {
  const fila = $(btn).closest('tr');
  const nombres = fila.find('td:eq(0)').text().trim();
  const apellidos = fila.find('td:eq(1)').text().trim();
  const tel = fila.find('td:eq(2)').text().trim();
  const cel = fila.find('td:eq(3)').text().trim();
  const mail = fila.find('td:eq(4)').text().trim();

  // Buscar el índice del contacto en el array
  const index = arrayContactos.findIndex(item =>
    item.nombres === nombres
    && item.apellidos === apellidos
    && item.tel === tel
    && item.cel === cel
    && item.mail === mail
  );

  // Si se encuentra el contacto, proceder a eliminar
  if (index !== -1) {
    const item = arrayContactos[index];
    if (item.id === 0) {
      // Eliminar directamente si el id es 0
      arrayContactos.splice(index, 1);
      Swal.fire('Eliminado', 'El contacto ha sido eliminado.', 'success');
    } else {
      // Marcar como eliminado si el id no es 0
      item.eliminar = 'x';
      Swal.fire('Marcado', 'El contacto ha sido marcado para eliminación.', 'info');
    }
    // Eliminar la fila de la tabla
    fila.remove();
  } else {
    Swal.fire('Error', 'El contacto no se encontró.', 'error');
  }
};
/*
@eliminarDirecciones
*/

const eliminarDirecciones = (btn) => {
  const fila = $(btn).closest('tr');
  const idReg = Number(fila.data('id-reg'));
  const index = arrayDirecciones.findIndex((item) => item.id_reg === idReg);

  if (index === -1) {
    Swal.fire('Error', 'La dirección no se encontró.', 'error');
    return;
  }

  const item = arrayDirecciones[index];
  const idTercero = parseInt($('#txtCodigo').val(), 10);
  const tieneTerceroPersistido = !isNaN(idTercero) && idTercero > 0;

  if (tieneTerceroPersistido && Number(item.id) > 0) {
    Swal.fire('Info', 'No se puede eliminar una dirección ya registrada porque puede afectar documentos y la integridad del sistema.', 'info');
    return;
  }

  if (tieneTerceroPersistido && arrayDirecciones.length <= 1) {
    Swal.fire('Oops', 'El tercero no puede quedar sin por lo menos una dirección', 'error');
    return;
  }

  if (item.id === 0) {
    arrayDirecciones.splice(index, 1);
    renderizarTablaDirecciones();
    Swal.fire('Eliminado', 'La dirección ha sido eliminada.', 'success');
  } else {
    item.eliminar = 'x';
    renderizarTablaDirecciones();
    Swal.fire('Marcado', 'La dirección ha sido marcada para eliminación.', 'info');
  }
};

/*
@listarTerceros
*/
const listarTerceros = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: link_,
      op: 'listar_terceros',
      filtro: $("#filtro").val()
    }
    const resp = await enviarPeticion(data);
    console.log(resp)
    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="tb_terceros" class="table table-sm table-borderless table-hover table-striped" width="100%">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable(
      'tb_terceros',
      resp.data,
      ['id', 'tipo_tercero', 'tratamiento', 'razon_comercial', 'nit', 'email_fe'],
      ['Id', 'Tipo Tercero', 'Tratamiento', 'Razón', 'Nit', 'Email FE'],
      editCallback,
      deleteCallback,
      false,
      '',
      '',
      true)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}
/*
@eliminarTerceros
*/

const deleteCallback = (data) => {
  try {
    showConfirmation(titulo = `¿Estás seguro de eliminar este tercero?`, texto = `¡No podrás revertir esto!`, icono = `question`).then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion({
          link: link_,
          op: 'd_tercero',
          id: data.id
        });
        if (!resp.success) {
          Swal.fire("¡Error!", resp.message, "error");
          return;
        }
        await listarTerceros();
        Swal.fire("¡Correcto!", resp.message, "success");
        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}
/*
@editCallback
*/
const editCallback = (data) => {
  selectedTab('tabs', 'navs-creacion');
  llenarFormulario(data);
}

/*
@descargarExcel
*/
const descargarExcel = (url, nombre = `Archivo.xlsx`) => {
  var a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

/*
@cargarExcel
*/
const cargarExcel = (file) => {
  var reader = new FileReader();
  reader.onload = function (event) {
    var data = new Uint8Array(event.target.result);

    var workbook = XLSX.read(data, {
      type: 'array'
    });
    var sheetName = workbook.SheetNames[0];
    var worksheet = workbook.Sheets[sheetName];
    var jsonData = XLSX.utils.sheet_to_json(worksheet, {
      header: 1,
      defval: "" // Asigna un valor predeterminado a las celdas vacías
    });

    $('#tableContainer').empty();
    var table = $(`<table class="table table-sm table-hover table-striped" id="ref-importadas_">
                       <thead class="table-primary">
                           <tr class="text-primary">
                                <th>Tipo Tercero</th>
                                <th>Primer Nombre</th>
                                <th>Segundo Nombre</th>
                                <th>Primer Apellido</th>
                                <th>Segundo Apellido</th>
                                <th>Razon Comercial</th>
                                <th>Tipo Identificación</th>
                                <th>NIT</th>
                                <th>Digito Verificación</th>
                                <th>Perfil Tributario</th>
                                <th>Regimen</th>
                                <th>Email FE</th>
                                <th>Pais</th>
                                <th>Departamento</th>
                                <th>Ciudad</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>Celular</th>
                                <th>Email</th>
                                <th>Cupo</th>
                            </tr>
                        </thead>
                    </table>`);

    var tbody = $('<tbody></tbody>');
    jsonData.slice(1).forEach(function (row) {
      while (row.length < 20) {
        row.push("");
      }
      arrayTerceros.push({
        tipoTercero: row[0] || "",
        primerNombre: row[1] || "",
        segundoNombre: row[2] || "",
        primerApellido: row[3] || "",
        segundoApellido: row[4] || "",
        razonComercial: row[5] || "",
        tipoIdentificacion: row[6] || "",
        NIT: row[7] || "",
        digitoVerificacion: row[8] || "",
        perfilTributario: row[9] || "",
        regimen: row[10] || "",
        emailFe: row[11] || "",
        pais: row[12] || "",
        departamento: row[13] || "",
        ciudad: row[14] || "",
        direccion: row[15] || "",
        telefono: row[16] || "",
        celular: row[17] || "",
        email: row[18] || "",
        cupo: row[19] || 0
      });

      var dataRow = $('<tr></tr>');
      row.forEach(function (cell) {
        dataRow.append('<td>' + (cell ?? 0) + '</td>');
      });
      tbody.append(dataRow);
    });
    table.append(tbody);
    $('#tableContainer').append(table);
  };
  reader.readAsArrayBuffer(file);
};

const limpiarImport = () => {
  $('#tableContainer').empty();
  arrayTerceros = [];
  $('#txtFileUp').val('');
};
const cargarImportados = async () => {
  showLoadingWithTetrisLogo('Cargando datos, por favor espere...', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      op: 'cargar_importados',
      datos: JSON.stringify(arrayTerceros)
    });

    console.log(resp);
    const nuevosCount = resp.datos.nuevos.length;
    const existentesCount = resp.datos.existentes.length;
    const erroresCount = resp.datos.errores.length;
    const tablaHTML = ` <table class="table">
                            <thead>
                                <tr>
                                    <th>Tipo de Registro</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Registros Nuevos</strong></td>
                                    <td>${nuevosCount}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registros Existentes</strong></td>
                                    <td>${existentesCount}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registros con Error</strong></td>
                                    <td>${erroresCount}</td>
                                </tr>
                            </tbody>
                        </table> `;

    Swal.fire({
      title: resp.message,
      html: tablaHTML,
      icon: 'info',
      confirmButtonText: 'Aceptar'
    });
    limpiarImport();
  } catch (e) {
    console.log(e);
    Swal.fire('Error interno', 'Se ha presentado un error, valide e intente nuevamente', 'error')
  }


};

$(async function () {
  await tiposTerceros();
  condicionesPago('C', 'id_condicion')
  condicionesPago('P', 'id_condicion_pago_prov')
  await regimen();
  await llenarSelectListas();
  await perfilTributario();
  await zonasVentas();
  await vendedores();
  await tipoIdentificacion();
  await paises('slcPais');
  let pais = $("#slcPais").val();
  await departamentos(pais, 'slcDepartamento');
  let dpto = $("#slcDepartamento").val();
  await municipios(pais, dpto, 'slcCiudad');
  //hide loading
  dissminSwal();

  $("#slcDepartamento").change(function () {
    municipios($("#slcPais").val(), $("#slcDepartamento").val(), 'slcCiudad');
  });

  $('#btnAddDireccion').on('click', () => {
    addDirecciones();
  });
  $('#btnAddContacto').on('click', () => {
    addContactos();
  });
  $('#tbContactos').on('click', '.btnEliminar', function () {
    eliminarContactos(this);
  });
  $('#tbDirecciones').on('click', '.btnEditar', function () {
    const fila = $(this).closest('tr');
    const idReg = Number(fila.data('id-reg'));
    cargarDireccionEnFormulario(idReg);
  });
  $("#btnLimpiar").click(() => {
    limpiar();
  });
  $("#btnAuditoria").click(() => {
    abrirAuditoriaTercero();
  });
  $("#filtroAuditoriaTerceroAccion, #filtroAuditoriaTerceroDesde, #filtroAuditoriaTerceroHasta").on('change', () => {
    aplicarFiltrosAuditoriaTercero();
  });
  $("#filtroAuditoriaTerceroUsuario, #filtroAuditoriaTerceroTexto").on('input', () => {
    aplicarFiltrosAuditoriaTercero();
  });
  $("#btnLimpiarFiltrosAuditoriaTercero").on('click', () => {
    $("#filtroAuditoriaTerceroAccion").val('');
    $("#filtroAuditoriaTerceroDesde").val('');
    $("#filtroAuditoriaTerceroHasta").val('');
    $("#filtroAuditoriaTerceroUsuario").val('');
    $("#filtroAuditoriaTerceroTexto").val('');
    aplicarFiltrosAuditoriaTercero();
  });
  $(document).on('click', '.btnVerDetalleAuditoriaTercero', function () {
    const index = parseInt($(this).data('index'), 10);
    const row = auditoriaTerceroData[index];
    if (!row) return;
    const html = construirDetalleAuditoriaTercero(row.detalle);
    $("#detalleAuditoriaTerceroContainer").html(html);
    $("#modalDetalleAuditoriaTercero").modal('show');
  });
  $('#tbDirecciones').on('click', '.btnEliminar', function () {
    eliminarDirecciones(this);
  });

  $("#nit").focusout(function () {
    const valor = $(this).val();
    if (valor.trim() !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nit_digito").val(dv);
    }
  });
  new Cleave('#cupo_credito', {
    numeral: true,
    numeralThousandsGroupStyle: 'thousand',
    numeralDecimalMark: '.',
    numeralDecimalScale: 2,
  });
  $("#txtContactoEmail, #txtEmail, #email_fe").focusout(function () {
    const valor = $(this).val();
    if (valor != '') {
      if (!validarEmail(valor)) {
        $(this).val('');
        $(this).focus();
        return;
      }
    }

  });
  $('#primer_nombre, #segundo_nombre, #primer_apellido, #segundo_apellido').on('input', function () {
    actualizarTratamiento();
  });
  $('#txtBusquedaTercero').autocomplete({
    source: async function (request, response) {
      const data = {
        link: link_,
        filtro: $.trim(request.term),
        estado: '',
        op: 's_tercero'
      };
      try {
        const resp = await enviarPeticion(data);
        let datos = resp.data;
        if (Array.isArray(datos)) {
          response(datos);
        } else {
          response([]);
        }
      } catch (error) {
        console.error("Error en la solicitud:", error);
        response([]);
      }
    },
    minLength: 3,
    select: function (event, ui) {
      item = ui.item;
      llenarFormulario(item);
    }
  });
  $("#buscar").click(function () {
    listarTerceros();
  });
  $("#filtro").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    listarTerceros();
  });
  /*Captura de formulario*/
  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      // Convertir todos los textos a mayúsculas
      convertirAMayusculas(form);

      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      let data = formatearArrayRequest($(this).serializeArray());
      if (arrayDirecciones.length === 0) {
        Swal.fire('Oops!', `Debe ingresar por lo menos una dirección`, 'error');
        return;
      }
      guardar(data);
    }, false);
  });

  // Prevenir autocomplete en campos de dirección y contacto
  const autocompletarCampos = [
    '#txtDireccion', '#txtTelefono', '#txtCelular', '#txtEmail',
    '#txtContactoNombres', '#txtContactoApellidos', '#txtContactoTelefono', '#txtContactoCelular', '#txtContactoEmail'
  ];

  autocompletarCampos.forEach(selector => {
    $(selector).on('change', function () {
      // Remover autofill
      $(this).css('box-shadow', 'inset 0 0 0 1000px white');
      $(this).css('-webkit-text-fill-color', '#333');
    });

    $(selector).on('autofill', function () {
      // Cambiar los estilos cuando autocomplete intente llenar
      $(this).css('box-shadow', 'inset 0 0 0 1000px white');
      $(this).css('-webkit-text-fill-color', '#333');
    });

    // Monitorear cambios no deseados
    $(selector).on('keyup change blur', function () {
      // Si el campo tiene atributos de protección, mantenerlos
      if (!$(this).attr('data-lpignore')) {
        $(this).attr('data-lpignore', '1');
      }
      if (!$(this).attr('data-1p-ignore')) {
        $(this).attr('data-1p-ignore', '1');
      }
    });
  });


  $('#btn-plantilla').click(function () {
    descargarExcel('https://thecyctechnology.com/cyc/plantillas-demo/plantilla-terceros.xlsx', 'plantilla-terceros.xlsx');
  });
  $('#txtFileUp').on('change', function (event) {
    var file = event.target.files[0];
    if (file) {
      cargarExcel(file);
    }
  });

  $('#btnClean').click(function () {
    limpiarImport();
  });
  $('#btnUp').click(function () {
    if (arrayTerceros.length > 0) {
      cargarImportados();
    } else {
      Swal.fire('No hay datos que importar', 'Por favor carque un archivo válido.', 'warning');
    }
  });

  $("#btnAutocompletar").click(function () {
    autocompletarTerceros()
  });
  // =========================
  // EVENTO BOTÓN VIDEO YOUTUBE
  // =========================

  $("#btn-video-tutorial").click(function () {
    const videoURL = "https://youtu.be/qztY2ZH5BV8";
    const videoID = videoURL.split('/').pop().split('?')[0];
    const embedURL = `https://www.youtube.com/embed/${videoID}`;

    $("#videoFrameTutorial").attr("src", embedURL);

    const modal = new bootstrap.Modal(document.getElementById('modalVideoTutorial'));
    modal.show();
  });

  // Detener video al cerrar el modal
  $("#modalVideoTutorial").on('hidden.bs.modal', function () {
    $("#videoFrameTutorial").attr("src", "");
  });

});
