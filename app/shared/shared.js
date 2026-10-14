const GetDataUser = () => {
  const dataCookie = leerCookieSeguraComoObjeto('dataApi');
  return dataCookie;
}

function enviarFormData(data) {
  return new Promise((resolve, reject) => {
    let metodo = 'POST';
    // Verifica que se haya enviado un 'link'
    if (data.link == undefined) {
      reject('No se envió el link');
    }
    // Si hay un método personalizado, usa ese
    if (data.metodo != undefined) {
      metodo = data.metodo;
    }
    // Crea un objeto FormData
    let formData = new FormData();

    // Si se pasan otros datos en 'data', los agregamos al FormData
    for (let key in data) {
      // Excluye la clave 'link' y 'metodo' para evitar sobrescribirlos
      if (data.hasOwnProperty(key) && key !== 'link' && key !== 'metodo' && key !== 'archivos') {
        formData.append(key, data[key]);
      }
    }
    // Si se incluyen archivos (imágenes o archivos en general), añádelos a FormData
    if (data.archivos && data.archivos.length > 0) {
      for (let i = 0; i < data.archivos.length; i++) {
        formData.append("file[]", data.archivos[i]); // 'file[]' puede ser el nombre del campo de archivo en el servidor
      }
    }

    // Realiza la petición AJAX
    $.ajax({
      url: data.link, // URL del servidor o script PHP
      type: metodo,
      data: formData,
      dataType: "json", // Esperamos una respuesta JSON
      processData: false, // No procesar los datos antes de enviarlos
      contentType: false, // No enviar un tipo de contenido porque 'FormData' maneja eso automáticamente
      async: true,
      success: function (response) {
        resolve(response);
      },
      error: function (error) {
        reject(error);
      }
    });

  });

}

const formatearArrayRequest = (data) => {
  if (data.length == 0) return;
  let nuevoObj = {};
  data.forEach(item => {
    nuevoObj[item.name] = item.value
  });
  return nuevoObj;
}

$.fn.serializeArrayAll = function () {
  var obj = [];
  $('[name]', this).each(function () {
    if ($(this).is(':checkbox')) {
      // Si es un checkbox, agregamos un objeto con su estado (checked/unchecked)
      obj.push({
        name: this.name,
        value: this.checked ? '1' : '0'
      });
    }
  });
  $(':disabled[name]', this).each(function () {
    if ($(this).is(':checkbox')) {
      // Si es un checkbox, agregamos un objeto con su estado (checked/unchecked)
      obj.push({
        name: this.name,
        value: this.checked ? '1' : '0'
      });
    }
  });

  return this.serializeArray().concat(obj);
};


const showLoadingSwalAlert2 = (title, html, loading) => {
  Swal.fire({
    title,
    html,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    onOpen: () => {
      if (loading) {
        Swal.showLoading();
      }
    }
  });
}

const showLoadingSwal = (title = "Cargando", message = "Por favor espere...") => {
  Swal.fire({
    html: `
      <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:#2c3e50;">
        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
        <div style="font-size:1.4rem; font-weight:bold;">${title}</div>
        <span style="font-size:1rem; color:#34495e;">${message}</span>
      </div>
    `,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
  });
};

const showLoadingWithTetrisLogo = (message = "Cargando...", title = "ISA Software") => {
  const tetrisLogoHtml = `
    <div style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
      <div style="display: flex; justify-content: center; align-items: center;">
        <div class="tetris-logo-loader">
          <div class="tetris-block size-1 purple-light"></div>
          <div class="tetris-block size-2 purple-medium"></div>
          <div class="tetris-block size-4-v purple-dark"></div>
          <div class="tetris-block size-3-tall purple-light"></div>
          <div class="tetris-block size-2-tall purple-medium"></div>
        </div>
      </div>
      ${title ? `<div style="font-size:1.2rem; font-weight:bold; color:#696cff;">${title}</div>` : ''}
      <div style="font-size:0.95rem; color:#696cff;">${message}</div>
    </div>
  `;

  Swal.fire({
    html: tetrisLogoHtml,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    willOpen: () => {
      // Asegurar que los estilos de CSS se apliquen correctamente
      setTimeout(() => {
        const swalContent = document.querySelector('.swal2-html-container');
        if (swalContent) {
          swalContent.style.padding = '20px 0';
        }
      }, 100);
    }
  });
};

const dissminSwal = () => {
  Swal.close();
}

const ShowAlert = (mensaje = '', icono) => {
  color = icono;
  iicons = '';
  switch (icono) {
    case "danger":
      iicons = '<i class="fa-solid fa-xmark ' + icono + '"></i>';
      break;
    case "warning":
      iicons = '<i class="fa-solid fa-triangle-exclamation ' + icono + '"></i>';
      break;
    case "success":
      iicons = '<i class="fa-regular fa-circle-check ' + icono + '"></i>';
      break;
    case "info":
      iicons = '<i class="fa-solid fa-circle-info ' + icono + '"></i>';
      break;
  }
  return `<div class="alert alert-${color}" >${iicons}  <span class="m-1">${mensaje}</span>!</div>`
}


