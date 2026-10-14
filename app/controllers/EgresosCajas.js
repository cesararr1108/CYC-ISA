
const bodegas =async ()=>{
    try{
        const resp =await enviarPeticion({
            link :'../shared/servicios/servicios.php',
            op:'bodegas-usuarios'
        })

        if(resp.length==0){
            return;
        }
        p = '';
        resp.forEach(element => {
            p+=`<option value="${element.id}">${element.descripcion}</option>`
        });
        $("#bodegas").html(p)
         $("#bodegas").val($("#bodega_session").val())

    }catch(e){
        console.error(e)
    }
}

const cargarTercero =async (nit)=>{

    try {
       $(".data-terceros").attr("disabled",true)
        const resp = await enviarPeticion({
            link : '../models/Terceros.php',
            op:'cargar_ter_nit',
            nit
        });  
        if(resp.data.length==0){
           $(".data-terceros").attr("disabled",false)
            Swal.fire("Ups","No se encontraron registros!","info")
            return;
        }

        const tercero = resp.data[0];

        $("#nit").val(tercero.nit)
        $("#tercero").val(tercero.tercero)
        $("#id_tercero").val(tercero.id)
        $("#id_tercero_dir").val(tercero.id_tercero_dir)
        $(".data-terceros").attr("disabled",false)

    } catch (error) {
       // dissminSwal();
        Swal.fire("Error","Se genero un error al consultar el tercero","error")
        console.error(error)
        $(".data-terceros").attr("disabled",false)
    }

}

const nuevo =()=>{
    $(".valores").val('$0');
    $("#nit").val('');
    $("#tercero").val('')
    $("#id_tercero").val(0)
    $("#id_tercero_dir").val(0)
    $("#notas").val("")
}

$(async function(){
    await bodegas();
    await tiposDocumentos(9,$("#bodegas").val(),'id_tipos_documentos');
    await condicionesPago('P','id_condicion',false);

   $(".valores").focus(function(){
      $(this).val(unformatNum($(this).val())).select()
   });
    $(".valores").focusout(function(){
      $(this).val(formatNum($(this).val(),'$'));
      $("#total").val()
    });

    $("#nit").keyup(function(e){
        const nit = $.trim($(this).val());

        if(nit.length>0){
            if(e.key =='Enter'){
                cargarTercero(nit);
                $("#bruto").focus();
            }
        }else{
            $("#tercero").val("");
            $("#id_tercero").val("0")
            $("#id_tercero_dir").val("0")
        }
    });

    $("#guardar").click(async function(){

        try {
        const bruto =isNaN( parseInt(unformatNum($("#bruto").val())) ) ? 0 : unformatNum($("#bruto").val());
        const iva   =isNaN( parseInt(unformatNum($("#iva").val())) ) ? 0 : unformatNum($("#iva").val());
        const valor_total = bruto  + iva;

        const id_tipo_documento=$("#id_tipos_documentos").val()
        const tipo = $.trim($("#id_tipos_documentos option:selected").text());
        const usuario = $.trim($("#usuario").val());
        const nit =isNaN (parseInt($("#nit").val())) ? 0 : $("#nit").val();
        const id_bodega = $("#bodegas").val();      
        const notas =$.trim($("#notas").val());
        const sw =9;
        const id_tercero =$("#id_tercero").val()
        const id_tercero_dir=$("#id_tercero_dir").val()
        const id_condicion= $("#id_condicion").val();
        const valor_aplicado = 0;
        const anulado = 0;
        const vendedor =0;
        const id_doc_link =0;
        const id_usuario = $("#id_usuario").val();
        const lista =0;

        const resp =await enviarPeticion({
            link :'../models/EgresosCajas.php',
            op:'i_egreso',
            valor_total,
            id_tipo_documento,
            tipo ,
            usuario ,
            nit ,
            id_bodega ,
            notas ,
            sw ,
            id_tercero,
            id_tercero_dir,
            id_condicion,
            valor_aplicado ,
            anulado ,
            vendedor ,
            id_doc_link ,
            id_usuario,
            lista 
          });

          console.log({
            resp
          })

          if(!resp.ok){
            Swal.fire("Error","Error al insertar el registro","error");
            return;
          }

          Swal.fire("Ok","Se guardo el egreso!","success");

          nuevo();
        } catch (error) {
            console.error(error)
        }

    });

    $("#nit").focus();
    dissminSwal()
})