// Variable global para timeout de actualización 
let timeoutActualizacion;
const id_modulo = '88';

const getMarcas = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'getMarcas'
    })
    // lo pinto en el select 
    let html = '<option value="">Seleccione una marca</option>';
    resp.forEach(marca => {
      html += `<option value="${marca.id}">${marca.descripcion}</option>`;
    }
    );
    $('#filtro_marca').html(html).select2({
      theme: 'bootstrap-5',
      language: 'es',
      placeholder: 'Seleccione una marca...'
    });

  } catch (error) {
    console.error('Error al obtener las marcas:', error);
  }
}

const getCategorias1 = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'getCategorias1'
    })

    // lo pinto en el select 
    let html = '<option value="">Seleccione una categoria</option>';
    resp.forEach(categoria => {
      html += `<option value="${categoria.id}">${categoria.descripcion}</option>`;
    }
    );
    $('#filtro_categoria1').html(html).select2({
      theme: 'bootstrap-5',
      language: 'es',
      placeholder: 'Seleccione una categoría...'
    });

  } catch (error) {
    console.error('Error al obtener las categorias:', error);
  }
}


const getCategorias2 = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'getCategorias2'
    })

    // lo pinto en el select
    let html = '<option value="">Seleccione una categoria</option>';
    resp.forEach(categoria => {
      html += `<option value="${categoria.id}">${categoria.descripcion}</option>`;
    }
    );
    $('#filtro_categoria2').html(html).select2({
      theme: 'bootstrap-5',
      language: 'es',
      placeholder: 'Seleccione una categoría...'
    });

  } catch (error) {
    console.error('Error al obtener las categorias:', error);
  }
}


const getCategorias3 = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'getCategorias3'
    })

    // lo pinto en el select
    let html = '<option value="">Seleccione una categoria</option>';
    resp.forEach(categoria => {
      html += `<option value="${categoria.id}">${categoria.descripcion}</option>`;
    }
    );
    $('#filtro_categoria3').html(html).select2({
      theme: 'bootstrap-5',
      language: 'es',
      placeholder: 'Seleccione una categoría...'
    });

  } catch (error) {
    console.error('Error al obtener las categorias:', error);
  }
}


const getCategorias4 = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'getCategorias4'
    })

    // lo pinto en el select
    let html = '<option value="">Seleccione una categoria</option>';
    resp.forEach(categoria => {
      html += `<option value="${categoria.id}">${categoria.descripcion}</option>`;
    }
    );
    $('#filtro_categoria4').html(html).select2({
      theme: 'bootstrap-5',
      language: 'es',
      placeholder: 'Seleccione una categoría...'
    });

  } catch (error) {
    console.error('Error al obtener las categorias:', error);
  }
}

const listarAlmacenes = async () => {
  try {
    const resp = await bodegasUsuarios();
    console.warn({ resp })
    if (resp.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#filtro_bodegas").html(options);

    // Inicializar Select2 para bodegas con múltiple selección
    $("#filtro_bodegas").select2({
      theme: 'bootstrap-5',
      language: 'es',
      allowClear: true,
      closeOnSelect: false,
      placeholder: 'Seleccionar bodegas...'
    });

    // Preseleccionar bodega de sesión si existe
    const bodegaSession = $("#bodega_session").val();
    if (bodegaSession) {
      $("#filtro_bodegas").val([bodegaSession]).trigger("change");
    }

    // Manejar checkbox "Seleccionar todas"
    $('#chk_seleccionar_todas_bodegas').on('change', function () {
      if ($(this).is(':checked')) {
        // Marcar todas las opciones excepto la primera (vacía)
        const todasLasBodegas = $("#filtro_bodegas option").map(function () {
          return $(this).val();
        }).get().filter(v => v !== '');
        $("#filtro_bodegas").val(todasLasBodegas).trigger("change");
      } else {
        // Desmarcar todas
        $("#filtro_bodegas").val([]).trigger("change");
      }
    });

    // Actualizar checkbox cuando se cambian las bodegas
    $('#filtro_bodegas').on('change', function () {
      const todasLasBodegas = $("#filtro_bodegas option").map(function () {
        return $(this).val();
      }).get().filter(v => v !== '');
      const bodegasSeleccionadas = $(this).val();

      if (bodegasSeleccionadas && bodegasSeleccionadas.length === todasLasBodegas.length) {
        $('#chk_seleccionar_todas_bodegas').prop('checked', true);
      } else {
        $('#chk_seleccionar_todas_bodegas').prop('checked', false);
      }
    });
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}

const generarSugerido = async () => {
  try {
    // Validar que al menos una bodega esté seleccionada (SOLO si NO está agrupado)
    const checkboxElement = $('#filtro_agrurar_productos');
    const estaAgrupado = checkboxElement.is(':checked') === true;
    let bodegasSeleccionadas = [];
    if (!estaAgrupado) {
      bodegasSeleccionadas = $('#filtro_bodegas').val();
      if (!bodegasSeleccionadas || bodegasSeleccionadas.length === 0) {
        Swal.fire('Alerta', 'Debe seleccionar al menos una bodega', 'warning');
        return;
      }
    }

    // ✅ NUEVA: Validar que se seleccione proveedor
    const nitProveedor = $('#id_tercero').val();
    if (!nitProveedor) {
      Swal.fire('Alerta', 'Debe seleccionar un proveedor antes de generar el sugerido', 'warning');
      return;
    }

    showLoadingWithTetrisLogo('Generando sugerido de compras...', 'ISA Software')


    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',
      op: 'generarSugerido',
      marca: $('#filtro_marca').val() || '',
      categoria1: $('#filtro_categoria1').val() || '',
      categoria2: $('#filtro_categoria2').val() || '',
      categoria3: $('#filtro_categoria3').val() || '',
      categoria4: $('#filtro_categoria4').val() || '',
      bodegas: JSON.stringify(bodegasSeleccionadas),  // Array como JSON (vacío si está agrupado)
      meses: $('#filtro_meses').val() || '3',
      dias: $('#filtro_dias').val() || '30',
      solo_rotacion: $('#filtro_solo_rotacion').is(':checked') ? 1 : 0,
      solo_sugerir: $('#filtro_solo_sugerir').is(':checked') ? 1 : 0,
      agruparProductos: estaAgrupado ? 1 : 0,
      nit_proveedor: nitProveedor,  // ✅ NUEVA: Pasar NIT del proveedor
      incluir_mes_actual: $('#filtro_mes_actual').val()
    });

    console.log('Respuesta de generarSugerido:', resp);

    dissminSwal();

    if (!resp.ok) {
      Swal.fire('Error', resp.error || 'Error al generar sugerido', 'error');
      console.error('Error:', resp);
      return;
    }
    mostrarResultadosEnDataTable(resp);

  } catch (e) {
    dissminSwal();
    console.error('Error:', e);
    Swal.fire('Error', 'Error interno de la aplicación: ' + e.message, 'error');
  }
};

// Función para exportar a Excel
const exportarAExcel = () => {
  try {
    if (!window.hotInstance || !window.dataOriginal) {
      Swal.fire('Alerta', 'No hay datos para exportar', 'warning');
      return;
    }

    const hot = window.hotInstance;
    const data = window.dataOriginal;
    const agrupado = data.agrupar_productos === 1 || data.agrupar_productos === true;

    // Obtener datos actuales de la tabla (filtrados o no) 
    const datosActuales = hot.getData();
    const numMeses = data.columnas_meses.length;

    // Crear encabezados dinámicamente 
    let headers = ['Código', 'Descripción'];

    if (!agrupado) {
      headers.push('Id bodega', 'Bodega', 'Bodega Principal');
    }

    headers = headers.concat([
      'Marca',
      'Unidad',
      'Conversión',
      ...data.columnas_meses,
      'Rot. Total',
      'Stock',
      'Stock Bodega Principal',
      'Costo Prom.',
      'Últ. Costo',
      'Promedio',
      'Rot. Diaria',
      `Sug. ${data.dias}d`,
      'Cantidad pendiente',
      'Cant Sug.',
      'Comprar?',
      'Cant pedir',
      'Cst compra',
      'Sub total',
      'Total cst. bodega',
      'Total cst. Bod. princ'
    ]);

    // Convertir datos para Excel con tipos correctos  
    const datosExcel = [];

    datosActuales.forEach((row, idx) => {
      const newRow = [];

      row.forEach((valor, colIdx) => {
        // Intentar convertir a número si es posible
        let valorFinal = valor;

        // Calcular offset: comenzando después de columnas de identidad
        const offsetNumerico = agrupado ? 5 : 7;  // Índice donde comienzan los meses

        // Columnas que deben ser numéricas
        if (colIdx >= offsetNumerico) {
          // Intentar parsear como número
          const num = parseFloat(valor);
          if (!isNaN(num) && valor !== '') {
            valorFinal = num;
          }
        }

        newRow.push(valorFinal);
      });

      datosExcel.push(newRow);
    });

    // Crear hoja de cálculo
    const ws = XLSX.utils.aoa_to_sheet([headers, ...datosExcel]);

    // Establecer anchos de columna
    const colWidths = [];
    headers.forEach((header, idx) => {
      if (idx === 1) { // Descripción
        colWidths.push({ wch: 40 });
      } else if (!agrupado && (idx === 2 || idx === 3)) { // Bodega, Bodega Principal
        colWidths.push({ wch: 25 });
      } else if ((!agrupado && idx === 4) || (agrupado && idx === 2)) { // Marca
        colWidths.push({ wch: 20 });
      } else {
        colWidths.push({ wch: 12 });
      }
    });
    ws['!cols'] = colWidths;

    // Dar formato a encabezados (fondo azul, texto blanco)
    const encabezadoStyle = {
      fill: { fgColor: { rgb: "FF6B63B5" } },
      font: { bold: true, color: { rgb: "FFFFFFFF" } },
      alignment: { horizontal: "center", vertical: "center" }
    };

    headers.forEach((header, idx) => {
      const cellRef = XLSX.utils.encode_col(idx) + '1';
      if (!ws[cellRef]) ws[cellRef] = {};
      ws[cellRef].s = encabezadoStyle;
    });

    // Formato para números (2 decimales)
    const offsetNumerico = agrupado ? 5 : 7;
    for (let row = 2; row <= datosExcel.length + 1; row++) {
      for (let col = offsetNumerico; col < headers.length - 1; col++) {
        const cellRef = XLSX.utils.encode_col(col) + row;
        if (ws[cellRef]) {
          ws[cellRef].z = '0.00';
        }
      }
    }

    // Crear libro de trabajo
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Sugerido Compras');

    // Generar nombre de archivo
    const fecha = new Date();
    const nombreArchivo = `Sugerido_Compras_${fecha.toISOString().slice(0, 10)}_${fecha.getTime()}.xlsx`;

    // Descargar
    XLSX.writeFile(wb, nombreArchivo);

    Swal.fire('Éxito', `Archivo "${nombreArchivo}" descargado correctamente`, 'success');

  } catch (e) {
    console.error('Error al exportar a Excel:', e);
    Swal.fire('Error', 'Error al exportar a Excel: ' + e.message, 'error');
  }
};

