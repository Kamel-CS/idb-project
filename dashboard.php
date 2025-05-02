<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle project creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_project') {
        $name = trim($_POST['project_name']);
        $description = trim($_POST['project_description']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO projects (user_id, name, description) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $name, $description]);
        } catch(PDOException $e) {
            $error = "Failed to create project";
        }
    }
}

// Fetch user's projects with task counts
try {
    // Get projects with task counts
    $stmt = $pdo->prepare("
        SELECT p.*, 
               c.name as category_name,
               c.color as category_color,
               COUNT(t.id) as total_tasks,
               SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM projects p
        LEFT JOIN tasks t ON p.id = t.project_id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $projects = $stmt->fetchAll();

    // Get all tasks from all projects
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as project_name, c.name as category_name, c.color as category_color
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $all_tasks = $stmt->fetchAll();

    // Calculate total completed and pending tasks across all projects
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE p.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $task_stats = $stmt->fetch();
    
    $completed_tasks_count = $task_stats['completed_tasks'] ?? 0;
    $pending_tasks_count = $task_stats['pending_tasks'] ?? 0;
} catch(PDOException $e) {
    $error = "Failed to fetch projects";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Task Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-dark: #1a1a1a;
            --bg-darker: #121212;
            --bg-card: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --accent-primary: #6c5ce7;
            --accent-secondary: #a29bfe;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --border-color: #404040;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
        }

        /* Slim Sidebar */
        .sidebar {
            background-color: var(--bg-darker);
            padding: 20px;
            border-radius: 12px;
            height: calc(100vh - 40px);
            position: sticky;
            top: 20px;
            display: flex;
            flex-direction: column;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--accent-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-info h3 {
            font-size: 1rem;
            color: var(--text-primary);
        }

        .user-info small {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        /* Navigation */
        .navigation {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .nav-link.active {
            background-color: var(--accent-primary);
            color: white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            background-color: var(--bg-darker);
            padding: 20px;
            border-radius: 12px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .content-header h2 {
            color: var(--text-primary);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--accent-secondary);
        }

        /* Project List */
        .project-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .project-item {
            background-color: var(--bg-card);
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .project-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .project-title {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .project-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .project-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .progress-bar {
            height: 4px;
            background-color: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress {
            height: 100%;
            background-color: var(--success);
            transition: width 0.3s ease;
        }

        .project-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            color: white;
        }

        .view-btn {
            background-color: var(--accent-primary);
        }

        .edit-btn {
            background-color: var(--warning);
        }

        .delete-btn {
            background-color: var(--danger);
        }

        /* Quick Task Form */
        .quick-task-form {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .quick-task-form input {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .quick-task-form input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .quick-task-form button {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .quick-task-form button:hover {
            background-color: var(--accent-secondary);
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100vh;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .sidebar.active {
                left: 0;
            }

            .project-list {
                grid-template-columns: 1fr;
            }
        }

        /* Add these styles to your existing CSS */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: var(--accent-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
        }

        .quick-task-form {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .quick-task-form form {
            display: flex;
            gap: 10px;
        }

        .quick-task-form input {
            flex: 1;
            padding: 12px;
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .quick-task-form input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .quick-task-form button {
            padding: 12px 20px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-task-form button:hover {
            background-color: var(--accent-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                        <small>Task Manager</small>
                    </div>
                </div>

                <div class="navigation">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="tasks.php" class="nav-link">
                        <i class="fas fa-tasks"></i> My Tasks
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-calendar"></i> Calendar
                    </a>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="main-content">
                <div class="content-header">
                    <h2>Dashboard</h2>
                    <a href="create_project.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Project
                    </a>
                </div>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Projects</h3>
                            <p><?php echo count($projects); ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Completed Tasks</h3>
                            <p><?php echo $task_stats['completed_tasks'] ?? 0; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Pending Tasks</h3>
                            <p><?php echo $task_stats['pending_tasks'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="project-list">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-item">
                            <div class="project-header">
                                <h3 class="project-title"><?php echo htmlspecialchars($project['name']); ?></h3>
                                <?php if ($project['category_name']): ?>
                                    <span class="category-badge" style="background-color: <?php echo $project['category_color']; ?>">
                                        <?php echo htmlspecialchars($project['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $project['status']; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                            
                            <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                            
                            <div class="project-stats">
                                <span class="stat">
                                    <i class="fas fa-tasks"></i>
                                    <?php echo $project['total_tasks']; ?> Tasks
                                </span>
                                <span class="stat">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo $project['completed_tasks']; ?> Completed
                                </span>
                                <?php if ($project['total_tasks'] > 0): ?>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo ($project['completed_tasks'] / $project['total_tasks']) * 100; ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="project-actions">
                                <a href="project.php?id=<?php echo $project['id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_project.php?id=<?php echo $project['id']; ?>" 
                                   class="action-btn delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this project?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Project Modal -->
    <div id="newProjectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); z-index: 1000;">
        <div style="position: relative; background-color: var(--bg-card); margin: 10% auto; padding: 20px; width: 80%; max-width: 500px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Create New Project</h2>
                <button onclick="document.getElementById('newProjectModal').style.display='none'" style="background: none; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_project">
                <div style="margin-bottom: 15px;">
                    <input type="text" name="project_name" placeholder="Project Name" required style="width: 100%; padding: 10px; background-color: var(--bg-darker); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-primary);">
                </div>
                <div style="margin-bottom: 20px;">
                    <textarea name="project_description" placeholder="Project Description" rows="3" style="width: 100%; padding: 10px; background-color: var(--bg-darker); border: 1px solid var(--border-color); border-radius: 6px; color: var(--text-primary);"></textarea>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" style="flex: 1; padding: 10px; background-color: var(--accent-primary); color: white; border: none; border-radius: 6px; cursor: pointer;">Create Project</button>
                    <button type="button" onclick="document.getElementById('newProjectModal').style.display='none'" style="flex: 1; padding: 10px; background-color: var(--border-color); color: white; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>
</html> 