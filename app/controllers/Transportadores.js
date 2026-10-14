let link = "../models/Transportadores.php";

/**
 * ===============================
 *   UTILIDADES
 * ===============================
 */
const showError = (msg = "Error interno de la aplicación") =>
  Swal.fire("Error", msg, "error");

const showSuccess = (msg) =>
  Swal.fire("Excelente", msg, "success");

const showInfo = (msg) =>
  Swal.fire("Información", msg, "info");

/**
 * ===============================
 *   CARGAR COMBOS
 * ===============================
 */
const tipoIdentificacion = async () => {
  try {
    const resp = await enviarPeticion({
      link: "../models/tipoIdentificaciones.php",
      filtro: "",
      op: "listar",
    });

    let options = `<option value="">Seleccionar</option>`;
    resp.data.forEach((d) => {
      options += `<option value="${d.id}">
        ${d.tipo_identificacion.toUpperCase()} - ${d.descripcion.toUpperCase()}
      </option>`;
    });
    $("#id_tipo_identificacion").html(options);
  } catch (e) {
    console.error("Error en tipoIdentificacion:", e);
  }
};

/**
 * ===============================
 *   VALIDAR NIT
 * ===============================
 */
const validarNit = async () => {
  const nit = $("#Nit").val().trim();
  if (!nit) return;

  try {
    const resp = await enviarPeticion({ link, op: "s_nit", nit });

    if (resp.data.length > 0) {
      const d = resp.data[0];
      $("#IdTrans").val(d.id);
      $("#Nit").val(d.nit);
      $("#Nombres").val(d.nombres);
      $("#Razon").val(d.razon_social);
      $("#Celular").val(d.celular);
      $("#dpto").val(d.departamento);
      await ciudades();
      $("#mpio").val(d.municipio);
      $("#Vehiculo").val(d.vehiculo);
      $("#Placas").val(d.placas);
      $("#BloqueoPlanilla").val(d.bloqueo_planilla);
      $("#SlcTipoT").val(d.tipo);
    } /*else {
      limpiarCampos();
    }*/
  } catch (e) {
    console.error("Error en validarNit:", e);
  }
};

/**
 * ===============================
 *   GUARDAR / ACTUALIZAR
 * ===============================
 */
const guardar = async () => {
  const formData = {
    id: $("#IdTrans").val().trim(),
    nit: $("#Nit").val().trim(),
    nombres: $("#Nombres").val().trim().toUpperCase(),
    razon_social: $("#Razon").val().trim().toUpperCase(),
    celular: $("#Celular").val().trim(),
    codigo_dpto: $("#dpto").val().trim(),
    codigo_mpio: $("#mpio").val().trim(),
    vehiculo: $("#Vehiculo").val().trim().toUpperCase(),
    placas: $("#Placas").val().trim().toUpperCase(),
    bloqueo_planilla: $("#BloqueoPlanilla").val(),
    tipo_transportador: $("#SlcTipoT").val(),
    id_tipo_identificacion: $("#id_tipo_identificacion").val(),
  };

  // Validación
  const obligatorios = ["nit", "nombres", "razon_social", "celular", "codigo_dpto", "codigo_mpio", "vehiculo", "placas"];
  const faltantes = obligatorios.filter((c) => !formData[c]);

  if (faltantes.length > 0) {
    return showError("Por favor complete todos los campos requeridos.");
  }

  try {
    showLoadingWithTetrisLogo('Guardando transportador', 'ISA Software');
    const resp = await enviarPeticion({ link, op: "g_transportador", ...formData });

    if (resp.error) {
      showError(resp.message);
    } else {
      showSuccess(resp.message);
      await limpiar();
    }
  } catch (e) {
    console.error("Error en guardar:", e);
    showError("Ocurrió un problema al guardar los datos.");
  } finally {
    dissminSwal();
  }
};

/**
 * ===============================
 *   LISTAR
 * ===============================
 */
