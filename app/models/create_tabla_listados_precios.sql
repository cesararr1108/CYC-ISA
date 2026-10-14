CREATE TABLE IF NOT EXISTS t_listados_precios_proveedor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ean VARCHAR(30) NOT NULL,
  id_referencia INT NOT NULL,
  nit_proveedor INT NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  precio_iva DECIMAL(10,2) NOT NULL,
  fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  usuario_actualizacion INT,
  UNIQUE KEY unique_ean_ref_prov (ean, id_referencia, nit_proveedor),
  FOREIGN KEY (nit_proveedor) REFERENCES t_terceros(nit),
  FOREIGN KEY (id_referencia) REFERENCES t_referencias(id),
  INDEX idx_ean (ean),
  INDEX idx_id_referencia (id_referencia),
  INDEX idx_nit_proveedor (nit_proveedor)
);
