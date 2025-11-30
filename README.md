# Online Voting System

A secure web-based voting platform built with PHP and MySQL for conducting online elections.

## Overview

This system allows voters to participate in elections online with secure authentication, while administrators can manage elections and view real-time results.

## Features

### For Voters
- User registration with email verification
- OTP-based secure login
- Vote in active elections
- View voting history
- Real-time election results

### For Administrators
- Create and manage elections
- Add candidates to elections
- View live voting results
- Export results to CSV
- Monitor secure vote logs

### Security Features
- OTP email verification for login
- One vote per user per election
- SHA-256 encrypted vote logging
- Secure session management
- SQL injection protection

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- SMTP server for emails (optional)

## Installation

### 1. Setup Files
```bash
# Download or clone the project
# Copy to your web server directory (e.g., C:\xampp\htdocs\)
```

### 2. Database Setup
```sql
-- Create database
CREATE DATABASE online_voting_system;

-- Import the schema
# Use phpMyAdmin to import database/database.sql
# OR use command line:
mysql -u root -p online_voting_system < database/database.sql
```

### 3. Configuration
Edit `includes/db_connect.php`:
```php
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';  // Your MySQL password
$db_name = 'online_voting_system';
```

### 4. Access URLs

| Page | URL | Credentials |
|------|-----|-------------|
| Homepage | `http://localhost/Online_Voting_System/` | - |
| Admin Login | `http://localhost/Online_Voting_System/admin/login.php` | admin / admin123 |
| Admin Dashboard | `http://localhost/Online_Voting_System/admin/dashboard.php` | After login |
| Voter Registration | `http://localhost/Online_Voting_System/user/register.php` | - |
| Voter Login | `http://localhost/Online_Voting_System/user/login.php` | Your registered email |
| Voter Dashboard | `http://localhost/Online_Voting_System/user/dashboard.php` | After login + OTP |
| Public Results | `http://localhost/Online_Voting_System/guest/view_results.php` | - |

## Complete Project Structure

```
Online_Voting_System/
├── admin/
│   ├── add_candidate.php           # Add new candidate form
│   ├── add_election.php            # Add new election form
│   ├── dashboard.php               # Admin main dashboard
│   ├── export_results.php          # CSV export functionality
│   ├── fetch_results.php           # AJAX results data
│   ├── login.php                   # Admin login page
│   ├── logout.php                  # Admin logout
│   ├── logs.php                    # Secure vote logs viewer
│   ├── manage_candidates.php       # Candidate management
│   ├── manage_elections.php        # Election management
│   ├── process_add_candidate.php   # Process candidate creation
│   ├── process_add_election.php    # Process election creation
│   └── view_results.php            # Live election results
├── user/
│   ├── dashboard.php               # Voter main dashboard
│   ├── forgot_password.php         # Password reset page
│   ├── login.php                   # Voter login with OTP
│   ├── logout.php                  # Voter logout
│   ├── register.php                # Voter registration
│   ├── resend_otp.php              # Resend OTP functionality
│   └── verify_otp.php              # OTP verification page
├── guest/
│   └── view_results.php            # Public election results
├── includes/
│   ├── db_connect.php              # Database connection
│   ├── email_config.php            # Email configuration
│   ├── email_sender_fixed.php      # Email functionality
│   ├── functions.php               # Core system functions
│   └── get_live_results.php        # Live results AJAX
├── css/
│   └── style.css                   # Main stylesheet
├── js/
│   ├── realtime-results.js         # Real-time results updates
│   └── script.js                   # General JavaScript functions
├── database/
│   └── database.sql                # Database schema and setup
├── vendor/
│   └── PHPMailer-master/
│       ├── LICENSE                 # PHPMailer license
│       └── src/
│           ├── Exception.php       # PHPMailer exception handler
│           ├── PHPMailer.php       # Main PHPMailer class
│           └── SMTP.php            # SMTP functionality
├── index.php                       # Landing page
└── README.md                       # Project documentation
```

## Usage

### For Voters
1. Register with email and phone number
2. Verify email with OTP code
3. Login and verify with OTP for each session
4. Vote in available elections
5. View results after voting

### For Administrators
1. Login to admin panel
2. Create new elections with start/end dates
3. Add candidates to elections
4. Monitor live results and statistics
5. Export results as needed

## Email Configuration (Optional)

To enable OTP emails, configure `includes/email_config.php`:
```php
$email_config = [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls'
    ]
];
```

## Troubleshooting

**Database Connection Issues:**
- Check MySQL service is running
- Verify credentials in `db_connect.php`

**Email Not Working:**
- Check SMTP settings
- Verify firewall allows email ports
- Check spam folder for OTP emails

**Vote Not Recording:**
- Check if `vote_hash` column exists in `vote_logs` table
- Verify user hasn't already voted in the election

## License

MIT License - see LICENSE file for details.

## Built With

- PHP - Server-side scripting
- MySQL - Database
- Bootstrap 5 - UI framework
- PHPMailer - Email functionality