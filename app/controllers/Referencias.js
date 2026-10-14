const link_ = '../models/Referencias.php';
let arrayListas = [];
let arrayEan = [];
let arrImages = [];
let arrayRef = [];
let auditoriaReferenciaData = [];
Dropzone.autoDiscover = false;
let comisiones = [];

const guardar = async (data) => {
  showLoadingWithTetrisLogo('Guardando información', 'ISA Software')
  try {

    let esValido = true;
    let undCompra = 0;
    // Validación de las unidades y cantidades
    for (let i = 1; i <= 4; i++) {
      const und = $(`#und_${i}`);
      const cant = $(`#cant_${i}`);
      const compra = $(`#compra_${i}`);

      // Si la unidad no está vacía, validar la cantidad
      if (und.val().trim() !== '') {
        if (cant.val().trim() === '' || parseFloat(cant.val()) <= 0) {
          Swal.fire(`Oops`, `La cantidad de ${und.val()} no puede ser vacía o cero.`, `error`);
          esValido = false;
          break;
        }
      }

      // Si la cantidad no está vacía, validar que la unidad no esté vacía
      if (cant.val().trim() > 0) {
        if (und.val().trim() === '') {
          Swal.fire(`Oops`, `Debe completar la unidad para la cantidad de ${cant.val()}.`, `error`);
          esValido = false;
          break;
        }
      }

      // Validación cuando la casilla de compra está marcada
      if (compra.prop('checked')) {
        if (und.val().trim() === '' || cant.val().trim() === '') {
          Swal.fire(`Oops`, `Debe completar tanto la unidad como la cantidad para ${und.val()}.`, `error`);
          esValido = false;
          break;
        }
        undCompra = parseFloat(cant.val());
      }
    }

    if (!esValido) {
      return;
    }


    // Operación de referencia y lista de datos
    const id_referencia = $("#idReferencia").val().trim();
    data.op = id_referencia == 0 ? 'g_referencia' : 'u_referencia';
    if (id_referencia != 0) data.id = id_referencia;

    if (id_referencia != 0) {
      //remuevo el codigo 
      delete data.codigo;
    }

    data.listas = JSON.stringify(arrayListas);
    data.ean = JSON.stringify(arrayEan);
    data.link = link_;

    // Eliminar comas en valores numéricos
    if (data.costo_unitario) data.costo_unitario = data.costo_unitario.replace(/,/g, '') / undCompra;
    if (data.impoconsumo) data.impoconsumo = data.impoconsumo.replace(/,/g, '');
    if (data.ibua) data.ibua = data.ibua.replace(/,/g, '');
    // Asignar null a las propiedades si las cantidades son vacías
    if (!data.cant_2) {
      data.cant_2 = 0;
      data.und_2 = '';
    }
    if (!data.cant_3) {
      data.cant_3 = 0;
      data.und_3 = '';
    }
    if (!data.cant_4) {
      data.cant_4 = 0;
      data.und_4 = '';
    }

    // Agregar archivos
    data.archivos = arrImages;
    // data.ubicaciones = $("#ubicacion").val().length > 0 ? $("#ubicacion").val() : new Array();
    data.ubicaciones = JSON.stringify($("#ubicacion").val() || []);

    if ($("#no_venta").is(":checked")) {
      data.no_venta = 'S';
    } else {
      data.no_venta = 'N';
    }


    let comisiones = [];
    // obtengo las comision 
    if ($("#chkComisiones").is(":checked")) {

      $("#tbComisiones tbody tr").each(function () {
        const lista = $.trim($(this).find("td").eq(0).text());
        const calculo_comision = $.trim($.trim($(this).find("td").eq(1).text()));
        const comision1 = parseFloat($(this).find("td").eq(2).text().replace(/[$,%]/g, '')) || 0;
        const comision2 = parseFloat($(this).find("td").eq(3).text().replace(/[$,%]/g, '')) || 0;
        const comision3 = parseFloat($(this).find("td").eq(4).text().replace(/[$,%]/g, '')) || 0;
        const comision4 = parseFloat($(this).find("td").eq(5).text().replace(/[$,%]/g, '')) || 0;

        comisiones.push({
          lista,
          comision1,
          comision2,
          comision3,
          comision4,
          calculo_comision
        });
      });
    } else {
      comisiones = [];
    }

    if (comisiones.length > 0) {
      data.comision = 'S';
      data.comisiones = JSON.stringify(comisiones);
    } else {
      data.comision = 'N';
    }
    console.log(data)

    const resp = await enviarFormData(data);
    if (!resp.error) {
      initToast('bg-success', 'Correcto!', resp.message, 'success');
      limpiar();
      getIva();

    } else {
      initToast('bg-danger', 'Error!', resp.message, 'danger');
    }
    dissminSwal();
  } catch (e) {
    dissminSwal();
    console.warn({ e });
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
};

const getReferencia = async (codigo) => {
  try {
    showLoadingWithTetrisLogo('Cargando información por favor espere...', 'ISA Software');
    const dataSend = {
      link: link_,
      op: 's_referencia',
      codigo: codigo
    };
    const resp = await enviarPeticion(dataSend);
    $('#formReferencias').removeClass('was-validated');
    if (resp.data.length > 0) {
      llenarFormulario(resp.data[0]);

      if ($.trim(resp.data[0].comision) == 'S') {
        $("#chkComisiones").attr("checked", true);
        limpiarImputComisiones();
        $(".element-comisiones,.element-comisiones-calculo").attr("disabled", false);

      } else {
        $("#chkComisiones").attr("checked", false);
        limpiarImputComisiones();
        $(".element-comisiones,.element-comisiones-calculo").attr("disabled", false);
      }

      //datos que no estan en el formulario no se guardan ni actualizan
      item = resp.data[0];
      $("#costo_ultimo").val(formatNum(item.ultimo_costo_compra, '$'))
      $("#costo_promedio").val(formatNum(item.costo_promedio, '$'))
      $("#stock_actual").val(formatNum(item.stock_actual, ''));
      $("#txtCodigo").val(item.codigo);
      const ubicaciones = await enviarPeticion({
        link: '../models/Referencias.php',
        op: 'ubicaciones_referencias',
        id_referencia: $("#idReferencia").val()
      })

      const idubs = ubicaciones.map(item => {
        return item.id_ubicacion
      })

      $("#ubicacion").val(idubs).trigger("change")
    } else {
      $("#idReferencia").val(0);
      limpiar(false);
      $("#txtCodigo").val(codigo);
    }
    dissminSwal();

  } catch (e) {
    console.log(e);
  }
};

const obtenerCostosListas = () => ({
  costoPromedio: parseFloat($("#costo_promedio").val().replace(/[$,]/g, '')) || 0,
  costoUltimo: parseFloat($("#costo_ultimo").val().replace(/[$,]/g, '')) || 0
});

const obtenerIvaActual = () => {
  const ivaText = $("#slcIva option:selected").text() || '0';
  return parseFloat(ivaText.replace(/[$,%]/g, '')) || 0;
};

const calcularRentabilidadLista = (precioFijo, costoBase, ivaPercent = 0) => {
  const precio = parseFloat(String(precioFijo || 0).replace(/[$,]/g, '')) || 0;
  if (costoBase <= 0) return 0;
  // Descontar IVA del precio: precio_sin_iva = precio_con_iva / (1 + (iva/100))
  const precioSinIva = precio / (1 + (ivaPercent / 100));
  return ((precioSinIva - costoBase) / costoBase) * 100;
};

const calcularPrecioVentaLista = (porcentaje, costoBase, ivaPercent = 0) => {
  const margen = parseFloat(String(porcentaje || 0).replace(/[$,]/g, '')) || 0;
  if (costoBase <= 0) return 0;
  // Calcular precio sin IVA y luego agregar IVA
  const precioSinIva = costoBase * (1 + margen / 100);
  const precioConIva = precioSinIva * (1 + (ivaPercent / 100));
  return precioConIva;
};

const formatearValorCalculadoLista = (valor, tipoLista) => {
  const numero = parseFloat(valor) || 0;
  return tipoLista === 'PF' ? `${numero.toFixed(2)}%` : formatNum(numero.toFixed(0), '$');
};

const asegurarEstiloRentabilidadNegativa = () => {
  if ($('#style-rentabilidad-negativa').length > 0) return;

  $('head').append(`
    <style id="style-rentabilidad-negativa">
      .rentabilidad-negativa {
        border-color: #dc3545 !important;
        background-color: #fff5f5 !important;
        color: #dc3545 !important;
        font-weight: 700;
        animation: latidoRentabilidad 0.9s infinite;
      }

      @keyframes latidoRentabilidad {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.55); }
        50% { transform: scale(1.02); box-shadow: 0 0 0 6px rgba(220, 53, 69, 0.10); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
      }
    </style>
  `);
};

const renderInputPrecioLista = (campo, valorEditable, valorCalculado, tipoLista) => {
  const valorFormateado = formatNum(String(valorEditable || 0).replace(/[$,]/g, ''));
  let txt1 = 'Precio';
  let txt2 = 'Rentabilidad';
  let formula1 = 'Precio con IVA ingresado';
  let formula2 = 'Rentabilidad: ((Precio sin IVA - Costo) / Costo) × 100%';
  if (tipoLista !== 'PF') {
    txt1 = 'Rentabilidad';
    txt2 = 'Precio';
    formula1 = 'Rentabilidad porcentual';
    formula2 = 'Precio con IVA: (Costo × (1 + Rentabilidad%)) × (1 + IVA%)';
  }
  return `
    <div class="row gx-1 gy-1">
      <div class="col-6">
        <label class="form-label mb-1 text-primary small" title="${formula1}">${txt1}</label>
        <input type="text" class="form-control form-control-sm precio-edit" data-col="${campo}" value="${valorFormateado}" title="${formula1}">
      </div>
      <div class="col-6">
        <label class="form-label mb-1 text-secondary small" title="${formula2}">${txt2}</label>
        <input type="text" class="form-control form-control-sm precio-calc bg-light" data-calc-col="${campo}" value="${formatearValorCalculadoLista(valorCalculado, tipoLista)}" readonly title="${formula2}">
      </div>
    </div>
  `;
};

const actualizarValoresCalculadosFila = (fila) => {
  asegurarEstiloRentabilidadNegativa();

  const tipoLista = fila.find('td:eq(1)').text().trim();
  const { costoPromedio, costoUltimo } = obtenerCostosListas();
  const costoBase = tipoLista === 'CU' ? costoUltimo : costoPromedio;
  const ivaActual = obtenerIvaActual();

  ['precio', 'precio_2', 'precio_3', 'precio_4'].forEach((campo) => {
    const inputEditable = fila.find(`[data-col="${campo}"]`);
    const inputCalculado = fila.find(`[data-calc-col="${campo}"]`);
    const valorActual = inputEditable.val();
    const calculado = tipoLista === 'PF'
      ? calcularRentabilidadLista(valorActual, costoBase, ivaActual)
      : calcularPrecioVentaLista(valorActual, costoBase, ivaActual);

    inputCalculado.val(formatearValorCalculadoLista(calculado, tipoLista));
    inputEditable.removeClass('rentabilidad-negativa');
    inputCalculado.removeClass('rentabilidad-negativa');

    const valorRentabilidad = tipoLista === 'PF'
      ? (parseFloat(calculado) || 0)
      : (parseFloat(String(valorActual || 0).replace(/[$,%]/g, '')) || 0);
    const valorBase = parseFloat(String(valorActual || 0).replace(/[$,%]/g, '')) || 0;

    if (valorRentabilidad < 0 && valorBase !== 0) {
      if (tipoLista === 'PF') {
        inputCalculado.addClass('rentabilidad-negativa');
      } else {
        inputEditable.addClass('rentabilidad-negativa');
      }
    }
  });
};

const recalcularTablaListas = () => {
  $('#tbListas tbody tr').each(function () {
    actualizarValoresCalculadosFila($(this));
  });
};

const getListas = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_referencia_listas',
      id_referencia: id
    };
    const resp = await enviarPeticion(dataSend);
    const { costoPromedio, costoUltimo } = obtenerCostosListas();
    $('#tbListas tbody').html('');
    $("#tbComisiones tbody").html('');
    arrayListas = [];

    if (resp.data.length > 0) {
      const ivaActual = obtenerIvaActual();
      resp.data.forEach((d) => {
        let p1 = 0;
        let p2 = 0;
        let p3 = 0;
        let p4 = 0;

        switch (d.tipo_lista) {
          case 'PF':
            p1 = calcularRentabilidadLista(d.precio, costoPromedio, ivaActual);
            p2 = calcularRentabilidadLista(d.precio_2, costoPromedio, ivaActual);
            p3 = calcularRentabilidadLista(d.precio_3, costoPromedio, ivaActual);
            p4 = calcularRentabilidadLista(d.precio_4, costoPromedio, ivaActual);
            break;
          case 'CP':
            p1 = calcularPrecioVentaLista(d.precio, costoPromedio, ivaActual);
            p2 = calcularPrecioVentaLista(d.precio_2, costoPromedio, ivaActual);
            p3 = calcularPrecioVentaLista(d.precio_3, costoPromedio, ivaActual);
            p4 = calcularPrecioVentaLista(d.precio_4, costoPromedio, ivaActual);
            break;
          case 'CU':
            p1 = calcularPrecioVentaLista(d.precio, costoUltimo, ivaActual);
            p2 = calcularPrecioVentaLista(d.precio_2, costoUltimo, ivaActual);
            p3 = calcularPrecioVentaLista(d.precio_3, costoUltimo, ivaActual);
            p4 = calcularPrecioVentaLista(d.precio_4, costoUltimo, ivaActual);
            break;
        }

        let newRow = ` <tr>
                        <td>${d.lista}</td>
                        <td>${d.tipo_lista}</td>
                        <td>${renderInputPrecioLista('precio', d.precio, p1, d.tipo_lista)}</td>
                        <td>${renderInputPrecioLista('precio_2', d.precio_2, p2, d.tipo_lista)}</td>
                        <td>${renderInputPrecioLista('precio_3', d.precio_3, p3, d.tipo_lista)}</td>
                        <td>${renderInputPrecioLista('precio_4', d.precio_4, p4, d.tipo_lista)}</td>
                        <td align="center">
                          <button class="btn btn-sm btn-outline-danger btnEliminar">
                            <i class="fa-regular fa-trash-can"></i>
                          </button>
                        </td>
                      </tr>
                      `;

        $('#tbListas tbody').append(newRow);
        actualizarValoresCalculadosFila($('#tbListas tbody tr:last'));
        arrayListas.push({
          lista: d.lista,
          tipo: d.tipo_lista,
          precio: d.precio,
          precio_2: d.precio_2,
          precio_3: d.precio_3,
          precio_4: d.precio_4,
          id: d.id,
          eliminar: ''
        });

        //valido las comisiones 
        const tipo_comision = d?.calculo_comision == 'porcentaje' || d?.calculo_comision == 'valor';

        if (tipo_comision) {

          let html = `<tr id="tr-comision-${d.lista}">
                          <td>${d?.lista}</td>
                          <td>${d.calculo_comision}</td>
                          <td>${d?.comision1}</td>
                          <td>${d?.comision2}</td>
                          <td>${d?.comision3}</td>
                          <td>${d?.comision4}</td>
                          <td><button type="button" data-id ="${d?.id}" class="btn btn-outline-danger btn-sm btnEliminarComision">
                              <i class="fa-regular fa-trash-can" ></i>
                          </button></td>
                      </tr>`;
          $("#tbComisiones tbody").append(html);

        }
      });


      $(".btnEliminarComision").off("click").on("click", async function () {
        const idLista = $(this).data("id");
        if (idLista > 0) {
          await updateComisiones(idLista);
          $(this).closest("tr").remove();
        } else {
          $(this).closest("tr").remove();
        }

      });
      limpiarImputComisiones();

    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

const getEan = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_referencia_ean',
      id_referencia: id
    };
    const resp = await enviarPeticion(dataSend);
    $('#tbCodBarras tbody').html('');
    if (resp.data.length > 0) {
      resp.data.forEach((d) => {
        // Crear una nueva fila
        let newRow = `
          <tr>
            <td>${d.ean}</td>
            <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar"><i class="fa-regular fa-trash-can"></i></button></td>
          </tr>`;

        // Agregar la nueva fila al DOM
        $('#tbCodBarras tbody').append(newRow);
        // Agregar a arrayListas
        arrayEan.push({
          ean: d.ean,
          id: d.id,
          eliminar: ''
        });
      });
    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

const llenarFormulario = async (data) => {
  try {
    $("#idReferencia").val(data.id);
    $("#btnAuditoria").prop('disabled', false);
    $("#btnComportamiento").prop('disabled', false);
    const cantKey = `cant_${data.und_compra}`;
    data.costo_unitario = data.costo_unitario * data[cantKey];
    ShowDataFrom(data, 'formReferencias', ['costo_unitario', 'impoconsumo', 'ibua']);
    getListas(data.id);
    getEan(data.id);
    getImagenes(data.id);
    validaConversion();

  } catch (e) {
    console.log(e);
  }
};

const getImagenes = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_referencia_img',
      id_referencia: id
    };

    const resp = await enviarPeticion(dataSend);
    var myDropzone = Dropzone.forElement(".dropzone");

    if (myDropzone) {
      myDropzone.removeAllFiles();
      myDropzone.removeAllFiles(true);
    } else {
      console.error("myDropzone no está definido.");
      return;
    }

    // Verifica que la respuesta tenga imágenes válidas
    if (Array.isArray(resp.data) && resp.data.length > 0) {
      for (const item of resp.data) {
        if (item.imagen && typeof item.imagen === 'string') {
          const imageUrl = item.path;

          // Descargar el archivo desde la URL
          const fileBlob = await fetch(imageUrl)
            .then(res => res.blob()) // Convierte la respuesta en un Blob
            .catch(err => {
              console.error("Error descargando la imagen:", err);
              return null;
            });

          if (fileBlob) {
            // Crea un objeto File a partir del Blob descargado
            const file = new File([fileBlob], item.imagen, {
              type: fileBlob.type
            });

            // Ahora agregamos el archivo real a Dropzone
            myDropzone.emit("addedfile", file);
            myDropzone.emit("thumbnail", file, imageUrl); // Puedes mostrar la miniatura si lo deseas
            myDropzone.emit("complete", file);
            myDropzone.files.push(file); // Agrega el archivo al array de archivos de Dropzone
          } else {
            console.warn("No se pudo descargar la imagen:", item);
          }
        } else {
          console.warn("La imagen no tiene un formato válido:", item);
        }
      }
    } else {
      console.log("No se encontraron imágenes o la respuesta no tiene el formato esperado.");
    }
  } catch (e) {
    console.error("Error en la solicitud:", e);
  }
};

