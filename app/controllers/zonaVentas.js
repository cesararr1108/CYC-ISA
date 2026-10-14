const link_ = 'api/zonaventas'


const CrearZonaVentas = async (data) => {

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
          link: '../models/zonasVentas.php',
          op: 'delete',
          id: data.id
        });
        console.log(resp)
        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarZonaVentas();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}


const listarZonaVentas = async () => {

  showLoadingSwalAlert2('<i class="fa fa-search fa-beat-fade"></i>', 'Buscando información', false)
  try {
    const data = {
      link: '../models/zonasVentas.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="zona_ventas" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('zona_ventas', resp.data, ['codigo_zona', 'descripcion', 'fecha_creacion', 'inactivo_des'], ['Zona ventas', 'Descripcion', 'Fecha creacion', 'Inactivo'], editCallback, deleteCallback)
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

        data.link = '../models/zonasVentas.php';

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }


        await CrearZonaVentas(data);
      }, false)
    });

  $("#buscar").click(function () {
    listarZonaVentas();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  dissminSwal();
})()