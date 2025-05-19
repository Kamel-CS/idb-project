# Task Manager

A modern task management system with project categorization, task tracking, and a beautiful dark theme interface.

## Features

- User authentication (login/register)
- Project management with categories
- Task creation and tracking
- Task filtering by status
- Dark theme interface
- Responsive design

## Installation

### Prerequisites

- XAMPP Server (Apache + MySQL + PHP)
- Git (optional, for cloning)

### Step 1: Get the Project Files

You have two options:

#### Option 1: Clone the repository
```bash
git clone https://github.com/yourusername/task-manager.git
```

#### Option 2: Download ZIP
1. Click the "Code" button on GitHub
2. Select "Download ZIP"
3. Extract the downloaded file

### Step 2: Place the Project Files

#### For Windows Users:
1. Open XAMPP Control Panel
2. Click on "Explorer" button
3. Navigate to `C:\xampp\htdocs\`
4. Create a new folder named `task-manager`
5. Copy all project files into this folder

#### For Linux Users:
1. Open terminal
2. Navigate to XAMPP's htdocs directory:
   ```bash
   cd /opt/lampp/htdocs/
   ```
3. Create a new directory:
   ```bash
   sudo mkdir task-manager
   ```
4. Copy project files:
   ```bash
   sudo cp -r /path/to/downloaded/files/* task-manager/
   ```
5. Set proper permissions:
   ```bash
   sudo chmod -R 755 task-manager/
   sudo chown -R daemon:daemon task-manager/
   ```

### Step 3: Start XAMPP Services

#### Windows:
1. Open XAMPP Control Panel
2. Start Apache and MySQL services
3. Click "Start" buttons for both services

#### Linux:
1. Open terminal
2. Start XAMPP services:
   ```bash
   sudo /opt/lampp/lampp start
   ```

### Step 4: Create Database

1. Open your web browser
2. Go to `http://localhost/phpmyadmin`
3. Click "Import" tab
4. Click "Choose File" and select the `database.sql` file from the project
5. Click "Go" to import the database structure

### Step 5: Access the Application

1. Open your web browser
2. Navigate to:
   ```
   http://localhost/task-manager
   ```
3. Sign up for a new account and start using the application