const createDataTable = (
  tableId,
  data,
  dataNames,
  columnNames,
  editCallback,
  deleteCallback,
  otherButton = false,
  boton = '',
  otherCallback = '',
  btnExport = false,
  currencyColumns = [], // columnas que deben formatearse como moneda
  currencyCode = 'COP', // moneda por defecto
  customColumnClasses = [],// 👈 clases personalizadas por columna,
  sumColumns = [],
  pageLength = null,
  cortarTexto = false
) => {
  // 🔄 Destruir DataTable previo si existe
  if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
    $(`#${tableId}`).DataTable().clear().destroy();
  }

  // Crear las columnas para el DataTable
  const columns = columnNames.map((name, index) => {
    const dataKey = dataNames[index];

    // Buscar si hay una clase personalizada para esta columna
    const customClass = customColumnClasses.find(c => c.data === dataKey)?.className || '';

    // Mostrar imagen si la columna es 'imagen_td'
    if (dataKey === 'imagen_td') {
      return {
        title: name,
        data: dataKey,
        render: function (data) {
          return data
            ? `<img src="${data}" alt="Imagen" style="height:40px;width:auto;" />`
            : '<span class="text-muted">Sin imagen</span>';
        },
        orderable: false,
        className: customClass,
      };
    }

    // Formato moneda si el campo está en currencyColumns
    if (currencyColumns.includes(dataKey)) {
      return {
        title: name,
        data: dataKey,
        render: function (data) {
          if (data === null || data === undefined || data === '') return '';
          /* return new Intl.NumberFormat('es-CO', {
             style: 'currency',
             currency: currencyCode,
             minimumFractionDigits: 0,
           }).format(data);*/
          return formatNum(data, '$')
        },
        className: `text-start ${customClass}`,
      };
    }

    // Columna normal 
    return {
      title: name,
      data: dataKey,
      render: function (data, type) {
        if (cortarTexto && type === 'display' && data && data.length > 50) {
          return `<span title="${data}">${data.substr(0, 50)}...</span>`;
        }
        return data ?? '';
      },
      className: customClass,
    };
  });

  // Generar botones por defecto
  let defaultButtons = '';

  if (typeof editCallback === 'function') {
    defaultButtons += `
      <button class="edit-btn btn btn-sm btn-outline-warning" title="Editar" aria-label="Editar">
        <i class="fa-regular fa-pen-to-square"></i>
      </button>
    `;
  }

  if (typeof deleteCallback === 'function') {
    defaultButtons += `
      <button class="delete-btn btn btn-sm btn-outline-danger" title="Eliminar" aria-label="Eliminar">
        <i class="fa-regular fa-trash-can"></i>
      </button>
    `;
  }

  // Añadir columna de acciones si aplica
  if (defaultButtons || otherButton) {
    let extraBtn = '';
    if (otherButton && boton !== '') {
      extraBtn = boton;
    }

    columns.push({
      title: 'Acciones',
      data: null,
      defaultContent: `
        <div class="btn-group btn-group-sm" aria-label="Acciones">
          ${defaultButtons} ${extraBtn}
        </div>
      `,
      orderable: false,
    });
  }
  /*if (sumColumns.length > 0) {
    // Generar tfoot si no existe
    if ($(`#${tableId} tfoot`).length === 0) {
      const footerHtml = columnNames
        .map(() => '<th></th>')
        .join('');
      $(`#${tableId}`).append(`<tfoot><tr>${footerHtml}</tr></tfoot>`);
    }
  }*/
  if (sumColumns.length > 0) {
    if ($(`#${tableId} tfoot`).length === 0) {
      const footerHtml = columns
        .map(() => '<th></th>')
        .join('');

      $(`#${tableId}`).append(`<tfoot><tr>${footerHtml}</tr></tfoot>`);
    }
  }


  // Configuración inicial del DataTable
  const config = {
    data,
    columns,
    pageLength: pageLength == null ? 50 : pageLength,   // ✅ número inicial de registros por página
    language: {
      sProcessing: 'Procesando...',
      sLengthMenu: 'Mostrar _MENU_ registros',
      sZeroRecords: 'No se encontraron resultados',
      sEmptyTable: 'Ningún dato disponible en esta tabla',
      sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
      sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
      sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
      sSearch: 'Buscar:',
      sLoadingRecords: 'Cargando...',
      oPaginate: {
        sFirst: 'Primero',
        sLast: 'Último',
        sNext: 'Siguiente',
        sPrevious: 'Anterior',
      },
      oAria: {
        sSortAscending: ': Activar para ordenar la columna de manera ascendente',
        sSortDescending: ': Activar para ordenar la columna de manera descendente',
      },
    },
    footerCallback: function (row, data, start, end, display) {
      const api = this.api();

      sumColumns.forEach((dataKey) => {
        // Obtener el índice de la columna por el dataName
        const colIndex = dataNames.indexOf(dataKey);
        if (colIndex === -1) return;

        // Sumar todos los valores visibles de la columna
        const total = api
          .column(colIndex, { page: 'current' })
          .data()
          .reduce((sum, val) => {
            const num = parseFloat(val.toString().replace(/[^\d.-]/g, ''));
            return sum + (isNaN(num) ? 0 : num);
          }, 0);

        // Mostrar total formateado en el tfoot
        const formatted = /*new Intl.NumberFormat('es-CO', {
          style: 'currency',
          currency: currencyCode,
          minimumFractionDigits: 0,
        }).format(total);*/
          formatNum(total, '$')

        $(api.column(colIndex).footer()).html(`<strong>${formatted}</strong>`);
      });
    }

  };

  // Activar exportación si se requiere
  if (btnExport) {
    config.dom = 'Bfrtip';
    config.buttons = ['copy', 'csv', 'excel', 'pdf', 'print'];
  }

  // Inicializar DataTable
  const table = $(`#${tableId}`).DataTable(config);

  // Añadir event listeners para los botones de acción
  $(`#${tableId} tbody`).off('click');

  $(`#${tableId} tbody`).on('click', 'button.edit-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    editCallback(rowData);
  });

  $(`#${tableId} tbody`).on('click', 'button.delete-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    deleteCallback(rowData);
  });

  if (otherButton && typeof otherCallback === 'function') {
    $(`#${tableId} tbody`).on('click', 'button.other-btn', function () {
      const rowData = table.row($(this).closest('tr')).data();
      otherCallback(rowData);
    });
  }
};


