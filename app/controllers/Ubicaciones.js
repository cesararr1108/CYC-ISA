const link_ = ''


const CrearUbicaciones = async (data) => {

  try {
    resp = await enviarPeticion(data);

    if (resp.error) {
      dissminSwal();
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    dissminSwal();
    Swal.fire("Ok", resp.message, "success");
    limpiarFormulario('FormCrear');

  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
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
          link: '../models/Ubicaciones.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarUbicaciones();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}


const listarUbicaciones = async () => {

  showLoadingWithTetrisLogo('Listando ubicaciones', 'ISA Software')
  try {
    const data = {
      link: '../models/Ubicaciones.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="ubicaciones" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('ubicaciones', resp.data, ['id', 'ubicacion', 'fecha_creacion', 'usuario'], ['#Id', 'Ubicacion', 'Fecha creacion', 'Usuario'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

(async function () {
  'use strict'

  $("#txtTipo").focus();

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
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
        let data = formatearArrayRequest($(this).serializeArray());

        data.link = '../models/Ubicaciones.php';

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }


        await CrearUbicaciones(data);
      }, false)
    });

  $("#buscar").click(function () {
    listarUbicaciones();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
   dissminSwal();
})()