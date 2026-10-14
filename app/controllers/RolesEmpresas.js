const link_ = 'api/rolesempresas'

const cargarRoles = async () => {

  try {
    resp = await enviarPeticion({
      link: '../models/rolesEmpresas.php',
      op: 'listar',
      filtro: ''
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    resp.data.forEach(rol => {
      p += `<option value="${rol.id}" onclick="">${rol.rol}</option>`
    })

    $("#slcRoles").html(p);
  } catch (e) {
    console.error(e)
  }

}

const cargarEmpresas = async () => {

  try {
    resp = await enviarPeticion({
      link: '../models/Empresas.php',
      op: 'listar'
    });

    if (resp.data.lenght == 0) {
      return;
    }
    p = '';
    resp.data.forEach(empresa => {
      p += `<option value="${empresa.id}">${empresa.nit}-<b>${empresa.razon_social}</b></option>`
    })

    $("#slcEmpresas").html(p);
  } catch (e) {
    console.error(e)
  }

}

function editCallback(data) {
  console.log({ data })
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/RolesEmpresas.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarRolesEmpresas();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}

const listarRolesEmpresas = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: url_api + link_ + '/buscarcriterios',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="empresas-roles" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable('empresas-roles', resp.data, ['rol', 'razon_social', 'fecha_creacion', 'usuario'], ['Rol', 'Empresa', 'Fecha creación', 'Usuario creacion'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'danger'))
    dissminSwal();
  }
}

const crearRolesEmpresas = async (data) => {
  try {
    resp = await enviarPeticion(data);

    if (resp.error) {
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    Swal.fire("Ok", resp.message, "success");
    limpiarFormulario('FormCrear');
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }
}

$(function () {
  cargarRoles();
  cargarEmpresas();

  $('#FormCrear select[multiple]').on('change', function () {
    const $select = $(this);
    const selectedOptions = $select.find('option:selected');

    if (selectedOptions.length > 1) {
      // Deseleccionar todos los elementos excepto el primero seleccionado
      const firstSelectedValue = selectedOptions.first().val();
      $select.val([firstSelectedValue]);

      // Retornar falso para evitar la acción predeterminada
      return false;
    }
  });


  $('#txtEmpresas').on('keyup', function () {
    var searchText = $(this).val().toLowerCase();
    $('#slcEmpresas option').each(function () {
      var optionText = $(this).text().toLowerCase();
      console.log(optionText)
      if (optionText.includes(searchText)) {
        $(this).removeClass('d-none');
      } else {
        $(this).addClass('d-none');
      }
    });
  });

  $('#txtRoles').on('keyup', function () {
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

        data.link = url_api + link_ + '/editar';

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }

        crearRolesEmpresas(data);
        return;
      }, false)
    });

  $("#buscar").click(function () {
    listarRolesEmpresas();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  dissminSwal();

})