const createDataTablev1 = (
  tableId,
  data,
  dataNames,
  columnNames,
  editCallback,
  deleteCallback,
  otherButton = false,
  boton = '',
  otherCallback = '',
  btnExport = false,
  currencyColumns = [], // 👈 columnas que deben formatearse como moneda
  currencyCode = 'COP' // 👈 moneda por defecto (parametrizable)
) => {
  // 🔄 Destruir DataTable previo si existe
  if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
    $(`#${tableId}`).DataTable().clear().destroy();
  }

  // Crear las columnas para el DataTable
  const columns = columnNames.map((name, index) => {
    const dataKey = dataNames[index];

    // Mostrar imagen si la columna es 'imagen_td'
    if (dataKey === 'imagen_td') {
      return {
        title: name,
        data: dataKey,
        render: function (data) {
          return data
            ? `<img src="${data}" alt="Imagen" style="height:40px;width:auto;" />`
            : '<span class="text-muted">Sin imagen</span>';
        },
        orderable: false,
      };
    }

    // Formato moneda si el campo está en currencyColumns
    if (currencyColumns.includes(dataKey)) {
      return {
        title: name,
        data: dataKey,
        render: function (data) {
          if (data === null || data === undefined || data === '') return '';
          return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 0,
          }).format(data);
        },
        className: 'text-start', // alineado a la derecha
      };
    }

    // Columna normal 
    return {
      title: name,
      data: dataKey,
      render: function (data, type) {
        if (type === 'display' && data && data.length > 30) {
          return `<span title="${data}">${data.substr(0, 30)}...</span>`;
        }
        return data ?? '';
      }
    };

  });

  // Generar botones por defecto
  let defaultButtons = '';

  if (typeof editCallback === 'function') {
    defaultButtons += `
      <button class="edit-btn btn btn-sm btn-outline-warning" title="Editar" aria-label="Editar">
        <i class="fa-regular fa-pen-to-square"></i>
      </button>
    `;
  }

  if (typeof deleteCallback === 'function') {
    defaultButtons += `
      <button class="delete-btn btn btn-sm btn-outline-danger" title="Eliminar" aria-label="Eliminar">
        <i class="fa-regular fa-trash-can"></i>
      </button>
    `;
  }

  // Añadir columna de acciones si aplica
  if (defaultButtons || otherButton) {
    let extraBtn = '';
    if (otherButton && boton != '') {
      extraBtn = boton;
    }

    columns.push({
      title: 'Acciones',
      data: null,
      defaultContent: `
        <div class="btn-group btn-group-sm" aria-label="Acciones">
          ${defaultButtons} ${extraBtn}
        </div>
      `,
      orderable: false,
    });
  }

  // Configuración inicial del DataTable
  const config = {
    data,
    columns,
    render: true,
    language: {
      sProcessing: 'Procesando...',
      sLengthMenu: 'Mostrar _MENU_ registros',
      sZeroRecords: 'No se encontraron resultados',
      sEmptyTable: 'Ningún dato disponible en esta tabla',
      sInfo: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
      sInfoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
      sInfoFiltered: '(filtrado de un total de _MAX_ registros)',
      sSearch: 'Buscar:',
      sLoadingRecords: 'Cargando...',
      oPaginate: {
        sFirst: 'Primero',
        sLast: 'Último',
        sNext: 'Siguiente',
        sPrevious: 'Anterior',
      },
      oAria: {
        sSortAscending: ': Activar para ordenar la columna de manera ascendente',
        sSortDescending: ': Activar para ordenar la columna de manera descendente',
      },
    },
  };

  // Activar exportación si se requiere
  if (btnExport) {
    config.dom = 'Bfrtip';
    config.buttons = ['copy', 'csv', 'excel', 'pdf', 'print'];
  }

  // Inicializar DataTable
  const table = $(`#${tableId}`).DataTable(config);


  // Añadir event listeners para los botones de acción
  $(`#${tableId} tbody`).off('click'); // evitar duplicados de eventos

  $(`#${tableId} tbody`).on('click', 'button.edit-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    editCallback(rowData);
  });

  $(`#${tableId} tbody`).on('click', 'button.delete-btn', function () {
    const rowData = table.row($(this).closest('tr')).data();
    deleteCallback(rowData);
  });

  if (otherButton && otherCallback) {
    $(`#${tableId} tbody`).on('click', 'button.other-btn', function () {
      const rowData = table.row($(this).closest('tr')).data();
      otherCallback(rowData);
    });
  }
};


const showConfirmation = (titulo = `¿Estás seguro?`, texto = `¡No podrás revertir esto!`, icono = ` warning`) => {
  return new Promise((resolve, reject) => {
    const swalWithBootstrapButtons = Swal.mixin({
      customClass: {
        confirmButton: "btn btn-success m-1",
        cancelButton: "btn btn-danger"
      },
      buttonsStyling: false
    });

    swalWithBootstrapButtons.fire({
      title: titulo,
      text: texto,
      icon: icono,
      showCancelButton: true,
      confirmButtonText: "Sí!",
      cancelButtonText: "No, cancelar!",
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        resolve(true);
      } else if (result.dismiss === Swal.DismissReason.cancel) {
        resolve(false);
      }
    });
  });
}


const selectedTab = (idTab, iditem) => {
  $('#' + idTab + ' button[data-bs-target="#' + iditem + '"]').tab('show')
}

const ShowDataFrom = (data, form = 'FormCrear', currencyFields = []) => {
  for (const key in data) {
    if (data.hasOwnProperty(key)) {

      const $input = $(`#${form} [name=${key}]`);

      if ($input.length) {
        const type = $input.attr('type');
        const tag = $input.prop('tagName').toLowerCase();
        if (type === 'checkbox') {

          if (data[key] == null || data[key] == undefined) {
            $input.prop('checked', false);
          } else
            if (data[key] == '0') {
              $input.prop('checked', false);
            } else if (data[key] == '1') {
              $input.prop('checked', true);
            } else
              if (data[key] == 'N') {
                $input.prop('checked', false);
              } else {
                $input.prop('checked', true);
              }


        } else if (type === 'radio') {
          $input.each(function () {
            if ($(this).val() == data[key]) {
              $(this).prop('checked', true);
            }
          });
        } else if (type === 'text' || type === 'date' || type === 'number' || type === 'color' || type === 'email' || type === 'hidden') {
          $input.val(data[key]);
        } else if (tag === 'textarea') {
          $input.val(data[key]);
        } else if (tag === 'select') {
          if ($input.prop('multiple')) {
            $input.val([data[key]]).change();
          } else {
            $input.val(data[key]).change();
          }
        } else if (tag == 'img') {
          $input.attr("src", data[key]);
        }

        // Formatear los campos especificados como moneda
        if (currencyFields.includes(key)) {
          new Cleave($input[0], {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            numeralDecimalMark: '.',
            numeralDecimalScale: 2,
          });
        }
      }
    }
  }
};


const limpiarFormulario = (id) => {
  $('#' + id)[0].reset();
  $('#' + id).removeClass('was-validated');
}

const validarToken = () => {
  return;
  if (datosCookie) {
    // Supongamos que este es el timestamp de caducidad del token proporcionado por PHP
    const tokenExpiryTimestamp = datosCookie.tiempo;
    // Convertir el timestamp a milisegundos
    const tokenExpiryDate = new Date(tokenExpiryTimestamp * 1000);

    // Obtener la fecha actual
    const currentDate = new Date();

    // Comparar las fechas
    if (currentDate > tokenExpiryDate) {
      // Redirigir la página si el token ha caducado
      window.location.href = "https://tuurl.com/sesion-expirada"; // Cambia esta URL a donde quieres redirigir
    } else {
      console.log("El token aún es válido.");
    }
  } else {
    console.log("aqui")
    window.location = 'login.php'
  }

}


