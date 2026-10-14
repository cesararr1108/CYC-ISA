
const CrearTiposIdentificaciones = async (data) => {

  try {

    showLoadingWithTetrisLogo('Guardando información', 'ISA Software')
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
          link: '../models/tipoIdentificaciones.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarTiposIdentificaciones();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}


const listarTiposIdentificaciones = async () => {

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: '../models/tipoIdentificaciones.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="modulos" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable(
      'modulos',
      resp.data,
      ['tipo_identificacion', 'descripcion', 'fecha_creacion'],
      ['Tipo', 'Descripcion', 'Fecha creacion'],
      editCallback,
      deleteCallback,
      null,
      '',
      null,
      false)
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

        data.link = '../models/tipoIdentificaciones.php'

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }

        await CrearTiposIdentificaciones(data);

      }, false)
    });

  $("#buscar").click(function () {
    listarTiposIdentificaciones();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  dissminSwal();
})()