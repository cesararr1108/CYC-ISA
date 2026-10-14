const link_ = 'modulos'

const loadGrupos = async () => {
  try {
    resp = await enviarPeticion({
      link: '../models/modulos.php',
      metodo: 'POST',
      op: 'cargarGrupos'
    });

    if (resp.lenght == 0) {
      return;
    }
    p = '';
    resp.forEach(grupo => {
      p += `<option value="${grupo.id}">${grupo.grupo}</option>`
    })
    console.log(p)
    $("#slcGrupos").html(p);
  } catch (e) {
    console.error(e)
  }
}

const loadGruposRpt = async () => {
  try {
    resp = await enviarPeticion({
      link: '../models/modulos.php',
      metodo: 'POST',
      op: 'cargarGruposRpt'
    });

    if (resp.lenght == 0) {
      return;
    }
    p = '<option value="0">N/A</option>';
    resp.forEach(grupo => {
      p += `<option value="${grupo.id}">${grupo.grupo}</option>`
    })
    console.log(p)
    $("#slcGruposRpt").html(p);
  } catch (e) {
    console.error(e)
  }
}

const crearModulo = async (data) => {

  try {
    resp = await enviarPeticion(data);

    if (resp.error) {
      dissminSwal()
      Swal.fire("Error", "Se genero un error al guardar", "error");
      return;
    }
    dissminSwal()
    Swal.fire("Ok", resp.message, "success");
    limpiarFormulario('FormCrear');
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire("Error", "Error interno de la aplicacion", "error");
  }

}


function editCallback(data) {
  console.log({
    data
  })
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
  $("#slcGruposRpt").trigger("change")
}

function deleteCallback(data) {

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/modulos.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarModulos();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }


}


const listarModulos = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
  try {
    const data = {
      link: '../models/modulos.php',
      op: 'listar',
      filtro: $("#filtro").val()
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="modulos" class="table table-sm table-borderless table-hover">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('modulos', resp.data, ['titulo', 'descripcion', 'grupo', 'version'], ['Titulo', 'Descripcion', 'Grupo', 'Version'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

(async function () {
  loadGrupos();
  loadGruposRpt();

  let usuario;
  $("#txtTitulo").focus();
  $("#slcGrupos").change(function () {
    const valor = $(this).val()
    if (valor == '7') {
      $("#slcGruposRpt").attr("disabled", false)
    } else {
      $("#slcGruposRpt").attr("disabled", true)
      $("#slcGruposRpt").val('0')
    }
  })
  //usuario =await GetDataUser();

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
        showLoadingWithTetrisLogo('Guardando registro', 'ISA Software')
        let data = formatearArrayRequest($(this).serializeArray());
        data.link = '../models/modulos.php';
        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar';
        }
        crearModulo(data);

        return;
      }, false)
    });

  $("#buscar").click(function () {
    listarModulos();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  })
  dissminSwal();
})()