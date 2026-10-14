//VARIABLES GLOBALES
let ventanaTomaFisica = null;
let ventanaSugeridoCompras = null;
let settime;
 time = 10;
let ultimoPing = 0;
const intervaloMin = 60 * 1000; 
async function cargarPagina(page) {
    // Permitir siempre acceder al cierre de mes
    if (page === "CierreMes") {
        cargarIframe(page);
        return;
    }

    // Validar cierre de mes
    const cierreValidado = await validadCierreMes();

    if (!cierreValidado.ok) {
        Swal.fire({
            icon: 'warning',
            title: 'Cierre de mes pendiente',
            text: 'Debes realizar el cierre de mes antes de continuar.',
            confirmButtonText: 'Ir a Cierre de Mes'
        }).then(() => {
            cargarIframe("CierreMes");
        });

        return;
    }

    cargarIframe(page);
};

function cargarIframe(page) {
    const url = page + ".php";
    const iframe = document.getElementById("iframe");

    $.ajax({
        url: url,
        type: "HEAD",

        error: function () {
            iframe.src = "404.php";
        },

        success: function () {
            iframe.src = url;
        }
    });
};

const cargarEmpresa = async () => {
  try {
    const resp = await enviarPeticion({
      op: "listar",
      link: "../models/Empresas.php",
      metodo: "POST",
    });

    if (resp && resp.length > 0) {
      const empresa = resp[0];
      const logo = empresa.logo && empresa.logo.trim() !== "" ? empresa.logo : "default_logo.png";
      $("#logo-empresa").html(`
        <img src="../../assets/img/logo/${logo}" 
             alt="Logo Empresa" 
             style="max-width: 100%; max-height: 80px; width: auto; height: auto; object-fit: contain;">
      `);
      $("#name-empresa").text(empresa.razon_comercial || "Nombre de Empresa");
    } else {
      $("#logo-empresa").html(`
        <img src="../../assets/img/logo/default_logo.png" 
             alt="Logo por defecto" 
             style="max-width: 100%; max-height: 80px; width: auto; height: auto; object-fit: contain;">
      `);
      $("#name-empresa").text("Empresa no configurada");
      // No hay empresa ? abrir modal forzado
      $("#modalEmpresa").modal({
        backdrop: "static", // no se cierra al hacer clic afuera
        keyboard: false // no se cierra con ESC
      });
      $("#modalEmpresa").modal("show");
    }
  } catch (error) {
    console.error("Error cargando empresa:", error);
    $("#logo-empresa").html(`
      <img src="../../assets/img/logo/default_logo.png" 
           alt="Logo por defecto" 
           style="max-width: 100%; max-height: 80px; width: auto; height: auto; object-fit: contain;">
    `);
    $("#name-empresa").text("Error al cargar");
  }
};

const cargarMenu = async () => {
  try {
    const resp = await enviarPeticion({
      op: "api/modulos/cargarmenu",
      link: "../models/home.php",
      metodo: "POST",
    });

    let html = '';

    resp.data.forEach((element) => {
      const modulos = element.modulos;
      const grupo = element.grupo;
      let menulink = "menu-link";

      if (modulos.length > 0) {
        menulink += " menu-toggle";
      }

      // Grupo Inicio
      if (grupo.id == "1") {
        html += `
          <li class="menu-item active"> 
            <a href="#" class="${menulink} item-dashboard"> 
              <i class="menu-icon tf-icons fa-solid fa-house"></i>
              <div>Inicio</div>
            </a>
          </li>`;
      }

      // Grupo Reportes
      else if (grupo.id == "7") {
        if (modulos.length > 0) {
          html += `
          <li class="menu-item"> 
            <a href="#" class="menu-link item-reportes"> 
              ${grupo.icono}
              <div>${grupo.grupo}</div>
            </a>
          </li>`;
        }
      }

      // Otros grupos normales
      else {

        // ? OCULTAR EL GRUPO COMPLETO SI NO TIENE M�DULOS
        if (modulos.length === 0) return;

        html += `
          <li class="menu-item"> 
            <a href="javascript:void(0);" class="${menulink}"> 
              ${grupo.icono}
              <div>${grupo.grupo}</div>
            </a>`;

        if (modulos.length > 0) {
          html += `<ul class="menu-sub" style="display:none">`;

          modulos.forEach((mod) => {
            html += `            
              <li class="menu-item"> 
                <a href="#${mod.url}" class="menu-link item-modulo">
                  <div>${mod.titulo}</div>
                </a> 
              </li>`;
          });

          html += `</ul>`;
        }

        html += `</li>`;
      }
    });

    // Renderizar menu completo
    $("#menu-principal").html(html);

    // Evento clic en submdulos
    $("#menu-principal").on("click", "a.item-modulo", function (e) {
      e.preventDefault();
      const ancho = screen.width;
      const alto = screen.height;
      const href = $(this).attr("href").substring(1);
      cargarPagina(href);
      $("#menu-principal .menu-item").removeClass("active");
      $(this).closest("li.menu-item").addClass("active");
      $(this).closest("ul.menu-sub").closest("li.menu-item").addClass("active");
    });

    // Evento clic en Inicio (dashboard)
    $("#menu-principal").on("click", "a.item-dashboard", function (e) {
      e.preventDefault();
      cargarPagina("dashboard");

      $("#menu-principal .menu-item").removeClass("active");
      $(this).closest("li.menu-item").addClass("active");

      $("#menu-principal .menu-sub").slideUp(200);
    });

    // Evento clic en Reportes
    $("#menu-principal").on("click", "a.item-reportes", function (e) {
      e.preventDefault();
      cargarPagina("reportes");

      $("#menu-principal .menu-item").removeClass("active");
      $(this).closest("li.menu-item").addClass("active");

      $("#menu-principal .menu-sub").slideUp(200);
    });

    // Evento clic en grupo principal con submen�
    $("#menu-principal").on("click", ".menu-toggle", function (e) {
      e.preventDefault();
      const submenu = $(this).next(".menu-sub");

      $(".menu-sub").not(submenu).slideUp(200);
      submenu.slideToggle(200);
    });
  } catch (e) {
    console.error(e);
  }
};