// Función para calcular y actualizar totales de la ORDEN DE COMPRA - OPTIMIZADA - FIXED
const actualizarTotalesOrdenCompra = (hot, dataOriginal) => {
  try {
    // ✅ FIX: Evitar múltiples timeouts usando una flag
    if (window.actualizandoTotales === true) {
      return;  // Salir si ya está actualizando
    }

    // Limpiar timeout anterior si existe
    if (window.timeoutTotalesOrden) {
      clearTimeout(window.timeoutTotalesOrden);
      window.timeoutTotalesOrden = null;
    }

    // ✅ Establecer flag para evitar llamadas concurrentes
    window.actualizandoTotales = true;

    window.timeoutTotalesOrden = setTimeout(() => {
      try {
        // ✅ Validar que hot y dataOriginal existan y sean válidos
        if (!hot || !dataOriginal || !hot.getData) {
          window.actualizandoTotales = false;
          return;
        }

        let totalItems = 0;
        let totalProductos = 0;
        let totalCosto = 0;

        // ✅ Obtener datos actuales GARANTIZANDO que sean válidos
        let tableData;
        try {
          tableData = hot.getData();
        } catch (e) {
          console.error('Error al obtener datos de la tabla:', e);
          window.actualizandoTotales = false;
          return;
        }

        if (!tableData || tableData.length === 0) {
          window.actualizandoTotales = false;
          return;
        }

        const numMeses = dataOriginal.columnas_meses ? dataOriginal.columnas_meses.length : 0;
        const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

        // ✅ ÍNDICES CORRECTOS PARA AMBOS MODOS - CON CANT PENDIENTE
        let indiceComprar, indiceCantPedir, indiceSubtotal;

        if (agrupado) {
          // MODO AGRUPADO: Código, Descripción, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
          indiceComprar = 15 + numMeses;
          indiceCantPedir = 16 + numMeses;
          indiceCostoCompra = 17 + numMeses;
          indiceSubtotal = 18 + numMeses;
        } else {
          // MODO NORMAL: Código, Descripción, IdBodega🔹, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
          indiceCantPendiente = 16 + numMeses;
          indiceComprar = 18 + numMeses;    // 🔹 Comprar
          indiceCantPedir = 19 + numMeses;  // 🔹 Cant Pedir
          indiceCostoCompra = 20 + numMeses; // 🔹 Costo Compra
          indiceSubtotal = 21 + numMeses;   // 🔹 Subtotal
        }

        // ✅ FOR LOOP MÁS ROBUSTO: Validar cada fila
        for (let i = 0; i < tableData.length; i++) {
          const row = tableData[i];
          if (!row || !Array.isArray(row)) continue;

          // ✅ Validar que los índices existen en la fila
          if (indiceComprar >= row.length || indiceCantPedir >= row.length || indiceSubtotal >= row.length) {
            console.warn(`⚠️ Índices fuera de rango en fila ${i}. Row length: ${row.length}, indices: {comprar:${indiceComprar}, cant:${indiceCantPedir}, subtotal:${indiceSubtotal}}`);
            continue;
          }

          const cantPedir = parseInt(row[indiceCantPedir]) || 0;
          const subtotal = parseFloat(row[indiceSubtotal]) || 0;

          // ✅ Solo sumar si hay cantidad a comprar (independiente de la columna Comprar?)
          if (cantPedir > 0) {
            totalItems += 1;
            totalProductos += cantPedir;
            totalCosto += subtotal;
          }
        }

        // ✅ Actualizar inputs SOLO si existen en el DOM
        const resumenItems = $('#resumen_items');
        const resumenProductos = $('#resumen_productos');
        const resumenCosto = $('#resumen_costo');
        const inputTotalCosto = $('#inputTotalCosto');
        const cardTotalesAcciones = $('#cardTotalesAcciones');

        if (resumenItems.length) resumenItems.val(totalItems);
        if (resumenProductos.length) resumenProductos.val(totalProductos);
        if (resumenCosto.length) resumenCosto.val('$' + totalCosto.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        if (inputTotalCosto.length) inputTotalCosto.val('$' + totalCosto.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

        if (cardTotalesAcciones.length) {
          if (totalItems > 0) {
            cardTotalesAcciones.slideDown(300);
          } else {
            cardTotalesAcciones.slideUp(300);
          }
        }

      } catch (innerError) {
        console.error('❌ Error en setTimeout de totales:', innerError);
      } finally {
        // ✅ SIEMPRE limpiar la flag
        window.actualizandoTotales = false;
      }

    }, 100);  // Aumentar delay a 100ms para más estabilidad

  } catch (e) {
    console.error('❌ Error en actualizarTotalesOrdenCompra:', e.message);
    window.actualizandoTotales = false;
  }
};
const mostrarResultadosEnDataTable = (data) => {
  console.log('Mostrando resultados en DataTable:', data);
  try {
    // Validar que existan registros
    if (!data.registros || data.registros.length === 0) {
      $('#dvSugerido').html(`
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
          <span>No se encontraron registros con los filtros seleccionados.</span>
        </div>
      `);
      $('[data-bs-target="#navs-resultados"]').tab('show');
      return;
    }

    // Detectar si está agrupando productos
    const agrupado = data.agrupar_productos === 1 || data.agrupar_productos === true;

    // Preparar datos para Handsontable
    const tableData = [];
    let totalCantidad = 0;
    let totalCosto = 0;

    data.registros.forEach(row => {
      const ultimoCosto = parseFloat(row.ultimo_costo_compra) || 0;
      const costoPromedio = parseFloat(row.costo_promedio) || 0;
      const costoCompra = parseFloat(row.costo_compra) || 0;  // ✅ Costo de compra del proveedor
      const rotacionDiaria = parseFloat(row.rotacion_diaria) || 0;
      const cantSugerida = Math.round(rotacionDiaria * data.dias);
      const stockBodegaPrincipal = parseFloat(row.stock_bodega_principal) || 0;
      const totalCstBodegaPrincipal = parseFloat(row.total_cst_bodega_principal) || 0;
      const totalCstBodega = parseFloat(row.total_cst_bodega) || 0;

      const rowData = [
        row.codigo,
        row.descripcion
      ];

      // Solo agregar bodega y bodega_principal si NO está agrupado
      if (!agrupado) {
        rowData.push(row.id_bodega);           // NUEVO: ID de bodega
        rowData.push(row.nombre_bodega);
        rowData.push(row.bodega_principal);
      }

      rowData.push(
        row.marca || '-',
        row.label_unidad || '-',
        row.conversion_compra || '-'
      );

      // Agregar valores de meses
      data.columnas_meses.forEach(mes => {
        const colKey = mes.toLowerCase().replace(/ /g, '_');
        rowData.push(row[colKey] || 0);
      });

      // Agregar valores de rotación y totales
      // ✅ FIX: usar las variables ya saneadas (con fallback a 0) en vez de
      // volver a parsear row.costo_promedio / row.ultimo_costo_compra crudos,
      // que producían "NaN" cuando el dato venía null/undefined.
      rowData.push(
        row.rotacion_total,
        row.stock,
        stockBodegaPrincipal,
        costoPromedio.toFixed(2),
        ultimoCosto.toFixed(2),
        row.promedio,
        rotacionDiaria.toFixed(2),
        `Sug. ${data.dias}d`,
        parseInt(row.cantidad_pendiente) || 0,  // Cantidad Pendiente
        cantSugerida,
        row.comprar
      );

      // COLUMNAS FINALES: Cant Pedir, Costo de Compra y Subtotal (Costo de Compra × Cant Pedir)
      const cantPedir = parseInt(row.cant_pedir) || 0;
      const subtotal = cantPedir > 0 ? parseFloat((costoCompra * cantPedir).toFixed(2)) : 0;

      rowData.push(
        cantPedir,
        costoCompra.toFixed(2),
        subtotal
      );

      rowData.push(
        totalCstBodegaPrincipal,
        totalCstBodega
      );

      tableData.push(rowData);
    });

    // Crear array de headers
    let headers = [
      'Código',
      'Descripción'
    ];

    // Solo agregar bodega y bodega_principal si NO está agrupado
    if (!agrupado) {
      headers.push('ID Bodega', 'Bodega', 'Bodega Principal');
    }

    headers = headers.concat([
      'Marca',
      'Unidad',
      'Conversión',
      ...data.columnas_meses,
      'Rot. Total',
      'Stock',
      'Stock Bodega Principal',
      'Costo Prom.',
      'Últ. Costo',
      'Promedio',
      'Rot. Diaria',
      `Sug. ${data.dias}d`,
      'Cant Pendiente',
      'Cant Sug.',
      'Comprar?',
      'Cant Pedir',
      'Costo de Compra',
      'Subtotal',
      'Total cst. bodega',
      'Total cst. Bod. princ'
    ]);


    // HTML para el contenedor con filtros de resultados
    let html = `
      <div class="alert alert-info mb-3 d-none">
        <strong>Período Analizado:</strong> ${data.meses} meses | 
        <strong>Sugerencia de compra por:</strong> ${data.dias} días |
        <strong>Productos a comprar:</strong> ${data.resumen.total_productos} |
        <strong>Costo Total Compra:</strong> $${parseFloat(data.resumen.costo_total_compra).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
      </div>
      
      <!-- Filtros en resultados -->
      <div class="row g-3 mb-3">`;

    // Solo mostrar filtro bodega si NO está agrupado
    if (!agrupado) {
      html += `
        <div class="col-md-2">
          <label class="form-label fw-semibold">Filtrar por Bodega</label>
          <select id="filtro_resultados_bodega" class="form-select form-select-sm">
            <option value="">Todas las bodegas</option>
          </select>
        </div>`;
    }

    html += `
        <div class="col-md-2">
          <label class="form-label fw-semibold">Filtrar por Marca</label>
          <select id="filtro_resultados_marca" class="form-select form-select-sm">
            <option value="">Todas las marcas</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">&nbsp;</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="filtro_resultados_sugerencia" value="1">
            <label class="form-check-label" for="filtro_resultados_sugerencia">
              Solo sugerencia
            </label>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">&nbsp;</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="filtro_resultados_sin_rotacion" value="1">
            <label class="form-check-label" for="filtro_resultados_sin_rotacion">
              Solo sin rotación
            </label>
          </div>
        </div>
        <div class="col-md-1">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-sm btn-outline-secondary w-100 mt-2" id="btnLimpiarFiltrosResultados" title="Limpiar filtros">
            <i class="fa-solid fa-undo"></i> Limpiar
          </button>
        </div>
        <div class="col-md-1">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-sm btn-success w-100 mt-2" id="btnExportarExcel" title="Exportar a Excel">
            <i class="fa-solid fa-file-excel"></i> Excel
          </button>
        </div>
      </div>

      <div id="sugeridoHandsontable" style="width: 100%; height: calc(100vh - 400px); overflow: auto;"></div>
      
      <!-- Preview del Array Generado -->
      <div id="previewArrayCompras" style="margin-top: 20px;"></div>
    `;

    // Insertar HTML en el DOM
    $('#dvSugerido').html(html);

    // Estilos para altura uniforme y sin envolvimiento
    $('<style>')
      .text(`
        #sugeridoHandsontable {
          line-height: 1;
        }
        #sugeridoHandsontable .htCore {
          line-height: 1;
        }
        #sugeridoHandsontable td {
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          height: 10px !important;
        }
        #sugeridoHandsontable th {
          height: 30px !important;
        }
        #sugeridoHandsontable td:nth-child(2),
        #sugeridoHandsontable th:nth-child(2) {
          background-color: #e8d5f2 !important;
          font-weight: 500;
        }
        .ht-cantidad-pendiente {
          background-color: #ffcccc !important;
          color: #8B0000 !important;
          font-weight: 600 !important;
        }
        #dvSugerido {
          display: flex;
          flex-direction: column;
        }
        #dvSugerido .row.g-3 {
          flex-shrink: 0;
        }
        #sugeridoHandsontable {
          flex-grow: 1;
          flex-shrink: 1;
          min-height: 400px;
        }
      `)
      .appendTo('head');

    // Esperar un poco para que el DOM esté listo
    setTimeout(() => {
      const container = document.getElementById('sugeridoHandsontable');

      if (!container) {
        console.error('Contenedor no encontrado');
        return;
      }

      // Crear configuración de columnas
      const columns = headers.map((header, index) => {
        const col = {
          title: header,
          type: 'text',
          readOnly: true
        };

        if (header === 'Cant Pedir') {
          col.type = 'numeric';
          col.readOnly = false;
          col.numericFormat = { pattern: '0' };
          col.className = 'ht-editable';
        }

        if (header === 'Cant Pendiente') {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '0' };
          col.className = 'ht-readonly-numeric ht-cantidad-pendiente';
        }

        if (header === 'Costo de Compra') {
          col.type = 'numeric';
          col.readOnly = false;
          col.numericFormat = { pattern: '$0.00', culture: 'es-CO' };
          col.className = 'ht-editable';
        }

        if (['Costo Prom.', 'Últ. Costo', 'Subtotal'].includes(header)) {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '$0.00', culture: 'es-CO' };
          col.className = 'ht-readonly-numeric';
        }

        if (['Total cst. bodega', 'Total cst. Bod. princ'].includes(header)) {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '$0,0.00', culture: 'es-CO' };
          col.className = 'ht-readonly-numeric';
        }

        if (header === 'Descripción') {
          col.width = 350;
        }

        return col;
      });

      // Inicializar Handsontable
      const hot = new Handsontable(container, {
        data: tableData,
        colHeaders: headers,
        columns: columns,
        rowHeaders: false,
        contextMenu: false,
        manualColumnResize: true,
        height: 'calc(100vh - 200px)',
        width: '100%',
        licenseKey: 'non-commercial-and-evaluation',

        fixedColumnsStart: 2,
        rowHeights: 25,

        renderAllRows: false,
        viewportRowRenderingOffset: 10,

        noNewRowsMode: true,
        outsideClickDeselects: true,
        autoRowSize: false,

        filters: true,
        // dropdownMenu: ['filter_by_value', 'filter_action_bar'],
        dropdownMenu: false,
        columnSorting: true,

        // EVENT LISTENER para cambios en la tabla
        afterChange: function (changes, source) {
          if (source === 'loadData' || source === 'calcData' || source === 'autofill') return;
          if (!changes || changes.length === 0) return;

          try {
            const numMeses = data.columnas_meses ? data.columnas_meses.length : 0;
            const agrupado = data.agrupar_productos === 1 || data.agrupar_productos === true;

            // ✅ FIX: índices recalculados según el orden real de columnas.
            // Modo agrupado -> Código, Descripción, Marca, Unidad, Conversión, ...meses,
            //   Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria,
            //   Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
            // Modo normal -> igual que arriba pero con IdBodega, Bodega, BodegaPpal
            //   insertadas después de Descripción (desplaza todo +3 en vez de +0).
            let indiceCantPendiente, indiceCantPedir, indiceCostoCompra, indiceSubtotal, indiceComprar;

            if (agrupado) {
              indiceCantPendiente = 13 + numMeses;
              indiceComprar = 15 + numMeses;
              indiceCantPedir = 16 + numMeses;
              indiceCostoCompra = 17 + numMeses;
              indiceSubtotal = 18 + numMeses;
            } else {
              indiceCantPendiente = 16 + numMeses;
              indiceComprar = 18 + numMeses;
              indiceCantPedir = 19 + numMeses;
              indiceCostoCompra = 20 + numMeses;
              indiceSubtotal = 21 + numMeses;
            }

            changes.forEach(([rowIndex]) => {
              try {
                const cant = parseInt(hot.getDataAtCell(rowIndex, indiceCantPedir)) || 0;
                const costo = parseFloat(hot.getDataAtCell(rowIndex, indiceCostoCompra)) || 0;
                const nuevoComprar = cant > 0 ? 'SÍ' : 'NO';
                hot.setDataAtCell(rowIndex, indiceComprar, nuevoComprar, 'calcData');
                const subtotal = cant > 0 ? parseFloat((costo * cant).toFixed(2)) : 0;
                hot.setDataAtCell(rowIndex, indiceSubtotal, subtotal, 'calcData');

              } catch (cellError) {
                console.warn(`⚠️ Error procesando fila ${rowIndex}:`, cellError);
              }
            });

            setTimeout(() => {
              actualizarTotalesOrdenCompra(hot, data);
            }, 50);

          } catch (handleError) {
            console.error('❌ Error en afterChange handler:', handleError);
          }
        }
      });

      // Guardar referencia global
      window.hotInstance = hot;
      window.dataOriginal = data;
      window.tableDataOriginal = tableData.slice();

      // ✅ FIX: el inicio de las columnas de meses depende del modo
      // (agrupado arrancan en 5, normal arrancan en 8).
      const numMesesInicial = data.columnas_meses.length;
      const inicioMesesInicial = agrupado ? 5 : 8;

      for (let row = 0; row < hot.countRows(); row++) {
        let tieneRotacion = false;

        for (let col = inicioMesesInicial; col < inicioMesesInicial + numMesesInicial; col++) {
          const valorCelda = hot.getDataAtCell(row, col);
          if (parseFloat(valorCelda) > 0) {
            tieneRotacion = true;
            break;
          }
        }

        if (!tieneRotacion) {
          for (let col = inicioMesesInicial; col < inicioMesesInicial + numMesesInicial; col++) {
            hot.setCellMeta(row, col, 'className', 'ht-cantidad-pendiente');
          }
        }
      }
      actualizarTotalesOrdenCompra(hot, data);
      hot.render();

      // Índices dinámicos según modo (Descripción siempre en posición 1)
      // ✅ FIX: en modo normal, IdBodega/Bodega/BodegaPpal desplazan Marca y
      // Unidad una posición más (5 y 6, no 4 y 5).
      const indiceMarcaBase = agrupado ? 2 : 5;
      const indiceUnidadBase = agrupado ? 3 : 6;
      const numMeses = data.columnas_meses.length;
      const inicioMeses = agrupado ? 5 : 8;
      // ✅ FIX: mismos índices que en afterChange (antes estaban desfasados
      // en 1 para el modo normal, lo que rompía "Solo sugerencia").
      const indiceComprar = agrupado ? (15 + numMeses) : (18 + numMeses);
      const indiceCantPedir = agrupado ? (16 + numMeses) : (19 + numMeses);

      // Llenar selects de bodegas y marcas únicas (solo si no está agrupado)
      if (!agrupado) {
        const bodegasUnicas = [...new Set(data.registros.map(r => r.nombre_bodega))];
        let htmlBodegas = '<option value="">Todas las bodegas</option>';
        bodegasUnicas.forEach(bodega => {
          if (bodega) htmlBodegas += `<option value="${bodega}">${bodega}</option>`;
        });
        $('#filtro_resultados_bodega').html(htmlBodegas).select2({
          theme: 'bootstrap-5',
          width: '100%'
        });
      }

      const marcasUnicas = [...new Set(data.registros.map(r => r.marca))];
      let htmlMarcas = '<option value="">Todas las marcas</option>';
      marcasUnicas.forEach(marca => {
        if (marca && marca !== '-') htmlMarcas += `<option value="${marca}">${marca}</option>`;
      });
      $('#filtro_resultados_marca').html(htmlMarcas).select2({
        theme: 'bootstrap-5',
        width: '100%'
      });

      // Función para aplicar filtros en la tabla
      const aplicarFiltrosResultados = () => {
        const bodegaSeleccionada = !agrupado ? $('#filtro_resultados_bodega').val() : '';
        const marcaSeleccionada = $('#filtro_resultados_marca').val();
        const soloSugerencia = $('#filtro_resultados_sugerencia').is(':checked');
        const soloSinRotacion = $('#filtro_resultados_sin_rotacion').is(':checked');

        // ✅ FIX: el filtro por bodega debe comparar contra "nombre_bodega"
        // (índice 3), que es el valor que carga el <select>. El índice 2
        // es "id_bodega" y nunca hacía match con el texto seleccionado.
        const indiceBodega = agrupado ? -1 : 3;
        let datosFiltrados = window.tableDataOriginal.filter((row) => {
          if (!agrupado && bodegaSeleccionada && row[indiceBodega] !== bodegaSeleccionada) {
            return false;
          }

          if (marcaSeleccionada && row[indiceMarcaBase] !== marcaSeleccionada) {
            return false;
          }

          if (soloSugerencia && (parseInt(row[indiceCantPedir]) || 0) <= 0) {
            return false;
          }

          if (soloSinRotacion) {
            let tieneRotacion = false;
            for (let col = inicioMeses; col < inicioMeses + numMeses; col++) {
              if (parseFloat(row[col]) > 0) {
                tieneRotacion = true;
                break;
              }
            }
            if (tieneRotacion) {
              return false;
            }
          }

          return true;
        });

        hot.loadData(datosFiltrados);

        setTimeout(() => {
          for (let row = 0; row < hot.countRows(); row++) {
            let tieneRotacion = false;

            for (let col = inicioMeses; col < inicioMeses + numMeses; col++) {
              const valorCelda = hot.getDataAtCell(row, col);
              if (parseFloat(valorCelda) > 0) {
                tieneRotacion = true;
                break;
              }
            }

            if (!tieneRotacion) {
              for (let col = inicioMeses; col < inicioMeses + numMeses; col++) {
                hot.setCellMeta(row, col, 'className', 'ht-cantidad-pendiente');
              }
            }
          }
          hot.render();
          actualizarTotalesOrdenCompra(hot, data);
        }, 50);
      };

      // Event listeners para filtros
      if (!agrupado) {
        $('#filtro_resultados_bodega').on('change', aplicarFiltrosResultados);
      }
      $('#filtro_resultados_marca').on('change', aplicarFiltrosResultados);
      $('#filtro_resultados_sugerencia').on('change', aplicarFiltrosResultados);
      $('#filtro_resultados_sin_rotacion').on('change', aplicarFiltrosResultados);

      // Botón de limpiar filtros
      $('#btnLimpiarFiltrosResultados').on('click', function () {
        location.reload();
      });

      // Botón de exportar a Excel
      $('#btnExportarExcel').on('click', exportarAExcel);

      // Recalcular altura de cards para coincidir con Handsontable
      setTimeout(() => {
        const cardTotales = $('#cardTotalesAcciones');
        const dvSugerido = $('#dvSugerido');
        const hotContainer = $('#sugeridoHandsontable');

        if (cardTotales.length && dvSugerido.length && hotContainer.length) {
          const viewportHeight = window.innerHeight;
          const cardHeight = cardTotales.outerHeight(true) || 0;
          const filtersHeight = dvSugerido.find('.row.g-3').outerHeight(true) || 0;
          const headerHeight = 120;

          const heightForTable = viewportHeight - cardHeight - filtersHeight - headerHeight;

          hotContainer.css('height', Math.max(400, heightForTable) + 'px');

          hot.render();
        }
      }, 150);

      // Recalcular al redimensionar la ventana
      $(window).off('resize.handsontable').on('resize.handsontable', function () {
        if (window.hotInstance) {
          setTimeout(() => {
            const cardTotales = $('#cardTotalesAcciones');
            const dvSugerido = $('#dvSugerido');
            const hotContainer = $('#sugeridoHandsontable');

            if (cardTotales.length && dvSugerido.length && hotContainer.length) {
              const viewportHeight = window.innerHeight;
              const cardHeight = cardTotales.outerHeight(true) || 0;
              const filtersHeight = dvSugerido.find('.row.g-3').outerHeight(true) || 0;
              const headerHeight = 120;

              const heightForTable = viewportHeight - cardHeight - filtersHeight - headerHeight;
              hotContainer.css('height', Math.max(400, heightForTable) + 'px');

              window.hotInstance.render();
            }
          }, 100);
        }
      });

    }, 100);

    // Cambiar a pestaña de resultados
    $('[data-bs-target="#navs-resultados"]').tab('show');

  } catch (e) {
    console.error('Error al mostrar resultados:', e);
    Swal.fire('Error', 'Error al mostrar los resultados: ' + e.message, 'error');
  }
};
/* funcion anterior de cesar la cambie el 2026-08-27
const mostrarResultadosEnDataTable = (data) => {
  try {
    // Validar que existan registros
    if (!data.registros || data.registros.length === 0) {
      $('#dvSugerido').html(`
        <div class="alert alert-warning d-flex align-items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
          <span>No se encontraron registros con los filtros seleccionados.</span>
        </div>
      `);
      $('[data-bs-target="#navs-resultados"]').tab('show');
      return;
    }

    // Detectar si está agrupando productos
    const agrupado = data.agrupar_productos === 1 || data.agrupar_productos === true;

    // Preparar datos para Handsontable
    const tableData = [];
    let totalCantidad = 0;
    let totalCosto = 0;

    data.registros.forEach(row => {
      const ultimoCosto = parseFloat(row.ultimo_costo_compra) || 0;
      const costoPromedio = parseFloat(row.costo_promedio) || 0;
      const costoCompra = parseFloat(row.costo_compra) || 0;  // ✅ NUEVA: Costo de compra del proveedor
      const rotacionDiaria = parseFloat(row.rotacion_diaria) || 0;
      const cantSugerida = Math.round(rotacionDiaria * data.dias);
      const stockBodegaPrincipal = parseFloat(row.stock_bodega_principal) || 0;
      const totalCstBodegaPrincipal = parseFloat(row.total_cst_bodega_principal) || 0;
      const totalCstBodega = parseFloat(row.total_cst_bodega) || 0;

      const rowData = [
        row.codigo,
        row.descripcion
      ];

      // Solo agregar bodega y bodega_principal si NO está agrupado
      if (!agrupado) {
        rowData.push(row.id_bodega);           // 🔹 NUEVO: Agregar ID de bodega
        rowData.push(row.nombre_bodega);
        rowData.push(row.bodega_principal);
      }

      rowData.push(
        row.marca || '-',
        row.label_unidad || '-',
        row.conversion_compra || '-'
      );

      // Agregar valores de meses
      data.columnas_meses.forEach(mes => {
        const colKey = mes.toLowerCase().replace(/ /g, '_');
        rowData.push(row[colKey] || 0);
      });

      // Agregar valores de rotación y totales
      rowData.push(
        row.rotacion_total,
        row.stock,
        stockBodegaPrincipal,
        parseFloat(row.costo_promedio).toFixed(2),
        parseFloat(row.ultimo_costo_compra).toFixed(2),
        row.promedio,
        rotacionDiaria.toFixed(2),
        `Sug. ${data.dias}d`,
        parseInt(row.cantidad_pendiente) || 0,  // ✅ NUEVA: Cantidad Pendiente
        cantSugerida,
        row.comprar
      );

      // COLUMNAS FINALES: Cant Pedir, Costo de Compra y Subtotal (Costo de Compra × Cant Pedir)
      const cantPedir = parseInt(row.cant_pedir) || 0;
      // ✅ El Costo de Compra ya viene con la lógica de fallback desde el PHP
      // ✅ IMPORTANTE: Calcular subtotal CORRECTAMENTE (Costo × Cantidad, no fijo)
      const subtotal = cantPedir > 0 ? parseFloat((costoCompra * cantPedir).toFixed(2)) : 0;

      rowData.push(
        cantPedir,
        costoCompra.toFixed(2),  // ✅ NUEVA: Columna Costo de Compra
        subtotal  // ✅ SUBTOTAL DINÁMICO: Se recalculará en afterChange
      );

      rowData.push(
        totalCstBodegaPrincipal,
        totalCstBodega
      );

      tableData.push(rowData);
    });

    // Crear array de headers
    let headers = [
      'Código',
      'Descripción'
    ];

    // Solo agregar bodega y bodega_principal si NO está agrupado
    if (!agrupado) {
      headers.push('ID Bodega', 'Bodega', 'Bodega Principal');  // 🔹 NUEVO: Agregar ID Bodega
    }

    headers = headers.concat([
      'Marca',
      'Unidad',
      'Conversión',
      ...data.columnas_meses,
      'Rot. Total',
      'Stock',
      'Stock Bodega Principal',
      'Costo Prom.',
      'Últ. Costo',
      'Promedio',
      'Rot. Diaria',
      `Sug. ${data.dias}d`,
      'Cant Pendiente',  // ✅ NUEVA COLUMNA: Cantidad Pendiente
      'Cant Sug.',
      'Comprar?',
      'Cant Pedir',
      'Costo de Compra',  // ✅ NUEVA COLUMNA
      'Subtotal',
      'Total cst. bodega',
      'Total cst. Bod. princ'
    ]);


    // HTML para el contenedor con filtros de resultados
    let html = `
      <div class="alert alert-info mb-3 d-none">
        <strong>Período Analizado:</strong> ${data.meses} meses | 
        <strong>Sugerencia de compra por:</strong> ${data.dias} días |
        <strong>Productos a comprar:</strong> ${data.resumen.total_productos} |
        <strong>Costo Total Compra:</strong> $${parseFloat(data.resumen.costo_total_compra).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
      </div>
      
      <!-- Filtros en resultados -->
      <div class="row g-3 mb-3">`;

    // Solo mostrar filtro bodega si NO está agrupado
    if (!agrupado) {
      html += `
        <div class="col-md-2">
          <label class="form-label fw-semibold">Filtrar por Bodega</label>
          <select id="filtro_resultados_bodega" class="form-select form-select-sm">
            <option value="">Todas las bodegas</option>
          </select>
        </div>`;
    }

    html += `
        <div class="col-md-2">
          <label class="form-label fw-semibold">Filtrar por Marca</label>
          <select id="filtro_resultados_marca" class="form-select form-select-sm">
            <option value="">Todas las marcas</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">&nbsp;</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="filtro_resultados_sugerencia" value="1">
            <label class="form-check-label" for="filtro_resultados_sugerencia">
              Solo sugerencia
            </label>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">&nbsp;</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="filtro_resultados_sin_rotacion" value="1">
            <label class="form-check-label" for="filtro_resultados_sin_rotacion">
              Solo sin rotación
            </label>
          </div>
        </div>
        <div class="col-md-1">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-sm btn-outline-secondary w-100 mt-2" id="btnLimpiarFiltrosResultados" title="Limpiar filtros">
            <i class="fa-solid fa-undo"></i> Limpiar
          </button>
        </div>
        <div class="col-md-1">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-sm btn-success w-100 mt-2" id="btnExportarExcel" title="Exportar a Excel">
            <i class="fa-solid fa-file-excel"></i> Excel
          </button>
        </div>
      </div>

      <div id="sugeridoHandsontable" style="width: 100%; height: calc(100vh - 400px); overflow: auto;"></div>
      
      <!-- ✅ NUEVA SECCIÓN: Preview del Array Generado -->
      <div id="previewArrayCompras" style="margin-top: 20px;"></div>
    `;

    // Insertar HTML en el DOM
    $('#dvSugerido').html(html);

    // ✅ AÑADIR ESTILOS PARA ALTURA UNIFORME Y SIN ENVOLVIMIENTO
    $('<style>')
      .text(`
        #sugeridoHandsontable {
          line-height: 1;
        }
        #sugeridoHandsontable .htCore {
          line-height: 1;
        }
        #sugeridoHandsontable td {
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          height: 10px !important;
        }
        #sugeridoHandsontable th {
          height: 30px !important;
        }
        // ✅ COLOR MORADO SUAVE PARA COLUMNA DESCRIPCIÓN 
        #sugeridoHandsontable td:nth-child(2),
        #sugeridoHandsontable th:nth-child(2) {
          background-color: #e8d5f2 !important;
          font-weight: 500;
        }
        // ✅ COLOR ROJO PASTEL PARA CANTIDAD PENDIENTE 
        .ht-cantidad-pendiente {
          background-color: #ffcccc !important;
          color: #8B0000 !important;
          font-weight: 600 !important;
        }
        // ✅ AJUSTE DINÁMICO DE CONTENEDORES
        #dvSugerido {
          display: flex;
          flex-direction: column;
        }
        #dvSugerido .row.g-3 {
          flex-shrink: 0;
        }
        #sugeridoHandsontable {
          flex-grow: 1;
          flex-shrink: 1;
          min-height: 400px;
        }
      `)
      .appendTo('head');

    // Esperar un poco para que el DOM esté listo
    setTimeout(() => {
      const container = document.getElementById('sugeridoHandsontable');

      if (!container) {
        console.error('Contenedor no encontrado');
        return;
      }

      // Crear configuración de columnas OPTIMIZADA
      const columns = headers.map((header, index) => {
        const col = {
          title: header,
          type: 'text',
          readOnly: true
        };

        // EDITABLE: Solo Cant Pedir
        if (header === 'Cant Pedir') {
          col.type = 'numeric';
          col.readOnly = false;
          col.numericFormat = { pattern: '0' };
          col.className = 'ht-editable';
        }

        // ✅ READONLY: Cantidad Pendiente CON FONDO ROJO PASTEL
        if (header === 'Cant Pendiente') {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '0' };
          col.className = 'ht-readonly-numeric ht-cantidad-pendiente';
        }

        // ✅ EDITABLE: Costo de Compra CON FORMATO MONEDA
        if (header === 'Costo de Compra') {
          col.type = 'numeric';
          col.readOnly = false;
          col.numericFormat = { pattern: '$0.00', culture: 'es-CO' };
          col.className = 'ht-editable';
        }

        // ✅ READONLY: Columnas de Costo con FORMATO MONEDA
        if (['Costo Prom.', 'Últ. Costo', 'Subtotal'].includes(header)) {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '$0.00', culture: 'es-CO' };
          col.className = 'ht-readonly-numeric';
        }

        // ✅ READONLY: Columnas de Costo con FORMATO MONEDA , formato miles con . decimales con ,
        if (['Total cst. bodega', 'Total cst. Bod. princ'].includes(header)) {
          col.type = 'numeric';
          col.readOnly = true;
          col.numericFormat = { pattern: '$0,0.00', culture: 'es-CO' };
          col.className = 'ht-readonly-numeric';
        }

        // Ancho para descripción
        if (header === 'Descripción') {
          col.width = 350;
        }

        return col;
      });

      // Inicializar Handsontable OPTIMIZADO para GRANDES VOLÚMENES de datos
      const hot = new Handsontable(container, {
        data: tableData,
        colHeaders: headers,
        columns: columns,
        rowHeaders: false,
        contextMenu: false,
        manualColumnResize: true,
        height: 'calc(100vh - 200px)',
        width: '100%',
        licenseKey: 'non-commercial-and-evaluation',

        // ✅ CONGELAR COLUMNAS: Código y Descripción
        fixedColumnsStart: 2,

        // ✅ ALTURA UNIFORME PARA TODAS LAS FILAS 
        rowHeights: 25,

        // VIRTUALIZACIÓN - Solo renderiza filas visibles
        renderAllRows: false,
        viewportRowRenderingOffset: 10,

        // OPTIMIZACIONES
        noNewRowsMode: true,
        outsideClickDeselects: true,
        autoRowSize: false,

        // FILTROS en los encabezados
        filters: true,
        dropdownMenu: ['filter_by_condition', 'filter_action_bar'],

        columnSorting: true,

        // EVENT LISTENER para cambios en la tabla - MEJORADO
        afterChange: function (changes, source) {
          // ✅ Ignorar cambios que no sean de usuario
          if (source === 'loadData' || source === 'calcData' || source === 'autofill') return;
          if (!changes || changes.length === 0) return;

          try {
            const numMeses = data.columnas_meses ? data.columnas_meses.length : 0;
            const agrupado = data.agrupar_productos === 1 || data.agrupar_productos === true;

            // ✅ ÍNDICES CORRECTOS PARA AMBOS MODOS
            let indiceCantPendiente, indiceCantPedir, indiceCostoCompra, indiceSubtotal, indiceComprar;

            if (agrupado) {
              // MODO AGRUPADO: Código, Descripción, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
              indiceCantPendiente = 14 + numMeses;
              indiceComprar = 15 + numMeses;
              indiceCantPedir = 16 + numMeses;
              indiceCostoCompra = 17 + numMeses;
              indiceSubtotal = 18 + numMeses;
            } else {
              // MODO NORMAL: Código, Descripción, IdBodega🔹, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
              indiceCantPendiente = 17 + numMeses;
              indiceComprar = 18 + numMeses;
              indiceCantPedir = 19 + numMeses;
              indiceCostoCompra = 20 + numMeses;
              indiceSubtotal = 21 + numMeses;  // 🔹 CORREGIDO: +1 por columna id_bodega
            }

            // ✅ Procesar cada cambio CON VALIDACIÓN
            changes.forEach(([rowIndex]) => {
              try {
                // ✅ Obtener valores CON validación
                const cant = parseInt(hot.getDataAtCell(rowIndex, indiceCantPedir)) || 0;
                const costo = parseFloat(hot.getDataAtCell(rowIndex, indiceCostoCompra)) || 0;
                // ✅ NUEVA LÓGICA: Actualizar "Comprar?" basado en cantidad
                const nuevoComprar = cant > 0 ? 'SÍ' : 'NO';
                hot.setDataAtCell(rowIndex, indiceComprar, nuevoComprar, 'calcData');
                // ✅ Calcular subtotal SOLO si hay cantidad
                const subtotal = cant > 0 ? parseFloat((costo * cant).toFixed(2)) : 0;
                // ✅ Actualizar subtotal en la tabla
                hot.setDataAtCell(rowIndex, indiceSubtotal, subtotal, 'calcData');

              } catch (cellError) {
                console.warn(`⚠️ Error procesando fila ${rowIndex}:`, cellError);
              }
            });

            // ✅ Actualizar totales DESPUÉS de todos los cambios
            setTimeout(() => {
              actualizarTotalesOrdenCompra(hot, data);
            }, 50);

          } catch (handleError) {
            console.error('❌ Error en afterChange handler:', handleError);
          }
        }
      });

      // Guardar referencia global
      window.hotInstance = hot;
      window.dataOriginal = data;
      window.tableDataOriginal = tableData.slice();  // Copiar datos originales

      for (let row = 0; row < hot.countRows(); row++) {
        let tieneRotacion = false;
        const numMeses = parseInt($("#filtro_meses").val());
        
        for (let col = 8; col < 8 + numMeses; col++) {
          const valorCelda = hot.getDataAtCell(row, col);
          if (parseFloat(valorCelda) > 0) {
            tieneRotacion = true;
            break;
          }
        }

        if (!tieneRotacion) {
          for (let col = 8; col < 8 + numMeses; col++) {
            hot.setCellMeta(row, col, 'className', 'ht-cantidad-pendiente');
          }
        }
      }
      // ✅ CALCULAR TOTALES INMEDIATAMENTE AL GENERAR SUGERIDO
      actualizarTotalesOrdenCompra(hot, data);
      hot.render();

      // Calcular índices dinámicamente (Descripción siempre en posición 1)
      const offsetBodega = agrupado ? -1 : 2;  // -1 si no existe la columna
      const indiceMarcaBase = agrupado ? 2 : 4;
      const indiceUnidadBase = agrupado ? 3 : 5;
      const numMeses = data.columnas_meses.length;
      // Índice de comprar: Agrupado = 15+numMeses, Normal = 17+numMeses
      const indiceComprar = agrupado ? (15 + numMeses) : (17 + numMeses);
      // Índice de cantidad a pedir: Agrupado = 16+numMeses, Normal = 18+numMeses
      const indiceCantPedir = agrupado ? (16 + numMeses) : (18 + numMeses);

      // Llenar selects de bodegas y marcas únicas (solo si no está agrupado)
      if (!agrupado) {
        const bodegasUnicas = [...new Set(data.registros.map(r => r.nombre_bodega))];
        let htmlBodegas = '<option value="">Todas las bodegas</option>';
        bodegasUnicas.forEach(bodega => {
          if (bodega) htmlBodegas += `<option value="${bodega}">${bodega}</option>`;
        });
        $('#filtro_resultados_bodega').html(htmlBodegas).select2({
          theme: 'bootstrap-5',
          width: '100%'
        });
      }

      const marcasUnicas = [...new Set(data.registros.map(r => r.marca))];
      let htmlMarcas = '<option value="">Todas las marcas</option>';
      marcasUnicas.forEach(marca => {
        if (marca && marca !== '-') htmlMarcas += `<option value="${marca}">${marca}</option>`;
      });
      $('#filtro_resultados_marca').html(htmlMarcas).select2({
        theme: 'bootstrap-5',
        width: '100%'
      });

      // Función para aplicar filtros en la tabla
      const aplicarFiltrosResultados = () => {
        const bodegaSeleccionada = !agrupado ? $('#filtro_resultados_bodega').val() : '';
        const marcaSeleccionada = $('#filtro_resultados_marca').val();
        const soloSugerencia = $('#filtro_resultados_sugerencia').is(':checked');
        const soloSinRotacion = $('#filtro_resultados_sin_rotacion').is(':checked');

        console.log(window.tableDataOriginal);

        const indiceBodega = agrupado ? -1 : 2;  // Bodega en posición 2 si no está agrupado
        let datosFiltrados = window.tableDataOriginal.filter((row, idx) => {
          // Filtro por bodega (solo si no está agrupado)
          if (!agrupado && bodegaSeleccionada && row[indiceBodega] !== bodegaSeleccionada) {
            return false;
          }

          // Filtro por marca
          if (marcaSeleccionada && row[indiceMarcaBase] !== marcaSeleccionada) {
            return false;
          }

          console.log('Fila:', row, 'Cant Pedir:', row[indiceCantPedir]);
          // Filtro por sugerencia - Mostrar solo filas con cantidad a pedir > 0
          if (soloSugerencia && (parseInt(row[indiceCantPedir]) || 0) <= 0) {
            return false;
          }

          // Filtro por sin rotación - Verificar si todas las columnas de meses son 0
          if (soloSinRotacion) {
            let tieneRotacion = false;
            for (let col = 8; col < 8 + numMeses; col++) {
              if (parseFloat(row[col]) > 0) {
                tieneRotacion = true;
                break;
              }
            }
            if (tieneRotacion) {
              return false;
            }
          }

          return true;
        });

        // Actualizar tabla con datos filtrados
        hot.loadData(datosFiltrados);

        // ✅ REAPLICAR COLORES DE SIN ROTACIÓN DESPUÉS DE FILTRAR
        setTimeout(() => {
          for (let row = 0; row < hot.countRows(); row++) {
            let tieneRotacion = false;
            
            for (let col = 8; col < 8 + numMeses; col++) {
              const valorCelda = hot.getDataAtCell(row, col);
              if (parseFloat(valorCelda) > 0) {
                tieneRotacion = true;
                break;
              }
            }

            if (!tieneRotacion) {
              for (let col = 8; col < 8 + numMeses; col++) {
                hot.setCellMeta(row, col, 'className', 'ht-cantidad-pendiente');
              }
            }
          }
          hot.render();
          actualizarTotalesOrdenCompra(hot, data);
        }, 50);
      };

      // Event listeners para filtros
      if (!agrupado) {
        $('#filtro_resultados_bodega').on('change', aplicarFiltrosResultados);
      }
      $('#filtro_resultados_marca').on('change', aplicarFiltrosResultados);
      $('#filtro_resultados_sugerencia').on('change', aplicarFiltrosResultados);
      $('#filtro_resultados_sin_rotacion').on('change', aplicarFiltrosResultados);

      // Botón de limpiar filtros
      $('#btnLimpiarFiltrosResultados').on('click', function () {
        ///reload  windows 
        location.reload();

      });

      // Botón de exportar a Excel
      $('#btnExportarExcel').on('click', exportarAExcel);

      // ✅ RECALCULAR ALTURA DE CARDS PARA COINCIDIR CON HANDSONTABLE
      setTimeout(() => {
        const cardTotales = $('#cardTotalesAcciones');
        const cardTotales2 = $('#cardTotalesAcciones2');
        const dvSugerido = $('#dvSugerido');
        const hotContainer = $('#sugeridoHandsontable');

        if (cardTotales.length && dvSugerido.length && hotContainer.length) {
          // Calcular altura total disponible
          const viewportHeight = window.innerHeight;
          const cardHeight = cardTotales.outerHeight(true) || 0;  // Con márgenes
          const filtersHeight = dvSugerido.find('.row.g-3').outerHeight(true) || 0;  // Filtros
          const headerHeight = 120;  // Aproximado para navegación

          // Altura para Handsontable = viewport - card - filtros - header
          const heightForTable = viewportHeight - cardHeight - filtersHeight - headerHeight;

          // Actualizar altura dinámicamente
          hotContainer.css('height', Math.max(400, heightForTable) + 'px');

          // Recalcular Handsontable
          hot.render();

        }
      }, 150);

      // ✅ RECALCULAR AL REDIMENSIONAR LA VENTANA
      $(window).off('resize.handsontable').on('resize.handsontable', function () {
        if (window.hotInstance) {
          setTimeout(() => {
            const cardTotales = $('#cardTotalesAcciones');
            const dvSugerido = $('#dvSugerido');
            const hotContainer = $('#sugeridoHandsontable');

            if (cardTotales.length && dvSugerido.length && hotContainer.length) {
              const viewportHeight = window.innerHeight;
              const cardHeight = cardTotales.outerHeight(true) || 0;
              const filtersHeight = dvSugerido.find('.row.g-3').outerHeight(true) || 0;
              const headerHeight = 120;

              const heightForTable = viewportHeight - cardHeight - filtersHeight - headerHeight;
              hotContainer.css('height', Math.max(400, heightForTable) + 'px');

              window.hotInstance.render();
            }
          }, 100);
        }
      });

    }, 100);

    // Cambiar a pestaña de resultados 
    $('[data-bs-target="#navs-resultados"]').tab('show');

  } catch (e) {
    console.error('Error al mostrar resultados:', e);
    Swal.fire('Error', 'Error al mostrar los resultados: ' + e.message, 'error');
  }
};
*/
// Función para actualizar resumen por bodega EN TIEMPO REAL
const actualizarResumenBodega = (hot, dataOriginal) => {
  /*
  try {
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses.length;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;
    console.log({ dataOriginal, hot })
    return;
    // Calcular índices dinámicamente según modo
    let indiceBodega, indiceCantidad, indiceCostoCompra, indiceComprar, indiceSubtotal;

    if (agrupado) {
      // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
      indiceBodega = -1;  // No existe
      indiceCantidad = 16 + numMeses;  // CantPedir
      indiceCostoCompra = 17 + numMeses;  // Costo Compra
      indiceComprar = 15 + numMeses;
      indiceSubtotal = 18 + numMeses;
    } else {
      // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
      indiceBodega = 2;
      indiceCantidad = 18 + numMeses;  // CantPedir
      indiceCostoCompra = 19 + numMeses;  // Costo Compra
      indiceComprar = 17 + numMeses;
      indiceSubtotal = 20 + numMeses;
    }

    // Compilar resumen por bodega
    const resumenBodegas = {};
    let totalGeneralCantidad = 0;
    let totalGeneralCosto = 0;

    for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
      const row = data[rowIndex];
      const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
      const comprar = row[indiceComprar];

      //  if (comprar === 'SÍ') {
      const cantidad = parseInt(row[indiceCantidad]) || 0;
      const subtotal = parseFloat(row[indiceSubtotal]) || 0;

      if (cantidad > 0) {
        if (!resumenBodegas[bodega]) {
          resumenBodegas[bodega] = {
            bodega: bodega,
            cantidad: 0,
            costo: 0,
            items: 0
          };
        }

        resumenBodegas[bodega].cantidad += cantidad;
        resumenBodegas[bodega].costo += subtotal;
        resumenBodegas[bodega].items += 1;

        totalGeneralCantidad += cantidad;
        totalGeneralCosto += subtotal;
      }
      //}
    }

    // Mostrar resumen por bodega en la UI
    const resumenHTML = Object.values(resumenBodegas)
      .map(b => `
        <div style="margin-bottom: 0.5rem; padding: 0.5rem; background-color: #f8f9fa; border-radius: 0.25rem;">
          <strong>${b.bodega}:</strong> 
          ${b.cantidad} unidades × <span style="color: #0d6efd; font-weight: 600;">$${b.costo.toFixed(2)}</span>
          (${b.items} items)
        </div>
      `)
      .join('');

    const resumenPorBodegaDiv = document.getElementById('resumenPorBodega');
    const contenidoResumenDiv = document.getElementById('contenidoResumenBodega');

    if (resumenPorBodegaDiv && contenidoResumenDiv) {
      if (Object.keys(resumenBodegas).length > 0) {
        contenidoResumenDiv.innerHTML = resumenHTML;
        resumenPorBodegaDiv.style.display = 'block';
      } else {
        resumenPorBodegaDiv.style.display = 'none';
      }
    }

  } catch (e) {
    console.error('Error al actualizar resumen por bodega:', e);
  }*/
};

