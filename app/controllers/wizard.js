// Arrays globales para almacenar datos importados
let arrayRef = [];
let arrayInv = [];
let arrayTer = [];

let currentStep = 1;
const totalSteps = 5;

let wizardData = {
  empresa: {},
  representante: {},
  referencias: null,
  inventario: null,
  terceros: null
};

const cargarExcel = async (file, tipo) => {
  showLoadingWithTetrisLogo('Cargando información', 'ISA Software')
  var reader = new FileReader();
  reader.onload = function (event) {
    var data = new Uint8Array(event.target.result);
    var workbook = XLSX.read(data, {
      type: 'array'
    });
    var sheetName = workbook.SheetNames[0];
    var worksheet = workbook.Sheets[sheetName];
    var jsonData = XLSX.utils.sheet_to_json(worksheet, {
      header: 1,
      raw: false
    });

    // Limpiar arrays
    if (tipo === "referencias") arrayRef = [];
    if (tipo === "inventario") arrayInv = [];
    if (tipo === "terceros") arrayTer = [];

    // Mapear filas
    jsonData.slice(1).forEach(function (row) {
      if (tipo === "referencias") {
        arrayRef.push({
          codigo: String(row[0] || ''),
          descripcionCorta: row[1] || '',
          descripcionLarga: row[2] || '',
          costoUnitario: row[3] || '',
          manejaLoteVmcto: row[4] || '',
          tipoLista: row[5] || '',
          precioRentabilidad: row[6] || '',
          tipo: row[7] || '',
          iva: row[8] || '',
          codigoBarraEAN: row[9] || '',
          und2: row[10] || '',
          can2: row[11] || '',
          und3: row[12] || '',
          can3: row[13] || '',
          und4: row[14] || '',
          can4: row[15] || ''
        });
      }
      if (tipo === "inventario") {
        if (row[3] == undefined) {
          row[3] = 'INICIAL';
        } else {
          row[3] = String(row[3]);
          if (row[3].length > 30) {
            row[3] = row[3].substring(0, 30);
          }
        }
        if (row[4] == undefined) {
          row[4] = '1900-01-01';
        } else {
          var fecha = new Date(row[4]);
          if (!isNaN(fecha.getTime())) {
            row[4] = fecha.toISOString().split('T')[0];
          } else {
            row[4] = '1900-01-01';
          }
        }
        arrayInv.push({
          codigo: String(row[0] || ''), // Primera columna: Código
          costo: parseFloat(row[1] || 0), // Segunda columna: Costo
          cantidad: parseInt(row[2] || 0), // Tercera columna: Cantidad
          lote: String(row[3] || 'INICIAL'), // Cuarta columna: Lote
          vmcto: String(row[4] || '1900-01-01'), // Quinta columna: Vmcto
        });
      }
      if (tipo === "terceros") {
        arrayTer.push({
          tipoTercero: row[0] || "",
          primerNombre: row[1] || "",
          segundoNombre: row[2] || "",
          primerApellido: row[3] || "",
          segundoApellido: row[4] || "",
          razonComercial: row[5] || "",
          tipoIdentificacion: row[6] || "",
          NIT: row[7] || "",
          digitoVerificacion: row[8] || "",
          perfilTributario: row[9] || "",
          regimen: row[10] || "",
          emailFe: row[11] || "",
          pais: row[12] || "",
          departamento: row[13] || "",
          ciudad: row[14] || "",
          direccion: row[15] || "",
          telefono: row[16] || "",
          celular: row[17] || "",
          email: row[18] || "",
          cupo: row[19] || 0
        });
      }
    });

    dissminSwal();

    // Construir tabla
    let headers = [];
    let dataPreview = [];

    if (tipo === "referencias") {
      headers = ["Código", "Descripción Corta", "Descripción Larga", "Costo", "Lote/Vmcto", "Tipo Lista", "Precio Rentabilidad", "Tipo", "IVA", "EAN"];
      dataPreview = arrayRef.map(r => [r.codigo, r.descripcionCorta, r.descripcionLarga, r.costoUnitario, r.manejaLoteVmcto, r.tipoLista, r.precioRentabilidad, r.tipo, r.iva, r.codigoBarraEAN]);
    }
    if (tipo === "inventario") {
      headers = ["Código", "Costo", "Cantidad", "Lote", "Vmcto"];
      dataPreview = arrayInv.map(r => [r.codigo, r.costo, r.cantidad, r.lote, r.vmcto]);
    }
    if (tipo === "terceros") {
      headers = ["Tipo", "Primer Nombre", "Segundo Nombre", "Primer Apellido", "Segundo Apellido", "Razón Comercial", "Tipo ID", "NIT", "DV", "Perfil Tributario", "Régimen", "Email FE", "País", "Dpto", "Ciudad", "Dirección", "Teléfono", "Celular", "Email", "Cupo"];
      dataPreview = arrayTer.map(r => [r.tipoTercero, r.primerNombre, r.segundoNombre, r.primerApellido, r.segundoApellido, r.razonComercial, r.tipoIdentificacion, r.NIT, r.digitoVerificacion, r.perfilTributario, r.regimen, r.emailFe, r.pais, r.departamento, r.ciudad, r.direccion, r.telefono, r.celular, r.email, r.cupo]);
    }

    let table = `
      <div class="table-responsive" style="max-height:400px; overflow:auto;">
        <table class="table table-bordered table-sm text-center align-middle">
          <thead class="table-primary sticky-top">
            <tr>${headers.map(h => `<th>${h}</th>`).join("")}</tr>
          </thead>
          <tbody>
            ${dataPreview.slice(0, 50).map(row => `
              <tr>${row.map(col => `<td>${col || "-"}</td>`).join("")}</tr>
            `).join("")}
          </tbody>
        </table>
      </div>
      ${dataPreview.length > 50 ? `<p class="text-muted mt-2">⚠️ Mostrando solo los primeros 50 registros de ${dataPreview.length}.</p>` : ""}
    `;

    // Insertar en modal
    $("#previewContent").html(`
      <p>Los datos de <b>${tipo}</b> se importaron correctamente.</p>
      ${table}
    `);

    // Mostrar modal
    $("#modalPreview").modal('show');
  };

  reader.readAsArrayBuffer(file);
};


