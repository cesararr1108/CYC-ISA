<?php
 ob_start();
 require_once 'funciones.php';
 session_start();
 conectar();

 require_once 'RifasConfig.php';

 /* =====================================================================
    FUNCIONES DEL EVENTO
    ===================================================================== */
 function evJson($data){
	 // Descarta avisos/notice de PHP que dañarían el JSON
	 while(ob_get_level() > 0){ ob_end_clean(); }
	 header('Content-Type: application/json; charset=utf-8');
	 echo json_encode($data);
	 exit;
 }

 function evError($msg = ''){
	 if($msg == ''){
		 $msg = 'Error en base de datos: '.evUtf8(mssql_get_last_message());
	 }
	 evJson(array('ok' => false, 'msg' => $msg));
 }

 function evUtf8($v){
	 if(is_string($v) && !preg_match('//u', $v)){
		 return utf8_encode($v);
	 }
	 return $v;
 }

 function evEsc($v){
	 return str_replace("'", "''", trim((string)$v));
 }


 function evRows($sql){
	 $q = mssql_query($sql);
	 if($q === false){
		 evError();
	 }
	 $rows = array();
	 if(is_resource($q)){
		 while($r = mssql_fetch_assoc($q)){
			 foreach($r as $k => $v){
				 $r[$k] = is_string($v) ? trim(evUtf8($v)) : $v;
			 }
			 $rows[] = $r;
		 }
		 mssql_free_result($q);
	 }
	 return $rows;
 }

 // Ejecuta una sentencia sin resultado (INSERT/UPDATE)
 function evExec($sql){
	 $q = mssql_query($sql);
	 if($q === false){
		 evError();
	 }
	 if(is_resource($q)){
		 mssql_free_result($q);
	 }
	 return true;
 }

 function evPass(){
	 $p = isset($_POST['Pass']) ? trim($_POST['Pass']) : '';
	 if(!preg_match('/^[A-Za-z0-9\-]{1,10}$/', $p)){
		 evError('Código SAP inválido');
	 }
	 return $p;
 }

 function evUsuario(){
	 $u = '';
	 if(!empty($_SESSION['Ses_Usuario'])){
		 $u = $_SESSION['Ses_Usuario'];
	 }
	 return evEsc(substr($u, 0, 16));
 }

 function evAhora(){
	 $r = evRows("SELECT ahora = CONVERT(varchar(19), GETDATE(), 120)");
	 return $r[0]['ahora'];  // AAAA-MM-DD HH:MM:SS
 }



 /* CTEs:  R = laboratorios participantes (T_RIFAS)
           LAB = terceros (T_TERCEROS) que pertenecen a cada laboratorio participante
           C = compras por cliente y laboratorio participante */
 function evSqlLabs($filtroPedidos){
	 return "
		WITH R AS (
			SELECT
				NIT     = LTRIM(RTRIM(NIT)),
				NOMBRES = MAX(NOMBRES)
			FROM T_RIFAS
			WHERE ISNULL(LTRIM(RTRIM(NIT)), '') <> ''
			GROUP BY LTRIM(RTRIM(NIT))
		), LAB AS (
			SELECT DISTINCT
				T.CODIGO_SAP,
				R.NIT
			FROM T_TERCEROS T
			INNER JOIN R ON T.NIT LIKE '%' + R.NIT + '%'
		), C AS (
			SELECT
				P.PASSPORT,
				LAB.NIT,
				COMPRA = SUM(P.VALOR_TOTAL)
			FROM ".EV_TABLA_PEDIDOS." P
			INNER JOIN T_MATERIALES M ON M.CODIGO_MATERIAL = P.CODIGO
			INNER JOIN LAB            ON LAB.CODIGO_SAP    = M.CODIGO_SAP
			WHERE ".$filtroPedidos."
			GROUP BY
				P.PASSPORT,
				LAB.NIT
		) ";
 }


 function evSqlNombre($campo){
	 return "(SELECT TOP 1 ISNULL(NULLIF(LTRIM(T.RAZON_COMERCIAL), ''), T.NOMBRES)
	          FROM T_TERCEROS T WHERE T.CODIGO_SAP = ".$campo.")";
 }

 function evMayorDinamica($labs50, $labs1m){
	 if($labs50 >= EV_MAYOR1_N_LABS){ return 1; }
	 if($labs1m >= EV_MAYOR2_N_LABS){ return 2; }
	 return 0;
 }

 // Evalúa todas las dinámicas de un cliente
 function evEvaluar($pass){
	 $p = evEsc($pass);

	 $cli = evRows("SELECT TOP 1
						codigo_sap      = CODIGO_SAP,
						nombres         = NOMBRES,
						razon_comercial = RAZON_COMERCIAL
					FROM T_TERCEROS
					WHERE CODIGO_SAP = '".$p."'");
	 if(count($cli) == 0){
		 evError('El código SAP '.$pass.' no existe');
	 }

	 $ahora = evAhora();

	 // Compras por laboratorio participante
	 $labs = evRows(evSqlLabs("P.PASSPORT = '".$p."'")."
		SELECT
			nit     = R.NIT,
			nombres = R.NOMBRES,
			compra  = ISNULL(C.COMPRA, 0)
		FROM R
		LEFT JOIN C ON C.NIT = R.NIT
		ORDER BY
			ISNULL(C.COMPRA, 0) DESC,
			R.NOMBRES");

	 $labsElectro = 0; $labs50 = 0; $labs1m = 0; $compraLabs = 0; $labsConCompra = 0;
	 foreach($labs as $i => $l){
		 $c = (float)$l['compra'];
		 $labs[$i]['compra'] = $c;
		 $compraLabs += $c;
		 if($c > 0)                      { $labsConCompra++; }
		 if($c >= EV_ELECTRO_MIN_LAB)    { $labsElectro++; }
		 if($c >= EV_MAYOR1_MIN_LAB)     { $labs50++; }
		 if($c >= EV_MAYOR2_MIN_LAB)     { $labs1m++; }
	 }

	 // Compra total (todos los laboratorios) y acumulada a cada corte de los sorteos
	 $cols = array();
	 $i = 0;
	 foreach(evSorteos() as $hora => $monto){
		 $cols[] = "c".$i." = ISNULL(SUM(CASE WHEN P.FECHA_HORA <= '".evCorteSql($hora)."' THEN P.VALOR_TOTAL END), 0)";
		 $i++;
	 }
	 $tot = evRows("SELECT
						total = ISNULL(SUM(P.VALOR_TOTAL), 0),
						".implode(",\n", $cols)."
					FROM ".EV_TABLA_PEDIDOS." P
					WHERE P.PASSPORT = '".$p."'");

	 $gan = evRows("SELECT hora = HORA, premio = PREMIO, anulado = CAST(ANULADO AS int)
					FROM T_RIFA_SORTEOS_GANADORES
					WHERE PASSPORT = '".$p."'");
	 $ganHora = array();
	 foreach($gan as $g){
		 $ganHora[$g['hora']] = $g;
	 }

	 $sorteos = array();
	 $i = 0;
	 foreach(evSorteos() as $hora => $monto){
		 $c = (float)$tot[0]['c'.$i];
		 $sorteos[] = array(
			 'hora'    => $hora,
			 'monto'   => $monto,
			 'compra'  => $c,
			 'cumple'  => $c >= $monto,
			 'cerrado' => $ahora >= evCorteIso($hora),
			 'ganador' => isset($ganHora[$hora]) ? $ganHora[$hora] : null
		 );
		 $i++;
	 }

	 // Electromenor
	 $ele = evRows("SELECT
						entregados = COUNT(*),
						propio     = SUM(CASE WHEN PASSPORT = '".$p."' THEN 1 ELSE 0 END),
						fecha      = MAX(CASE WHEN PASSPORT = '".$p."' THEN CONVERT(varchar(19), FECHA_HORA, 120) END)
					FROM T_RIFA_ELECTROMENORES");
	 $entregados = (int)$ele[0]['entregados'];

	 // Premios mayores
	 $usu = evRows("SELECT
						registros = COUNT(*),
						boletas1  = ISNULL(MAX(ISNULL(BOLETAS1, 0)), 0)
					FROM T_USUARIOS
					WHERE CODIGO_SAP = '".$p."'");

	 return array(
		 'ok'          => true,
		 'ahora'       => $ahora,
		 'cliente'     => $cli[0],
		 'registrado'  => (int)$usu[0]['registros'] > 0,
		 'compra_total'=> (float)$tot[0]['total'],
		 'compra_labs' => $compraLabs,
		 'labs'        => $labs,
		 'n_labs'      => count($labs),
		 'labs_con_compra' => $labsConCompra,
		 'electro'     => array(
			 'labs_cumple' => $labsElectro,
			 'requeridos'  => EV_ELECTRO_N_LABS,
			 'minimo'      => EV_ELECTRO_MIN_LAB,
			 'hora_limite' => EV_ELECTRO_HORA_LIMITE,
			 'abierto'     => substr($ahora, 11, 5) < EV_ELECTRO_HORA_LIMITE,
			 'stock'       => EV_ELECTRO_STOCK,
			 'entregados'  => $entregados,
			 'ganado'      => (int)$ele[0]['propio'] > 0,
			 'fecha'       => $ele[0]['fecha']
		 ),
		 'sorteos'     => $sorteos,
		 'sorteo_porcentaje' => EV_SORTEO_PORCENTAJE,
		 'sorteo_tope' => EV_SORTEO_TOPE_COMPRA,
		 'mayores'     => array(
			 'labs_50'    => $labs50,
			 'labs_1m'    => $labs1m,
			 'min1'       => EV_MAYOR1_MIN_LAB,
			 'req1'       => EV_MAYOR1_N_LABS,
			 'min2'       => EV_MAYOR2_MIN_LAB,
			 'req2'       => EV_MAYOR2_N_LABS,
			 'dinamica'   => evMayorDinamica($labs50, $labs1m),
			 'boleta'     => (int)$usu[0]['boletas1'] > 0
		 )
	 );
 }

 switch($_POST["op"]){
	 case 1:{
	 		 $sql="SELECT
			        T.razon_comercial,
					T.nombres,
					compras=ISNULL((
					   SELECT
						  SUM(P.VALOR_TOTAL)
					   FROM T_PEDIDOS P
					   WHERE P.PASSPORT=T.CODIGO_SAP
					),0)+ISNULL((
					   SELECT
						  SUM(P.VALOR_TOTAL)
					   FROM T_PEDIDOS_DIA1 P
					   WHERE P.PASSPORT=T.CODIGO_SAP
					),0)+ISNULL((
					   SELECT
						  SUM(P.VALOR_TOTAL)
					   FROM T_PEDIDOS_DIA2 P
					   WHERE P.PASSPORT=T.CODIGO_SAP
					),0)+ISNULL((
					   SELECT
						  SUM(P.VALOR_TOTAL)
					   FROM T_PEDIDOS_DIA3 P
					   WHERE P.PASSPORT=T.CODIGO_SAP
					),0),
					boletas_imp=isnull((
						select top 1
						  boletas1
						from t_usuarios
						where codigo_sap=t.codigo_sap
					),0)
			 	   FROM T_TERCEROS T
				   WHERE
				    T.CODIGO_SAP='".$_POST["Pass"]."' ";
			 $Q=GenerarArray($sql,"");
			 echo json_encode($Q);
	 mssql_close();
	 }break;
	 case 2:{

	   $sql ="SELECT
				 R.nit,
				 R.grupo_articulos,
				 R.nombres,
				 R.monto,
				 CASE
				  WHEN R.TIPO = 'C' THEN 'SORTEO'
				  WHEN R.TIPO = 'M' THEN 'MOTO'
				  WHEN R.TIPO = 'B' THEN 'BONO'
				 END AS tipo,
				compras = ISNULL((SELECT
									   SUM(P.VALOR_TOTAL)
									 FROM T_PEDIDOS P
									 INNER JOIN T_MATERIALES M ON P.CODIGO = M.CODIGO_MATERIAL
									 INNER JOIN T_TERCEROS   T ON T.CODIGO_SAP = M.CODIGO_SAP
									 WHERE
									 P.PASSPORT='".$_POST["Pass"]."' AND
									 T.NIT LIKE('%'+R.NIT+'%')
									),0),
				ngrupo
				FROM T_RIFAS R

				ORDER BY
				ngrupo,
				R.tipo,
                R.nombres";

		$Q=GenerarArray($sql,"");
		echo json_encode($Q);
	 }break;
	 case 3:
	     $sql ="SELECT
				  SUM(P.VALOR_TOTAL) AS total
				FROM T_PEDIDOS P
				INNER JOIN T_MATERIALES M ON P.CODIGO = M.CODIGO_MATERIAL
				INNER JOIN T_TERCEROS   T ON T.CODIGO_SAP = M.CODIGO_SAP
				WHERE
					P.PASSPORT='".trim($_POST["Pass"])."' ";
		 $q = GenerarArray($sql,"");
		echo json_encode($q);
	break;
	case 4:
	 $sql ="SELECT
				P.PASSPORT,
				T.NOMBRES,
				SUM(P.VALOR_TOTAL) AS VALOR
				FROM T_PEDIDOS P

				INNER JOIN T_TERCEROS T ON T.CODIGO_SAP = P.CODIGO_SAP
				WHERE
				CAST(P.FECHA_HORA AS TIME) BETWEEN '13:00' AND '17:00' AND
				P.PASSPORT = '".$_POST['pass']."'
				GROUP BY
				P.PASSPORT,
				T.NOMBRES";
	  $q = GenerarArray($sql,"");
	  echo json_encode($q);
	break;
	case 5:
	 $sql ="SELECT
				compras_g = isnull((select sum(valor_total) from t_pedidos where passport= o.passport),0),
				compras_m = ISNULL((select sum(valor_total) from t_pedidos where passport= '0005' and
							  cast(fecha_hora as time)>=CAST('07:00:00' as time) and
							  cast(fecha_hora as time)<=CAST('10:00:00' as time)),0),
				compras_t = ISNULL((select sum(valor_total) from t_pedidos where passport= '0005' and
							cast(fecha_hora as time)>=CAST('15:00:00' as time) and
							cast(fecha_hora as time)<=CAST('20:00:00' as time)),0)
				FROM T_USUARIOS O
				WHERE
				O.PASSPORT= '".$_POST['Pass']."' and
				boletas3=0";
	  $q = GenerarArray($sql,"");
	  echo json_encode($q);
	break;

	 case "S_CLIENTE_PED":
		 $sql="SELECT
					V.PASSPORT,
					SUM(v.VALOR_TOTAL)
					FROM T_PEDIDOS V
					INNER JOIN T_MATERIALES M ON 	 M.CODIGO_MATERIAL	=V.CODIGO
					INNER JOIN T_TERCEROS   T ON	 T.CODIGO_SAP		=M.CODIGO_SAP
					INNER JOIN T_RIFAS  	R ON	 T.NIT LIKE '%'+R.NIT+'%'
					GROUP BY
					V.PASSPORT
					ORDER BY
					SUM(V.VALOR_TOTAL) ASC";
		 $q = GenerarArray($sql,"");
	     echo json_encode($q);
	break;
	case "I_BOLETAS":
			 $insert="INSERT INTO T_BOLETAS_TOTALES(PASSPORT,CANT,COMPRA,CANT_BOL)
					VALUES('".$_POST["passport"]."',
					        ".$_POST["cant"].",
							".$_POST["compra"].",
							".$_POST["cantbol"].") ";
			 $query=mssql_query($insert);
			 if($query){
				 echo 1;
			 }else{echo 0;}
	break;

	/* =====================================================================
	   EVENTO 16 DE OCTUBRE
	   ===================================================================== */

	// Consulta completa de un cliente: compras por laboratorio y estado de las 3 dinámicas
	case "EV_CONSULTA":
		 evJson(evEvaluar(evPass()));
	break;

	// Entregar electromenor: >= 10 labs con compra >= 50.000, antes de las 2 pm, con stock
	case "EV_ELECTRO_REGISTRAR":
		 $pass = evPass();
		 $ev   = evEvaluar($pass);
		 $e    = $ev['electro'];
		 if($e['ganado']){
			 evError('El cliente ya reclamó su electromenor ('.$e['fecha'].')');
		 }
		 if(!$e['abierto']){
			 evError('La dinámica de electromenores cerró a las '.EV_ELECTRO_HORA_LIMITE);
		 }
		 if($e['labs_cumple'] < EV_ELECTRO_N_LABS){
			 evError('El cliente tiene '.$e['labs_cumple'].' de '.EV_ELECTRO_N_LABS.' laboratorios con compras mínimas de $'.number_format(EV_ELECTRO_MIN_LAB, 0, ',', '.'));
		 }
		 $p = evEsc($pass);
		 $p = evEsc($pass);
		 // Una sola sentencia: inserta sólo si el cliente no ha reclamado y queda stock
		 evExec("INSERT INTO T_RIFA_ELECTROMENORES (PASSPORT, NOMBRES, N_LABS, COMPRA, USUARIO)
				SELECT
					'".$p."',
					LEFT(".evSqlNombre("'".$p."'").", 120),
					".(int)$e['labs_cumple'].",
					".evNum($ev['compra_labs']).",
					'".evUsuario()."'
				WHERE
					NOT EXISTS (SELECT 1 FROM T_RIFA_ELECTROMENORES WITH (UPDLOCK, HOLDLOCK) WHERE PASSPORT = '".$p."') AND
					(SELECT COUNT(*) FROM T_RIFA_ELECTROMENORES WITH (UPDLOCK, HOLDLOCK)) < ".EV_ELECTRO_STOCK);
		 // Verificar que quedó grabado
		 $r = evRows("SELECT
						entregados = COUNT(*),
						propio     = SUM(CASE WHEN PASSPORT = '".$p."' THEN 1 ELSE 0 END)
					FROM T_RIFA_ELECTROMENORES");
		 if((int)$r[0]['propio'] == 0){
			 if((int)$r[0]['entregados'] >= EV_ELECTRO_STOCK){
				 evError('Ya se entregaron los '.EV_ELECTRO_STOCK.' electromenores disponibles');
			 }
			 evError('No se pudo registrar el electromenor: '.evUtf8(mssql_get_last_message()));
		 }
		 evJson(array(
			 'ok'         => true,
			 'msg'        => 'Electromenor registrado',
			 'entregados' => (int)$r[0]['entregados'],
			 'stock'      => EV_ELECTRO_STOCK
		 ));
	break;

	case "EV_ELECTRO_LISTA":
		 $rows = evRows("SELECT
							id       = ID,
							passport = PASSPORT,
							nombres  = NOMBRES,
							n_labs   = N_LABS,
							compra   = COMPRA,
							usuario  = USUARIO,
							fecha    = CONVERT(varchar(19), FECHA_HORA, 120)
						FROM T_RIFA_ELECTROMENORES
						ORDER BY ID");
		 evJson(array('ok' => true, 'rows' => $rows, 'stock' => EV_ELECTRO_STOCK));
	break;

	// Premios mayores: Dinámica #1 (50.000 en 20 labs) o #2 (1.000.000 en 8 labs). 1 boleta por cliente
	case "EV_MAYORES_REGISTRAR":
		 $pass = evPass();
		 $ev   = evEvaluar($pass);
		 $m    = $ev['mayores'];
		 if(!$ev['registrado']){
			 evError('El cliente no está registrado en el evento (T_USUARIOS)');
		 }
		 if($m['boleta']){
			 evError('El cliente ya tiene su boleta para los premios mayores');
		 }
		 if($m['dinamica'] == 0){
			 evError('El cliente no cumple ninguna dinámica: '.$m['labs_50'].'/'.EV_MAYOR1_N_LABS.' labs con $50.000 y '.$m['labs_1m'].'/'.EV_MAYOR2_N_LABS.' labs con $1.000.000');
		 }
		 $p = evEsc($pass);
		 // Una sola boleta por cliente: se marca la primera fila del cliente en T_USUARIOS
		 evExec("UPDATE T_USUARIOS SET BOLETAS1 = 1
				WHERE
					ID = (SELECT MIN(ID) FROM T_USUARIOS WHERE CODIGO_SAP = '".$p."') AND
					NOT EXISTS (SELECT 1 FROM T_USUARIOS X WITH (UPDLOCK, HOLDLOCK)
					            WHERE X.CODIGO_SAP = '".$p."' AND ISNULL(X.BOLETAS1, 0) > 0)");
		 // Verificar que quedó grabado
		 $r = evRows("SELECT boletas1 = ISNULL(MAX(ISNULL(BOLETAS1, 0)), 0) FROM T_USUARIOS WHERE CODIGO_SAP = '".$p."'");
		 if((int)$r[0]['boletas1'] <= 0){
			 evError('No se pudo actualizar BOLETAS1 en T_USUARIOS: '.evUtf8(mssql_get_last_message()));
		 }
		 evJson(array('ok' => true, 'msg' => 'Boleta generada por la Dinámica #'.$m['dinamica'], 'dinamica' => $m['dinamica'], 'pass' => $pass));
	break;

	// Genera la boleta a todos los clientes registrados que cumplan y aún no la tengan
	case "EV_MAYORES_MASIVO":
		 $antes = evRows("SELECT n = COUNT(DISTINCT CODIGO_SAP) FROM T_USUARIOS WHERE ISNULL(BOLETAS1, 0) > 0");
		 evExec(evSqlLabs("1 = 1").", E AS (
				SELECT
					PASSPORT,
					L50 = SUM(CASE WHEN COMPRA >= ".evNum(EV_MAYOR1_MIN_LAB)." THEN 1 ELSE 0 END),
					L1M = SUM(CASE WHEN COMPRA >= ".evNum(EV_MAYOR2_MIN_LAB)." THEN 1 ELSE 0 END)
				FROM C
				GROUP BY PASSPORT
			)
			UPDATE U SET BOLETAS1 = 1
			FROM T_USUARIOS U
			INNER JOIN E ON E.PASSPORT = U.CODIGO_SAP
			WHERE
				(E.L50 >= ".EV_MAYOR1_N_LABS." OR E.L1M >= ".EV_MAYOR2_N_LABS.") AND
				U.ID = (SELECT MIN(Y.ID) FROM T_USUARIOS Y WHERE Y.CODIGO_SAP = U.CODIGO_SAP) AND
				NOT EXISTS (SELECT 1 FROM T_USUARIOS Z WHERE Z.CODIGO_SAP = U.CODIGO_SAP AND ISNULL(Z.BOLETAS1, 0) > 0)");
		 $despues = evRows("SELECT n = COUNT(DISTINCT CODIGO_SAP) FROM T_USUARIOS WHERE ISNULL(BOLETAS1, 0) > 0");
		 evJson(array('ok' => true, 'generadas' => (int)$despues[0]['n'] - (int)$antes[0]['n']));
	break;

	case "EV_MAYORES_LISTA":
		 $rows = evRows("SELECT
							codigo_sap = U.CODIGO_SAP,
							nombres    = ".evSqlNombre("U.CODIGO_SAP")."
						FROM T_USUARIOS U
						WHERE ISNULL(U.BOLETAS1, 0) > 0
						GROUP BY U.CODIGO_SAP
						ORDER BY U.CODIGO_SAP");
		 evJson(array('ok' => true, 'rows' => $rows));
	break;

	// Sorteos de la tarde
	case "EV_SORTEO_PARTICIPANTES":
		 $sorteos = evSorteos();
		 $hora = isset($_POST['hora']) ? $_POST['hora'] : '';
		 if(!isset($sorteos[$hora])){ evError('Hora de sorteo inválida'); }
		 $rows = evRows("SELECT
							passport   = X.PASSPORT,
							nombres    = ".evSqlNombre("X.PASSPORT").",
							compra     = X.COMPRA,
							registrado = CASE WHEN EXISTS (SELECT 1 FROM T_USUARIOS U WHERE U.CODIGO_SAP = X.PASSPORT) THEN 1 ELSE 0 END,
							gano       = (SELECT TOP 1 G.HORA FROM T_RIFA_SORTEOS_GANADORES G
							              WHERE G.PASSPORT = X.PASSPORT AND G.ANULADO = 0 ORDER BY G.HORA)
						FROM (".evSqlParticipantes($hora, $sorteos[$hora]).") X
						ORDER BY X.COMPRA DESC");
		 $ahora = evAhora();
		 evJson(array(
			 'ok'      => true,
			 'hora'    => $hora,
			 'monto'   => $sorteos[$hora],
			 'cerrado' => $ahora >= evCorteIso($hora),
			 'excluir_ganadores' => EV_SORTEO_EXCLUIR_GANADORES,
			 'rows'    => $rows
		 ));
	break;

	case "EV_SORTEO_SORTEAR":
		 $sorteos = evSorteos();
		 $hora = isset($_POST['hora']) ? $_POST['hora'] : '';
		 if(!isset($sorteos[$hora])){ evError('Hora de sorteo inválida'); }
		 if(evAhora() < evCorteIso($hora)){
			 evError('El sorteo de las '.$hora.' aún no se puede realizar (corte: '.evCorteIso($hora).')');
		 }
		 $excluir = EV_SORTEO_EXCLUIR_GANADORES ? "1 = 1" : "G.HORA = '".$hora."'";
		 $r = evRows("SELECT TOP 1
						passport = X.PASSPORT,
						nombres  = LEFT(".evSqlNombre("X.PASSPORT").", 120),
						compra   = X.COMPRA,
						premio   = ROUND(CASE WHEN X.COMPRA > ".evNum(EV_SORTEO_TOPE_COMPRA)." THEN ".evNum(EV_SORTEO_TOPE_COMPRA)." ELSE X.COMPRA END * ".EV_SORTEO_PORCENTAJE.", 0)
					FROM (".evSqlParticipantes($hora, $sorteos[$hora]).") X
					WHERE NOT EXISTS (
						SELECT 1 FROM T_RIFA_SORTEOS_GANADORES G
						WHERE G.PASSPORT = X.PASSPORT AND (G.HORA = '".$hora."' OR (G.ANULADO = 0 AND ".$excluir."))
					)
					ORDER BY NEWID()");
		 if(count($r) == 0){
			 evError('No hay más clientes para sortear en el sorteo de las '.$hora);
		 }
		 $g = $r[0];
		 evExec("INSERT INTO T_RIFA_SORTEOS_GANADORES (HORA, MONTO_MINIMO, PASSPORT, NOMBRES, COMPRA, PREMIO, USUARIO)
				SELECT '".$hora."', ".evNum($sorteos[$hora]).", '".evEsc($g['passport'])."',
				       LEFT(".evSqlNombre("'".evEsc($g['passport'])."'").", 120),
				       ".evNum($g['compra']).", ".evNum($g['premio']).", '".evUsuario()."'");
		 evJson(array('ok' => true, 'hora' => $hora, 'ganador' => $g));
	break;

	// Anula un ganador que no reclamó (queda registrado y no vuelve a salir en esa hora)
	case "EV_SORTEO_ANULAR":
		 $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		 if($id <= 0){ evError('Ganador inválido'); }
		 evRows("UPDATE T_RIFA_SORTEOS_GANADORES SET ANULADO = 1 WHERE ID = ".$id);
		 evJson(array('ok' => true));
	break;

	case "EV_SORTEO_GANADORES":
		 $rows = evRows("SELECT
							id       = ID,
							hora     = HORA,
							monto    = MONTO_MINIMO,
							passport = PASSPORT,
							nombres  = NOMBRES,
							compra   = COMPRA,
							premio   = PREMIO,
							anulado  = CAST(ANULADO AS int),
							usuario  = USUARIO,
							fecha    = CONVERT(varchar(19), FECHA_HORA, 120)
						FROM T_RIFA_SORTEOS_GANADORES
						ORDER BY HORA, ID");
		 evJson(array('ok' => true, 'rows' => $rows));
	break;

	case "EV_CONFIG":
		 $s = array();
		 foreach(evSorteos() as $h => $m){ $s[] = array('hora' => $h, 'monto' => $m); }
		 evJson(array('ok' => true, 'ahora' => evAhora(), 'sorteos' => $s));
	break;
 }
