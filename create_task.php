<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $task_name = $_POST['task_name'] ?? '';
    $task_description = $_POST['task_description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ? date('Y-m-d', strtotime($_POST['due_date'])) : null;

    if (empty($task_name)) {
        $error = 'Task name is required';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (project_id, name, description, priority, due_date, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([$project_id, $task_name, $task_description, $priority, $due_date]);
            
            $success = 'Task created successfully';
            header("Location: project.php?id=" . $project_id);
            exit();
        } catch (PDOException $e) {
            $error = 'Error creating task: ' . $e->getMessage();
        }
    }
}

// Get projects for the current user
try {
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM projects 
        WHERE user_id = ? 
        ORDER BY name ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching projects: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - Task Manager</title>
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
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--bg-card);
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: var(--accent-primary);
        }

        .card {
            background-color: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card h1 {
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .error {
            color: var(--danger);
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(214, 48, 49, 0.1);
            border-radius: 6px;
        }

        .success {
            color: var(--success);
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(0, 184, 148, 0.1);
            border-radius: 6px;
        }

        button[type="submit"] {
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

        button[type="submit"]:hover {
            background-color: var(--accent-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <h1>Create New Task</h1>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="project_id">Project</label>
                    <select name="project_id" id="project_id" required>
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="task_name">Task Name</label>
                    <input type="text" id="task_name" name="task_name" required>
                </div>

                <div class="form-group">
                    <label for="task_description">Description</label>
                    <textarea id="task_description" name="task_description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <input type="date" id="due_date" name="due_date">
                </div>

                <button type="submit">
                    <i class="fas fa-plus"></i> Create Task
                </button>
            </form>
        </div>
    </div>
</body>
</html> 