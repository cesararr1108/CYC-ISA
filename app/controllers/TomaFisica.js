const link_ = '../models/TomaFisica.php';
const id_modulo = '63';
let permisos = [];
let conteo;
let filaSeleccionadaEnGrid = null;
let preAjuste = [];

const terceroEmpresa = async () => {
  try {
    const resp = await enviarPeticion({
      link: link_,
      op: 'tercero'
    });

    if (resp.length == 0) {
      return;
    }
    item = resp[0]

    $("#tercero").val(item.razon_comercial);
    $("#id_tercero").val(item.id);
    $("#direccion").val(item.direccion);
    $("#id_direccion").val(item.id_direccion);
  } catch (error) {
    console.error(error)
  }
}
const listarMarcas = () => {
  enviarPeticion({
    link: '../models/Marcas.php',
    op: 'listar',
    filtro: ''
  }).then(resp => {
    if (resp.data.lenght == 0) {
      return;
    }
    let p = ``
    resp.data.forEach(item => {
      p += `<option value="${item.id}">${item.descripcion}</option>`
    });

    $("#marcas").html(p).select2();
  })
    .catch(e => {
      console.error(e)
    });
}
const validarConteoUsuario = async () => {
  try {

    const validarUsuario = await enviarPeticion({
      link: link_,
      op: 'validar-usuario'
    });
    if (validarUsuario.length == 0) {
      Swal.fire("Oops", "  El usuario debe estar registrado en las tablas de conteo", "error");
      return 0;
    }
    return validarUsuario.length > 0 ? validarUsuario[0].conteo : 0;
  } catch (error) {
    console.error(error)
    return 0;
  }
}
const lotes = async (id_referencia) => {
  try {
    const resp = await enviarPeticion({
      link: link_,
      op: 'cargar-lotes',
      id_referencia: id_referencia
    })
    return resp;
  } catch (error) {
    console.error(error)
    return [];
  }
}
const buscarProductos = async (data) => {
  showLoadingWithTetrisLogo('Guardando documento...', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: link_,
      op: 'busqueda-referencia',
      filtro: data.filtro,
      bodega: data.bodega,
      conteo: data.conteo
    })
    if (resp.length == 0) {
      dissminSwal();
      Swal.fire("Oops", "  No se encontraron productos", "error");
      return;
    }

    if (resp.length > 1) {
      //abro modal para seleccionar producto
      $("#listado-productos").modal("show")
      tab = `<table class="table table-sm">
              <thead>
                <tr>
                  <th>Codigo</th>
                  <th>Descripción</th>
                  <th>Marca</th>
                </tr>
              </thead>
              <tbody>`
      resp.forEach(element => {
        tab += `<tr data-element ='${JSON.stringify(element)}'>
                      <td>${element.codigo}</td>
                      <td>${element.descripcion}</td>
                      <td>${element.marca}</td>
                    </tr>`
      });
      tab += `</tbody>
              </table>`
      $("#result-referencias").html(tab)
      dissminSwal();
      $("#result-referencias tr").on('click', async function () {
        const data = $(this).data('element');

        if (data.marcado == "0") {
          Swal.fire("Oops", "  El producto no ha sido marcado para toma fisica", "error");
          return;
        }
        $("#listado-productos").modal("hide")
        $("#descripcion").val(data.descripcion)
        $("#busqueda").val(data.codigo)
        $("#conteo").val(data.conteo)
        $("#id_referencia").val(data.id)
        const listadoLote = await lotes(data.id);


        let p = ``
        if (listadoLote.length > 0) {
          p += `<option value ="">Seleccionar</option>`
          listadoLote.forEach(element => {
            p += `<option value ="${element.id}" data-element='${JSON.stringify(element)}'>${element.lote} -  ${element.vencimiento}</option>`
          });
        }
        $("#lotes").html(p)
        dissminSwal();

      })
    } else {
      const data = resp[0];
      if (data.marcado == "0") {
        Swal.fire("Oops", "  El producto no ha sido marcado para toma fisica", "error");
        return;
      }
      $("#descripcion").val(data.descripcion)
      $("#busqueda").val(data.codigo)
      $("#conteo").val(data.conteo)
      $("#id_referencia").val(data.id)
      const listadoLote = await lotes(data.id);


      let p = ``
      if (listadoLote.length > 0) {
        p += `<option value ="">Seleccionar</option>`
        listadoLote.forEach(element => {
          p += `<option value ="${element.id}" data-element='${JSON.stringify(element)}'>${element.lote} -  ${element.vencimiento}</option>`
        });
      }
      $("#lotes").html(p)
      dissminSwal();
    }
  } catch (error) {
    dissminSwal();
    Swal.fire("Error", "Error al buscar productos", "error")
    console.error(error)
  }
}
const limpiarBusqueda = () => {
  $("#busqueda").val("")
  $("#descripcion").val("")
  $("#lotes").html(`<option selected>Seleccione un lote</option>`)
  $("#cantidad").val("")
  $("#conteo").val("")
  $("#id_referencia").val("")
  $("#busqueda").focus()
}
const usuarios = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/usuarios.php',
      filtro: '',
      op: 's_usuario_all',
      estado: 'A',
    })

    let p = `<option value="">Todos</option>`;

    resp.data.forEach(element => {
      p += `<option value="${element.id}">${element.usuario} ( ${element.nombres} )</option>`
    });
    $("#select_usuario").html(p).select2()
    //$('#seleccionar-totos').trigger("click")
    console.log({ resp })
  } catch (error) {
    console.error(error)
  }
}
const nuevo = async () => {
  limpiarBusqueda();
  $("#result-info").html("");
  $("#ajuste_salidas").text('')
  $("#ajuste_entradas").text('')
  $("#notas").val('')
  $("#result-pre-ajuste").html('')
  //reseto select_usuario
  $("#select_usuario").val("").trigger("change");
  $("#select_conteo").val("").trigger("change");
  conteo = await validarConteoUsuario();
  infoConteo()
}
const infoConteo = () => {
  desconteo = 'Sin asignar'
  switch (parseInt(conteo)) {
    case 1: cellToma1 = false; desconteo = 'Conteo 1'; break;
    case 2: cellToma2 = false; desconteo = 'Conteo 2'; break;
    case 3: cellToma3 = false; desconteo = 'Conteo 3'; break;
  }
  $(".info-conteo").text(desconteo)
}