// Función para actualizar totales al editar cantidad - OPTIMIZADA - FIXED
const actualizarTotales = (hot, dataOriginal) => {
  try {
    // ✅ FIX: Usar flag para evitar actualizaciones concurrentes
    if (window.actualizandoTotalesGlobal === true) {
      return;
    }

    // Limpiar timeout anterior si existe
    if (timeoutActualizacion) {
      clearTimeout(timeoutActualizacion);
      timeoutActualizacion = null;
    }

    // ✅ Establecer flag
    window.actualizandoTotalesGlobal = true;

    // Debounce: esperar 150ms antes de actualizar
    timeoutActualizacion = setTimeout(() => {
      try {
        // ✅ Validar que hot y dataOriginal existan
        if (!hot || !dataOriginal || !hot.getData) {
          window.actualizandoTotalesGlobal = false;
          return;
        }

        let totalCantidad = 0;
        let totalCosto = 0;

        const data = hot.getData();
        if (!data || data.length === 0) {
          window.actualizandoTotalesGlobal = false;
          return;
        }

        const numMeses = dataOriginal.columnas_meses ? dataOriginal.columnas_meses.length : 0;
        const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

        // Calcular índices dinámicamente según modo
        let indiceBodega, indiceCantSug, indiceCantidad, indiceCostoCompra, indiceTotal, indiceComprar;

        if (agrupado) {
          // Modo agrupado
          indiceBodega = -1;  // No existe
          indiceCantSug = 4 + numMeses;
          indiceCantidad = 5 + numMeses;
          indiceCostoCompra = 6 + numMeses;
          indiceTotal = 7 + numMeses;
          indiceComprar = 8 + numMeses;
        } else {
          // Modo normal
          indiceBodega = 2;
          indiceCantSug = 7 + numMeses;
          indiceCantidad = 8 + numMeses;
          indiceCostoCompra = 9 + numMeses;
          indiceTotal = 10 + numMeses;
          indiceComprar = 11 + numMeses;
        }

        // ✅ OPTIMIZADO: Solo actualizar datos en memoria (mucho más rápido que setDataAtCell)
        const changes = [];

        for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
          const row = data[rowIndex];
          if (!row || !Array.isArray(row)) continue;

          // ✅ Validar índices
          if (indiceCantidad >= row.length || indiceCostoCompra >= row.length || indiceTotal >= row.length) {
            continue;
          }

          const cantidad = parseInt(row[indiceCantidad]) || 0;
          const costoCompra = parseFloat(row[indiceCostoCompra]) || 0;
          const subtotal = cantidad * costoCompra;

          // Registrar cambio de total
          changes.push([rowIndex, indiceTotal, subtotal.toFixed(2)]);

          // Acumular totales
          totalCantidad += cantidad;
          totalCosto += subtotal;
        }

        // Aplicar todos los cambios de una vez (batch)
        if (changes.length > 0) {
          hot.setDataAtCell(changes, 'loadData');
        }

        // Actualizar resumen
        const resumenCantidad = document.getElementById('resumen_cantidad');
        const resumenCosto = document.getElementById('resumen_costo');

        if (resumenCantidad) {
          resumenCantidad.textContent = `Total Cantidad: ${totalCantidad}`;
        }
        if (resumenCosto) {
          resumenCosto.textContent = `Total Costo: $${totalCosto.toFixed(2)}`;
        }

        // Actualizar resumen por bodega EN TIEMPO REAL
        //  actualizarResumenBodega(hot, dataOriginal);

        // Construir array de compras
        construirArrayCompras(hot, dataOriginal);

      } catch (innerError) {
        console.error('❌ Error en setTimeout de actualizarTotales:', innerError);
      } finally {
        // ✅ SIEMPRE limpiar la flag
        window.actualizandoTotalesGlobal = false;
      }

    }, 150);  // Esperar 150ms

  } catch (e) {
    console.error('❌ Error en actualizarTotales:', e);
    window.actualizandoTotalesGlobal = false;
  }
};

