const estadoServicio = () => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'estado-servicio'
    });
  } catch (e) {
    console.error(e)
  }
}
const ventasTotales = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'ventas',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}
const ventasMensuales = (anio) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'ventas-mensuales',
      anio
    })
  } catch (e) {
    console.error(e)
  }
}

const ventasDiarias = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'ventas-diarias',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}

const Ingresos = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'ingresos',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}


const Egresos = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'egresos',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}


const Devoluciones = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'devoluciones',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}

const Utilidad = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'utilidad',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}

const productosVencidos = () => {

  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'productos-vencidos'
    })
  } catch (e) {
    console.error(e)
  }
}

const facturasVencidas = () => {

  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'facturas-vencidas'
    })
  } catch (e) {
    console.error(e)
  }
}

const referenciasStockMinimo = () => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'referencias-stock-minimo'
    })
  } catch (e) {
    console.error(e)
  }
}

const promocionesVigentes = () => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'promociones-vigentes'
    })
  } catch (e) {
    console.error(e)
  }
}
// Cumplimiento del presupuesto de ventas de la bodega (totales por tipo + venta neta del mes)
const presupuestoCumplimiento = (anio, mes) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'presupuesto-cumplimiento',
      anio,
      mes
    })
  } catch (e) {
    console.error(e)
  }
}

// Detalle del cumplimiento por zona, vendedor o usuario
const presupuestoDetalle = (anio, mes, tipo) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'presupuesto-detalle',
      anio,
      mes,
      tipo
    })
  } catch (e) {
    console.error(e)
  }
}

// Detalle por documento de ventas, devoluciones, ingresos o egresos del período
const detalleMovimientos = (anio, mes, concepto) => {
  try {
    return enviarPeticion({
      link: '../shared/servicios/services-dashboard.php',
      op: 'detalle-movimientos',
      anio,
      mes,
      concepto
    })
  } catch (e) {
    console.error(e)
  }
}