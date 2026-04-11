# WeatherHub System

##  Overview
WeatherHub is a PHP and MySQL web application developed for IT223 (Web System and Technologies).  
It features user registration, login authentication, session management, and a structured folder architecture.  
The system is designed to be scalable and prepared for future API integrations such as weather services.

---

##  Features
- User registration with input validation  
- Secure login with password hashing  
- Session-based authentication  
- Protected dashboard (authorized access only)  
- Weather search module (scaffold)  
- Saved locations  
- Search history tracking  
- User preferences  
- Original custom UI design  

---

##  Folder Structure
weatherhub/
├── app/
│ ├── controllers/
│ ├── models/
│ ├── middleware/
│ ├── helpers/
│ └── config/
├── public/
│ ├── assets/
│ └── *.php
├── resources/
│ └── views/
├── database/
│ └── weatherhub.sql

##  How to Run

1. Copy the `weatherhub` folder into your XAMPP `htdocs` directory.
2. Start **Apache** and **MySQL** using XAMPP.
3. Open phpMyAdmin in your browser:

http://localhost/phpmyadmin

4. Create a database named:

- weatherhub

5. Import the file:

- database/weatherhub.sql

6. If needed, configure database connection in:

- app/config/database.php

7. Open the system in your browser:

- http://localhost/weatherhub/public/

or (if using port 8080):

- http://localhost:8080/weatherhub/public/


---

##  Authentication
The system uses **session-based authentication** to restrict access to protected pages.  
Only logged-in users can access the dashboard and system features.

---

##  Database Design
The database is **normalized** and uses **foreign keys** to maintain data integrity.

Main tables:
- users  
- saved_locations  
- search_history  
- user_preferences  

This structure ensures scalability and supports future system enhancements.

---

##  Use Case Roles

###  Guest
- Register  
- Login  

###  User
- Login  
- Logout  
- View Dashboard  
- Search Weather  
- Save Location  
- View Search History  
- Manage Preferences  

###  Admin (Future Role)
- Create User  
- Read User  
- Update User  
- Delete User  

---

##  Notes
- The weather module currently uses sample output.  
- Future implementation can integrate real-time weather APIs.  
- The system is designed with scalability in mind.

---

## 👨‍💻 Developer
King Josh Robles & Jhon Owen Morales  
BS Information Technology
IT223 – Web System and Technologies  

---
