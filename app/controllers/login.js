const link_ = '../models/usuarios.php';
const bloqueoInputs = (sw) => {
  if (sw) {
    $("#entrar").html(`Entrando <i class="fa fa-spinner fa-spin-pulse"></i>`);
  } else {
    $("#entrar").html(`Entrar <i class="ri-arrow-right-line text-dark"></i>`);
  }
  $("#usuario").attr("disabled", sw);
  $("#clave").attr("disabled", sw);
}
const loguear = async () => {
  try {
    const dataSend = {
      usuario: $("#usuario").val(),
      clave: $("#clave").val(),
      link: link_,
      op: 'login',
      empresa: $("#empresa").val()
    }
    bloqueoInputs(true);
    const respuesta = await enviarPeticion(dataSend);
    console.log(respuesta)
    if (!respuesta.error) {
      if (respuesta.valido) {
        crearElemento('login_user', {
          empresa: $("#empresa").val(),
          usuario: $("#usuario").val()
        });
        // Notificar a las demás pestañas
        crearElemento('login_event', {
          empresa: $("#empresa").val(),
          usuario: $("#usuario").val(),
          timestamp: Date.now()
        });
        document.body.classList.add('transition-page');
        setTimeout(function () {
          window.location.href = 'SeleccionBodega.php';
        }, 300);
      } else {
        Swal.fire("Error", respuesta.mensaje, "error")
        bloqueoInputs(false);
      }
    } else {
      Swal.fire("Error", "Ha ocurrido un error en el servidor!", "error");
      bloqueoInputs(false);
    }
  } catch (e) {
    console.log(JSON.stringify(e));
    Swal.fire("Error", "Ha ocurrido un error en el servidor!", "error")
    bloqueoInputs(false);
  }
}


const dbValidate = async () => {

  try {
    showLoadingWithTetrisLogo('Validando empresa...', 'ISA Software')


    const dataSend = {
      empresa: $("#empresa").val(),
      link: '../models/validarDb.php'
    }
    const respuesta = await enviarPeticion(dataSend);
    if (!respuesta.valido) {

      dissminSwal();
      Swal.fire("Error", respuesta.mensaje, "error");
      return;
    } else {
      await loguear();
    }
  } catch (e) {
    console.error(e)
    dissminSwal();
  }

}

$(async function () {

  const usuarioLocalStorage = await leerElemento('login_user');

  if (usuarioLocalStorage) {
    $("#remember-me").attr("checked", true);
    $("#usuario").val(usuarioLocalStorage.usuario.toUpperCase());
    $("#empresa").val(usuarioLocalStorage.empresa)
    $("#clave").focus();
  } else {
    $("#usuario").focus();
  }
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
          form.classList.add('was-validated');
          return;
        }
        dbValidate();
      }, false)
    })
  $("#remember-me").click(function () {
    if ($(this).is(":checked")) {
      const usuario = leerElemento('login_user');
      console.log({ usuario })
      if (usuario) {
        eliminarElemento('login_user');
      } else {
        crearElemento('login_user', { empresa: $("#empresa").val(), usuario: $("#usuario").val() })
      }
    }
  });
  $("#recuperar").click(function () {
    if ($.trim($("#empresa").val()) == '') {
      Swal.fire("Ups", "Debe digitar la empresa", "error")
      return;
    }
    console.log({
      empresa: $("#empresa").val()
    });
    window.location = 'auth-forgot-password.php?empresa=' + $("#empresa").val()

  })

  // Realiza un fadeOut en el logo-inicio antes de eliminarlo
  setTimeout(() => {
    $('#logo-inicio').addClass("d-none")
  }, 2000);

  // Luego muestra el container-xxl
  $('.container-xxl').addClass("fade-in-image").removeClass("d-none");

})
