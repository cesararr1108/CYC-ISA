const link = '../models/usuarios.php';
const nuevo = (clearUser) => {
  usuario = ''
  if (!clearUser) {
    usuario = $("#usuario").val()
  }
  limpiarFormulario('FormCrear');
  $("#preview").attr(
    "src",
    "../../assets/img/avatars/2.png?" + Math.random()
  );
  cargarBodegas();
  $("#terero").val('');
  $("#clave").attr("disabled", false);
  //$("#bloqueo-clave").hide();
  $("#bloqueo-clave").addClass("d-none")
  $("#bloqueo-clave").html('<i class="fa-solid fa-lock text-primary"></i>')
  $("#usuario").val(usuario)
  if (clearUser) {
    $("#usuario").focus();
  }
}
const buscarUsuario = async (usuario) => {

  showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
  try {
    const resp = await enviarPeticion({
      link,
      usuario,
      op: 's_usuario'
    })

    if (resp.usuario.length == 0) {
      nuevo(false)
      dissminSwal()
      // Swal.fire("Ups","No se encontraron resultados","warning")
      return;
    }

    limpiarFormulario('FormCrear')
    selectedTab('tabs', 'navs-creacion');
    ShowDataFrom(resp.usuario[0], 'FormCrear');

    if ($("#id").val() != '' && $("#id").val() != '0') {
      $("#bloqueo-clave").removeClass("d-none")
      $("#clave").attr("disabled", true)
      const tercero = resp.usuario[0].primer_nombre + ' ' + resp.usuario[0].segundo_nombre + ' ' + resp.usuario[0].primer_apellido + ' ' + resp.usuario[0].segundo_apellido;
      $("#tercero").val(tercero)
      //bodegas
      const select = document.getElementById('bodegas');

      respuestaJson = JSON.parse(resp.usuario[0].bodegas)

      bodegas_id = respuestaJson.map(item => {
        return item.bodega
      })

      $("#bodegas").val(bodegas_id).trigger("change")
    } else {
      $("#bloqueo-clave").addClass("d-none")
      $("#clave").attr("disabled", false)
    }
    dissminSwal();
  } catch (error) {
    dissminSwal()
    console.error(error)
    Swal.fire("Ups", "Se genero un error al consultar el usuario", "error")
  }

}

const cargarRoles = async () => {
  const resp = await enviarPeticion({
    link: '../models/roles.php',
    op: 'listar',
    filtro: ''
  })

  p = '<option value>Seleccionar</option>'
  if (resp.data.length > 0) {

    resp.data.forEach(element => {
      p += `<option value="${element.id}">${element.descripcion}</option>`
    });
    $("#roles_id,#rol_filter").html(p)
  }
}

const buscarTercero = async (nit) => {

  try {
    $("#nit").attr('disabled', true)
    const resp = await enviarPeticion({
      link: '../models/Terceros.php',
      op: 'cargar_ter_nit',
      nit
    });

    $("#nit").attr('disabled', false)
    if (!resp.ok) {
      Swal.fire("Ups", resp.message, "error");
      return;
    }
    if (resp.data.length == 0) {
      Swal.fire("Ups", resp.message, "error");
      return;
    }
    const tercero = resp.data[0].primer_nombre + ' ' + resp.data[0].segundo_nombre + ' ' + resp.data[0].primer_apellido + ' ' + resp.data[0].segundo_apellido;
    $("#tercero").val(tercero)

  } catch (error) {
    console.error(error);
    Swal.fire('Ups', 'Ocurrio un error al consultar el tercero', 'error')
    $("#nit").attr('disabled', false)
  }
}

const guardar = async (data) => {
  try {
    const resp = await enviarPeticion(data);
    console.log({ resp })
    if (resp.error) {
      dissminSwal();
      Swal.fire("Oops", resp.message, "error")
      return;
    }

    const archivo = $('#imagen')[0].files[0];
    console.log({ archivo })

    if (archivo) {
      let formData = new FormData();
      formData.append('archivo', archivo);
      formData.append('id', resp.id); // variable POST
      console.log({ formData })
      resimg = await $.ajax({
        url: '../shared/servicios/upload.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (respuesta) {
          console.log({
            respuesta
          })
          if (!respuesta.ok) {
            Swal.fire("Ups", respuesta.message, "error");
            return;
          }
        }
      }).fail(function (e) {
        console.error(e)
      });
    }
    Swal.fire("Ok", "Se guardo correctamente el usuario", "success");
    nuevo();
  } catch (error) {
    console.error(error)
    dissminSwal();
    Swal.fire("Ups", "Ha ocurrido un error al intentar guardar el usuario", "error")
  }
}

const cargarBodegas = async () => {
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'bodegas'
  })
  if (resp.length > 0) {
    let p = ''
    resp.forEach(element => {
      p += `<option value="${element.almacen}" data-element='${JSON.stringify(element)}'>${element.almacen} - ${element.descripcion}</option>`
    });
    $("#bodegas").html(p).select2();
  }
}