function validacionInputs(array, valor) {
  let regexStr = "^"; // Inicia la expresión regular

  array.forEach(elemento => {
    switch (elemento) {
      case 'number':
        regexStr += "(?=.*[0-9])"; // Asegura que al menos haya un número en cualquier parte del texto
        break;
      case 'text':
        regexStr += "(?=.*[a-zA-Z])"; // Asegura que al menos haya una letra en cualquier parte del texto
        break;
      case 'guin_':
        regexStr += "(?=.*_)"; // Asegura que al menos haya un guion bajo en cualquier parte del texto
        break;
      case 'astk':
        regexStr += "(?=.*\\*)"; // Asegura que al menos haya un asterisco en cualquier parte del texto
        break;
      case 'guion':
        regexStr += "(?=.*-)"; // Asegura que al menos haya un guion medio en cualquier parte del texto
        break;
      default:
        throw new Error("Elemento no reconocido en el array");
    }
  });

  regexStr += ".+$"; // Finaliza la expresión regular

  const exp = new RegExp(regexStr);

  return exp.test(valor);
}

function calcularDigitoVerificacion(myNit) {
  var vpri,
    x,
    y,
    z;

  // Se limpia el Nit
  myNit = myNit.replace(/\s/g, ""); // Espacios
  myNit = myNit.replace(/,/g, ""); // Comas
  myNit = myNit.replace(/\./g, ""); // Puntos
  myNit = myNit.replace(/-/g, ""); // Guiones

  // Se valida el nit
  if (isNaN(myNit)) {
    return "";
  };

  // Procedimiento
  vpri = new Array(16);
  z = myNit.length;

  vpri[1] = 3;
  vpri[2] = 7;
  vpri[3] = 13;
  vpri[4] = 17;
  vpri[5] = 19;
  vpri[6] = 23;
  vpri[7] = 29;
  vpri[8] = 37;
  vpri[9] = 41;
  vpri[10] = 43;
  vpri[11] = 47;
  vpri[12] = 53;
  vpri[13] = 59;
  vpri[14] = 67;
  vpri[15] = 71;

  x = 0;
  y = 0;
  for (var i = 0; i < z; i++) {
    y = (myNit.substr(i, 1));

    x += (y * vpri[z - i]);
  }

  y = x % 11;

  return (y > 1) ? 11 - y : y;
}
//--Nuevo Christian Bula 2024-10-16

function enviarPeticion_(data, metodo = 'POST') {
  return new Promise((resolve, reject) => {
    let link = null;
    // Verificar si es FormData
    if (data instanceof FormData) {
      link = data.get('link');
    } else {
      link = data.link;
    }

    if (!link) {
      reject('No se envi� el link');
      return;
    }


    if (data.metodo !== undefined) {
      metodo = data.metodo;
    }

    const isFormData = data instanceof FormData;
    const ajaxOptions = {
      url: isFormData ? data.get('link') : data.link,
      type: metodo,
      dataType: "json",
      async: true,
      success: function (response) {
        resolve(response);
      },
      error: function (error) {
        reject(error);
      }
    };

    if (isFormData) {
      ajaxOptions.data = data;
      ajaxOptions.processData = false;
      ajaxOptions.contentType = false;
    } else {
      ajaxOptions.data = data;
    }

    $.ajax(ajaxOptions);
  });
};

window.enviarPeticion = async function (data, metodo = 'POST') {

  await loadAxios();

  return new Promise((resolve, reject) => {

    let link;

    // ✅ DIFERENCIAR FORM DATA vs OBJETO NORMAL
    if (data instanceof FormData) {
      link = data.get('link');
    } else {
      link = data.link;
    }

    if (!link) {
      reject("No se envió el link");
      return;
    }

    // Si envía 'metodo' dentro del objeto
    if (data.metodo !== undefined) {
      metodo = data.metodo;
    }

    let config = {
      method: metodo,
      url: link,
      responseType: "json",
    };

    if (data instanceof FormData) {
      config.data = data;
      config.headers = {
        "Content-Type": "multipart/form-data"
      };
    } else {
      config.data = new URLSearchParams(data);
      config.headers = {
        "Content-Type": "application/x-www-form-urlencoded"
      };
    }

    axios(config)
      .then(res => {
        resolve(res.data);
      })
      .catch(err => {
        console.error(err);
        reject(err);
      });
  });
};

const obtenerTituloporUrl = () => {
  const queryString = window.location.search;
  // Crear un objeto URLSearchParams para analizar los parámetros
  const params = new URLSearchParams(queryString);
  const titutlo = params.get('titulo'); // 
  $("#titulo-modulo").text(titutlo);
}


function formatNum(num, prefix, ConfGeneral) {

  if (!ConfGeneral) {
    ConfGeneral = {
      NumeroDecimales: 0
    }
  }
  prefix = prefix || '';

  // Validar que es un número
  if (isNaN(num)) return prefix + '0';

  // Obtener número de decimales desde configuración
  const decimales = (typeof ConfGeneral !== 'undefined' && typeof ConfGeneral.NumeroDecimales !== 'undefined')
    ? parseInt(ConfGeneral.NumeroDecimales)
    : 0;

  // Redondear al número de decimales
  num = parseFloat(num).toFixed(decimales);

  // Separar parte entera y decimal
  let [entero, decimal] = num.split('.');

  // Agregar comas a los miles
  const regx = /(\d+)(\d{3})/;
  while (regx.test(entero)) {
    entero = entero.replace(regx, '$1' + ',' + '$2');
  }

  // Unir de nuevo con los decimales si existen
  const resultado = decimales > 0 ? `${entero}.${decimal}` : entero;

  return prefix + resultado;
}

function unformatNum(num) { //Quita formato moneda a un numero

  if (num != '') {
    return num.replace(/([^0-9\.\-])/g, '') * 1;
  } else {
    return num;
  }
}


function vnumeros(e) { // solo numeros sin espacios con keypress
  tecla = (document.all) ? e.keyCode : e.which; // 2
  if (tecla == 8 || tecla == 0) return true; // 3
  patron = /[0-9]/; // Solo acepta números
  te = String.fromCharCode(tecla); // 5
  return patron.test(te); // 6
}

