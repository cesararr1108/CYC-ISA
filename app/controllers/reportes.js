let dataReportes = []; // guardamos todos los reportes globalmente

// 1. Cargar los grupos
const cargarMenuReportes = async () => {
  try {
    const resp = await enviarPeticion({
      link: '../models/reportes.php',
      op: 's_modulos_reportes',
      metodo: "POST",
    });
  console.log({
    resp
  })
    dataReportes = resp.data; // guardamos la info para reuso
    renderizarGrupos();
  } catch (e) {
    console.error("Error en cargarMenuReportes:", e);
  }
};

// 2. Renderizar los grupos
const renderizarGrupos = () => {
  $('#card-container').empty();

  dataReportes.forEach(item => {
    let card = $(`
      <div class="col-12 col-md-3 mb-3 cursor-pointer">
         <div class="card card-hover shadow-sm border border-primary mb-3">
          <div class="card-body">
            <h5 class="card-title">
              <i class="${item.grupo.icono} ${item.grupo.color}"></i> 
              ${item.grupo.grupo}
            </h5>
            <p class="card-text">${item.modulos.length} reportes</p>
          </div>
        </div>
      </div>
    `);

    // Click para cargar los módulos de ese grupo
    card.on('click', () => renderizarModulos(item.grupo.grupo, item.modulos));

    $('#card-container').append(card);
  });
};

// 3. Renderizar los módulos del grupo seleccionado
const renderizarModulos = (grupoNombre, modulos) => {
  $('#card-container').empty();

  // Botón de atrás
  let backBtn = $(`
    <div class="col-12 mb-3">
      <button class="btn btn-sm btn-outline-primary" id="btn-back">
        <i class="fa-solid fa-rotate-left"></i> Atrás
      </button>
    </div>
  `);
  backBtn.on('click', () => renderizarGrupos());
  $('#card-container').append(backBtn);

  // Título del grupo
  let titulo = $(`
    <div class="col-12 mb-3">
      <h4>Reportes de ${grupoNombre}</h4>
    </div>
  `);
  $('#card-container').append(titulo);

  // Cards de módulos
  modulos.forEach(m => {
    let card = $(`
      <div class="col-12 col-md-3 mb-3 cursor-pointer">
        <div class="card card-hover shadow-sm border border-primary mb-3">
          <div class="card-body">
            <h5 class="card-title"><i class="fa-solid fa-chart-simple"></i></h5>
            <h6 class="card-title">${m.titulo}</h6>
          </div>
        </div>
      </div>
    `);

    // Evento para el módulo (abrir modal, mostrar reporte, etc.)
    card.on('click', () => {
      cargarPagina(m.url,m.titulo);
    });

    $('#card-container').append(card);
  });
};

const cargarPagina = async (page, title = "Reporte") => {
  const modal = $("#modalReporte");
  const iframe = document.getElementById("iframe-report");
  const url = `${page}.php`;

  // Mostrar modal y título
  modal.modal("show");

  // Mientras carga, poner un loader
  iframe.src = "load.html"; // aquí podrías poner un spinner/loader

  try {
    const resp = await fetch(url, { method: "HEAD" });

    if (resp.ok) {
      iframe.src = url;
    } else {
      iframe.src = "404.php";
    }
  } catch (err) {
    console.error("Error cargando página:", err);
    iframe.src = "404.php";
  }
};


$(function () {
  cargarMenuReportes();
  dissminSwal();
})
