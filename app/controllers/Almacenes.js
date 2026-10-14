const cargarCentros = async () => {
  try {
    resp = await enviarPeticion({
      link: '../models/centros.php',
      filtro: '',
      op: 'listar'
    });
    if (resp.data.length == 0) {
      return;
    }
    p = '<option value="0">Ninguno</option>';
    resp.data.forEach(centro => {
      p += `<option value="${centro.id}">${centro.centro} - ${centro.descripcion}</option>`
    })
    $("#slcCentros").html(p);
  } catch (e) {
    console.error(e)
  }

}

const crearAlmacenes = async (data) => {
  try {
    const resp = await enviarPeticion(data);
    console.log({resp})
    if (resp.error) {
      Swal.fire("Error", resp.message, "error");
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
  limpiarFormulario('FormCrear')
  selectedTab('tabs', 'navs-creacion');
  ShowDataFrom(data, 'FormCrear');
}

function deleteCallback(data) {

  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        resp = await enviarPeticion({
          link: '../models/almacenes.php',
          op: 'delete',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", "No se puedo eliminar el registro", "error");
          return;
        }
        await listarAlmacenes();
        Swal.fire("Ok", "Se elimino correctamente el registro", "success");

        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}


const listarAlmacenes = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: '../models/almacenes.php',
      filtro: $("#filtro").val(),
      op: 'listar'
    }

    resp = await enviarPeticion(data);

    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }

    $("#result").html(`<table id="almacenes" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('almacenes', resp.data, ['almacen', 'descripcion', 'centro_des', 'direccion', 'transito_des', 'dpto_des', 'municipio_des', 'ean_edi'],
      ['Almacen', 'Descripcion', 'Centro', 'Direccion', 'Virtual', 'Dpto', 'Ciudad', 'EAN EDI'], editCallback, deleteCallback)
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

const llenarSelectListas = async () => {
  let listas = await listasPrecios();

  if (!Array.isArray(listas)) {
    console.warn("El parámetro listas no es un array");
    return;
  }

  const options = [
    `<option value="0">Seleccione una lista</option>`,
    ...listas.map(({
      id,
      Descripcion
    }) =>
      `<option value="${id}">${id} - ${Descripcion}</option>`
    )
  ];

  $("#lista_precios").html(options.join(""));
}


async function listarListas() {

  const resp = await listasPrecios();
  if (!Array.isArray(resp)) return;

  let html = '';

  resp.forEach(lista => {
    html += `
      <div class="d-flex justify-content-between align-items-center border-bottom py-1">
        <small>${lista.Descripcion}</small>
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-primary"
                  onclick='editarLista(${JSON.stringify(lista)})'>
            <i class="fa fa-edit"></i>
          </button>
          <button class="btn btn-outline-danger"
                  onclick='deleteLista(${JSON.stringify(lista)})'>
            <i class="fa fa-trash"></i>
          </button>
        </div>
      </div>
    `;
  });

  $("#contenedorListas").html(html);
}

$("#btnGuardarLista").on("click", async function () {

  const id = $("#lista_id").val();
  const descripcion = $("#lista_descripcion").val().trim();

  if (!descripcion) {
    Swal.fire("Error", "Ingrese una descripción", "warning");
    return;
  }

  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: id ? 'update-listas' : 'insert-listas',
    id,
    Descripcion: descripcion
  });

  if (resp.error) {
    Swal.fire("Error", resp.message, "error");
    return;
  }

  await listarListas();
  await recargarSelectListas();
  await listarAlmacenes();

  $("#btnNuevoLista").click();

  Swal.fire("Excelente", resp.message, "success");
});


function editarLista(data) {
  $("#lista_id").val(data.id);
  $("#lista_descripcion").val(data.Descripcion).focus();
}

function deleteLista(data) {

  try {

    showConfirmation().then(async (confirmed) => {

      if (confirmed) {
        const resp = await enviarPeticion({
          link: '../shared/servicios/servicios.php',
          op: 'delete-listas',
          id: data.id
        });

        if (resp.error) {
          Swal.fire("Error", resp.message, "error");
          return;
        }

        await listarListas();
        await recargarSelectListas();

        Swal.fire("Excelente", resp.message, "success");
      }

    });

  } catch (e) {
    Swal.fire("Error", "Se generó un error interno en el sistema", "error");
  }

}


async function recargarSelectListas() {
  listarListas();
  llenarSelectListas();
}
(async function () {
  'use strict'
  await llenarSelectListas();
  await paises('slcPais');
  await cargarCentros();
  await departamentos($("#slcPais").val(), 'slcDepartamento');
  await municipios($("#slcPais").val(), $("#slcDepartamento").val(), 'slcCiudad');


  $("#slcDepartamento").change(async function () {
    await municipios($("#slcPais").val(), $("#slcDepartamento").val(), 'slcCiudad');
  })

  $("#txtTitulo").focus();

  dissminSwal();
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
        let data = formatearArrayRequest($(this).serializeArray());
        data.link = '../models/almacenes.php';
        if ($("#txtId").val() != '0' && $("#txtId").val() != '') {
          data.op = 'update';
        } else {
          data.op = 'agregar'
        }

        if ($("#Chktransito").is(":checked")) {
          data.transito = 1;
        } else {
          data.transito = 0;
        }

        console.log({
          data
        })
        crearAlmacenes(data);
      }, false)
    });

  $("#buscar").click(function () {
    listarAlmacenes();
  });

  $("#filtro").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    listarAlmacenes();
  });

  $("#btnNuevo").click(function () {
    limpiarFormulario('FormCrear');
  });

  $("#btnGestionListas").on("click", async function () {
    await listarListas();
    $("#modalListas").modal('show');
  });

  $("#btnNuevoLista").on("click", function () {
    $("#lista_id").val('');
    $("#lista_descripcion").val('').focus();
  });


})()
