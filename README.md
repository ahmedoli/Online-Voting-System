# Online Voting System

A secure web-based voting platform built with PHP and MySQL for conducting online elections.

## Overview

This system allows voters to participate in elections online with secure authentication and OTP verification, while providing administrators with comprehensive election management tools.

## Features

### For Voters

- Secure user registration with email verification
- OTP-based authentication for each login session
- Participate in active elections with intuitive interface
- View personal voting history and status
- Access real-time election results
- Password recovery functionality

### For Election Management

- Create and manage multiple elections with flexible scheduling
- Add candidates to different election positions
- Monitor live voting statistics and participation rates
- Export detailed results to CSV format
- View comprehensive audit logs of all voting activity
- Manage election timelines with start/end date controls

### Security Features

- OTP email verification for every login session
- One vote per user per election enforcement
- SHA-256 encrypted vote logging with secure hash generation
- Advanced session management and timeout protection
- Comprehensive SQL injection and XSS protection
- Rate limiting and brute force protection
- Secure headers implementation (CSP, HSTS, etc.)

## Technical Requirements

- **PHP**: 8.0 or higher (with MySQLi extension)
- **MySQL**: 5.7 or higher
- **Web Server**: Apache/Nginx with mod_rewrite enabled
- **Email Server**: SMTP server for OTP delivery (optional but recommended)
- **SSL Certificate**: Recommended for production deployment

## Installation Guide

### 1. File Setup

```bash
# Clone or download the project
git clone https://github.com/yourusername/Online_Voting_System.git
# Move to your web server directory
cp -r Online_Voting_System /var/www/html/
# Or for XAMPP: C:\xampp\htdocs\
```

### 2. Database Configuration

```sql
-- Create the database
CREATE DATABASE online_voting_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import the complete schema
mysql -u root -p online_voting_system < database/database.sql
```

### 3. System Configuration

<<<<<<< HEAD
Edit `includes/db_connect.php` with your database credentials:

```php
$db_host = '127.0.0.1';
$db_user = 'your_username';
$db_pass = 'your_secure_password';
$db_name = 'online_voting_system';
```

Configure email settings in `includes/email_config.php` (optional):
=======
**Database Setup:**

```bash
# Copy the example database config file
cp includes/db_connect.example.php includes/db_connect.php

# Edit includes/db_connect.php with your database credentials:
# $db_host = '127.0.0.1';
# $db_user = 'your_username'; 
# $db_pass = 'your_secure_password';
# $db_name = 'online_voting_system';
```

**Email Configuration (Optional but Recommended):**

```bash
# Copy the example email config file  
cp includes/email_config.example.php includes/email_config.php

# Edit includes/email_config.php with your SMTP settings
```

```php
// Email configuration for OTP delivery
$email_config = [
    'smtp' => [
        'host' => 'your-smtp-server.com',
        'port' => 587,
        'username' => 'your-email@domain.com',
        'password' => 'your-app-password',
        'encryption' => 'tls'
    ]
];
```

### 4. Access Points

| Function | URL Path | Description |
|----------|----------|-------------|
| **Homepage** | `/Online_Voting_System/` | Landing page with system overview |
| **Voter Registration** | `/user/register.php` | New user account creation |
| **Voter Login** | `/user/login.php` | Secure login with OTP verification |
| **Voter Dashboard** | `/user/dashboard.php` | Main voting interface and history |
| **Public Results** | `/guest/view_results.php` | Live election results (public access) |
| **Management Panel** | `/admin/` | Election administration interface |

## Project Structure

