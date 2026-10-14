-- Vista que calcula el costo promedio por referencia y período (mes-año)
CREATE OR REPLACE VIEW v_costo_promedio AS
SELECT 
    td.id_referencia,
    MONTH(t.fecha_hora) as mes,
    YEAR(t.fecha_hora) as ano,
    AVG(td.costo_unitario) as promedio
FROM t_transacciones_detalle td
INNER JOIN t_transacciones t ON t.id = td.id_transaccion
WHERE t.sw IN (5, 8)  -- Ventas y devoluciones
GROUP BY 
    td.id_referencia,
    MONTH(t.fecha_hora),
    YEAR(t.fecha_hora)
ORDER BY 
    td.id_referencia,
    YEAR(t.fecha_hora) DESC,
    MONTH(t.fecha_hora) DESC;