const listarTodos = async () => {
  showLoadingWithTetrisLogo('Cargando transportadores', 'ISA Software');
  try {
    const resp = await enviarPeticion({ link, op: "s_transportadores" });

    if (resp.data.length === 0) {
      $("#result").html(ShowAlert("No se encontraron resultados", "warning"));
      return;
    }

    $("#result").html(`
      <table id="tb_transportadores" class="table table-sm table-borderless table-hover table-striped" width="100%">
        <thead class="table-primary"></thead> 
      </table>
    `);

    createDataTable(
      "tb_transportadores",
      resp.data,
      ["id", "nit", "nombres", "razon_social", "celular", "departamento", "municipio", "vehiculo", "placas", "bloqueo_planilla", "tipo_desc", "fecha_creacion"],
      ["ID", "NIT", "Nombres", "Razón Social", "Celular", "Departamento", "Municipio", "Vehículo", "Placas", "Bloqueo Planilla", "Tipo", "Fecha Creación"],
      editCallback,
      eliminar // unifico con la misma función de eliminación
    );
  } catch (e) {
    console.error("Error en listarTodos:", e);
    $("#result").html(ShowAlert("Error interno de la aplicación", "error"));
  } finally {
    dissminSwal();
  }
};

/**
 * ===============================
 *   ELIMINAR
 * ===============================
 */
const eliminar = async (data) => {
  const id = data?.id || $("#IdTrans").val();
  if (!id || id == 0) {
    return showInfo("Debe seleccionar un transportador.");
  }

  try {
    const confirmed = await showConfirmation(
      "¿Estás seguro de eliminar este transportador?",
      "¡No podrás revertir esto!",
      "question"
    );

    if (!confirmed) return;

    const resp = await enviarPeticion({ link, op: "d_transportador", id });
    if (resp.error) {
      showError(resp.message);
    } else {
      showSuccess(resp.message);
      await listarTodos();
    }
  } catch (e) {
    console.error("Error en eliminar:", e);
    showError("Se generó un error interno en el sistema.");
  }
};

/**
 * ===============================
 *   EDITAR
 * ===============================
 */
const editCallback = async (data) => {
  selectedTab("tabs", "navs-busqueda");

  $("#IdTrans").val(data.id);
  $("#Nit").val(data.nit);
  $("#Nombres").val(data.nombres);
  $("#Razon").val(data.razon_social);
  $("#Celular").val(data.celular);
  $("#dpto").val(data.codigo_dpto);

  let pais = $("#pais").val();
  await municipios(pais, data.codigo_dpto, "mpio");

  $("#mpio").val(data.codigo_mpio);
  $("#Vehiculo").val(data.vehiculo);
  $("#Placas").val(data.placas);
  $("#BloqueoPlanilla").val(data.bloqueo_planilla);
  $("#SlcTipoT").val(data.tipo_transportador);
  $("#id_tipo_identificacion").val(data.id_tipo_identificacion);
};

/**
 * ===============================
 *   LIMPIAR CAMPOS
 * ===============================
 */
const limpiarCampos = () => {
  $("#IdTrans").val(0);
  $("#Nit, #Nombres, #Razon, #Celular, #Vehiculo, #Placas").val("");
  $("#dpto, #mpio").val("");
};

const limpiar = async () => {
  limpiarCampos();
  await paises("pais");
  let pais = $("#pais").val();
  await departamentos(pais, "dpto");
  let dpto = $("#dpto").val();
  await municipios(pais, dpto, "mpio");
  await tipoIdentificacion();
};

/**
 * ===============================
 *   INIT
 * ===============================
 */
$(async function () {

  await limpiar();

  $("#dpto").change(async function () {
    let pais = $("#pais").val();
    await municipios(pais, $(this).val(), "mpio");
  });

  $("#btnGuardar").click(guardar);
  $("#btnLimpiar").click(limpiar);
  $("#btnListar").click(listarTodos);
  $("#btnEliminar").click(eliminar);

  $("#Nit").focusout(async function () {
    $(this).val() ? await validarNit() : await limpiar();
  });

  dissminSwal();
});
