# 🎓 Project: Student Idea Management System (Unildeas Portal)

**Course:** COMP1640  
**Developed by:** Team KH  

Dear Lecturers, below is a detailed guide to help you easily access, experience the features, and assess our team's project.

---

## 🌐 1. Live Website Experience (Live Demo)

For the utmost convenience during grading—without the need for complex environment setups—our team has deployed the entire system (Frontend & API Backend) on a live server. You can access it directly via the following link:

👉 **[https://mmideas.infinityfree.me/login.html](https://mmideas.infinityfree.me/login.html)**

*(A quick note: Since we are utilising a free educational hosting server, the initial load or file uploads might occasionally experience a slight delay of 3-5 seconds. We kindly appreciate your patience!).*

---

## 🔑 2. Test Accounts

We have pre-configured accounts for each specific role to facilitate your testing of the Role-Based Access Control (RBAC). 
*(Tip: You can use the 👁️ icon in the password field to view the password as you type).*

| Role | Key Features | Login Email | Password |
| :--- | :--- | :--- | :--- |
| **Admin** | Account management, view statistics | `admin@univ.edu` | `123456` |
| **Staff** | Write posts, submit ideas, upload files | `staffit@univ.edu` | `123456` |
| **QA Coordinator**| Manage categories, download CSV files | `qac@univ.edu` | `123456` |
| **QA Manager** | Review ideas, view Dashboard | `qam@univ.edu` | `123456` |

---

## 🚀 3. Core Feature Testing Flow

To comprehensively evaluate the system, we recommend following this testing flow:

1. **Submission Test (Staff):** Log in with the Staff account -> Write a new idea -> Attach an image/document -> Click Submit. (The system is configured with appropriate directory permissions for secure file storage).
2. **Interaction Test:** Other Staff members can view ideas, drop a Like (Thumbs up), or add a Comment.
3. **Management Test (QA Coordinator):** Log in with the QAC account -> Download all ideas or attached files in `.zip` or `.csv` formats.
4. **Statistics Test (QA Manager):** Log in with the QAM account -> Access the Dashboard to view interactive statistical charts.

---

## 💻 4. Local Setup Guide (Fallback Option)

In the event of InfinityFree server maintenance or network issues, you can run the source code directly on your local machine using standard Laravel procedures:

**System Requirements:** PHP >= 8.1, Composer, MySQL/XAMPP.

**Step 1: Install Backend Dependencies**

Open Terminal at the project's root directory and execute:

composer install

**Step 2: Environment Configuration**

Copy the .env.example file to .env. Then, open XAMPP, create a new database named nhomkh, and update the .env file with the following information:
   DB_CONNECTION=mysql

   DB_HOST=127.0.0.1

   DB_PORT=3306

   DB_DATABASE=nhomkh

   DB_USERNAME=root

   DB_PASSWORD=

**Step 3: Database Initialization**

Run the following commands to generate the application key, link the storage directory for images, and populate the database with sample data:
   php artisan key:generate

   php artisan storage:link

   php artisan migrate --seed


**Step 4: Start the Server**
   
   php artisan serve


**Afterwards, please open your browser and navigate to: http://127.0.0.1:8000/login.html to experience the Local system.**
**Team KH sincerely thanks you for taking the time to review and assess our project!**