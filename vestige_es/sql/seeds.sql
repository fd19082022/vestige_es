
INSERT INTO roles (id, nombre) VALUES
  (1, 'admin') ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
INSERT INTO roles (id, nombre) VALUES
  (2, 'usuario') ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO categorias (nombre) VALUES
('Poleras'), ('Pantalones'), ('Abrigos'), ('Zapatos'), ('Accesorios')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO tallas (id, nombre) VALUES
(1, 'XS'), (2, 'S'), (3, 'M'), (4, 'L'), (5, 'XL')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);

INSERT INTO colores (id, nombre) VALUES
(1, 'Negro'), (2, 'Blanco'), (3, 'Azul'), (4, 'Rojo'), (5, 'Verde')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
