const clase = async () => {
  const data = {
    link: '../models/cuentasContables.php',
    op: 'clase'
  }
  const resp = await enviarPeticion(data);
  console.log({ resp }) 
  let option = ``;
  resp.data.forEach(function (d) {
    option += `<option value="${d.cuenta}">${d.cuenta} - ${d.descripcion.toUpperCase()}</option>`;
  });
  $("#clase").html(option);
}

const grupo = async () => {
  let clase = $("#clase").val();
  const data = {
    link: '../models/cuentasContables.php',
    clase: clase,
    op: 'grupo'
  }
  const resp = await enviarPeticion(data);
  let option = ``;
  resp.data.forEach(function (d) {
    option += `<option value="${d.cuenta}">${d.cuenta} - ${d.descripcion.toUpperCase()}</option>`;
  });
  $("#grupo").html(option);
}

const cuenta = async () => {
  let grupo = $("#grupo").val();
  const data = {
    link: '../models/cuentasContables.php',
    grupo: grupo,
    op: 'cuenta'
  }
  const resp = await enviarPeticion(data);
  let option = ``;
  resp.data.forEach(function (d) {
    option += `<option value="${d.cuenta}">${d.cuenta} - ${d.descripcion.toUpperCase()}</option>`;
  });
  $("#cuenta").html(option);
}

const subcuenta = async () => {
  let cuenta = $("#cuenta").val();
  const data = {
    link: '../models/cuentasContables.php',
    cuenta: cuenta,
    op: 'subcuenta'
  }
  const resp = await enviarPeticion(data);
  let option = ``;
  resp.data.forEach(function (d) {
    option += `<option value="${d.cuenta}">${d.cuenta} - ${d.descripcion.toUpperCase()}</option>`;
  });
  $("#subcuenta").html(option);
}

const auxiliar = async () => {
  let subcuenta = $("#subcuenta").val();
  const data = {
    link: '../models/cuentasContables.php',
    subcuenta: subcuenta,
    op: 'auxiliar'
  }
  const resp = await enviarPeticion(data);
  let option = ``;
  resp.data.forEach(function (d) {
    option += `<option value="${d.cuenta}">${d.cuenta} - ${d.descripcion.toUpperCase()}</option>`;
  });
  $("#auxiliar").html(option);
}

const validarCuenta = async (cuenta) => {
  const data = {
    link: '../models/cuentasContables.php',
    cuenta: cuenta,
    op: 'validar'
  };
  const resp = await enviarPeticion(data);
  return resp;
}

const crearCuenta = async (cuenta, descripcion, estado, id) => {
  let option = 'create';
  if (Number.isInteger(parseInt(id)) && parseInt(id) > 0) {
    option = 'update';
  }
  const data = {
    link: '../models/cuentasContables.php',
    cuenta: cuenta,
    estado: estado,
    descripcion: descripcion,
    op: option
  };
  const resp = await enviarPeticion(data);
  if (!resp.error) {
    Swal.fire('Correcto', resp.message, 'success');
  } else {
    Swal.fire('Error', resp.message, 'error');
  }
}

const eliminarCuenta = async (tipo) => {
  let id = 0;
  if (tipo === 'sub') { //Subcuentas
    id = $("#txt-id-sub").val();
  }
  if (tipo === 'aux') { //Auxiliares
    id = $("#txt-id-aux").val();
  }

  if (!id || isNaN(id) || parseInt(id) <= 0) {
    Swal.fire('Error', 'No ha cargado una cuenta válida para eliminar.', 'error');
    return;
  }
  const data = {
    link: '../models/cuentasContables.php',
    id: id,
    op: 'delete'
  };
  try {
    const resp = await enviarPeticion(data);
    if (resp.error) {
      Swal.fire('Error', resp.message, 'error');
    } else {
      Swal.fire('Correcto', resp.message, 'success');
      if (tipo === 'sub') { //Subcuentas
        $("#mdl-subcuentas").modal('hide');
        await subcuenta();
      }
      if (tipo === 'aux') { //Auxiliares
        $("#mdl-auxiliares").modal('hide');
        await auxiliar();
      }

    }
  } catch (error) {
    Swal.fire('Error', 'Hubo un problema al eliminar la cuenta. Intente de nuevo.', 'error');
    console.error(error);
  }
};