//Funcion que exporta la informacion a excel
function Exportar(div, e) {
  if ($("#" + div).html() != '') {
    window.open('data:application/vnd.ms-excel,' + encodeURIComponent($("#" + div).html()));
    e.preventDefault();
  } else {
    swal('Oops..!', 'No hay nada que exportar', 'warning');
  }
}

//Nueva funcion de exportacion de excel

function fnExcelReport(IdDiv) {
  var tab_text = "<table border='2px'><tr bgcolor='#87AFC6'>";
  var textRange;
  var j = 0;
  tab = document.getElementById(IdDiv); // id of table

  for (j = 0; j < tab.rows.length; j++) {
    tab_text = tab_text + tab.rows[j].innerHTML + "</tr>";
    //tab_text=tab_text+"</tr>";
  }

  tab_text = tab_text + "</table>";
  tab_text = tab_text.replace(/<A[^>]*>|<\/A>/g, ""); //remove if u want links in your table
  tab_text = tab_text.replace(/<img[^>]*>/gi, ""); // remove if u want images in your table
  tab_text = tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params

  var ua = window.navigator.userAgent;
  var msie = ua.indexOf("MSIE ");

  if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) // If Internet Explorer
  {
    txtArea1.document.open("txt/html", "replace");
    txtArea1.document.write(tab_text);
    txtArea1.document.close();
    txtArea1.focus();

    sa = txtArea1.document.execCommand("SaveAs", true, "Say Thanks to Sumit.xls");
  } else //other browser not tested on IE 11
    sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));


  return (sa);
}

const recibirRespuestaInsertUpdate = (data, callback = null) => {

  if (data.ok) {
    if (callback && typeof callback === "function") {
      callback(); // Ejecutar el callback si está definido y es una función
    }
    resp = "success";
    $("#form")[0].reset();
  } else {
    resp = "danger"
  }
  Swal.fire("Ok", data.mensaje, resp);
}

const validarMensaulidad = async () => {

  return await enviarPeticion({
    op: 'alerta_pago',
    link: '../models/home.php'
  });

}
const initToast = (color, title, description, icon, delay = '2000', posicion = 'top-0 end-0') => {
  switch (icon) {
    case 'success':
      icon = 'fa-solid fa-check';
      break;
    case 'danger':
      icon = 'fa-regular fa-circle-xmark';
      break;
    case 'warning':
      icon = 'fa-solid fa-triangle-exclamation';
      break;
    case 'info':
      icon = 'fa-solid fa-circle-info';
      break;
    case 'bug':
      icon = 'fa-solid fa-bug';
      break;
  }

  // Crear un nuevo elemento toast
  const toastElement = document.createElement('div');
  toastElement.className = `bs-toast toast toast-placement-ex m-2 ${color} ${posicion}`;
  toastElement.setAttribute('role', 'alert');
  toastElement.setAttribute('aria-live', 'assertive');
  toastElement.setAttribute('aria-atomic', 'true');
  toastElement.setAttribute('data-delay', delay);

  // Crear la estructura del toast
  toastElement.innerHTML = `
    <div class="toast-header">
      <i class="${icon} me-2"></i>
      <div class="me-auto fw-semibold toast-title">${title}</div>
      <small class="toast-hour"></small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body toast-description">${description}</div>
  `;

  // Agregar el toast al contenedor deseado (por ejemplo, el body)
  document.body.appendChild(toastElement);

  // Inicializar el toast de Bootstrap
  const toastInstance = new bootstrap.Toast(toastElement);

  // Mostrar el toast
  toastInstance.show();

  // Limpiar el toast después de que se haya mostrado
  toastElement.addEventListener('hidden.bs.toast', () => {
    toastElement.remove(); // Eliminar el toast del DOM
  });
};

function validarEmail(email) {
  // Expresión regular para validar el formato del email
  var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return regex.test(email); // Devuelve true si es válido, false si no lo es
}

const cerrar = () => {
  window.location.href = 'logout.php';
}

const validarFecha = (fecha) => {
  // Expresión regular para validar el formato de la fecha dd/mm/yyyy
  const regex = /^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-\d{4}$/;
  return regex.test(fecha);
}


// Ejemplo de uso: validar al enviar un formulario
function enviarFormulario() {
  if (validarFecha("miFecha")) {
    alert("Fecha válida, puedes continuar.");
    // continuar con el envío o lo que necesites
  }
}

let time = 10;

const validarSession = () => {

  enviarPeticion({
    op: 'valid_session',
    link: '../models/Session.php'
  })
    .then(resp => {
      if (!resp.activa) {
        showLoadingSwalAlert2(
          '<h4><i class="fa-solid fa-link-slash text-primary"></i></h4>',
          'La sesion ha expirado, el sistema se cerrara en <span class="text-primary"></span> <br> <button class="btn-primary btn btn-sm mt-2" onclick="cerrar()">Salir</button>',
          false)
        clearInterval(settime);
        setInterval(function () {
          $("#swal2-html-container").find("span").text(time);
          time--;
          if (time == 0) {
            window.location.href = 'logout.php';
          }
        }, 1000);
        return;
      }

      if (!resp.unica_session) {
        showLoadingSwalAlert2(
          '<i class="fa-solid fa-user-lock text-primary fa-2x"></i>',
          '<p>Se detecto que se inicio sesion con tú usuario desde otra dirección</p><button class="btn  btn-primary"  onclick="cerrar()">Iniciar session</button>',
          false)
        clearInterval(settime);
        return;
      }
    })
    .catch(e => {
    })
}

const visualizarPdf = (url, id) => {

  // Hacer la solicitud fetch al archivo PHP que genera el PDF
  fetch(url, {
    method: 'POST', // Definir que es una solicitud POST
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded', // Tipo de contenido para enviar parámetros en URL codificada
    },
    body: new URLSearchParams({
      id
    }) // Convertir el objeto a un formato de URL codificada
  })
    .then(response => response.blob()) // Convertir la respuesta en un blob
    .then(blob => {
      // Crear una URL para el blob
      const url = URL.createObjectURL(blob);

      // Crear un iframe para mostrar el PDF
      const iframe = document.createElement('iframe');
      iframe.style.width = '100%';
      iframe.style.height = '600px';
      iframe.src = url;
      // Agregar el iframe al cuerpo del documento
      document.body.appendChild(iframe);
    })
    .catch(error => {
      console.error('Error al generar el PDF:', error);
    });

}

