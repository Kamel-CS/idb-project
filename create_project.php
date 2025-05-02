<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Fetch categories
try {
    $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error fetching categories: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = $_POST['category_id'] ?? null;

    if (empty($name)) {
        $error = 'Project name is required';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (user_id, name, description, category_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$_SESSION['user_id'], $name, $description, $category_id]);
            
            $success = 'Project created successfully';
            header("Location: dashboard.php");
            exit();
        } catch (PDOException $e) {
            $error = 'Error creating project: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Project - Task Manager</title>
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
            color: var(--text-primary);
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
            background-color: var(--danger);
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background-color: var(--success);
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: var(--accent-secondary);
        }

        .category-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="card">
            <h1>Create New Project</h1>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Project Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" style="color: <?php echo $category['color']; ?>">
                                <div class="category-option">
                                    <span class="category-color" style="background-color: <?php echo $category['color']; ?>"></span>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </div>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">
                    <i class="fas fa-plus"></i> Create Project
                </button>
            </form>
        </div>
    </div>
</body>
</html> 