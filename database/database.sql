CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL
);
INSERT INTO admins (username, password)
VALUES ('admin', 'admin123') ON DUPLICATE KEY
UPDATE username = username;
CREATE TABLE IF NOT EXISTS elections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  start_date DATE,
  end_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS candidates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  election_id INT NOT NULL,
  candidate_name VARCHAR(100) NOT NULL,
  party VARCHAR(100),
  position VARCHAR(100) DEFAULT '',
  votes INT DEFAULT 0,
  FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
);
-- Voter Authentication Tables
CREATE TABLE IF NOT EXISTS voters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(15) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  id_number VARCHAR(20) NOT NULL UNIQUE,
  id_type ENUM('NID', 'Student') NOT NULL DEFAULT 'NID',
  registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS voter_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  voter_id INT NOT NULL,
  otp_code VARCHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS vote_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  voter_id INT NOT NULL,
  election_id INT NOT NULL,
  candidate_id INT NOT NULL,
  vote_hash VARCHAR(64) NOT NULL,
  voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
  FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE,
  FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
  UNIQUE KEY unique_voter_election_position (voter_id, election_id, position)
);
-- Add vote_hash column to existing tables
ALTER TABLE vote_logs
ADD COLUMN IF NOT EXISTS vote_hash VARCHAR(64) DEFAULT '';
-- Add position column to existing candidates table (safe migration)
ALTER TABLE candidates
ADD COLUMN IF NOT EXISTS position VARCHAR(100) DEFAULT '';