const tipoIdentificacion = async () => {
  try {
    const data = {
      link: '../models/tipoIdentificaciones.php',
      filtro: '',
      op: 'listar'
    };

    var resp = await enviarPeticion(data);
    let option = `<option value="">Seleccionar</option>`;
    resp.data.forEach(function (d) {
      option += `<option value="${d.id}">${d.tipo_identificacion.toUpperCase()} - ${d.descripcion.toUpperCase()}</option>`;
    });
    $("#id_tipo_identificacion").html(option);
  } catch (e) {
    console.log(e);
  }
}

function showStep(step) {
  $(".step").addClass("hidden");
  $("#step-" + step).removeClass("hidden");

  $(".stepwizard-step .btn")
    .removeClass("btn-primary")
    .addClass("btn-secondary");
  $(".stepwizard-step .btn").each(function (index) {
    if (index + 1 <= step) {
      $(this).removeClass("btn-secondary").addClass("btn-primary");
    }
  });
}

function collectData(step) {
  switch (step) {
    case 1:
      wizardData.empresa = $("#form-empresa").serializeArray()
        .reduce((obj, item) => (obj[item.name] = item.value, obj), {});
      break;

    case 2:
      wizardData.representante = $("#form-representante").serializeArray()
        .reduce((obj, item) => (obj[item.name] = item.value, obj), {});
      break;

    case 3:
      // Guardar datos del array de referencias ya cargado
      wizardData.referencias = arrayRef.length > 0 ? [...arrayRef] : [];
      break;

    case 4:
      // Guardar datos del array de inventario ya cargado
      wizardData.inventario = arrayInv.length > 0 ? [...arrayInv] : [];
      break;

    case 5:
      // Guardar datos del array de terceros ya cargado
      wizardData.terceros = arrayTer.length > 0 ? [...arrayTer] : [];
      break;
  }
}


