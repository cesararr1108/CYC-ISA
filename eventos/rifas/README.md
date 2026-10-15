# Rifas – Evento viernes 16 de octubre

Módulo PHP 5.4 + MSSQL (`mssql_*`) con la misma estructura del proyecto de eventos:

| Archivo | Descripción |
|---|---|
| `sql/evento_16_octubre.sql` | Crea `T_RIFA_ELECTROMENORES` y `T_RIFA_SORTEOS_GANADORES` (ejecutar una vez). |
| `models/Rifas.php` | Conserva los `case` existentes y agrega los nuevos `EV_*`. Configuración al inicio del archivo. |
| `controllers/Rifas.js` | Lógica de pantalla (jQuery + SweetAlert). |
| `views/Rifas.php` | Vista con 4 pestañas: consulta cliente, sorteos de la tarde, premios mayores, electromenores. |

## Dinámicas

1. **Electromenores**: compras ≥ $50.000 en ≥ 10 labs de `T_RIFAS`, antes de las 2:00 pm, stock 20.
   Se registra en `T_RIFA_ELECTROMENORES` (1 por cliente; control de stock con bloqueo para que dos cajas no entreguen el último al mismo tiempo).
2. **Sorteos de la tarde** (14:00 $2M, 15:00 $3M, 16:00 $4M, 17:00 $5M): compra total de **cualquier** laboratorio acumulada
   hasta la hora de corte (`FECHA_HORA <= corte`). El botón *Sortear* elige al azar (`NEWID()`), calcula el 5% sobre una compra máxima de $10.000.000
   y guarda en `T_RIFA_SORTEOS_GANADORES`. Un ganador que no reclama se puede **anular** y volver a sortear.
3. **Premios mayores**: 1 sola boleta por cliente en `T_USUARIOS.BOLETAS1 = 1`. Se valida primero la Dinámica #1 (≥ $50.000 en 20 labs)
   y si no cumple la #2 (≥ $1.000.000 en 8 labs). El cliente debe estar registrado en `T_USUARIOS`.
   Al generarla se imprime con `../resources/pdf/pdf_boleta.php?pass=X&cant=1` (mismo PDF de `PDF_Boletas()`); se puede reimprimir.
   Existe además *Generar boletas pendientes* para hacerlo masivo antes del sorteo final.

Todas las validaciones se repiten en el servidor al registrar (no se confía en lo que muestra la pantalla) y la hora se toma de `GETDATE()` del SQL Server.

## Configuración (`models/Rifas.php`)

- `EV_TABLA_PEDIDOS`: `T_PEDIDOS` (columnas `CODIGO`, `VALOR_TOTAL`, `PASSPORT` y `FECHA_HORA`).
- `EV_FECHA`: `20261016`.
- `EV_ELECTRO_STOCK`, montos, número de labs y horas de los sorteos (`evSorteos()`).
- `EV_SORTEO_EXCLUIR_GANADORES`: `true` = quien ya ganó un sorteo de la tarde no vuelve a participar en los siguientes.
- Usuario que registra: `$_SESSION['Ses_Usuario']` (ajustar en `evUsuario()` si la sesión usa otro nombre).
