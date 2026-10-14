let link = "../models/Despachos.php";
const listarTrasportadores = async () => {
  let tipo = $("#SlcTipoT").val();
  try {
    const data = {
      link,
      op: "s_transportadores",
      tipo
    }
    const resp = await enviarPeticion(data);
    if (resp.data.length > 0) {
      resp.data.forEach((d) => {
        $('#SlcTransportador').append(`<option value="${d.id}">${d.nombres} - ${d.razon_social}</option>`);
      })
    } else {
      $('#SlcTransportador').html('');
    }
  } catch (e) {
    console.log(e);
  }
}
const listarTiposFacturas = async () => {
  try {
    const data = {
      link,
      op: "s_tipos_facturas"
    }
    const resp = await enviarPeticion(data);
    if (resp.data.length > 0) {
      resp.data.forEach((d) => {
        $('#slcTipoFactura').append(`<option value="${d.tipo}">${d.tipo} - ${d.descripcion}</option>`);
      })
    } else {
      $('#slcTipoFactura').html('');
    }
  } catch (e) {
    console.log(e);
  }
}
const limpiar = async () => {
  $('#nCajas').empty();
  for (let i = 1; i <= 20; i++) {
    $('#nCajas').append(`<option value="${i}">${i}</option>`);
  }
  $("#nFlete").val(0).prop('disabled', true);
  $("#nGuia").val(0).prop('disabled', true);
  $("#nota").val('').prop('disabled', true);
  $('#nCajas').prop('disabled', true);
  $("#btnAdd").prop('disabled', true);
  $("#idFactura").val(0);

  $("#nFactura").val('').prop('disabled', false);
  $("#SlcTipoT").prop('disabled', false);
  $("#SlcTransportador").prop('disabled', false);
  $("#slcTipoFactura").prop('disabled', false);
  $("#nFactura").focus();

  const today = new Date().toISOString().split("T")[0];
  $("#fhIni").val(today);
  $("#fhFin").val(today);
}

const validarGrid = (tipo, numero) => {
  let sw = false;
  $("#tdPlanillaDetalle tr").each(function () {
    const tipo_fact = $(this).find('td').eq(0).html();
    const num_fact = $(this).find('td').eq(1).html();
    if (num_fact == numero && tipo_fact == tipo) {
      sw = true;
      return false; // Rompe el bucle .each()
    }
  });
  return sw;
};

const addFactura = async () => {
  let id = $("#idFactura").val();
  let flete = $("#nFlete").val().trim();
  let guia = $("#nGuia").val();
  let nota = $("#nota").val();
  let cajas = $('#nCajas').val();
  let transportador = $("#SlcTransportador").val();

  if (id > 0) {
    if (guia === '') {
      Swal.fire('Campo guia vacío!', 'Este campo no puede estar vacio.', 'warning');
      return;
    }
    if (flete === '') {
      Swal.fire('Campo flete vacío!', 'Este campo no puede estar vacio.', 'warning');
      return;
    }

    if (transportador == '' || transportador == null) {
      Swal.fire('Datos incompletos', 'Debe seleccionar un transportador.', 'info');
      return;
    }

    try {
      const data = {
        link,
        op: "s_factura",
        id
      }
      const resp = await enviarPeticion(data);
      if (resp.data.length > 0) {
        const datos = resp.data[0];
        if (!validarGrid(datos.tipo, datos.numero)) {
          let tr = `<tr>
						<td>${datos.tipo}</td>
						<td>${datos.numero}</td>
						<td>${datos.nit}</td>
						<td>${datos.nombres}</td>
						<td>${datos.razon_comercial}</td>
						<td>${datos.dpto}</td>
						<td>${datos.ciudad}</td>
						<td>${cajas}</td>
						<td>${formatNum(flete, '$')}</td>
						<td>${guia}</td>
						<td>${nota}</td>
						<td align="center"><button type="button" class="btn btn-sm btn-outline-danger" onClick="eliminarFila(this)"><i class="fa-solid fa-trash-can"></i></button></td>
				    </tr>`;
          $("#tdPlanillaDetalle").append(tr);
        } else {
          Swal.fire('Oops', 'La factura ya ha sido ingresada.', 'info');
        }
        $("#nFactura").val('');
        $("#nFactura").prop('disabled', false);
        $("#slcTipoFactura").prop('disabled', false);
        $("#nFactura").focus();
        $("#idFactura").val(0);
        $('#nCajas').empty();
        for (let i = 1; i <= 20; i++) {
          $('#nCajas').append(`<option value="${i}">${i}</option>`);
        }
        $("#nFlete").val(0).prop('disabled', true);
        $("#nGuia").val(0).prop('disabled', true);
        $("#nota").val('').prop('disabled', true);
        $('#nCajas').prop('disabled', true);
        $("#btnAdd").prop('disabled', true);
      }

    } catch (e) {
      console.log(e);
    }
  } else {
    Swal.fire('Nada que agregar', 'No ha cargado una factura.', 'info');
  }
}