async function validateStep(step) {
  let form = $("#step-" + step + " form")[0];
  if (form) {
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return false;
    }
  }

  // Validación especial pasos 3 a 5 con arrays
  if (step === 3 && arrayRef.length === 0) {
    return Swal.fire({
      title: "¿Deseas continuar sin cargar referencias?",
      text: "Puedes importarlas más adelante.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, continuar",
      cancelButtonText: "No, volver",
      confirmButtonColor: "#0d6efd",
      cancelButtonColor: "#6c757d"
    }).then((r) => r.isConfirmed);
  }

  if (step === 4 && arrayInv.length === 0) {
    return Swal.fire({
      title: "¿Deseas continuar sin cargar inventario?",
      text: "Puedes importarlo más adelante.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, continuar",
      cancelButtonText: "No, volver",
      confirmButtonColor: "#0d6efd",
      cancelButtonColor: "#6c757d"
    }).then((r) => r.isConfirmed);
  }

  if (step === 5 && arrayTer.length === 0) {
    return Swal.fire({
      title: "¿Deseas continuar sin cargar terceros?",
      text: "Puedes importarlos más adelante.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, continuar",
      cancelButtonText: "No, volver",
      confirmButtonColor: "#0d6efd",
      cancelButtonColor: "#6c757d"
    }).then((r) => r.isConfirmed);
  }
  return true;
}
// --- Función auxiliar para actualizar progreso ---
const actualizarProgreso = (percent, mensaje) => {
  const bar = document.getElementById("progress-bar");
  const text = document.getElementById("progress-text");
  if (bar) {
    bar.style.width = percent + "%";
    bar.innerText = percent + "%";
  }
  if (text) text.innerText = mensaje;
};