```texttext
Online_Voting_System/
├── 📁 admin/                       # Administration Panel
│   ├── add_candidate.php           # Candidate registration form
│   ├── add_election.php            # Election creation interface
│   ├── dashboard.php               # Administrative overview
│   ├── export_results.php          # CSV export functionality
│   ├── index.php                   # Admin landing page
│   ├── login.php                   # Administrative authentication
│   ├── logout.php                  # Session termination
│   ├── logs.php                    # Audit trail viewer
│   ├── manage_candidates.php       # Candidate management tools
│   ├── manage_elections.php        # Election lifecycle management
│   ├── process_add_candidate.php   # Candidate creation handler
│   ├── process_add_election.php    # Election creation handler
│   └── view_results.php            # Real-time results dashboard
├── 📁 user/                        # Voter Interface
│   ├── change_password.php         # Password update functionality
│   ├── dashboard.php               # Main voter interface
│   ├── forgot_password.php         # Password recovery system
│   ├── index.php                   # User portal landing
│   ├── login.php                   # OTP-secured authentication
│   ├── logout.php                  # Secure session cleanup
│   ├── process_vote.php            # Vote processing engine
│   ├── profile.php                 # User profile management
│   ├── register.php                # New voter registration
│   ├── resend_otp.php              # OTP re-delivery system
│   └── verify_otp.php              # OTP validation handler
├── 📁 guest/                       # Public Access
│   └── view_results.php            # Anonymous results viewing
├── 📁 includes/                    # Core System Files
│   ├── db_connect.php              # Database connection handler
│   ├── email_config.php            # SMTP configuration
│   ├── email_sender_fixed.php      # Email delivery system
│   ├── error_handler.php           # Centralized error management
│   ├── functions.php               # Core utility functions
│   ├── get_live_results.php        # Live results API endpoint
│   ├── otp_send.php                # OTP generation and delivery
│   └── security_headers.php        # Security headers implementation
├── 📁 css/                         # Styling
│   └── style.css                   # Main stylesheet
├── 📁 js/                          # Client-side Scripts
│   ├── realtime-results.js         # Live results updates
│   └── script.js                   # General JavaScript utilities
├── 📁 database/                    # Database Schema
│   └── database.sql                # Complete database structure
├── 📁 error_pages/                 # Error Handling
│   └── 500.html                    # Server error page
├── 📁 vendor/                      # Third-party Libraries
│   └── PHPMailer-master/           # Email library
├── index.php                       # System entry point
└── README.md                       # Documentation
```

## Usage Instructions

### Voter Workflow

1. **Registration**: Create account with email and personal information
2. **Email Verification**: Confirm email address via OTP
3. **Secure Login**: Authenticate with email + OTP for each session
4. **Vote Casting**: Select candidates for available election positions
5. **Result Viewing**: Access live results after completing votes

### Administration Workflow

1. **System Access**: Login to administrative panel
2. **Election Setup**: Create elections with scheduling parameters
3. **Candidate Management**: Add candidates to specific positions
4. **Monitoring**: Track participation rates and voting progress
5. **Results Management**: Export final results and audit reports

## Security Implementation

- **Authentication**: Multi-factor with email + OTP verification
- **Session Security**: Secure session handling with regeneration
- **Data Protection**: Prepared statements preventing SQL injection
- **Input Validation**: Comprehensive server-side validation
- **Rate Limiting**: Protection against automated attacks
- **Audit Logging**: Complete transaction trail with SHA-256 hashing
- **Error Handling**: Secure error messages preventing information disclosure

## Troubleshooting

**Database Issues:**

- Verify MySQL service status
- Check database credentials in `includes/db_connect.php`
- Ensure proper character encoding (utf8mb4)

**Email Delivery Problems:**

- Verify SMTP configuration in `includes/email_config.php`
- Check firewall settings for email ports (587, 465)
- Review spam/junk folders for OTP messages

**Voting Issues:**

- Confirm user email verification status
- Check election active status and timing
- Verify candidate assignments to elections

**Performance Optimization:**

- Enable PHP OPcache for better performance
- Configure MySQL query cache
- Implement proper server-side caching

## License

This project is released under the MIT License.

## Technology Stack

- **Backend**: PHP 8+ with MySQLi
- **Frontend**: Bootstrap 5.3.2 + Custom CSS
- **Email System**: PHPMailer with SMTP support
- **Security**: Custom implementation with industry best practices
- **Database**: MySQL 5.7+ with optimized schema design
