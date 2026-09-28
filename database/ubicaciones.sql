-- =====================================================================
--  PUNTO Y APARTE - ubicaciones.sql
--  División territorial de El Salvador vigente desde el 1 de mayo de 2024
--  (Ley Especial para la Reestructuración Municipal, D.L. 762/2023):
--    14 departamentos, 44 municipios y 262 distritos.
--  Los distritos corresponden a los antiguos municipios.
--
--  Ejecutar DESPUÉS de schema.sql, sobre las tablas vacías.
-- =====================================================================

USE punto_y_aparte;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Departamentos (código oficial = id)
-- ---------------------------------------------------------------------
INSERT INTO departamentos (id_departamento, nombre) VALUES
  (1,  'Ahuachapán'),
  (2,  'Santa Ana'),
  (3,  'Sonsonate'),
  (4,  'Chalatenango'),
  (5,  'La Libertad'),
  (6,  'San Salvador'),
  (7,  'Cuscatlán'),
  (8,  'La Paz'),
  (9,  'Cabañas'),
  (10, 'San Vicente'),
  (11, 'Usulután'),
  (12, 'San Miguel'),
  (13, 'Morazán'),
  (14, 'La Unión');

-- ---------------------------------------------------------------------
-- Municipios (44)
-- ---------------------------------------------------------------------
INSERT INTO municipios (id_municipio, id_departamento, nombre) VALUES
  -- Ahuachapán
  (1,  1,  'Ahuachapán Norte'),
  (2,  1,  'Ahuachapán Centro'),
  (3,  1,  'Ahuachapán Sur'),
  -- Santa Ana
  (4,  2,  'Santa Ana Norte'),
  (5,  2,  'Santa Ana Centro'),
  (6,  2,  'Santa Ana Este'),
  (7,  2,  'Santa Ana Oeste'),
  -- Sonsonate
  (8,  3,  'Sonsonate Norte'),
  (9,  3,  'Sonsonate Centro'),
  (10, 3,  'Sonsonate Este'),
  (11, 3,  'Sonsonate Oeste'),
  -- Chalatenango
  (12, 4,  'Chalatenango Norte'),
  (13, 4,  'Chalatenango Centro'),
  (14, 4,  'Chalatenango Sur'),
  -- La Libertad
  (15, 5,  'La Libertad Norte'),
  (16, 5,  'La Libertad Centro'),
  (17, 5,  'La Libertad Oeste'),
  (18, 5,  'La Libertad Este'),
  (19, 5,  'La Libertad Costa'),
  (20, 5,  'La Libertad Sur'),
  -- San Salvador
  (21, 6,  'San Salvador Norte'),
  (22, 6,  'San Salvador Oeste'),
  (23, 6,  'San Salvador Este'),
  (24, 6,  'San Salvador Centro'),
  (25, 6,  'San Salvador Sur'),
  -- Cuscatlán
  (26, 7,  'Cuscatlán Norte'),
  (27, 7,  'Cuscatlán Sur'),
  -- La Paz
  (28, 8,  'La Paz Oeste'),
  (29, 8,  'La Paz Centro'),
  (30, 8,  'La Paz Este'),
  -- Cabañas
  (31, 9,  'Cabañas Oeste'),
  (32, 9,  'Cabañas Este'),
  -- San Vicente
  (33, 10, 'San Vicente Norte'),
  (34, 10, 'San Vicente Sur'),
  -- Usulután
  (35, 11, 'Usulután Norte'),
  (36, 11, 'Usulután Este'),
  (37, 11, 'Usulután Oeste'),
  -- San Miguel
  (38, 12, 'San Miguel Norte'),
  (39, 12, 'San Miguel Centro'),
  (40, 12, 'San Miguel Oeste'),
  -- Morazán
  (41, 13, 'Morazán Norte'),
  (42, 13, 'Morazán Sur'),
  -- La Unión
  (43, 14, 'La Unión Norte'),
  (44, 14, 'La Unión Sur');