const ImprimirDocumento = async (
  sw,
  id,
  titulo = `Impresión de documento`,
  credito = true,
  NombreArchivo = '',
  formato = 'C'

) => {

  const base = 'https://thecyctechnology.com/development';
  const esDev = window.location.href.startsWith(base);

  if (esDev) {
    url = 'https://thecyctechnology.com/development/app/pdf/';
  } else {
    url = 'https://thecyctechnology.com/cyc/app/pdf/';
  }

  switch (parseInt(sw)) {
    case 0: //comprobante contable
      url += 'comprobante-contable.php?id=' + id;
      break;
    case 1: // Compras
      if (formato == 'C') {
        url += 'compras.php?id=' + id;
      } else {
        url += 'compras-pos.php?id=' + id;
      }
      break;
    case 2: // Entradas de inventario
      url += 'entradas-salidas.php?id=' + id;
      break;
    case 3: // Entradas de inventario
      url += 'trasladosBodegas.php?id=' + id;
      break;
    case 4: // dev en ventas
      if (formato == 'C') {
        url += 'dev-ventas.php?id=' + id;
      } else {
        url += 'dev-ventas-pos.php?id=' + id;
      }

      break;
    case 6: // Salidas de inventario
      url += 'entradas-salidas.php?id=' + id;
      break;
    case 7: // devoluciones en compras
      url += 'devoluciones-compras.php?id=' + id;
      break;
    /*case 8:
     // url += 'devoluciones-compras.php?id=' + id+'&NombreArchivo='+NombreArchivo;
    break;*/
    case 8:
      url += 'pedidos-ventas.php?id=' + id + '&NombreArchivo=' + NombreArchivo;
      break;
    case 200: // Órdenes de compra
      url += 'pedidos-compra.php?id=' + id;
      break;
    case 5:
      if (credito) {
        titulo = 'Factura crédito'
        url = '../pdf/factura.php?id=' + id;
      } else {
        titulo = 'Factura POS'
        url = '../pdf/factura-pos.php?id=' + id;
      }
      break;
    case 9:
      if (credito) {
        titulo = 'Egreso'
        url = '../pdf/egreso.php?id=' + id;
      } else {
        titulo = 'Egreso POS'
        url = '../pdf/egreso-pos.php?id=' + id;
      }
      break;
    case 10:
      if (credito) {
        titulo = 'Ingreso'
        url = '../pdf/ingreso.php?id=' + id;
      } else {
        titulo = 'Ingreso POS'
        url = '../pdf/ingreso-pos.php?id=' + id;
      }
      break;
    case 17: // Sube costo promedio
      titulo = 'Sube costo promedio'
      url += 'sube-baja-costo.php?id=' + id;
      break;
    case 18: // Baja costo promedio
      titulo = 'Baja costo promedio'
      url += 'sube-baja-costo.php?id=' + id;
      break;
    case 11: // Notas credito / clientes
      url += 'notas.php?id=' + id;
      break;
    case 12: // Notas debito / clientes
      url += 'notas.php?id=' + id;
      break;
    case 13: // Notas credito / proveedores
      url += 'notas.php?id=' + id;
      break;
    case 14: // Notas debito / Proveedores
      url += 'notas.php?id=' + id;
      break;
  }

  // Eliminar el modal si ya existe
  $("#modalDoc").remove();

  // Crear el modal y el iframe
  $("body").append(`
    <div class="modal fade" id="modalDoc" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">${titulo}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div>
              <iframe width="100%" style="height:400px; border:none;" src="${url}" frameborder="0"></iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  `);

  // Mostrar el modal 
  $("#modalDoc").modal("show");
};


//FUNCIONES DE PEDIDO Y VENTAS 

const calcularTotal = (precio, dcto, iva, cantidad) => {


  let tbruto = 0;
  let tdto = 0;
  let tiva = 0;
  let ttotal = 0;


  if (isNaN(cantidad) || cantidad == 0) {
    return {
      tbruto,
      tdto,
      tiva,
      ttotal
    };
  }

  ttotal = ((precio * (1 - (dcto / 100))) * ((iva / 100) + 1)) * cantidad;
  tiva = ((precio * (1 - (dcto / 100))) * ((iva / 100))) * cantidad;
  tdto = (precio * (dcto / 100)) * cantidad;
  tbruto = precio * cantidad;
  /* 
  tbruto = Math.round(precio * cantidad);
  tdto = Math.round((precio * ((dcto / 100))) * cantidad)
  tiva = Math.round((precio * (iva / 100)) * cantidad)
  ttotal = Math.round((((precio * (1 - (dcto / 100))) * ((iva / 100) + 1))) * cantidad)*/

  return {
    tbruto,
    tdto,
    tiva,
    ttotal
  };
}
const calcularStockDisp = (stock, can_und) => {
  return parseInt(stock / can_und)
}

const mostrarStockDisponible = (stock, can_und, und) => {
  return stock > 0 ? parseInt(stock / can_und) + ` ${und} Y ` + (stock - (parseInt(stock / can_und) * can_und)) + ' UM' : '0 UND'
}
const calcularPrecioUnidad = (precio_unitario, can_und, dto, iva) => {

  if (isNaN(parseInt(can_und))) {
    can_und = 1;
  }
  if (isNaN(parseFloat(precio_unitario))) {
    precio_unitario = 0;
  }
  if (isNaN(parseFloat(dto))) {
    dto = 0;
  }
  if (isNaN(parseFloat(iva))) {
    iva = 0;
  }
  const precion = (((precio_unitario * can_und) * (1 - (dto / 100))) * ((iva / 100) + 1)).toFixed(2);
  const preciob = (((precio_unitario * can_und))).toFixed(2)
  return {
    precion,
    preciob
  }
}

const validarStock = (cantidad, stock) => {
  return cantidad >= stock
}

const validarRentabilidad = (costo, precio, cantidad_und) => {
  precio_real = (precio / cantidad_und).toFixed(2);
  return precio_real > costo;
}

const enviarDocumentoElctronico = (id, sw) => {

  return enviarPeticion({
    link: '../models/Fe/Controllers/Procesar.php',
    id,
    sw
  });

}

const enviarNominaElectronica = (id) => {
  return enviarPeticion({
    link: '../models/Fe/Controllers/ProcesarNomina.php',
    id
  });
}

const obtenerVigenciaCD = () => {
  return enviarPeticion({
    link: '../models/Fe/Controllers/obtenerVigenciaCD.php'
  });
}

