function editCallback(data) {

  if (data.editable == 'N') {
    Swal.fire("Ups", "Esta condición de pago no se puede editar", "info")
    return;
  }
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {
  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/condicionesPago.php',
          op: 'delete',
          id: data.id
        });
        if (resp.error) {
          Swal.fire("Error", resp.message, "error");
          return;
        }
        await listarCondiciones();
        Swal.fire("Correcto!", resp.message, "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }
}

const listarCondiciones = async () => {
  showLoadingWithTetrisLogo('Buscando condiciones de pago', 'ISA Software')
  try {
    const data = {
      link: '../models/condicionesPago.php',
      filtro: $("#filtro").val(),
      op: 'listar-todos'
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="condicionesPago" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable(
      'condicionesPago',
      resp.data,
      ['id', 'tipo_condicion', 'descripcion', 'dias', 'dto', 'dias_gracia', 'fecha_creacion'],
      ['Id', 'Tipo', 'Descripción', 'Dias', 'Descuento', 'Dias gracia', 'Fecha creacion'],
      editCallback,
      deleteCallback,
      false,
      `<button class="btn btn-sm btn-outline-info other-btn"><i class="fa fa-info-circle"></i></button>`
    )
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}
const crearCondicionPago = async (data) => {
  try {
    const resp = await enviarPeticion(data);
    if (resp.error) {
      dissminSwal();
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    dissminSwal();
    Swal.fire("Correcto!", resp.message, "success");
    limpiarFormulario('FormCrear');
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }

}

$(function () {
  $("#buscar").click(function () {
    listarCondiciones();
  });
  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  });
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
        form.classList.add('was-validated');

        return;
      }
      showLoadingWithTetrisLogo('Guardando condición de pago', 'ISA Software')
      let data = formatearArrayRequest($(this).serializeArray());
      data.link = '../models/condicionesPago.php';

      if (isNaN(parseInt($("#dias_gracia").val()))) {
        data.dias_gracia = 0
      }

      if ($("#id").val() != '0' && $("#id").val() != '') {
        data.op = 'update';
      } else {
        data.op = 'agregar';
        delete data.id;
      }

      if ($("#editable").is(":checked")) {
        data.editable = 'S'
      } else {
        data.editable = 'N'
      }
      await crearCondicionPago(data);
    }, false)
  });
  dissminSwal();
})