-- ---------------------------------------------------------------------
-- Distritos (262), agrupados por departamento
-- ---------------------------------------------------------------------

-- Parte 1. Ahuachapán (12 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Ahuachapán Norte
  (1, 'Atiquizaya'), (1, 'El Refugio'), (1, 'San Lorenzo'), (1, 'Turín'),
  -- Ahuachapán Centro
  (2, 'Ahuachapán'), (2, 'Apaneca'), (2, 'Concepción de Ataco'), (2, 'Tacuba'),
  -- Ahuachapán Sur
  (3, 'Guaymango'), (3, 'Jujutla'), (3, 'San Francisco Menéndez'), (3, 'San Pedro Puxtla');

-- Parte 2. Santa Ana (13 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Santa Ana Norte
  (4, 'Masahuat'), (4, 'Metapán'), (4, 'Santa Rosa Guachipilín'), (4, 'Texistepeque'),
  -- Santa Ana Centro
  (5, 'Santa Ana'),
  -- Santa Ana Este
  (6, 'Coatepeque'), (6, 'El Congo'),
  -- Santa Ana Oeste
  (7, 'Candelaria de la Frontera'), (7, 'Chalchuapa'), (7, 'El Porvenir'),
  (7, 'San Antonio Pajonal'), (7, 'San Sebastián Salitrillo'), (7, 'Santiago de la Frontera');

-- Parte 3. Sonsonate (16 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Sonsonate Norte
  (8, 'Juayúa'), (8, 'Nahuizalco'), (8, 'Salcoatitán'), (8, 'Santa Catarina Masahuat'),
  -- Sonsonate Centro
  (9, 'Sonsonate'), (9, 'Sonzacate'), (9, 'Nahulingo'), (9, 'San Antonio del Monte'),
  (9, 'Santo Domingo de Guzmán'),
  -- Sonsonate Este
  (10, 'Izalco'), (10, 'Armenia'), (10, 'Caluco'), (10, 'San Julián'), (10, 'Cuisnahuat'),
  (10, 'Santa Isabel Ishuatán'),
  -- Sonsonate Oeste
  (11, 'Acajutla');

-- Parte 4. Chalatenango (33 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Chalatenango Norte
  (12, 'La Palma'), (12, 'Citalá'), (12, 'San Ignacio'),
  -- Chalatenango Centro
  (13, 'Nueva Concepción'), (13, 'Tejutla'), (13, 'La Reina'), (13, 'Agua Caliente'),
  (13, 'Dulce Nombre de María'), (13, 'El Paraíso'), (13, 'San Francisco Morazán'),
  (13, 'San Rafael'), (13, 'Santa Rita'), (13, 'San Fernando'),
  -- Chalatenango Sur
  (14, 'Chalatenango'), (14, 'Arcatao'), (14, 'Azacualpa'), (14, 'Comalapa'),
  (14, 'Concepción Quezaltepeque'), (14, 'El Carrizal'), (14, 'La Laguna'), (14, 'Las Vueltas'),
  (14, 'Nombre de Jesús'), (14, 'Nueva Trinidad'), (14, 'Ojos de Agua'), (14, 'Potonico'),
  (14, 'San Antonio de la Cruz'), (14, 'San Antonio Los Ranchos'), (14, 'San Francisco Lempa'),
  (14, 'San Isidro Labrador'), (14, 'San José Cancasque'), (14, 'San José Las Flores'),
  (14, 'San Luis del Carmen'), (14, 'San Miguel de Mercedes');

-- Parte 5. La Libertad (22 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- La Libertad Norte
  (15, 'Quezaltepeque'), (15, 'San Matías'), (15, 'San Pablo Tacachico'),
  -- La Libertad Centro
  (16, 'San Juan Opico'), (16, 'Ciudad Arce'),
  -- La Libertad Oeste
  (17, 'Colón'), (17, 'Jayaque'), (17, 'Sacacoyo'), (17, 'Tepecoyo'), (17, 'Talnique'),
  -- La Libertad Este
  (18, 'Antiguo Cuscatlán'), (18, 'Huizúcar'), (18, 'Nuevo Cuscatlán'),
  (18, 'San José Villanueva'), (18, 'Zaragoza'),
  -- La Libertad Costa
  (19, 'Chiltiupán'), (19, 'Jicalapa'), (19, 'La Libertad'), (19, 'Tamanique'), (19, 'Teotepeque'),
  -- La Libertad Sur
  (20, 'Comasagua'), (20, 'Santa Tecla');

-- Parte 6. San Salvador (19 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- San Salvador Norte
  (21, 'Aguilares'), (21, 'El Paisnal'), (21, 'Guazapa'),
  -- San Salvador Oeste
  (22, 'Apopa'), (22, 'Nejapa'),
  -- San Salvador Este
  (23, 'Ilopango'), (23, 'San Martín'), (23, 'Soyapango'), (23, 'Tonacatepeque'),
  -- San Salvador Centro
  (24, 'San Salvador'), (24, 'Mejicanos'), (24, 'Ayutuxtepeque'), (24, 'Ciudad Delgado'),
  (24, 'Cuscatancingo'),
  -- San Salvador Sur
  (25, 'Panchimalco'), (25, 'Rosario de Mora'), (25, 'San Marcos'), (25, 'Santo Tomás'),
  (25, 'Santiago Texacuangos');

-- Parte 7. Cuscatlán (16 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Cuscatlán Norte
  (26, 'Suchitoto'), (26, 'San José Guayabal'), (26, 'Oratorio de Concepción'),
  (26, 'San Bartolomé Perulapía'), (26, 'San Pedro Perulapán'),
  -- Cuscatlán Sur
  (27, 'Cojutepeque'), (27, 'San Rafael Cedros'), (27, 'Candelaria'), (27, 'Monte San Juan'),
  (27, 'El Carmen'), (27, 'San Cristóbal'), (27, 'Santa Cruz Michapa'), (27, 'San Ramón'),
  (27, 'El Rosario'), (27, 'Santa Cruz Analquito'), (27, 'Tenancingo');

-- Parte 8. La Paz (22 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- La Paz Oeste
  (28, 'Cuyultitán'), (28, 'Olocuilta'), (28, 'San Juan Talpa'), (28, 'San Luis Talpa'),
  (28, 'San Pedro Masahuat'), (28, 'Tapalhuaca'), (28, 'San Francisco Chinameca'),
  -- La Paz Centro
  (29, 'El Rosario'), (29, 'Jerusalén'), (29, 'Mercedes La Ceiba'), (29, 'Paraíso de Osorio'),
  (29, 'San Antonio Masahuat'), (29, 'San Emigdio'), (29, 'San Juan Tepezontes'),
  (29, 'San Luis La Herradura'), (29, 'San Miguel Tepezontes'), (29, 'San Pedro Nonualco'),
  (29, 'Santa María Ostuma'), (29, 'Santiago Nonualco'),
  -- La Paz Este
  (30, 'San Juan Nonualco'), (30, 'San Rafael Obrajuelo'), (30, 'Zacatecoluca');

-- Parte 9. Cabañas (9 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Cabañas Oeste
  (31, 'Ilobasco'), (31, 'Tejutepeque'), (31, 'Jutiapa'), (31, 'Cinquera'),
  -- Cabañas Este
  (32, 'Sensuntepeque'), (32, 'Victoria'), (32, 'Dolores'), (32, 'Guacotecti'), (32, 'San Isidro');

-- Parte 10. San Vicente (13 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- San Vicente Norte
  (33, 'Apastepeque'), (33, 'Santa Clara'), (33, 'San Ildefonso'), (33, 'San Esteban Catarina'),
  (33, 'San Sebastián'), (33, 'San Lorenzo'), (33, 'Santo Domingo'),
  -- San Vicente Sur
  (34, 'San Vicente'), (34, 'Guadalupe'), (34, 'Verapaz'), (34, 'Tepetitán'), (34, 'Tecoluca'),
  (34, 'San Cayetano Istepeque');

-- Parte 11. Usulután (23 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Usulután Norte
  (35, 'Santiago de María'), (35, 'Alegría'), (35, 'Berlín'), (35, 'Mercedes Umaña'),
  (35, 'Jucuapa'), (35, 'El Triunfo'), (35, 'Estanzuelas'), (35, 'San Buenaventura'),
  (35, 'Nueva Granada'),
  -- Usulután Este
  (36, 'Usulután'), (36, 'Jucuarán'), (36, 'San Dionisio'), (36, 'Concepción Batres'),
  (36, 'Santa María'), (36, 'Ozatlán'), (36, 'Tecapán'), (36, 'Santa Elena'),
  (36, 'California'), (36, 'Ereguayquín'),
  -- Usulután Oeste
  (37, 'Jiquilisco'), (37, 'Puerto El Triunfo'), (37, 'San Agustín'), (37, 'San Francisco Javier');

-- Parte 12. San Miguel (20 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- San Miguel Norte
  (38, 'Ciudad Barrios'), (38, 'Sesori'), (38, 'Nuevo Edén de San Juan'), (38, 'San Gerardo'),
  (38, 'San Luis de la Reina'), (38, 'Carolina'), (38, 'San Antonio del Mosco'), (38, 'Chapeltique'),
  -- San Miguel Centro
  (39, 'San Miguel'), (39, 'Comacarán'), (39, 'Uluazapa'), (39, 'Moncagua'), (39, 'Quelepa'),
  (39, 'Chirilagua'),
  -- San Miguel Oeste
  (40, 'Chinameca'), (40, 'Nueva Guadalupe'), (40, 'Lolotique'), (40, 'San Jorge'),
  (40, 'San Rafael Oriente'), (40, 'El Tránsito');

-- Parte 13. Morazán (26 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- Morazán Norte
  (41, 'Arambala'), (41, 'Cacaopera'), (41, 'Corinto'), (41, 'El Rosario'), (41, 'Joateca'),
  (41, 'Jocoaitique'), (41, 'Meanguera'), (41, 'Perquín'), (41, 'San Fernando'),
  (41, 'San Isidro'), (41, 'Torola'),
  -- Morazán Sur
  (42, 'Chilanga'), (42, 'Delicias de Concepción'), (42, 'El Divisadero'), (42, 'Gualococti'),
  (42, 'Guatajiagua'), (42, 'Jocoro'), (42, 'Lolotiquillo'), (42, 'Osicala'), (42, 'San Carlos'),
  (42, 'San Francisco Gotera'), (42, 'San Simón'), (42, 'Sensembra'), (42, 'Sociedad'),
  (42, 'Yamabal'), (42, 'Yoloaiquín');

-- Parte 14. La Unión (18 distritos)
INSERT INTO distritos (id_municipio, nombre) VALUES
  -- La Unión Norte
  (43, 'Anamorós'), (43, 'Bolívar'), (43, 'Concepción de Oriente'), (43, 'El Sauce'),
  (43, 'Lislique'), (43, 'Nueva Esparta'), (43, 'Pasaquina'), (43, 'Polorós'), (43, 'San José'),
  (43, 'Santa Rosa de Lima'),
  -- La Unión Sur
  (44, 'Conchagua'), (44, 'El Carmen'), (44, 'Intipucá'), (44, 'La Unión'),
  (44, 'Meanguera del Golfo'), (44, 'San Alejo'), (44, 'Yayantique'), (44, 'Yucuaiquín');

-- Verificación rápida (debe dar 14 / 44 / 262):
-- SELECT (SELECT COUNT(*) FROM departamentos) AS departamentos,
--        (SELECT COUNT(*) FROM municipios)    AS municipios,
--        (SELECT COUNT(*) FROM distritos)     AS distritos;
