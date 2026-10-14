const link_ = ''


const CrearMarcas = async (data) => {

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
          link: '../models/Marcas.php',
          op: 'delete',
          id: data.id
        });
        console.log(resp)
        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarMarcas();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}


const listarMarcas = async () => {

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/Marcas.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="marcas" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('marcas', resp.data, ['id', 'descripcion', 'fecha_creacion', 'inactivo_des'], ['#Id', 'Descripcion', 'Fecha creacion', 'Estado'], editCallback, deleteCallback, false, '', '', true)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

(async function () {

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

        data.link = '../models/Marcas.php';

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }


        await CrearMarcas(data);
      }, false)
    });

  $("#buscar").click(function () {
    listarMarcas();
  });
  $("#filtro").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    listarMarcas();
  });
  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
dissminSwal();
})()