// Función para construir array de objetos de compras - AGRUPADO POR BODEGA
// Estructura final: [ [{bodega1_items}], [{bodega2_items}] ]
const construirArrayCompras = (hot, dataOriginal) => {
  try {
    const comprasObjeto = {};  // Objeto para agrupar por bodega
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses.length;
    const dataLength = data.length;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

    // Calcular índices dinámicamente según modo
    let indiceBodega, indiceCodigo, indiceDescripcion, indiceUnidad, indiceConversion, indiceCantidad, indiceCostoCompra, indiceComprar;

    if (agrupado) {
      // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
      indiceBodega = -1;  // No existe
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 3;
      indiceConversion = 4;
      indiceCantidad = 16 + numMeses;  // CantPedir
      indiceCostoCompra = 17 + numMeses;  // ✅ NUEVA: Costo de Compra
      indiceComprar = 15 + numMeses;
    } else {
      // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses, Rot.Total, Stock, Stock BP, Costo Prom, Últ.Costo, Promedio, Rot.Diaria, Sug.Xd, Cant.Pendiente, Cant.Sug, Comprar, CantPedir, CostoCompra, Subtotal
      indiceBodega = 2;
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 5;
      indiceConversion = 6;
      indiceCantidad = 18 + numMeses;  // CantPedir
      indiceCostoCompra = 19 + numMeses;  // ✅ NUEVA: Costo de Compra
      indiceComprar = 17 + numMeses;
    }

    // FOR LOOP es más rápido que forEach para grandes volúmenes
    for (let rowIndex = 0; rowIndex < dataLength; rowIndex++) {
      const row = data[rowIndex];
      const comprar = row[indiceComprar];

      if (comprar === 'SÍ') {
        const cantidad = parseInt(row[indiceCantidad]) || 0;
        if (cantidad > 0) {
          const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
          const costoCompra = parseFloat(row[indiceCostoCompra]) || 0;  // ✅ NUEVA: Usar Costo de Compra
          const codigo = row[indiceCodigo] || '';
          const canUnd = parseFloat(row[indiceConversion]) || 1;  // ✅ can_und (conversión de unidades)

          // Crear bodega si no existe
          if (!comprasObjeto[bodega]) {
            comprasObjeto[bodega] = [];
          }

          // ✅ AGREGAR ITEM A LA BODEGA: {id_bodega, id_referencia, cantidad, costo, can_und}
          comprasObjeto[bodega].push({
            id_bodega: bodega,
            id_referencia: codigo,
            cantidad: cantidad,
            costo: costoCompra,  // ✅ Ahora usa costo_compra (ya con fallback del proveedor)
            can_und: canUnd
          });
        }
      }
    }

    // Convertir a estructura final: [ [{items}], [{items}] ]
    const comprasArrayFinal = Object.values(comprasObjeto);

    // Guardar arrays para uso posterior
    window.comprasArray = comprasArrayFinal;  // Array agrupado por bodega
    window.comprasObjeto = comprasObjeto;  // Objeto por si se necesita

    // ✅ Mostrar preview del array generado
    mostrarPreviewArrayCompras(comprasArrayFinal);

  } catch (e) {
    console.error('Error al construir array de compras:', e);
  }
};

