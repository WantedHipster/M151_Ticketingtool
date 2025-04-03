"# M151_Ticketingtool" 
"# M151_Ticketingtool" 
"# M151_Ticketingtool" 
# Ticketing System Installation Guide

## Overview

This is a PHP-based ticketing system with user authentication, role-based permissions, and ticket management features. The system allows users to create, view, and manage support tickets with different priorities and statuses.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.4+)
- Web server (Apache or Nginx)
- Composer (optional, for future dependencies)

## Installation Steps

### 1. Database Setup

1. Create a new MySQL database for the application:

```sql
CREATE DATABASE 151_users;
```

2. Create a database user and grant permissions:

```sql
CREATE USER 'rob'@'localhost' IDENTIFIED BY 'Test1234';
GRANT ALL PRIVILEGES ON 151_users.* TO 'rob'@'localhost';
FLUSH PRIVILEGES;
```

> **Note:** In a production environment, use a strong password and a more specific username.

### 2. Application Files

1. Clone or download all files to your web server's document root or a subdirectory.

2. Directory structure should include:
   - PHP files (index.php, dashboard.php, etc.)
   - CSS files (session_timer.css, etc.)
   - JavaScript files (session_timer.js)

### 3. Configuration

1. Check the database connection settings in `db_connect.php` and update if necessary:

```php
$host = 'localhost'; // Database host
$username = 'rob';   // Database username
$password = 'Test1234'; // Database password
$database = '151_users'; // Database name
```

### 4. Database Schema Initialization

1. Run the database setup script by visiting:

```
http://yourdomain.com/update_db.php
```

This script will:
- Create necessary tables (users, tickets, ticket_comments, ticket_history)
- Add required columns
- Set up initial configuration

### 5. First User Setup

1. Navigate to the registration page and create the first user:

```
http://yourdomain.com/register.php
```

2. The first user will automatically be assigned admin privileges.

3. Subsequent users will be regular users by default, but can be promoted by an admin.

### 6. Security Considerations

1. **Update passwords**: Change default database credentials in `db_connect.php`.

2. **Configure session security**: Review the session timeout settings in `session_config.php` if needed.

3. **Set proper permissions**: Ensure web server has appropriate permissions on files and directories.

4. **HTTPS**: For production environments, configure your web server to use HTTPS.

## Features

- **User management**: User registration, login, and role-based access control
- **Ticket creation**: Users can create tickets with different priorities
- **Ticket management**: View, update, assign tickets based on user roles
- **Comment system**: Add comments to tickets
- **History tracking**: Track changes to tickets
- **Admin panel**: Administrative features for user and ticket management
- **Session management**: Automatic session timeout and refresh

## File Descriptions

- `index.php` - Login page and application entry point
- `register.php` - User registration page
- `dashboard.php` - Main ticket overview dashboard
- `create_ticket.php` - Ticket creation form
- `view_ticket.php` - Detailed ticket view and management
- `admin.php` - Administrative dashboard and controls
- `db_connect.php` - Database connection configuration
- `session_config.php` - Session management configuration
- `session_timer.js` - Client-side session timeout handling
- `update_db.php` - Database initialization and update script

## Troubleshooting

1. **Database Connection Issues**:
   - Verify database credentials in `db_connect.php`
   - Ensure MySQL/MariaDB service is running
   - Check database user permissions

2. **Permission Errors**:
   - Ensure web server has read/write permissions for application directories

3. **Session Issues**:
   - Check PHP session configuration in php.ini
   - Verify session directory is writable

## Credits

This ticketing system was developed as part of the M151 module.

## License

This project is for educational purposes. Please respect intellectual property rights.
