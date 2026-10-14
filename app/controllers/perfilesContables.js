const getIva = async () => {
  try {
    const dataSend = {
      link: '../models/Referencias.php',
      op: 's_iva'
    };
    const resp = await enviarPeticion(dataSend);
    let option = ``;
    resp.forEach((d) => {
      option += `<option value="${d.id}">${d.porcentaje_iva}</option>`;
    });
    $("#slcIva").html(option);
  } catch (e) {
    console.log(e);
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
};

const listarCuentas = async () => {
  showLoadingWithTetrisLogo('Cargando plan de cuentas', 'ISA Software');

  try {
    const data = {
      link: '../models/cuentasContables.php',
      op: 'listar-todos'
    }
    const resp = await enviarPeticion(data);
    if (resp.data.lenght == 0) {
      Swal.fire({
        title: 'Imposible continuar!',
        text: 'No hay un plan de cuentas cargado, por favor validar.',
        icon: 'info',
        showCloseButton: false,
        showConfirmButton: false,
        allowOutsideClick: false
      })
      return;
    }
    let option = new Option('', '', false, false);
    $('.form-select').append(option);
    resp.data.forEach(item => {
      option = new Option(item.cuenta + ' - ' + item.descripcion, item.cuenta, false, false);
      $('.form-select').append(option);
    });
    $('.form-select').trigger('change');
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}
const editCallback = (data) => {
  limpiar();
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'form-perfiles-contables');
}
const deleteCallback = (data) => {
  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion({
          link: '../models/perfilesContables.php',
          op: 'delete',
          id: data.id
        });
        if (resp.error) {
          Swal.fire("Error", resp.message, "error");
          return;
        }
        await listarPerfiles();
        Swal.fire("Ok", resp.message, "success");
        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}

const listarPerfiles = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: '../models/perfilesContables.php',
      filtro: $("#filtro").val(),
      op: 'listar-todos'
    }

    resp = await enviarPeticion(data);
    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="perfilesContables" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable(
      'perfilesContables',
      resp.data,
      ['id', 'porcentaje_iva', 'descripcion', 'cta_ventas', 'cta_iva_ventas','cta_dev_ventas', 'cta_iva_dev', 'cta_compras', 'cta_iva_compras','cta_impoconsumo','cta_icui','cta_ibua'],
      ['Id', 'Tarifa %', 'Descripcion','Cta. Ventas', 'Cta.IVA Ventas','Cta. devoluciones', 'Cta. IVA devoluciones', 'Cta. Compras', 'Cta. IVA Compras','Cta. Impoconsumo','Cta. ICUI','Cta. IBUA'],
      editCallback,
      deleteCallback,
      false,
      false,
      false,
      false
    )
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

const limpiar = () => {
  limpiarFormulario('form-perfiles-contables');
  $('select').val(null).trigger('change');
  $("#descripcion").focus();
}
$(async function () {
  limpiar();
  await listarCuentas();
    await getIva();
  $('.form-select').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    language: {
      noResults: function () {
        return "No se encontraron resultados";
      }
    }
  });
  var forms = document.querySelectorAll('.needs-validation')
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
        data.link = '../models/perfilesContables.php';
        let id = $("#id").val();
        if (id == 0) {
          data.op = 'create';
        } else {
          data.op = 'update';
        }

        try {
          const resp = await enviarPeticion(data);
          if (resp.error) {
            dissminSwal();
            Swal.fire("Error", resp.message, "error");
            return;
          }
          dissminSwal();
          Swal.fire("Correcto!", resp.message, "success");
          limpiar();
        } catch (e) {
          dissminSwal();
          console.error(e);
          Swal.fire("Error", "Error interno de la aplicacion", "error");
        }
      }, false)
    });

  $("#buscar").click(function () {
    listarPerfiles();
  });
  $("#btnNew").click(function () {
    limpiar();
  });
});
