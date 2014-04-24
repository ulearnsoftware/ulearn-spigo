##
## Table structure for table `acl_action`
##

CREATE TABLE IF NOT EXISTS acl_action  (
  id int(11) NOT NULL auto_increment,
  name varchar(50) default NULL,
  description varchar(180) default NULL,
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_action`
##

INSERT INTO `acl_action` VALUES (1, 'view', 'Mostrar');
INSERT INTO `acl_action` VALUES (2, 'toggl', NULL);
INSERT INTO `acl_action` VALUES (3, 'creat', NULL);
INSERT INTO `acl_action` VALUES (4, 'updat', NULL);
INSERT INTO `acl_action` VALUES (5, 'delet', NULL);
INSERT INTO `acl_action` VALUES (6, 'admin', NULL);

##
## Table structure for table `acl_group`
##

CREATE TABLE IF NOT EXISTS acl_group (
  id int(11) NOT NULL auto_increment,
  name varchar(50) NOT NULL default '',
  description varchar(180) default NULL,
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_group`
##

INSERT INTO acl_group VALUES (1,'desarrollo','Cuenta de desarrollo');

##
## Table structure for table `acl_group_permission`
##

CREATE TABLE IF NOT EXISTS acl_group_permission (
  id int(11) NOT NULL auto_increment,
  id_action int(11) NOT NULL default '0',
  id_group int(11) NOT NULL default '0',
  id_resource int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_group_permission`
##

INSERT INTO acl_group_permission VALUES (1,1,1,1);
INSERT INTO acl_group_permission VALUES (2,1,1,2);
INSERT INTO acl_group_permission VALUES (3,1,1,3);

##
## Table structure for table `acl_membership`
##

CREATE TABLE IF NOT EXISTS acl_membership (
  id int(11) NOT NULL auto_increment,
  id_user int(11) NOT NULL default '0',
  id_group int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_membership`
##

INSERT INTO acl_membership VALUES (1,1,1);

##
## Table structure for table `acl_resource`
##

CREATE TABLE IF NOT EXISTS acl_resource (
  id int(11) NOT NULL auto_increment,
  name varchar(50) default NULL,
  description varchar(180) default NULL,
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_resource`
##


INSERT INTO `acl_resource` VALUES (1, 'acl_user', 'Administracion de ACLs de usuarios');
INSERT INTO `acl_resource` VALUES (2, 'acl_perfiles', 'Administracion de ACLs de grupos');
INSERT INTO `acl_resource` VALUES (3, 'acl_user2', 'Administracion de ACLs de usuarios (2)');
INSERT INTO `acl_resource` VALUES (4, 'rec_lista', 'Lista de Recursos');
INSERT INTO `acl_resource` VALUES (5, 'rec_opciones', 'Opciones de Administración');
INSERT INTO `acl_resource` VALUES (7, 'opc_cambioclave', NULL);
INSERT INTO `acl_resource` VALUES (8, 'con_lista', NULL);
INSERT INTO `acl_resource` VALUES (9, 'col_foros', NULL);
INSERT INTO `acl_resource` VALUES (10, 'ag_lista', NULL);
INSERT INTO `acl_resource` VALUES (11, 'ag_calendario', NULL);
INSERT INTO `acl_resource` VALUES (12, 'cart_lista', NULL);
INSERT INTO `acl_resource` VALUES (13, 'chat', 'chat');
INSERT INTO `acl_resource` VALUES (14, 'calf_lista', NULL);
INSERT INTO `acl_resource` VALUES (15, 'calf_asignar', NULL);
INSERT INTO `acl_resource` VALUES (16, 'calf_tomar', NULL);
INSERT INTO `acl_resource` VALUES (17, 'not_lista', NULL);
INSERT INTO `acl_resource` VALUES (18, 'not_notas_alumno', NULL);
INSERT INTO `acl_resource` VALUES (19, 'calf_calificar', NULL);
INSERT INTO `acl_resource` VALUES (20, 'acl_materias_grupo', NULL);
INSERT INTO `acl_resource` VALUES (21, 'adm_eliminar_materias', NULL);
##
## Table structure for table `acl_user`
##

CREATE TABLE IF NOT EXISTS acl_user (
  id int(11) NOT NULL auto_increment,
  name varchar(50) default NULL,
  description varchar(180) default NULL,
  md5_password varchar(32) default NULL,
  estatus enum('A','I') not NULL default 'I',
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_user`
##

INSERT INTO acl_user VALUES (1,'root','Usuario administrativo de prueba','7a5210c173ea40c03205a5de7dcd4cb0');

##
## Table structure for table `acl_user_permission`
##

CREATE TABLE IF NOT EXISTS acl_user_permission (
  id int(11) NOT NULL auto_increment,
  id_action int(11) NOT NULL default '0',
  id_user int(11) NOT NULL default '0',
  id_resource int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
);

##
## Dumping data for table `acl_user_permission`
##

# --------------------------------------------------------

#
# Table structure for table `chat_materias`
#

CREATE TABLE chat_materias (
  id int(11) NOT NULL auto_increment,
  id_materia_periodo_lectivo int(11) NOT NULL default '0',
  estatus enum('A','I') NOT NULL default 'A',
  PRIMARY KEY  (id)
);

# --------------------------------------------------------

#
# Table structure for table `chat_messages`
#

CREATE TABLE chat_messages (
  id int(11) NOT NULL auto_increment,
  id_materia_periodo_lectivo int(11) NOT NULL default '0',
  `type` tinyint(1) NOT NULL default '0',
  room varchar(30) NOT NULL default '',
  login varchar(255) NOT NULL default '',
  latin1 tinyint(1) NOT NULL default '0',
  m_time int(11) NOT NULL default '0',
  address varchar(30) NOT NULL default '',
  message text NOT NULL,
  PRIMARY KEY  (id)
);

# --------------------------------------------------------

#
# Table structure for table `chat_users`
#

CREATE TABLE chat_users (
  id int(11) NOT NULL auto_increment,
  id_materia_periodo_lectivo int(11) NOT NULL default '0',
  login varchar(255) NOT NULL default '',
  ultimo_envio datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id)
);

# --------------------------------------------------------

#
# Table structure for table `ul_alumno`
#

CREATE TABLE IF NOT EXISTS ul_alumno  (
  id int(10) unsigned NOT NULL auto_increment,
  cedula varchar(13) NOT NULL default '',
  matricula int(11) default NULL,
  nombre varchar(40) NOT NULL default '',
  apellido varchar(40) NOT NULL default '',
  email varchar(64) NOT NULL default '',
  direccion varchar(255) NOT NULL default '',
  telefono varchar(60) NOT NULL default '',
  civil enum('S','C','D','V') NOT NULL default 'S',
  estatus enum('A','I') NOT NULL default 'A',
  fecha_ingreso date NOT NULL default '0000-00-00',
  id_representante int(10) unsigned default NULL,
  id_acl_user int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY UC_cedula (cedula),
  UNIQUE KEY UC_matricula (matricula),
  KEY IDX_apellido_nombre (apellido,nombre)
);

# ########################################################

#
# Table structure for table `ul_alumno_calificable`
#

CREATE TABLE IF NOT EXISTS ul_alumno_calificable  (
  id_alumno_calificable int(10) unsigned NOT NULL auto_increment,
  puntuacion decimal(10,4) default NULL,
  estatus enum('N','V','T','A') NOT NULL default 'N',
  fecha_inicio datetime NOT NULL default '0000-00-00 00:00:00',
  fecha_cierre datetime NOT NULL default '0000-00-00 00:00:00',
  fecha_realizacion datetime NOT NULL default '0000-00-00 00:00:00',
  fecha_terminacion datetime NOT NULL default '0000-00-00 00:00:00',
  id_alumno_materia int(10) unsigned NOT NULL default '0',
  id_calificable int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_alumno_calificable)
);

# ########################################################

#
# Table structure for table `ul_alumno_materia`
#

CREATE TABLE IF NOT EXISTS ul_alumno_materia  (
  id int(10) unsigned NOT NULL auto_increment,
  nota_final decimal(10,2) NOT NULL default '0.00',
  asistencia decimal(5,2) unsigned default NULL,
  n_vez int(10) unsigned NOT NULL default '1',
  estatus enum('A','I') NOT NULL default 'A',
  aprobada enum('S','H','N','C') NOT NULL default 'N',
  descripcion varchar(255) default NULL,
  id_alumno int(10) unsigned NOT NULL default '0',
  id_periodo_lectivo int(10) unsigned NOT NULL default '0',
  id_materia int(10) unsigned NOT NULL default '0',
  id_materia_periodo_lectivo int(10) unsigned NOT NULL default '0',
  abierto char(1) NOT NULL default '1',
  PRIMARY KEY  (id),
  UNIQUE KEY UC_alumno_materia (id_periodo_lectivo,id_alumno,id_materia,id_materia_periodo_lectivo)
);

# ########################################################

#
# Estructura de tabla para la tabla `ul_alumno_pregunta`
#

CREATE TABLE IF NOT EXISTS ul_alumno_pregunta (
  id_alumno_pregunta int(11) NOT NULL auto_increment,
  puntuacion decimal(10,4) default NULL,
  id_alumno_calificable int(11) NOT NULL default '0',
  id_pregunta int(11) NOT NULL default '0',
  fecha_hora datetime default NULL,
  PRIMARY KEY  (id_alumno_pregunta)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_calificable`
#

CREATE TABLE IF NOT EXISTS ul_calificable  (
  id_calificable int(10) unsigned NOT NULL auto_increment,
  id_subparcial int(10) unsigned default NULL,
  codigo varchar(20) default NULL,
  titulo varchar(50) default NULL,
  base decimal(10,2) default NULL,
  ponderacion decimal(10,2) default NULL,
  duracion int(11) default NULL,
  disponibilidad int(11) default NULL,
  fecha_inicio datetime default NULL,
  fecha_creacion datetime default NULL,
  fecha_cierre datetime default NULL,
  estatus enum('A','I') NOT NULL default 'A',
  id_materia_periodo_lectivo int(11) NOT NULL default '0',
  PRIMARY KEY  (id_calificable)
);

#
# COMMENTS FOR TABLE ul_calificable:
#   estado
#       A - Activo; I - Inactivo
#

# ########################################################

#
# Table structure for table `ul_cartelera`
#

CREATE TABLE IF NOT EXISTS ul_cartelera  (
  id_cartelera int(11) NOT NULL auto_increment,
  titulo text NOT NULL,
  contenido text NOT NULL,
  creacion datetime NOT NULL default '0000-00-00 00:00:00',
  inicio datetime NOT NULL default '0000-00-00 00:00:00',
  final datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id_cartelera)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_categoria_materia`
#

CREATE TABLE IF NOT EXISTS ul_categoria_materia  (
  id int(10) unsigned NOT NULL auto_increment,
  nombre varchar(40) NOT NULL default '',
  estatus enum('A','I') NOT NULL default 'A',
  fecha_cierre datetime default NULL,
  id_unidad int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_configuracion`
#

CREATE TABLE IF NOT EXISTS ul_configuracion (
  id int(10) unsigned NOT NULL auto_increment,
  grupo varchar(32) NOT NULL default '',
  parametro varchar(32) NOT NULL default '',
  valor varchar(255) NOT NULL default '',
  lectura_flg int(1) NOT NULL default '1',
  descripcion varchar(100) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY UC_grupo_parametro (grupo,parametro)
) TYPE=MyISAM;

#
# Dumping data for table `ul_configuracion`
#

INSERT INTO ul_configuracion VALUES (11, 'Notas', 'Nota_base', '20', 1, 'Nota base para las calificaciones');
INSERT INTO ul_configuracion VALUES (1, 'Notas', 'Inicio_redondeo', '14.5', 1, 'Valor en el que inicia el redondeo al entero superior');
INSERT INTO ul_configuracion VALUES (2, 'Notas', 'Fin_redondeo', '15', 1, 'Valor en el que finaliza el redondeo al entero superior');
INSERT INTO ul_configuracion VALUES (3, 'Notas', 'Valor_aprobacion', '15', 1, 'Nota con la que se aprueba una materia');

# ########################################################

#
# Table structure for table `ul_contenido`
#

CREATE TABLE IF NOT EXISTS ul_contenido  (
  id_contenido int(11) NOT NULL auto_increment,
  orden int(10) unsigned NOT NULL default '0',
  titulo varchar(100) default NULL,
  contenido text,
  id_materia int(10) unsigned NOT NULL,
  estatus enum('A','I') NOT NULL default 'A',
  observacion varchar(20) default NULL,
  PRIMARY KEY  (id_contenido)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_docente`
#

CREATE TABLE IF NOT EXISTS ul_docente  (
  id int(10) unsigned NOT NULL auto_increment,
  cedula varchar(13) NOT NULL default '',
  nombre varchar(40) NOT NULL default '',
  apellido varchar(40) NOT NULL default '',
  email varchar(64) NOT NULL default '',
  estatus enum('A','I') NOT NULL default 'A',
  id_acl_user int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY UC_cedula (cedula),
  KEY IDX_sa_docente_apellido (apellido),
  KEY IDX_sa_docente_nombre (nombre)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_evento`
#

CREATE TABLE IF NOT EXISTS ul_evento  (
  id_evento int(11) NOT NULL auto_increment,
  titulo varchar(255) default NULL,
  contenido text,
  creacion datetime NOT NULL default '0000-00-00 00:00:00',
  inicio datetime NOT NULL default '0000-00-00 00:00:00',
  final datetime NOT NULL default '0000-00-00 00:00:00',
  id_calificable int(10) unsigned default NULL,
  tipo enum('A','N') default NULL,
  id_materia_periodo_lectivo int(11) NOT NULL ,
  PRIMARY KEY  (id_evento)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_foro`
#

CREATE TABLE IF NOT EXISTS ul_foro  (
  id_foro int(10) unsigned NOT NULL auto_increment,
  titulo varchar(255) NOT NULL default '',
  contenido text,
  estatus enum('A','I') default NULL,
  autor varchar(50) default NULL,
  id_materia_periodo_lectivo int(11) NOT NULL,
  PRIMARY KEY  (id_foro)
) TYPE=MyISAM;


# ########################################################

#
# Table structure for table `ul_materias_grupo`
#

CREATE TABLE ul_materias_grupo (
  id int(11) NOT NULL auto_increment,
  id_group int(11) NOT NULL default '0',
  id_materia_periodo_lectivo int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY materia_grupo (id_group,id_materia_periodo_lectivo)
) TYPE=MyISAM;




# ########################################################

#
# Estructura de tabla para la tabla `ul_grupo_parcial`
#

CREATE TABLE IF NOT EXISTS ul_grupo_parcial (
  id int(10) unsigned NOT NULL auto_increment,
  nombre varchar(40) NOT NULL default '',
  orden int(10) unsigned NOT NULL default '1',
  n_parciales int(11) NOT NULL default '0',
  fecha_inicio date NOT NULL default '0000-00-00',
  fecha_fin date NOT NULL default '0000-00-00',
  estatus enum('A','I') NOT NULL default 'A',
  id_periodo_lectivo int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY IDX_sa_periodo_1 (id_periodo_lectivo)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_grupo_pregunta`
#

CREATE TABLE IF NOT EXISTS ul_grupo_pregunta  (
  id_grupo_pregunta int(11) NOT NULL auto_increment,
  contenido text,
  orden int(11) NOT NULL default '0',
  id_calificable int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_grupo_pregunta),
  UNIQUE KEY IDX_ul_grupo_preguntas_orden (id_calificable,orden)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_materia`
#

CREATE TABLE IF NOT EXISTS ul_materia  (
  id int(10) unsigned NOT NULL auto_increment,
  cod_legal varchar(16) NOT NULL default '',
  nombre varchar(50) NOT NULL default '',
  creditos int(5) unsigned NOT NULL default '0',
  horasXsemana int(5) unsigned NOT NULL default '0',
  estatus enum('A','I') NOT NULL default 'A',
  fecha_cierre datetime default NULL,
  id_categoria int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY UC_cod_legal (cod_legal)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_materia_periodo_lectivo`
#

CREATE TABLE IF NOT EXISTS ul_materia_periodo_lectivo  (
  id int(10) unsigned NOT NULL auto_increment,
  paralelo int(10) unsigned NOT NULL default '0',
  estatus enum('A','I') NOT NULL default 'A',
  n_alumnos int(10) unsigned NOT NULL default '0',
  id_materia int(10) unsigned NOT NULL default '0',
  id_modalidad int(10) unsigned NOT NULL default '0',
  id_docente int(10) unsigned NOT NULL default '0',
  id_curso_periodo_lectivo int(10) unsigned default NULL,
  id_periodo_lectivo int(10) unsigned NOT NULL default '0',
  abierto char(1) NOT NULL default '1',
  PRIMARY KEY  (id),
  KEY IDX_sa_materia_anio_lectivo_estatus (estatus),
  KEY IDX_sa_materia_anio_lectivo_curso (id_curso_periodo_lectivo),
  KEY IDX_sa_materia_anio_lectivo_materia (id_materia)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_mensaje`
#

CREATE TABLE IF NOT EXISTS ul_mensaje  (
  id_mensaje int(10) unsigned NOT NULL auto_increment,
  titulo varchar(255) default NULL,
  contenido text,
  fecha_envio datetime default NULL,
  autor varchar(50) default NULL,
  tipo_docente char(1) binary NOT NULL default '0',
  id_parent int(10) unsigned default NULL,
  id_topico int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_mensaje)
) TYPE=MyISAM;

#
# Table structure for table `ul_mensaje_archivo`
#

CREATE TABLE IF NOT EXISTS ul_mensaje_archivo  (
  id int(10) unsigned NOT NULL auto_increment,
  id_mensaje int(10) unsigned NOT NULL default '0',
  URL varchar(255) NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;


# ########################################################

#
# Table structure for table `ul_modalidad`
#

CREATE TABLE IF NOT EXISTS ul_modalidad  (
  id int(10) NOT NULL auto_increment,
  modalidad enum('P','S','O','E') NOT NULL default 'P',
  horario enum('M','N','E','S','I','P','T') NOT NULL default 'M',
  descripcion varchar(50) NOT NULL default '',
  costo decimal(10,2) NOT NULL default '0.00',
  estatus enum('A','I') NOT NULL default 'A',
  PRIMARY KEY  (id),
  UNIQUE KEY UC_modalidad_horario (modalidad,horario)
) TYPE=MyISAM;

# ########################################################

#
# Estructura de tabla para la tabla `ul_parcial`
#

CREATE TABLE IF NOT EXISTS ul_parcial  (
  id int(10) unsigned NOT NULL auto_increment,
  fecha_inicio date default NULL,
  fecha_fin date default NULL,
  orden int(10) NOT NULL default '1',
  nombre varchar(15) NOT NULL default '',
  descripcion varchar(30) NOT NULL default '',
  calificable enum('S','C','N') NOT NULL default 'N',
  formula varchar(255) default NULL,
  estatus enum('A','I') NOT NULL default 'A',
  n_final char(1) binary default '0',
  id_grupo_parcial int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_periodo_lectivo`
#

CREATE TABLE IF NOT EXISTS ul_periodo_lectivo  (
  id int(10) unsigned NOT NULL auto_increment,
  nombre varchar(20) NOT NULL default '',
  descripcion varchar(120) NOT NULL default '',
  anio int(10) NOT NULL default '0',
  fecha_inicio date NOT NULL default '0000-00-00',
  fecha_fin date NOT NULL default '0000-00-00',
  estatus enum('A','P','F','C','I') NOT NULL default 'P',
  fecha_cierre datetime default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY UC_nombre (nombre),
  KEY IDX_sa_periodo_lectivo_anio (anio)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_pregunta`
#

CREATE TABLE IF NOT EXISTS ul_pregunta  (
  id_pregunta int(10) unsigned NOT NULL auto_increment,
  contenido text,
  orden int(10) unsigned default NULL,
  tipo_respuesta enum('A','M') default NULL,
  abierta enum('T','A') NOT NULL default 'T',
  t_ponderacion enum('V','P') NOT NULL default 'V',
  v_ponderacion decimal(10,2) NOT NULL default '0.00',
  URL varchar(255) default NULL,
  id_grupo_pregunta int(11) NOT NULL default '0',
  PRIMARY KEY  (id_pregunta)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_recurso`
#

CREATE TABLE IF NOT EXISTS ul_recurso  (
  id_recurso int(11) NOT NULL auto_increment,
  URL text,
  comentario varchar(255) default NULL,
  tipo enum('D','L','A') NOT NULL default 'A',
  id_parent int(11) default NULL,
  id_materia_periodo_lectivo int(11) default NULL,
  id_materia int(10) unsigned NOT NULL,
  estatus enum('A','I') NOT NULL default 'A',
  PRIMARY KEY  (id_recurso),
  KEY id_materia_periodo_lectivo (id_materia_periodo_lectivo),
  KEY id_parent (id_parent),
  KEY id_materia (id_materia),
  KEY id_materia_2 (id_materia,id_materia_periodo_lectivo,id_parent,tipo,URL(255))
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_respuesta`
#

CREATE TABLE IF NOT EXISTS ul_respuesta  (
  id_respuesta int(10) unsigned NOT NULL auto_increment,
  orden int(11) NOT NULL default '0',
  contenido text,
  URL varchar(255) default NULL,
  correcto char(1) binary NOT NULL default '',
  id_pregunta int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_respuesta)
) TYPE=MyISAM;

# ########################################################

#
# Estructura de tabla para la tabla `ul_respuesta_abierta`
#

CREATE TABLE IF NOT EXISTS ul_respuesta_abierta (
  id_respuesta_abierta int(11) NOT NULL auto_increment,
  tipo enum('T','A') NOT NULL default 'T',
  URL_Texto text NOT NULL,
  Puntos int(11) default NULL,
  PRIMARY KEY  (id_respuesta_abierta)
) TYPE=MyISAM COMMENT='Respuestas Abiertas del Alumno';

# ########################################################

#
# Table structure for table `ul_respuesta_alumno`
#

CREATE TABLE IF NOT EXISTS ul_respuesta_alumno  (
  id_respuesta_alumno int(10) unsigned NOT NULL auto_increment,
  fecha_hora datetime NOT NULL default '0000-00-00 00:00:00',
  id_alumno_pregunta int(10) unsigned NOT NULL default '0',
  id_respuesta int(10) unsigned NOT NULL default '0',
  id_respuesta_abierta int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_respuesta_alumno)
) TYPE=MyISAM;

# ########################################################

#
# Estructura de tabla para la tabla `ul_subparcial`
#

CREATE TABLE IF NOT EXISTS ul_subparcial  (
  id int(11) NOT NULL auto_increment,
  estatus enum('A','I') NOT NULL default 'A',
  nombre varchar(20) NOT NULL default '',
  ponderacion double(10,4) default NULL,
  ponderado char(1) binary NOT NULL default '0',
  id_parcial int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_topico`
#

CREATE TABLE IF NOT EXISTS ul_topico  (
  id_topico int(10) unsigned NOT NULL auto_increment,
  titulo varchar(255) NOT NULL default '',
  estatus enum('A','I') NOT NULL default 'A',
  contenido text,
  fecha_envio datetime default NULL,
  id_ultimo_envio int(10) unsigned default NULL,
  autor varchar(50) default NULL,
  n_respuestas int(10) unsigned default NULL,
  fecha_creacion datetime default NULL,
  fecha_cierre datetime default NULL,
  id_foro int(10) unsigned NOT NULL,
  PRIMARY KEY  (id_topico)
) TYPE=MyISAM;

# ########################################################

#
# Table structure for table `ul_unidad_academica`
#

CREATE TABLE IF NOT EXISTS ul_unidad_academica  (
  id int(10) NOT NULL auto_increment,
  nombre varchar(40) NOT NULL default '',
  cod_legal varchar(16) NOT NULL default '',
  cod_suc char(3) default NULL,
  estatus enum('A','I') NOT NULL default 'A',
  fecha_cierre datetime default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY UC_cod_legal (cod_legal)
) TYPE=MyISAM;
