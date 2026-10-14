
$(function(){
    
    $("button").click(async function(){
        showLoadingSwalAlert2('Enviando factura electronica','Por favor espere!',false)
        try{
            const resp = await enviarPeticion({
                link:'../models/Fe/Controllers/Procesar.php',
                numero:2,
                tipo :'FVE',
            })
            console.log({resp:resp.IsValid})
            $("#resp").html(JSON.stringify(resp, null, 4))
            $("#resp2").html(resp.IsValid);
            dissminSwal();
        }catch(e){
            console.error(e)
            dissminSwal();
        }
    });

 
})


