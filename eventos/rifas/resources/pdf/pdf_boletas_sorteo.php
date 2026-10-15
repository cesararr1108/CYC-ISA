<?php
/* Boletas de los sorteos de la tarde: una boleta por cada cliente que participa en el sorteo de la hora indicada.
   Uso: pdf_boletas_sorteo.php?hora=15:00 */
include("../../models/funciones.php");
require_once("../../models/RifasConfig.php");
//-----------Inicializacion---------
require("../../resources/fpdf/fpdf.php");

// FPDF trabaja en ISO-8859-1: convertir sólo si el texto viene en UTF-8
function TextoPdf($t){
	$t = trim((string)$t);
	if(preg_match('/[\x80-\xFF]/', $t) && preg_match('//u', $t)){
		return utf8_decode($t);
	}
	return $t;
}

function HoraAmPm($h){
	$p = explode(':', $h);
	$hh = (int)$p[0];
	return ($hh > 12 ? $hh - 12 : $hh).':'.$p[1].($hh >= 12 ? ' PM' : ' AM');
}

class PDF extends FPDF
{
	public $evento = null;

	function Header(){
		if($this->evento === null){
			$Query = GenerarArray("SELECT TOP 1 ID, NOMBRE, ORGANIZACION FROM T_EVENTOS WHERE ESTADO = 1", "");
			$this->evento = count($Query) > 0 ? $Query[0] : array('NOMBRE' => '', 'ORGANIZACION' => 0);
		}
		if($this->evento['ORGANIZACION'] == 1000){
			$this->Image('../images/logo_cm.png', 15, 2, 40);
		}else{
			$this->Image('../images/logo_roma.png', 15, 2, 40);
		}
		$this->line(0, 17, 140, 17);
		$this->SetFont("Arial", "B", 6);
		$this->Text(20, 21, TextoPdf($this->evento['NOMBRE']));
		$this->line(0, 23, 140, 23);
	}

	function cuerpo($hora, $monto){
		$excluir = "";
		if(EV_SORTEO_EXCLUIR_GANADORES){
			// Quien ya ganó en un sorteo anterior no recibe boleta
			$excluir = "WHERE NOT EXISTS (SELECT 1 FROM T_RIFA_SORTEOS_GANADORES G
			                              WHERE G.PASSPORT = X.PASSPORT AND G.ANULADO = 0 AND G.HORA <> '".$hora."')";
		}
		$Query = GenerarArray("SELECT
								X.PASSPORT,
								X.COMPRA,
								NOMBRES         = (SELECT TOP 1 T.NOMBRES FROM T_TERCEROS T WHERE T.CODIGO_SAP = X.PASSPORT),
								RAZON_COMERCIAL = (SELECT TOP 1 T.RAZON_COMERCIAL FROM T_TERCEROS T WHERE T.CODIGO_SAP = X.PASSPORT)
							FROM (".evSqlParticipantes($hora, $monto).") X
							".$excluir."
							ORDER BY X.COMPRA DESC", "");

		$total = count($Query);
		if($total == 0){
			$this->AddPage();
			$this->SetFont("Arial", "B", 10);
			$this->SetXY(0, 35);
			$this->MultiCell(77, 6, TextoPdf("NO HAY CLIENTES PARA EL SORTEO DE LAS ".HoraAmPm($hora)), 0, "C");
			return;
		}

		$n = 0;
		foreach($Query as $r){
			$n++;
			$this->AddPage();
			$this->SetFont("Arial", "B", 10);
			$this->SetXY(0, 25);
			$this->MultiCell(77, 5, TextoPdf("SORTEO ".HoraAmPm($hora)), 0, "C");
			$this->SetFont("Arial", "", 7);
			$this->SetXY(0, 30);
			$this->MultiCell(77, 4, TextoPdf("COMPRA MÍNIMA $".number_format($monto, 0, ',', '.')), 0, "C");
			$this->SetFont("Arial", "B", 8);
			$this->SetXY(2, 35);
			$this->MultiCell(73, 4, TextoPdf($r['RAZON_COMERCIAL']), 0, "C");
			$this->SetFont("Arial", "", 7);
			$this->SetX(2);
			$this->MultiCell(73, 4, TextoPdf($r['NOMBRES']), 0, "C");
			$this->SetFont("Arial", "B", 16);
			$this->SetXY(0, 50);
			$this->MultiCell(77, 8, $r['PASSPORT'], 0, "C");
			$this->SetFont("Arial", "B", 9);
			$this->SetXY(0, 59);
			$this->MultiCell(77, 5, TextoPdf("COMPRA: $".number_format((float)$r['COMPRA'], 0, ',', '.')), 0, "C");
			$this->SetFont("Arial", "", 6);
			$this->SetXY(0, 66);
			$this->MultiCell(77, 4, TextoPdf("BOLETA ".$n." DE ".$total), 0, "C");
		}
	}
}//clase

	conectar();
	$sorteos = evSorteos();
	$hora = isset($_GET['hora']) ? $_GET['hora'] : '';
	if(!isset($sorteos[$hora])){
		die("Hora de sorteo inválida. Use: ?hora=".implode(" | ?hora=", array_keys($sorteos)));
	}

	$pdf = new PDF('P', 'mm', array(78, 78));
	$pdf->SetAutoPageBreak(false);
	$pdf->cuerpo($hora, $sorteos[$hora]);
	$pdf->Output("boletas_sorteo_".str_replace(':', '', $hora).".pdf", "I");
?>
