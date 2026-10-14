const link_ = '../models/Terceros.php';
let arrayDirecciones = [];

/*
 @ Validar si tercero existe y llenar datos
*/
const validarYLlenarTerceroExistente = async (identificationNumber) => {
  try {
    const validarResp = await enviarPeticion({
      link: link_,
      filtro: identificationNumber,
      estado: '',
      op: 's_tercero'
    });

    // Si el tercero ya existe, llenar los datos del formulario
    if (validarResp.data && Array.isArray(validarResp.data) && validarResp.data.length > 0) {
      const terceroExistente = validarResp.data[0];
      
      ShowDataFrom(terceroExistente, 'FormTerceroBasicos');
      if (terceroExistente.codigo_pais) {
        $("#slcPais").val(terceroExistente.codigo_pais);
        await departamentos(terceroExistente.codigo_pais, 'slcDepartamento');
        if (terceroExistente.codigo_dpto) {
          $("#slcDepartamento").val(terceroExistente.codigo_dpto);
          await municipios(terceroExistente.codigo_pais, terceroExistente.codigo_dpto, 'slcCiudad');
          if (terceroExistente.codigo_mpio) {
            $("#slcCiudad").val(terceroExistente.codigo_mpio);
          }
        }
      }
      return true; // Tercero encontrado
    }
    
    return false; // Tercero no encontrado
  } catch (e) {
    console.error('Error validando tercero existente:', e);
    return false;
  }
};

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

    // Validar si el tercero ya existe
    const terceroExiste = await validarYLlenarTerceroExistente(identificationNumber);
    
    if (terceroExiste) {
      dissminSwal();
      Swal.fire('Éxito', 'Datos del tercero cargados correctamente', 'success');
      return;
    }

    // Si no existe, continuar con la consulta al servicio DIAN
    showLoadingWithTetrisLogo('Consultando DIAN', 'ISA Software');
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
 @ Guardar cliente
*/
const guardar = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software')
  try {
    const id_tercero = $("#id_tercero").val().trim();
    const nit = $("#nit").val().trim();
    
    if (id_tercero == 0 || id_tercero == '') {
      // Crear nuevo: validar que el tercero no exista
      const terceroExiste = await validarYLlenarTerceroExistente(nit);
      
      if (terceroExiste) {
        dissminSwal();
        Swal.fire('Advertencia', 'Este tercero ya existe en el sistema. Por favor, edítelo en lugar de crear uno nuevo.', 'warning');
        return;
      }
      
      data.op = 'g_tercero';
    } else {
      data.op = 'u_tercero';
      data.id = id_tercero;
    }
    
    data.link = link_;
    data.direcciones = JSON.stringify(arrayDirecciones);
    data.contactos = JSON.stringify([]);

    const resp = await enviarPeticion(data);
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
 @ Limpiar
*/
const limpiar = (focus = true) => {
  limpiarFormulario('FormTerceroBasicos');
  arrayDirecciones = [];
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
 @ buscar tercero
*/
const cargarTercero = async (nit) => {
  try {
    const data = {
      link: '../models/Terceros.php',
      op: 's_tercero_basico',
      nit
    };

    const resp = await enviarPeticion(data);
    return resp;
  } catch (e) {
    console.error('Error en cargarTercero:', e);
    return [];
  }
};

/*
 @ Convertir formulario a mayusculas
*/
const convertirAMayusculas = (form) => {
  var inputs = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
  inputs.forEach(function (input) {
    input.value = input.value.toUpperCase();
  });
}
$(async function () {
  await tipoIdentificacion();
  await paises('slcPais');
  let pais = $("#slcPais").val();
  await departamentos(pais, 'slcDepartamento');
  let dpto = $("#slcDepartamento").val();
  await municipios(pais, dpto, 'slcCiudad');
  $("#slcDepartamento").change(function () {
    municipios(pais, $(this).val(), 'slcCiudad');
  });
  //hide loading
  dissminSwal();
  $("#nit").focusout(async function () {
    const valor = $(this).val().trim();
    if (valor !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nit_digito").val(dv);
      
      // Validar y llenar si el tercero existe
      await validarYLlenarTerceroExistente(valor);
    }
  });

  $('#primer_nombre, #segundo_nombre, #primer_apellido, #segundo_apellido').on('input', function () {
    actualizarTratamiento();
  });


  /*Captura de formulario*/
  var forms = document.querySelectorAll('.needs-validation');

  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      convertirAMayusculas(form);
      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }
      let data = formatearArrayRequest($(this).serializeArray());
      if (!data.razon_comercial || data.razon_comercial.trim() === '') {
        data.razon_comercial = data.tratamiento || '';
      }

      let tercero = {
        id_tipo_identificacion: data.id_tipo_identificacion,
        nit: data.nit,
        nit_digito: data.nit_digito,
        primer_apellido: data.primer_apellido,
        primer_nombre: data.primer_nombre,
        segundo_apellido: data.segundo_apellido,
        segundo_nombre: data.segundo_nombre,
        tratamiento: data.tratamiento,
        razon_comercial: data.razon_comercial,
        email_fe: data.email_fe
      };

      const id_reg = Date.now() + 1;
      arrayDirecciones.push({
        id_reg,
        pais: data.slcPais,
        dpto: data.slcDepartamento,
        mpio: data.slcCiudad,
        dir: data.direccion,
        tel: data.telefono,
        cel: data.celular,
        mail: data.email_fe,
        id: data.id_direccion,
        eliminar: '',
        principal: '1'
      });
      guardar(tercero);
    }, false);
  });

  $("#btnAutocompletar").click(function () {
    autocompletarTerceros()
  });

});