const listarCuentas = async () => {
   showLoadingWithTetrisLogo('Buscando cuentas contables', 'ISA Software')
   try {
    const data = {
      link: '../models/cuentasContables.php',
      filtro: $("#filtro").val(),
      op: 'listar-todos'
    }

    resp = await enviarPeticion(data);
    if (resp.data.lenght == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }
    $("#result").html(`<table id="cuentasContables" class="table table-sm table-borderless">
                            <thead class="table-primary"></thead> 
                        </table>`)
    createDataTable(
      'cuentasContables',
      resp.data,
      ['id', 'cuenta', 'descripcion', 'activa'],
      ['Id', 'Cuenta', 'Descripción', 'Activa'],
      false, //editCallback,
      false, //deleteCallback,
      false,
      false,
      false,
      true
    )
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}


$(async function () {
  await clase();
  await grupo();
  await cuenta();
  await subcuenta();
  await auxiliar();
  dissminSwal();

  $("#clase").change(async function () {
    await grupo();
    await cuenta();
    await subcuenta();
    await auxiliar();
  });
  $("#grupo").change(async function () {
    await cuenta();
    await subcuenta();
    await auxiliar();
  });
  $("#cuenta").change(async function () {
    await subcuenta();
    await auxiliar();
  });
  $("#subcuenta").change(async function () {
    await auxiliar();
  })

  $("#btn-subcuenta").click(function () {
    const cuenta = $("#cuenta").val();
    $("#txt-cuenta-sub").val(cuenta);
    $("#txt-subcuenta-sub").val('');
    $("#txt-descripcion-sub").val('');
    $("#txt-id-sub").val(0);
    $("#mdl-subcuentas").modal('show');
  });
  $("#txt-subcuenta-sub").focusout(async function () {
    const cuenta = $("#cuenta").val();
    const subcuentaValor = $(this).val();
    if (subcuentaValor && cuenta) {
      if (subcuentaValor.length === 2) {
        const subcuenta = cuenta + subcuentaValor;
        let resp = await validarCuenta(subcuenta);
        if (resp.data.length > 0) {
          resp.data.forEach(function (d) {
            $("#txt-descripcion-sub").val(d.descripcion);
            $("#txt-id-sub").val(d.id);
            if (d.activa === 'S') {
              $("#chk-estado-sub").prop("checked", true);
            } else {
              $("#chk-estado-sub").prop("checked", false);
            }
          });
        } else {
          $("#txt-descripcion-sub").val('');
          $("#txt-id-sub").val(0);
        }
      } else {
        $("#txt-subcuenta-sub").val('');
        Swal.fire('Error', 'La subcuenta debe tener una longitud de 2 caracteres.', 'error');
      }
    } else {
      $("#txt-descripcion-sub").val('');
      $("#txt-id-sub").val(0);
    }
  });

  $("#btn-guardar-sub").click(async function () {
    let cuenta = $("#txt-cuenta-sub").val();
    let sub = $("#txt-subcuenta-sub").val();
    let descripcion = $("#txt-descripcion-sub").val();
    let id = $("#txt-id-sub").val();
    let estado = 'N';

    if ($("#chk-estado-sub").prop("checked")) {
      estado = 'S';
    }
    if (cuenta === "" || sub === "" || descripcion === "") {
      Swal.fire({
        icon: 'error',
        title: 'Campos Vacíos',
        text: 'Por favor, complete todos los campos.',
        confirmButtonText: 'Aceptar'
      });
    } else {
      cuenta = cuenta + sub;
      await crearCuenta(cuenta, descripcion, estado, id);
      await subcuenta();
      $("#mdl-subcuentas").modal('hide');

    }
  });

  $("#btn-eliminar-sub").click(async function () {
    await eliminarCuenta('sub');
  });

  //Creacion de Auxiliares

  $("#btn-auxiliar").click(function () {
    const subcuenta = $("#subcuenta").val();
    $("#txt-subcuenta-aux").val(subcuenta);
    $("#txt-auxiliar-aux").val('');
    $("#txt-descripcion-aux").val('');
    $("#txt-id-aux").val(0);
    $("#mdl-auxiliares").modal('show');
  });

  $("#txt-auxiliar-aux").focusout(async function () {
    const sub = $("#subcuenta").val();
    const auxValor = $(this).val();
    if (auxValor && sub) {
      if (auxValor.length === 2) {
        const aux = sub + auxValor;
        let resp = await validarCuenta(aux);
        if (resp.data.length > 0) {
          resp.data.forEach(function (d) {
            $("#txt-descripcion-aux").val(d.descripcion);
            $("#txt-id-aux").val(d.id);
            if (d.activa === 'S') {
              $("#chk-estado-aux").prop("checked", true);
            } else {
              $("#chk-estado-aux").prop("checked", false);
            }
          });
        } else {
          $("#txt-descripcion-aux").val('');
          $("#txt-id-aux").val(0);
        }
      } else {
        $("#txt-auxiliar-aux").val('');
        Swal.fire('Error', 'La subcuenta debe tener una longitud de 2 caracteres.', 'error');
      }
    } else {
      $("#txt-descripcion-aux").val('');
      $("#txt-id-aux").val(0);
    }
  });

  $("#btn-guardar-aux").click(async function () {
    let sub = $("#txt-subcuenta-aux").val();
    let aux = $("#txt-auxiliar-aux").val();
    let descripcion = $("#txt-descripcion-aux").val();
    let id = $("#txt-id-aux").val();
    let estado = 'N';

    if ($("#chk-estado-aux").prop("checked")) {
      estado = 'S';
    }
    if (sub === "" || aux === "" || descripcion === "") {
      Swal.fire({
        icon: 'error',
        title: 'Campos Vacíos',
        text: 'Por favor, complete todos los campos.',
        confirmButtonText: 'Aceptar'
      });
    } else {
      sub = sub + aux;
      await crearCuenta(sub, descripcion, estado, id);
      await auxiliar();
      $("#mdl-auxiliares").modal('hide');

    }
  });

  $("#btn-eliminar-aux").click(async function () {
    await eliminarCuenta('aux');
  });

  $("#buscar").click(async function () {
    await listarCuentas();
  });

});
