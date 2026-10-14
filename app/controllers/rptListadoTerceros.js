const link_ = '../models/rptListadoTerceros.php';

$(function () {
  $("#btn-busqueda").click(async () => {
    await consultarTerceros();
  })
  $("#btn-limpiar").click(async () => {
    await limpiarFiltros();
  });
  tiposTerceros();
  dissminSwal();
});
/*
 @ Tipos de terceros
*/
const tiposTerceros = async () => {
  try {
    const data = {
      link: '../models/tiposTerceros.php',
      filtro: '',
      op: 'listar'
    };
    var resp = await enviarPeticion(data);

    let option = `<option value="">TODOS</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_tercero").html(option);
  } catch (e) {
    console.log(e);
  }
}

const consultarTerceros = async () => {
  showLoadingWithTetrisLogo('Cargando terceros', 'ISA Software')

  try {
    // 🔹 Definir campos y encabezados
    const campos = [
      "nit",
      "nit_digito",
      "tipo_identificacion",
      "razon_comercial",
      "tipo_tercero",
      "departamento",
      "municipio",
      "direccion",
      "zona_ventas",
      "des_condicion",
      "des_condicion_prov",
      "email",
      "telefono"
    ];

    const encabezados = [
      "NIT",
      "Dígito",
      "Tipo Identificación",
      "Razón Comercial",
      "Tipo",
      "Departamento",
      "Municipio",
      "Dirección",
      "Zona Ventas",
      "Condición Cliente",
      "Condición Proveedor",
      "Email",
      "Teléfono"
    ];

    // 🔹 Capturar filtros
    const filtros = {
      op: "s_terceros_detalle",
      tipo_tercero: $("#id_tipo_tercero").val(),
      nombre_tercero: $("#busqueda-tercero").val()
    };
    // 🔹 Preparar payload
    const data = {
      link: link_,
      ...filtros
    };

    // 🔹 Ejecutar petición
    const resp = await enviarPeticion(data);
    // 🔹 Validar respuesta
    if (!resp || resp.length === 0) {
      $("#resultado-terceros").html(
        ShowAlert("No se encontraron terceros", "warning")
      );
      dissminSwal();
      return;
    }

    // 🔹 Renderizar tabla
    $("#resultado-terceros").html(`
     <table id="tb_terceros" class="table table-sm table-borderless table-hover table-striped w-100">
        <thead class="table-primary"></thead> 
        <tbody></tbody>
      </table>
    `);

    createDataTable(
      'tb_terceros', // ID tabla
      resp, // Datos
      campos, // Campos
      encabezados, // Encabezados
      '', // Callback editar
      '', // Callback eliminar
      false, // Checkbox
      '', // Extra param
      '', // Extra param
      true // Responsive
    );

    dissminSwal();
  } catch (e) {
    console.error("❌ Error en consultarTerceros:", e);
    $("#resultado-terceros").html(
      ShowAlert("Error interno de la aplicación", "error")
    );
    dissminSwal();
  }
};

const limpiarFiltros = async () => {
  $("#id_tipo_tercero").val('');
  $("#busqueda-tercero").val('');
  $("#resultado-terceros").html('');
};