async function enviarDatos() {
  let respReferencias = {
    datos: {
      nuevos: [],
      existentes: [],
      errores: []
    }
  };
  let respInventario = {
    datos_validos: 0,
    det_insert: 0,
    errores: 0
  };
  let respTerceros = {
    datos: {
      nuevos: [],
      existentes: [],
      errores: []
    }
  };

  const swalConfig = {
    showClass: {
      popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
      popup: 'animate__animated animate__fadeOutUp'
    },
    confirmButtonColor: "#0d6efd",
    cancelButtonColor: "#6c757d"
  };

  const result = await Swal.fire({
    ...swalConfig,
    title: "¿Confirmar envío?",
    text: "Se guardará la configuración inicial con los datos diligenciados.",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Sí, guardar",
    cancelButtonText: "Cancelar"
  });

  if (!result.isConfirmed) return;

  try {
    // ✅ Mostrar barra de progreso inicial
    Swal.fire({
      ...swalConfig,
      title: "Procesando...",
      html: `
    <div class="progress" style="height: 20px;">
      <div id="progress-bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated" 
           role="progressbar" style="width: 0%">0%</div>
    </div>
    <p id="progress-text" class="mt-2">Iniciando...</p>
  `,
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => {
        const text = document.getElementById("progress-text");
        if (text) text.innerText = "Preparando proceso...";
      }
    });


    // --- Validación empresa ---
    if (!wizardData.empresa.nit || !wizardData.empresa.razon_comercial) {
      return Swal.fire({
        ...swalConfig,
        icon: "warning",
        title: "⚠️ Atención",
        text: "Debes diligenciar los datos de la empresa."
      });
    }
    actualizarProgreso(10, "Guardando empresa...");
    wizardData.empresa.op = 'g_empresa';
    wizardData.empresa.link = '../models/Empresas.php';
    wizardData.empresa.correo_fe = wizardData.empresa.correo;
    wizardData.empresa.representante_legal = [wizardData.representante.primer_nombre, wizardData.representante.segundo_nombre, wizardData.representante.primer_apellido, wizardData.representante.segundo_apellido].filter(Boolean).join(" ");
    const resp1 = await enviarFormData(wizardData.empresa);
    if (resp1.error) throw new Error("Error al guardar empresa: " + resp1.message);

    // --- Representante ---
    if (!wizardData.representante.primer_nombre || !wizardData.representante.primer_apellido) {
      return Swal.fire({
        ...swalConfig,
        icon: "warning",
        title: "⚠️ Atención",
        text: "Debes diligenciar el representante legal."
      });
    }
    actualizarProgreso(30, "Guardando representante legal...");
    let arrayDirecciones = [{
      id_reg: Date.now(),
      pais: wizardData.empresa.codigo_pais,
      dpto: wizardData.empresa.codigo_dpto,
      mpio: wizardData.empresa.codigo_mpio,
      dir: wizardData.empresa.direccion,
      tel: wizardData.empresa.telefono,
      cel: wizardData.empresa.telefono,
      mail: wizardData.empresa.correo,
      id: 0,
      eliminar: '',
      principal: 1
    }];
    Object.assign(wizardData.representante, {
      op: 'g_tercero',
      link: '../models/Terceros.php',
      direcciones: JSON.stringify(arrayDirecciones),
      contactos: [],
      tratamiento: [wizardData.representante.primer_nombre, wizardData.representante.segundo_nombre, wizardData.representante.primer_apellido, wizardData.representante.segundo_apellido].filter(Boolean).join(" "),
      nit: wizardData.representante.nit_rl,
      nit_digito: wizardData.representante.nit_digito_rl,
      razon_comercial: wizardData.empresa.razon_comercial,
      email_fe: wizardData.empresa.correo
    });
    delete wizardData.representante.representante_legal;
    delete wizardData.representante.nit_rl;
    delete wizardData.representante.nit_digito_rl;

    const resp2 = await enviarFormData(wizardData.representante);
    if (resp2.error) throw new Error("Error al guardar representante legal: " + resp2.message);

    // --- Referencias ---
    if (arrayRef.length > 0) {
      actualizarProgreso(50, "Cargando referencias...");
      respReferencias = await enviarPeticion({
        link: '../models/Referencias.php',
        op: 'cargar_importados',
        datos: JSON.stringify(arrayRef)
      });
      if (respReferencias.error) throw new Error("Error al guardar referencias: " + respReferencias.message);
    }

    // --- Inventario ---
    if (arrayInv.length > 0 && arrayRef.length > 0) {
      actualizarProgreso(70, "Cargando inventario...");
      respInventario = await enviarPeticion({
        link: '../models/Entradas.php',
        op: 'cargar_importados',
        id_tercero: 1,
        id_bodega: 1,
        id_tercero_dir: 1,
        id_condicion: 1,
        id_tipo_documento: 1,
        notas: 'Entrada de mercancía inicial',
        datos: JSON.stringify(arrayInv)
      });
      if (respInventario.error) {
        return Swal.fire({
          ...swalConfig,
          icon: "error",
          title: "❌ Error",
          text: "No se pudo cargar el inventario: " + respInventario.message
        });
      }
      await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'asignar_lotes',
        id_transaccion: 1
      });
      await enviarPeticion({
        link: '../shared/servicios/servicios.php',
        op: 'volcar_documento',
        id_transaccion: 1
      });
    }

    // --- Terceros ---
    if (arrayTer.length > 0) {
      console.log(arrayTer);
      actualizarProgreso(90, "Cargando terceros...");
      respTerceros = await enviarPeticion({
        link: '../models/Terceros.php',
        op: 'cargar_importados',
        datos: JSON.stringify(arrayTer)
      });
      if (respTerceros.error) throw new Error("Error al guardar terceros: " + respTerceros.message);
    }

    // --- Finalización ---
    actualizarProgreso(100, "Finalizando proceso...");
    await new Promise(res => setTimeout(res, 500)); // pequeña pausa para ver el 100%


    // --- Construcción del resumen ---
    let resumen = `
  <h5 class="mb-3">📊 Resumen de carga</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-sm text-center align-middle">
      <thead class="table-primary">
        <tr>
          <th>Módulo</th>
          <th>Nuevos</th>
          <th>Errores</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><b>Referencias</b></td>
          <td>${respReferencias.datos.nuevos.length}</td>
          <td>${respReferencias.datos.errores.length}</td>
        </tr>
        <tr>
          <td><b>Inventario</b></td>
          <td>${respInventario.det_insert.length || 0}</td>
          <td>${respInventario.errores.length || 0}</td>
        </tr>
        <tr>
          <td><b>Terceros</b></td>
          <td>${respTerceros.datos.nuevos.length}</td>
          <td>${respTerceros.datos.errores.length}</td>
        </tr>
      </tbody>
    </table>
  </div>
`;


    // Cerrar el modal de progreso y mostrar resumen
    Swal.fire({
      icon: "success",
      title: "✅ Proceso finalizado",
      html: resumen,
      confirmButtonText: "Aceptar"
    }).then(() => {
      parent.location.reload(); // 🔄 recarga la página al confirmar
    });

  } catch (error) {
    Swal.fire({
      ...swalConfig,
      icon: "error",
      title: "❌ Error",
      text: error.message || "Ocurrió un error en el proceso, comuníquese con soporte técnico."
    });
    console.error(error);
  }
}


