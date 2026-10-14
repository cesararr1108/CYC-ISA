
const servicioEliminarPedido =async (id_pedido)=>{
    return await enviarPeticion({
        link :'../shared/servicios/servicios.php',
        op:'delete_pedido',
        id_pedido
    })
}