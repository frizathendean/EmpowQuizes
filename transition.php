<?php
session_start();
if (!isset($_SESSION['last_quiz_id'])) {
    header("Location: subjects.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Memuat Hasil...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background: linear-gradient(to bottom, #fdd835, #fffde7);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .loading-text {
            font-size: 1.8em;
            color: #0d47a1;
            margin-bottom: 20px;
            animation: fadeIn 1s ease-in-out;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 6px solid #f2e88f;
            border-top: 6px solid #0d47a1;
            border-radius: 50%;
            animation: spin 1.2s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="loading-box">
        <div class="loading-text">Menghitung skor kamu...</div>
        <div class="spinner"></div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "results.php";
        }, 2000);
    </script>
</body>
</html>