const logout = () => {
  window.location.href = 'logout.php';
};

function validarConControl() {
  const ahora = Date.now();
  if (ahora - ultimoPing > intervaloMin) {
    ultimoPing = ahora;
    validarSession(); // tu función AJAX
  }
};

function pedirLiderazgo() {
  const ahora = Date.now();
  const lider = localStorage.getItem("lider-session");
  if (!lider || ahora - lider > 15000) {
    // 15s de expiración del liderazgo
    localStorage.setItem("lider-session", ahora);
    return true;
  }
  return false;
};

function evaluarFortalezaPassword(password) {
  let puntos = 0;
  const feedback = [];

  if (password.length >= 8) puntos += 20;
  if (password.length >= 12) puntos += 10;
  if (/[a-z]/.test(password)) puntos += 20;
  if (/[A-Z]/.test(password)) puntos += 20;
  if (/[0-9]/.test(password)) puntos += 20;
  if (/[^a-zA-Z0-9]/.test(password)) puntos += 10;

  let texto = "";
  let clase = "";

  if (puntos < 30) {
    texto = "Muy débil";
    clase = "bg-danger";
  } else if (puntos < 60) {
    texto = "Débil";
    clase = "bg-danger";
  } else if (puntos < 80) {
    texto = "Media";
    clase = "bg-warning";
  } else {
    texto = "Fuerte";
    clase = "bg-success";
  }

  return { valor: Math.min(puntos, 100), texto: texto, clase: clase };
};