const eliminarFila = (ob) => {
  Swal.fire({
    title: "Eliminar factura",
    text: "Esta seguro de eliminar esta factura?",
    icon: "question",
    html: '',
    showCancelButton: true,
    confirmButtonColor: "#82ED81",
    cancelButtonColor: "#FFA3A4",
    confirmButtonText: "Eliminar!",
    cancelButtonText: "Cancelar",
    closeOnConfirm: false,
    closeOnCancel: false
  }).then((result) => {
    if (result.value) {
      $(ob).parent().parent().remove();
    } else {
      Swal.fire("Cancelado", "Operacion cancelada!", "error");
    }
  })

}
const validarFactura = async () => {
  let tipo = $("#slcTipoFactura").val();
  let factura = $("#nFactura").val();
  if (factura != '') {
    try {
      const data = {
        link,
        op: "validar_factura",
        factura,
        tipo
      }
      const resp = await enviarPeticion(data);
      //40864
      if (resp.data.length > 0) {
        if (resp.data[0].id_despacho != 0) { //Se valida que no cuente con registro de despacho.
          Swal.fire('Imposible agregar!', 'La factura ya posee un registro de despacho.', 'warning');
          return;
        }
        $("#idFactura").val(resp.data[0].id);
        $("#nFlete").prop('disabled', false);
        $("#nGuia").prop('disabled', false);
        $("#nota").prop('disabled', false);
        $('#nCajas').prop('disabled', false);
        $("#btnAdd").prop('disabled', false);
        //se desactivan los campos 
        $("#nFactura").prop('disabled', true);
        $("#SlcTipoT").prop('disabled', true);
        $("#SlcTransportador").prop('disabled', true);
        $("#slcTipoFactura").prop('disabled', true);

      } else {
        Swal.fire('Sin resultados', 'La factura no existe o no coincide con el tipo seleccionado, verifique e intente nuevamente.', 'info');
      }
    } catch (e) {
      console.log(e);
    }
  } else {
    limpiar();
  }

}
const guardar = async () => {
  let datos = [];
  let transportador = $("#SlcTransportador").val();
  if (transportador == '') {
    Swal.fire('Datos incompletos', 'Debe seleccionar un transportador.', 'info');
    return;
  }

  $("#tdPlanillaDetalle tr").each(function () {
    const filaDatos = {
      tipo: $(this).find('td').eq(0).html(),
      numero: $(this).find('td').eq(1).html(),
      cajas: $(this).find('td').eq(7).html(),
      flete: unformatNum($(this).find('td').eq(8).html()),
      guia: $(this).find('td').eq(9).html(),
      nota: $(this).find('td').eq(10).html()
    };
    datos.push(filaDatos);
  });
  if (datos.length == 0) {
    Swal.fire('No hay nada que guardar', 'No hay facturas cargadas.', 'info');
    return;
  }
  try {



    Swal.fire({
      title: "Guardar despacho",
      text: "Esta seguro de guardar esta planilla, esta operación no se puede reversar?",
      icon: "question",
      html: '',
      showCancelButton: true,
      confirmButtonColor: "#82ED81",
      cancelButtonColor: "#FFA3A4",
      confirmButtonText: "Guardar!",
      cancelButtonText: "Cancelar",
      closeOnConfirm: false,
      closeOnCancel: false
    }).then(async (result) => {
      if (result.value) {
        console.log({
          link,
          op: "g_despacho",
          datos,
          transportador
        })
        const resp = await enviarPeticion({
          link,
          op: "g_despacho",
          datos: JSON.stringify(datos),
          transportador
        });
        console.log({ resp })
        if (resp.ok) {
          Swal.fire("Correcto!", "Planilla de despacho creada correctamente", "success");
          limpiar();
          $("#tdPlanillaDetalle").html('');
        } else {
          Swal.fire("Error", "Algo salio mal!", "error");
        }
      } else {
        Swal.fire("Cancelado", "Operacion cancelada!", "error");
      }
    })

  } catch (e) {
    console.log(e);
  }

};

