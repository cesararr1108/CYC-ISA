
const limpiarImputComisiones = (NoClear = []) => {
   // recorro los elmentos con clase element-comisiones y limpio su valor, valido si es select o input
   $(".element-comisiones").each(function () {
       if (NoClear.includes($(this).attr("id"))) {
           return;
       }
       if ($(this).is("select")) {
            $(this).prop('selectedIndex', 0);
       } else if ($(this).is("input")) {
           $(this).val("0");
       }
   });  
   $(".element-comisiones-calculo").each(function () {
       if ($(this).is("input")) {
           if($(this).attr("id").includes("rentabilidad")){
                $(this).val("0%");
           }else{
                $(this).val("$0");
           }
       }
    });

}

//obtiene el precio de venta   de la tabla  tbListas
const getDataPrice =(lista,unidad)=>{

    let col = 0;
    let precio =0;
    let listaPrecios =0;
    switch (unidad) {
        case "1":
            col = 2;
        break;
        case "2":
                col = 3;
        break;
        case "3":
                col = 4;
        break;
        case "4":
                col = 5;
        break;
    }

    $("#tbListas tbody tr").each(function () {
        const xlista = $.trim($(this).find("td").eq(0).text());
        
        if (xlista === lista) {
             // si tiene formato o tiene valor
             if(parseFloat($(this).find("td").eq(col).find("input.precio-edit").val())){
                precio = unformatNum($(this).find("td").eq(col).find("input.precio-edit").val());   
             }
             //precio = unformatNum($(this).find("td").eq(col).find("input.precio-edit").val());
             listaPrecios=xlista;   
        }
    });
    return {precio, listaPrecios};
}


const updateComisiones =async (id)=>{

    try {
        const resp  =await enviarPeticion({
            link : '../models/Referencias.php',
            id,
            op:'update_comision'
        }) ;

        if(!resp.ok){
         Swal.fire("Error","No se pudo eliminar la comisión","error");
         return;
        }

        if(resp.update_comision==0){
            $("#chckComisiones").prop("checked", false).trigger("change");
        }
    } catch (error) {
        console.error("Error al eliminar comisión:", error);    
    }
}

