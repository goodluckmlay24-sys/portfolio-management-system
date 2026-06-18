# Digital Portfolio Management System

Degree Program: Multimedia Technology and Animation
Group Number: Group 2
Course: Open Source Technologies - CP 222
Deadline: 18th June 2026


Project Overview

This is a web-based portfolio management system built for Multimedia Technology and Animation students. The system allows students to store, display, and search artwork and project details in a professional online portfolio format.


Features

1. Store Artwork/Project Details
   - Add new portfolio entries with title, category, description, and tags
   - Upload artwork images
   - Categorize projects

2. Display Portfolio Entries
   - Grid view of all portfolio entries
   - View details of each entry
   - Filter entries by "My Entries"

3. Search Portfolio Items
   - Search by title, category, or description
   - Filter by tags

4. Additional Features
   - User authentication (Login/Register)
   - Analytics dashboard
   - Comments system
   - Share links
   - Export portfolio


Technologies Used

- Backend: PHP 8.x with PDO
- Database: MySQL 5.7+
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Icons: Font Awesome 6
- Version Control: Git and GitHub


Installation Steps

Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache/Nginx)
- Git

Step 1: Clone the Repository
git clone https://github.com/goodluckmlay24-sys/portfolio-management-system.git

Step 2: Move to Web Server Root
# For XAMPP
C:\xampp\htdocs\portfolio-management-system\

# For WAMP
C:\wamp64\www\portfolio-management-system\

Step 3: Create Uploads Folder
cd portfolio-management-system
mkdir uploads

Step 4: Create Database
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" and create database: complete_db
3. Click "Import" tab
4. Select file: complete_db.sql from the project folder
5. Click "Go"

Step 5: Configure Database
Open config/database.php and update:
private $host = 'localhost';
private $dbname = 'complete_db';
private $username = 'root';
private $password = '';

Step 6: Access the Application
Open browser and go to:
http://localhost/portfolio-management-system/login.php

Default Login Credentials

Username    Password    Role
admin       admin123    Administrator
student     student123  Student
paul        paulo123    User


Git Commands Used

Repository Setup
git init
git add .
git commit -m "Initial commit: Project setup and structure"
git remote add origin https://github.com/goodluckmlay24-sys/portfolio-management-system.git
git branch -M main
git push -u origin main

Commits with Meaningful Messages
git commit -m "Initial commit: Project setup and structure"
git commit -m "Added user authentication and login system"
git commit -m "Implemented portfolio CRUD operations"
git commit -m "Added search functionality and My Entries filter"
git commit -m "Added analytics dashboard and reporting"
git commit -m "Added export feature"
git commit -m "Finalized UI/UX and documentation"

Branch Management
git checkout -b development
git push -u origin development
git add .
git commit -m "Added new feature in development branch"
git checkout main
git merge development
git push origin main

Other Useful Commands
git status
git branch -a
git log --oneline --graph --all
git pull origin main
git diff


Project Structure

portfolio-management-system/
|
|-- config/
|   |-- database.php
|
|-- uploads/
|
|-- index.php
|-- login.php
|-- log_out.php
|-- add_entry.php
|-- edit_entry.php
|-- delete_entry.php
|-- analytics.php
|-- install.php
|
|-- complete_db.sql
|-- style.css
|-- script.js
|
|-- .gitignore
|-- .htaccess
|-- README.md
|-- LICENSE


GitHub Repository Link

Repository URL:
https://github.com/goodluckmlay24-sys/portfolio-management-system

Clone Command:
git clone https://github.com/goodluckmlay24-sys/portfolio-management-system.git


Group Members

Number  Name                Registration Number    Role
1       Goodluck Mlay       T24-03-23274           Developer
2       Paul Jumanne        T24-                   Developer
3       Isaya Didas         T24-03-23185           Documentation


Git Commit History

git log --oneline --graph --all

Example output:
f8a2d3f (HEAD -> main, origin/main) Finalized UI/UX and documentation
4e7b1c9 (origin/development) Added export feature in development branch
c9b4e7a Added analytics dashboard and reporting
b2d5f8c Added search functionality and My Entries filter
a1e4f7b Implemented portfolio CRUD operations
d6c8e9a Added user authentication and login system
e3f7a1d Initial commit: Project setup and structure


Challenges Encountered

1. Database Connection Issues
Problem: Could not connect to MySQL database
Solution: Used PDO with proper error handling and prepared statements

2. Image Upload Problems
Problem: Images were not uploading correctly
Solution: Created proper uploads directory with correct permissions and added file validation

3. Git Merge Conflicts
Problem: Conflicts when merging development branch
Solution: Resolved conflicts manually and used proper merge strategies

4. Session Management
Problem: Sessions were not persisting correctly
Solution: Implemented proper session_start() and session handling


Conclusion

This project successfully demonstrates a complete Digital Portfolio Management System for Multimedia Technology and Animation students. The system meets all requirements:

- User management module (login/register)
- Store artwork/project details
- Display portfolio entries
- Search portfolio items
- Git version control with 5+ commits
- Development branch creation and merge
- Complete documentation in README

The project showcases skills in:
- PHP development with MySQL database
- Frontend development with HTML/CSS/JavaScript
- Version control with Git and GitHub
- Team collaboration and project management


License

This project is licensed under the MIT License - see the LICENSE file for details.


Repository: https://github.com/goodluckmlay24-sys/portfolio-management-system

Completed for: Open Source Technologies - CP 222
