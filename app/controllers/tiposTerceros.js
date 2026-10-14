
const CrearTiposTerceros = async (data) => {

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
  if (data.editable != 'S') {
    Swal.fire("Up", "Este tipo de tercero no puede ser modificado ni eliminado", "info")
    return;
  }

  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {

  try {

    if (data.editable != 'S') {
      Swal.fire("Up", "Este tipo de tercero no puede ser modificado ni eliminado", "info")
      return;
    }
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          op: 'delete',
          link: '../models/tiposTerceros.php',
          id: data.id
        });
        console.log(resp)
        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarTiposTerceros();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}


const listarTiposTerceros = async () => {

  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: '../models/tiposTerceros.php',
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
    createDataTable('modulos', resp.data, ['tipo_tercero', 'descripcion', 'fecha_creacion', 'inactivo'], ['Tipo', 'Descripcion', 'Fecha creacion', 'Inactivo'], editCallback, deleteCallback)
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

        data.link = '../models/tiposTerceros.php'

        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }
        data.es_proveedor = 'N';
        data.es_cliente = 'N';
        data.es_vendedor = 'N';
        data.es_empleado = 'N';

        if ($("#es_proveedor").is(":checked")) {
          data.es_proveedor = 'S';
        }

        if ($("#es_cliente").is(":checked")) {
          data.es_cliente = 'S';
        }

        if ($("#es_vendedor").is(":checked")) {
          data.es_vendedor = 'S';
        }

        if ($("#es_empleado").is(":checked")) {
          data.es_empleado = 'S';
        }
        console.log({ data })
        await CrearTiposTerceros(data);

      }, false)
    });

  $("#buscar").click(function () {
    listarTiposTerceros();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  dissminSwal();
  // =========================
  // EVENTO BOTÓN VIDEO YOUTUBE
  // =========================

  $("#btn-video-tutorial").click(function () {
    const videoURL = "https://youtu.be/0J7XBo_CkPE";
    const videoID = videoURL.split('/').pop().split('?')[0];
    const embedURL = `https://www.youtube.com/embed/${videoID}`;

    $("#videoFrameTutorial").attr("src", embedURL);

    const modal = new bootstrap.Modal(document.getElementById('modalVideoTutorial'));
    modal.show();
  });

  // Detener video al cerrar el modal
  $("#modalVideoTutorial").on('hidden.bs.modal', function () {
    $("#videoFrameTutorial").attr("src", "");
  });
})()