$(async function () {
  await cargarEmpresa();

  // Detecta cuando el usuario vuelve a la pestaña
  document.addEventListener("visibilitychange", () => {
    if (!document.hidden) {
      validarConControl();
    }
  });

  // Detecta actividad del usuario
  ["mousemove", "keydown", "click"].forEach(ev => {
    window.addEventListener(ev, () => validarConControl());
  });

  setInterval(() => {
    if (pedirLiderazgo()) { 
      validarSession();
    }
  }, 10000);

  cargarMenu();
  $("#logout").click(function () {
    logout();
  })

  $("#listado-bodegas").click(async function () {
    try {

      showLoadingWithTetrisLogo('Cargando bodegas', 'ISA Software')
      const resp = await bodegasUsuarios();

      dissminSwal();
      if (resp.length == 0) {
        return;
      }
      let li = '';
      resp.forEach(element => { //console.log(element);
        li += `<li class="list-group list-group-item btn p-2 m-1 btn-outline-primary text-start" onclick="seleccionarBodega('${element.bodega}','${element.descripcion}',${element.id})"><i class="fa-solid fa-map-location-dot"></i> ${element.descripcion}</li>`
      });
      $("#bodegas-usuario").html(li)
      $("#modal-selected-bodegas").modal("show")
    } catch (error) {
      dissminSwal();
      console.error(error)
    }
  });
  
  cargarPagina("dashboard");
  // Buscador de menús
  $("#menu-search").on("keyup", function () {
    const search = $(this).val().toLowerCase();

    if (search === "") {
      // Restaurar estado inicial: todo colapsado
      $("#menu-principal .menu-sub").slideUp(200);
      $("#menu-principal .menu-item").show();
      return;
    }

    $("#menu-principal li.menu-item").each(function () {
      const text = $(this).text().toLowerCase();

      if (text.includes(search)) {
        $(this).show();

        // Si es grupo con submenú, expandirlo
        $(this).find(".menu-sub").slideDown(200);
      } else {
        // Evitar ocultar el buscador
        if ($(this).find("#menu-search").length === 0) {
          $(this).hide();
        }
      }
    });
  });
  //Duplicar ventana
  $("#btn-duplicar-ventana").click(function () {
    window.open(window.location.href, '_blank');
  });

  //  Abrir Modal de Cambiar Contraseña
  $("#miperfil").click(function (e) {
    e.preventDefault();
    $("#formCambiarPassword")[0].reset();
    $("#strength-bar").css("width", "0%");
    $("#strength-text").text("");
    $("#modalCambiarPassword").modal("show");
  });

  // Indicador de Fortaleza de Contraseña
  $("#passwordNueva").on("keyup", function () {
    const password = $(this).val();
    const strength = evaluarFortalezaPassword(password);

    const strengthBar = $("#strength-bar");
    const strengthText = $("#strength-text");

    strengthBar.css("width", strength.valor + "%");
    strengthText.text(strength.texto);

    strengthBar.removeClass("bg-danger bg-warning bg-success");
    strengthBar.addClass(strength.clase);
  });

  // Validar coincidencia de contraseñas
  $("#passwordConfirmar").on("blur", function () {
    const nueva = $("#passwordNueva").val();
    const confirmar = $(this).val();

    if (nueva && confirmar && nueva !== confirmar) {
      $(this).addClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
      $(this).after('<div class="invalid-feedback d-block">Las contraseñas no coinciden</div>');
    } else {
      $(this).removeClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
    }
  });

  // Guardar Nueva Contraseña
  $("#btnGuardarPassword").click(async function () {
    const passwordActual = $("#passwordActual").val();
    const passwordNueva = $("#passwordNueva").val();
    const passwordConfirmar = $("#passwordConfirmar").val();

    // Validaciones
    if (!passwordActual || !passwordNueva || !passwordConfirmar) {
      initToast('alert-warning', 'Campos Incompletos', 'Por favor completa todos los campos', 'warning');
      return;
    }

    if (passwordNueva.length < 8) {
      initToast('alert-warning', 'Contraseña Muy Corta', 'La contraseña debe tener mínimo 8 caracteres', 'warning');
      return;
    }

    if (passwordNueva !== passwordConfirmar) {
      initToast('alert-danger', 'Error', 'Las contraseñas no coinciden', 'danger');
      return;
    }

    if (passwordActual === passwordNueva) {
      initToast('alert-info', 'Aviso', 'La nueva contraseña debe ser diferente a la actual', 'info');
      return;
    }

    // Mostrar loading
    const btnGuardar = $("#btnGuardarPassword");
    const textoOriginal = btnGuardar.html();
    btnGuardar.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Procesando...');

    try {
      const resp = await enviarPeticion({
        op: "cambiar_password",
        link: "../models/usuarios.php",
        metodo: "POST",
        password_actual: passwordActual,
        password_nueva: passwordNueva

      });
      console.log(resp);

      if (resp.ok) {
        initToast('alert-success', '¡Éxito!', 'Tu contraseña ha sido cambiada correctamente', 'success');
        setTimeout(() => {
          $("#modalCambiarPassword").modal("hide");
        }, 1500);
      } else {
        initToast('alert-danger', 'Error', resp.message || 'No se pudo cambiar la contraseña', 'danger');
      }
    } catch (error) {
      console.error(error);
      initToast('alert-danger', 'Error', 'Ocurrió un error al cambiar la contraseña', 'danger');
    } finally {
      btnGuardar.prop('disabled', false).html(textoOriginal);
    }
  });

  $("#btn-whatsapp-soporte").on("click", function (e) {
    e.preventDefault();
    const empresa = $("#name-empresa").text().trim();
    const mensaje = `Hola, necesito soporte técnico. Empresa: ${empresa}`;
    const url = `https://wa.me/573015941578?text=${encodeURIComponent(mensaje)}`;
    window.open(url, "_blank");
  });
})