const getIva = async () => {
  try {
    const dataSend = {
      link: link_,
      op: 's_iva_perfil',
      id_perfil: $("#slcPerfilContable").val()
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

const getIcui = async () => {
  try {
    const dataSend = {
      link: link_,
      op: 's_icui'
    };
    const resp = await enviarPeticion(dataSend);
    let option = ``;
    resp.forEach((d) => {
      option += `<option value="${d.id}">${d.porcentaje_icui}</option>`;
    });
    $("#slcICUI").html(option);
  } catch (e) {
    console.log(e);
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
};

const llenarSelectListas = async () => {
  let listas = await listasPrecios();

  if (!Array.isArray(listas)) {
    console.warn("El parámetro listas no es un array");
    return;
  }

  const options = [
    ...listas.map(({
      id,
      Descripcion
    }) =>
      `<option value="${id}">${id} - ${Descripcion}</option>`
    )
  ];

  $("#slcLista,#slcListaComision").html(options.join(""));
}

const tipoListas = () => {
  let options = `<option value="PF">PF - Precio Fijo</option>
			  	  <option value="CP">CP - Costo Promedio</option>
				  <option value="CU">CU - Costo Ultimo</option>`;
  $('#slcTipoLista').html(options);
};

const addEan = () => {
  const ean = $('#txtEan').val();
  if (!ean) {
    Swal.fire('Oops', 'Por favor, complete todos los campos.', 'error');
    return;
  }
  const existingRow = $('#tbCodBarras tbody tr').filter(function () {
    return $(this).find('td:eq(0)').text() === ean;
  });

  if (existingRow.length > 0) {
    Swal.fire('Oops', 'El codigo ingresado ya ha sido asignado.', 'error');
    return;
  } else {
    const newRow = `
      <tr>
        <td>${ean}</td>
        <td align="center"><button class="btn btn-sm btn-outline-danger btnEliminar"><i class="fa-regular fa-trash-can"></i></button></td>
      </tr>
    `;
    $('#tbCodBarras tbody').append(newRow);
    arrayEan.push({
      ean: ean,
      id: 0,
      eliminar: ''
    });
  }
  $('#txtEan').val('');
}

const addLista = () => {

  // Leer valores
  const lista = $('#slcLista').val();
  const tipoLista = $('#slcTipoLista').val();
  const rent1 = $('#txtRentabildad').val();
  const rent2 = $('#txtRentabildad2').val();
  const rent3 = $('#txtRentabildad3').val();
  const rent4 = $('#txtRentabildad4').val();
  const { costoPromedio, costoUltimo } = obtenerCostosListas();
  const costoBase = tipoLista === 'CU' ? costoUltimo : costoPromedio;
  const ivaActual = obtenerIvaActual();
  const calcularValor = tipoLista === 'PF' ? calcularRentabilidadLista : calcularPrecioVentaLista;

  const p1 = calcularValor(rent1, costoBase, ivaActual);
  const p2 = calcularValor(rent2, costoBase, ivaActual);
  const p3 = calcularValor(rent3, costoBase, ivaActual);
  const p4 = calcularValor(rent4, costoBase, ivaActual);

  // Validar que todos los campos estén completos
  if (!lista || !tipoLista || !rent1 || !rent2 || !rent3 || !rent4) {
    Swal.fire('Oops', 'Por favor, complete todos los campos de rentabilidad.', 'error');
    return;
  }

  // Buscar fila existente por lista
  const existingRow = $('#tbListas tbody tr').filter(function () {
    return $(this).find('td:eq(0)').text() === lista;
  });

  if (existingRow.length > 0) {

    // Actualizar tipo de lista
    existingRow.find('td:eq(1)').text(tipoLista);

    // Actualizar valores de los inputs
    existingRow.find('[data-col="precio"]').val(formatNum(rent1.replace(/[$,]/g, '')));
    existingRow.find('[data-col="precio_2"]').val(formatNum(rent2.replace(/[$,]/g, '')));
    existingRow.find('[data-col="precio_3"]').val(formatNum(rent3.replace(/[$,]/g, '')));
    existingRow.find('[data-col="precio_4"]').val(formatNum(rent4.replace(/[$,]/g, '')));

    // Actualizar arrayListas
    const index = arrayListas.findIndex(item => item.lista === lista);

    if (index !== -1) {
      arrayListas[index].tipo = tipoLista;
      arrayListas[index].precio = rent1.replace(/,/g, '');
      arrayListas[index].precio_2 = rent2.replace(/,/g, '');
      arrayListas[index].precio_3 = rent3.replace(/,/g, '');
      arrayListas[index].precio_4 = rent4.replace(/,/g, '');
    }

    actualizarValoresCalculadosFila(existingRow);

  } else {

    // Insertar nueva fila
    const newRow = `
      <tr>
        <td>${lista}</td>
        <td>${tipoLista}</td>
        <td>${renderInputPrecioLista('precio', rent1, p1, tipoLista)}</td>
        <td>${renderInputPrecioLista('precio_2', rent2, p2, tipoLista)}</td>
        <td>${renderInputPrecioLista('precio_3', rent3, p3, tipoLista)}</td>
        <td>${renderInputPrecioLista('precio_4', rent4, p4, tipoLista)}</td>
        <td align="center">
          <button class="btn btn-sm btn-outline-danger btnEliminar">
            <i class="fa-regular fa-trash-can"></i>
          </button>
        </td>
      </tr>
    `;

    $('#tbListas tbody').append(newRow);
    actualizarValoresCalculadosFila($('#tbListas tbody tr:last'));

    // Agregar al arrayListas
    arrayListas.push({
      lista: lista,
      tipo: tipoLista,
      precio: rent1.replace(/,/g, ''),
      precio_2: rent2.replace(/,/g, ''),
      precio_3: rent3.replace(/,/g, ''),
      precio_4: rent4.replace(/,/g, ''),
      id: 0,
      eliminar: ''
    });

  }
  // Limpiar los campos de rentabilidad
  for (let i = 1; i <= 4; i++) {

    const sufijo = i === 1 ? '' : i;

    $('#txtRentabildad' + sufijo).val(0);
    $('#textPrecioSinImp' + sufijo).val('$0');
    $('#textPrecioConImp' + sufijo).val('$0');

  }

};

const eliminarLista = (btn) => {
  const fila = $(btn).closest('tr');
  const lista = fila.find('td:eq(0)').text();
  const tipoLista = fila.find('td:eq(1)').text();
  const precioRentabilidad = fila.find('td:eq(2)').text();
  const index = arrayListas.findIndex(item => item.lista === lista);
  if (index !== -1) {
    const item = arrayListas[index];
    if (item.id === 0) {
      arrayListas.splice(index, 1);
    } else {
      item.eliminar = 'x';
    }
    fila.remove();
  }
};

const eliminarEan = (btn) => {
  const fila = $(btn).closest('tr');
  const ean = fila.find('td:eq(0)').text();
  const index = arrayEan.findIndex(item => item.ean === ean);
  if (index !== -1) {
    const item = arrayEan[index];
    if (item.id === 0) {
      arrayEan.splice(index, 1);
    } else {
      item.eliminar = 'x';
    }
    fila.remove();
  }
};

const limpiar = (focus = true) => {
  $("#idReferencia").val(0);
  $("#btnAuditoria").prop('disabled', true);
  $("#btnComportamiento").prop('disabled', true);
  $('#tbListas tbody').html('');
  $('#tbCodBarras tbody').html('');
  $("#tbComisiones tbody").html('');
  limpiarImputComisiones();
  for (let i = 1; i <= 4; i++) {
    const sufijo = i === 1 ? '' : i;
    $('#txtRentabildad' + sufijo).val(0);
    $('#textPrecioSinImp' + sufijo).val('$0');
    $('#textPrecioConImp' + sufijo).val('$0');
  }
  limpiarFormulario('formReferencias');
  arrayListas = [];
  arrayEan = [];
  arrImages = [];
  getCategoriasSelect(1);
  getCategoriasSelect(2);
  getCategoriasSelect(3);
  getCategoriasSelect(4);
  if (focus) {
    $("#txtCodigo").focus();
  }

  var myDropzone = Dropzone.forElement(".dropzone");
  if (myDropzone) {
    myDropzone.removeAllFiles();
    myDropzone.removeAllFiles(true);
  }
  validaConversion();
  ultimoCodigo();
  ubicaciones();

}

let aliasAuditoriaMap = {};

const mapearAliasFieldReferencia = {
  // Campos principales
  'id': 'ID',
  'codigo': 'Código',
  'descripcion': 'Descripción',
  'referencia_externa': 'Código Externo',
  'conversiones': 'Conversión Unidades',
  'costo_unitario': 'Costo Unitario',
  'costo_promedio': 'Costo Promedio',
  'costo_ultimo': 'Cambio Precio',
  'estado': 'Estado',

  // Impuestos y aranceles
  'id_perfil_contable': 'Perfil Contable',
  'id_iva': 'IVA',
  'id_icui': 'ICUI',
  'impuesto_consumo': 'Impuesto Consumo',
  'ibua': 'IBUA',

  // Indicadores
  'no_venta': 'No Permitir Venta',
  'generar_registro_inventario': 'Generar Registro Inventario',
  'revisado': 'Revisado',
  'migrado': 'Migrado',

  // Listas de precios (tabla relacionada)
  'lista': 'Lista Precios',
  'tipo_lista': 'Tipo Lista',
  'precio': 'Precio Lista',
  'precio_2': 'Precio 2',
  'precio_3': 'Precio 3',
  'precio_4': 'Precio 4',

  // Códigos de barras (tabla relacionada)
  'ean': 'Código EAN',

  // General
  'usuario_creacion': 'Usuario Creación',
  'fecha_creacion': 'Fecha Creación',
  'usuario_modificacion': 'Usuario Modificación'
};

const obtenerAliasAuditoria = (nombreCampo) => {
  if (!nombreCampo) return 'Campo';
  // Extrae solo la última parte después del punto si existe (ej: "referencias.codigo" → "codigo")
  const campoLimpio = String(nombreCampo).split('.').pop();
  return mapearAliasFieldReferencia[campoLimpio] || campoLimpio.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const parseDetalleAuditoria = (detalle) => {
  if (!detalle) return null;
  try {
    return typeof detalle === 'string' ? JSON.parse(detalle) : detalle;
  } catch (e) {
    return detalle;
  }
};

const flattenObject = (obj, prefix = '', out = {}) => {
  if (obj === null || obj === undefined) return out;

  Object.keys(obj).forEach((key) => {
    const value = obj[key];
    const nextKey = prefix ? `${prefix}.${key}` : key;

    if (Array.isArray(value)) {
      out[nextKey] = value;
      return;
    }

    if (value && typeof value === 'object') {
      flattenObject(value, nextKey, out);
      return;
    }

    out[nextKey] = value;
  });

  return out;
};

const normalizarComparacion = (valor) => {
  if (valor === null || valor === undefined) {
    return '';
  }
  if (typeof valor === 'object') {
    try {
      return JSON.stringify(valor);
    } catch (e) {
      return String(valor);
    }
  }
  return String(valor);
};

const escapeHtml = (text) => {
  if (text === null || text === undefined) return '';
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

const renderizarValorAuditoria = (valor) => {
  if (valor === null || valor === undefined || valor === '') {
    return '';
  }

  if (Array.isArray(valor)) {
    if (valor.length === 0) {
      return '<span class="text-muted">[]</span>';
    }

    const primer = valor[0];
    if (primer && typeof primer === 'object' && !Array.isArray(primer)) {
      const headers = [...new Set(valor.flatMap(item => Object.keys(item || {})))];
      let rows = '';

      valor.forEach((item) => {
        rows += '<tr>';
        headers.forEach((h) => {
          rows += `<td>${escapeHtml(item && item[h] !== undefined ? item[h] : '')}</td>`;
        });
        rows += '</tr>';
      });

      return `
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr>${headers.map(h => `<th>${obtenerAliasAuditoria(h)}</th>`).join('')}</tr>
            </thead>
            <tbody>${rows}</tbody>
          </table>
        </div>
      `;
    }

    let rows = '';
    valor.forEach((item, idx) => {
      rows += `
        <tr>
          <td>${idx + 1}</td>
          <td>${escapeHtml(item)}</td>
        </tr>
      `;
    });

    return `
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `;
  }

  if (typeof valor === 'object') {
    const plano = flattenObject(valor || {});
    let rows = '';
    Object.keys(plano).forEach((k) => {
      rows += `
        <tr>
          <td>${obtenerAliasAuditoria(k)}</td>
          <td>${escapeHtml(plano[k])}</td>
        </tr>
      `;
    });

    return `
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>Campo</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>${rows || '<tr><td colspan="2" class="text-center">Sin datos</td></tr>'}</tbody>
        </table>
      </div>
    `;
  }

  return escapeHtml(valor);
};

const construirTablaCampoValor = (titulo, data, colSpan = 2) => {
  const plano = flattenObject(data || {});
  let rows = '';

  Object.keys(plano).forEach((k) => {
    rows += `
      <tr>
        <td>${obtenerAliasAuditoria(k)}</td>
        <td>${renderizarValorAuditoria(plano[k])}</td>
      </tr>
    `;
  });

  return `
    <div class="mb-3">
      <div class="table-responsive">
        <table class="table table-sm table-bordered table-striped align-middle">
          <thead class="table-light">
            <tr>
              <th>Campo</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>${rows || `<tr><td colspan="${colSpan}" class="text-center">Sin datos</td></tr>`}</tbody>
        </table>
      </div>
    </div>
  `;
};

const construirTablaDetalleAuditoria = (detalle) => {
  const parsed = parseDetalleAuditoria(detalle);

  if (!parsed) {
    return `<div class="alert alert-warning">Sin detalle disponible</div>`;
  }

  if (typeof parsed === 'string') {
    return `<div class="alert alert-secondary">${escapeHtml(parsed)}</div>`;
  }

  if (parsed.estado_anterior !== undefined || parsed.estado_nuevo !== undefined) {
    const anterior = flattenObject(parsed.estado_anterior || {});
    const nuevo = flattenObject(parsed.estado_nuevo || {});
    const keys = [...new Set([...Object.keys(anterior), ...Object.keys(nuevo)])].sort();

    let rows = '';
    keys.forEach((k) => {
      const oldVal = anterior[k] !== undefined ? anterior[k] : '';
      const newVal = nuevo[k] !== undefined ? nuevo[k] : '';
      const changed = normalizarComparacion(oldVal) !== normalizarComparacion(newVal);
      rows += `
        <tr class="${changed ? 'table-warning' : ''}">
          <td>${obtenerAliasAuditoria(k)}</td>
          <td>${renderizarValorAuditoria(oldVal)}</td>
          <td>${renderizarValorAuditoria(newVal)}</td>
        </tr>
      `;
    });

    const tablaComparativa = `
      <div class="mb-3">
        <h6 class="mb-2 text-primary">Comparativo de cambios</h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>Campo</th>
                <th>Estado anterior</th>
                <th>Estado nuevo</th>
              </tr>
            </thead>
            <tbody>${rows || '<tr><td colspan="3" class="text-center">Sin cambios detectados</td></tr>'}</tbody>
          </table>
        </div>
      </div>
    `;

    const tablaAnterior = construirTablaCampoValor('Estado anterior', parsed.estado_anterior || {});
    const tablaNueva = construirTablaCampoValor('Estado nuevo', parsed.estado_nuevo || {});

    return `
      ${tablaComparativa}
      ${tablaAnterior}
      ${tablaNueva}
    `;
  }

  return construirTablaCampoValor('Detalle', parsed);
};

const renderTablaAuditoria = (data) => {
  let rows = '';
  data.forEach((item, index) => {
    rows += `
      <tr>
        <td>${item.fecha || ''}</td>
        <td>${item.usuario || ''}</td>
        <td><span class="badge bg-label-primary">${item.accion || ''}</span></td>
        <td class="text-center">
          <button class="btn btn-sm btn-outline-info btnVerDetalleAuditoria" data-index="${index}">
            <i class="fa-regular fa-eye"></i> Ver
          </button>
        </td>
      </tr>
    `;
  });

  $("#tbodyAuditoriaReferencia").html(rows || '<tr><td colspan="4" class="text-center">No hay registros con los filtros seleccionados.</td></tr>');
};

const aplicarFiltrosAuditoria = () => {
  const accion = ($("#filtroAuditoriaAccion").val() || '').toUpperCase();
  const usuario = ($("#filtroAuditoriaUsuario").val() || '').toLowerCase().trim();
  const texto = ($("#filtroAuditoriaTexto").val() || '').toLowerCase().trim();
  const desde = $("#filtroAuditoriaDesde").val();
  const hasta = $("#filtroAuditoriaHasta").val();

  const filtrados = auditoriaReferenciaData.filter((item) => {
    if (accion && String(item.accion || '').toUpperCase() !== accion) {
      return false;
    }

    if (usuario && !String(item.usuario || '').toLowerCase().includes(usuario)) {
      return false;
    }

    if (texto && !String(item.detalle || '').toLowerCase().includes(texto)) {
      return false;
    }

    const fechaItem = String(item.fecha || '').substring(0, 10);
    if (desde && fechaItem < desde) {
      return false;
    }
    if (hasta && fechaItem > hasta) {
      return false;
    }

    return true;
  });

  renderTablaAuditoria(filtrados);
};

const consultarComportamiento = async () => {
  const id_referencia = parseInt($("#idReferencia").val(), 10);
  const bodega = $("#bodegas").val();
  const fecha_inicial = $("#fecha_inicial").val();
  const fecha_final = $("#fecha_final").val();
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios-referencias.php',
    op: 's_comportamiento_referencia',
    id_referencia,
    bodega,
    fecha_inicial,
    fecha_final
  });

  $("#kpi_stock").text(formatNum(resp.stock));
  $("#kpi_ventas").text(formatNum(resp.unidades_vendidas));
  $("#kpi_compras").text(formatNum(resp.unidades_compradas));
  $("#ultima_compra").text(resp.ultima_fecha_compra);
  $("#ultima_venta").text(resp.ultima_fecha_venta);

}

const consultarProveedores = async () => {
  const id_referencia = parseInt($("#idReferencia").val(), 10);
  const bodega = $("#bodegas").val();
  const fecha_inicial = $("#fecha_inicial").val();
  const fecha_final = $("#fecha_final").val();

  try {
    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios-referencias.php',
      op: 's_proveedores',
      id_referencia,
      bodega,
      fecha_inicial,
      fecha_final
    });

    const tbody = $("#tbProveedores tbody");

    // Limpiar tabla
    tbody.empty();

    // Validar resultados
    if (!resp || resp.length === 0) {
      tbody.html(`
        <tr>
          <td colspan="4" class="text-center text-muted py-3">
            <i class="fa-solid fa-circle-info me-1"></i>
            No se encontraron proveedores para los filtros seleccionados.
          </td>
        </tr>
      `);
      return;
    }

    let filas = '';

    resp.forEach((proveedor) => {
      const proveedorNombre = `${proveedor.razon_comercial ?? ''}`.trim();

      const cantidad = parseFloat(proveedor.cantidad) || 0;
      const costo = parseFloat(proveedor.costo_unitario) || 0;

      let fechaCompra = '-';

      if (proveedor.fecha_hora) {
        const fecha = new Date(proveedor.fecha_hora);

        if (!isNaN(fecha.getTime())) {
          fechaCompra = fecha.toLocaleDateString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
          });
        } else {
          fechaCompra = proveedor.fecha_hora;
        }
      }

      filas += `
        <tr>
          <td>
            <div class="fw-semibold">
              ${proveedorNombre || '-'}
            </div>
            <small class="text-muted">
              NIT: ${proveedor.nit ?? '-'}
            </small>
          </td>

          <td class="text-end">
            ${formatNum(cantidad)}
          </td>

          <td class="text-end fw-semibold">
            ${formatNum(costo, '$')}
          </td>

          <td>
            ${fechaCompra}
          </td>
        </tr>
      `;
    });

    tbody.html(filas);

  } catch (e) {
    console.error("❌ Error en consultarProveedores:", e);

    $("#tbProveedores tbody").html(`
      <tr>
        <td colspan="4" class="text-center text-danger py-3">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          Error al consultar los proveedores.
        </td>
      </tr>
    `);
  }
};

