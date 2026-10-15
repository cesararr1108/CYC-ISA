<?php
 /* =====================================================================
    CONFIGURACION EVENTO VIERNES 16 DE OCTUBRE
    ===================================================================== */
 // Tabla/vista con TODOS los pedidos del evento (CODIGO, VALOR_TOTAL, PASSPORT, FECHA_HORA)
 define('EV_TABLA_PEDIDOS', 'T_PEDIDOS');
 define('EV_FECHA', '20261016');                 // AAAAMMDD

 // Dinámica electromenores
 define('EV_ELECTRO_MIN_LAB', 50000);            // compra mínima por laboratorio
 define('EV_ELECTRO_N_LABS', 10);                // laboratorios requeridos
 define('EV_ELECTRO_HORA_LIMITE', '14:00');      // se puede reclamar ANTES de esta hora
 define('EV_ELECTRO_STOCK', 20);                 // electromenores disponibles

 // Dinámica premios mayores (1 sola boleta por cliente, en T_USUARIOS.BOLETAS1)
 define('EV_MAYOR1_MIN_LAB', 50000);             // Dinámica #1
 define('EV_MAYOR1_N_LABS', 20);
 define('EV_MAYOR2_MIN_LAB', 1000000);           // Dinámica #2
 define('EV_MAYOR2_N_LABS', 8);

 // Sorteos de la tarde (compra total, sin importar el laboratorio)
 define('EV_SORTEO_PORCENTAJE', 0.05);           // 5% de la compra
 define('EV_SORTEO_TOPE_COMPRA', 10000000);      // compra máxima sobre la que se calcula el 5%
 define('EV_SORTEO_EXCLUIR_GANADORES', true);    // un cliente que ya ganó no participa en los siguientes sorteos

 function evSorteos(){
	 return array(
		 '14:00' => 2000000,
		 '15:00' => 3000000,
		 '16:00' => 4000000,
		 '17:00' => 5000000
	 );
 }

 /* Funciones compartidas por el modelo y el PDF de boletas */
 function evNum($v){
	 return sprintf('%.0f', (float)$v);
 }

 // Fecha de corte para SQL (formato independiente del idioma del servidor)
 function evCorteSql($hora){
	 return EV_FECHA.' '.$hora.':00';
 }

 // Fecha de corte para comparar con evAhora()
 function evCorteIso($hora){
	 return substr(EV_FECHA, 0, 4).'-'.substr(EV_FECHA, 4, 2).'-'.substr(EV_FECHA, 6, 2).' '.$hora.':00';
 }

 // Clientes que participan en el sorteo de una hora: compra total acumulada hasta el corte
 function evSqlParticipantes($hora, $monto){
	 return "
		SELECT
			P.PASSPORT,
			COMPRA = SUM(P.VALOR_TOTAL)
		FROM ".EV_TABLA_PEDIDOS." P
		WHERE P.FECHA_HORA <= '".evCorteSql($hora)."'
		GROUP BY P.PASSPORT
		HAVING SUM(P.VALOR_TOTAL) >= ".evNum($monto);
 }
