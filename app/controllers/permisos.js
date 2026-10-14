const link_ = 'permisos'


const crearPermiso = async (data) => {

  try {
    resp = await enviarPeticion(data);

    if (resp.error) {
      Swal.fire("Error", resp.message, "error");
      return;
    }
    Swal.fire("Ok", resp.message, "success");
    limpiarFormulario('FormCrear');
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }

}


function editCallback(data) {
  console.log(data);
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/Permisos.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }

        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        await listarPermisos();

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}


const listarPermisos = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');

  try {
    const data = {
      link: '../models/Permisos.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="modulos" class="table table-sm table-borderless table-hover">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('modulos', resp, ['permiso', 'descripcion'], ['Permiso', 'Descripcion'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

(async function () {

  $("#txtPermiso").focus();

  var forms = document.querySelectorAll('.needs-validation')


  $("#buscar").click(function () {
    listarPermisos();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  $("#btnGuardar").click(function (event) {
    Array.prototype.slice.call(forms).forEach(function (form) {
      event.preventDefault();

      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }

      // Serializamos usando "form"
      let data = formatearArrayRequest($(form).serializeArray());
      data.link = '../models/Permisos.php';

      if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
        data.op = 'update';
      } else {
        data.op = 'agregar';
      }

      console.log({ data });
      crearPermiso(data);
    });
  });
  dissminSwal();
})()