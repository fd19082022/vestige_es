-- ========================================
-- SEEDS ACTUALIZADOS: Solo 2 roles (admin y usuario)
-- ========================================

-- Roles del sistema
INSERT INTO roles (id, nombre, descripcion) VALUES
  (1, 'admin', 'Administrador del sistema con acceso total'),
  (2, 'usuario', 'Usuario regular (puede publicar, comprar y vender)')
ON DUPLICATE KEY UPDATE 
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion);

-- Categorías principales
INSERT INTO categorias (nombre, descripcion) VALUES
  ('Niños', 'Ropa y accesorios para niños'),
  ('Mujeres', 'Ropa y accesorios para mujeres'),
  ('Hombres', 'Ropa y accesorios para hombres'),
  ('Accesorios', 'Accesorios diversos para todas las edades')
ON DUPLICATE KEY UPDATE 
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion);

-- Tallas estándar
INSERT INTO tallas (id, nombre, codigo, descripcion) VALUES
  (1, 'XS', 'XS', 'Extra Small'),
  (2, 'S', 'S', 'Small'),
  (3, 'M', 'M', 'Medium'),
  (4, 'L', 'L', 'Large'),
  (5, 'XL', 'XL', 'Extra Large'),
  (6, NULL, '36', 'Talla numérica 36'),
  (7, NULL, '38', 'Talla numérica 38'),
  (8, NULL, '40', 'Talla numérica 40')
ON DUPLICATE KEY UPDATE 
  nombre = VALUES(nombre),
  codigo = VALUES(codigo),
  descripcion = VALUES(descripcion);

-- Colores disponibles
INSERT INTO colores (id, nombre, hex) VALUES
  (1, 'Negro', '#000000'),
  (2, 'Blanco', '#FFFFFF'),
  (3, 'Gris', '#808080'),
  (4, 'Azul', '#0000FF'),
  (5, 'Rojo', '#FF0000'),
  (6, 'Verde', '#008000'),
  (7, 'Beige', '#F5F5DC'),
  (8, 'Marrón', '#8B4513'),
  (9, 'Amarillo', '#FFFF00'),
  (10, 'Rosa', '#FFC0CB')
ON DUPLICATE KEY UPDATE 
  nombre = VALUES(nombre),
  hex = VALUES(hex);

-- Estados de publicación
INSERT INTO estados_publicacion (id, nombre) VALUES
  (0, 'Activa'),
  (1, 'Borrador'),
  (2, 'Publicada'),
  (3, 'Pausada'),
  (4, 'Vendida')
ON DUPLICATE KEY UPDATE 
  nombre = VALUES(nombre);

-- ========================================
-- USUARIOS DE PRUEBA
-- ========================================

-- Admin principal (password: admin123)
INSERT INTO usuarios (rol_id, nombre, apellido, correo, telefono, password_hash, estado, creado_en)
VALUES (
    1,
    'Admin',
    'Sistema',
    'admin@vestige.local',
    '70000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'activo',
    NOW()
) ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    apellido = VALUES(apellido);

-- Usuario regular 1 (password: usuario123)
INSERT INTO usuarios (rol_id, nombre, apellido, correo, telefono, password_hash, estado, creado_en)
VALUES (
    2,
    'Juan',
    'Pérez',
    'juan@vestige.local',
    '71234567',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'activo',
    NOW()
) ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    apellido = VALUES(apellido);

-- Usuario regular 2 (password: usuario123)
INSERT INTO usuarios (rol_id, nombre, apellido, correo, telefono, password_hash, estado, creado_en)
VALUES (
    2,
    'María',
    'García',
    'maria@vestige.local',
    '72345678',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'activo',
    NOW()
) ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    apellido = VALUES(apellido);

-- Usuario regular 3 (password: usuario123)
INSERT INTO usuarios (rol_id, nombre, apellido, correo, telefono, password_hash, estado, creado_en)
VALUES (
    2,
    'Carlos',
    'López',
    'carlos@vestige.local',
    '73456789',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'activo',
    NOW()
) ON DUPLICATE KEY UPDATE 
    nombre = VALUES(nombre),
    apellido = VALUES(apellido);

-- ========================================
-- VERIFICACIÓN
-- ========================================

-- Ver roles creados
SELECT * FROM roles ORDER BY id;

-- Ver usuarios por rol
SELECT r.nombre AS rol, COUNT(u.id) AS total
FROM roles r
LEFT JOIN usuarios u ON u.rol_id = r.id
GROUP BY r.id, r.nombre
ORDER BY r.id;

COMMIT;