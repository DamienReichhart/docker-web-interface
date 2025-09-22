-- noinspection SqlNoDataSourceInspectionForFile

/*
    ==================================================
    Author:        Damien REICHHART
    Date:          16/09/2024
    Description:   Script to create the database and tables 
                   with initial data setup for the Atelier Proffessionalisant 3 project.
    Version:       1.0
    ==================================================
*/

-- ================================
-- 1. Initial Setup and Configurations
-- ================================

SET AUTOCOMMIT = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";
SET GLOBAL event_scheduler = "ON";

START TRANSACTION;

-- ================================
-- 2. Database Creation and Selection
-- ================================

-- Drop the database if it exists to avoid conflicts
DROP DATABASE IF EXISTS AtelierPro;

-- Create the database
CREATE DATABASE AtelierPro;
USE AtelierPro;

-- ================================
-- 3. Table Definitions
-- ================================

-- Table for email authentication tokens
CREATE TABLE EmailAuthentications (
    id INT AUTO_INCREMENT,
    tokenEmailAuthentications VARCHAR(100) NOT NULL,
    createdAtEmailAuthentications DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table for server information
CREATE TABLE Servers (
    id INT AUTO_INCREMENT,
    nameServers VARCHAR(50) NOT NULL,
    host VARCHAR(50) NOT NULL,
    urlIdentifierServer VARCHAR(257) NOT NULL,
    description TEXT,
    PRIMARY KEY (id),
    UNIQUE (nameServers)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table for user roles
CREATE TABLE Roles (
    id INT AUTO_INCREMENT,
    nameRoles VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (nameRoles)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table for different types of user actions
CREATE TABLE ActionsTypes (
    id INT AUTO_INCREMENT,
    descriptionActionsTypes VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (descriptionActionsTypes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table for users with a foreign key to Role
CREATE TABLE Users (
    id INT AUTO_INCREMENT,
    usernameUsers VARCHAR(50) NOT NULL,
    emailUsers VARCHAR(250) NOT NULL,
    passwordUsers VARCHAR(250) NOT NULL,
    idRoles INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (usernameUsers),
    FOREIGN KEY (idRoles) REFERENCES Roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Table for system logs with foreign keys to User and ActionsTypes
CREATE TABLE Logs (
    id INT AUTO_INCREMENT,
    createdAtLogs DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    idUsers INT NOT NULL,
    idActionsTypes INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (idUsers) REFERENCES Users(id),
    FOREIGN KEY (idActionsTypes) REFERENCES ActionsTypes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- Many-to-Many relationship table between User and Servers
CREATE TABLE ManageServers (
    idUsers INT,
    idServers INT,
    sshUserManageServers VARCHAR(50) NOT NULL,
    sshUserPasswordManageServers VARCHAR(250) DEFAULT NULL,
    PRIMARY KEY (idUsers, idServers),
    FOREIGN KEY (idUsers) REFERENCES Users(id),
    FOREIGN KEY (idServers) REFERENCES Servers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- ================================
-- 4. Performance Indexes
-- ================================

-- Adding indexes to improve query performance
CREATE INDEX idx_nameServers ON Servers(nameServers);
CREATE INDEX idx_usernameUsers ON Users(usernameUsers);
CREATE INDEX idx_emailUsers ON Users(emailUsers);

-- ================================
-- 5. Database Event Schedules
-- ================================

-- Event to clean up old email authentications every 15 minutes
CREATE EVENT cleanup_email_authentications
ON SCHEDULE EVERY 15 MINUTE
DO
DELETE FROM EmailAuthentications
WHERE createdAtEmailAuthentications < (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE);


DELIMITER $$

CREATE TRIGGER hash_url_identifier_before_insert
    BEFORE INSERT ON Servers
    FOR EACH ROW
BEGIN
    SET NEW.urlIdentifierServer = SHA2(NEW.host, 256);
END$$

    CREATE TRIGGER hash_url_identifier_before_update
        BEFORE UPDATE ON Servers
        FOR EACH ROW
    BEGIN
        SET NEW.urlIdentifierServer = SHA2(NEW.host, 256);
END$$

DELIMITER ;


-- ================================
-- 6. Insert Initial Data into Tables
-- ================================

-- Insert default data into the Servers table
INSERT INTO Servers (nameServers, host) VALUES
    ('Internal', '192.168.20.40');

-- Insert default roles into the Role table
INSERT INTO Roles (nameRoles) VALUES
    ('ADMIN'),
    ('USER'),
    ('WAITING');

-- Insert sample users into the User table
INSERT INTO Users (usernameUsers, emailUsers, passwordUsers, idRoles) VALUES
    ('admin', 'admin@localhost', '$2y$10$8sWtzlnmxJRiCFhORJwWTupu6Vo9ZBOZGyxONSw57ggh10tL0rKVa', 1);

-- Insert sample email authentication tokens into the EmailAuthentications table
INSERT INTO EmailAuthentications (tokenEmailAuthentications, createdAtEmailAuthentications) VALUES
    ('token123', NOW()),
    ('token456', NOW()),
    ('token789', NOW());

-- Insert action types into the ActionsTypes table
INSERT INTO ActionsTypes (descriptionActionsTypes) VALUES
    ('Login'),
    ('Logout'),
    ('Server Access');

-- Insert sample logs into the Logs table
INSERT INTO Logs (idUsers, idActionsTypes) VALUES
    (1, 1),  -- Admin login
    (1, 3),  -- Manager server access
    (1, 2);  -- User logout

-- Insert data into ManageServers table
INSERT INTO ManageServers (idUsers, idServers, sshUserManageServers, sshUserPasswordManageServers) VALUES
    (1, 1, 'dockeruser', 'dockerpassword');  -- Admin manages Server1


-- ================================
-- 7. Finalize Transaction
-- ================================

COMMIT;
SET AUTOCOMMIT = 1;