function editCallback(data) {
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');

  if ($("#id").val() != '' && $("#id").val() != '0') {
    $("#bloqueo-clave").removeClass("d-none")
    $("#clave").attr("disabled", true)
    const tercero = data.primer_nombre + ' ' + data.segundo_nombre + ' ' + data.primer_apellido + ' ' + data.segundo_apellido;
    $("#tercero").val(tercero)
    //bodegas
    const select = document.getElementById('bodegas');
    respuestaJson = JSON.parse(data.bodegas)
    bodegas_id = respuestaJson.map(item => {
      return item.bodega
    })
    $("#bodegas").val(bodegas_id).trigger("change")

  } else {
    $("#bloqueo-clave").addClass("d-none")
    $("#clave").attr("disabled", false)
  }
}

const deleteCallback = (data) => {
  try {
    showConfirmation(`Estas seguro de eliminar este usuario?`, `Esta operación no se puede reversar`, `warning`).then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion({
          link: '../models/usuarios.php',
          op: 'd_usuario',
          id: data.id
        });
        if (resp.error) {
          Swal.fire("Error", resp.mensaje, "error");
          return;
        }
        await listarUsuarios();
        Swal.fire("Excelente!", resp.mensaje, "success");
        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}

const listarUsuarios = async () => {

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/usuarios.php',
      filtro: $("#filtro").val(),
      op: 's_usuario_all',
      rol: $("#rol_filter").val(),
      estado: $("#estado_filter").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="usuarios" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable(
      'usuarios',
      resp.data,
      ['id', 'imagen_td', 'usuario', 'nombres', 'apellidos', 'fecha_creacion', 'rol_descripcion'],
      ['Id', 'Foto', 'Usuario', 'Nombres', 'Apellidos', 'Fecha de creacion', 'Rol'],
      editCallback,
      deleteCallback,
      false,
      '',
      ''
    );

    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

$(async function () {
  'use strict'
  $("#usuario").focus();
  await cargarBodegas();
  await cargarRoles();
  dissminSwal();

  const input = document.getElementById('imagen');
  const preview = document.getElementById('preview');
  preview.setAttribute(
    "src",
    "../../assets/img/avatars/2.png?" + Math.random()
  );


  input.addEventListener('change', function () {
    const file = this.files[0];

    if (file) {
      const reader = new FileReader();

      reader.onload = function (e) {
        preview.setAttribute('src', e.target.result);
        preview.style.display = 'block';
      }

      reader.readAsDataURL(file);
    } else {
      preview.setAttribute('src', '#');
      preview.style.display = 'none';
    }
  });

  $("#btnNuevo").click(function () {
    nuevo(true);
  });

  $("#busqueda-usuarios").click(function () {
    listarUsuarios();
  });

  $("#bloqueo-clave").click(function () {

    const $icon = $(this).find("i");

    if ($icon.hasClass("fa-lock")) {

      $icon.removeClass("fa-lock").addClass("fa-save");

      $("#clave")
        .prop("disabled", false)
        .prop("type", "password")
        .focus();

      return;
    }

    // === Guardar ===

    if ($("#id").val() == '' || $("#id").val() == '0') {
      Swal.fire("Error", "No se ha cargado un usuario", "error");
      return;
    }

    if ($.trim($("#clave").val()).length < 4) {
      Swal.fire("Error", "La clave debe ser de al menos 4 caracteres", "error");
      return;
    }

    enviarPeticion({
      link,
      op: 'update-password',
      id: $("#id").val(),
      clave: $.trim($("#clave").val())
    })
      .then(resp => {

        $icon.removeClass("fa-save").addClass("fa-lock");

        $("#clave")
          .prop("disabled", true)
          .val("");

        initToast('bg-success', 'Excelente!', 'Contraseña actualizada correctamente.', 'success')

      })
      .catch(e => {
        console.error(e);
        initToast('bg-danger', 'Error!', 'Error al actualizar la contraseña', 'danger');
      });

  });


  $("#usuario").focusout(function () {
    const usuario = $.trim($(this).val());

    if (usuario.length > 0) {
      buscarUsuario(usuario)
    }
  })

  $("#nit").on('focusout', function (e) {
    const nit = $.trim($(this).val());

    if (nit.length == 0) {
      $('#tercero').val('')
      return;
    }
    buscarTercero(nit)

  })
  $("#nit").on('keyup', function (e) {
    const nit = $.trim($(this).val());
    if (nit.length == 0) {
      $('#tercero').val('')
      return;
    }
  });

  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', async function (event) {

        event.preventDefault();

        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
          form.classList.add('was-validated');
          return;
        }
        showLoadingWithTetrisLogo('Guardando usuario', 'ISA Software')
        let data = formatearArrayRequest($(this).serializeArray());

        data.link = link;

        const seleccionadas = $("#bodegas").val(); // ["B01", "B02"]
        let bodegas = [];
        console.log(seleccionadas);

        seleccionadas.forEach(item => {

          const data_bodega = JSON.parse($("#bodegas option[value='" + item + "']").attr("data-element"))

          bodegas.push({
            bodega: data_bodega.almacen,
            descripcion: data_bodega.descripcion,
            id: data_bodega.id
          });
        })

        data.bodegas = JSON.stringify(bodegas);

        if ($("#id").val() != '0' && $("#id").val() != '') {
          data.op = 'u_usuario';
        } else {
          data.op = 'i_usuario';
        }
        await guardar(data);

      }, false)
    });
})
