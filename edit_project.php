<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$project_id = $_GET['id'];

// Verify project belongs to user
try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Failed to fetch project";
}

// Handle project update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['project_name']);
    $description = trim($_POST['project_description']);
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $description, $status, $project_id, $_SESSION['user_id']]);
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update project";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - Task Manager</title>
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

        .delete-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--danger);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <h1>Edit Project</h1>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Project Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <button type="submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>

            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this project? This action cannot be undone.');">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="delete-btn">
                    <i class="fas fa-trash"></i> Delete Project
                </button>
            </form>
        </div>
    </div>
</body>
</html> 