// ✅ NUEVA FUNCIÓN: Mostrar preview del array generado
const mostrarPreviewArrayCompras = (comprasArray) => {
  try {
    if (!comprasArray || comprasArray.length === 0) {
      return;
    }

    let previewHTML = '<div class="card border-info mt-3"><div class="card-header bg-info text-white"><i class="fa-solid fa-list-check"></i> Vista Previa - Orden de Pedido Generada</div><div class="card-body">';

    let totalItems = 0;
    let totalCantidad = 0;
    let totalCosto = 0;

    comprasArray.forEach((bodegaItems, bodegaIdx) => {
      previewHTML += `<div class="mb-3"><h6 class="text-primary"><i class="fa-solid fa-warehouse"></i> Bodega ${bodegaIdx + 1}</h6><div class="table-responsive"><table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;"><thead class="table-light"><tr><th>ID Ref</th><th>Bodega</th><th>Cantidad</th><th>Costo Unit.</th><th>Can. Und</th><th>Subtotal</th></tr></thead><tbody>`;

      bodegaItems.forEach(item => {
        const subtotal = (item.cantidad * item.costo).toFixed(2);
        previewHTML += `<tr><td><strong>${item.id_referencia}</strong></td><td>${item.id_bodega}</td><td style="text-align: center;">${item.cantidad}</td><td>$${parseFloat(item.costo).toFixed(2)}</td><td style="text-align: center;">${item.can_und}</td><td class="text-end"><strong>$${subtotal}</strong></td></tr>`;

        totalItems++;
        totalCantidad += item.cantidad;
        totalCosto += parseFloat(subtotal);
      });

      previewHTML += `</tbody></table></div></div>`;
    });

    previewHTML += `<div class="mt-3 pt-3 border-top"><div class="row"><div class="col-md-4"><small class="text-muted">TOTAL ITEMS:</small><br><strong class="fs-5">${totalItems}</strong></div><div class="col-md-4"><small class="text-muted">TOTAL CANTIDAD:</small><br><strong class="fs-5">${totalCantidad} und</strong></div><div class="col-md-4"><small class="text-muted">TOTAL COSTO:</small><br><strong class="fs-5 text-success">$${totalCosto.toFixed(2)}</strong></div></div></div></div></div>`;

    const previewDiv = document.getElementById('previewArrayCompras');

    if (previewDiv) {
      previewDiv.innerHTML = previewHTML;
    }

  } catch (e) {
    console.error('Error al mostrar preview:', e);
  }
};