const listarUbicaciones = async () => {
  try {
     const resp = await enviarPeticion({
      link: '../models/Referencias.php',
      op: 'listar-ubicaciones',

     })
      console.log({ resp })
     if (resp.length > 0) {
       // Procesar las ubicaciones
     }
     resp.forEach(item => {
        $("#ubicaciones").append(`<option value="${item.id}">${item.ubicacion}</option>`);
        
      });
      $("#ubicaciones").select2();

  } catch (error) {
    
  }
}

$(async function () {
  tiposDocumentos(2, $("#id_bodega_session").val(), 'tipo_doc_entrada');
  tiposDocumentos(6, $("#id_bodega_session").val(), 'tipo_doc_salida');
  condicionesPago('', 'id_condicion');
  terceroEmpresa()
  usuarios();
  listarUbicaciones()
  const { general, modulos } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];
  conteo = await validarConteoUsuario();
  infoConteo();

  permisos = await permisosUsuario(id_modulo);

  if (permisos.RealizarMarcadoProductosInventarios == 1) {
    $("#marcar-productos").show()
  }

  if (permisos.RealizarAjusteInventarios == 1 || permisos?.RealizarAjusteInventarios) {
    $("#li_ajuste").show()
  }
  if (permisos.VerReporteSegInventario == 1 || permisos?.VerReporteSegInventario) {
    $("#li_informe").show()
  }
  if (permisos.EliminarConteo == 1 || permisos?.EliminarConteo) {
    $("#BtnEliminarConteo").show()
  }

  if (conteo == 0) {
    $("body").html(`<div class="d-flex justify-content-center align-items-center" style="height: 100vh;"><button class="btn btn-primary" onclick="location.reload()"><i class="fa fa-refresh"></i> Recargar</button></div>`).show()
    Swal.fire("Oops", "El usuario debe estar registrado en las tablas de conteo", "error");
    return;
  }
  $("#body").show()

  listarMarcas()
  $("#lotes").select2();

  $("#desmarcar-referencias").click(async function () {
    const result = await Swal.fire({
      title: '¿Estás seguro de desmarcar todas las referencias?',
      text: "Esta acción eliminará las referencias marcadas de esta bodega y sus conteos asociados.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });

    if (!result.isConfirmed) {
      return;
    }

    try {

      const resp = await enviarPeticion({
        link: '../models/TomaFisica.php',
        op: 'desmarcar-referencias',
        bodega: $("#id_bodega_session").val()
      });

      if (resp.ok) {
        await Swal.fire({
          icon: 'success',
          title: 'Referencias desmarcadas',
          text: 'Las referencias fueron desmarcadas correctamente',
          timer: 1500,
          showConfirmButton: false
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: resp.message || 'No se pudo desmarcar las referencias'
        });
      }

    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Error inesperado',
        text: error.message
      });
    }
  });

  $("#BtnEliminarConteo").on('click', async function () {

    if ($("#select_conteo").val() == "") {
      Swal.fire("Oops", "  Debe seleccionar un conteo", "error");
      return;
    }

    if ($("#navs-informe").html() == '') {
      Swal.fire("Oops", "  Debe generar el informe antes de eliminar el conteo", "error");
      return;
    }

    const result = await Swal.fire({
      title: '¿Estás seguro?',
      text: "Esta acción eliminará el conteo actual.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    });

    if (!result.isConfirmed) {
      return;
    }

    try {

      const resp = await enviarPeticion({
        link: '../models/TomaFisica.php',
        op: 'eliminar-conteos',
        bodega: $("#id_bodega_session").val(),
        conteo: $("#select_conteo").val()
      });

      if (resp.ok) {
        await Swal.fire({
          icon: 'success',
          title: 'Eliminado',
          text: 'El conteo fue eliminado correctamente',
          timer: 1500,
          showConfirmButton: false
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: resp.message || 'No se pudo eliminar el conteo'
        });
      }

    } catch (error) {
      Swal.fire({
        icon: 'error',
        title: 'Error inesperado',
        text: error.message
      });
    }

  });

  $("#busqueda").on('keyup', function (e) {
    const data = {
      filtro: $(this).val(),
      bodega: $("#id_bodega_session").val(),
      conteo: conteo
    }

    if (data.filtro.length > 2) {
      if (e.key === 'Enter') {
        buscarProductos(data);
      }
    }
  })

  $("#marcar-productos").on('click', function () {
    $("#modalMarcarProductos").modal("show")
  })

  $("#filtro_referencias").change(function () {
    const tipo = $.trim($(this).val());
    console.log({ tipo })
    $(".card-filtros").hide()
    switch (tipo) {
      case 'Ubicacion':
        $(".card-ubicaciones").show()
        break;
      case 'Marca':
        $(".card-marcas").show()
        break;
      case 'Categorias':
        $(".card-categorias").show()
        break;
      case 'Todas':
        $(".card-todas").show()
        break;
    }
  });

  $("#marcar-referencias").click(async function () {

    if ($("#filtro_referencias").val() == '') {
      Swal.fire("Oops", "Debe seleccionar el tipo de marcado", "error")
      return;
    }
    // ✅ VALIDAR QUE SE HAYA SELECCIONADO AL MENOS UNA OPCIÓN SEGÚN EL TIPO DE MARCADO
    const tipoMarca = $("#filtro_referencias").val();
    let tieneSelecciones = false;
    let mensajeError = "";

    if (tipoMarca === "Ubicacion") {
      const ubicacionesSeleccionadas = $("#ubicaciones").val() || [];
      if (ubicacionesSeleccionadas.length === 0) {
        mensajeError = "Debe seleccionar al menos una ubicación para marcar por ubicación";
      } else {
        tieneSelecciones = true;
      }
    } else if (tipoMarca === "Marca") {
      const marcasSeleccionadas = $("#marcas").val() || [];
      if (marcasSeleccionadas.length === 0) {
        mensajeError = "Debe seleccionar al menos una marca para marcar por marca";
      } else {
        tieneSelecciones = true;
      }
    } else if (tipoMarca === "Categorias") {
      const cat1 = $("#slcCategoria1").val() || [];
      const cat2 = $("#slcCategoria2").val() || [];
      const cat3 = $("#slcCategoria3").val() || [];
      const cat4 = $("#slcCategoria4").val() || [];
      const totalCategorias = cat1.length + cat2.length + cat3.length + cat4.length;

      if (totalCategorias === 0) {
        mensajeError = "Debe seleccionar al menos una categoría para marcar por categoría";
      } else {
        tieneSelecciones = true;
      }
    }

    if (!tieneSelecciones) {
      Swal.fire("Atención", mensajeError, "warning");
      return;
    }

    // ✅ VALIDAR SI HAY CONTEO EN PROCESO
    showLoadingWithTetrisLogo('Marcando productos ...', 'ISA Software');

    try {
      const validacionConteo = await enviarPeticion({
        link: link_,
        op: 'validar-conteo-proceso'
      });
   console.log({ validacionConteo })
      if (!validacionConteo.ok && validacionConteo.en_proceso) {
        dissminSwal();
        Swal.fire({
          title: '⚠️ Conteo en Proceso',
          html: '<p style="text-align: left; font-size: 1rem;">' + validacionConteo.message + '</p>',
          icon: 'warning',
          confirmButtonText: 'Entendido'
        });
        return;
      }
    } catch (error) {
      dissminSwal();
      console.warn({ error });
      Swal.fire("Error", "Error al validar conteos en proceso", "error");
      return;
    }

    // ✅ CONTINUAR CON EL MARCADO DE REFERENCIAS
    showLoadingWithTetrisLogo('Marcando referencias', 'ISA Software');

    try {
      const marcado = await enviarPeticion({
        link: link_,
        op: 'marcar-ferencias',
        tipoMarca: $("#filtro_referencias").val(),

        marcas: JSON.stringify($("#marcas").val() || []),
        ubicaciones: JSON.stringify($("#ubicaciones").val() || []),

        categoria1: JSON.stringify($("#slcCategoria1").val() || []),
        categoria2: JSON.stringify($("#slcCategoria2").val() || []),
        categoria3: JSON.stringify($("#slcCategoria3").val() || []),
        categoria4: JSON.stringify($("#slcCategoria4").val() || []),
      });


      if (!marcado.ok) {
        dissminSwal();
        Swal.fire("Oops", "Error al marcar los productos", "error")
        return;
      }

      // Mostrar resumen de referencias marcadas
      const resumen = `
            <div style="text-align: left; padding: 20px;">
              <h5>📊 Resumen de Referencias Marcadas</h5>
              <hr>
              <p><strong>✓ Referencias lotes insertadas :</strong> <span style="color: green; font-size: 1.2em; font-weight: bold;">${marcado.referencias_insertadas}</span></p>
              <p><strong>⚠ Referencias que ya existían:</strong> <span style="color: orange; font-size: 1.2em; font-weight: bold;">${marcado.referencias_existentes}</span></p>
              <p><strong>📦Total de referencias procesadas:</strong> <span style="color: blue; font-size: 1.2em; font-weight: bold;">${marcado.referencias_totales}</span></p>
            </div>
          `;

      Swal.fire({
        title: '✅ Operación Completada',
        html: resumen,
        icon: 'success',
        confirmButtonText: 'Continuar'
      });

      $("#filtros").hide()
      document.getElementById('marcas').selectedIndex = -1;
      $("#ubicaciones").val(null).trigger('change');
      $("#slcCategoria1").val(null).trigger('change');
      $("#slcCategoria2").val(null).trigger('change');
      $("#slcCategoria3").val(null).trigger('change');
      $("#slcCategoria4").val(null).trigger('change');
      $("#filtro_referencias").val(null).trigger('change');
      $("#modalMarcarProductos").modal("hide")

    } catch (error) {
      Swal.fire("Oops", "Error al marcar los productos", "error")
      console.error({ error })
    }

    //  dissminSwal();
  })

  $("#limpiarBusqueda").on('click', function () {
    $("#codigo").val("")
    $("#descripcion").val("")
    $("#lotes").html(`<option selected>Seleccione un lote</option>`)
    $("#cantidad").val("")
    $("#conteo").val("")
    $("#id_referencia").val("")
    $("#codigo").focus()
  })

  $("#btn-guardar-conteo").on('click', async function () {

    const lote = $("#lotes").val();
    const cantidad = parseInt($("#cantidad").val());
    const id_referencia = $("#id_referencia").val();

    if (id_referencia == null || id_referencia == "") {
      Swal.fire("Oops", "Debe seleccionar una referencia", "error")
      return;
    }

    //debe seleccionar un lote 
    if (lote == null || lote == "") {
      Swal.fire("Oops", "Debe seleccionar un lote", "error")
      return;
    }
    //debe ingresar una cantidad
    if (isNaN(cantidad) || cantidad == 0) {
      Swal.fire("Oops", "Debe ingresar una cantidad valida", "error")
      return;
    }

    const data = {
      id_lote: lote,
      cantidad: cantidad,
      conteo: conteo,
      id_referencia: $("#id_referencia").val(),
      id_bodega: $("#id_bodega_session").val()
    }

    showLoadingWithTetrisLogo('Guardando conteo', 'ISA Software');
    try {
      const resp = await enviarPeticion({
        link: link_,
        op: 'guardar-linea',
        item: JSON.stringify(data)
      })

      if (!resp.ok) {
        dissminSwal()
        Swal.fire("Oops", "Error al guardar el conteo", "error")
        return;
      }
      dissminSwal()
      Swal.fire("Exito", "Conteo guardado correctamente", "success")
      limpiarBusqueda();

      setTimeout(() => {
        $("#codigo").focus()
      }, 500);
    } catch (error) {
      dissminSwal()
      Swal.fire("Oops", "Error al guardar el conteo", "error")
      console.error({ error })
    }

  });

  $("#btn-crear-lotes").on('click', function () {
    const id_ref = $("#id_referencia").val();
    if (id_ref == null || id_ref == "") {
      Swal.fire("Oops", "Debe seleccionar una referencia", "error")
      return;
    }
    $("#lote_lote").val('');
    $("#descripcion_referencia_lote").val($("#descripcion").val());
    $("#id_referencia_lote").val(id_ref);
    $("#modalCrearLote").modal("show")
  })

  $("#crear-lote").click(async function () {
    const id_referencia = $("#id_referencia_lote").val();
    let lote = $.trim($("#lote_lote").val());
    let vencimiento =/* '01-' + */$("#fecha_vencimiento_lote").val();
    //VALIDO LA FECHA yyyy-mm-dd
    const fechaValida = /^\d{4}-\d{2}-\d{2}$/.test(vencimiento);

    if (!fechaValida) {
      Swal.fire("Oops", "fecha no valida", "error")
      $("#fecha_vencimiento_lote").val("")
      return;
    }

    try {
      let fechaFormateada = vencimiento.split('-').reverse().join('-');
      showLoadingWithTetrisLogo('Creando lote', 'ISA Software');
      const resp = await enviarPeticion({
        link: link_,
        op: 'crear-lote',
        id_referencia,
        lote,
        vencimiento
      });

      if (!resp.ok) {
        dissminSwal();
        Swal.fire("Error", "Error al insertar el lote", "error")
        return;
      }
      $("#modalCrearLote").modal("hide")
      const listadoLote = await lotes(id_referencia);
      let p = ``
      if (listadoLote.length > 0) {
        p += `<option value ="">Seleccionar</option>`
        listadoLote.forEach(element => {
          p += `<option value ="${element.id}" data-element='${JSON.stringify(element)}'>${element.lote} -  ${element.vencimiento}</option>`
        });
      }
      $("#lotes").html(p)

      $("#id_referencia_lote").val('')
      $("#descripcion_referencia_lote").val('')
      $("#lote_lote").val('')
      $("#fecha_vencimiento_lote").val('')
      dissminSwal()

    } catch (error) {
      dissminSwal()
      Swal.fire("Error", "Error al insertar el lote", "error")
      console.error(error)
    }

  });

  $(document).on('keydown', function (e) {

    if (e.key === 'Escape') {

      $("#descripcion").val("")
      $("#lotes").html(`<option selected>Seleccione un lote</option>`)
      $("#cantidad").val("")
      $("#conteo").val("")
      $("#id_referencia").val("")
      $("#busqueda").val("")
      $("#busqueda").focus()
    }
  })

  $("#lote_lote").focusout(async function () {
    const lote = $(this).val();
    if (lote == null || lote == "") {
      return;
    }
    const id_referencia = $("#id_referencia").val();
    if (id_referencia == null || id_referencia == "") {
      return;
    }
    try {
      showLoadingWithTetrisLogo('Validando lote', 'ISA Software');
      const resp = await enviarPeticion({
        link: link_,
        op: 'validar-lote',
        id_referencia,
        lote
      });

      if (resp.length > 0) {
        dissminSwal();
        Swal.fire("Error", "El lote ya existe, por favor ingrese otro lote", "error")
        $("#lote_lote").val("")
        $("#lote_lote").focus()
        return;
      }
      dissminSwal();
    } catch (error) {
      dissminSwal();
      Swal.fire("Error", "Error al validar el lote", "error")
      console.error(error)
    }
  });

  $("#consultar").click(async function () {

    try {
      showLoadingWithTetrisLogo('Consultando información de inventario', 'ISA Software');
      const resp = await enviarPeticion({
        link: '../models/TomaFisica.php',
        op: 'informe-seguimiento',
        id_bodega: $("#id_bodega_session").val(),
        id_usuario: $("#select_usuario").val()
      });

      if (resp.data.length == 0) {
        $("#result-info").html(ShowAlert('No se encontraron resultados', 'warning'))
        dissminSwal();
        return;
      }
      const codigosUnicos = new Set(resp.data.map(r => r.codigo));
      const totalUnicos = codigosUnicos.size;
      //$("#total_ref_mov").text(totalUnicos)
      //$("#total_contadas").text(resp.n_ref_mov)
      //$("#pcj_conteo").text(((totalUnicos / resp.n_ref_mov) * 100).toFixed(2))

      $("#result-info").html(`<table id="informe" class="table table-sm table-borderless table-hover table-striped">
                          <thead class="table-primary"></thead> 
                      </table>`)
      createDataTable('informe', resp.data,
        ['codigo', 'descripcion', 'marca', 'stock', 'toma_1', 'toma_2', 'toma_3', 'observacion'],
        ['Codigo', 'Descripcion', 'Marca', 'Stock', 'Toma 1', 'Toma 2', 'Toma 3', 'Observacion'],
        null,
        null,
        false,
        '',
        '', true, [], '', [{ data: 'toma_1', className: 'table-primary' }, { data: 'toma_2', className: 'table-success' }, { data: 'toma_3', className: 'table-warning' }])
      dissminSwal();

    } catch (error) {
      console.error(error)
      Swal.fire("Error", "Error al consultar los registros", "error")
    }
  });

  $("#li_ajuste").click(async function () {
    try {
      const resp = await enviarPeticion({
        link: '../models/TomaFisica.php',
        op: 'pre-ajuste',
        id_bodega: $("#id_bodega_session").val()
      })
      console.log({ resp })
      if (resp.length == 0) {
        preAjuste = [];
        $("#result-pre-ajuste").html(ShowAlert('No se encontraron resultados', 'warning'))
        $("#ajuste_salidas").text("$0");
        $("#ajuste_entradas").text("$0");
        return;
      }
      const entradas = resp.filter(item => item.proceso == 'Entrada');
      const salidas = resp.filter(item => item.proceso == 'Salida');

      const totalEntradas = entradas.reduce((sum, item) => {
        return sum + (Number(item.total_costo) || 0);
      }, 0);

      const totalSalidas = salidas.reduce((sum, item) => {
        return sum + (Number(item.total_costo) || 0);
      }, 0);

      $("#ajuste_salidas").text(formatNum(totalSalidas, '$'));
      $("#ajuste_entradas").text(formatNum(totalEntradas, '$'));

      $("#result-pre-ajuste").html(`<table id="pre-ajuste" class="table table-sm table-borderless">
                          <thead class="table-primary"></thead> 
                      </table>`)


      createDataTable(
        'pre-ajuste',
        resp,
        ['id_referencia', 'codigo', 'descripcion', 'marca', 'stock', 'lote', 'vencimiento', 'toma_1', 'toma_2', 'toma_3', 'cantidad_ajustar', 'lote_toma', 'proceso', 'total_costo'],
        ['Id', 'Codigo', 'Descripcion', 'Marca', 'Stock', 'Lote', 'Vencimiento', 'Toma 1', 'Toma 2', 'Toma 3', 'Cantidad ajuste', 'Lote toma', 'proceso', 'Costo total'],
        null,
        null,
        false,
        '',
        '',
        true,
        ['total_costo'],
        'COP',
        [{ data: 'toma_1', className: 'table-primary' }, { data: 'toma_2', className: 'table-success' }, { data: 'toma_3', className: 'table-warning' }],
        [],
        null
      );
      preAjuste = resp;

      // Aplicar estilos condicionales para colorear SALIDAS en rojo
      setTimeout(() => {
        $('#pre-ajuste tbody tr').each(function () {
          const fila = $(this);
          // Obtener el valor de la columna "proceso" (índice 12)
          const procesos = fila.find('td').eq(12).text().trim();

          if (procesos === 'Salida') {
            // Colorear en rojo la columna "Cantidad ajuste" (índice 10)
            fila.find('td').eq(10).css({ 'color': 'red', 'font-weight': 'bold' });
            // Colorear en rojo la columna "proceso" (índice 12)
            fila.find('td').eq(12).css({ 'color': 'red', 'font-weight': 'bold' });
          }
        });
      }, 100);

      dissminSwal();

      return;

    } catch (error) {
      dissminSwal();
      Swal.fire("Oops", "Error al generar el reporte", "error")
      console.error(error)
    }

  })

  $("#realizar_ajuste").click(async function () {

    if (preAjuste.length == 0) {
      Swal.fire("Oops", "No se ha cargado un inventario, no hay nada que ajustar", "error");
      return;
    }

    // valido si hay tercero cargado
    if ($("#id_tercero").val() == '') {
      Swal.fire("Oops", "Seleccione un tercero para el ajuste", "error");
      return;
    }
    //valido si hay id de direccion
    if ($("#id_direccion").val() == '') {
      Swal.fire("Oops", "Seleccione una dirección para el ajuste", "error");
      return;
    }

    showLoadingWithTetrisLogo('Ejecutando ajuste', 'ISA Software');

    let entradas = [];
    let salidas = [];

    // ✅ recorrer el array de forma asincrónica
    for (const item of preAjuste) {

      // 🔹 Ceder control un micro-momento para que actualice el loading
      await new Promise(res => setTimeout(res, 1));

      if (item.proceso === "Entrada") {

        entradas.push({
          id_referencia: item.id_referencia,
          cantidad: item.cantidad_ajustar,
          id_bodega: $("#bodegas").val(),
          id_lote: item.id_lote,
          codigo: item.codigo,
          costo: parseFloat(item.costo),
          pcj_iva: item.pcj_iva,
          lote: item.lote_toma,
          vmcto: item.vmcto_toma
        });

      } else if (item.proceso === "Salida") {

        salidas.push({
          id_referencia: item.id_referencia,
          cantidad: item.cantidad_ajustar * -1,
          id_bodega: $("#bodegas").val(),
          id_lote: item.id_lote,
          codigo: item.codigo,
          costo: parseFloat(item.costo),
          pcj_iva: item.pcj_iva,
          lote: item.lote_toma,
          vmcto: item.vmcto_toma
        });
      }
    }

    try {

      const resp = await enviarPeticion({
        link: '../models/TomaFisica.php',
        op: 'realizar-ajuste',
        id_bodega: $("#id_bodega_session").val(),
        id_tercero: $("#id_tercero").val(),
        id_tipo_documento_salida: $("#tipo_doc_salida").val(),
        id_tipo_documento_entrada: $("#tipo_doc_entrada").val(),
        id_condicion: $("#id_condicion").val(),
        notas: $("#notas").val(),
        id_tercero_dir: $("#id_direccion").val(),
        salidas: JSON.stringify(salidas),
        entradas: JSON.stringify(entradas)
      });

      console.log({ resp });

      if (!resp.ok) {
        Swal.fire("Error", resp.message, "error");
        return;
      }

      nuevo();
      dissminSwal();

      await Swal.fire({
        title: "Documentos generados",
        width: "90%",
        html: `
                  <div style="margin-bottom:10px;">
                      ${resp.id_entrada > 0 ? `
                          <button id="btnEntrada" class="swal2-confirm swal2-styled">
                              🖨️ Entrada
                          </button>
                      ` : ""}

                      ${resp.id_salida > 0 ? `
                          <button id="btnSalida" class="swal2-deny swal2-styled">
                              🖨️ Salida
                          </button>
                      ` : ""}
                  </div>

                  <iframe
                      id="visorPdf"
                      src="${resp.id_entrada > 0
            ? `../pdf/entradas-salidas.php?id=${resp.id_entrada}`
            : `../pdf/entradas-salidas.php?id=${resp.id_salida}`
          }"
                      style="width:100%;height:600px;border:none;">
                  </iframe>
              `,
        showConfirmButton: false,
        showCloseButton: true,
        didOpen: () => {

          const visor = document.getElementById("visorPdf");

          const btnEntrada = document.getElementById("btnEntrada");
          if (btnEntrada) {
            btnEntrada.addEventListener("click", () => {
              visor.src = `../pdf/entradas-salidas.php?id=${resp.id_entrada}`;
            });
          }

          const btnSalida = document.getElementById("btnSalida");
          if (btnSalida) {
            btnSalida.addEventListener("click", () => {
              visor.src = `../pdf/entradas-salidas.php?id=${resp.id_salida}`;
            });
          }

        }
      });

    } catch (error) {
      dissminSwal();
      console.error({ error });
      Swal.fire("Error", "Ocurrió un error realizando el ajuste", "error");
    }
  });
  dissminSwal();
})