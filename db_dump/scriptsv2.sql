-- Tablas del Sistema
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    activo BOOLEAN DEFAULT 1,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) NOT NULL UNIQUE,
    nombre_completo VARCHAR(150) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    telefono VARCHAR(20)
);

CREATE TABLE especialidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    especialidad_id INT NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    licencia VARCHAR(50) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (especialidad_id) REFERENCES especialidades(id)
);

CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    nombre_completo VARCHAR(150) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    fecha_hora DATETIME NOT NULL,
    estado ENUM('Programada', 'Completada', 'Cancelada') DEFAULT 'Programada',
    motivo TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (medico_id) REFERENCES medicos(id)
);

DELIMITER //
CREATE PROCEDURE sp_crear_cita(
    IN p_paciente_id INT,
    IN p_medico_id INT,
    IN p_fecha_hora DATETIME,
    IN p_motivo TEXT,
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_existe INT;

    SELECT COUNT(*) INTO v_existe
    FROM citas
    WHERE medico_id = p_medico_id
      AND fecha_hora = p_fecha_hora
      AND estado = 'Programada';

    IF v_existe > 0 THEN
        SET p_resultado = 'Error: El médico ya tiene una cita en ese horario.';
    ELSE
        INSERT INTO citas (paciente_id, medico_id, fecha_hora, motivo)
        VALUES (p_paciente_id, p_medico_id, p_fecha_hora, p_motivo);
        SET p_resultado = 'Cita agendada exitosamente.';
    END IF;
END //

CREATE PROCEDURE sp_obtener_citas_paciente(
    IN p_paciente_id INT
)
BEGIN
    SELECT c.id, c.fecha_hora, c.estado, c.motivo, m.nombre_completo AS medico, e.nombre AS especialidad
    FROM citas c
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.paciente_id = p_paciente_id
    ORDER BY c.fecha_hora DESC;
END //
DELIMITER ;