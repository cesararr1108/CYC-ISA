const cargarPermisos = async (modulo, rol) => {
  try {
    resp = await enviarPeticion({
      link: '../models/rolesPermisos.php',
      modulo: modulo[0],
      rol: rol[0],
      op: 'listarRolesPermisos'
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    p_selected = '';
    resp.data.forEach(permiso => {
      console.log(permiso);
      if (permiso.asociado > 0) {
        p_selected += `<option class="bg-primary w-100 text-white mt-2 p-1 border border-round text-start" onclick="deleteData(${permiso.asociado})" value="${permiso.asociado}">${permiso.permiso}</option>`
      } else {
        p += `<option class="alert-secondary p-1 w-100" value="${permiso.id_modulo_permiso}">${permiso.permiso}</option>`
      }

    })

    $("#slcPermisos").html(p);
    $("#slcPermisosSelected").html(p_selected);
  } catch (e) {
    console.error(e)
  }

}
const cargarRoles = async () => {
  try {
    resp = await enviarPeticion({
      link: '../models/roles.php',
      op: 'listar',
      filtro: ''
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    resp.data.forEach(rol => {
      p += `<option value="${rol.id}">${rol.descripcion}</option>`
    })

    $("#slcRoles").html(p);
  } catch (e) {
    console.error(e)
  }

}

const cargarModulos = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/ModulosPermisos.php',
      op: 'listar',
      filtro: ''
    });

    if (!resp.data || resp.data.length === 0) {
      return;
    }

    let vistos = new Set();
    let p = '';

    resp.data.forEach(modulo => {
      if (!vistos.has(modulo.id_modulo)) {
        p += `<option value="${modulo.id_modulo}"><b>${modulo.nombre_grupo}</b> - ${modulo.titulo}</option>`;
        vistos.add(modulo.id_modulo);
      }
    });

    $("#slcModulos").html(p);
  } catch (e) {
    console.error(e);
  }
};


const crearRolesPermisos = async (data) => {
  try {
    resp = await enviarPeticion(data);
    if (resp.error) {
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    Swal.fire("Ok", resp.message, "success");
    cargarPermisos($("#slcModulos").val(), $("#slcRoles").val())
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }

}

function editCallback(data) {
  limpiarFormularioGeneral('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteData(id) {
  console.log(id);

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        console.log({
          link: '../models/rolesPermisos.php',
          op: 'delete',
          id
        })
        resp = await enviarPeticion({
          link: '../models/rolesPermisos.php',
          op: 'delete',
          id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");
        cargarPermisos($("#slcModulos").val(), $("#slcRoles").val())
        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}

const deleteCallback = () => {

}
const listarRolesPermisos = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/rolesPermisos.php',
      op: '',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="modulos-permisos" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable('modulos-permisos', resp.data, ['titulo', 'permiso', 'fecha_creacion', 'usuario'], ['Modulo', 'Permiso', 'Fecha creación', 'Usuario creacion'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'danger'))
    dissminSwal();
  }
}

const limpiarFormularioGeneral = (id) => {
  $("#slcModulos").html('');
  $("#slcPermisos").html('');
  $("#slcPermisosSelected").html('');
  $('#' + id)[0].reset();
}

$(async function () {
  await cargarRoles();
  $("#slcRoles").change(function () {
    cargarModulos();
  })

  $("#slcModulos").change(function () {
    cargarPermisos($(this).val(), $("#slcRoles").val())
  })

  $('#slcPermisosSelected').on('click', 'option', function (e) {
    id = $("#")
  });

  $('#FormCrear select[multiple]').on('change', function () {
    const $select = $(this);
    const selectedOptions = $select.find('option:selected');
    if ($select.attr('id') !== 'slcPermisos') {
      if (selectedOptions.length > 1) {
        // Deseleccionar todos los elementos excepto el primero seleccionado
        const firstSelectedValue = selectedOptions.first().val();
        $select.val([firstSelectedValue]);

        // Retornar falso para evitar la acción predeterminada
        return false;
      }
    }

  });


  $('#txtModulos').on('keyup', function () {
    var searchText = $(this).val().toLowerCase();
    $('#slcModulos option').each(function () {
      var optionText = $(this).text().toLowerCase();
      console.log(optionText)
      if (optionText.includes(searchText)) {
        $(this).removeClass('d-none');
      } else {
        $(this).addClass('d-none');
      }
    });
  });

  $('#txtRol').on('keyup', function () {
    var searchText = $(this).val().toLowerCase();
    $('#slcRoles option').each(function () {
      var optionText = $(this).text().toLowerCase();
      console.log(optionText)
      if (optionText.includes(searchText)) {
        $(this).removeClass('d-none');
      } else {
        $(this).addClass('d-none');
      }
    });
  });

  $('#txtPermisos').on('keyup', function () {
    var searchText = $(this).val().toLowerCase();
    $('#slcPermisos option').each(function () {
      var optionText = $(this).text().toLowerCase();

      if (optionText.includes(searchText)) {
        $(this).removeClass('d-none');
      } else {
        $(this).addClass('d-none');
      }
    });
  });


  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {

        event.preventDefault();

        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
          form.classList.add('was-validated');
          return;
        }
        let data = formatearArrayRequest($(this).serializeArray());
        data.link = '../models/rolesPermisos.php';
        data.op = 'agregar';
        crearRolesPermisos(data);
        return;
      }, false)
    });

  $("#buscar").click(function () {
    listarRolesPermisos();
  });

  $("#btnNuevo").click(function () {
    limpiarFormularioGeneral('FormCrear');
  })
  dissminSwal();
})