/*
@descargarExcel
*/
const descargarExcel = (url, nombre = `Archivo.xlsx`) => {
  var a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

$(async function () {
  await tipoIdentificacion();
  await paises('codigo_pais');
  let pais = $("#codigo_pais").val();
  await departamentos(pais, 'codigo_dpto');
  let dpto = $("#codigo_dpto").val();
  await municipios(pais, dpto, 'codigo_mpio');
  //hide loading
  dissminSwal();

  $(".next").click(async function () {
    let valid = await validateStep(currentStep);
    if (!valid) return;

    collectData(currentStep);
    if (currentStep < totalSteps) {
      currentStep++;
      showStep(currentStep);
    }
  });


  $(".prev").click(function () {
    if (currentStep > 1) {
      currentStep--;
      showStep(currentStep);
    }
  });

  $("#step-5 form").on("submit", async function (e) {
    e.preventDefault();

    let valid = await validateStep(currentStep); // esperar confirmación
    if (!valid) return;

    collectData(currentStep);
    enviarDatos();
  });


  showStep(currentStep);

  $("#nit").focusout(function () {
    const valor = $(this).val();
    if (valor.trim() !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nit_digito").val(dv);
    }
  });
  $("#nit_rl").focusout(function () {
    const valor = $(this).val();
    if (valor.trim() !== '') {
      const dv = calcularDigitoVerificacion(valor);
      $("#nit_digito_rl").val(dv);
    }
  });


  $('#txtFileUp-referencia').on('change', async function (event) {
    var file = event.target.files[0];
    if (file) {
      cargarExcel(file, 'referencias');
    }
  });
  $('#txtFileUp-inventario').on('change', async function (event) {
    var file = event.target.files[0];
    if (file) {
      cargarExcel(file, 'inventario');
    }
  });
  $('#txtFileUp-terceros').on('change', async function (event) {
    var file = event.target.files[0];
    if (file) {
      cargarExcel(file, 'terceros');
    }
  });

  $("#btnClean-referencia").click(function () {
    $('#txtFileUp-referencia').val('');
    arrayRef = [];
  });

  $("#btnClean-inventario").click(function () {
    $('#txtFileUp-inventario').val('');
    arrayInv = [];
  });

  $("#btnClean-terceros").click(function () {
    $('#txtFileUp-terceros').val('');
    arrayTer = [];
  });

  $('#btn-plantilla-ter').click(function () {
    descargarExcel('https://thecyctechnology.com/cyc/plantillas-demo/plantilla-terceros.xlsx', 'plantilla-terceros.xlsx');
  });
  $('#btn-plantilla-inv').click(function () {
    descargarExcel('https://thecyctechnology.com/cyc/plantillas-demo/plantilla-inventario.xlsx', 'plantilla-inventario.xlsx');
  });
  $('#btn-plantilla-ref').click(function () {
    descargarExcel('https://thecyctechnology.com/cyc/plantillas-demo/plantilla-referencias.xlsx', 'plantilla-referencias.xlsx');
  });
  dissminSwal();

});
