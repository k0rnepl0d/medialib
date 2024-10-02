<?php

// Папка для загрузки файлов
$uploadDir = "upload";

function getFiles($dir) {
    $files = [];
    $items = array_diff(scandir($dir), array(".", ".."));
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getFiles($path));
        } else {
            $files[] = $path;
        }
    }
    return $files;
}

// Получаем список файлов
$files = getFiles($uploadDir);

// Обработка загрузки файлов
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_FILES["file"])) {
        $file = $_FILES["file"];
        $targetFile = $uploadDir . basename($file["name"]);

        // Проверка типа файла и размера
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "jpeg", "png", "gif", "pdf");
        if (!in_array($fileType, $allowedTypes)) {
            echo "Недопустимый тип файла.";
        } elseif ($file["size"] > 5000000) { // Ограничение размера файла 5MB
            echo "Файл слишком большой.";
        } else {
            if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                echo "<script>window.location.href = window.location.href;</script>";
            } else {
                echo "Ошибка при загрузке файла.";
            }
        }
    } elseif (isset($_POST["file_url"])) {
        $fileUrl = $_POST["file_url"];
        $fileName = microtime(true) . ".png";
        $targetFile = $uploadDir . $fileName;

        $pathInfo = pathinfo($targetFile);
        if (!isset($pathInfo["extension"])) {
            $targetFile .= ".png";
        }

        // Используем cURL для скачивания файла
        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $fileContent = curl_exec($ch);

        if ($fileContent === false) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode == 200) {
                if (file_put_contents($targetFile, $fileContent)) {
                    echo "<script>window.location.href = window.location.href;</script>";
                } else {
                    echo "Ошибка при сохранении файла.";
                }
            } else {
                echo "HTTP Error: $httpCode";
            }
        }

        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Медиатека</title>
    <style>
        .media-container {
            display: flex;
            flex-wrap: wrap;
        }
        .media-item {
            margin: 10px;
            flex: 1 1 calc(25% - 20px); /* 4 элемента в строке */
            box-sizing: border-box;
        }
        .media-item img, .media-item video {
            max-width: 100%;
            height: auto;
            cursor: pointer;
        }
        form {
            margin: 5px;
        }
        .control_panel {
            align-items: center;
            text-align: center; /* Если нужно центрировать текст */
        }
    </style>
</head>
<body>
<div class="control_panel">
<h1>Медиатека</h1>
<form action="index.php" method="post" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Загрузить</button>
</form>
<form action="index.php" method="post">
    <label>
        <input type="url" name="file_url" placeholder="Введите URL файла" required>
    </label>
    <button type="submit">Загрузить по ссылке</button>
</form>
<div class="media-container" id="media-container">
    <?php foreach ($files as $file): ?>
        <div class="media-item">
            <?php if (preg_match("/\.(jpg|jpeg|png|gif)$/i", $file)): ?>
                <img src="<?=$file ?>" alt="<?= $file ?>" onclick="openInNewWindow('<?=$file ?>')">
            <?php elseif (preg_match("/\.(mp4|webm|ogg|mkv)$/i", $file)): ?>
                <video controls onclick="openInNewWindow('<?=$file ?>')">
                    <source src="<?=$file ?>" type="video/<?= pathinfo($file, PATHINFO_EXTENSION) ?>">
                </video>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<script>
    function openInNewWindow(url) {
        window.open(url, "_blank");
    }
</script>
</div>
</body>
</html>
