<?php
require_once 'config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Get all tasks from all projects
try {
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

  // Calculate task statistics
  $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks
        FROM tasks t
        JOIN projects p ON t.project_id = p.id
        WHERE p.user_id = ?
    ");
  $stmt->execute([$_SESSION['user_id']]);
  $task_stats = $stmt->fetch();
} catch (PDOException $e) {
  $error = "Failed to fetch tasks";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Tasks - Task Manager</title>
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
      font-size: 1.5rem;
    }

    /* Stats Container */
    .stats-container {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
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

    /* Task Filters */
    .task-filters {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
    }

    .filter-btn {
      padding: 8px 16px;
      border: none;
      border-radius: 20px;
      background-color: var(--bg-card);
      color: var(--text-secondary);
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .filter-btn:hover {
      background-color: var(--accent-primary);
      color: white;
    }

    .filter-btn.active {
      background-color: var(--accent-primary);
      color: white;
    }

    /* Task List */
    .task-list {
      display: grid;
      gap: 15px;
    }

    .task-item {
      background-color: var(--bg-card);
      padding: 20px;
      border-radius: 8px;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .task-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .task-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .task-title {
      font-size: 1.1rem;
      color: var(--text-primary);
    }

    .task-description {
      color: var(--text-secondary);
      margin-bottom: 15px;
      font-size: 0.9rem;
    }

    .task-meta {
      display: flex;
      gap: 15px;
      color: var(--text-secondary);
      font-size: 0.9rem;
      margin-bottom: 15px;
      align-items: center;
    }

    .task-actions {
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

    /* Status and Priority Badges */
    .status-badge,
    .priority-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .status-pending {
      background-color: var(--warning);
      color: #000;
    }

    .status-in_progress {
      background-color: var(--accent-primary);
      color: white;
    }

    .status-completed {
      background-color: var(--success);
      color: white;
    }

    .priority-high {
      background-color: var(--danger);
      color: white;
    }

    .priority-medium {
      background-color: var(--warning);
      color: #000;
    }

    .priority-low {
      background-color: var(--success);
      color: white;
    }

    .project-badge {
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      color: white;
    }

    /* Error Message */
    .error {
      background-color: var(--danger);
      color: white;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
    }

    @media (max-width: 1024px) {
      .stats-container {
        grid-template-columns: repeat(2, 1fr);
      }
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

      .stats-container {
        grid-template-columns: 1fr;
      }

      .task-filters {
        flex-wrap: wrap;
      }

      .task-actions {
        flex-direction: column;
      }

      .action-btn {
        width: 100%;
      }
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
          <a href="dashboard.php" class="nav-link">
            <i class="fas fa-home"></i> Dashboard
          </a>
          <a href="tasks.php" class="nav-link active">
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
          <h2>My Tasks</h2>
        </div>

        <div class="stats-container">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-tasks"></i>
            </div>
            <div class="stat-info">
              <h3>Total Tasks</h3>
              <p><?php echo $task_stats['total_tasks'] ?? 0; ?></p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <h3>Completed</h3>
              <p><?php echo $task_stats['completed_tasks'] ?? 0; ?></p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
              <h3>In Progress</h3>
              <p><?php echo $task_stats['in_progress_tasks'] ?? 0; ?></p>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-info">
              <h3>Pending</h3>
              <p><?php echo $task_stats['pending_tasks'] ?? 0; ?></p>
            </div>
          </div>
        </div>

        <div class="task-filters">
          <button class="filter-btn active" data-filter="all">All Tasks</button>
          <button class="filter-btn" data-filter="pending">Pending</button>
          <button class="filter-btn" data-filter="in_progress">In Progress</button>
          <button class="filter-btn" data-filter="completed">Completed</button>
        </div>

        <?php if (isset($error)): ?>
          <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="task-list">
          <?php foreach ($all_tasks as $task): ?>
            <div class="task-item" data-status="<?php echo $task['status']; ?>">
              <div class="task-header">
                <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                <span class="status-badge status-<?php echo $task['status']; ?>">
                  <?php echo ucfirst($task['status']); ?>
                </span>
              </div>

              <p class="task-description"><?php echo htmlspecialchars($task['description']); ?></p>

              <div class="task-meta">
                <span class="project-badge" style="background-color: <?php echo $task['category_color']; ?>">
                  <?php echo htmlspecialchars($task['project_name']); ?>
                </span>
                <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                  <?php echo ucfirst($task['priority']); ?> Priority
                </span>
                <?php if ($task['due_date']): ?>
                  <span>
                    <i class="fas fa-calendar"></i>
                    Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                  </span>
                <?php endif; ?>
              </div>

              <div class="task-actions">
                <a href="project.php?id=<?php echo $task['project_id']; ?>" class="action-btn view-btn">
                  <i class="fas fa-eye"></i> View Project
                </a>
                <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="action-btn edit-btn">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <a href="delete_task.php?id=<?php echo $task['id']; ?>"
                  class="action-btn delete-btn"
                  onclick="return confirm('Are you sure you want to delete this task?')">
                  <i class="fas fa-trash"></i> Delete
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Task filtering
      const filterBtns = document.querySelectorAll('.filter-btn');
      const taskItems = document.querySelectorAll('.task-item');

      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          const filter = btn.getAttribute('data-filter');

          // Update active filter button
          filterBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');

          // Filter tasks
          taskItems.forEach(task => {
            if (filter === 'all' || task.getAttribute('data-status') === filter) {
              task.style.display = '';
            } else {
              task.style.display = 'none';
            }
          });
        });
      });
    });
  </script>
</body>

</html>
