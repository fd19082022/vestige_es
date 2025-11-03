
-- Migración: chat (conversaciones, mensajes) y ofertas
CREATE TABLE IF NOT EXISTS conversaciones (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  comprador_id BIGINT UNSIGNED NOT NULL,
  vendedor_id BIGINT UNSIGNED NOT NULL,
  publicacion_id BIGINT UNSIGNED NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_conv_pub (publicacion_id),
  KEY idx_conv_users (comprador_id, vendedor_id),
  CONSTRAINT fk_conv_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_vendedor FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_pub FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversacion_id BIGINT UNSIGNED NOT NULL,
  emisor_id BIGINT UNSIGNED NOT NULL,
  contenido TEXT NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_msg_conv (conversacion_id),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_user FOREIGN KEY (emisor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ofertas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  publicacion_id BIGINT UNSIGNED NOT NULL,
  comprador_id BIGINT UNSIGNED NOT NULL,
  precio_ofrecido DECIMAL(10,2) NOT NULL,
  estado ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_oferta_pub (publicacion_id),
  KEY idx_oferta_user (comprador_id),
  CONSTRAINT fk_oferta_pub FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_oferta_user FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
