-- removes database if one exists
DROP DATABASE IF EXISTS helpdesk;

-- starts a new
CREATE DATABASE helpdesk
CHARACTER SET utf8mb4;

-- Choose a db
USE helpdesk;


CREATE TABLE users(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- UNSISGNED FOR POSITIVE WHOLE NUMBERS ONLY, AUTO_INCREMENT TO AUTO GEN IDS, PRIMARY KEY IDENTIFIES EACH USER
    firstname VARCHAR(50) NOT NULL, -- NOT NULL MAKES SURE VALUE MUST BE ADDED
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE, -- PREVENTS DUPES
    password VARCHAR(255) NOT NULL,
    role ENUM("admin", "member") NOT NULL DEFAULT "member", -- ENUM RESTRICTS VALUES TO SPECIFIED CHOICES, DEFAULT PROVIDES A VALUE AUTOMATICALLY
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tickets(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM("Low", "Medium", "High") NOT NULL DEFAULT "Medium",
    status ENUM("Open", "In progress", "Closed") NOT NULL DEFAULT "Open",
    created_by INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY(created_by) REFERENCES users(id),
    FOREIGN KEY(assigned_to) REFERENCES users(id)
        ON DELETE SET NULL -- IF A USER ASSIGNED TO A TICKET GETS DELETED, THE TICKET WILL REMAIL BUT ITS ASSIGNED_TO BECOMES NULL

);


CREATE TABLE notes(
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY(ticket_id) REFERENCES tickets(id)
        ON DELETE CASCADE, -- if a ticket is deleted, all notes belonging to that ticket are automatically deleted aswell

    FOREIGN KEY(created_by) REFERENCES users(id)
);

INSERT INTO users(firstname, lastname, email, password, role)
VALUES
("Admin", "User", "admin@fixit.test", "$2y$12$NzQCLXarF6X1sjxuSIdIpOW", "admin"),
("John", "Doe", "john@fixit.test", "$2y$12$NzQCLXarF6X1sjxuSIdIpOW","member"),
("Jane", "Doe", "jane@fixit.test", "$2y$12$NzQCLXarF6X1sjxuSIdIpOW", "member");


INSERT INTO tickets(title,description,priority,status,created_by,assigned_to)
VALUES
('Broken classroom projector', 'The projector is not displaying anything.', 'High', 'Open', 2, 3),
('Faulty computer keyboard', 'Several keys are not responding.', 'Low', 'In progress', 3, 2),
('Network connection issue', 'The office computer cannot connect to the network.', 'High', 'Closed', 2, 2),
('Printer not working', 'The printer is showing a paper jam error.', 'Medium', 'Open', 3, NULL),
('Software installation request', 'A required application needs to be installed.', 'Medium', 'In progress', 2, 3);



INSERT INTO notes (ticket_id, body, created_by)
VALUES
(1, 'Investigating the projector connection.', 3),
(1, 'Replacement cable may be required.', 2),
(2, 'Keyboard needs further inspection.', 2),
(4, 'Printer tray has been checked.', 3);



-- Create the application database user
DROP USER IF EXISTS 'comp2245'@'localhost';
CREATE USER 'comp2245'@'localhost'
IDENTIFIED BY 'comp2245-demo';

-- Give the user permission to access the helpdesk database
GRANT SELECT, INSERT, UPDATE, DELETE
ON helpdesk.*
TO 'comp2245'@'localhost';