const listarDespachos = async () => {
  let fhIni = $("#fhIni").val();
  let fhFin = $("#fhFin").val();
  try {
    const data = {
      link,
      op: "s_despacho",
      fhIni,
      fhFin
    }
    const resp = await enviarPeticion(data);
    if (resp.data.length > 0) {
      let tabla = ` <table class="table table-sm table-striped table-hover table-bordered table-responsive-md">
					   <thead class="cabecera table-primary">
                        <tr>
							<th>Id</th>
							<th>Fecha</th>
							<th>Usuario</th>
							<th>Nombres</th>
							<th>Razón</th>
							<th>Cajas</th>
							<th>Flete</th>
							<th>Facturas</th>
							<th>Despachadas</th>
							<th>Entregadas</th>
							<th>Planilla</th>
							<th>Gestionar</th>
                        </tr>
                      </thead>
                      <tbody>`;
      resp.data.forEach((d) => {
        tabla += `<tr>
					<td>${d.id}</td>
					<td>${d.fecha_hora}</td>
					<td>${d.usuario}</td>
					<td>${d.nombres}</td>
					<td>${d.razon_social}</td>
					<td>${d.cajas}</td>
					<td>${formatNum(d.flete, '$')}</td>
					<td>${d.nfacturas}</td>
					<td>${d.despachadas}</td>
					<td>${d.entregadas}</td>
					<td align="center">
						<button type="button" class="btn btn-sm btn-outline-primary" onClick="verPlanilla(${d.id})">
							<i class="fa-solid fa-list-check"></i>
						</button>
					</td>
					<td align="center">
						<button type="button" class="btn btn-sm btn-outline-success" onClick="gestionarPlanilla(${d.id})">
							<i class="fa-solid fa-clipboard-check"></i>
						</button>
					</td>
				  </tr>`;
      });
      tabla += `</tbody></table>`;
      $("#dvListarDespachos").html(tabla);
    } else {
      $("#dvListarDespachos").html(`<div class="alert alert-danger" role="alert">
					<i class="fa-solid fa-bell fa-shake"></i> No existen resultados para las condiciones seleccionadas
				   </div>`);
    }
  } catch (e) {
    console.log(e);
  }
}
const gestionarPlanilla = async (id) => {
  try {
    const data = {
      link,
      op: "s_despacho_det",
      id
    }
    const resp = await enviarPeticion(data);
    if (resp.data.length > 0) {
      let tabla = ` <table class="table table-sm table-striped table-hover table-bordered table-responsive-sm">
					   <thead class="cabecera table-primary">
                        <tr>
							<th>Id</th>
							<th>Tipo</th>
							<th>Numero</th>
							<th>Cajas</th>
							<th>Flete</th>
							<th>Guia</th>
							<th>Estado</th>
							<th>Eliminar</th>
							<th>Entregar</th>
                        </tr>
                      </thead>
                      <tbody>`;
      resp.data.forEach((d) => {
        let estado = '';
        let disable = '';
        if (d.estado == 'D') {
          estado = `<button type="button" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-d"></i></button>`;
        }
        if (d.estado == 'E') {
          disable = `disabled`
          estado = `<button type="button" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-e"></i></button>`;
        }

        tabla += `<tr>
					<td>${d.id}</td>
					<td>${d.tipo_fac}</td>
					<td>${d.numero_fac}</td>
					<td>${d.cajas}</td>
					<td>${formatNum(d.flete, '$')}</td>
					<td>${d.guia}</td>
					<td align="center">${estado}</td>
					<td align="center">
						<button type="button" class="btn btn-sm btn-outline-danger" onClick="eliminarFactura(${d.id},${id})" ${disable}>
							<i class="fa-solid fa-trash-can"></i>
						</button>
					</td>
					<td align="center">
						<button type="button" class="btn btn-sm btn-outline-warning" onClick="entregarFactura(${d.id},${id})" ${disable}>
							<i class="fa-solid fa-bolt-lightning"></i>
						</button>
					</td>
				</tr>`;

      });
      tabla += `</tbody></table>`;
      $("#mdPlanillaDetalleBody").html(tabla);

    } else {
      $("#mdPlanillaDetalleBody").html(`<div class="alert alert-danger" role="alert">
					<i class="fa-solid fa-bell fa-shake"></i> No existen resultados para las condiciones seleccionadas
				   </div>`);
    }
  } catch (e) {
    console.log(e);
  }


  $("#mdPlanillaDetalle").modal('show');
}

