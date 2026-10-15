/* =====================================================================
   RIFAS - EVENTO VIERNES 16 DE OCTUBRE
   - Electromenores (10 labs x $50.000, hasta las 2 pm, stock limitado)
   - Sorteos de la tarde (2, 3, 4 y 5 pm por compra total)
   - Premios mayores (Dinámica #1: 20 labs x $50.000 / #2: 8 labs x $1.000.000)
   ===================================================================== */
var URL_RIFAS = '../models/Rifas.php';
var URL_PDF_BOLETA = '../resources/pdf/pdf_boleta.php';
var URL_PDF_BOLETAS_SORTEO = '../resources/pdf/pdf_boletas_sorteo.php';
var EV_VERSION = 'rifas-v5';
var EV_DATOS = null;          // última consulta de cliente
var EV_SORTEO_HORA = null;    // sorteo seleccionado en la pestaña de sorteos

$(function(){
	console.log('Rifas.js ' + EV_VERSION);
	$('#evVersion').text(EV_VERSION);
	$('#TxtPassportRifas').on('keypress', function(e){
		if(e.which == 13){ ConsultarCliente(); }
	}).focus();
	$('#btnConsultar').on('click', ConsultarCliente);
	$('#btnElectro').on('click', RegistrarElectro);
	$('#btnMayores').on('click', RegistrarMayores);
	$('#btnReimprimir').on('click', function(){ if(EV_DATOS){ ImprimirBoleta(EV_DATOS.cliente.codigo_sap, 1); } });
	$(document).on('click', '.ev-imprimir', function(){ ImprimirBoleta($(this).data('pass'), 1); });
	$('#n_lab_sin_compas').on('click', VerLabsSinCompra);

	$('#btnSortear').on('click', Sortear);
	$('#btnRefrescarGanadores').on('click', CargarGanadores);
	$('#btnMayoresMasivo').on('click', MayoresMasivo);
	$('#btnMayoresRefrescar').on('click', CargarMayores);

	$('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
		var tab = $(e.target).attr('href');
		if(tab == '#tabSorteos'){ CargarGanadores(); if(EV_SORTEO_HORA){ CargarParticipantes(EV_SORTEO_HORA); } }
		if(tab == '#tabElectro'){ CargarElectro(); }
	});

	CargarConfig();
	CargarElectro();

	var u = $.trim($('#GetUsuario').val());
	if(u != '' && u != '0'){
		$('#TxtPassportRifas').val(u);
		ConsultarCliente();
	}
});

/* ---------------------------- utilidades ---------------------------- */
function EvPost(data, ok){
	$.ajax({
		url: URL_RIFAS,
		type: 'POST',
		data: data,
		dataType: 'json',
		cache: false
	}).done(function(r){
		if(!r || r.ok === false){
			EvAviso('warning', 'Atención:', (r && r.msg) ? r.msg : 'No fue posible procesar la solicitud');
			return;
		}
		ok(r);
	}).fail(function(xhr){
		console.log(xhr.responseText);
		var detalle = $.trim($('<div/>').html(xhr.responseText || '').text()).substring(0, 300);
		EvAviso('error', 'Error del servidor:', detalle != '' ? detalle : 'No fue posible comunicarse con el servidor (' + xhr.status + ')');
	});
}

// Confirmación nativa del navegador (no depende de la versión de SweetAlert)
function EvConfirmar(titulo, texto, fn){
	if(window.confirm(titulo + '\n\n' + texto)){
		fn();
	}
}

// Imprime la boleta con el mismo PDF de PDF_Boletas()
function ImprimirBoleta(pass, cant){
	var url = URL_PDF_BOLETA + '?pass=' + encodeURIComponent(pass) + '&cant=' + (cant || 1) + '&r=' + new Date().getTime();
	$('#dvPDF').html('<iframe width="100%" style="height:400px; border:0;" src="' + url + '"></iframe>');
	$('#ModalPDF').modal('show');
}

