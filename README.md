Car Rental System

A modern and user-friendly car rental management system built with PHP and MySQL. Designed and developed by Firas Baklouti.

![alt text](assets\img\screenshot.png)

About

This Car Rental System is a comprehensive web application that provides an easy-to-use platform for both customers and administrators to manage car rentals efficiently. With a clean and intuitive interface, it simplifies the process of car booking and rental management.

Features

User Features:

User Registration and Login

Browse Available Cars

Book Cars

View Booking History

Upload and Manage User Documents (CIN, Driving License/Permis, etc.)

Access Booking Documents (Rental Contracts and other useful files)

Update Profile

Multi-Language Support (switch between languages seamlessly)

Contact Form

Admin Features:

Secure Admin Login

Dashboard with Statistics

Manage Cars (Add, Edit, Delete, Upload Multiple Images)

Manage Bookings

Manage Booking Documents (Contracts, Reports)

User Management (including user document verification)

Enquiries Management

Maintenance & Paperwork Tracking (Insurance, Vignette, Technical Check, Oil Change)

SEO-friendly URLs via .htaccess

Screenshots
User Interface

Home Page: Browse available cars and featured vehicles

Car Listings: View detailed car information and pricing

Booking System: Easy-to-use booking interface

User Profile: Manage personal information, documents, and booking history

Multi-Language Toggle: Switch website language with one click

Admin Interface

Dashboard: Overview of bookings, users, deadlines, and revenue

Car Management: Add, edit, and manage car inventory with multiple images

Booking Management: Track and manage all bookings and related documents

User Management: Manage accounts, permissions, and user documents

SEO Optimization: Clean URLs using .htaccess

Requirements

PHP 7.4 or higher

MySQL 5.7 or higher

Apache Web Server (with .htaccess enabled for SEO)

XAMPP/WAMP/LAMP stack

Installation

Clone the repository to your web server directory:

git clone https://github.com/Firasbaklouti1/carrentalphp.git


Create a new MySQL database named 'carrentalp'

Import the database:

Open phpMyAdmin

Select the 'carrentalp' database

Go to Import tab

Select the file 'DATABASE FILE/new_carrentalp.sql'

Click 'Go' to import the database structure and initial data

Configure database connection:

Open includes/config.php

Update the database credentials if needed:

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carrentalp";


Access the system:

User Interface: http://localhost/carrentalphp

Admin Interface: http://localhost/carrentalphp/admin

Default Admin Credentials

Username: admin

Password: admin123

Directory Structure
carrentalphp/
├── admin/              # Admin panel files
├── assets/             # CSS, JS, and image files
├── DATABASE FILE/      # Database setup file
├── includes/           # PHP includes (config, functions)
├── uploads/            # Car image uploads
├── user_docs/          # User uploaded documents (CIN, permis, etc.)
├── booking_docs/       # Booking documents (contracts, invoices)
└── .htaccess           # SEO-friendly URL configuration

Tech Stack

Frontend: HTML5, CSS3, JavaScript, Bootstrap

Backend: PHP 7.4+

Database: MySQL 5.7+

Server: Apache with .htaccess enabled

Additional Libraries:

Font Awesome (Icons)

jQuery (JavaScript Framework)

Bootstrap (UI Framework)

Security Features

Password Hashing using PHP's password_hash()

SQL Injection Prevention

XSS Protection

CSRF Protection

Input Validation and Sanitization

Secure Session Management

File Upload Validation for User and Booking Documents

Support

If you encounter any issues or need assistance, please:

Check the Issues
 page

Create a new issue if your problem isn't already listed

Provide detailed information about your problem

Contributing

We welcome contributions! Please see our Contributing Guidelines
 for details.

Author

Firas Baklouti

Project Creator and Lead Developer

GitHub: @Firasbaklouti1

License

This project is open source and available under the MIT License
.

Acknowledgments

Special thanks to everyone who has contributed to making this project better!