const eliminarFactura = async (id, id_planilla) => {
  try {
    const data = {
      link,
      op: "d_despacho_det",
      id
    }
    Swal.fire({
      title: "Eliminar factura",
      text: "Esta seguro de eliminar esta factura, esta operación no se puede reversar?",
      icon: "question",
      html: '',
      showCancelButton: true,
      confirmButtonText: "Eliminar!",
      cancelButtonText: "Cancelar",
      closeOnConfirm: false,
      closeOnCancel: false
    }).then(async (result) => {
      if (result.value) {
        const resp = await enviarPeticion(data);
        if (resp.ok) {
          Swal.fire("Excelente", resp.mensaje, "success");
          listarDespachos();
          gestionarPlanilla(id_planilla);
        } else {
          Swal.fire("Error", resp.mensaje, "error");
        }
      }
    });
  } catch (e) {
    console.log(e);
  }
}
const entregarFactura = async (id, id_planilla) => {
  try {
    const data = {
      link,
      op: "u_despacho_det",
      id
    }
    Swal.fire({
      title: "Entregar factura",
      text: "Esta seguro de entregar esta factura, esta operación no se puede reversar?",
      icon: "question",
      html: '',
      showCancelButton: true,
      confirmButtonText: "Guardar!",
      cancelButtonText: "Cancelar",
      closeOnConfirm: false,
      closeOnCancel: false
    }).then(async (result) => {
      if (result.value) {
        const resp = await enviarPeticion(data);
        if (resp.ok) {
          Swal.fire("Excelente", resp.mensaje, "success");
          listarDespachos();
          gestionarPlanilla(id_planilla);
        } else {
          Swal.fire("Error", resp.mensaje, "error");
        }

      }
    });
  } catch (e) {
    console.log(e);
  }
}


const verPlanilla = (id) => {
  const iframeSrc = `../pdf/planilla-despachos.php?id=${id}`;
  $("#mdPlanillaBody").html(`
    <iframe width="100%" style="height:400px;" src="${iframeSrc}" frameborder="0"></iframe>
  `);

  $("#mdPlanilla").modal('show');
}


$(async function () {
  await limpiar();
  await listarTrasportadores();
  await listarTiposFacturas();
  $("#SlcTipoT").change(() => {
    listarTrasportadores();
  });
  $("#btnAdd").click(() => {
    addFactura();
  });
  $("#btnLimpiar").click(() => {
    limpiar();
  });
  $("#btnNuevo").click(() => {
    limpiar();
    $("#tdPlanillaDetalle").html('');
  });
  $("#btnGuardar").click(() => {
    guardar();
  });
  $("#nFactura").keydown((event) => {
    if (event.key === "Enter") {
      validarFactura();
    }
  });
  $("#btnConsultarDespachos").click(() => {
    listarDespachos();
  });
  dissminSwal();
})
