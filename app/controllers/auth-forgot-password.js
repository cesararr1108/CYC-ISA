$("#formRecuperar").on("submit", async function(e) {
  e.preventDefault();

  const email = $("#email").val().trim();
  const captcha = $("input[name='g-recaptcha-response']").val();

  if (!email) {
    Swal.fire("Error","Por favor ingresa tu correo.","error");
    return;
  }
/*
  const formData = new FormData();
  formData.append("op", "request_reset");
  formData.append("email", email);
  formData.append("g-recaptcha-response", captcha);

  try {
    const resp = await fetch("../shared/servicios/services-reset-password.php", {
      method: "POST",
      body: formData
    });
    const data = await resp.json();
    console.log(data)
  } catch (err) {
    console.error("Error:", err);
  }*/
 try {
  showLoadingSwalAlert2('<i class="fa fa-plane"></i>Cargando ','Enviando correo de recuperación',false)
  
  const resp = await enviarPeticion({
        link :"../shared/servicios/services-reset-password.php",
        op:'solicitar_reset',
        email:email,
        "g-recaptcha-response":captcha,
        empresa:$("#empresa").val()
     })
     console.log({resp})
     
     dissminSwal();

     if(!resp.ok){
      Swal.fire("Error",resp.msg,"error");
      return;
     }
      Swal.fire("Ok","Solicitud enviada correctamnte. Por favor revisa tú correo, hemos enviado un link para continuar con el restablecimiento de tú contraseña","success")
      $("#email").val("");
    } catch (error) {
    dissminSwal();
    console.error(error)
    Swal.fire("Error","Error al enviar la solicitud","error")
 }
});