// Mensaje visible en pantalla (no depende de SweetAlert) + alerta emergente
function EvAviso(tipo, titulo, texto, sinPopup){
	var clase = tipo == 'success' ? 'alert-success' : (tipo == 'error' ? 'alert-danger' : 'alert-warning');
	$('#evMensaje').html(
		'<div class="alert ' + clase + ' alert-dismissible" style="font-size:18px;">' +
		  '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
		  '<b>' + Esc(titulo) + '</b> ' + Esc(texto) +
		'</div>');
	if(!sinPopup && typeof swal == 'function'){
		setTimeout(function(){ swal(titulo, texto, tipo); }, 400);
	}
}

// Deja la consulta lista para el siguiente cliente
function LimpiarCliente(){
	EV_DATOS = null;
	$('#evResultado').hide();
	$('#SpClienteRifas').html('');
	$('#ResultadosRifas').html('');
	$('#btnElectro, #btnMayores, #btnReimprimir').hide().prop('disabled', false);
	$('#TxtPassportRifas').val('').focus();
}

function Moneda(n){
	n = Math.round(parseFloat(n) || 0);
	var s = Math.abs(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	return (n < 0 ? '-$' : '$') + s;
}

function Esc(s){
	return $('<div/>').text(s === null || s === undefined ? '' : s).html();
}

function HoraAmPm(h){
	var p = h.split(':'), hh = parseInt(p[0], 10);
	return (hh > 12 ? hh - 12 : hh) + ':' + p[1] + (hh >= 12 ? ' pm' : ' am');
}

function Barra(valor, meta, clase){
	var pct = Math.min(100, Math.round(valor * 100 / meta));
	return '<div class="progress ev-progress">' +
	         '<div class="progress-bar progress-bar-' + (valor >= meta ? 'success' : clase) + '" style="width:' + Math.max(pct, 12) + '%">' +
	           valor + ' / ' + meta +
	         '</div>' +
	       '</div>';
}

/* ---------------------------- configuración ---------------------------- */
function CargarConfig(){
	EvPost({ op: 'EV_CONFIG' }, function(r){
		var reglas = '', botones = '';
		$.each(r.sorteos, function(i, s){
			reglas  += '<tr><td><b>' + HoraAmPm(s.hora) + '</b></td><td class="text-right">' + Moneda(s.monto) + '</td></tr>';
			botones += '<button class="btn btn-default btn-lg ev-btn-hora" data-hora="' + s.hora + '" style="margin-right:6px;">' +
			             '<span class="fa fa-clock-o"></span> ' + HoraAmPm(s.hora) + ' <small>(' + Moneda(s.monto) + ')</small>' +
			           '</button>';
		});
		$('#evReglasSorteos').html(reglas);
		$('#evBotonesSorteo').html(botones);
		$('.ev-btn-hora').on('click', function(){
			$('.ev-btn-hora').removeClass('btn-warning').addClass('btn-default');
			$(this).removeClass('btn-default').addClass('btn-warning');
			CargarParticipantes($(this).data('hora'));
		});
	});
}

/* ---------------------------- consulta cliente ---------------------------- */
function ConsultarCliente(){
	var pass = $.trim($('#TxtPassportRifas').val());
	if(pass == ''){
		swal('Atención', 'Digite el código SAP del cliente', 'warning');
		return;
	}
	$('#btnConsultar').prop('disabled', true);
	$('#evMensaje').html('');
	EvPost({ op: 'EV_CONSULTA', Pass: pass }, function(r){
		EV_DATOS = r;
		PintarCliente(r);
	});
	setTimeout(function(){ $('#btnConsultar').prop('disabled', false); }, 800);
}

function PintarCliente(r){
	var c = r.cliente;
	$('#SpClienteRifas').html(Esc(c.codigo_sap) + ' - ' + Esc(c.razon_comercial || c.nombres) +
		(c.razon_comercial && c.nombres && c.razon_comercial != c.nombres ? ' <small>(' + Esc(c.nombres) + ')</small>' : ''));
	$('#SpCompras').text(Moneda(r.compra_total));
	$('#comprasLabPart').text(Moneda(r.compra_labs));
	$('#n_lab_compas').text(r.labs_con_compra);
	$('#n_lab_sin_compas').text(r.n_labs - r.labs_con_compra);
	$('#evRegistrado').html(r.registrado
		? '<span class="label label-success">REGISTRADO</span>'
		: '<span class="label label-danger">NO REGISTRADO</span>');

	PintarElectro(r);
	PintarSorteosCliente(r);
	PintarMayores(r);
	PintarDetalleLabs(r);
	$('#evResultado').show();
}

function PintarElectro(r){
	var e = r.electro, html = '', clase, mostrarBtn = false;
	var disp = Math.max(0, e.stock - e.entregados);
	$('#evElectroDisp').text(disp + ' de ' + e.stock);

	html += 'Labs con compra &ge; ' + Moneda(e.minimo) + ':' + Barra(e.labs_cumple, e.requeridos, 'warning');

	if(e.ganado){
		clase = 'ev-done';
		html += '<div class="alert alert-info" style="margin:0;"><b>YA RECLAMÓ</b> su electromenor (' + Esc(e.fecha) + ')</div>';
	}else if(!e.abierto){
		clase = 'ev-no';
		html += '<div class="alert alert-danger" style="margin:0;">Dinámica cerrada (hasta las ' + HoraAmPm(e.hora_limite) + ')</div>';
	}else if(disp <= 0){
		clase = 'ev-no';
		html += '<div class="alert alert-danger" style="margin:0;">Se agotaron los electromenores</div>';
	}else if(e.labs_cumple >= e.requeridos){
		clase = 'ev-ok';
		mostrarBtn = true;
		html += '<div class="alert alert-success" style="margin:0;"><b>¡GANÓ UN ELECTROMENOR!</b> Quedan ' + disp + '</div>';
	}else{
		clase = 'ev-wait';
		html += '<div class="alert alert-warning" style="margin:0;">Le faltan <b>' + (e.requeridos - e.labs_cumple) +
		        '</b> laboratorios con compras de ' + Moneda(e.minimo) + '. Quedan ' + disp + '</div>';
	}
	$('#cardElectro').removeClass('ev-ok ev-no ev-wait ev-done').addClass(clase);
	$('#evElectroInfo').html(html);
	$('#btnElectro').toggle(mostrarBtn);
}

function PintarSorteosCliente(r){
	var html = '';
	$.each(r.sorteos, function(i, s){
		var estado;
		if(s.ganador && s.ganador.anulado == 0){
			estado = '<span class="label label-primary">GANÓ ' + Moneda(s.ganador.premio) + '</span>';
		}else if(s.cumple){
			estado = '<span class="label label-success">PARTICIPA</span>';
		}else if(!s.cerrado){
			estado = '<span class="label label-warning">FALTAN ' + Moneda(s.monto - s.compra) + '</span>';
		}else{
			estado = '<span class="label label-default">NO PARTICIPA</span>';
		}
		html += '<tr>' +
		          '<td><b>' + HoraAmPm(s.hora) + '</b></td>' +
		          '<td>' + Moneda(s.monto) + '</td>' +
		          '<td class="text-right">' + Moneda(s.compra) + '</td>' +
		          '<td>' + estado + '</td>' +
		        '</tr>';
	});
	$('#evSorteosCliente').html(html);
}

function PintarMayores(r){
	var m = r.mayores, html = '', clase, mostrarBtn = false, mostrarReimp = false;
	html += '<b>Dinámica #1</b> (labs con ' + Moneda(m.min1) + '):' + Barra(m.labs_50, m.req1, 'warning');
	html += '<b>Dinámica #2</b> (labs con ' + Moneda(m.min2) + '):' + Barra(m.labs_1m, m.req2, 'warning');

	if(m.boleta){
		clase = 'ev-done';
		mostrarReimp = true;
		html += '<div class="alert alert-info" style="margin:0;"><b>YA TIENE SU BOLETA</b> para los premios mayores</div>';
	}else if(m.dinamica > 0 && !r.registrado){
		clase = 'ev-no';
		html += '<div class="alert alert-danger" style="margin:0;">Cumple la Dinámica #' + m.dinamica + ' pero el cliente <b>no está registrado</b> en el evento</div>';
	}else if(m.dinamica > 0){
		clase = 'ev-ok';
		mostrarBtn = true;
		html += '<div class="alert alert-success" style="margin:0;"><b>¡GANA 1 BOLETA!</b> por la Dinámica #' + m.dinamica + '</div>';
	}else{
		clase = 'ev-wait';
		html += '<div class="alert alert-warning" style="margin:0;">Le faltan <b>' + (m.req1 - m.labs_50) + '</b> labs para la #1 ' +
		        'o <b>' + (m.req2 - m.labs_1m) + '</b> labs para la #2</div>';
	}
	$('#cardMayores').removeClass('ev-ok ev-no ev-wait ev-done').addClass(clase);
	$('#evMayoresInfo').html(html);
	$('#btnMayores').toggle(mostrarBtn);
	$('#btnReimprimir').toggle(mostrarReimp);
}

function PintarDetalleLabs(r){
	var html = '', ok = '<span class="fa fa-check text-success"></span>', no = '<span class="fa fa-times text-danger"></span>';
	$.each(r.labs, function(i, l){
		var c = parseFloat(l.compra) || 0;
		var fila = c >= r.mayores.min1 ? 'success' : (c <= 0 ? 'text-muted' : '');
		html += '<tr class="' + fila + '">' +
		          '<td>' + (i + 1) + '</td>' +
		          '<td>' + Esc(l.nit) + '</td>' +
		          '<td>' + Esc(l.nombres) + '</td>' +
		          '<td class="text-right">' + Moneda(c) + '</td>' +
		          '<td class="text-center">' + (c >= r.mayores.min1 ? ok : no) + '</td>' +
		          '<td class="text-center">' + (c >= r.mayores.min2 ? ok : no) + '</td>' +
		        '</tr>';
	});
	$('#ResultadosRifas').html(html);
}

function VerLabsSinCompra(){
	if(!EV_DATOS){ return; }
	var html = '<ul class="list-group">';
	$.each(EV_DATOS.labs, function(i, l){
		if((parseFloat(l.compra) || 0) <= 0){
			html += '<li class="list-group-item"><b>' + Esc(l.nit) + '</b> - ' + Esc(l.nombres) + '</li>';
		}
	});
	html += '</ul>';
	$('#DivLabSinCompras').html(html);
	$('#ModalFaltantes').modal('show');
}

function RegistrarElectro(){
	if(!EV_DATOS){ return; }
	var c = EV_DATOS.cliente;
	var nombre = c.codigo_sap + ' - ' + (c.razon_comercial || c.nombres);
	EvConfirmar('¿Entregar electromenor?', 'Cliente: ' + nombre, function(){
		$('#btnElectro').prop('disabled', true);
		EvPost({ op: 'EV_ELECTRO_REGISTRAR', Pass: c.codigo_sap }, function(r){
			LimpiarCliente();
			EvAviso('success', '¡ELECTROMENOR REDIMIDO!', nombre + '. Entregados ' + r.entregados + ' de ' + r.stock +
			        ' (quedan ' + Math.max(0, r.stock - r.entregados) + ').');
			CargarElectro();
		});
		setTimeout(function(){ $('#btnElectro').prop('disabled', false); }, 2000);
	});
}

function RegistrarMayores(){
	if(!EV_DATOS){ return; }
	var c = EV_DATOS.cliente;
	var nombre = c.codigo_sap + ' - ' + (c.razon_comercial || c.nombres);
	EvConfirmar('¿Generar boleta premios mayores?', 'Cliente: ' + nombre, function(){
		$('#btnMayores').prop('disabled', true);
		EvPost({ op: 'EV_MAYORES_REGISTRAR', Pass: c.codigo_sap }, function(r){
			LimpiarCliente();
			EvAviso('success', '¡BOLETA GENERADA!', nombre + '. ' + r.msg + '.', true);
			ImprimirBoleta(c.codigo_sap, 1);
		});
		setTimeout(function(){ $('#btnMayores').prop('disabled', false); }, 2000);
	});
}

function ConsultarClienteActual(){
	if(!EV_DATOS){ return; }
	EvPost({ op: 'EV_CONSULTA', Pass: EV_DATOS.cliente.codigo_sap }, function(r){
		EV_DATOS = r;
		PintarCliente(r);
	});
}

/* ---------------------------- sorteos de la tarde ---------------------------- */
function CargarParticipantes(hora, mantenerGanador){
	EV_SORTEO_HORA = hora;
	if(!mantenerGanador){ $('#evGanadorActual').html(''); }
	EvPost({ op: 'EV_SORTEO_PARTICIPANTES', hora: hora }, function(r){
		var html = '';
		$('#evSorteoTitulo').text('SORTEO ' + HoraAmPm(r.hora) + ' - COMPRA MÍNIMA ' + Moneda(r.monto));
		$('#evSorteoTotal').text(r.rows.length);
		$('#evSorteoAviso').html(r.cerrado ? '' :
			'<div class="alert alert-warning">El corte de este sorteo es a las <b>' + HoraAmPm(r.hora) +
			'</b>. La lista aún puede cambiar y el sorteo se habilita después del corte.</div>');
		$('#btnSortear').toggle(r.cerrado && r.rows.length > 0);
		// Un solo PDF con todas las boletas del sorteo
		$('#btnImprimirBoletas')
			.attr('href', URL_PDF_BOLETAS_SORTEO + '?hora=' + encodeURIComponent(r.hora) + '&r=' + new Date().getTime())
			.html('<span class="fa fa-print"></span> Imprimir boletas ' + HoraAmPm(r.hora) + ' (' + r.rows.length + ')')
			.toggle(r.rows.length > 0);

		$.each(r.rows, function(i, p){
			var gano = p.gano ? '<span class="label label-primary">' + HoraAmPm(p.gano) + '</span>' : '';
			html += '<tr>' +
			          '<td>' + (i + 1) + '</td>' +
			          '<td>' + Esc(p.passport) + '</td>' +
			          '<td>' + Esc(p.nombres) + '</td>' +
			          '<td class="text-right">' + Moneda(p.compra) + '</td>' +
			          '<td>' + (p.registrado == 1 ? '<span class="fa fa-check text-success"></span>' : '<span class="fa fa-times text-danger"></span>') + '</td>' +
			          '<td>' + gano + '</td>' +
			        '</tr>';
		});
		$('#evSorteoParticipantes').html(html || '<tr><td colspan="6" class="text-center">No hay clientes que cumplan el monto</td></tr>');
	});
}

function Sortear(){
	if(!EV_SORTEO_HORA){ return; }
	var hora = EV_SORTEO_HORA;
	EvConfirmar('¿Sortear ganador de las ' + HoraAmPm(hora) + '?', 'Se elegirá un cliente al azar entre los participantes', function(){
		$('#btnSortear').prop('disabled', true);
		EvPost({ op: 'EV_SORTEO_SORTEAR', hora: hora }, function(r){
			var g = r.ganador;
			$('#evGanadorActual').html(
				'<div class="alert alert-success ev-ganador animated tada">' +
				  '<span class="fa fa-trophy"></span> <b>' + Esc(g.passport) + ' - ' + Esc(g.nombres) + '</b><br>' +
				  'Compra: ' + Moneda(g.compra) + ' &nbsp; | &nbsp; Premio 5%: <b>' + Moneda(g.premio) + '</b>' +
				'</div>');
			CargarGanadores();
			CargarParticipantes(hora, true);
		});
		setTimeout(function(){ $('#btnSortear').prop('disabled', false); }, 1500);
	});
}

function CargarGanadores(){
	EvPost({ op: 'EV_SORTEO_GANADORES' }, function(r){
		var html = '', total = 0;
		$.each(r.rows, function(i, g){
			var anulado = g.anulado == 1;
			if(!anulado){ total += parseFloat(g.premio) || 0; }
			html += '<tr' + (anulado ? ' class="text-muted" style="text-decoration:line-through;"' : '') + '>' +
			          '<td><b>' + HoraAmPm(g.hora) + '</b></td>' +
			          '<td>' + Esc(g.passport) + ' - ' + Esc(g.nombres) + '</td>' +
			          '<td class="text-right">' + Moneda(g.compra) + '</td>' +
			          '<td class="text-right">' + Moneda(g.premio) + '</td>' +
			          '<td>' + (anulado ? '' :
			            '<button class="btn btn-xs btn-danger ev-anular" data-id="' + g.id + '" title="Anular (no reclamó)"><span class="fa fa-ban"></span></button>') +
			          '</td>' +
			        '</tr>';
		});
		if(html != ''){
			html += '<tr class="success"><td colspan="3" class="text-right"><b>TOTAL PREMIOS</b></td><td class="text-right"><b>' + Moneda(total) + '</b></td><td></td></tr>';
		}
		$('#evGanadores').html(html || '<tr><td colspan="5" class="text-center">Sin ganadores</td></tr>');
		$('.ev-anular').on('click', function(){
			var id = $(this).data('id');
			EvConfirmar('¿Anular este ganador?', 'Úselo si el cliente no reclamó el premio. Luego puede sortear de nuevo.', function(){
				EvPost({ op: 'EV_SORTEO_ANULAR', id: id }, function(){
					CargarGanadores();
					if(EV_SORTEO_HORA){ CargarParticipantes(EV_SORTEO_HORA); }
				});
			});
		});
	});
}

/* ---------------------------- premios mayores ---------------------------- */
function CargarMayores(){
	EvPost({ op: 'EV_MAYORES_LISTA' }, function(r){
		var html = '';
		$.each(r.rows, function(i, m){
			html += '<tr><td>' + (i + 1) + '</td><td>' + Esc(m.codigo_sap) + '</td><td>' + Esc(m.nombres) + '</td>' +
			        '<td><button class="btn btn-xs btn-default ev-imprimir" data-pass="' + Esc(m.codigo_sap) + '"><span class="fa fa-print"></span> Imprimir</button></td></tr>';
		});
		$('#evMayoresTotal').text(r.rows.length);
		$('#evMayoresLista').html(html || '<tr><td colspan="4" class="text-center">Sin boletas generadas</td></tr>');
	});
}

function MayoresMasivo(){
	EvConfirmar('¿Generar boletas pendientes?', 'Se genera 1 boleta a cada cliente registrado que cumpla la Dinámica #1 o #2 y aún no la tenga', function(){
		EvPost({ op: 'EV_MAYORES_MASIVO' }, function(r){
			swal('Proceso terminado', 'Boletas generadas: ' + r.generadas, 'success');
			CargarMayores();
		});
	});
}

/* ---------------------------- electromenores ---------------------------- */
function CargarElectro(){
	EvPost({ op: 'EV_ELECTRO_LISTA' }, function(r){
		var html = '';
		$.each(r.rows, function(i, e){
			html += '<tr>' +
			          '<td>' + (i + 1) + '</td>' +
			          '<td>' + Esc(e.passport) + '</td>' +
			          '<td>' + Esc(e.nombres) + '</td>' +
			          '<td class="text-center">' + Esc(e.n_labs) + '</td>' +
			          '<td class="text-right">' + Moneda(e.compra) + '</td>' +
			          '<td>' + Esc(e.usuario) + '</td>' +
			          '<td>' + Esc(e.fecha) + '</td>' +
			        '</tr>';
		});
		$('#evElectroTotal').text(r.rows.length + ' / ' + r.stock);
		$('#evElectroDisp').text(Math.max(0, r.stock - r.rows.length) + ' de ' + r.stock);
		$('#evElectroLista').html(html || '<tr><td colspan="7" class="text-center">Sin entregas</td></tr>');
	});
}
