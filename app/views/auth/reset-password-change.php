  <?php
  session_start();
    $token = $_GET['token'] ?? '';
    if ($token == '') {
        echo "<p>Enlace inválido o expirado.</p>";
        exit;
    }
    $empresa = $_GET['empresa'] ?? '';
  ?>
<!DOCTYPE html>
<html lang="es" class="light-style customizer-hide" dir="ltr" data-theme="theme-default">
<head>
<meta name="description" content="" />

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="../../../assets/img/logo-mini.png" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet"
    />
<!-- boostrap -->
<link rel="stylesheet" href="../../../assets/vendor/css/bootstrap-5.0.2-dist/css/bootstrap.min.css" />
<!-- Icons. Uncomment required icon fonts -->
<link rel="stylesheet" href="../../../assets/vendor/fonts/boxicons.css" />

<!-- Core CSS -->
<link rel="stylesheet" href="../../../assets/vendor/css/core.css" class="template-customizer-core-css" />
<link rel="stylesheet" href="../../../assets/vendor/css/theme-default.css" class="template-customizer-theme-css" />
<link rel="stylesheet" href="../../../assets/css/demo.css" />
<link rel="stylesheet" href="../../../assets/vendor/css/SwalAlert2/dist/css/sweetalert2.min.css" />
<!-- Vendors CSS -->
<link rel="stylesheet" href="../../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
<link rel="stylesheet" href="../../../assets/vendor/css/fontawesome-free-6.1.2-web/css/all.css" />
<!-- Page CSS --> 
<!-- Page -->
<link rel="stylesheet" href="../../../assets/vendor/css/pages/page-auth.css" />
</head>

<body>


  <div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
      <div class="authentication-inner py-4">
        <div class="card">
          <div class="card-body">
          <div class="app-brand justify-content-center"> 
            <a href="index.php" class="app-brand-link gap-2"> 
            <span class="app-brand-logo demo">
            <img src="../../../assets/img/logo-mini.png" height="90" /> </span> 
          </a> 
          </div>

            <h4 class="mb-2 text-center">Restablecer contraseña 🔒</h4>
            <p class="mb-4 text-center">Ingresa la nueva contraseña</p>

            <!-- ✅ FORMULARIO -->
            <form id="formCambiarClave"  method="POST">
              <div class="mb-2">
                <div class="mb-3 form-password-toggle" >
                    <div class="d-flex justify-content-between">
                      <label class="form-label text-primary" for="password">Clave</label>
                    </div>
                    <div class="input-group input-group-merge">
                      <input
                            type="password"
                            id="nueva"
                            class="form-control"
                            name="new_password"
                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                            aria-describedby="clave"
                            required
                          />
                      <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span> </div>
                    <div class="valid-feedback "> Debe digitar la clave! </div>
                  </div>
                    <div class="d-flex justify-content-between">
                      <label class="form-label text-primary" for="password">Clave</label>
                    </div>
                    <div class="input-group input-group-merge">
                      <input
                            type="password"
                            id="confirmar"
                            class="form-control"
                            name="new_password_confirm"
                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                            aria-describedby="clave"
                            required
                          />
                      <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span> </div>
                    <div class="valid-feedback "> Debe digitar la clave! </div>
                  </div>
              </div>

              <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
              <input type="hidden" name="empresa" value="<?php echo htmlspecialchars($empresa); ?>">

              <button class="btn btn-outline-primary m-2" type="submit">Cambiar contraseña</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="../../../assets/vendor/libs/jquery/jquery.js"></script>
  <script src="../../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
  <script src="../../shared/servicios.js?<?php echo rand(); ?>"></script> 
  <script src="../../shared/shared.js?<?php echo rand(); ?>"></script> 
<!-- js app --> 
  <script>
  $("#formCambiarClave").on("submit", async function(e){
    e.preventDefault();

    const nueva = $("#nueva").val().trim();
    const confirmar = $("#confirmar").val().trim();

    // 🔹 Validar campos vacíos
    if (!nueva || !confirmar) {
      Swal.fire("Atención", "Debe llenar ambos campos.", "warning");
      return;
    }

    // 🔹 Validar coincidencia de contraseñas
    if (nueva !== confirmar) {
      Swal.fire("Error", "Las contraseñas no coinciden.", "error");
      return;
    }

    // 🔹 Validar longitud mínima
    if (nueva.length < 6) {
      Swal.fire("Error", "La contraseña debe tener al menos 6 caracteres.", "error");
      return;
    }

    try{
      let data= formatearArrayRequest($(this).serializeArray());
      const resp = await enviarPeticion({
         link : "../../shared/servicios/services-reset-password-change.php",
         data,
         op:'cambiar_clave'
      })
       if(!resp.ok){
        Swal.fire("Error",resp.msg,"error");
        return;
       }
       Swal.fire({
        title: 'Contraseña actualizada',
        text: 'Tu contraseña se cambió exitosamente',
        icon: 'success',
        confirmButtonText: 'OK',
        allowOutsideClick: false,
        allowEscapeKey: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Redirige cuando el usuario da clic en OK
          window.location.href = "../login.php";
        }
      });
      // window.location.href = "login.php");
    }catch(e){
      console.error(e)
    }

  });
  </script>
</body>
</html>