const consultarRentabilidad = async () => {
  try {
    const data = {
      link: '../shared/servicios/servicios-referencias.php',
      op: 's_rentabilidad',
      id_referencia: parseInt($("#idReferencia").val(), 10),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      bodega: $("#bodegas").val()
    };

    const resp = await enviarPeticion(data);
    if (resp && resp.length > 0) {
      mostrarRentabilidad(resp[0]);
    }
  } catch (e) {
    console.error("❌ Error en consultarInventario:", e);
    $("#resultado-inventario").html(
      ShowAlert("Error interno de la aplicación", "error")
    );
    dissminSwal();
  }
};

let graficaRentabilidad = null;

const mostrarRentabilidad = (data) => {

  const venta = parseFloat(data.venta) || 0;
  const costo = parseFloat(data.costo) || 0;
  const utilidad = parseFloat(data.utilidad) || 0;

  const ventaPP = parseFloat(data.venta_pp) || 0;
  const utilidadPP = parseFloat(data.utilidad_pp) || 0;

  const margen = parseFloat(data.margen) || 0;
  const markup = parseFloat(data.markup) || 0;

  const margenPP = parseFloat(data.margen_pp) || 0;
  const markupPP = parseFloat(data.markup_pp) || 0;


  // ==========================================
  // VALORES PRINCIPALES
  // ==========================================

  $("#rent_venta").text(formatNum(venta, '$'));
  $("#rent_costo").text(formatNum(costo, '$'));
  $("#rent_utilidad").text(formatNum(utilidad, '$'));


  // ==========================================
  // INDICADORES
  // ==========================================

  $("#rent_margen").text(`${margen.toFixed(2)}%`);
  $("#rent_markup").text(`${markup.toFixed(2)}%`);

  $("#progress_margen")
    .css("width", `${Math.min(Math.max(margen, 0), 100)}%`);

  $("#progress_markup")
    .css("width", `${Math.min(Math.max(markup, 0), 100)}%`);


  // ==========================================
  // COMPARACIÓN PP
  // ==========================================

  $("#rent_venta_normal").text(formatNum(venta, '$'));
  $("#rent_venta_pp").text(formatNum(ventaPP, '$'));

  $("#rent_utilidad_normal").text(formatNum(utilidad, '$'));
  $("#rent_utilidad_pp").text(formatNum(utilidadPP, '$'));

  $("#rent_margen_normal").text(`${margen.toFixed(2)}%`);
  $("#rent_margen_pp").text(`${margenPP.toFixed(2)}%`);

  $("#rent_markup_normal").text(`${markup.toFixed(2)}%`);
  $("#rent_markup_pp").text(`${markupPP.toFixed(2)}%`);


  // ==========================================
  // DIFERENCIAS
  // ==========================================

  const difVenta = ventaPP - venta;
  const difUtilidad = utilidadPP - utilidad;
  const difMargen = margenPP - margen;
  const difMarkup = markupPP - markup;


  $("#rent_dif_venta").text(formatNum(difVenta, '$'));
  $("#rent_dif_utilidad").text(formatNum(difUtilidad, '$'));
  $("#rent_dif_margen").text(`${difMargen.toFixed(2)}%`);
  $("#rent_dif_markup").text(`${difMarkup.toFixed(2)}%`);


  // ==========================================
  // GRÁFICA
  // ==========================================

  const ctx = document
    .getElementById("graficaRentabilidad")
    .getContext("2d");


  if (graficaRentabilidad) {
    graficaRentabilidad.destroy();
  }


  graficaRentabilidad = new Chart(ctx, {

    type: 'bar',

    data: {
      labels: [
        'Venta',
        'Costo',
        'Utilidad'
      ],

      datasets: [{
        data: [
          venta,
          costo,
          utilidad
        ],

        backgroundColor: [
          '#0d6efd',
          '#ffc107',
          '#198754'
        ],

        borderRadius: 6,

        borderWidth: 0
      }]
    },

    options: {

      responsive: true,

      maintainAspectRatio: false,

      plugins: {

        legend: {
          display: false
        },

        tooltip: {
          callbacks: {
            label: function (context) {
              return formatNum(context.raw, '$');
            }
          }
        }
      },

      scales: {

        y: {
          beginAtZero: true,

          ticks: {
            callback: function (value) {
              return formatNum(value, '$');
            }
          }
        },

        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });
};

const consultarKardex = async () => {
  try {
    const data = {
      link: '../models/RptKardex.php',
      id_referencia: parseInt($("#idReferencia").val(), 10),
      fecha_inicial: $("#fecha_inicial").val(),
      fecha_final: $("#fecha_final").val(),
      id_bodega: $("#bodegas").val()
    };

    const resp = await enviarPeticion(data);

    $("#resultado-inventario").html(`
        <table id="kardex" class="table table-sm table-bordered table-hover table-striped">
          <thead class="table-primary">
            <tr>
              <th>Fecha / Hora</th>
              <th>Movimiento</th>
              <th>Cantidad</th>
              <th>Saldo</th>
              <th>Costo</th>
              <th>Precio</th>
              <th>Vencimiento / Lote</th>
              <th>Tipo / Numero</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
    `);
    let tr = '';

    if (!resp || resp.length === 0) {
      tr = `
        <tr>
          <td colspan="8" class="text-center text-muted py-3">
            <i class="fa-solid fa-circle-info me-1"></i>
            No se encontraron resultados para los filtros seleccionados.
          </td>
        </tr>
      `;
      $("#kardex tbody").html(tr);
      return;
    }


    const totals = {
      entradas: 0,
      salidas: 0,
      movimientos: resp.length,
      saldoInicial: resp[0]?.saldo_inicial ?? 0,
      saldoFinal: resp[resp.length - 1]?.saldo_acumulado ?? 0
    };


    resp.forEach((element) => {
      const cantidad = parseFloat(element.cantidad) || 0;
      if (cantidad > 0) {
        totals.entradas += cantidad;
      } else {
        totals.salidas += Math.abs(cantidad);
      }
    });



    resp.forEach((element) => {
      const cantidad = parseFloat(element.cantidad) || 0;
      const color_cantidad = cantidad > 0 ? 'text-success' : 'text-danger';
      tr += `<tr>
            <td>${(element.fecha)}</td>
            <td>${element.movimiento}</td>
            <td class="${color_cantidad} text-center">${element.cantidad}</td>
            <td class="text-primary text-center"><b>${element.saldo_acumulado}</b></td>
            <td>${formatNum(element.costo, '$')}</td>
            <td>${formatNum(element.precio, '$')}</td>
            <td>${element.vmcto} / ${element.lote}</td>
            <td>${element.tipo} / ${element.numero}</td>
      </tr>`;
    });
    $("#kardex tbody").html(tr);
  } catch (e) {
    console.error("❌ Error en consultarInventario:", e);
    $("#resultado-inventario").html(
      ShowAlert("Error interno de la aplicación", "error")
    );
    dissminSwal();
  }
};

const abrirComportamiento = async () => {
  const idReferencia = parseInt($("#idReferencia").val(), 10);
  const codigo = $("#txtCodigo").val();
  const descripcion = $("#txtDescripcion").val();
  const estado = $("#activo").is(":checked") ? "on" : "off";
  const costoU = $("#costo_ultimo").val();
  const costoP = $("#costo_promedio").val();

  if (estado === "on") {
    $("#info_estado")
      .removeClass("bg-danger")
      .addClass("bg-success")
      .text("Activo");
  } else {
    $("#info_estado")
      .removeClass("bg-success")
      .addClass("bg-danger")
      .text("Inactivo");
  }
  $("#info_costo_ultimo").text(costoU);
  $("#info_costo_promedio").text(costoP);
  $("#info_codigo").text(codigo);
  $("#info_descripcion").text(descripcion);


  if (isNaN(idReferencia) || idReferencia <= 0) {
    Swal.fire('Alerta', 'Debe cargar una referencia para ver su auditoria.', 'warning');
    return;
  }
  try {
    showLoadingWithTetrisLogo('Consultando comportamiento', 'ISA Software');
    await consultarKardex();
    await consultarComportamiento();
    await consultarProveedores();
    await consultarRentabilidad();
    $("#modalComportamientoProducto").modal("show");
    dissminSwal();
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'No se pudo consultar el comportamiento de la referencia.', 'error');
  }
}

const abrirAuditoriaReferencia = async () => {
  const idReferencia = parseInt($("#idReferencia").val(), 10);

  if (isNaN(idReferencia) || idReferencia <= 0) {
    Swal.fire('Alerta', 'Debe cargar una referencia para ver su auditoria.', 'warning');
    return;
  }

  try {
    showLoadingWithTetrisLogo('Consultando auditoria', 'ISA Software');

    const resp = await enviarPeticion({
      link: link_,
      op: 's_auditoria_referencia',
      id_referencia: idReferencia
    });

    $("#auditoriaReferenciaLabel").text(`#${idReferencia}`);

    if (resp.error) {
      $("#alertAuditoriaReferencia").html(ShowAlert(resp.message || 'Error al consultar auditoria', 'danger'));
      $("#tbodyAuditoriaReferencia").html('');
      $("#modalAuditoriaReferencia").modal('show');
      dissminSwal();
      return;
    }

    if (!resp.data || resp.data.length === 0) {
      $("#alertAuditoriaReferencia").html(ShowAlert('No hay registros de auditoria para esta referencia.', 'warning'));
      $("#tbodyAuditoriaReferencia").html('');
      $("#modalAuditoriaReferencia").modal('show');
      dissminSwal();
      return;
    }

    $("#alertAuditoriaReferencia").html('');
    auditoriaReferenciaData = resp.data;
    $("#filtroAuditoriaAccion").val('');
    $("#filtroAuditoriaDesde").val('');
    $("#filtroAuditoriaHasta").val('');
    $("#filtroAuditoriaUsuario").val('');
    $("#filtroAuditoriaTexto").val('');
    aplicarFiltrosAuditoria();
    $("#modalAuditoriaReferencia").modal('show');
    dissminSwal();
  } catch (e) {
    dissminSwal();
    console.error(e);
    Swal.fire('Error', 'No se pudo consultar la auditoria de la referencia.', 'error');
  }
}

const addCategorias = (categoria) => {
  getCategorias(categoria);
  $("#txtIdCategoria").val(categoria);
  $("#modalCategoriaTitle").html(`Modificación Categoria #${categoria}`);
  $("#modalCategoria").modal('show');
}

const saveCategoria = async () => {
  let idCat = $("#txtIdCategoria").val().trim();
  let codigo = $("#txtCodigoCategoria").val().trim();
  let descripcion = $("#txtDescCategoria").val().trim();

  if (codigo === '') {
    initToast('bg-danger', 'Error!', 'El campo codigo no puede estar vacio', 'danger');
    $("#txtCodigoCategoria").focus();
    return;
  }
  if (descripcion === '') {
    initToast('bg-danger', 'Error!', 'El campo descripcion no puede estar vacio', 'danger');
    $("#txtDescCategoria").focus();
    return;
  }
  if (idCat === '') {
    initToast('bg-danger', 'Error!', 'El campo id no puede estar vacio', 'danger');
    return;
  }


  try {
    const dataSend = {
      link: link_,
      op: 'g_categoria',
      id: idCat,
      codigo: codigo,
      descripcion: descripcion
    };
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion(dataSend);
        if (!resp.error) {
          initToast('bg-success', 'Correcto!', resp.message, 'success');
          getCategorias(idCat);
          $("#txtCodigoCategoria, #txtDescCategoria").val('');
        } else {
          initToast('bg-danger', 'Error!', resp.message, 'danger');
        }
      }
    });
  } catch (e) {
    console.log(e);
  }

}