const obtenerFoliosDisponibles = () => {
  return enviarPeticion({
    link: '../models/Fe/Controllers/obtenerFoliosDisponibles.php'
  });
}

const guardarRespuestasDian = async (id, respDian) => {

  await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'i_respuestas_dian',
    id,
    respDian
  });
}

function generarListaHTML(items) {
  const lista = items.map(item => `<li>${item}</li>`).join('');
  return `<ul>${lista}</ul>`;
}

const fechaActual = (config = []) => {
  const hoy = new Date();

  const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
  const ultimoDia = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

  const formatoFecha = (fecha) => {
    const y = fecha.getFullYear();
    const m = String(fecha.getMonth() + 1).padStart(2, '0');
    const d = String(fecha.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  };

  config.forEach(item => {
    // ?? Caso 1: rango de fechas [inicio, fin]
    if (Array.isArray(item) && item.length === 2) {
      const [inicio, fin] = item;
      if (inicio) $(`#${inicio}`).val(formatoFecha(primerDia));
      if (fin) $(`#${fin}`).val(formatoFecha(ultimoDia));
    }
    // ?? Caso 2: un solo input (fecha actual)
    else if (typeof item === 'string') {
      $(`#${item}`).val(formatoFecha(hoy));
    }
  });
};

/**
 * 
 * @param {string} bodega 
 * @param {string} bodegaDes 
 * @param {numeric} id_bodega 
 * @returns 
 */
const seleccionarBodega = (bodega, bodegaDes, id_bodega) => {

  enviarPeticion({
    link: '../models/SeleccionBodega.php',
    bodega,
    bodegaDes,
    id_bodega
  }).then(resp => {
    if (!resp.ok) {
      Swal.fire("Ups", resp.message, "error");
      return;
    }
    window.location.href = 'home.php';
  })
    .catch(error => {
      Swal.fire("Ups", error.message, "error");
      return;
    })

}

window.loadAxios = function () {
  return new Promise((resolve, reject) => {
    // Si ya está cargado
    if (typeof window.axios !== 'undefined') {
      resolve(window.axios);
      return;
    }

    // Crear script
    const script = document.createElement('script');
    script.src = "https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js";
    script.onload = () => {
      resolve(window.axios);
    };
    script.onerror = () => reject("No pudo cargarse Axios");
    document.head.appendChild(script);
  });
};

const calcularPrecio = (tipo, valor, unidad, promedio, iva, costo_ultimo) => {
  //const tipo = $("#tipo_precio").val();
  //const valor = $.trim(unformatNum($("#valor_tipo_precio").val()));
  //const unidad = $("#unidad option:selected").val()
  //const promedio = unformatNum($("#promedio").val());
  //const iva = parseFloat($("#iva").val())

  if (parseFloat(isNaN(valor)) || valor == 0) {
    //  $("#precio_venta").val(0)
    return 0;
  }

  switch (tipo) {
    case "PF":
      //$("#precio_venta").val(formatNum(valor, '$', ConfGeneral))
      precio = valor;
      break;
    case "CP":
      precio = Math.round((promedio * (unidad * (valor / 100) + 1)) * ((iva / 100) + 1));
      //$("#precio_venta").val(formatNum(precio, '$', ConfGeneral))
      break;
    case "CU":
      precio = Math.round(((unformatNum(costo_ultimo * unidad)) * ((valor / 100) + 1)) * ((iva / 100) + 1));
      //  $("#precio_venta").val(formatNum(precio, '$', ConfGeneral))
      break;
  }

  return precio;
}

const GenerarEnlaceDocumento = async (
  sw,
  id,
  credito = true,
  NombreArchivo = '',
  formato = 'C',
  expiracionHoras = 24
) => {

  try {

    const response = await fetch('../models/generar-pdf.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        sw: parseInt(sw),
        id: id,
        credito: credito,
        NombreArchivo: NombreArchivo,
        formato: formato,
        expiracionHoras: expiracionHoras
      })
    });
    // Obtener respuesta como texto
    const texto = await response.text();
    console.log('Respuesta del servidor:', texto);

    let data;

    try {
      data = JSON.parse(texto);

    } catch (e) {

      throw new Error(
        `El servidor devolvió una respuesta no válida. HTTP ${response.status}`
      );
    }

    // Validar respuesta
    if (!response.ok || !data.ok) {

      throw new Error(
        data.mensaje || 'No fue posible generar el documento.'
      );
    }

    return data;

  } catch (error) {

    console.error('Error GenerarEnlaceDocumento:', error);

    // Mostrar error al usuario
    await Swal.fire({
      icon: 'error',
      title: 'No fue posible generar el documento',
      text: error.message || 'Ocurrió un error inesperado.',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#d33'
    });

    // Mantener el error disponible para quien llame la función
    throw error;
  }
};

const enviarWhatsapp = async (id, sw, telefonoActual = '', nombres = '') => {
  if (!id) {
    Swal.fire(
      'Sin documento cargado',
      'Por favor, cargue un documento antes de intentar enviar el WhatsApp.',
      'warning'
    );
    return;
  }

  // Solicitar número de WhatsApp
  const { value: telefono } = await Swal.fire({
    title: 'Enviar por WhatsApp',
    text: 'Ingrese el número de teléfono al que desea enviar el documento.',
    input: 'tel',
    inputValue: telefonoActual || '',
    inputPlaceholder: 'Ej: 3001234567',
    inputAttributes: {
      autocomplete: 'tel',
      maxlength: 15
    },
    showCancelButton: true,
    confirmButtonText: '<i class="fab fa-whatsapp"></i> Continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#25D366',
    inputValidator: (value) => {
      if (!value || !value.trim()) {
        return 'Por favor, ingrese un número de teléfono.';
      }

      // Solo números
      const numero = value.replace(/\D/g, '');

      if (numero.length < 10) {
        return 'Ingrese un número de teléfono válido.';
      }
    }
  });

  // Si canceló
  if (!telefono) {
    return;
  }

  // Eliminar espacios, guiones, paréntesis, etc.
  let numeroWhatsApp = telefono.replace(/\D/g, '');

  /*
   * Si el usuario escribe un número colombiano de 10 dígitos,
   * agregamos automáticamente el código de Colombia.
   *
   * Ejemplo:
   * 3001234567 → 573001234567
   */
  if (numeroWhatsApp.length === 10 && numeroWhatsApp.startsWith('3')) {
    numeroWhatsApp = '57' + numeroWhatsApp;
  }

  // Generar enlace del documento

  let resultado = await GenerarEnlaceDocumento(
    sw,
    id,
    true,
    'archivo_temporal'
  );

  if (!resultado || !resultado.url) {
    Swal.fire(
      'Error',
      'No fue posible generar el enlace del documento.',
      'error'
    );
    return;
  }

  const mensaje =
    `Hola ${nombres}, \n` +
    `Adjunto encontrarás el documento solicitado:\n` +
    `${resultado.url}\n` +
    `*Este enlace es válido por 24 horas.*\n` +
    `━━━━━━━━━━━━━━━━\n` +
    `*Gestionado con ISA Software*\n` +
    `*Tecnología que impulsa tu negocio.*`;

  const whatsappUrl =
    'https://wa.me/' +
    numeroWhatsApp +
    '?text=' +
    encodeURIComponent(mensaje);

  window.open(whatsappUrl, '_blank');
};