// Función para enviar el sugerido a PHP
const enviarSugeridoAphp = async (comprasData) => {
  try {
    if (!comprasData || comprasData.length === 0) {
      Swal.fire('Alerta', 'No hay datos para enviar', 'warning');
      return false;
    }

    showLoadingWithTetrisLogo('Guardando sugerido en base de datos...', 'ISA Software')

    // Calcular totales desde estructura agrupada por bodega: [ [{items}], [{items}] ]
    let totalItems = 0;
    let totalCantidad = 0;
    let totalCosto = 0;

    comprasData.forEach(bodegaArray => {
      bodegaArray.forEach(item => {
        totalItems++;
        totalCantidad += item.cantidad;
        totalCosto += (item.cantidad * item.costo);
      });
    });

    // Enviar datos a PHP
    const resp = await enviarPeticion({
      link: '../models/SugeridoCompras.php',  // Ajusta la ruta según tu estructura
      op: 'guardarSugerido',
      datos: JSON.stringify(comprasData),
      fecha: new Date().toLocaleString('es-CO'),
      total_items: totalItems,
      total_cantidad: totalCantidad,
      total_costo: totalCosto.toFixed(2)
    });

    dissminSwal();

    if (resp.ok === false || resp.error) {
      Swal.fire('Error', resp.error || 'Error al guardar el sugerido', 'error');
      console.error('Error en respuesta:', resp);
      return false;
    }

    console.log('✓ Sugerido guardado exitosamente:', resp);

    // Mostrar alert de éxito con ID generado
    Swal.fire({
      title: '✓ Sugerido Guardado',
      html: `
        <div style="text-align: left;">
          <p><strong>ID Sugerido:</strong> ${resp.id_sugerido || 'N/A'}</p>
          <p><strong>Fecha:</strong> ${new Date().toLocaleString('es-CO')}</p>
          <p><strong>Total Items:</strong> ${totalItems}</p>
          <p><strong>Total Cantidad:</strong> ${totalCantidad} unidades</p>
          <p><strong>Costo Total:</strong> <span style="color: #0d6efd; font-weight: bold; font-size: 1.2rem;">$${totalCosto.toFixed(2)}</span></p>
        </div>
      `,
      icon: 'success',
      confirmButtonText: 'Entendido'
    });

    return true;

  } catch (e) {
    dissminSwal();
    console.error('Error al enviar sugerido:', e);
    Swal.fire('Error', 'Error al enviar datos a PHP: ' + e.message, 'error');
    return false;
  }
};

// Función para guardar compras y mostrar resumen por bodega
// ✅ NUEVA FUNCIÓN: Mostrar modal con pestañas por bodega ANTES de guardar
// ✅ NUEVA FUNCIÓN: Mostrar modal con pestañas por bodega - VERSIÓN CORREGIDA
const generarModalOrdenPorBodega = (hot, dataOriginal) => {
  try {
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses ? dataOriginal.columnas_meses.length : 0;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

    //console.log('🔍 Generando modal...', { totalFilas: data.length, numMeses, agrupado });

    // ✅ ÍNDICES CORRECTOS
    let idxBodega, idxCodigo, idxDesc, idxMarca, idxComprar, idxCantidad, idxCosto, idxSubtotal;
    let idxLabelUnidad, idxConversion, idxIdBodega;

    if (agrupado) {
      idxIdBodega = -1;
      idxBodega = -1;
      idxCodigo = 0;
      idxDesc = 1;
      idxMarca = 2;
      idxLabelUnidad = 3;
      idxConversion = 4;
      idxComprar = 15 + numMeses;
      idxCantidad = 16 + numMeses;
      idxCosto = 17 + numMeses;
      idxSubtotal = 18 + numMeses;
    } else {
      // 🔹 ACTUALIZADO: Se agregó id_bodega en índice 2
      idxIdBodega = 2;         // 🆕 Nuevo
      idxBodega = 3;           // 🔄 Cambió de 2 a 3
      idxCodigo = 0;
      idxDesc = 1;
      idxMarca = 5;            // 🔄 Cambió de 4 a 5 (+1)
      idxLabelUnidad = 6;      // 🔄 Cambió de 5 a 6 (+1)
      idxConversion = 7;       // 🔄 Cambió de 6 a 7 (+1)
      idxComprar = 18 + numMeses;    // 🔄 Cambió de 17 a 18 (+1)
      idxCantidad = 19 + numMeses;   // 🔄 Cambió de 18 a 19 (+1)
      idxCosto = 20 + numMeses;      // 🔄 Cambió de 19 a 20 (+1)
      idxSubtotal = 21 + numMeses;   // 🔄 Cambió de 20 a 21 (+1)
    }

    // ✅ AGRUPAR POR BODEGA
    const bodegas = {};
    let totalProd = 0, totalCant = 0, totalCost = 0;

    for (let i = 0; i < data.length; i++) {
      const row = data[i];
      if (!row) continue;

      const comprar = String(row[idxComprar] || 'NO').trim();
      const cant = parseInt(row[idxCantidad]) || 0;

      if (comprar === 'SÍ' && cant > 0) {
        const bodega = agrupado ? $("#bodega_descripcion").val() : (row[idxBodega] || 'SIN BODEGA');

        if (!bodegas[bodega]) {
          bodegas[bodega] = [];
        }

        bodegas[bodega].push({
          idBodega: row[idxIdBodega] || $("#bodega_session").val(),
          codigo: row[idxCodigo] || '',
          descripcion: row[idxDesc] || '',
          marca: row[idxMarca] || '-',
          unidad: row[idxLabelUnidad] || 'UND',
          can_und: parseInt(row[idxConversion]) || 1,
          cantidad: cant,
          costo: parseFloat(row[idxCosto]) || 0,
          subtotal: parseFloat(row[idxSubtotal]) || 0
        });

        totalProd++;
        totalCant += cant;
        totalCost += parseFloat(row[idxSubtotal]) || 0;
      }
    }

    if (Object.keys(bodegas).length === 0) {
      Swal.fire('Alerta', 'No hay productos con Comprar=SÍ y Cantidad>0', 'warning');
      return;
    }

    // ✅ CREAR PESTAÑAS
    let tabBotones = '', tabContenido = '';
    const bodegasList = Object.keys(bodegas).sort();
    let esFirst = true;

    bodegasList.forEach((nombreBod, idx) => {
      const activo = esFirst ? 'active show' : '';
      const tabId = `tab-compra-${idx}`;

      // Botón tab con estilo mejorado
      tabBotones += `<li class="nav-item" role="presentation">
        <button class="nav-link ${activo}" id="btn-${tabId}" data-bs-toggle="tab" data-bs-target="#${tabId}" type="button" role="tab" aria-selected="${esFirst ? 'true' : 'false'}">
          <i class="fa-solid fa-warehouse me-2"></i>${nombreBod}
          <span class="badge bg-primary ms-2">${bodegas[nombreBod].length}</span>
        </button>
      </li>`;

      // Contenido tab con tabla elegante
      let tabBody = `<div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle">
          <thead class="table-primary sticky-top">
            <tr>
              <th>Código</th>
              <th>Producto</th>
              <th>Marca</th>
              <th class="text-center">Cantidad</th>
              <th class="text-end">Costo Unit.</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>`;

      let bodTotal = 0, bodCost = 0;
      bodegas[nombreBod].forEach((prod, idx) => {
        tabBody += `<tr class="border-bottom">
          <td><span class="badge bg-light text-dark">${prod.codigo}</span></td>
          <td><small class="text-muted">${prod.descripcion}</small></td>
          <td><small>${prod.marca}</small></td>
          <td class="text-center"><span class="badge bg-primary">${prod.cantidad}</span></td>
          <td class="text-end">$${prod.costo.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</td>
          <td class="text-end"><strong class="text-primary">$${prod.subtotal.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</strong></td>
        </tr>`;
        bodTotal += prod.cantidad;
        bodCost += prod.subtotal;
      });

      tabBody += `</tbody>
        <tfoot class="table-light">
          <tr class="fw-bold border-top-2">
            <td colspan="3" class="text-end py-3"><i class="fa-solid fa-warehouse me-2"></i>TOTAL ${nombreBod}:</td>
            <td class="text-center py-3 text-primary" style="font-size: 1.1rem;">${bodTotal}</td>
            <td></td>
            <td class="text-end py-3" style="font-size: 1.1rem;">$${bodCost.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</td>
          </tr>
        </tfoot>
      </table>
      </div>`;

      tabContenido += `<div class="tab-pane fade ${activo}" id="${tabId}" role="tabpanel" aria-labelledby="btn-${tabId}">${tabBody}</div>`;
      esFirst = false;
    });

    // ✅ MODAL ELEGANTE Y PROFESIONAL
    const modal = `<div class="modal fade" id="modalOrdenComprasDetallado" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-fullscreen" style="height: 90vh;">
        <div class="modal-content shadow-lg border-0">
          <!-- Header limpio -->
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title mb-0">
                <i class="fa-solid fa-shopping-cart text-primary me-2"></i>
                <strong>Orden de Compras</strong>
              </h5>
              <small class="text-muted d-block mt-1">${bodegasList.length} bodega${bodegasList.length > 1 ? 's' : ''} • ${totalProd} producto${totalProd > 1 ? 's' : ''}</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <!-- Contenido principal -->
          <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
            <!-- Tabs navigation -->
            <ul class="nav nav-tabs mb-4 border-bottom-2" role="tablist">
              ${tabBotones}
            </ul>

            <!-- Tabs content -->
            <div class="tab-content">
              ${tabContenido}
            </div>
          </div>

          <!-- Resumen final -->
          <div class="modal-body border-top bg-gradient py-3">
            <div class="row g-3 text-center">
              <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-light">
                  <div class="card-body py-2">
                    <small class="text-muted d-block mb-1"><i class="fa-solid fa-cube me-1"></i>Total Productos</small>
                    <h5 class="mb-0 text-primary">${totalProd}</h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-light">
                  <div class="card-body py-2">
                    <small class="text-muted d-block mb-1"><i class="fa-solid fa-cubes me-1"></i>Total Cantidad</small>
                    <h5 class="mb-0 text-info">${totalCant} <small>und</small></h5>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border-0 shadow-sm ">
                  <div class="card-body py-2">
                    <small class="text-muted d-block mb-1"><i class="fa-solid fa-dollar-sign me-1"></i>Costo Total</small>
                    <h5 class="mb-0" style="color:#0d6efd;">$${totalCost.toLocaleString('es-CO', { minimumFractionDigits: 2 })}</h5>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer con botones -->
          <div class="modal-footer border-top gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="fa-solid fa-times me-2"></i>Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="btnConfirmarGuardarOrden">
              <i class="fa-solid fa-check me-2"></i>Guardar Orden
            </button>
          </div>
        </div>
      </div>
    </div>`;

    // ✅ Remover modal anterior y agregar el nuevo
    $('#modalOrdenComprasDetallado').remove();
    $('body').append(modal);

    // ✅ Crear instancia de modal
    const modalInstance = new bootstrap.Modal(document.getElementById('modalOrdenComprasDetallado'), {
      backdrop: 'static',
      keyboard: false
    });
    modalInstance.show();

    // ✅ Guardar datos globales
    window.datosOrdenCompras = bodegas;
    window.resumenOrdenCompras = { totalProductos: totalProd, totalCantidad: totalCant, totalCosto: totalCost };
    window.modalOrdenInstance = modalInstance;


    // ✅ Listener de confirmar (OFF primero para evitar duplicados)
    $(document).off('click', '#btnConfirmarGuardarOrden').on('click', '#btnConfirmarGuardarOrden', function () {

      console.log({ bodegas });
      const arrayFinal = [];
      Object.entries(bodegas).forEach(([bod, items]) => {

        items.forEach(item => {

          arrayFinal.push({
            bodega: item.idBodega,
            codigo: item.codigo,
            descripcion: item.descripcion,
            marca: item.marca,
            cantidad: item.cantidad * item.can_und,
            costoCompra: item.costo,
            subtotal: item.subtotal,
            und: item.unidad || 'UND',
            can_und: item.can_und || 1
          });
        });
      });

      //console.log('✓ Enviando orden:', arrayFinal);

      // Cerrar modal
      if (window.modalOrdenInstance) {
        window.modalOrdenInstance.hide();
      }
      // Enviar a PHP
      enviarOrdenAphp(arrayFinal, window.resumenOrdenCompras);
    });

  } catch (e) {
    console.error('Error modal:', e);
    Swal.fire('Error', e.message, 'error');
  }
};

