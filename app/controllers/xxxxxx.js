const getMarcas =async ()=>{
  try {
    const resp = await enviarPeticion({
      link : '../models/SugeridoCompras.php',
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

const getCategorias1 =async ()=>{
  try {
    const resp = await enviarPeticion({ 
      link : '../models/SugeridoCompras.php',
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


const getCategorias2 =async ()=>{
  try {
    const resp = await enviarPeticion({ 
      link : '../models/SugeridoCompras.php',
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


const getCategorias3 =async ()=>{
  try {
    const resp = await enviarPeticion({ 
      link : '../models/SugeridoCompras.php',
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


const getCategorias4 =async ()=>{
  try {
    const resp = await enviarPeticion({ 
      link : '../models/SugeridoCompras.php',
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
    console.warn({resp})
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
    $('#chk_seleccionar_todas_bodegas').on('change', function() {
      if ($(this).is(':checked')) {
        // Marcar todas las opciones excepto la primera (vacía)
        const todasLasBodegas = $("#filtro_bodegas option").map(function() {
          return $(this).val();
        }).get().filter(v => v !== '');
        $("#filtro_bodegas").val(todasLasBodegas).trigger("change");
      } else {
        // Desmarcar todas
        $("#filtro_bodegas").val([]).trigger("change");
      }
    });

    // Actualizar checkbox cuando se cambian las bodegas
    $('#filtro_bodegas').on('change', function() {
      const todasLasBodegas = $("#filtro_bodegas option").map(function() {
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
    //Validar que al menos una bodega esté seleccionada (SOLO si NO está agrupado)
    const checkboxElement = $('#filtro_agrurar_productos');
    console.log('Elemento checkbox encontrado:', checkboxElement.length > 0);
    console.log('Valor del checkbox:', checkboxElement.is(':checked'));
    
    const estaAgrupado = checkboxElement.is(':checked') === true;
    let bodegasSeleccionadas = [];
    
    console.log('¿Está agrupado?', estaAgrupado);
    
    if (!estaAgrupado) {
      bodegasSeleccionadas = $('#filtro_bodegas').val();
      console.log('Bodegas seleccionadas:', bodegasSeleccionadas);
      if (!bodegasSeleccionadas || bodegasSeleccionadas.length === 0) {
        Swal.fire('Alerta', 'Debe seleccionar al menos una bodega', 'warning');
        return;
      }
    }

    showLoadingSwalAlert2('<i class="fa fa-spinner fa-spin"></i>', 'Generando sugerido de compras...', false);

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
      agruparProductos : estaAgrupado ? 1 : 0
    });

    dissminSwal();

    if (!resp.ok) {
      Swal.fire('Error', resp.error || 'Error al generar sugerido', 'error');
      console.error('Error:', resp);
      return;
    }

    console.warn('Respuesta:', resp);
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
      headers.push('Bodega', 'Bodega Principal');
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
      'Cant Sug.',
      'Comprar?'
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
      const rowData = [
        row.codigo,
        row.descripcion
      ];

      // Solo agregar bodega y bodega_principal si NO está agrupado
      if (!agrupado) {
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
      const sugerido = parseInt(row.sugerido_dias) || 0;
      const ultimoCosto = parseFloat(row.ultimo_costo_compra) || 0;
      const rotacionDiaria = parseFloat(row.rotacion_diaria) || 0;
      const cantSugerida = Math.round(rotacionDiaria * data.dias);
      const stockBodegaPrincipal = parseFloat(row.stock_bodega_principal) || 0;
      
      rowData.push(
        row.rotacion_total,
        row.stock,
        stockBodegaPrincipal,
        parseFloat(row.costo_promedio).toFixed(2),
        parseFloat(row.ultimo_costo_compra).toFixed(2),
        row.promedio,
        rotacionDiaria.toFixed(2),
        `Sug. ${data.dias}d`,
        cantSugerida,
        row.comprar
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
      headers.push('Bodega', 'Bodega Principal');
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
      'Cant Sug.',
      'Comprar?'
    ]);

    // HTML para el contenedor con filtros de resultados
    let html = `
      <div class="alert alert-info mb-3">
        <strong>Período Analizado:</strong> ${data.meses} meses | 
        <strong>Sugerencia de compra por:</strong> ${data.dias} días |
        <strong>Productos a comprar:</strong> ${data.resumen.total_productos}
      </div>
      
      <!-- Filtros en resultados -->
      <div class="row g-3 mb-3">`;

    // Solo mostrar filtro bodega si NO está agrupado
    if (!agrupado) {
      html += `
        <div class="col-md-3">
          <label class="form-label fw-semibold">Filtrar por Bodega</label>
          <select id="filtro_resultados_bodega" class="form-select form-select-sm">
            <option value="">Todas las bodegas</option>
          </select>
        </div>`;
    }

    html += `
        <div class="col-md-3">
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
          <button class="btn btn-sm btn-outline-secondary w-100 mt-2" id="btnLimpiarFiltrosResultados" title="Limpiar filtros">
            <i class="fa-solid fa-undo"></i> Limpiar
          </button>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">&nbsp;</label>
          <button class="btn btn-sm btn-success w-100 mt-2" id="btnExportarExcel" title="Exportar a Excel">
            <i class="fa-solid fa-file-excel"></i> Excel
          </button>
        </div>
      </div>

      <div id="sugeridoHandsontable" style="width: 100%; height: calc(100vh - 400px); overflow: auto;"></div>
    `;

    // Insertar HTML en el DOM
    $('#dvSugerido').html(html);

    // Esperar un poco para que el DOM esté listo
    setTimeout(() => {
      const container = document.getElementById('sugeridoHandsontable');
      
      if (!container) {
        console.error('Contenedor no encontrado');
        return;
      }

      // Crear configuración de columnas SIMPLIFICADA
      const columns = headers.map((header, index) => {
        const col = {
          title: header,
          type: 'text',
          readOnly: true
        };

        // EDITABLE: Solo Cant Sug.
        if (header === 'Cant Sug.') {
          col.type = 'numeric';
          col.readOnly = false;
        }

        // Ancho para descripción
        if (header === 'Descripción') {
          col.width = 250;
        }

        return col;
      });

      // Inicializar Handsontable OPTIMIZADO para GRANDES VOLÚMENES de datos
      const hot = new Handsontable(container, {
        data: tableData,
        colHeaders: headers,
        columns: columns,
        rowHeaders: false,
        contextMenu: true,
        manualColumnResize: true,
        height: 'calc(100vh - 400px)',
        width: '100%',
        licenseKey: 'non-commercial-and-evaluation',
        
        // VIRTUALIZACIÓN - Solo renderiza filas visibles
        renderAllRows: false,
        viewportRowRenderingOffset: 10,
        
        // OPTIMIZACIONES
        noNewRowsMode: true,
        outsideClickDeselects: true,

        // FILTROS en los encabezados
        filters: true,
        dropdownMenu: ['filter_by_condition', 'filter_action_bar'],
        
        columnSorting: true
      });

      // Guardar referencia global
      window.hotInstance = hot;
      window.dataOriginal = data;
      window.tableDataOriginal = tableData.slice();  // Copiar datos originales

      // Calcular índices dinámicamente (Descripción siempre en posición 1)
      const offsetBodega = agrupado ? -1 : 2;  // -1 si no existe la columna
      const indiceMarcaBase = agrupado ? 2 : 4;
      const indiceUnidadBase = agrupado ? 3 : 5;
      const numMeses = data.columnas_meses.length;
      // Índice de comprar: Agrupado = 5+numMeses, Normal = 8+numMeses
      const indiceComprar = agrupado ? (5 + numMeses) : (8 + numMeses);

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

          // Filtro por sugerencia
          if (soloSugerencia && row[indiceComprar] !== 'SÍ') {
            return false;
          }

          return true;
        });

        // Actualizar tabla con datos filtrados
        hot.loadData(datosFiltrados);
      };

      // Event listeners para filtros
      if (!agrupado) {
        $('#filtro_resultados_bodega').on('change', aplicarFiltrosResultados);
      }
      $('#filtro_resultados_marca').on('change', aplicarFiltrosResultados);
      $('#filtro_resultados_sugerencia').on('change', aplicarFiltrosResultados);

      // Botón de limpiar filtros
      $('#btnLimpiarFiltrosResultados').on('click', function() {
        if (!agrupado) {
          $('#filtro_resultados_bodega').val('');
        }
        $('#filtro_resultados_marca').val('');
        $('#filtro_resultados_sugerencia').prop('checked', false);
        hot.loadData(window.tableDataOriginal);
      });

      // Botón de exportar a Excel
      $('#btnExportarExcel').on('click', exportarAExcel);

    }, 100);

    // Cambiar a pestaña de resultados 
    $('[data-bs-target="#navs-resultados"]').tab('show');

  } catch (e) {
    console.error('Error al mostrar resultados:', e);
    Swal.fire('Error', 'Error al mostrar los resultados: ' + e.message, 'error');
  }
};

// Función para actualizar resumen por bodega EN TIEMPO REAL
const actualizarResumenBodega = (hot, dataOriginal) => {
  try {
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses.length;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

    // Calcular índices dinámicamente según modo
    let indiceBodega, indiceCantidad, indiceCostoCompra, indiceComprar;
    
    if (agrupado) {
      // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses
      indiceBodega = -1;  // No existe
      indiceCantidad = 5 + numMeses;
      indiceCostoCompra = 6 + numMeses;
      indiceComprar = 8 + numMeses;
    } else {
      // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses
      indiceBodega = 2;
      indiceCantidad = 8 + numMeses;
      indiceCostoCompra = 9 + numMeses;
      indiceComprar = 11 + numMeses;
    }

    // Compilar resumen por bodega
    const resumenBodegas = {};
    let totalGeneralCantidad = 0;
    let totalGeneralCosto = 0;

    for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
      const row = data[rowIndex];
      const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
      const comprar = row[indiceComprar];

      if (comprar === 'SÍ') {
        const cantidad = parseInt(row[indiceCantidad]) || 0;
        const costoCompra = parseFloat(row[indiceCostoCompra]) || 0;
        const subtotal = cantidad * costoCompra;

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
      }
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
  }
};

// Función para actualizar totales al editar cantidad - OPTIMIZADA
const actualizarTotales = (hot, dataOriginal) => {
  try {
    // Limpiar timeout anterior si existe
    if (timeoutActualizacion) {
      clearTimeout(timeoutActualizacion);
    }
    
    // Debounce: esperar 200ms antes de actualizar
    timeoutActualizacion = setTimeout(() => {
      let totalCantidad = 0;
      let totalCosto = 0;

      const data = hot.getData();
      const numMeses = dataOriginal.columnas_meses.length;
      const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;
      
      // Calcular índices dinámicamente según modo
      let indiceBodega, indiceCantSug, indiceCantidad, indiceCostoCompra, indiceTotal, indiceComprar;
      
      if (agrupado) {
        // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses
        indiceBodega = -1;  // No existe
        indiceCantSug = 4 + numMeses;
        indiceCantidad = 5 + numMeses;
        indiceCostoCompra = 6 + numMeses;
        indiceTotal = 7 + numMeses;
        indiceComprar = 8 + numMeses;
      } else {
        // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses
        indiceBodega = 2;
        indiceCantSug = 7 + numMeses;
        indiceCantidad = 8 + numMeses;
        indiceCostoCompra = 9 + numMeses;
        indiceTotal = 10 + numMeses;
        indiceComprar = 11 + numMeses;
      }

      console.log('Actualizando totales...', {
        numMeses,
        agrupado,
        indiceCantidad,
        indiceCostoCompra,
        indiceTotal,
        totalFilas: data.length
      });

      // OPTIMIZADO: Solo actualizar datos en memoria (mucho más rápido que setDataAtCell)
      const changes = [];
      
      for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
        const row = data[rowIndex];
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
      hot.setDataAtCell(changes, 'loadData');
      
      console.log('Totales calculados:', {
        totalCantidad,
        totalCosto,
        filasConCambios: changes.length
      });
      
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
      actualizarResumenBodega(hot, dataOriginal);
      
      // Construir array de compras
      construirArrayCompras(hot, dataOriginal);
      
    }, 200);  // Esperar 200ms

  } catch (e) {
    console.error('Error al actualizar totales:', e);
  }
};

// Función para construir array de objetos de compras - OPTIMIZADA
const construirArrayCompras = (hot, dataOriginal) => {
  try {
    const comprasObjeto = {};
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses.length;
    const dataLength = data.length;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

    // Calcular índices dinámicamente según modo
    let indiceBodega, indiceCodigo, indiceDescripcion, indiceUnidad, indiceCantidad, indiceCostoCompra, indiceComprar;
    
    if (agrupado) {
      // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses
      indiceBodega = -1;  // No existe
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 3;
      indiceCantidad = 5 + numMeses;
      indiceCostoCompra = 6 + numMeses;
      indiceComprar = 8 + numMeses;
    } else {
      // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses
      indiceBodega = 2;
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 5;
      indiceCantidad = 8 + numMeses;
      indiceCostoCompra = 9 + numMeses;
      indiceComprar = 11 + numMeses;
    }

    // FOR LOOP es más rápido que forEach para grandes volúmenes
    for (let rowIndex = 0; rowIndex < dataLength; rowIndex++) {
      const row = data[rowIndex];
      const comprar = row[indiceComprar];
      
      if (comprar === 'SÍ') {
        const cantidad = parseInt(row[indiceCantidad]) || 0;
        if (cantidad > 0) {
          const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
          const costoCompra = parseFloat(row[indiceCostoCompra]) || 0;
          
          if (!comprasObjeto[bodega]) {
            comprasObjeto[bodega] = {
              bodega: bodega,
              items: []
            };
          }

          comprasObjeto[bodega].items.push({
            codigo: row[indiceCodigo] || '',
            descripcion: row[indiceDescripcion] || '',
            cantidad: cantidad,
            costo: costoCompra,
            unidad: row[indiceUnidad] || 'UND',
            total: (cantidad * costoCompra).toFixed(2)
          });
        }
      }
    }

    // Guardar el array para uso posterior
    window.comprasArray = Object.values(comprasObjeto);
    console.log('Array de compras actualizado:', window.comprasArray.length, 'bodegas');

  } catch (e) {
    console.error('Error al construir array de compras:', e);
  }
};

// Función para guardar compras y mostrar resumen por bodega
const guardarCompras = (hot, dataOriginal) => {
  try {
    const data = hot.getData();
    const numMeses = dataOriginal.columnas_meses.length;
    const agrupado = dataOriginal.agrupar_productos === 1 || dataOriginal.agrupar_productos === true;

    // Calcular índices dinámicamente según modo
    let indiceBodega, indiceCodigo, indiceDescripcion, indiceUnidad, indiceCantidad, indiceCostoCompra, indiceComprar;
    
    if (agrupado) {
      // Modo agrupado: Código, Descripción, Marca, Unidad, Conversión, ...meses
      indiceBodega = -1;  // No existe
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 3;
      indiceCantidad = 5 + numMeses;
      indiceCostoCompra = 6 + numMeses;
      indiceComprar = 8 + numMeses;
    } else {
      // Modo normal: Código, Descripción, Bodega, BodegaPpal, Marca, Unidad, Conversión, ...meses
      indiceBodega = 2;
      indiceCodigo = 0;
      indiceDescripcion = 1;
      indiceUnidad = 5;
      indiceCantidad = 8 + numMeses;
      indiceCostoCompra = 9 + numMeses;
      indiceComprar = 11 + numMeses;
    }

    // Compilar resumen por bodega
    const resumenBodegas = {};
    let totalGeneralCantidad = 0;
    let totalGeneralCosto = 0;

    for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
      const row = data[rowIndex];
      const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
      const comprar = row[indiceComprar];

      if (comprar === 'SÍ') {
        const cantidad = parseInt(row[indiceCantidad]) || 0;
        const costoCompra = parseFloat(row[indiceCostoCompra]) || 0;
        const subtotal = cantidad * costoCompra;

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
      }
    }

    // Construir el array final que se va a guardar
    const comprasFinales = Object.values(resumenBodegas).map(bodega => ({
      bodega: bodega.bodega,
      cantidad_total: bodega.cantidad,
      costo_total: bodega.costo.toFixed(2),
      items: bodega.items,
      detalles: []
    }));

    // Llenar detalles de cada bodega
    for (let rowIndex = 0; rowIndex < data.length; rowIndex++) {
      const row = data[rowIndex];
      const bodega = agrupado ? 'GENERAL' : (row[indiceBodega] || 'SIN BODEGA');
      const comprar = row[indiceComprar];

      if (comprar === 'SÍ') {
        const cantidad = parseInt(row[indiceCantidad]) || 0;
        if (cantidad > 0) {
          const bodegaObj = comprasFinales.find(b => b.bodega === bodega);
          if (bodegaObj) {
            bodegaObj.detalles.push({
              codigo: row[indiceCodigo] || '',
              descripcion: row[indiceDescripcion] || '',
              cantidad: cantidad,
              costo_unitario: parseFloat(row[indiceCostoCompra]) || 0,
              subtotal: (cantidad * (parseFloat(row[indiceCostoCompra]) || 0)).toFixed(2),
              unidad: row[indiceUnidad] || 'UND'
            });
          }
        }
      }
    }

    // Mostrar en console
    console.log('=== SUGERIDO DE COMPRAS PARA GUARDAR ===');
    console.log('Fecha:', new Date().toLocaleString());
    console.log('Total General:', {
      cantidad: totalGeneralCantidad,
      costo: totalGeneralCosto.toFixed(2)
    });
    console.log('Resumen por Bodega:', resumenBodegas);
    console.log('Array Completo para Guardar:', comprasFinales);
    console.table(comprasFinales);

    // Mostrar alerta
    Swal.fire({
      title: 'Datos Listos',
      html: `
        <p><strong>Total a Comprar:</strong></p>
        <ul style="text-align: left;">
          <li>Cantidad: ${totalGeneralCantidad} unidades</li>
          <li>Costo: <strong style="color: #0d6efd; font-size: 1.1rem;">$${totalGeneralCosto.toFixed(2)}</strong></li>
          <li>Bodegas: ${Object.keys(resumenBodegas).length}</li>
        </ul>
        <p style="color: #666; font-size: 0.9rem;">Los datos han sido mostrados en la consola del navegador (F12 → Console)</p>
      `,
      icon: 'success',
      confirmButtonText: 'Entendido'
    });

  } catch (e) {
    console.error('Error al guardar compras:', e);
    Swal.fire('Error', 'Error al procesar compras: ' + e.message, 'error');
  }
};

$(function(){
  getMarcas();
  getCategorias1();
  getCategorias2();
  getCategorias3();
  getCategorias4();
  listarAlmacenes();

  // Event listener para mostrar/ocultar filtro bodega según agruparProductos
  $('#filtro_agrurar_productos').on('change', function() {
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

})