$(function () {


    $("#btnAddComision").on('click',function(){

        const comision1 = parseFloat($("#comision_und_1" ).val()) || 0;
        const comision2 = parseFloat($("#comision_und_2" ).val()) || 0;
        const comision3 = parseFloat($("#comision_und_3" ).val()) || 0;
        const comision4 = parseFloat($("#comision_und_4" ).val()) || 0;
        
        const lista = $("#slcListaComision").val();
        // valido si ya hay comision para esa lista  
        let existe = false;
        $("#tbComisiones tbody tr").each(function () {
            const xlista = $.trim($(this).find("td").eq(0).text());
            if (xlista === $("#slcListaComision option:selected").val()) {
                existe = true;
                return false; // Salir del bucle
            }
        });
        //DEBO REEPLAZARLO
        if(existe){
            $("#tr-comision-" + lista).remove();    
        }

        if((!isNaN(comision1) || !isNaN(comision2) || !isNaN(comision3) || !isNaN(comision4))
           && ( comision1 > 0 || comision2 > 0 || comision3 > 0 || comision4 > 0)
        ){
                let html = `<tr id="tr-comision-${lista}">
                                <td>${$("#slcListaComision option:selected").val()}</td>
                                <td>${$("#slcTipoListaComision option:selected").val()}</td>
                                <td>${comision1}</td>
                                <td>${comision2}</td>
                                <td>${comision3}</td>
                                <td>${comision4}</td>
                                <td><button type="button" class="btn btn-outline-danger btn-sm btnEliminarComision">
                                    <i class="fa-regular fa-trash-can" ></i>
                                </button></td>
                            </tr>`;
                $("#tbComisiones tbody").append(html);

                $(".btnEliminarComision").off("click").on("click", function () {
                    $(this).closest("tr").remove();
                });
                limpiarImputComisiones();            
        }

        
    })
   
    $("#chkComisiones").change(function () {

        if($("#idReferencia").val() === "" || $("#idReferencia").val() === "0") {
            Swal.fire("Ups","Debe cargar una referencia","warning")
            $(this).prop("checked", false);
            return;
        }

        const nPrecios = $("#tbListas tbody tr").length;

        if(nPrecios === 0){
            Swal.fire("Ups","Debe cargar al menos un precio","warning");
            $(this).prop("checked", false);
            return;
        }

        if ($(this).is(":checked")) {
            $(".element-comisiones").prop("disabled", false);
        } else {
            $(".element-comisiones").prop("disabled", true);
            limpiarImputComisiones();
        }   
    });

    $("#slcListaComision").change(function () {
         const data = getDataPrice(lista = $(this).val(),1);
         console.log(data);
         if(data.listaPrecios == "0"){
            Swal.fire("Ups","La lista de precios seleccionada no tiene precios para este producto","warning");
             $(this).prop('selectedIndex', 0);
             return;
         }
        limpiarImputComisiones(['slcListaComision']);
    });

     $("#slcTipoListaComision").change(function () {
        // limpio los input de comisiones
        limpiarImputComisiones(['slcListaComision','slcTipoListaComision']);
    });

    $(".element-comisiones").on("input", function () {
        if($(this).is("input")) {
             const tipoCalculo = $("#slcTipoListaComision").val();
             let   comision    = parseFloat($(this).val());
             const und = $(this).attr("id").split("_").pop(); // Obtiene el número de unidad del id
             if( isNaN(comision) || comision== 0){
                 $(this).trigger("change");
                
                 $("#vlr_comision_und_" + und).val("$0");
                 $("#precio_comision_und_" + und).val("$0");
                 $("#rentabilidad_comision_und_" + und).val("0%");
                 return;
             }

            if (tipoCalculo === "porcentaje" && comision > 100) {  
                $(this).val("0").trigger("change");
            } else if (tipoCalculo === "fijo" && comision < 0) {
                $(this).val("0").trigger("change");
            }
            // valido
            const lista = $("#slcListaComision").val();
            const idInput = $(this).attr("id");
            const pcj_iva = parseFloat($("#slcIva option:selected").text()) || 0;
            let precio =0;
            let rent =0;
            let preciobrutocomi =0;
            let precionetocomi =0;
            const costo = parseFloat( unformatNum($("#costo_promedio").val()))?  
                          parseFloat(unformatNum($("#costo_promedio").val())): 
                          
                          ( $("#txtCostoUnd").val() ? parseFloat(unformatNum($("#txtCostoUnd").val()))   : 0);

            switch (idInput) {
                case "comision_und_1":
                    precio = getDataPrice(lista, "1");
                 break;
                case "comision_und_2":
                    precio = getDataPrice(lista, "2");
                 break;
                case "comision_und_3":
                    precio = getDataPrice(lista, "3");
                 break;
                case "comision_und_4":
                    precio = getDataPrice(lista, "4");
                 break;
        }
        precio = precio.precio; 
        const preciobruto =Math.round( precio / (pcj_iva/100 +1));

        if(preciobruto  === 0){
            Swal.fire("Ups","El producto no tiene precio en la lista o en la unidad de conversion actual","warning");
            $(this).val("0");
            return;
        }

        comision = $("#slcTipoListaComision").val() === "porcentaje" ? Math.round((preciobruto * comision) / 100) : comision;
        preciobrutocomi = preciobruto - comision;
        //calculo la rentabilidad con el preciobrutocomi y el costo 
        rent =  ((preciobrutocomi - costo) / costo) * 100;
        precionetocomi =Math.round(preciobrutocomi * (pcj_iva/100 +1));
        
        // Extraer el número de unidad del idInput (ej: "comision_und_1" → "1")
        const unidadNum = idInput.replace("comision_und_", "");
        const badgeId = `#badge_comision_und_${unidadNum}`;
        
        // Mostrar datos en formato legible en el badge correspondiente
        const badgeText = `Precio Neto: $${formatNum(precionetocomi)} | Rentabilidad: ${formatNum(rent)}%`;
        
        $(badgeId).show().text(badgeText);
        
        // NUEVO: Llenar los campos de comisión, precio y rentabilidad
        $(`#vlr_comision_und_${unidadNum}`).val(formatNum(Math.round(comision)));
        $(`#precio_comision_und_${unidadNum}`).val(formatNum(Math.round(precionetocomi)));
        $(`#rentabilidad_comision_und_${unidadNum}`).val((rent >= 0 ? (rent.toFixed(2)) : (rent.toFixed(2))) + '%');
     }
    });
});