const mostrarModalOrdenComprasDetallado = (hot, dataOriginal) => {
  /* try {
     const data = hot.getData();
     console.log({ data, dataOriginal })
     return;
 
     const numMeses = dataOriginal.columnas_meses.length;
     const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;
 
     // Calcular índices dinámicamente según modo
     let indiceIdBodega, indiceBodega, indiceCodigo, indiceDescripcion, indiceMarca, indiceConversion, indiceCantidad, indiceCostoCompra, indiceComprar, indiceSubtotal;
 
     if (agrupado) {
       indiceIdBodega = -1;
       indiceBodega = -1;
       indiceCodigo = 0;
       indiceDescripcion = 1;
       indiceMarca = 2;
       indiceConversion = 4;
       indiceCantidad = 16 + numMeses;
       indiceCostoCompra = 17 + numMeses;
       indiceComprar = 15 + numMeses;
       indiceSubtotal = 18 + numMeses;
     } else {
       // 🔹 AHORA: id_bodega está en índice 2, nombre_bodega en 3
       indiceIdBodega = 2;  // 🆕 Nuevo índice para ID de bodega
       indiceBodega = 3;    // 🔄 Cambió de 2 a 3 por el nuevo id_bodega
       indiceCodigo = 0;
       indiceDescripcion = 1;
       indiceMarca = 5;     // 🔄 Cambió de 4 a 5
       indiceConversion = 7; // 🔄 Cambió de 6 a 7
       indiceCantidad = 19 + numMeses;  // 🔄 +1
       indiceCostoCompra = 20 + numMeses; // 🔄 +1
       indiceComprar = 18 + numMeses; // 🔄 +1
       indiceSubtotal = 21 + numMeses; // 🔄 +1
     }
 
     // ✅ Agrupar datos por bodega
     const datosPorBodega = {};
     let totalGeneralCantidad = 0;
     let totalGeneralCosto = 0;
 
 
     for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
       const row = data[rowIndex];
       if (!row) continue;
 
       const comprar = row[indiceComprar] ? String(row[indiceComprar]).trim() : 'NO';
       const cantidad = parseInt(row[indiceCantidad]) || 0;
 
       // ✅ Loguear primeras 3 filas para debugging
       if (rowIndex < 3) {
         console.log(`Fila ${rowIndex}: comprar="${comprar}", cantidad=${cantidad}, indiceComprar=${indiceComprar}`);
       }
       // se debe hacer la validacion por el id de la bodega se debe agrupar por el id no po r la descripcion
 
       if (comprar === 'SÍ' && cantidad > 0) {
         const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
         const idBodega = row[indiceIdBodega];
         const codigo = row[indiceCodigo] || '';
         const descripcion = row[indiceDescripcion] || '';
         const marca = row[indiceMarca] || '-';
         const costCompra = parseFloat(row[indiceCostoCompra]) || 0;
         const subtotal = parseFloat(row[indiceSubtotal]) || 0;
         const conversion = row[indiceConversion] || 1;  // 🔹 NUEVO: Extrae conversion_compra
 
         // 🔹 NUEVO: Obtener id_referencia del dataOriginal
         const registroOriginal = dataOriginal.registros.find(r => r.codigo === codigo);
         const id_referencia = registroOriginal ? registroOriginal.id : 0;
         const und = registroOriginal ? (registroOriginal.label_unidad || 'UND') : 'UND';  // 🔹 NUEVO
 
         if (!datosPorBodega[idBodega]) {
           datosPorBodega[idBodega] = {
             id: idBodega,  // 🔹 Guardar el ID de bodega
             items: []
           };
         }
 
         datosPorBodega[idBodega].items.push({
           codigo,
           descripcion,
           marca,
           cantidad,
           costoCompra,
           subtotal,
           id_referencia,        // 🔹 NUEVO: Para búsqueda en backend
           und,                  // 🔹 NUEVO: Unidad de medida
           can_und: conversion,  // 🔹 NUEVO: Factor de conversión
           pcj_iva: 0,           // 🔹 NUEVO: Porcentaje IVA (por defecto 0)
           pcj_descuento: 0      // 🔹 NUEVO: Porcentaje Descuento (por defecto 0)
         });
 
         totalGeneralCantidad += cantidad;
         totalGeneralCosto += subtotal;
       }
     }
 
 
     // 🔹 MAPEO: Crear relación nombre_bodega -> id_bodega directamente de los datos
     const bodegaNombreAID = {};
     Object.keys(datosPorBodega).forEach(idBodega => {
       bodegaNombreAID[idBodega] = datosPorBodega[idBodega].id;
     });
 
     window.bodegaNombreAID = bodegaNombreAID; // 🔹 Guardar globalmente
 
     // Si no hay datos para comprar
     if (Object.keys(datosPorBodega).length === 0) {
       Swal.fire('Alerta', 'No hay productos seleccionados para comprar (Comprar? = SÍ y Cant Pedir > 0)', 'warning');
       return;
     }
 
     // ✅ Crear HTML del modal con pestañas
     let tabsHTML = '';
     let contenidoTabsHTML = '';
     let primerTab = true;
 
     Object.keys(datosPorBodega).sort().forEach((bodega, idx) => {
       const productos = datosPorBodega[bodega].items;  // 🔹 ACTUALIZADO: Usar .items
       let totalBodegaCantidad = 0;
       let totalBodegaCosto = 0;
 
       // Calcular totales por bodega
       productos.forEach(prod => {
         totalBodegaCantidad += prod.cantidad;
         totalBodegaCosto += prod.subtotal;
       });
 
       const activa = primerTab ? 'active' : '';
       const tabId = `tab-bodega-${idx}`;
 
       // Tab button
       tabsHTML += `
         <button class="nav-link ${activa}" id="btn-${tabId}" data-bs-toggle="tab" data-bs-target="#${tabId}" type="button">
           <i class="fa-solid fa-warehouse"></i> ${bodega}
           <span class="badge bg-primary ms-2">${productos.length}</span>
         </button>
       `;
 
       // Tab content
       let tablaHTML = `
         <table class="table table-sm table-hover">
           <thead class="table-light">
             <tr>
               <th>Código</th>
               <th>Producto</th>
               <th>Marca</th>
               <th class="text-center">Cantidad</th>
               <th class="text-end">Costo Unit.</th>
               <th class="text-end">Subtotal</th>
             </tr>
           </thead>
           <tbody>
       `;
 
       productos.forEach(prod => {
         tablaHTML += `
           <tr>
             <td><strong>${prod.codigo}</strong></td>
             <td>${prod.descripcion}</td>
             <td>${prod.marca}</td>
             <td class="text-center">${prod.cantidad}</td>
             <td class="text-end">$${parseFloat(prod.costoCompra).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
             <td class="text-end"><strong>$${parseFloat(prod.subtotal).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
           </tr>
         `;
       });
 
       tablaHTML += `
           </tbody>
           <tfoot class="table-light">
             <tr>
               <td colspan="3" class="text-end fw-bold">TOTAL BODEGA:</td>
               <td class="text-center fw-bold">${totalBodegaCantidad}</td>
               <td></td>
               <td class="text-end fw-bold" style="color: #0d6efd;">$${totalBodegaCosto.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
             </tr>
           </tfoot>
         </table>
       `;
 
       contenidoTabsHTML += `
         <div class="tab-pane fade ${activa}" id="${tabId}" role="tabpanel">
           ${tablaHTML}
         </div>
       `;
 
       primerTab = false;
     });
 
     // ✅ HTML completo del modal
     const modalHTML = `
       <div class="modal fade" id="modalOrdenComprasDetallado" tabindex="-1" backdrop="static" keyboard="false">
         <div class="modal-dialog modal-lg">
           <div class="modal-content">
             <div class="modal-header border-bottom">
               <h5 class="modal-title">
                 <i class="fa-solid fa-shopping-cart"></i> Orden de Compras por Bodega
               </h5>
               <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
             </div>
             <div class="modal-body" style="max-height: 60vh; display: flex; flex-direction: column;">
               <div class="nav nav-tabs mb-3" role="tablist">
                 ${tabsHTML}
               </div>
               <div class="tab-content flex-grow-1" style="overflow-y: auto;">
                 ${contenidoTabsHTML}
               </div>
             </div>
             
             <!-- Resumen General en Footer -->
             <div class="modal-body border-top bg-light py-3">
               <div class="row text-center">
                 <div class="col-md-4">
                   <small class="text-muted d-block mb-1">TOTAL PRODUCTOS:</small>
                   <strong class="fs-6">${Object.keys(datosPorBodega).reduce((sum, b) => sum + datosPorBodega[b].items.length, 0)}</strong>  <!-- 🔹 ACTUALIZADO: .items.length -->
                 </div>
                 <div class="col-md-4">
                   <small class="text-muted d-block mb-1">TOTAL CANTIDAD:</small>
                   <strong class="fs-6">${totalGeneralCantidad} und</strong>
                 </div>
                 <div class="col-md-4">
                   <small class="text-muted d-block mb-1">COSTO TOTAL:</small>
                   <strong class="fs-6" style="color: #0d6efd;">$${totalGeneralCosto.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                 </div>
               </div>
             </div>
             
             <div class="modal-footer border-top">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
               <button type="button" class="btn btn-success" id="btnConfirmarGuardarOrden">
                 <i class="fa-solid fa-check"></i> Guardar
               </button>
             </div>
           </div>
         </div>
       </div>
     `;
 
     // ✅ Eliminar modal anterior si existe
     $('#modalOrdenComprasDetallado').remove();
 
     // ✅ Insertar modal en el DOM
     $('body').append(modalHTML);
 
     // ✅ Mostrar modal
     const modal = new bootstrap.Modal(document.getElementById('modalOrdenComprasDetallado'));
     modal.show();
 
     // ✅ Guardar array en variable global para usar al confirmar
     window.datosOrdenCompras = datosPorBodega;
     window.resumenOrdenCompras = {
       totalProductos: Object.keys(datosPorBodega).reduce((sum, b) => sum + datosPorBodega[b].items.length, 0),  // 🔹 ACTUALIZADO: .items.length
       totalCantidad: totalGeneralCantidad,
       totalCosto: totalGeneralCosto
     };
 
 
     // ✅ Event listener para botón de confirmar
     $(document).off('click', '#btnConfirmarGuardarOrden').on('click', '#btnConfirmarGuardarOrden', function () {
       // 🔹 Usar el mapeo bodega nombre -> ID que se creó al abrir el modal
       // 🔹 IMPORTANTE: Mantener estructura por bodega para modo "por_bodega"
       const arrayFinal = Object.entries(window.datosOrdenCompras).map(([nombreBodega, bodegaData]) => {
         // obtenemos el ID de bodega usando el mapeo creado
         const idBodega = window.bodegaNombreAID[nombreBodega];
 
         if (!idBodega) {
           console.warn('⚠️ No se encontró ID para bodega:', nombreBodega, 'Disponibles:', window.bodegaNombreAID);
         }
 
         return bodegaData.items.map(item => ({
           bodega: idBodega,  // 🔹 Envía el ID de bodega, no el nombre
           bodega_nombre: nombreBodega,  // 🔹 Guardar nombre para referencia
           codigo: item.codigo,
           descripcion: item.descripcion,
           marca: item.marca,
           cantidad: item.cantidad,
           costoCompra: item.costoCompra,  // 🔹 CORREGIDO: era 'costo', ahora 'costoCompra'
           subtotal: item.subtotal,
           und: item.und || 'UND',           // 🔹 CORREGIDO: era 'item.unidad'
           can_und: item.can_und || 1,
           sw: 5,                            // 🔹 NUEVO: 5 = Entrada (compra)
           id_referencia: item.id_referencia || 0,  // 🔹 NUEVO: Para búsqueda en backend
           pcj_iva: item.pcj_iva || 0,           // 🔹 Porcentaje IVA
           pcj_descuento: item.pcj_descuento || 0  // 🔹 Porcentaje Descuento
         }));
       });
 
       console.log('✓ Array final para guardar:', arrayFinal);
       console.log('🔹 Mapeo bodega nombre->ID usado:', window.bodegaNombreAID);
 
       // Cerrar modal y proceder a guardar
       modal.hide();
       enviarOrdenAphp(arrayFinal, window.resumenOrdenCompras);
     });
 
   } catch (e) {
     console.error('Error al mostrar modal de orden:', e);
     Swal.fire('Error', 'Error al procesar orden: ' + e.message, 'error');
   }*/
};

