const link_ = 'api/modulospermisos'

const cargarPermisos = async (id_modulo) => {

  try {

    resp = await enviarPeticion({
      link: '../models/ModulosPermisos.php',
      op: 'listarPermisosModulos',
      id_modulo: id_modulo[0]
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    p2 = ''
    resp.data.forEach(permiso => {

      if (permiso.asociado > 0) {
        cl = ' btn-primary text-start  mt-1';
        p2 += `<option class="${cl}" value="${permiso.id}">${permiso.permiso}</option>`;
      } else {
        cl = ' btn-ligth text-start  mt-1';
        p += `<option class="${cl}" value="${permiso.id}">${permiso.permiso}</option>`;
      }

    })

    $("#slcPermisos").html(p);
    $("#slcAsignados").html(p2);

  } catch (e) {
    console.error(e)
  }

}


const cargarModulos = async () => {
  try {
    resp = await enviarPeticion({
      link: '../models/modulos.php',
      op: 'listar',
      filtro: ''
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    resp.data.forEach(modulo => {
      p += `<option value="${modulo.id}"><b>${modulo.nombre_grupo}</b> - ${modulo.titulo}</option>`
    })
    $("#slcModulos").html(p);
  } catch (e) {
    console.error(e)
  }

}

const crearModulosPermisos = async (data) => {

  try {
    resp = await enviarPeticion(data);

    if (resp.data.error) {
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    Swal.fire("Ok", resp.message, "success");
    limpiarFormulario('FormCrear');
    $("#slcPermisos").html("");
    $("#slcAsignados").html("");
    cargarPermisos($("#slcModulos").val());
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }

}


function editCallback(data) {
  limpiarFormulario('FormCrear')
  $("#slcPermisos").html("");
  $("#slcAsignados").html("");
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/ModulosPermisos.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }

        listarModulosPermisos();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}

const listarModulosPermisos = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/ModulosPermisos.php',
      op: 'listar',
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
    createDataTable('modulos-permisos', resp.data, ['titulo', 'permiso'], ['Modulo', 'Permiso'], false, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'danger'))
    dissminSwal();
  }
}

const listarVinculados = () => {

}

$(function () {

  cargarModulos();

  $("#slcModulos").change(function () {
    cargarPermisos($(this).val());
  })

  $('#txtModulo').on('keyup', function () {
    var searchText = $(this).val().toLowerCase();
    $('#slcModulos option').each(function () {
      var optionText = $(this).text().toLowerCase();

      if (optionText.includes(searchText)) {
        $(this).removeClass('d-none');
      } else {
        $(this).addClass('d-none');
      }
    });
  });
  $('#txtPermiso').on('keyup', function () {
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

        data.link = '../models/ModulosPermisos.php';

        data.op = 'agregar';

        console.log(data)

        crearModulosPermisos(data);
        return;
      }, false)
    });

  $("#buscar").click(function () {
    listarModulosPermisos();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
    $("#slcPermisos").html("");
    $("#slcAsignados").html("");
  })

  $("#slcModulos").change(function () {

  })
  dissminSwal();
})
