const cargarBalance = async (filtros) => {
  try {
    showLoadingWithTetrisLogo('Consultando información', 'ISA Software')
    const resp = await enviarPeticion(filtros);

    if (!resp || !resp.success || resp.data.length === 0) {
      $("#contenedor-balance").html('<div class="alert alert-info">No se encontraron registros.</div>');
      dissminSwal();
      return;
    }

    const data = resp.data;

    // 🔹 Calcular totales
    const totales = data.reduce((acc, row) => {
      acc.saldo_inicial += Number(row.saldo_inicial) || 0;
      acc.debito += Number(row.debito) || 0;
      acc.credito += Number(row.credito) || 0;
      acc.saldo_final += Number(row.saldo_final) || 0;
      return acc;
    }, { saldo_inicial: 0, debito: 0, credito: 0, saldo_final: 0 });

    // 🔹 Renderizar cards con totales
    $("#contenedor-balance").html(`
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6>Saldo Inicial</h6>
                            <h5>${totales.saldo_inicial.toLocaleString()}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card ">
                        <div class="card-body">
                            <h6>Débito</h6>
                            <h5>${totales.debito.toLocaleString()}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6>Crédito</h6>
                            <h5>${totales.credito.toLocaleString()}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6>Saldo Final</h6>
                            <h5>${totales.saldo_final.toLocaleString()}</h5>
                        </div>
                    </div>
                </div>
            </div>
            <table id="tb_balance" class="table table-sm table-borderless table-hover table-striped w-100">
                <thead class="table-primary"></thead>
                <tbody></tbody>
            </table>
        `);

    // Campos fijos de balance de prueba
    let campos = ["ano", "mes", "cuenta", "descripcion", "saldo_inicial", "debito", "credito", "saldo_final"];
    let encabezados = ["Año", "Mes", "Cuenta", "Descripción", "Saldo Inicial", "Débito", "Crédito", "Saldo Final"];

    // Detectar si hay terceros dinámicamente
    const terceros = Object.keys(data[0]).includes("nit") ? ["nit"] : [];
    if (terceros.length > 0) {
      campos.splice(4, 0, "nit"); // insertar 'nit' antes de saldo inicial
      encabezados.splice(4, 0, "Cliente / Nit");
    }

    // Columnas a formatear como numéricas
    const columnasFormateadas = ["saldo_inicial", "debito", "credito", "saldo_final"];

    // Crear DataTable usando tu función
    createDataTable(
      'tb_balance',
      data,
      campos,
      encabezados,
      '',   // opciones adicionales
      '',   // botones extra
      false, // orden inicial
      '',   // scrollX extra
      '',   // responsive extra
      true, // scroll o responsive activado
      columnasFormateadas
    );
    dissminSwal();

  } catch (e) {
    console.error(e);
    $("#contenedor-balance").html(ShowAlert('Error interno de la aplicación', 'error'));
    dissminSwal();
  }
};


const listarCuentas = async () => {
  showLoadingWithTetrisLogo('Cargando plan de cuentas', 'ISA Software')
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

const limpiar = () => {
  const hoy = new Date();
  const mesAnio = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');
  $("#fecha_inicial").val(mesAnio);
  $("#fecha_final").val(mesAnio);
  $("#cu_inicial").val(null).trigger('change');
  $("#cu_final").val(null).trigger('change');
  $("#ver_terceros").prop('checked', false);
  $("#contenedor-balance").html('');
}

$(async function () {
  $('.form-select').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    language: {
      noResults: function () {
        return "No se encontraron resultados";
      }
    }
  });
  await listarCuentas();
  limpiar();

  $("#btn-busqueda").click(function () {
    let fhIni = $("#fecha_inicial").val();
    let fhFin = $("#fecha_final").val();
    let cuIni = $("#cu_inicial").val() || '';
    let cuFin = $("#cu_final").val() || '';
    let verTerceros = $("#ver_terceros").is(":checked") ? 1 : 0;
    let verNiveles = $("#ver_niveles").is(":checked") ? 1 : 0;

    // Validar fechas
    if (!fhIni || !fhFin) {
      Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
      return;
    }

    // Convertir a objeto Date para comparar
    let dIni = new Date(fhIni);
    let dFin = new Date(fhFin);
    if (dFin < dIni) {
      Swal.fire("Error", "La fecha final no puede ser anterior a la fecha inicial", "error");
      return;
    }

    // Llamar a la función de carga pasando todos los parámetros
    cargarBalance({
      link: '../models/rptBalancePrueba.php',
      fecha_inicial: fhIni,
      fecha_final: fhFin,
      cuenta_inicial: cuIni,
      cuenta_final: cuFin,
      ver_terceros: verTerceros,
      ver_niveles: verNiveles
    });
  });

  $("#btn-limpiar").click(function () {
    limpiar();
  });


});