const deleteCategorias = async (id, id_categoria) => {
  try {
    const dataSend = {
      link: link_,
      op: 'd_categoria',
      id: id,
      id_categoria: id_categoria
    };
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion(dataSend);
        if (!resp.error) {
          initToast('bg-success', 'Correcto!', resp.message, 'success');
          getCategorias(id_categoria);
          $("#txtCodigoCategoria, #txtDescCategoria").val('');
        } else {
          initToast('bg-danger', 'Error!', resp.message, 'danger');
        }
      }
    });
  } catch (e) {
    console.error(e);
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }

}

const getCategorias = async (id) => {

  try {
    const dataSend = {
      link: link_,
      op: 's_categoria',
      id: id
    };
    const resp = await enviarPeticion(dataSend);

    if (resp.data.length > 0) {
      $('#tbCategorias tbody').html('');
      resp.data.forEach((d) => {
        // Crear una nueva fila
        let newRow = `
          <tr>
            <td>${d.id}</td>
            <td>${d.codigo}</td>
            <td>${d.descripcion}</td>
            <td align="center"><button class="btn btn-sm btn-outline-danger" onclick="deleteCategorias(${d.id},${id})"><i class="fa-regular fa-trash-can"></i></button></td>
          </tr>`;

        $('#tbCategorias tbody').append(newRow);

      });
      getCategoriasSelect(id);
    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

const getCategoriasSelect = async (id) => {
  try {
    const dataSend = {
      link: link_,
      op: 's_categoria',
      id: id
    };
    const resp = await enviarPeticion(dataSend);

    if (resp.data.length > 0) {
      let option = ``;
      resp.data.forEach((d) => {
        option += `<option value="${d.id}">${d.codigo} - ${d.descripcion}</option>`;
      });
      $("#slcCategoria" + id).html(option);
    }
  } catch (e) {
    console.error(e); // Agrega manejo de errores
    Swal.fire('Error!', 'Un error ha ocurrido, por favor notifique a su administrador.', 'error');
  }
}

const deleteCallback = (data) => {
  try {
    showConfirmation().then(async (confirmed) => {
      if (confirmed) {
        const resp = await enviarPeticion({
          link: link_,
          op: 'd_referencia',
          id: data.id
        });
        if (resp.error) {
          Swal.fire("Error", resp.message, "error");
          return;
        }
        await listarReferencias();
        Swal.fire("Ok", resp.message, "success");
        return;
      }
    });
  } catch (e) {
    Swal.fire("Error", "Se genero un error interno en el sistema", "error");
  }

}

const editCallback = async (data) => {
  getReferencia(data.codigo);
  selectedTab('tabs', 'navs-creacion');
}

const listarReferencias = async () => {
  showLoadingWithTetrisLogo('Buscando información', 'ISA Software');
  try {
    const data = {
      link: link_,
      op: 'listar_referencias',
      filtro: $("#filtro").val(),
      estado: $("#estado_filter").val() || 1,
      LimiteRegistros : 0
    }

    const resp = await enviarPeticion(data);

    const referencias = Array.isArray(resp.data) ? resp.data : [];

    if (referencias.length === 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      dissminSwal();
      return;
    }

    const dataTable = referencias.map((item) => ({
      ...item,
      estado_label: Number(item.activo) === 1 ? '<span class="badge bg-label-success"><i class="fa-regular fa-circle-check"></i> Activo</span>'  : 
                                                '<span class="badge bg-label-danger"><i class="fa-regular fa-circle-xmark"></i> Inactivo</span>'
    }));

    $("#result").html(`<table id="tb_referencias" class="table table-sm table-borderless table-hover table-striped" width="100%">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('tb_referencias',
      dataTable,
      ['codigo', 'descripcion', 'descripcion_larga', 'estado_label'],
      ['Código', 'Descripcion', 'Descripcion Larga', 'Estado'],
      editCallback,
      deleteCallback,
      false,
      '',
      '',
      true);
    dissminSwal();
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    dissminSwal();
  }
}

const validaConversion = () => {
  // Se comenta temporalmente porque no se le encuentra uso Cesar Arroyo 2025-10-28
  const value = parseFloat($('#txtConversion').val());
  if (isNaN(value) || value <= 0) {
    $('#txtConversion').val(1);
  }
}

const marcas = async () => {
  const resp = await enviarPeticion({
    link: '../models/Marcas.php',
    op: 'listar',
    filtro: ''
  });
  if (resp.data.length == 0) {
    return;
  }
  let p = ``
  resp.data.forEach(item => {
    p += `<option value="${item.id}">${item.descripcion}</option>`
  });

  $("#slcMarcas").html(p);
  $('#slcMarcas').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    language: {
      noResults: function () {
        return "No se encontraron resultados";
      }
    }
  });
}

const ultimoCodigo = async () => {
  const resp = await enviarPeticion({
    link: '../models/Referencias.php',
    op: 'ultimo_codigo'
  })

  $("#ultimo_codigo").val(resp.codigo)
}

const listarPerfiles = async () => {
  try {
    const data = {
      link: '../models/perfilesContables.php',
      op: 'listar-todos'
    }
    const resp = await enviarPeticion(data);
    let option = ``;
    resp.data.forEach((d) => {
      option += `<option value="${d.id}">${d.descripcion}</option>`;
    });
    $("#slcPerfilContable").html(option);
    await getIva();
  } catch (e) {
    console.log(e);
  }
}

const descargarExcel = (url, nombre = `Archivo.xlsx`) => {
  var a = document.createElement('a');
  a.href = url;
  a.download = nombre;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

const cargarExcel = async (file) => {
  showLoadingWithTetrisLogo('Cargando datos de Excel', 'ISA Software');
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
      raw: false // <-- fuerza conversión a texto
    });
    $('#tableContainer').empty();
    var table = $(`<table class="table table-sm table-hover table-striped" id="ref-importadas_">
                       <thead class="table-primary">
                           <tr class="text-primary">
                                <th>Código</th>
                                <th>Descripción Corta</th>
                                <th>Descripción Larga</th>
                                <th>Costo Unitario</th>
                                <th>Maneja Lote/Vmcto</th>
                                <th>Tipo Lista</th>
                                <th>Precio/Rentabilidad</th>
                                <th>Tipo</th>
                                <th>IVA</th>
                                <th>Código de Barra / EAN</th>
                                <th>UND2</th>
                                <th>CANT2</th>
                                <th>UND3</th>
                                <th>CANT3</th>
                                <th>UND4</th>
                                <th>CANT4</th>
                            </tr>
                        </thead>
                    </table>`);

    var tbody = $('<tbody></tbody>');
    jsonData.slice(1).forEach(function (row) {
      arrayRef.push({
        codigo: String(row[0] || ''), // Primera columna: Índice 0 (Código)
        descripcionCorta: row[1], // Segunda columna: Índice 1 (Descripción Corta)
        descripcionLarga: row[2], // Tercera columna: Índice 2 (Descripción Larga)
        costoUnitario: row[3], // Cuarta columna: Índice 3 (Costo Unitario)
        manejaLoteVmcto: row[4], // Quinta columna: Índice 4 (Maneja Lote/Vmcto)
        tipoLista: row[5], // Sexta columna: Índice 5 (Tipo Lista)
        precioRentabilidad: row[6], // Séptima columna: Índice 6 (Precio / Rentabilidad)
        tipo: row[7], // Octava columna: Índice 7 (Tipo)
        iva: row[8], // Novena columna: Índice 8 (IVA)
        codigoBarraEAN: row[9], // Décima columna: Índice 9 (Codigo de barra / EAN)
        und2: row[10],
        can2: row[11],
        und3: row[12],
        can3: row[13],
        und4: row[14],
        can4: row[15]
      });
      var dataRow = $('<tr></tr>');
      row.forEach(function (cell) {
        dataRow.append('<td>' + cell + '</td>');
      });
      tbody.append(dataRow);
    });
    table.append(tbody);
    $('#tableContainer').append(table);
    dissminSwal();
  };
  reader.readAsArrayBuffer(file);
}

const limpiarImport = () => {
  $('#tableContainer').empty();
  arrayRef = [];
  $('#txtFileUp').val('');
}

const cargarImportados = async () => {
  showLoadingWithTetrisLogo('Cargando datos de Excel', 'ISA Software');

  try {
    const resp = await enviarPeticion({
      link: '../models/Referencias.php',
      op: 'cargar_importados',
      datos: JSON.stringify(arrayRef)
    });
    const nuevosCount = resp.datos.nuevos.length;
    const existentesCount = resp.datos.existentes.length;
    const erroresCount = resp.datos.errores.length;
    const tablaHTML = ` <table class="table">
                            <thead>
                                <tr>
                                    <th>Tipo de Registro</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Registros Nuevos</strong></td>
                                    <td>${nuevosCount}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registros Existentes</strong></td>
                                    <td>${existentesCount}</td>
                                </tr>
                                <tr>
                                    <td><strong>Registros con Error</strong></td>
                                    <td>${erroresCount}</td>
                                </tr>
                            </tbody>
                        </table> `;

    Swal.fire({
      title: resp.message,
      html: tablaHTML,
      icon: 'info',
      confirmButtonText: 'Aceptar'
    });
    limpiarImport();
  } catch (e) {
    console.log(e);
    Swal.fire('Error interno', 'Se ha presentado un error, valide e intente nuevamente', 'error')
  }


}
/**ubicaciones */

const ubicaciones = async () => {
  const resp = await enviarPeticion({
    link: '../models/Ubicaciones.php',
    op: 'listar',
    filtro: ''
  });

  if (!resp || resp.data.length === 0) {
    return;
  }

  let opciones = '';

  resp.data.forEach(item => {
    opciones += `<option value="${item.id}">${item.ubicacion}</option>`;
  });

  // Asigna las opciones al select
  $("#ubicacion").html(opciones);
  $('#ubicacion').select2({
    theme: 'bootstrap-5',
    allowClear: true,
    language: {
      noResults: function () {
        return "No se encontraron resultados";
      }
    }
  });

}

const listarAlmacenes = async () => {
  try {
    const data = await getRol();
    const resp = await bodegasUsuarios();
    let options = '';
    let id_rol = parseInt(data.rol);

    if (id_rol == 4) {
      options = `<option value="0">Todas las bodegas</option>`;
    }
    resp.sort((a, b) => a.bodega.localeCompare(b.bodega));
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
  } catch (e) {
    console.error(e)
    Swal.fire("Error", "Error al cargar las bodegas", "error");
  }
}
/**ubicaciones */
$(async function () {
  // Configuración de Dropzone para un elemento específico
  var myDropzone = new Dropzone('.dropzone', {
    url: '../../assets/img/referencias',
    maxFiles: 4, // El número máximo de archivos que se pueden cargar
    maxFilesize: 2, // Tamaño máximo de archivo en MB
    acceptedFiles: "image/*",
    addRemoveLinks: true,
    dictDefaultMessage: "Arrastra los archivos aquí para subirlos",
    dictRemoveFile: "Eliminar archivo",
    dictCancelUpload: "Cancelar carga",
    dictFileTooBig: "El archivo es demasiado grande. El tamaño máximo permitido es de 5 MB.",
    dictInvalidFileType: "Archivo invalido, solo se aceptan imagenes",
    dictMaxFilesExceeded: "Has alcanzado el límite de archivos permitidos. Solo puedes subir hasta 4 archivos.",
    init: function () {
      this.on("addedfile", function (file) {
        arrImages.push(file);
      });
      this.on('removedfile', function (file) {
        let i = arrImages.indexOf(file);
        if (i > -1) {
          arrImages.splice(i, 1);
        }
      });
    }
  });

  fechaActual([['fecha_inicial', 'fecha_final']]);

  await getIcui();
  await llenarSelectListas();
  await tipoListas();
  await marcas();
  await listarPerfiles();
  await listarAlmacenes();
  ubicaciones();
  dissminSwal();

  $('#btnGuardar').on('click', function () {
    const form = document.getElementById('formReferencias');

    if (!form) {
      return;
    }

    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    let data = formatearArrayRequest($(form).serializeArray());

    if (data.activo == 'on') {
      data.activo = 1;
    } else {
      data.activo = 0;
    }
    if ($("#comision").is(':checked')) {
      data.comision = 'S';
    } else {
      data.comision = 'N';
    }

    guardar(data);
  });

  var forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      event.stopPropagation();
      return false;
    }, false);
  });

  $("#slcICUI").change(function () {
    const valor = $.trim($("#slcICUI option:selected").text());
    if (valor > 0) {
      $("#txtIBUA").attr("disabled", true)
    } else {
      $("#txtIBUA").attr("disabled", false)
    }
  });

  $("#txtIBUA").keyup(function () {
    const ibua = unformatNum($(this).val());

    if (isNaN(parseFloat(ibua))) {
      $("#slcICUI").attr("disabled", false)
      return;
    }

    if (ibua > 0) {
      $("#slcICUI").attr("disabled", true)
    } else {
      $("#slcICUI").attr("disabled", false)
    }
  })
  $(".asigar-precio").on('input', function () {

    const id = $(this).attr("id");

    // Si cambia la lista → resetear todo
    if (id === 'slcLista') {

      $("#slcTipoLista").val('PF');

      for (let i = 1; i <= 4; i++) {
        $("#txtRentabildad" + (i === 1 ? '' : i)).val(0);
        $("#textPrecioSinImp" + (i === 1 ? '' : i)).val('$0');
        $("#textPrecioConImp" + (i === 1 ? '' : i)).val('$0');
      }

      return;
    }

    // Detectar qué rentabilidad cambió
    let numero = 1;

    if (id.includes('2')) numero = 2;
    if (id.includes('3')) numero = 3;
    if (id.includes('4')) numero = 4;

    const rentInput = $("#txtRentabildad" + (numero === 1 ? '' : numero));
    const precioSinInput = $("#textPrecioSinImp" + (numero === 1 ? '' : numero));
    const precioConInput = $("#textPrecioConImp" + (numero === 1 ? '' : numero));

    const rentabilidad = unformatNum(rentInput.val()) || 0;

    const cst_prom = unformatNum($("#costo_promedio").val()) || 0;
    const cst_ult = unformatNum($("#costo_ultimo").val()) || 0;

    const tipo = $("#slcTipoLista").val();
    const ivaPercent = parseFloat($("#slcIva option:selected").text()) || 0;

    let precioSinIva = 0;
    let precioConIva = 0;

    if (tipo === 'PF') {

      precioConIva = rentabilidad;
      precioSinIva = precioConIva / (1 + (ivaPercent / 100));

    } else if (tipo === 'CP') {

      if (rentabilidad > 0)
        precioSinIva = cst_prom * (1 + rentabilidad / 100);

      precioConIva = precioSinIva * (1 + (ivaPercent / 100));

    } else { // CU

      if (rentabilidad > 0)
        precioSinIva = cst_ult * (1 + rentabilidad / 100);

      precioConIva = precioSinIva * (1 + (ivaPercent / 100));
    }

    precioSinInput.val(formatNum(precioSinIva.toFixed(0), '$'));
    precioConInput.val(formatNum(precioConIva.toFixed(0), '$'));

  });

  const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
    getReferencia(rowData.codigo);
  }, {
    check_stock: false
  });

  let buscandoReferencia = false;
  let enterEnCodigo = false;

  $("#txtCodigo").on("keydown", async function (e) {
    if (e.key !== "Enter") return;
    e.preventDefault();
    e.stopImmediatePropagation();
    // Marcamos que la salida del campo fue provocada por Enter
    enterEnCodigo = true;
    if (buscandoReferencia) return;

    const filtro = $.trim(this.value);
    if (!filtro) return;
    try {
      buscandoReferencia = true;
      $("#busqueda-descripcion").val(filtro);
      const resp = await modalBusquedaRef.getRecords({
        data: filtro,
        cod_barras: false
      });
      const nregistros = modalBusquedaRef.getNrecords();
      if (!nregistros) return;
      if (nregistros === 1 && Array.isArray(resp) && resp[0]) {
        await getReferencia(resp[0].codigo);
      } else {
        modalBusquedaRef.show();
        setTimeout(() => {
          $("#busqueda-descripcion").focus();
        }, 200);
      }
    } catch (error) {
      console.error("Error en búsqueda de referencia:", error);
    } finally {
      buscandoReferencia = false;
      // Liberamos después de terminar el proceso
      setTimeout(() => {
        enterEnCodigo = false;
      }, 100);


    }
  });
  $("#txtCodigo").on("input", function () {
    const valor = $.trim(this.value);
    if (!valor && !buscandoReferencia) {
      limpiar(false);
    }
  });

  $("#txtCodigo").on("focusout", async function () {
    // Si el focusout fue provocado por Enter,
    // no hacemos nada aquí.
    if (enterEnCodigo || buscandoReferencia) {
      return;
    }
    const valor = $.trim(this.value);
    if (valor !== '') {
      try {
        buscandoReferencia = true;
        await getReferencia(valor);
      } catch (error) {
        console.error("Error al consultar referencia:", error);
      } finally {
        buscandoReferencia = false;
      }
    } else {
      limpiar(false);
    }
  });

  $("#btnBusquedaAvanzada").on('click', function () {
    modalBusquedaRef.show();
    $("#busqueda-descripcion").val($("#txtCodigo").val());
    setTimeout(() => {
      $("#busqueda-descripcion").focus();
    }, 200);
  });

  $("#guardar-marca").click(function () {

    const form = document.getElementById('FormCrear');
    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
      }
      let data = formatearArrayRequest($(this).serializeArray());

      data.link = '../models/Marcas.php';
      data.op = 'agregar';

      try {
        const resp = await enviarPeticion(data);

        if (resp.error) {
          dissminSwal();
          Swal.fire("Error", "Se genero un error al guardar", "error");
          return;
        }
        dissminSwal();

        Swal.fire("Ok", resp.message, "success");
        marcas();

      } catch (e) {
        dissminSwal();
        console.error(e);
        Swal.fire("Error", "Error interno de la aplicacion", "error");
      }

    }, false);

  })

  $("#btn-add-marca").click(function () {
    $("#modalMarcas").modal("show")
  });
  $("#btnLimpiar").click(() => {
    limpiar();
    getIva();
  });
  $("#btnAuditoria").click(() => {
    abrirAuditoriaReferencia();
  });
  $("#btnComportamiento").click(() => {
    abrirComportamiento();
  });
  $("#filtroAuditoriaAccion, #filtroAuditoriaDesde, #filtroAuditoriaHasta").on('change', () => {
    aplicarFiltrosAuditoria();
  });
  $("#filtroAuditoriaUsuario, #filtroAuditoriaTexto").on('input', () => {
    aplicarFiltrosAuditoria();
  });
  $("#btnLimpiarFiltrosAuditoria").on('click', () => {
    $("#filtroAuditoriaAccion").val('');
    $("#filtroAuditoriaDesde").val('');
    $("#filtroAuditoriaHasta").val('');
    $("#filtroAuditoriaUsuario").val('');
    $("#filtroAuditoriaTexto").val('');
    aplicarFiltrosAuditoria();
  });
  $(document).on('click', '.btnVerDetalleAuditoria', function () {
    const index = parseInt($(this).data('index'), 10);
    const row = auditoriaReferenciaData[index];
    if (!row) {
      return;
    }

    const html = construirTablaDetalleAuditoria(row.detalle);
    $("#detalleAuditoriaContainer").html(html);
    $("#modalDetalleAuditoria").modal('show');
  });
  $('#btnAddLista').on('click', () => {
    addLista();
  });
  $('#btnAddEan').on('click', () => {
    addEan();
  });
  $('#tbListas').on('click', '.btnEliminar', function () {
    eliminarLista(this);
  });
  $('#tbCodBarras').on('click', '.btnEliminar', function () {
    eliminarEan(this);
  });
  $('#tbCodBarras').on('click', '.btnEliminar', function () {
    $(this).closest('tr').remove();
  });
  const elements = document.querySelectorAll('.moneda');
  elements.forEach((element) => {
    new Cleave(element, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: '.',
      numeralDecimalScale: 2,
    });
  });

  $('.btnCat').on('click', function () {
    const categoria = $(this).attr('id').replace('btnAddCat', '');
    addCategorias(categoria);
  });
  $("#saveCategoria").on('click', function () {
    saveCategoria();
  });
  $("#buscar").click(function () {
    listarReferencias();
  });
  $("#estado_filter").change(function () {
    listarReferencias();
  });
  $("#filtro").on("keydown", async function (e) {
    if (e.key !== "Enter") return;

    listarReferencias();
  });

  // Validar al cambiar el valor
  $('#txtConversion').focusout(function () {
    validaConversion();
  });

  await ultimoCodigo();


  $('#btn-plantilla').click(function () {
    descargarExcel('https://thecyctechnology.com/cyc/plantillas-demo/plantilla-referencias.xlsx', 'plantilla-referencias.xlsx');
  });

  $('#txtFileUp').on('change', async function (event) {
    var file = event.target.files[0];
    if (file) {
      cargarExcel(file);
    }
  });

  $('#btnClean').click(function () {
    limpiarImport();
  });

  $('#btnUp').click(function () {
    if (arrayRef.length > 0) {
      cargarImportados();
    } else {
      Swal.fire('No hay datos que importar', 'Por favor carque un archivo válido.', 'warning');
    }
  });

  $("#slcPerfilContable").change(function () {
    getIva();
  });

  $('#costo_promedio, #costo_ultimo').on('input', function () {
    recalcularTablaListas();
  });

  $(document).on('input', '.precio-edit', function () {

    const fila = $(this).closest('tr');
    const lista = fila.find('td:eq(0)').text();
    const campo = $(this).data('col');
    const valor = $(this).val().replace(/,/g, '');

    const index = arrayListas.findIndex(item => item.lista === lista);

    if (index !== -1) {
      arrayListas[index][campo] = valor;
    }

    actualizarValoresCalculadosFila(fila);
  });

  // =========================
  // EVENTO BOTÓN VIDEO YOUTUBE
  // =========================

  $("#btn-video-tutorial").click(function () {
    const videoURL = "https://youtu.be/3pBSaD1X-Ok";
    const videoID = videoURL.split('/').pop().split('?')[0];
    const embedURL = `https://www.youtube.com/embed/${videoID}`;

    $("#videoFrameTutorial").attr("src", embedURL);

    const modal = new bootstrap.Modal(document.getElementById('modalVideoTutorial'));
    modal.show();
  });

  // Detener video al cerrar el modal
  $("#modalVideoTutorial").on('hidden.bs.modal', function () {
    $("#videoFrameTutorial").attr("src", "");
  });



  $("#btnBuscar").click(async function () {
    try {
      showLoadingWithTetrisLogo('Consultando comportamiento', 'ISA Software');
      await consultarKardex();
      await consultarComportamiento();
      await consultarProveedores();
      await consultarRentabilidad();
      $("#modalComportamientoProducto").modal("show");
      dissminSwal();
    } catch (e) {
      dissminSwal();
      console.error(e);
      Swal.fire('Error', 'No se pudo consultar el comportamiento de la referencia.', 'error');
    }
  });
});