// ✅ NUEVA FUNCIÓN: Enviar orden a PHP
const enviarOrdenAphp = async (arrayOrden, resumen) => {
  try {
    if (!arrayOrden || arrayOrden.length === 0) {
      Swal.fire('Alerta', 'No hay datos para guardar', 'warning');
      return;
    }
    /*
        // Pregunta: Consolidar en una orden o una por bodega
        const result = await Swal.fire({
          title: 'Tipo de Orden',
          text: '¿Cómo deseas guardar la orden?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Una orden por bodega',
          cancelButtonText: 'Orden consolidada (bodega default)',
          allowOutsideClick: false,
          allowEscapeKey: false,
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-outline-secondary'
          }
        });
    
        if (result.dismiss && result.dismiss !== Swal.DismissReason.cancel) {
          return; // Usuario canceló
        }*/

    const modoConsolidado =/* result.dismiss === Swal.DismissReason.cancel*/ false;

    // Obtener datos del formulario
    const id_usuario = document.getElementById('id_usuario').value;
    const id_tercero = document.getElementById('id_tercero').value;
    const id_bodega_session = document.getElementById('bodega_session').value;
    const id_condicion = document.getElementById('id_condicion_pago').value;
    const id_tercero_dir = document.getElementById('id_tercero_dir').value;
    const id_tipo_documento = document.getElementById('tipo_documento').value;

    if (!id_usuario || !id_tercero || !id_condicion || !id_tipo_documento) {
      Swal.fire('Error', 'Faltan datos requeridos (Usuario, Proveedor, Condición de pago, Tipo de documento)', 'error');
      return;
    }

    // Mostrar modal de guardando
    showLoadingWithTetrisLogo('Guardando orden de compras en base de datos...', 'ISA Software');
    const ordenesPorBodega = arrayOrden.reduce((acc, item) => {
      const bodega = item.bodega;

      if (!acc[bodega]) {
        acc[bodega] = [];
      }

      acc[bodega].push(item);

      return acc;
    }, {});

    // Preparar datos para enviar
    const datosEnvio = {
      link: '../models/SugeridoCompras.php',
      op: 'guardarOrdenCompras',
      modo: 'por_bodega',
      id_usuario: id_usuario,
      id_tercero: id_tercero,
      id_bodega_default: id_bodega_session,
      id_condicion: id_condicion,
      id_tercero_dir: id_tercero_dir,
      id_tipo_documento: id_tipo_documento,
      orden: JSON.stringify(ordenesPorBodega),
      resumen: JSON.stringify(resumen),
      fecha: new Date().toLocaleString('es-CO')
    };

    const resp = await enviarPeticion(datosEnvio);

    dissminSwal();

    // Validar respuesta
    if (resp.ok === false || resp.error) {
      Swal.fire({
        title: '❌ Error al Guardar',
        html: `
          <div style="text-align: left;">
            <p><strong>Error:</strong> ${resp.error || 'Error desconocido'}</p>
            ${resp.details ? `<p style="color: #856404; font-size: 0.9rem;"><small>${resp.details}</small></p>` : ''}
          </div>
        `,
        icon: 'error',
        confirmButtonText: 'Entendido'
      });
      console.error('Error en respuesta:', resp);
      return;
    }

    //console.log('✓ Orden guardada exitosamente:', resp);

    // Mostrar resultado exitoso con números de órdenes
    let numeroOrdenes = '';
    if (Array.isArray(resp.ordenes_creadas)) {
      numeroOrdenes = resp.ordenes_creadas.join(', ');
    } else if (resp.numero_orden) {
      numeroOrdenes = resp.numero_orden;
    }

    Swal.fire({
      title: '✓ Orden Guardada Exitosamente',
      html: `
        <div style="text-align: left;">
          <p><strong>Número${Array.isArray(resp.ordenes_creadas) && resp.ordenes_creadas.length > 1 ? 's' : ''} de Orden:</strong> 
            <span style="color: #0d6efd; font-weight: bold; font-size: 1.1rem;">${numeroOrdenes}</span>
          </p>
          <p><strong>Fecha:</strong> ${new Date().toLocaleString('es-CO')}</p>
          <p><strong>Modo:</strong> ${modoConsolidado ? 'Consolidado (1 orden)' : 'Por bodega'}</p>
          <p><strong>Total Productos:</strong> ${resumen.totalProductos}</p>
          <p><strong>Total Cantidad:</strong> ${resumen.totalCantidad} unidades</p>
          <p><strong>Costo Total:</strong> <span style="color: #0d6efd; font-weight: bold; font-size: 1.2rem;">$${resumen.totalCosto.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></p>
        </div>
      `,
      icon: 'success',
      confirmButtonText: 'Entendido',
      customClass: {
        confirmButton: 'btn btn-success'
      }
    }).then(() => {
      // Limpiar tabla y resetear
      location.reload();
    });

  } catch (e) {
    dissminSwal();
    console.error('Error al enviar orden:', e);
    Swal.fire({
      title: '❌ Error',
      html: `
        <div style="text-align: left;">
          <p><strong>Error:</strong> ${e.message}</p>
          <small style="color: #666;">Por favor, contacte al administrador si el problema persiste.</small>
        </div>
      `,
      icon: 'error',
      confirmButtonText: 'Entendido'
    });
  }
};

const guardarCompras = (hot, dataOriginal) => {
  try {
    // ✅ Mostrar modal detallado con pestañas por bodega (nueva versión)
    generarModalOrdenPorBodega(hot, dataOriginal);

  } catch (e) {
    console.error('Error al guardar compras:', e);
    Swal.fire('Error', 'Error al procesar compras: ' + e.message, 'error');
  }
};


$(async function () {
  getMarcas();
  getCategorias1();
  getCategorias2();
  getCategorias3();
  getCategorias4();
  listarAlmacenes();

  try {
    tiposDocumentos(16, 0, 'tipo_documento');
  } catch (e) {
    console.error('Error al cargar tipos de documentos:', e);
  }

  permisos = await permisosUsuario(id_modulo)
  const {
    general,
    modulos
  } = await cargarConfiguraciones();
  ConfGeneral = general;
  ConfModulos = modulos[id_modulo];
  console.log('Configuraciones cargadas:', { ConfGeneral, ConfModulos });

  $('#filtro_proveedores').autocomplete({
    source: async function (request, response) {
      const data = {
        link: '../models/Terceros.php',
        filtro: $.trim(request.term),
        tipo_tercero: 2,
        estado: 'A',
        op: 's_tercero'
      };
      try {
        const resp = await enviarPeticion(data);
        let datos = resp.data;

        if (Array.isArray(datos)) {
          response(datos);
        } else {
          response([]);
        }
      } catch (error) {
        console.error("Error en la solicitud:", error);
        response([]);
      }
    },
    minLength: 3,
    select: async function (event, ui) {
      const item = ui.item;
      console.log('Tercero seleccionado:', item);
      if (ConfModulos.SoloTercerosProveedores == 'S') {
        if (item.es_proveedor != 'S') {
          Swal.fire("Error", "El tercero no es un proveedor", "error");
          return;
        }
      }

      if (item.id_condicion_prov == 0) {
        Swal.fire("Error", "El tercero no tiene condicion de pago para proveedor", "error");
        return;
      }

      $("#id_tercero").val(item.id);
      $("#nit").val(item.nit);
      $("#tratamiento").val(item.tratamiento);
      $("#razon_comercial").val(item.razon_comercial);
      $("#textDireccion").val(item.direccion)
      $("#id_tercero_dir").val(item.id_direccion);
      $("#condicion_pago").val(item.des_condicion);
      $("#id_condicion_pago").val(item.id_condicion_prov);

    }
  });

  // Event listener para mostrar/ocultar filtro bodega según agruparProductos
  $('#filtro_agrurar_productos').on('change', function () {
    const estaAgrupado = $(this).is(':checked');
    const filtroBodegas = $('#div-filtro-bodegas');
    const selectBodegas = $('#filtro_bodegas');
    const checkTodasBodegas = $('#chk_seleccionar_todas_bodegas');

    if (estaAgrupado) {
      // AGRUPADO: Ocultar, deshabilitar y limpiar filtro de bodegas
      filtroBodegas.slideUp(300);
      selectBodegas.prop('disabled', true).val([]).trigger('change');
      checkTodasBodegas.prop('disabled', true).prop('checked', false);
    } else {
      // NO AGRUPADO: Mostrar y habilitar filtro de bodegas
      filtroBodegas.slideDown(300);
      selectBodegas.prop('disabled', false);
      checkTodasBodegas.prop('disabled', false);
    }
  });

  $('#btnGenerarSugeridoBottom').on('click', generarSugerido);

  // Event listener para botón de guardar orden de compras
  $(document).on('click', '#btnGuardarOrdenCompras', function () {
    try {
      if (!window.hotInstance || !window.dataOriginal) {
        Swal.fire('Alerta', 'No hay datos para guardar', 'warning');
        return;
      }

      const hot = window.hotInstance;
      const data = window.dataOriginal;

      // Llamar a la función que guardar compras y envía a PHP
      guardarCompras(hot, data);

    } catch (e) {
      console.error('Error al procesar guardar:', e);
      Swal.fire('Error', 'Error al procesar: ' + e.message, 'error');
    }
  });
  dissminSwal();
})