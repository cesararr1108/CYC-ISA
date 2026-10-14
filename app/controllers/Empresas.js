let arrImages = [];
let idEmpresa = 0;
Dropzone.autoDiscover = false;
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
      option += `<option value="${d.id}">${d.tipo_identificacion.toUpperCase()} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_identificacion").html(option);
  } catch (e) {
    console.log(e);
  }
}
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
 @ Cargar empresa
*/
const cargarEmpresa = async () => {
  try {
    const data = {
      link: '../models/Empresas.php',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);
    ShowDataFrom(resp[0], 'formEmpresas');
    $("#id").val(resp[0].id);
    idEmpresa = resp[0].id;
    getLogo(resp[0].logo);
    let pais = $("#codigo_pais").val();
    let dpto = $("#codigo_dpto").val();
    await municipios(pais, dpto, 'codigo_mpio');
    $("#codigo_mpio").val(resp[0].codigo_mpio);

    if (resp[0].autorretenedor == 'S') {
      $("#autorretenedor").prop('checked', true);
    } else {
      $("#autorretenedor").prop('checked', false);
    }
    if (resp[0].agente_retenedor == 'S') {
      $("#agente_retenedor").prop('checked', true);
    } else {
      $("#agente_retenedor").prop('checked', false);
    }
    if (resp[0].gran_contribuyente == 'S') {
      $("#gran_contribuyente").prop('checked', true);
    } else {
      $("#gran_contribuyente").prop('checked', false);
    }
  } catch (e) {
    console.log(e);
  }
}
/*
 @ Cargar logo de empresa
*/
const getLogo = async (logo) => {

  try {
    const dataSend = {
      link: '../models/Empresas.php',
      op: 's_referencia_logo',
      logo: logo
    };

    const resp = await enviarPeticion(dataSend);
    const myDropzone = Dropzone.forElement(".dropzone");

    if (!myDropzone) {
      return;
    }
    cargandoLogo = true;
    myDropzone.removeAllFiles(true);
    arrImages = [];
    if (
      !Array.isArray(resp.data) ||
      resp.data.length === 0
    ) {
      cargandoLogo = false;
      return;
    }
    for (const item of resp.data) {
      if (
        !item.imagen ||
        typeof item.imagen !== "string"
      ) {
        continue;
      }

      const imageUrl = item.path;

      try {
        const response = await fetch(imageUrl);
        if (!response.ok) {
          throw new Error(
            `HTTP ${response.status} - ${imageUrl}`
          );

        }

        const fileBlob = await response.blob();

        const file = new File(
          [fileBlob],
          item.imagen,  {
            type: fileBlob.type
          }
        );

        file.logoName = item.imagen;
        file.isExisting = true;
        file.serverPath = imageUrl;
        file.accepted = true;
        file.status = Dropzone.SUCCESS;

        myDropzone.emit("addedfile", file);
        myDropzone.emit("thumbnail", file, imageUrl);
        myDropzone.emit("complete", file);
        myDropzone.files.push(file);
        arrImages.push(file);

      } catch (error) {
        console.error(
          "Error descargando el logo:",
          error
        );
      }
    }
    cargandoLogo = false;
  } catch (error) {
    cargandoLogo = false;
    console.error(
      "Error en getLogo:",
      error
    );

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
/*
 @ Guardar 
*/
const guardar = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software');
  try {
    const id = $("#id").val().trim();
    data.op = id == 0 || id == '' ? 'g_empresa' : 'u_empresa';
    data.link = '../models/Empresas.php';
    data.archivos = arrImages;

    data.id_frecuencia_pago = data.id_frecuencia_pago || 0;
    data.id_arl = data.id_arl || 0;
    data.id_metodo_pago = data.id_metodo_pago || 0;

    if ($("#autorretenedor").is(':checked')) {
      data.autorretenedor = 'S';
    } else {
      data.autorretenedor = 'N';
    }
    if ($("#agente_retenedor").is(':checked')) {
      data.agente_retenedor = 'S';
    } else {
      data.agente_retenedor = 'N';
    }
    if ($("#gran_contribuyente").is(':checked')) {
      data.gran_contribuyente = 'S';
    } else {
      data.gran_contribuyente = 'N';
    }
    const resp = await enviarFormData(data);
    if (!resp.error) {
      initToast('bg-success', 'Correcto!', resp.message, 'success');
    } else {
      initToast('bg-danger', 'Error!', resp.message, 'danger');
    }
    dissminSwal();

  } catch (e) {
    console.log(e);
    dissminSwal();
  }
}

const listarPerfiles = async () => {
  try {
    const data = {
      link: '../models/perfilesTributarios.php',
      op: 'listar'
    };

    const resp = await enviarPeticion(data);

    if (!resp.error) {

      let html = '';

      resp.data.forEach(perfil => {
        html += `
          <li class="list-group-item list-group-item-action"
              data-id="${perfil.id}"
              data-tipo="${perfil.tipo}"
              data-descripcion="${perfil.descripcion}"
              data-editable="${perfil.editable}">
              ${perfil.tipo} - ${perfil.descripcion}
          </li>
        `;
      });

      $("#listaPerfiles").html(html);
    }

  } catch (e) {
    console.log(e);
  }

  $("#crearPerfilModal").modal('show');
};

const limpiarPerfil = () => {
  $("#codigoPerfil").val("").data("id", "");
  $("#descripcionPerfil").val("");
  $("#listaPerfiles li").removeClass("active");
}

const cargarSw = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios.php',
      op: 'cargar_sw'
    });

    if (!resp.data || resp.data.length === 0) return;

    // SW permitidos
    const permitidos = [1, 5, 7, 4, 11, 12, 13, 14];

    let opciones = '';

    resp.data
      .filter(element => permitidos.includes(Number(element.sw)))
      .forEach(element => {
        opciones += `
          <option value="${element.sw}">
            ${element.sw} - ${element.descripcion}
          </option>
        `;
      });

    $("#pSw").html(opciones);

  } catch (e) {
    console.error(e);
  }
};


const cargarAlmacenes = async () => {
  const resp = await enviarPeticion({
    link: '../models/almacenes.php',
    op: 'listar',
    filtro: '',
  });
  if (resp.data.lenght == 0) {
    return;
  }
  p = '<option value="0">TODAS LAS BODEGAS / ALMACENES</option>'
  resp.data.forEach(almacen => {
    p += `<option value="${almacen.id}">${almacen.almacen}-${almacen.descripcion}</option>`
  })
  $("#pBodegas").html(p);
}

const listarPerfilTributario = async () => {
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
    $("#pPerfil").html(option);
  } catch (e) {
    console.log(e);
  }
}

const listarPerfildefinicion = async () => {

  let sw = $("#pSw").val();
  let bodega = $("#pBodegas").val();
  let id_definicion = $("#pPerfil").val();

  try {
    const data = {
      link: '../models/perfilesTributarios.php',
      filtro: '',
      op: 'listarPerfildefinicion',
      sw,
      bodega,
      id_definicion
    };

    const resp = await enviarPeticion(data);

    if (!resp.error && resp.data.length > 0) {
      // Solo hay un registro por combinación
      const perfil = resp.data[0];

      $("#pRetFte").val(perfil.ret_fte || '');
      $("#pRetIva").val(perfil.ret_iva || '');
      $("#pRetIca").val(perfil.ret_ica || '');
      $("#pRetCree").val(perfil.ret_cree || '');

    }

  } catch (e) {
    console.error(e);
  }

};
const editarPerfilDefinicion = async () => {

  let sw = $("#pSw").val();
  let bodega = $("#pBodegas").val();
  let id_definicion = $("#pPerfil").val();
  let ret_fte = $("#pRetFte").val();
  let ret_iva = $("#pRetIva").val();
  let ret_ica = $("#pRetIca").val();
  let ret_cree = $("#pRetCree").val();

  // Validación mínima
  if (!sw || !bodega || !id_definicion) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos incompletos',
      text: 'Debe seleccionar SW, Bodega y Perfil Tributario'
    });
    return;
  }

  // Confirmación
  const confirmacion = await Swal.fire({
    title: '¿Desea guardar los cambios en este perfil?',
    text: 'Se actualizarán o crearán las retenciones correspondientes',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33'
  });

  if (!confirmacion.isConfirmed) return;

  try {
    const data = {
      link: '../models/perfilesTributarios.php',
      op: 'editarPerfilDefinicion',
      sw,
      bodega,
      id_definicion,
      ret_fte,
      ret_iva,
      ret_ica,
      ret_cree
    };

    const resp = await enviarPeticion(data);
    // Mostrar resultado
    if (!resp.error) {
      await Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: resp.message,
        timer: 1500,
        showConfirmButton: false
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: resp.message
      });
    }

  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Error inesperado',
      text: 'Ocurrió un problema al guardar el perfil'
    });
    console.error(e);
  }

};

const modificarPerfiles = async () => {
  await cargarAlmacenes();
  await cargarSw();
  await listarPerfilTributario();
  await listarPerfildefinicion();
  $("#perfilModal").modal('show');
}

const listarArl = async () => {
  try {
    const data = {
      link: '../models/nominaEntidades.php',
      filtro: 'ARL',
      op: 'entidades_listar',
    };
    var resp = await enviarPeticion(data);

    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.razon_comercial.toUpperCase()}</option>`;
    });
    $("#id_arl").html(option);
  } catch (e) {
    console.log(e);
  }
}