const enviarEmail = async (id, sw, emailActual = '', nombres = '') => {

  if (!id) {
    Swal.fire(
      'Sin documento cargado',
      'Por favor, cargue un documento antes de intentar enviar el correo.',
      'warning'
    );
    return;
  }

  // Solicitar correo
  const { value: email } = await Swal.fire({
    title: 'Enviar por correo',
    text: 'Ingrese el correo electrónico al que desea enviar el documento.',
    input: 'email',
    inputValue: emailActual || '',
    inputPlaceholder: 'Ej: cliente@empresa.com',
    inputAttributes: {
      autocomplete: 'email'
    },
    showCancelButton: true,
    confirmButtonText: '<i class="fa-solid fa-envelope"></i> Continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0d6efd',

    inputValidator: (value) => {

      if (!value || !value.trim()) {
        return 'Por favor, ingrese un correo electrónico.';
      }

      const emailLimpio = value.trim();

      const regexEmail =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!regexEmail.test(emailLimpio)) {
        return 'Ingrese un correo electrónico válido.';
      }
    }
  });

  // Si canceló
  if (!email) {
    return;
  }

  const emailDestino = email.trim();

  try {

    // ==========================================
    // MOSTRAR LOADING
    // ==========================================

    showLoadingWithTetrisLogo(
      'Enviando documento por email...',
      'ISA Software'
    );

    // ==========================================
    // GENERAR ENLACE DEL DOCUMENTO
    // ==========================================

    let resultado = await GenerarEnlaceDocumento(
      sw,
      id,
      true,
      'archivo_temporal'
    );

    if (!resultado || !resultado.url) {

      Swal.close();

      await Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No fue posible generar el enlace del documento.',
        confirmButtonText: 'Aceptar'
      });

      return;
    }
    // ==========================================
    // ENVIAR CORREO
    // ==========================================

    const asunto = 'Documento solicitado - ISA Software';

    const resp = await enviarPeticion({
      link: '../shared/servicios/servicios-email-enviar.php',
      destinatarios: emailDestino,
      asunto,
      url: resultado.url,
      nombres
    });

    // ==========================================
    // CERRAR LOADING
    // ==========================================

    Swal.close();

    // ==========================================
    // VALIDAR RESPUESTA
    // ==========================================

    if (!resp) {
      throw new Error(
        'No se recibió respuesta del servidor de correo.'
      );
    }

    // Si tu API devuelve algún indicador de éxito
    // puedes ajustar esta validación según tu estructura.
    if (resp.ok === false) {
      throw new Error(
        resp.mensaje ||
        'El servidor no pudo enviar el correo.'
      );
    }

    // ==========================================
    // ÉXITO
    // ==========================================

    await Swal.fire({
      icon: 'success',
      title: 'Correo enviado',
      text: `El documento fue enviado correctamente a ${emailDestino}.`,
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#0d6efd'
    });

  } catch (error) {

    console.error(
      'Error enviando correo:',
      error
    );

    // ==========================================
    // CERRAR LOADING
    // ==========================================

    Swal.close();

    // ==========================================
    // MOSTRAR ERROR
    // ==========================================

    await Swal.fire({
      icon: 'error',
      title: 'No se pudo enviar',
      text: error.message ||
        'Ocurrió un error al generar o enviar el documento.',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#dc3545'
    });
  }
};


$(async function () {
  await loadAxios();

});
window.addEventListener('storage', function (event) {
  if (event.key === 'login_event') {
    window.location.reload();
  }
});

function actualizarEstadoRed() {
  const icon = document.getElementById('conn-icon');
  const text = document.getElementById('conn-text');
  const container = document.getElementById('network-status'); // Contenedor principal

  if (!icon || !text || !container) return;

  let mensajeEstado = '';

  // 1. Sin conexión (Offline)
  if (!navigator.onLine) {
    icon.className = 'fas fa-times-circle';
    icon.style.color = '#dc3545';
    mensajeEstado = 'Sin conexión';
  } else {
    // 2. Analizar métricas de velocidad
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

    if (connection) {
      const tipoEfectivo = connection.effectiveType;
      const rtt = connection.rtt;
      const downlink = connection.downlink;

      if (tipoEfectivo === 'slow-2g' || tipoEfectivo === '2g' || tipoEfectivo === '3g' || rtt > 400 || downlink < 1.0) {
        icon.className = 'fas fa-exclamation-triangle';
        icon.style.color = '#ffc107';
        mensajeEstado = `Conexión débil (${downlink} Mbps)`;
      } else {
        icon.className = 'fas fa-wifi';
        icon.style.color = '#28a745';
        mensajeEstado = '';
      }
    } else {
      icon.className = 'fas fa-wifi';
      icon.style.color = '#28a745';
      mensajeEstado = '';
    }
  }

  // Actualizamos texto visible y el tooltip para móviles
  text.textContent = mensajeEstado;
  container.setAttribute('title', mensajeEstado);
}

// Escuchar cambios repentinos de red
window.addEventListener('online', actualizarEstadoRed);
window.addEventListener('offline', actualizarEstadoRed);

// Escuchar cambios de calidad de red en vivo
const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
if (connection) {
  connection.addEventListener('change', actualizarEstadoRed);
}

// Verificación periódica cada 5 segundos
setInterval(actualizarEstadoRed, 5000);

// Ejecutar al cargar la página por primera vez
document.addEventListener('DOMContentLoaded', actualizarEstadoRed);