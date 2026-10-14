
const crearRol = async (formulario) => {

  let data = formatearArrayRequest($(formulario).serializeArray());

  data.link = '../models/roles.php';

  if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
    data.op = 'update'
  } else {
    data.op = 'agregar';
  }


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
          link: '../models/roles.php',
          op: 'delete',
          id: data.id
        });
        console.log({ resp })

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarRoles();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}


const listarRoles = async () => {

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/roles.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="modulos" class="table table-sm table-borderless table-hover table-striped">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('modulos', resp.data, ['id', 'descripcion', 'estado'], ['Id', 'Rol', 'Estado'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

(async function () {
  $("#txtRol").focus();
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
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

        crearRol(this);
        return;
      }, false)
    });

  $("#buscar").click(function () {
    listarRoles();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
 dissminSwal();
})()