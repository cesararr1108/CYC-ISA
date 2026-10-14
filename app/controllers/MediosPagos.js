
const cargarCuentasContables = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/MediosPagos.php',
      op: 'listar-cuentas'
    });
    const options = ['<option value="">Seleccione una cuenta</option>'];
    (resp.data || []).forEach((item) => {
      options.push(`<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion}</option>`);
    });
    $('#slcCuenta').html(options.join('')).trigger('change');
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error al cargar cuentas contables", "error");
  }
}

const limpiarFormularioConSelect2 = () => {
  limpiarFormulario('FormCrear');
  $('#slcCuenta').val('').trigger('change');
}

const crearMedioPago = async (data) => {
  try {
    const resp = await enviarPeticion(data);

    if (resp.error) {
      Swal.fire("Error", resp.message, "error");
      return;
    }

    // Mostrar mensaje de éxito y luego limpiar el formulario
    await Swal.fire("Ok", resp.message, "success");
    limpiarFormularioConSelect2();
    $("#result").html('');
  } catch (e) {
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }
}

function editCallback(data) {
  limpiarFormularioConSelect2();
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {
  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/MediosPagos.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se pudo eliminar el registro", "error");
          return;
        }
        await Swal.fire("Ok", "Se eliminó correctamente el registro", "success");
        await listarMediosPagos();

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se generó un error interno en el sistema", "error");
  }
}

const listarMediosPagos = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: '../models/MediosPagos.php',
      filtro: $("#filtro").val(),
      op: 'listar'
    };

    resp = await enviarPeticion(data);

    if (resp.data.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'));
      dissminSwal();
      return;
    }

    $("#result").html(`<table id="mediosPagos" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`);

    createDataTable('mediosPagos', resp.data, ['descripcion', 'cuenta', 'naturaleza', 'editable', 'inactivo'],
      ['Descripción', 'Cuenta', 'Naturaleza', 'Editable', 'Inactivo'], editCallback, deleteCallback);

    // Ajustar ancho de columnas después de crear la tabla
    setTimeout(() => {
      $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    }, 100);

    dissminSwal();
  } catch (e) {
    console.error(e);
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'));
    dissminSwal();
  }
}

(async function () {
  await cargarCuentasContables();

  $('#slcCuenta').select2({
    placeholder: 'Seleccione una cuenta',
    allowClear: true,
    width: '100%'
  });

  $("#txtDescripcion").focus();

  dissminSwal();
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation');

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {

        event.preventDefault();

        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
          form.classList.add('was-validated');

          return;
        }
        let data = formatearArrayRequest($(this).serializeArray());
        data.link = '../models/MediosPagos.php';
        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }

        console.log({ data });
        crearMedioPago(data);
      }, false);
    });

  $("#buscar").click(function () {
    listarMediosPagos();
  });

  $("#btnNuevo").click(function () {
    limpiarFormularioConSelect2();
  });
  dissminSwal();
})();