$(async function () {

  showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
  await regimen();
  await listarArl();
  await perfilTributario();
  await tipoIdentificacion();
  await paises('codigo_pais');
  let pais = $("#codigo_pais").val();
  await departamentos(pais, 'codigo_dpto');
  let dpto = $("#codigo_dpto").val();
  await municipios(pais, dpto, 'codigo_mpio');

  // Configuración de Dropzone para un elemento específico
  let arrImages = [];
  let cargandoLogo = false;

  new Dropzone('.dropzone', {
    url: '../models/Empresas.php',
    maxFiles: 1,
    maxFilesize: 2,
    acceptedFiles: "image/jpeg,image/png,image/gif",
    addRemoveLinks: true,
    dictDefaultMessage: "Arrastra el logo aquí para subirlo",
    dictRemoveFile: "Eliminar logo",
    dictCancelUpload: "Cancelar carga",
    dictFileTooBig: "El archivo es demasiado grande. Máximo 2 MB.",
    dictInvalidFileType: "Archivo inválido, solo se aceptan imágenes.",
    dictMaxFilesExceeded: "Solo puedes subir 1 logo.",
    init: function () {
      this.on("addedfile", function (file) {
        if (!arrImages.includes(file)) {
          arrImages.push(file);
        }
      });

      this.on("sending", function (file, xhr, formData) {
        formData.append("op", "upLogo");
        formData.append("id", idEmpresa);
      });
      this.on("success", function (file, response) {
        try {
          const data = typeof response === "string" ? JSON.parse(response) : response;
          if (!data.error && data.file) {
            file.logoName = data.file;
            file.isExisting = true;
            console.log(
              "Logo guardado:",
              file.logoName
            );
          }
        } catch (error) {
          console.error(
            "Error procesando respuesta:",
            error
          );
        }
      });
      this.on("removedfile", function (file) {
        if (cargandoLogo) {
          return;
        }
        if (!file.logoName) {
          return;
        }
        const index = arrImages.indexOf(file);

        if (index > -1) {
          arrImages.splice(index, 1);
        }

        $.ajax({
          url: '../models/Empresas.php',
          type: 'POST',
          data: {
            op: 'deleteLogo',
            id: idEmpresa,
            logo: file.logoName
          },
          success: function (response) {
            try {
              const data = typeof response === "string" ? JSON.parse(response) : response;
              if (data.error) {
                initToast('bg-danger', "Error eliminando logo:", data.message, 'danger');
              } else {
                initToast('bg-success', "Logo eliminado correctamente.", data.message, 'success');
              }
            } catch (error) {
              initToast('bg-warning', "Respuesta inválida:", response, 'warning');
            }
          },
          error: function (xhr) {
            console.error(
              "Error AJAX eliminando logo:",
              xhr.responseText
            );
          }
        });
      });
      this.on("error", function (file, errorMessage) {
        console.error(
          "Error subiendo archivo:",
          errorMessage
        );
      });
    }

  });



  await cargarEmpresa();
  dissminSwal();
  $("#codigo_dpto").change(async function () {
    let pais = $("#codigo_pais").val();
    let dpto = $(this).val();
    await municipios(pais, dpto, 'codigo_mpio');
  });

  /*Captura de formulario*/
  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      // Convertir todos los textos a mayúsculas
      //convertirAMayusculas(form);
      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }
      let data = formatearArrayRequest($(this).serializeArray());
      guardar(data);
    }, false);
  });

  //Calcular digito de verificacion
  $("#nit").focusout(function () {
    const valor = $(this).val();
    if (valor.trim() !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nit_digito").val(dv);
    }
  });


  $("#btnPerfilModal").click(async function () {
    await modificarPerfiles();
  });

  $("#btnCrearPerfiles").click(async function () {
    await listarPerfiles();
  });

  $(document).on("click", "#listaPerfiles li", function () {
    $("#listaPerfiles li").removeClass("active");
    $(this).addClass("active");
    $("#codigoPerfil").val($(this).data("tipo"));
    $("#descripcionPerfil").val($(this).data("descripcion"));
    $("#codigoPerfil").data("id", $(this).data("id"));
    $("#codigoPerfil").data("editable", $(this).data("editable"));
  });
  $("#btnNuevo").click(function () {
    limpiarPerfil();
  });
  $("#btnModificar").click(async function () {

    const id = $("#codigoPerfil").data("id") || "";
    const tipo = $("#codigoPerfil").val().trim();
    const descripcion = $("#descripcionPerfil").val().trim();

    if (!tipo || !descripcion) {
      Swal.fire({
        icon: 'warning',
        title: 'Campos incompletos',
        text: 'Debe completar los campos'
      });
      return;
    }

    const esEdicion = id !== "";
    const accionTexto = esEdicion ? "modificar" : "crear";
    const accionTitulo = esEdicion ? "Modificar perfil" : "Crear perfil";

    const confirmacion = await Swal.fire({
      title: `¿Desea ${accionTexto} este perfil?`,
      text: `Se procederá a ${accionTexto} el perfil tributario.`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: `Sí, ${accionTexto}`,
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33'
    });

    if (!confirmacion.isConfirmed) return;

    const data = {
      link: '../models/perfilesTributarios.php',
      op: esEdicion ? 'editarPerfil' : 'crearPerfil',
      id: id,
      tipo: tipo,
      descripcion: descripcion
    };

    const resp = await enviarPeticion(data);

    if (!resp.error) {

      await Swal.fire({
        icon: 'success',
        title: 'Proceso exitoso',
        text: resp.message,
        timer: 1500,
        showConfirmButton: false
      });

      limpiarPerfil();
      listarPerfiles();

    } else {

      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: resp.message
      });

    }

  });
  $("#btnBorrar").click(async function () {

    const id = $("#codigoPerfil").data("id");

    if (!id) {
      Swal.fire({
        icon: 'warning',
        title: 'Seleccione un perfil',
        text: 'Debe seleccionar un perfil para eliminar'
      });
      return;
    }

    const confirmacion = await Swal.fire({
      title: '¿Desea eliminar este perfil?',
      text: 'Esta acción marcará el perfil como eliminado.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d'
    });

    if (!confirmacion.isConfirmed) return;

    const data = {
      link: '../models/perfilesTributarios.php',
      op: 'eliminarPerfil',
      id: id
    };

    const resp = await enviarPeticion(data);

    if (!resp.error) {

      await Swal.fire({
        icon: 'success',
        title: 'Eliminado',
        text: resp.message,
        timer: 1500,
        showConfirmButton: false
      });

      limpiarPerfil();
      listarPerfiles();

    } else {

      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: resp.message
      });

    }

  });


  $("#btnGuardarPerfilDefinicion").click(async function () {
    await editarPerfilDefinicion();
  });
});
