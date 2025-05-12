<?php
/**
 * PHP File Manager v1.0
 *
 * A comprehensive, modern file manager with Bootstrap styling and all essential features:
 * - File Tree Navigation
 * - Search functionality
 * - Create/Edit/Delete files and folders
 * - Upload/Download files
 * - Copy/Move operations
 * - Compression (zip, tar, gzip)
 * - Extraction (zip, tar, gzip)
 * - Deletion confirmation
 * - Rename operations
 * - Permission management
 * - Drag and drop support
 * - Multi-select operations
 * - Sorting and filtering
 */

date_default_timezone_set("UTC");

$username = ""; // Username for directory listing
$root_path = ""; // Path to the root directory

$config = [
    "root_path" => $root_path,
    "max_upload_size" => 1024 * 1024 * 1024,
    "allowed_extensions" => ["*"],
    "date_format" => "Y-m-d H:i:s",
    "theme" => "dark",
];

function securityCheck($path)
{
    global $config;
    $realPath = realpath($path);
    if (!$realPath) {
        return false;
    }
    return strpos($realPath, $config["root_path"]) === 0;
}

function formatSize($bytes)
{
    $units = ["b", "kb", "mb", "gb", "tb"];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . " " . $units[$i];
}

function getFileIcon($file)
{
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    $iconMap = [
        "pdf" => "bi-file-earmark-pdf",
        "doc" => "bi-file-earmark-word",
        "docx" => "bi-file-earmark-word",
        "xls" => "bi-file-earmark-excel",
        "xlsx" => "bi-file-earmark-excel",
        "ppt" => "bi-file-earmark-ppt",
        "pptx" => "bi-file-earmark-ppt",
        "jpg" => "bi-file-earmark-image",
        "jpeg" => "bi-file-earmark-image",
        "png" => "bi-file-earmark-image",
        "gif" => "bi-file-earmark-image",
        "txt" => "bi-file-earmark-text",
        "zip" => "bi-file-earmark-zip",
        "tar" => "bi-file-earmark-zip",
        "gz" => "bi-file-earmark-zip",
        "html" => "bi-file-earmark-code",
        "htm" => "bi-file-earmark-code",
        "css" => "bi-file-earmark-code",
        "js" => "bi-file-earmark-code",
        "php" => "bi-file-earmark-code",
        "py" => "bi-file-earmark-code",
        "java" => "bi-file-earmark-code",
        "c" => "bi-file-earmark-code",
        "cpp" => "bi-file-earmark-code",
        "mp3" => "bi-file-earmark-music",
        "mp4" => "bi-file-earmark-play",
        "mov" => "bi-file-earmark-play",
        "avi" => "bi-file-earmark-play",
    ];

    if (is_dir($file)) {
        return "bi-folder";
    } elseif (isset($iconMap[$extension])) {
        return $iconMap[$extension];
    } else {
        return "bi-file-earmark";
    }
}

function getPermissions($file)
{
    $perms = fileperms($file);

    if (($perms & 0xc000) == 0xc000) {
        $info = "s";
    } elseif (($perms & 0xa000) == 0xa000) {
        $info = "l";
    } elseif (($perms & 0x8000) == 0x8000) {
        $info = "-";
    } elseif (($perms & 0x6000) == 0x6000) {
        $info = "b";
    } elseif (($perms & 0x4000) == 0x4000) {
        $info = "d";
    } elseif (($perms & 0x2000) == 0x2000) {
        $info = "c";
    } elseif (($perms & 0x1000) == 0x1000) {
        $info = "p";
    } else {
        $info = "u";
    }

    $info .= $perms & 0x0100 ? "r" : "-";
    $info .= $perms & 0x0080 ? "w" : "-";
    $info .=
        $perms & 0x0040
        ? ($perms & 0x0800
            ? "s"
            : "x")
        : ($perms & 0x0800
            ? "S"
            : "-");

    $info .= $perms & 0x0020 ? "r" : "-";
    $info .= $perms & 0x0010 ? "w" : "-";
    $info .=
        $perms & 0x0008
        ? ($perms & 0x0400
            ? "s"
            : "x")
        : ($perms & 0x0400
            ? "S"
            : "-");

    $info .= $perms & 0x0004 ? "r" : "-";
    $info .= $perms & 0x0002 ? "w" : "-";
    $info .=
        $perms & 0x0001
        ? ($perms & 0x0200
            ? "t"
            : "x")
        : ($perms & 0x0200
            ? "T"
            : "-");

    return $info;
}

function buildDirectoryTree($dir, $relativePath = "")
{
    global $config;

    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;

            $relPathPrefix = $relativePath
                ? ltrim($relativePath, "/") . "/"
                : "";
            $relPath = $relPathPrefix . $value;

            if (is_dir($fullPath)) {
                if (securityCheck($fullPath)) {
                    $result[] = [
                        "name" => $value,
                        "type" => "dir",
                        "path" => "/" . ltrim($relPath, "/"),
                        "children" => buildDirectoryTree($fullPath, $relPath),
                    ];
                }
            }
        }
    }

    return $result;
}

function getDirectoryContents($dir, $sort = "name", $order = "asc")
{
    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;

            $fileInfo = [
                "name" => $value,
                "type" => is_dir($fullPath) ? "dir" : "file",
                "size" => is_dir($fullPath)
                    ? ""
                    : formatSize(filesize($fullPath)),
                "size_raw" => is_dir($fullPath) ? 0 : filesize($fullPath),
                "extension" => is_dir($fullPath)
                    ? ""
                    : pathinfo($value, PATHINFO_EXTENSION),
                "date_added" => date(
                    $GLOBALS["config"]["date_format"],
                    filectime($fullPath)
                ),
                "last_modified" => date(
                    $GLOBALS["config"]["date_format"],
                    filemtime($fullPath)
                ),
                "permissions" => getPermissions($fullPath),
                "icon" => getFileIcon($fullPath),
            ];

            $result[] = $fileInfo;
        }
    }

    usort($result, function ($a, $b) use ($sort, $order) {
        if ($a["type"] != $b["type"]) {
            return $a["type"] == "dir" ? -1 : 1;
        }

        $valA = $a[$sort];
        $valB = $b[$sort];

        if ($sort == "size") {
            $valA = $a["size_raw"];
            $valB = $b["size_raw"];
        }

        if ($order == "asc") {
            return $valA <=> $valB;
        } else {
            return $valB <=> $valA;
        }
    });

    return $result;
}

function createDirectory($path, $name)
{
    $dirPath = $path . DIRECTORY_SEPARATOR . $name;

    if (!file_exists($dirPath)) {
        if (mkdir($dirPath, 0755)) {
            return [
                "status" => "success",
                "message" => "Directory created successfully",
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to create directory",
            ];
        }
    } else {
        return ["status" => "error", "message" => "Directory already exists"];
    }
}

function createFile($path, $name, $content = "")
{
    $filePath = $path . DIRECTORY_SEPARATOR . $name;

    if (!file_exists($filePath)) {
        if (file_put_contents($filePath, $content) !== false) {
            return [
                "status" => "success",
                "message" => "File created successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to create file"];
        }
    } else {
        return ["status" => "error", "message" => "File already exists"];
    }
}

function renameItem($path, $oldName, $newName)
{
    $oldPath = $path . DIRECTORY_SEPARATOR . $oldName;
    $newPath = $path . DIRECTORY_SEPARATOR . $newName;

    if (file_exists($oldPath) && !file_exists($newPath)) {
        if (rename($oldPath, $newPath)) {
            return [
                "status" => "success",
                "message" => "Item renamed successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to rename item"];
        }
    } else {
        return [
            "status" => "error",
            "message" => "Source does not exist or destination already exists",
        ];
    }
}

function deleteItem($path)
{
    if (is_dir($path)) {
        $files = array_diff(scandir($path), [".", ".."]);

        foreach ($files as $file) {
            deleteItem($path . DIRECTORY_SEPARATOR . $file);
        }

        if (rmdir($path)) {
            return [
                "status" => "success",
                "message" => "Directory deleted successfully",
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to delete directory",
            ];
        }
    } else {
        if (unlink($path)) {
            return [
                "status" => "success",
                "message" => "File deleted successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to delete file"];
        }
    }
}

function copyItem($source, $destination)
{
    if (is_dir($source)) {
        if (!file_exists($destination)) {
            mkdir($destination, 0755);
        }

        $files = array_diff(scandir($source), [".", ".."]);

        foreach ($files as $file) {
            copyItem(
                $source . DIRECTORY_SEPARATOR . $file,
                $destination . DIRECTORY_SEPARATOR . $file
            );
        }

        return [
            "status" => "success",
            "message" => "Directory copied successfully",
        ];
    } else {
        if (copy($source, $destination)) {
            return [
                "status" => "success",
                "message" => "File copied successfully",
            ];
        } else {
            return ["status" => "error", "message" => "Failed to copy file"];
        }
    }
}

function changePermissions($path, $mode)
{
    if (chmod($path, octdec($mode))) {
        return [
            "status" => "success",
            "message" => "Permissions changed successfully",
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to change permissions",
        ];
    }
}

function compressItems($items, $destination, $type = "zip")
{
    switch ($type) {
        case "zip":
            $zip = new ZipArchive();

            if ($zip->open($destination, ZipArchive::CREATE) === true) {
                foreach ($items as $item) {
                    if (is_dir($item)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($item),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );

                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr(
                                    $filePath,
                                    strlen(dirname($item)) + 1
                                );

                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                    } else {
                        $zip->addFile($item, basename($item));
                    }
                }

                $zip->close();
                return [
                    "status" => "success",
                    "message" => "Files compressed successfully (ZIP)",
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "Failed to create ZIP archive",
                ];
            }
            break;

        case "tar":
            $phar = new PharData($destination);

            foreach ($items as $item) {
                $phar->addFile($item, basename($item));
            }

            return [
                "status" => "success",
                "message" => "Files compressed successfully (TAR)",
            ];
            break;

        case "gzip":
            if (count($items) > 1) {
                return [
                    "status" => "error",
                    "message" => "GZip can only compress one file at a time",
                ];
            }

            $content = file_get_contents($items[0]);
            $gzContent = gzencode($content, 9);
            file_put_contents($destination, $gzContent);

            return [
                "status" => "success",
                "message" => "File compressed successfully (GZip)",
            ];
            break;

        default:
            return [
                "status" => "error",
                "message" => "Unknown compression type",
            ];
    }
}

function isEditable($file)
{
    $editableExtensions = [
        "txt",
        "html",
        "htm",
        "css",
        "js",
        "php",
        "xml",
        "json",
        "md",
        "log",
        "config",
        "ini",
        "yml",
        "yaml",
        "sql",
        "sh",
        "sql",
        "Dockerfile",
        ".gitignore",
        ".gitkeep",
        ".htaccess",
        ".htpasswd",
        ".htaccess.dist",
        ".env",
        ".env.example",
        ".env.local",
        ".env.test",
        ".env.development",
        ".env.staging",
        ".env",
        ".ini",
        ".conf",
    ];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    return in_array($extension, $editableExtensions);
}

function moveToTrash($source, $currentPath, $config)
{
    $trashDir = $config["root_path"] . "/.trash";
    if (!file_exists($trashDir)) {
        mkdir($trashDir, 0755, true);
    }

    $itemName = basename($source);

    $destinationPath = $trashDir . "/" . $itemName;
    $counter = 1;

    $originalName = pathinfo($itemName, PATHINFO_FILENAME);
    $extension = pathinfo($itemName, PATHINFO_EXTENSION);

    while (file_exists($destinationPath)) {
        if ($extension) {
            $destinationPath =
                $trashDir .
                "/" .
                $originalName .
                " (" .
                $counter .
                ")." .
                $extension;
        } else {
            $destinationPath =
                $trashDir . "/" . $originalName . " (" . $counter . ")";
        }
        $counter++;
    }

    if (rename($source, $destinationPath)) {
        $metaFilename = $destinationPath . ".trashinfo";
        $metadata = [
            "original_path" =>
            str_replace($config["root_path"], "", $currentPath) .
                "/" .
                $itemName,
            "deletion_date" => date("Y-m-d H:i:s"),
            "original_name" => $itemName,
        ];

        file_put_contents(
            $metaFilename,
            json_encode($metadata, JSON_PRETTY_PRINT)
        );

        return true;
    }

    return false;
}

function restoreFromTrash($source, $config)
{
    $metaFilename = $source . ".trashinfo";
    if (!file_exists($metaFilename)) {
        return ["status" => "error", "message" => "Trash metadata not found"];
    }

    $metadata = json_decode(file_get_contents($metaFilename), true);
    if (!$metadata || !isset($metadata["original_path"])) {
        return ["status" => "error", "message" => "Invalid trash metadata"];
    }

    $destPath = $config["root_path"] . $metadata["original_path"];
    $destDir = dirname($destPath);

    if (!file_exists($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return [
                "status" => "error",
                "message" => "Failed to create destination directory",
            ];
        }
    }

    if (file_exists($destPath)) {
        $originalName = pathinfo($destPath, PATHINFO_FILENAME);
        $extension = pathinfo($destPath, PATHINFO_EXTENSION);
        $counter = 1;

        $newDestPath = $destPath;
        while (file_exists($newDestPath)) {
            if ($extension) {
                $newDestPath =
                    $destDir .
                    "/" .
                    $originalName .
                    " (restored " .
                    $counter .
                    ")." .
                    $extension;
            } else {
                $newDestPath =
                    $destDir .
                    "/" .
                    $originalName .
                    " (restored " .
                    $counter .
                    ")";
            }
            $counter++;
        }

        $destPath = $newDestPath;
    }

    if (rename($source, $destPath)) {
        if (file_exists($metaFilename)) {
            unlink($metaFilename);
        }
        return [
            "status" => "success",
            "message" => "Item restored successfully",
        ];
    }

    return ["status" => "error", "message" => "Failed to restore item"];
}

function extractArchive($source, $destination, $config)
{
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return [
                "status" => "error",
                "message" => "Failed to create destination directory",
            ];
        }
    }

    $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

    switch ($extension) {
        case "zip":
            return extractZip($source, $destination);

        case "tar":
            return extractTar($source, $destination);

        case "gz":
        case "gzip":
            return extractGzip($source, $destination);

        case "bz2":
        case "bzip2":
            return extractBzip2($source, $destination);

        case "rar":
            return extractRar($source, $destination);

        case "7z":
            return extract7Zip($source, $destination);

        default:
            return [
                "status" => "error",
                "message" => "Unsupported archive type: " . $extension,
            ];
    }
}

function extractZip($source, $destination)
{
    $zip = new ZipArchive();
    $res = $zip->open($source);

    if ($res === true) {
        $zip->extractTo($destination);
        $zip->close();
        return [
            "status" => "success",
            "message" => "ZIP archive extracted successfully",
        ];
    } else {
        return [
            "status" => "error",
            "message" =>
            "Failed to open ZIP archive (Error code: " . $res . ")",
        ];
    }
}

function extractTar($source, $destination)
{
    try {
        $phar = new PharData($source);
        $phar->extractTo($destination, null, true);
        return [
            "status" => "success",
            "message" => "TAR archive extracted successfully",
        ];
    } catch (Exception $e) {
        return [
            "status" => "error",
            "message" => "Failed to extract TAR archive: " . $e->getMessage(),
        ];
    }
}

function extractGzip($source, $destination)
{
    $basename = basename($source, ".gz");
    if (substr($basename, -4) === ".tar") {
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return [
                "status" => "success",
                "message" => "TAR.GZ archive extracted successfully",
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract TAR.GZ archive: " . $e->getMessage(),
            ];
        }
    } else {
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;

        $sfp = gzopen($source, "rb");
        $fp = fopen($destFile, "wb");

        if (!$sfp || !$fp) {
            return [
                "status" => "error",
                "message" => "Failed to open files for extraction",
            ];
        }

        while (!gzeof($sfp)) {
            fwrite($fp, gzread($sfp, 4096));
        }

        gzclose($sfp);
        fclose($fp);

        return [
            "status" => "success",
            "message" => "GZIP file extracted successfully",
        ];
    }
}

function extractBzip2($source, $destination)
{
    $basename = basename($source, ".bz2");
    if (substr($basename, -4) === ".tar") {
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return [
                "status" => "success",
                "message" => "TAR.BZ2 archive extracted successfully",
            ];
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract TAR.BZ2 archive: " . $e->getMessage(),
            ];
        }
    } else {
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;

        $sfp = bzopen($source, "r");
        $fp = fopen($destFile, "w");

        if (!$sfp || !$fp) {
            return [
                "status" => "error",
                "message" => "Failed to open files for extraction",
            ];
        }

        while (!feof($sfp)) {
            fwrite($fp, bzread($sfp, 4096));
        }

        bzclose($sfp);
        fclose($fp);

        return [
            "status" => "success",
            "message" => "BZIP2 file extracted successfully",
        ];
    }
}

function extractRar($source, $destination)
{
    if (class_exists("RarArchive")) {
        $rar = RarArchive::open($source);
        if ($rar === false) {
            return [
                "status" => "error",
                "message" => "Failed to open RAR archive",
            ];
        }

        $entries = $rar->getEntries();
        foreach ($entries as $entry) {
            $entry->extract($destination);
        }

        $rar->close();
        return [
            "status" => "success",
            "message" => "RAR archive extracted successfully",
        ];
    } elseif (function_exists("exec")) {
        $command =
            "unrar x -o+ " .
            escapeshellarg($source) .
            " " .
            escapeshellarg($destination);
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return [
                "status" => "success",
                "message" =>
                "RAR archive extracted successfully using unrar command",
            ];
        } else {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract RAR archive using unrar command",
            ];
        }
    } else {
        return [
            "status" => "error",
            "message" =>
            "RAR extraction not supported - PHP RAR extension or exec() function required",
        ];
    }
}

function extract7Zip($source, $destination)
{
    if (function_exists("exec")) {
        $command =
            "7z x " .
            escapeshellarg($source) .
            " -o" .
            escapeshellarg($destination) .
            " -y";
        $output = [];
        $returnVar = 0;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return [
                "status" => "success",
                "message" => "7Zip archive extracted successfully",
            ];
        } else {
            return [
                "status" => "error",
                "message" =>
                "Failed to extract 7Zip archive: " . implode("\n", $output),
            ];
        }
    } else {
        return [
            "status" => "error",
            "message" =>
            "7Zip extraction not supported - exec() function required",
        ];
    }
}

if (isset($_POST["action"]) || isset($_GET["action"])) {
    $action = isset($_POST["action"]) ? $_POST["action"] : $_GET["action"];

    $response = ["status" => "error", "message" => "Unknown action"];

    $currentPath = isset($_POST["path"])
        ? $config["root_path"] . $_POST["path"]
        : $config["root_path"];
    if (isset($_GET["path"])) {
        $currentPath = $config["root_path"] . $_GET["path"];
    }

    if (!securityCheck($currentPath) && $action != "search") {
        $response = ["status" => "error", "message" => "Security violation"];
    } else {
        switch ($action) {
            case "list":
                $sort = isset($_POST["sort"])
                    ? $_POST["sort"]
                    : (isset($_GET["sort"])
                        ? $_GET["sort"]
                        : "name");
                $order = isset($_POST["order"])
                    ? $_POST["order"]
                    : (isset($_GET["order"])
                        ? $_GET["order"]
                        : "asc");
                $response = [
                    "status" => "success",
                    "data" => getDirectoryContents($currentPath, $sort, $order),
                    "current_path" => str_replace(
                        $config["root_path"],
                        "",
                        $currentPath
                    ),
                ];
                break;

            case "tree":
                $response = [
                    "status" => "success",
                    "data" => buildDirectoryTree($config["root_path"]),
                ];
                break;

            case "create_dir":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $response = createDirectory($currentPath, $name);
                break;

            case "create_file":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";
                $response = createFile($currentPath, $name, $content);
                break;

            case "rename":
                $oldName = isset($_POST["old_name"]) ? $_POST["old_name"] : "";
                $newName = isset($_POST["new_name"]) ? $_POST["new_name"] : "";
                $response = renameItem($currentPath, $oldName, $newName);
                break;

            case "trash":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $status = true;
                $messages = [];
                $movedCount = 0;
                $failedCount = 0;

                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                    if (moveToTrash($itemPath, $currentPath, $config)) {
                        $movedCount++;
                        $messages[] = "$item moved to trash";
                    } else {
                        $failedCount++;
                        $status = false;
                        $messages[] = "Failed to move $item to trash";
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" =>
                    "Moved $movedCount item(s) to trash" .
                        ($failedCount > 0
                            ? ", Failed $failedCount item(s)"
                            : ""),
                ];
                break;

            case "delete":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $permanent = isset($_POST["permanent"])
                    ? $_POST["permanent"] === "true"
                    : false;
                $status = true;
                $messages = [];

                if (!$permanent && $action === "delete") {
                    $formData = new FormData();
                    $formData . append("action", "trash");
                    $formData . append("path", $currentPath);

                    foreach ($items as $item) {
                        $formData . append("items[]", $item);
                    }
                } else {
                    foreach ($items as $item) {
                        $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                        $result = deleteItem($itemPath);

                        if ($result["status"] != "success") {
                            $status = false;
                        }

                        $messages[] = $result["message"];
                    }

                    $response = [
                        "status" => $status ? "success" : "error",
                        "message" => implode(", ", $messages),
                    ];
                }
                break;

            case "restore":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $status = true;
                $messages = [];
                $restoredCount = 0;
                $failedCount = 0;

                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                    $result = restoreFromTrash($itemPath, $config);

                    if ($result["status"] === "success") {
                        $restoredCount++;
                        $messages[] = "$item restored successfully";
                    } else {
                        $failedCount++;
                        $status = false;
                        $messages[] =
                            "Failed to restore $item: " . $result["message"];
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" =>
                    "Restored $restoredCount item(s)" .
                        ($failedCount > 0
                            ? ", Failed $failedCount item(s)"
                            : ""),
                ];
                break;

            case "copy":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $destination = isset($_POST["destination"])
                    ? $config["root_path"] . $_POST["destination"]
                    : "";

                if (!securityCheck($destination)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation on destination",
                    ];
                    break;
                }

                $status = true;
                $messages = [];

                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;

                    $result = copyItem($sourcePath, $destPath);

                    if ($result["status"] != "success") {
                        $status = false;
                    }

                    $messages[] = $result["message"];
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" => implode(", ", $messages),
                ];
                break;

            case "move":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $destination = isset($_POST["destination"])
                    ? $config["root_path"] . $_POST["destination"]
                    : "";

                if (!securityCheck($destination)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation on destination",
                    ];
                    break;
                }

                $status = true;
                $messages = [];

                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;

                    if (!file_exists($destPath)) {
                        if (rename($sourcePath, $destPath)) {
                            $messages[] = "Moved $item successfully";
                        } else {
                            $status = false;
                            $messages[] = "Failed to move $item";
                        }
                    } else {
                        $status = false;
                        $messages[] = "$item already exists in destination";
                    }
                }

                $response = [
                    "status" => $status ? "success" : "error",
                    "message" => implode(", ", $messages),
                ];
                break;

            case "permissions":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $mode = isset($_POST["mode"]) ? $_POST["mode"] : "0644";

                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                $response = changePermissions($itemPath, $mode);
                break;

            case "compress":
                $items = isset($_POST["items"]) ? $_POST["items"] : [];
                $type = isset($_POST["type"]) ? $_POST["type"] : "zip";
                $name = isset($_POST["name"]) ? $_POST["name"] : "archive";

                $itemPaths = [];
                foreach ($items as $item) {
                    $itemPaths[] = $currentPath . DIRECTORY_SEPARATOR . $item;
                }

                $extension = "";
                switch ($type) {
                    case "zip":
                        $extension = ".zip";
                        break;
                    case "tar":
                        $extension = ".tar";
                        break;
                    case "gzip":
                        $extension = ".gz";
                        break;
                }

                $destination =
                    $currentPath . DIRECTORY_SEPARATOR . $name . $extension;
                $response = compressItems($itemPaths, $destination, $type);
                break;

            case "read_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                if (file_exists($itemPath) && is_file($itemPath)) {
                    if (isEditable($itemPath)) {
                        $content = file_get_contents($itemPath);
                        $response = [
                            "status" => "success",
                            "data" => [
                                "content" => $content,
                                "editable" => true,
                            ],
                        ];
                    } else {
                        $response = [
                            "status" => "success",
                            "data" => [
                                "editable" => false,
                                "message" => "This file type is not editable",
                            ],
                        ];
                    }
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;

            case "save_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";

                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;

                if (file_exists($itemPath) && is_file($itemPath)) {
                    if (isEditable($itemPath)) {
                        if (file_put_contents($itemPath, $content) !== false) {
                            $response = [
                                "status" => "success",
                                "message" => "File saved successfully",
                            ];
                        } else {
                            $response = [
                                "status" => "error",
                                "message" => "Failed to save file",
                            ];
                        }
                    } else {
                        $response = [
                            "status" => "error",
                            "message" => "This file type is not editable",
                        ];
                    }
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;

            case "search":
                $query = isset($_POST["query"]) ? $_POST["query"] : "";
                $path = isset($_POST["path"])
                    ? $config["root_path"] . $_POST["path"]
                    : $config["root_path"];

                if (!securityCheck($path)) {
                    $response = [
                        "status" => "error",
                        "message" => "Security violation",
                    ];
                    break;
                }

                $results = [];

                if (!empty($query)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $path,
                            RecursiveDirectoryIterator::SKIP_DOTS
                        ),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $file) {
                        if (stripos($file->getFilename(), $query) !== false) {
                            $relativePath = str_replace(
                                $config["root_path"],
                                "",
                                $file->getPathname()
                            );

                            $results[] = [
                                "name" => $file->getFilename(),
                                "path" => $relativePath,
                                "type" => $file->isDir() ? "dir" : "file",
                                "size" => $file->isDir()
                                    ? ""
                                    : formatSize($file->getSize()),
                                "last_modified" => date(
                                    $config["date_format"],
                                    $file->getMTime()
                                ),
                                "icon" => getFileIcon($file->getPathname()),
                            ];
                        }
                    }
                }

                $response = ["status" => "success", "data" => $results];
                break;

            case "extract":
                $file = isset($_POST["file"]) ? $_POST["file"] : "";
                $destination = isset($_POST["destination"])
                    ? $_POST["destination"]
                    : "";

                if (empty($file)) {
                    $response = [
                        "status" => "error",
                        "message" => "No file specified for extraction",
                    ];
                    break;
                }

                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;

                if (empty($destination)) {
                    $fileInfo = pathinfo($file);
                    $destination =
                        $currentPath .
                        DIRECTORY_SEPARATOR .
                        $fileInfo["filename"];
                } else {
                    $destination = $config["root_path"] . $destination;
                }

                if (!file_exists($destination)) {
                    if (!mkdir($destination, 0755, true)) {
                        $response = [
                            "status" => "error",
                            "message" =>
                            "Failed to create destination directory",
                        ];
                        break;
                    }
                }

                $result = extractArchive($filePath, $destination, $config);
                $response = $result;
                break;

            case "download":
                $file = isset($_GET["file"]) ? $_GET["file"] : "";
                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;

                if (file_exists($filePath) && is_file($filePath)) {
                    header("Content-Description: File Transfer");
                    header("Content-Type: application/octet-stream");
                    header(
                        'Content-Disposition: attachment; filename="' .
                            basename($filePath) .
                            '"'
                    );
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: public");
                    header("Content-Length: " . filesize($filePath));
                    readfile($filePath);
                    exit();
                } else {
                    $response = [
                        "status" => "error",
                        "message" => "File not found",
                    ];
                }
                break;
        }
    }

    if ($action != "download") {
        header("Content-Type: application/json");
        echo json_encode($response);
        exit();
    }
}

if (isset($_FILES["files"])) {
    header("Content-Type: application/json");
    $currentPath = isset($_POST["path"])
        ? $config["root_path"] . $_POST["path"]
        : $config["root_path"];

    if (!securityCheck($currentPath)) {
        echo json_encode([
            "status" => "error",
            "message" => "Security violation",
        ]);
        exit();
    }

    $files = $_FILES["files"];
    $uploaded = 0;
    $failed = 0;
    $failedFiles = [];

    for ($i = 0; $i < count($files["name"]); $i++) {
        $fileName = $files["name"][$i];
        $filePath = $currentPath . DIRECTORY_SEPARATOR . $fileName;

        if ($files["error"][$i] !== UPLOAD_ERR_OK) {
            $failed++;
            $errorMsg = "";
            switch ($files["error"][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg =
                        "File exceeds upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg =
                        "File exceeds MAX_FILE_SIZE directive specified in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = "File was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMsg = "File upload stopped by extension";
                    break;
                default:
                    $errorMsg = "Unknown upload error";
            }
            $failedFiles[] = $fileName . " (" . $errorMsg . ")";
            continue;
        }

        if ($files["size"][$i] > $config["max_upload_size"]) {
            $failed++;
            $failedFiles[] = $fileName . " (Exceeds maximum file size limit)";
            continue;
        }

        if ($config["allowed_extensions"][0] !== "*") {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($extension, $config["allowed_extensions"])) {
                $failed++;
                $failedFiles[] = $fileName . " (File type not allowed)";
                continue;
            }
        }

        if (move_uploaded_file($files["tmp_name"][$i], $filePath)) {
            $uploaded++;
        } else {
            $failed++;
            $failedFiles[] = $fileName . " (Server error during move)";
        }
    }

    $response = [
        "status" => $failed == 0 ? "success" : "partial",
        "message" =>
        "Uploaded $uploaded file(s)" .
            ($failed > 0 ? ", Failed $failed file(s)" : ""),
    ];

    if (!empty($failedFiles)) {
        $response["failedFiles"] = $failedFiles;
    }

    echo json_encode($response);
    exit();
}

$lines = file(__FILE__);
foreach ($lines as $line) {
    if (strpos($line, "* The Kinsmen") !== false) {
        $versionInfo = trim(str_replace("*", "", $line));
        break;
    }
}

$version = explode(" ", $versionInfo);
$version = end($version);

if ($username == null) {
    echo "Loading...";
}
?>
<?php if ($username != null) { ?>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>File Manager - <?= $version ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="icon" href="icon.png" type="image/png">
        <style>
            :root {
                --bs-primary: #202654;
                --bs-primary-dark: #2980b9;
                --sidebar-width: 300px;
            }


            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                font-size: 14px;
                overflow-x: hidden;
            }


            h2,
            h3,
            h4,
            h5 {
                font-size: 16px;
            }

            #sidebar {
                width: var(--sidebar-width);
                height: 100vh;
                position: fixed;
                left: 0;
                top: 0;
                z-index: 1000;
                overflow-y: auto;
                transition: all 0.3s;
                background: #100237;
                border-right: 1px solid #dee2e6;
            }

            #content {
                margin-left: var(--sidebar-width);
                padding: 20px;
                transition: all 0.3s;
            }



            .file-tree {
                padding-left: 15px;
                list-style-type: none;
            }

            .file-tree li {
                margin: 5px 0;
            }

            .tree-toggle {
                cursor: pointer;
            }

            .files-container {
                min-height: 300px;
                padding: 15px;
            }

            .file-item {
                border: 1px solid transparent;
                padding: 8px;
                border-radius: 4px;
                cursor: pointer;
                color: #000;
            }

            .file-item:hover {
                background-color: #f8f9fa;
                border-color: #dee2e6;
            }

            .file-item.selected {
                background-color: rgba(13, 110, 253, 0.5);
                border-color: #086bfc;
            }

            .item-icon {
                font-size: 14px;
                margin-right: 5px;
            }

            .breadcrumb-item a {
                text-decoration: none;
                color: var(--bs-primary);
                font-weight: 600;
            }

            .action-bar {
                margin-bottom: 0px;
            }

            .progress {
                margin-top: 10px;
                display: none;
            }

            .drag-over {
                background-color: rgba(13, 110, 253, 0.1);
                border: 2px dashed #086bfc !important;
            }

            .modal-header {
                background-color: #f8f9fa;
            }

            /* Context menu */
            .context-menu {
                position: absolute;
                z-index: 1000;
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                min-width: 180px;
                display: none;
            }

            .context-menu-item {
                padding: 0.5rem 1rem;
                cursor: pointer;
            }

            .context-menu-item:hover {
                background-color: #f8f9fa;
            }

            .context-menu-divider {
                border-top: 1px solid #dee2e6;
                margin: 0.25rem 0;
            }

            /* Spinner */
            .spinner-border {
                width: 1rem;
                height: 1rem;
                border-width: 0.15em;
            }

            /* Add these CSS styles for better progress bar */
            .progress {
                margin-top: 10px;
                margin-bottom: 15px;
                height: 20px;
                display: none;
            }

            .progress-bar {
                transition: width 0.3s ease;
                text-align: center;
                line-height: 20px;
                font-size: 12px;
                font-weight: bold;
                color: white;
                overflow: visible;
                text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
                white-space: nowrap;
            }

            .filename {
                color: #03051e;
                font-size: 14px
            }



            /* Dark mode adjustments */
            [data-theme="dark"] .progress-bar {
                color: #fff;
                text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.5);
            }

            /* Dark Mode */
            [data-theme="dark"] {
                --bs-body-bg: #03051e;
                --bs-body-color: #f8f9fa;
            }

            [data-theme="dark"] #sidebar {
                background: #100237;
                border-right-color: #f48513;
            }

            [data-theme="dark"] .filename {
                color: #ced4df;
                font-size: 14px
            }

            [data-theme="dark"] .file-item:hover {
                background-color: #f48513;
                border-color: #f48513;
            }

            [data-theme="dark"] .file-item.selected {
                background-color: rgba(244, 133, 19, 0.5);
                border-color: #f48513;
            }

            [data-theme="dark"] .modal-content {
                background-color: #03051e;
                color: #f8f9fa;
                border-color: #03051e;
            }


            [data-theme="dark"] .context-menu {
                background-color: #100237;
                color: #f8f9fa;
                border-color: #100237;
            }

            [data-theme="dark"] .modal-header {
                background-color: #100237;
                border-bottom-color: #f48513;
            }

            [data-theme="dark"] .modal-footer {
                border-top-color: #100237;
            }

            [data-theme="dark"] .context-menu-item:hover {
                background-color: #495057;
            }

            [data-theme="dark"] .context-menu-divider {
                border-top-color: #495057;
            }

            [data-theme="dark"] table,
            [data-theme="dark"] th,
            [data-theme="dark"] td {
                border-color: #495057 !important;
            }

            [data-theme="dark"] .table {
                color: #f8f9fa;
            }

            [data-theme="dark"] .table-striped>tbody>tr:nth-of-type(odd)>* {
                background-color: rgba(255, 255, 255, 0.05);
                color: #f8f9fa;
            }

            [data-theme="dark"] .dropdown-menu {
                background-color: #100237;
                border-color: #100237;
            }

            [data-theme="dark"] .dropdown-item {
                color: #f8f9fa;
            }

            [data-theme="dark"] .dropdown-item:hover,
            [data-theme="dark"] .dropdown-item:focus {
                background-color: #03051e;
                color: #f8f9fa;
            }

            [data-theme="dark"] .navigate-link {
                color: #f48513;
            }

            [data-theme="dark"] .breadcrumb-item {
                color: #a7b1c2;
                font-weight: 600;
            }

            a {
                text-decoration: none;
            }

            #directory-tree>li>i,
            .file-tree>li>i {
                color: #f48513;
            }

            .dir-link {
                color: #a7b1c2;
            }

            .form-control,
            .form-control:focus,
            .form-control:active {
                background-color: #dee2e6;
                color: #100237;
            }

            .btn-close {
                background-color: red;
            }

            .text-custom {
                color: #f48513;
            }


            @media (max-width: 768px) {
                #sidebar {
                    margin-left: -300px;
                    /* Match your --sidebar-width */
                    position: fixed;
                    top: 0;
                    left: 0;
                    height: 100vh;
                    z-index: 1050;
                    transition: all 0.3s;
                    box-shadow: none;
                }

                #sidebar.active {
                    margin-left: 0;
                    box-shadow: 3px 0 10px rgba(0, 0, 0, 0.5);
                }

                #content {
                    width: 100%;
                    margin-left: 0;
                    transition: all 0.3s;
                    padding: 10px;
                }

                /* Add class for overlay when sidebar is active */
                body.sidebar-active::before {
                    content: "";
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.7);
                    z-index: 1040;
                    opacity: 1;
                    transition: opacity 0.3s;
                }
            }

            #sidebarCollapse {
                z-index: 1031;
                position: relative;
                display: inline-block;
            }

            /* Make action buttons more responsive on mobile */
            @media (max-width: 576px) {
                .action-bar {
                    flex-direction: column;
                    align-items: stretch;
                    width: 100%;
                }

                .action-bar .btn-group {
                    width: 100%;
                    margin-right: 0 !important;
                    margin-bottom: 0.5rem;
                }

                .action-bar .btn {
                    flex: 1;
                }

                /* Hide some text on very small screens */
                .action-bar .btn i {
                    margin-right: 0.25rem;
                }

                /* Make buttons larger for touch targets */
                .btn-sm {
                    padding: 0.375rem 0.75rem;
                    font-size: 0.875rem;
                    line-height: 1.5;
                }

                /* Compact file table on mobile */
                .files-container th:nth-child(5),
                .files-container th:nth-child(6),
                .files-container td:nth-child(5),
                .files-container td:nth-child(6) {
                    display: none;
                }

                .files-container th:nth-child(3),
                .files-container td:nth-child(3) {
                    display: none;
                }

                /* Adjust icon size */
                .item-icon {
                    font-size: 14px;
                    margin-right: 5px;
                }

                /* Fix checkbox size */
                .form-check-input {
                    width: 18px;
                    height: 18px;
                }

                /* Ensure file names don't overflow */
                .file-item td:nth-child(2) {
                    max-width: 200px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
            }

            /* Medium screens adjustments */
            @media (min-width: 577px) and (max-width: 768px) {
                .action-bar {
                    flex-wrap: wrap;
                }

                .action-bar .btn-group {
                    margin-bottom: 0.5rem;
                }

                /* Hide less important columns */
                .files-container th:nth-child(6),
                .files-container td:nth-child(6) {
                    display: none;
                }
            }

            /* Make sorting dropdown full width on mobile */
            @media (max-width: 576px) {

                .dropdown,
                #sortDropdown {
                    width: 100%;
                    margin-top: 0.5rem;
                }

                .dropdown-menu {
                    width: 100%;
                }
            }

            .btn-custom {
                background-color: #f48513;
                color: #100237;
            }
        </style>
    </head>

    <body data-theme="dark">
        <div class="wrapper">
            <!-- Sidebar -->
            <nav id="sidebar">
                <div class=" justify-content-between align-items-center d-flex d-lg-none">
                    <button type="button" class="btn-close mt-2 ms-auto me-2" id="closeSidebar" aria-label="Close"></button>
                </div>

                <div class="position-sticky pt-3">
                    <div class="p-3 mb-3 border-bottom d-flex justify-content-center">
                        <div>
                            <img src="logo.png" width="200" />
                            <p class="text-center text-secondary mt-1" style="font-size: 12px;">
                                <?= $versionInfo ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="search-input" placeholder="Search files...">
                            <button class="btn btn-outline-info" type="button" id="search-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-1 py-2">
                        <ul class="file-tree">
                            <li> <a class="nav-link navigate-link text-custom" href="#" data-path="">
                                    <i class="bi bi-house-door me-2 text-custom"></i> <span style="color: #a7b1c2">/home/<?= $username ?></span>
                                </a>
                            </li>
                            <li>
                                <ul class="file-tree" id="directory-tree">
                                    <li class="text-white"><i class="bi bi-arrow-clockwise text-warning"></i> Loading...</li>
                                </ul>
                            </li>
                            <li id="show-trash"></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div id="content">


                <div class="files-container" id="dropzone">

                    <nav class="navbar navbar-expand-lg border border-secondary mb-3">
                        <div class="container-fluid">
                            <button type="button" id="sidebarCollapse" class="btn btn-outline-warning d-lg-none">
                                <i class="bi bi-list"></i>
                            </button>

                            <div class="ms-2 me-auto">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0 p-0" id="breadcrumb">
                                        <li class="breadcrumb-item"><a href="#" class="navigate-link" data-path=""><i class="bi bi-house-door"></i> </a></li>
                                    </ol>
                                </nav>
                            </div>

                            <div class="d-flex align-items-center">
                                <i class="bi bi-moon-fill me-2"></i>
                                <div class="form-check form-switch">
                                    <label class="form-check-label" for="darkModeSwitch">
                                        <input class="form-check-input" type="checkbox" checked id="darkModeSwitch">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <div class="progress mb-3" id="upload-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            role="progressbar"
                            style="width: 0%"
                            aria-valuenow="0"
                            aria-valuemin="0"
                            aria-valuemax="100">
                            Preparing upload...
                        </div>
                    </div>


                    <div class="d-flex justify-content-between align-items-center mb-3 border border-secondary p-1">

                        <div class="action-bar d-flex flex-wrap gap-2 action-buttons-mobile">


                            <button type="button" class="btn btn-sm btn-outline-info" id="new-folder-btn">
                                <i class="bi bi-folder-plus"></i> New Folder
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" id="new-file-btn">
                                <i class="bi bi-file-earmark-plus"></i> New File
                            </button>




                            <button type="button" class="btn btn-sm btn-outline-info" id="upload-btn">
                                <i class="bi bi-upload"></i> Upload
                                <input type="file" id="file-upload" multiple style="display: none;">
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="download-btn" disabled>
                                <i class="bi bi-download"></i> Download
                            </button>



                            <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="copy-btn" disabled>
                                <i class="bi bi-files"></i> Copy
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="cut-btn" disabled>
                                <i class="bi bi-scissors"></i> Move
                            </button>



                            <div class="btn-group" id="restore-btn-group" style="display: none;">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="restore-btn" disabled>
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="compress-btn" disabled>
                                    <i class="bi bi-file-zip"></i> Compress
                                </button>
                            </div>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="permissions-btn" disabled>
                                    <i class="bi bi-shield"></i> Permissions
                                </button>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary first-btns" id="delete-btn" disabled>
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>

                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-info" id="refresh-btn">
                                <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Reload</span>
                            </button>
                            <div class="dropdown ms-1">
                                <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-sort-alpha-down"></i> Sort
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item sort-item active" href="#" data-sort="name" data-order="asc">Name (A-Z)</a></li>
                                    <li><a class="dropdown-item sort-item" href="#" data-sort="name" data-order="desc">Name (Z-A)</a></li>
                                    <li><a class="dropdown-item sort-item" href="#" data-sort="size" data-order="asc">Size (Smallest first)</a></li>
                                    <li><a class="dropdown-item sort-item" href="#" data-sort="size" data-order="desc">Size (Largest first)</a></li>
                                    <li><a class="dropdown-item sort-item" href="#" data-sort="last_modified" data-order="desc">Date (Newest first)</a></li>
                                    <li><a class="dropdown-item sort-item" href="#" data-sort="last_modified" data-order="asc">Date (Oldest first)</a></li>
                                </ul>
                            </div>
                        </div>


                    </div>

                    <div id="files-view" class="table-responsive border border-secondary">
                        <table class="table table-hover" style="width: 100%;">
                            <thead>
                                <tr class="table-active">
                                    <th class="text-info" style="font-size:14px" width="3%"><input type="checkbox" class="form-check-input" id="select-all-checkbox"></th>
                                    <th class="text-info" style="font-size:14px" width="50%">Name</th>
                                    <th class="text-info" style="font-size:14px" width="10%">Size</th>
                                    <th class="text-info" style="font-size:14px" width="15%">Type</th>
                                    <th class="text-info" style="font-size:14px" width="15%">Last Modified</th>
                                    <th class="text-info" style="font-size:14px" width="17%">Permissions</th>
                                </tr>
                            </thead>
                            <tbody id="files-list">
                                <tr>
                                    <td colspan="6" class="text-center p-2">Loading files...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div>


        <!-- Context Menu -->
        <div class="context-menu" id="context-menu">
            <div class="context-menu-item" id="ctx-open"><i class="bi bi-folder2-open me-2"></i> Open</div>
            <div class="context-menu-item" id="ctx-download"><i class="bi bi-download me-2"></i> Download</div>
            <div class="context-menu-item" id="ctx-edit"><i class="bi bi-pencil-square me-2"></i> Edit</div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" id="ctx-copy"><i class="bi bi-files me-2"></i> Copy</div>
            <div class="context-menu-item" id="ctx-cut"><i class="bi bi-scissors me-2"></i> Move</div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" id="ctx-rename"><i class="bi bi-input-cursor-text me-2"></i> Rename</div>
            <div class="context-menu-item" id="ctx-permissions"><i class="bi bi-shield me-2"></i> Permissions</div>
            <div class="context-menu-item" id="ctx-compress"><i class="bi bi-file-zip me-2"></i> Compress</div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item text-danger" id="ctx-delete"><i class="bi bi-trash me-2"></i> Delete</div>
        </div>

        <!-- Modals -->
        <!-- Extract Modal -->
        <div class="modal fade" id="extractModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Extract Archive</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Archive: <span id="extractFileName" class="fw-bold"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="extractPath" class="form-label">Extract to</label>
                            <input type="text" class="form-control" id="extractPath" placeholder="Extraction path">
                            <small class="text-muted">Leave empty to extract to a folder with the same name as the archive</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            The archive will be extracted to the specified location. If the directory doesn't exist, it will be created.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="extractConfirmBtn">Extract</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Folder Modal -->
        <div class="modal fade" id="newFolderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Folder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folderName" class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folderName" placeholder="Enter folder name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="createFolderBtn">Create</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create File Modal -->
        <div class="modal fade" id="newFileModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fileName" class="form-label">File Name</label>
                            <input type="text" class="form-control" id="fileName" placeholder="Enter file name">
                        </div>
                        <div class="mb-3">
                            <label for="fileContent" class="form-label">Content</label>
                            <textarea class="form-control" id="fileContent" rows="10"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="createFileBtn">Create</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rename Modal -->
        <div class="modal fade" id="renameModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Rename Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newName" class="form-label">New Name</label>
                            <input type="text" class="form-control" id="newName">
                            <input type="hidden" id="oldName">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="renameBtn">Rename</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Modal -->
        <div class="modal fade" id="permissionsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Permissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item: <span id="permItem"></span></label>
                        </div>
                        <div class="mb-3">
                            <label for="permValue" class="form-label">Octal Permission Value</label>
                            <input type="text" class="form-control" id="permValue" placeholder="e.g. 0755">
                        </div>
                        <div class="row mb-3">
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerRead" data-value="400">
                                        <label class="form-check-label">Owner Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerWrite" data-value="200">
                                        <label class="form-check-label">Owner Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="ownerExec" data-value="100">
                                        <label class="form-check-label">Owner Execute</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupRead" data-value="40">
                                        <label class="form-check-label">Group Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupWrite" data-value="20">
                                        <label class="form-check-label">Group Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="groupExec" data-value="10">
                                        <label class="form-check-label">Group Execute</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2">
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicRead" data-value="4">
                                        <label class="form-check-label">Public Read</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicWrite" data-value="2">
                                        <label class="form-check-label">Public Write</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input perm-check" type="checkbox" id="publicExec" data-value="1">
                                        <label class="form-check-label">Public Execute</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="changePermBtn">Change Permissions</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compress Modal -->
        <div class="modal fade" id="compressModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Compress Items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="compressName" class="form-label">Archive Name</label>
                            <input type="text" class="form-control" id="compressName" placeholder="archive">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Compression Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="compressType" id="compressZip" value="zip" checked>
                                <label class="form-check-label" for="compressZip">
                                    ZIP (.zip)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="compressType" id="compressTar" value="tar">
                                <label class="form-check-label" for="compressTar">
                                    TAR (.tar)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="compressType" id="compressGzip" value="gzip">
                                <label class="form-check-label" for="compressGzip">
                                    GZip (.gz) - Single file only
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="compressBtn">Compress</button>
                        <button type="button" class="btn btn-success" id="compressAndDownloadBtn" style="display: none;">Compress & Download</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal with Trash Option -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the selected item(s)?</p>
                        <ul id="deleteItems" class="mb-3"></ul>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="permanentDeleteCheck">
                            <label class="form-check-label" for="permanentDeleteCheck">
                                Skip the trash and permanently delete the files
                            </label>
                        </div>

                        <div class="alert alert-info" id="deleteInfoAlert">
                            <i class="bi bi-info-circle me-2"></i>
                            Items will be moved to the trash folder and can be restored later.
                        </div>

                        <div class="alert alert-danger" id="permanentDeleteAlert" style="display: none;">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. Items will be permanently deleted.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <span id="deleteButtonText">Move to Trash</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Modal -->
        <div class="modal fade" id="alertModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="alertTitle">Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="alertMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Modal -->
        <div class="modal fade" id="searchModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Search Results</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="search-results-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Path</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Last Modified</th>
                                    </tr>
                                </thead>
                                <tbody id="search-results">
                                    <tr>
                                        <td colspan="5" class="text-center">Searching...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Operation Modal (Copy/Move) -->
        <div class="modal fade" id="fileOperationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileOpTitle">File Operation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="fileOpType" value="copy">
                        <div class="mb-3">
                            <label class="form-label">Selected Items:</label>
                            <ul id="fileOpItems" class="list-group mb-3">
                            </ul>
                        </div>
                        <div class="mb-3">
                            <label for="destinationPath" class="form-label">Destination Path</label>
                            <input type="text" class="form-control" id="destinationPath" placeholder="Enter destination path">
                            <small class="text-muted">Current path: <span id="currentPathDisplay"></span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="executeFileOpBtn">Execute</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Confirmation Modal - Make sure this exists in your HTML -->
        <div class="modal fade" id="restoreModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Restore Items</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to restore the following items to their original locations?</p>
                        <ul id="restoreItems" class="list-group mb-3"></ul>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Items will be moved back to their original locations if possible. If an item with the same name exists at the destination, the restored item will be renamed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirmRestoreBtn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
        <script>
            // Pass PHP variables to JavaScript
            var SERVER_ROOT_PATH = "<?php echo $config["root_path"]; ?>";
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Global variables
                let currentPath = '';
                let selectedItems = [];
                let fileList = [];
                let clipboardItems = [];
                let clipboardAction = '';
                let currentSort = 'name';
                let currentOrder = 'asc';
                let viewMode = 'list'; // grid or list
                let contextTarget = null;

                // Load file list
                function loadFileList() {
                    const filesList = document.getElementById('files-list');
                    filesList.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading files...</td></tr>';

                    // Reset selected items
                    selectedItems = [];
                    updateButtonStates();

                    // Make AJAX request to get file list
                    const formData = new FormData();
                    formData.append('action', 'list');
                    formData.append('path', currentPath);
                    formData.append('sort', currentSort);
                    formData.append('order', currentOrder);

                    fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                fileList = data.data;

                                // Update breadcrumb
                                updateBreadcrumb(data.current_path);

                                // Show files in the table
                                showFiles(fileList);

                                // Update current path if returned from server
                                if (data.current_path !== undefined) {
                                    currentPath = data.current_path;
                                }

                                // Check for .trash path and update restore button visibility
                                updateRestoreButtonVisibility();
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {
                            showAlert('Error', 'Failed to load file list');
                        });
                }

                // Update breadcrumb
                function updateBreadcrumb(path) {
                    const breadcrumb = document.getElementById('breadcrumb');
                    breadcrumb.innerHTML = '<li class="breadcrumb-item"><a href="#" class="navigate-link" data-path=""><i class="bi bi-house-door"></i></a></li>';

                    if (path) {
                        const parts = path.split('/').filter(part => part !== '');
                        let currentPath = '';

                        parts.forEach((part, index) => {
                            currentPath += '/' + part;

                            if (index === parts.length - 1) {
                                breadcrumb.innerHTML += `<li class="breadcrumb-item active">${part}</li>`;
                            } else {
                                breadcrumb.innerHTML += `<li class="breadcrumb-item"><a href="#" class="navigate-link" data-path="${currentPath}">${part}</a></li>`;
                            }
                        });
                    }
                }

                // Show files in table
                function showFiles(files) {
                    const filesList = document.getElementById('files-list');

                    if (files.length === 0) {
                        filesList.innerHTML = '<tr><td colspan="6" class="text-center p-3 text-danger">No files found</td></tr>';
                        return;
                    }

                    let html = '';

                    files.forEach(file => {
                        html += `
                    <tr class="file-item" data-name="${file.name}" data-type="${file.type}">
                        <td><input type="checkbox" class="form-check-input item-check"></td>
                        <td class="filename">
                        <i class="bi ${file.name === "public_html" ? "bi-globe2 text-info" : file.type === 'dir' ? file.icon + " text-custom" : file.icon + " text-primary"} item-icon"></i> ${file.name}
                        </td>
                        <td class="text-secondary" style="font-size:14px">${file.size}</td>
                        <td class="text-secondary" style="font-size:14px">${file.type === 'dir' ? 'Folder' : (file.extension ? file.extension.toLowerCase() : 'File')}</td>
                        <td class="text-secondary" style="font-size:14px">${file.last_modified}</td>
                        <td class="text-secondary" style="font-size:14px">${file.permissions}</td>
                    </tr>`;
                    });

                    filesList.innerHTML = html;

                    // Add event listeners to table rows
                    document.querySelectorAll('.file-item').forEach(item => {
                        // Double click to open
                        item.addEventListener('dblclick', function() {
                            const name = this.getAttribute('data-name');
                            const type = this.getAttribute('data-type');

                            if (type === 'dir') {
                                navigateTo(currentPath + '/' + name);
                            } else {
                                if (isEditable(name)) {
                                    openFileEditor(name);
                                } else {
                                    // Download the file
                                    window.location.href = `${window.location.href}?action=download&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(name)}`;
                                }
                            }
                        });

                        // Single click to select
                        item.addEventListener('click', function(e) {
                            if (e.target.type === 'checkbox') return;

                            const checkbox = this.querySelector('.item-check');
                            checkbox.checked = !checkbox.checked;

                            // Trigger change event
                            const event = new Event('change');
                            checkbox.dispatchEvent(event);
                        });

                        // Context menu
                        item.addEventListener('contextmenu', function(e) {
                            e.preventDefault();

                            const name = this.getAttribute('data-name');
                            const type = this.getAttribute('data-type');

                            // Store the context target
                            contextTarget = {
                                name: name,
                                type: type
                            };

                            // Select this item
                            const checkbox = this.querySelector('.item-check');
                            if (!checkbox.checked) {
                                checkbox.checked = true;
                                const event = new Event('change');
                                checkbox.dispatchEvent(event);
                            }

                            // Show context menu
                            showContextMenu(e.pageX, e.pageY, type);
                        });

                        // Checkbox change
                        const checkbox = item.querySelector('.item-check');
                        checkbox.addEventListener('change', function() {
                            const name = item.getAttribute('data-name');

                            if (this.checked) {
                                item.classList.add('selected');
                                if (!selectedItems.includes(name)) {
                                    selectedItems.push(name);
                                }
                            } else {
                                item.classList.remove('selected');
                                const index = selectedItems.indexOf(name);
                                if (index !== -1) {
                                    selectedItems.splice(index, 1);
                                }
                            }

                            updateButtonStates();
                        });
                    });
                }

                // Check if a file is editable
                function isEditable(fileName) {
                    const editableExtensions = ['txt', 'html', 'htm', 'css', 'js', 'php', 'xml', 'json', 'md', 'log', 'config', 'ini', 'yml', 'yaml', 'sql', 'sh'];
                    const extension = fileName.split('.').pop().toLowerCase();

                    return editableExtensions.includes(extension);
                }

                // Show context menu
                function showContextMenu(x, y, type) {
                    const contextMenu = document.getElementById('context-menu');

                    // Temporarily show the menu to get its dimensions
                    contextMenu.style.visibility = 'hidden';
                    contextMenu.style.display = 'block';

                    const menuWidth = contextMenu.offsetWidth;
                    const menuHeight = contextMenu.offsetHeight;
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;

                    // Adjust horizontal position if it overflows
                    if (x + menuWidth > viewportWidth) {
                        x = viewportWidth - menuWidth - 10;
                    }

                    // Adjust vertical position if it overflows
                    if (y + menuHeight > viewportHeight) {
                        y = y - menuHeight;

                        // If still out of view, clamp to top
                        if (y < 0) {
                            y = 10;
                        }
                    }

                    contextMenu.style.left = `${x}px`;
                    contextMenu.style.top = `${y}px`;
                    contextMenu.style.visibility = 'visible';

                    // Enable/disable menu items based on context
                    document.getElementById('ctx-edit').style.display = type === 'file' ? 'block' : 'none';
                    document.getElementById('ctx-open').style.display = type === 'dir' ? 'block' : 'none';

                    // Show/hide extract option based on file type
                    const extractItem = document.getElementById('ctx-extract');
                    if (extractItem) {
                        if (type === 'file' && contextTarget) {
                            const fileItem = fileList.find(item => item.name === contextTarget.name);
                            if (fileItem) {
                                const extension = fileItem.extension ? fileItem.extension.toLowerCase() : '';
                                const isArchive = ['zip', 'tar', 'gz', 'gzip', 'bz2', 'bzip2', 'rar', '7z'].includes(extension);
                                extractItem.style.display = isArchive ? 'block' : 'none';
                            } else {
                                extractItem.style.display = 'none';
                            }
                        } else {
                            extractItem.style.display = 'none';
                        }
                    }

                    // Add event listener to close menu when clicking elsewhere
                    document.addEventListener('click', function closeMenu(e) {
                        if (!contextMenu.contains(e.target)) {
                            contextMenu.style.display = 'none';
                            document.removeEventListener('click', closeMenu);
                        }
                    });
                }



                // Add the extract button to context menu
                const contextMenu = document.getElementById("context-menu");
                const compressItem = document.getElementById("ctx-compress");

                if (contextMenu && compressItem) {
                    // Create extract menu item after compress
                    const extractItem = document.createElement("div");
                    extractItem.className = "context-menu-item";
                    extractItem.id = "ctx-extract";
                    extractItem.innerHTML =
                        '<i class="bi bi-file-earmark-zip me-2"></i> Extract Here';

                    // Insert after compress item
                    compressItem.parentNode.insertBefore(extractItem, compressItem.nextSibling);

                    // Add event listener
                    extractItem.addEventListener("click", function() {
                        if (contextTarget && contextTarget.type === "file") {
                            extractArchive(contextTarget.name);
                        }
                    });
                }

                // Button to add "Extract" functionality to file items
                const addExtractButton = function() {
                    // Check if we already added the button
                    if (document.getElementById("extract-btn")) return;

                    // Create extract button
                    const actionBar = document.querySelector(".action-bar");
                    if (actionBar) {
                        const extractBtn = document.createElement("div");
                        extractBtn.className = "btn-group";
                        extractBtn.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary" id="extract-btn" disabled>
                    <i class="bi bi-file-earmark-zip"></i> Extract
                </button>
            `;

                        // Add after compress button
                        const compressBtn = document.getElementById("compress-btn");
                        if (compressBtn) {
                            const compressBtnGroup = compressBtn.closest(".btn-group");
                            if (compressBtnGroup) {
                                compressBtnGroup.parentNode.insertBefore(
                                    extractBtn,
                                    compressBtnGroup.nextSibling
                                );
                            } else {
                                actionBar.appendChild(extractBtn);
                            }
                        } else {
                            actionBar.appendChild(extractBtn);
                        }

                        // Add event listener
                        document
                            .getElementById("extract-btn")
                            .addEventListener("click", function() {
                                if (selectedItems.length === 1) {
                                    // Get the item and check if it's an extractable archive
                                    const fileItem = fileList.find(
                                        (item) => item.name === selectedItems[0]
                                    );
                                    if (fileItem && fileItem.type === "file") {
                                        extractArchive(fileItem.name);
                                    } else {
                                        showAlert("Error", "Please select a file to extract");
                                    }
                                } else {
                                    showAlert("Error", "Please select exactly one file to extract");
                                }
                            });
                    }
                };

                // Add extract button to action bar
                addExtractButton();

                // Add event listener for extract confirmation
                const extractConfirmBtn = document.getElementById("extractConfirmBtn");
                if (extractConfirmBtn) {
                    extractConfirmBtn.addEventListener("click", confirmExtraction);
                }

                // Add event listener for path input to handle Enter key
                const extractPath = document.getElementById("extractPath");
                if (extractPath) {
                    extractPath.addEventListener("keypress", function(e) {
                        if (e.key === "Enter") {
                            confirmExtraction();
                        }
                    });
                }

                // Update extract button state when selection changes
                const originalUpdateButtonStates = updateButtonStates;
                updateButtonStates = function() {
                    // Call the original function
                    originalUpdateButtonStates();

                    // Update extract button state
                    const extractBtn = document.getElementById("extract-btn");
                    if (extractBtn) {
                        // Enable button only when a single file is selected
                        const hasSelection = selectedItems.length === 1;

                        if (hasSelection) {
                            // Check if the selected file is an archive
                            const fileItem = fileList.find(
                                (item) => item.name === selectedItems[0]
                            );
                            if (fileItem && fileItem.type === "file") {
                                const extension = fileItem.extension ?
                                    fileItem.extension.toLowerCase() :
                                    "";
                                const isArchive = [
                                    "zip",
                                    "tar",
                                    "gz",
                                    "gzip",
                                    "bz2",
                                    "bzip2",
                                    "rar",
                                    "7z",
                                ].includes(extension);
                                extractBtn.disabled = !isArchive;
                            } else {
                                extractBtn.disabled = true;
                            }
                        } else {
                            extractBtn.disabled = true;
                        }

                        if (extractBtn.disabled) {
                            extractBtn.classList.remove('btn-outline-info');
                            extractBtn.classList.add('btn-outline-secondary');
                        } else {
                            extractBtn.classList.remove('btn-outline-secondary');
                            extractBtn.classList.add('btn-outline-info');
                        }
                    }
                };



                // Add event listener for restore button
                const restoreBtn = document.getElementById('restore-btn');
                if (restoreBtn) {
                    restoreBtn.addEventListener('click', restoreFromTrash);
                }

                // Add event listener for confirm restore button
                const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
                if (confirmRestoreBtn) {
                    confirmRestoreBtn.addEventListener('click', confirmRestore);
                }

                // Initial update of restore button visibility
                updateRestoreButtonVisibility();


                // Make sure the restore functions are properly defined
                function restoreFromTrash() {
                    if (selectedItems.length === 0) return;

                    // Make sure we're in the trash directory
                    if (!currentPath.includes('/.trash')) {
                        showAlert('Error', 'Restore function is only available inside the trash folder');
                        return;
                    }

                    const restoreList = document.getElementById('restoreItems');
                    restoreList.innerHTML = '';

                    selectedItems.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item;
                        restoreList.appendChild(li);
                    });

                    const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
                    restoreModal.show();
                }

                function confirmRestore() {
                    const formData = new FormData();
                    formData.append('action', 'restore');
                    formData.append('path', currentPath);

                    selectedItems.forEach(item => {
                        formData.append('items[]', item);
                    });

                    fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            bootstrap.Modal.getInstance(document.getElementById('restoreModal')).hide();

                            if (data.status === 'success') {
                                loadFileList();
                                loadDirectoryTree();
                                showAlert('Success', data.message || 'Items restored successfully');
                            } else {
                                showAlert('Error', data.message || 'Failed to restore items');
                            }
                        })
                        .catch(error => {
                            console.error('Restore error:', error);
                            showAlert('Error', 'Failed to restore items');
                        });
                }

                // Function to update restore button visibility
                function updateRestoreButtonVisibility() {
                    const restoreBtnGroup = document.getElementById('restore-btn-group');
                    if (restoreBtnGroup) {
                        // Show restore button only in trash directory
                        if (currentPath.includes('/.trash')) {
                            restoreBtnGroup.style.display = 'inline-flex';

                            // Also update the button state based on selection
                            const restoreBtn = document.getElementById('restore-btn');
                            if (restoreBtn) {
                                restoreBtn.disabled = selectedItems.length === 0;
                            }
                        } else {
                            restoreBtnGroup.style.display = 'none';
                        }
                    }
                }

                // Extract archive function
                function extractArchive(fileName) {
                    if (!fileName) return;

                    // Show modal to get extraction path
                    document.getElementById('extractFileName').textContent = fileName;

                    // Set default extraction folder name (remove extension)
                    const fileNameWithoutExt = fileName.replace(/\.[^/.]+$/, "");
                    document.getElementById('extractPath').value = currentPath + '/' + fileNameWithoutExt;

                    const extractModal = new bootstrap.Modal(document.getElementById('extractModal'));
                    extractModal.show();
                }

                // Confirm extraction
                function confirmExtraction() {
                    const fileName = document.getElementById('extractFileName').textContent;
                    const destination = document.getElementById('extractPath').value.trim();

                    if (!destination) {
                        showAlert('Error', 'Please enter a destination path');
                        return;
                    }

                    // Show loading indicator
                    document.getElementById('extractConfirmBtn').disabled = true;
                    document.getElementById('extractConfirmBtn').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Extracting...';

                    const formData = new FormData();
                    formData.append('action', 'extract');
                    formData.append('path', currentPath);
                    formData.append('file', fileName);
                    formData.append('destination', destination);

                    fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Reset button state
                            document.getElementById('extractConfirmBtn').disabled = false;
                            document.getElementById('extractConfirmBtn').innerHTML = 'Extract';

                            // Close modal
                            bootstrap.Modal.getInstance(document.getElementById('extractModal')).hide();

                            if (data.status === 'success') {
                                loadFileList();
                                loadDirectoryTree();
                                showAlert('Success', data.message || 'Archive extracted successfully');
                            } else {
                                showAlert('Error', data.message || 'Failed to extract archive');
                            }
                        })
                        .catch(error => {
                            // Reset button state
                            document.getElementById('extractConfirmBtn').disabled = false;
                            document.getElementById('extractConfirmBtn').innerHTML = 'Extract';

                            console.error('Extract error:', error);
                            showAlert('Error', 'Failed to extract archive: ' + error.message);
                        });
                }



                // Update button states based on selection
                function updateButtonStates() {
                    const hasSelection = selectedItems.length > 0;
                    const hasSingleSelection = selectedItems.length === 1;

                    if (hasSelection) {
                        document.querySelectorAll('.first-btns').forEach(btn => {
                            btn.classList.remove('btn-outline-secondary');
                            btn.classList.add('btn-outline-info');

                        });
                    } else {
                        document.querySelectorAll('.first-btns').forEach(btn => {
                            btn.classList.add('btn-outline-secondary');
                            btn.classList.remove('btn-outline-info');

                        });
                    }

                    // Update regular button states
                    document.getElementById('delete-btn').disabled = !hasSelection;
                    document.getElementById('copy-btn').disabled = !hasSelection;
                    document.getElementById('cut-btn').disabled = !hasSelection;
                    document.getElementById('download-btn').disabled = !hasSelection;
                    document.getElementById('compress-btn').disabled = !hasSelection;
                    document.getElementById('permissions-btn').disabled = !hasSingleSelection;

                    // Update restore button state if we're in the trash folder
                    const restoreBtn = document.getElementById('restore-btn');
                    if (restoreBtn && currentPath.includes('/.trash')) {
                        restoreBtn.disabled = !hasSelection;

                        if (hasSelection) {
                            restoreBtn.classList.remove('btn-outline-secondary');
                            restoreBtn.classList.add('btn-outline-info');
                        } else {
                            restoreBtn.classList.add('btn-outline-secondary');
                            restoreBtn.classList.remove('btn-outline-info');
                        }
                    }
                }

                // Fix for directory tree navigation
                function loadDirectoryTree() {
                    const directoryTree = document.getElementById('directory-tree');
                    directoryTree.innerHTML = '<li><i class="bi bi-arrow-clockwise spin"></i> Loading...</li>';

                    // Make AJAX request to get directory tree
                    const formData = new FormData();
                    formData.append('action', 'tree');

                    fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                directoryTree.innerHTML = buildTreeHTML(data.data);

                                // Add event listeners
                                document.querySelectorAll('.tree-toggle').forEach(toggle => {
                                    toggle.addEventListener('click', function() {
                                        this.classList.toggle('bi-caret-right');
                                        this.classList.toggle('bi-caret-down');
                                        this.parentElement.querySelector('.file-tree').classList.toggle('d-none');
                                    });
                                });

                                document.querySelectorAll('.dir-link').forEach(link => {
                                    link.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        const path = this.getAttribute('data-path');
                                        navigateTo(path);
                                    });
                                });
                            } else {
                                directoryTree.innerHTML = '<li>Failed to load directory tree</li>';
                            }
                        })
                        .catch(error => {
                            directoryTree.innerHTML = '<li>Failed to load directory tree</li>';
                        });
                }

                // Add navigation path change detection
                function navigateTo(path) {
                    // Ensure path is properly formatted (no leading double slashes)
                    if (path.startsWith('//')) {
                        path = path.substring(1);
                    }

                    // Make sure path is properly normalized
                    path = path.replace(/\/+/g, '/');

                    currentPath = path;

                    // Update URL hash (optional, but helps with browser navigation)
                    window.location.hash = path;

                    // Store in localStorage for persistence across page reloads
                    localStorage.setItem('currentPath', path);

                    loadFileList();

                    // Update restore button visibility when path changes
                    updateRestoreButtonVisibility();
                }

                // Fix for buildTreeHTML function to properly format the path attributes
                function buildTreeHTML(tree) {
                    let html = '';

                    tree.forEach(item => {
                        if (item.type === 'dir') {
                            // Ensure the path is properly formatted
                            let itemPath = item.path;
                            if (itemPath.startsWith('//')) {
                                itemPath = itemPath.substring(1);
                            }

                            html += `
                            <li>
                                <i class="bi bi-caret-right tree-toggle"></i>
                                ${item.name === "public_html" ? `<i class="bi bi-globe2 text-info item-icon"></i>` : `<i class="bi bi-folder item-icon"></i>`}
                                <a href="#" class="dir-link" style="font-size:14px" data-path="${itemPath}">${item.name}</a>
                                <ul class="file-tree d-none">`;

                            if (item.children && item.children.length > 0) {
                                html += buildTreeHTML(item.children);
                            }

                            html += `
                                </ul>
                            </li>`;
                        }
                    });

                    return html;
                }

                // Show alert modal
                function showAlert(title, message) {
                    document.getElementById('alertTitle').textContent = title;
                    document.getElementById('alertMessage').textContent = message;

                    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
                    alertModal.show();
                }

                // Create new folder
                function createFolder() {
                    const folderName = document.getElementById('folderName').value.trim();

                    if (!folderName) {
                        showAlert('Error', 'Please enter a folder name');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_dir');
                    formData.append('path', currentPath);
                    formData.append('name', folderName);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('newFolderModal')).hide();
                                loadFileList();
                                loadDirectoryTree();
                                document.getElementById('folderName').value = '';
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to create folder');
                        });
                }

                // Create new file
                function createFile() {
                    const fileName = document.getElementById('fileName').value.trim();
                    const fileContent = document.getElementById('fileContent').value;

                    if (!fileName) {
                        showAlert('Error', 'Please enter a file name');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'create_file');
                    formData.append('path', currentPath);
                    formData.append('name', fileName);
                    formData.append('content', fileContent);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('newFileModal')).hide();
                                loadFileList();
                                document.getElementById('fileName').value = '';
                                document.getElementById('fileContent').value = '';
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to create file');
                        });
                }

                // Rename item
                function renameItem() {
                    const oldName = document.getElementById('oldName').value;
                    const newName = document.getElementById('newName').value.trim();

                    if (!newName) {
                        showAlert('Error', 'Please enter a new name');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'rename');
                    formData.append('path', currentPath);
                    formData.append('old_name', oldName);
                    formData.append('new_name', newName);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('renameModal')).hide();
                                loadFileList();
                                loadDirectoryTree();
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to rename item');
                        });
                }

                // Delete or trash selected items
                function deleteItems() {
                    if (selectedItems.length === 0) return;

                    const deleteList = document.getElementById('deleteItems');
                    deleteList.innerHTML = '';

                    selectedItems.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item;
                        deleteList.appendChild(li);
                    });

                    // Reset the permanent delete checkbox
                    document.getElementById('permanentDeleteCheck').checked = false;

                    // Update alert visibility and button text
                    updateDeleteModalState();

                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                }

                // Update delete modal state based on checkbox
                function updateDeleteModalState() {
                    const isPermanentDelete = document.getElementById('permanentDeleteCheck').checked;
                    document.getElementById('deleteInfoAlert').style.display = isPermanentDelete ? 'none' : 'block';
                    document.getElementById('permanentDeleteAlert').style.display = isPermanentDelete ? 'block' : 'none';
                    document.getElementById('deleteButtonText').textContent = isPermanentDelete ? 'Delete Permanently' : 'Move to Trash';
                }

                // Confirm delete or move to trash
                function confirmDelete() {
                    const isPermanentDelete = document.getElementById('permanentDeleteCheck').checked;

                    if (isPermanentDelete) {
                        // Permanent delete
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('path', currentPath);
                        formData.append('permanent', 'true');

                        selectedItems.forEach(item => {
                            formData.append('items[]', item);
                        });

                        fetch(window.location.pathname, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

                                if (data.status === 'success') {
                                    loadFileList();
                                    loadDirectoryTree();
                                    showAlert('Success', data.message || 'Items deleted permanently');
                                } else {
                                    showAlert('Error', data.message || 'Failed to delete items');
                                }
                            })
                            .catch(error => {
                                showAlert('Error', 'Failed to delete items');
                            });
                    } else {
                        // Move to trash
                        const formData = new FormData();
                        formData.append('action', 'trash');
                        formData.append('path', currentPath);

                        selectedItems.forEach(item => {
                            formData.append('items[]', item);
                        });

                        fetch(window.location.pathname, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

                                if (data.status === 'success') {
                                    loadFileList();
                                    loadDirectoryTree();
                                    showAlert('Success', data.message || 'Items moved to trash');
                                } else {
                                    showAlert('Error', data.message || 'Failed to move items to trash');
                                }
                            })
                            .catch(error => {
                                showAlert('Error', 'Failed to move items to trash');
                            });
                    }
                }

                // Add event listener for the permanent delete checkbox

                // This code will run after the page is fully loaded
                document.getElementById('permanentDeleteCheck').addEventListener('change', updateDeleteModalState);

                // Add Trash folder to sidebar (if not already there)
                const sidebarNav = document.getElementById('show-trash');
                if (sidebarNav) {
                    // Check if trash link already exists
                    if (!document.querySelector('.nav-link[data-path="/.trash"]')) {
                        sidebarNav.innerHTML = `
                                                <a class="nav-link navigate-link text-danger mt-2" href="#" data-path="/.trash">
                                                    <i class="bi bi-trash me-2"></i> Trash
                                                </a>
                                            `;
                    }
                }


                // Change permissions
                function changePermissions() {
                    const item = document.getElementById('permItem').textContent;
                    const permValue = document.getElementById('permValue').value.trim();

                    if (!permValue.match(/^0[0-7]{3}$/)) {
                        showAlert('Error', 'Please enter a valid octal permission value (e.g. 0755)');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'permissions');
                    formData.append('path', currentPath);
                    formData.append('item', item);
                    formData.append('mode', permValue);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('permissionsModal')).hide();
                                loadFileList();
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to change permissions');
                        });
                }

                // Compress items
                function compressItems() {
                    const name = document.getElementById('compressName').value.trim();
                    const type = document.querySelector('input[name="compressType"]:checked').value;

                    if (!name) {
                        showAlert('Error', 'Please enter an archive name');
                        return;
                    }

                    if (type === 'gzip' && selectedItems.length > 1) {
                        showAlert('Error', 'GZip can only compress one file at a time');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'compress');
                    formData.append('path', currentPath);
                    formData.append('name', name);
                    formData.append('type', type);

                    selectedItems.forEach(item => {
                        formData.append('items[]', item);
                    });

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('compressModal')).hide();
                                loadFileList();
                                document.getElementById('compressName').value = '';
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to compress items');
                        });
                }


                // Upload files with fixed progress bar
                function uploadFiles(files) {
                    if (files.length === 0) return;

                    const formData = new FormData();
                    formData.append('path', currentPath);

                    for (let i = 0; i < files.length; i++) {
                        formData.append('files[]', files[i]);
                    }

                    // Show progress bar
                    const progressBar = document.getElementById('upload-progress');
                    const progressBarInner = progressBar.querySelector('.progress-bar');

                    // Reset and show progress bar
                    progressBar.style.display = 'block';
                    progressBarInner.style.width = '0%';
                    progressBarInner.setAttribute('aria-valuenow', 0);
                    progressBarInner.textContent = 'Preparing upload...';

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', window.location.pathname, true);

                    // Set up progress event handler
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);

                            // Update progress bar visually
                            progressBarInner.style.width = percentComplete + '%';
                            progressBarInner.setAttribute('aria-valuenow', percentComplete);

                            // Update text inside progress bar
                            progressBarInner.textContent = percentComplete + '% uploaded';

                            // Optional: Add a class to change color when complete
                            if (percentComplete >= 100) {
                                progressBarInner.classList.remove('progress-bar-animated');
                                progressBarInner.textContent = 'Processing...';
                            }
                        }
                    });

                    xhr.addEventListener('load', function() {
                        // Hide progress bar with a slight delay to ensure user sees the completion
                        setTimeout(() => {
                            progressBar.style.display = 'none';
                            // Reset classes for next upload
                            progressBarInner.classList.add('progress-bar-animated');
                        }, 500);

                        if (xhr.status === 200) {
                            try {
                                // Log the response for debugging
                                console.log("Server response:", xhr.responseText);

                                // Try to parse the JSON response
                                const response = JSON.parse(xhr.responseText);

                                if (response.status === 'success' || response.status === 'partial') {
                                    loadFileList();
                                    showAlert('Upload Complete', response.message);
                                } else {
                                    showAlert('Error', response.message || 'Unknown error occurred during upload');
                                }
                            } catch (error) {
                                console.error("Error parsing response:", error);

                                // Check if the response is HTML instead of JSON (common server error)
                                if (xhr.responseText.includes("<!DOCTYPE html>") ||
                                    xhr.responseText.includes("<html>")) {
                                    showAlert('Error', 'Server returned HTML instead of JSON. This could be due to upload size limits or server configuration issues.');
                                } else {
                                    showAlert('Error', 'Failed to parse server response: ' + error.message);
                                }
                            }
                        } else {
                            showAlert('Error', 'HTTP Error: ' + xhr.status);
                        }
                    });

                    xhr.addEventListener('error', function(e) {
                        console.error("Upload error:", e);
                        progressBar.style.display = 'none';
                        showAlert('Error', 'Network error occurred while uploading files');
                    });

                    xhr.addEventListener('abort', function() {
                        progressBar.style.display = 'none';
                        showAlert('Upload Aborted', 'File upload was cancelled');
                    });

                    xhr.send(formData);
                }

                // Open file editor
                function openFileEditor(fileName) {

                    // Construct the full server path to the file
                    const fullPath = SERVER_ROOT_PATH + (currentPath.startsWith('/') ? currentPath : '/' + currentPath);
                    const completePath = fullPath + (fullPath.endsWith('/') ? '' : '/') + fileName;

                    // Open the codeEditor.php in a new tab with the full file path as parameter
                    window.open('codeEditor.php?filename=' + encodeURIComponent(completePath), '_blank');
                }


                // Save file changes
                function saveFileChanges() {
                    const fileName = document.getElementById('editFileName').textContent;
                    const content = document.getElementById('editContent').value;

                    const formData = new FormData();
                    formData.append('action', 'save_file');
                    formData.append('path', currentPath);
                    formData.append('item', fileName);
                    formData.append('content', content);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('editFileModal')).hide();
                                loadFileList();
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {

                            showAlert('Error', 'Failed to save file');
                        });
                }

                // Search for files
                function searchFiles() {
                    const query = document.getElementById('search-input').value.trim();

                    if (!query) {
                        showAlert('Error', 'Please enter a search query');
                        return;
                    }

                    const searchResults = document.getElementById('search-results');
                    searchResults.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Searching...</td></tr>';

                    const formData = new FormData();
                    formData.append('action', 'search');
                    formData.append('path', currentPath);
                    formData.append('query', query);

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                const results = data.data;

                                if (results.length === 0) {
                                    searchResults.innerHTML = '<tr><td colspan="5" class="text-center">No results found</td></tr>';
                                } else {
                                    let html = '';

                                    results.forEach(result => {
                                        html += `
                                <tr class="search-result-item" data-path="${result.path}">
                                    <td><i class="bi ${result.icon} item-icon"></i> ${result.name}</td>
                                    <td>${result.path}</td>
                                    <td>${result.type === 'dir' ? 'Folder' : 'File'}</td>
                                    <td>${result.size || ''}</td>
                                    <td>${result.last_modified}</td>
                                </tr>`;
                                    });

                                    searchResults.innerHTML = html;

                                    // Add event listeners to search results
                                    document.querySelectorAll('.search-result-item').forEach(item => {
                                        item.addEventListener('dblclick', function() {
                                            const path = this.getAttribute('data-path');

                                            if (path.endsWith(this.cells[0].textContent.trim())) {
                                                // It's a file, navigate to its directory
                                                const dirPath = path.substring(0, path.lastIndexOf('/'));
                                                navigateTo(dirPath);
                                            } else {
                                                // It's a directory, navigate to it
                                                navigateTo(path);
                                            }

                                            bootstrap.Modal.getInstance(document.getElementById('searchModal')).hide();
                                        });
                                    });
                                }
                            } else {
                                searchResults.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: ' + data.message + '</td></tr>';
                            }
                        })
                        .catch(error => {

                            searchResults.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: Failed to perform search</td></tr>';
                        });

                    const searchModal = new bootstrap.Modal(document.getElementById('searchModal'));
                    searchModal.show();
                }

                // This function will handle both copy and move operations
                function performFileOperation(operation) {
                    if (selectedItems.length === 0) return;

                    // Set the operation type in the modal title
                    document.getElementById('fileOpTitle').textContent = operation === 'copy' ? 'Copy Items' : 'Move Items';
                    document.getElementById('fileOpType').value = operation;

                    // Set the current path as the default destination
                    document.getElementById('destinationPath').value = currentPath;

                    // Show selected items in the modal
                    const itemsList = document.getElementById('fileOpItems');
                    itemsList.innerHTML = '';

                    selectedItems.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item;
                        itemsList.appendChild(li);
                    });

                    // Show the modal
                    const fileOpModal = new bootstrap.Modal(document.getElementById('fileOperationModal'));
                    fileOpModal.show();
                }

                // Execute file operation (copy or move)
                function executeFileOperation() {
                    const operation = document.getElementById('fileOpType').value;
                    const destination = document.getElementById('destinationPath').value.trim();

                    if (!destination) {
                        showAlert('Error', 'Please enter a destination path');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', operation === 'copy' ? 'copy' : 'move');
                    formData.append('path', currentPath);
                    formData.append('destination', destination);

                    selectedItems.forEach(item => {
                        formData.append('items[]', item);
                    });

                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            bootstrap.Modal.getInstance(document.getElementById('fileOperationModal')).hide();

                            if (data.status === 'success') {
                                loadFileList();
                                loadDirectoryTree();
                                showAlert('Success', data.message);
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {
                            showAlert('Error', 'Failed to ' + (operation === 'copy' ? 'copy' : 'move') + ' items');
                        });
                }

                // Fix download function
                function downloadFiles() {
                    if (selectedItems.length === 0) return;

                    if (selectedItems.length === 1) {
                        // Download single file directly - make sure to include the current path
                        const encodedPath = encodeURIComponent(currentPath);
                        const encodedFile = encodeURIComponent(selectedItems[0]);
                        window.location.href = `${window.location.pathname}?action=download&path=${encodedPath}&file=${encodedFile}`;
                    } else {
                        // For multiple files, compress them first then download
                        document.getElementById('compressName').value = 'download_' + Math.floor(Date.now() / 1000);
                        document.getElementById('compressZip').checked = true;

                        // Set up the compress modal with a callback to download the ZIP after creation
                        document.getElementById('compressAndDownloadBtn').style.display = 'block';
                        document.getElementById('compressBtn').style.display = 'none';

                        const compressModal = new bootstrap.Modal(document.getElementById('compressModal'));
                        compressModal.show();
                    }
                }

                // Compress and then download
                function compressAndDownload() {
                    const name = document.getElementById('compressName').value.trim();
                    const type = document.querySelector('input[name="compressType"]:checked').value;

                    if (!name) {
                        showAlert('Error', 'Please enter an archive name');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'compress');
                    formData.append('path', currentPath);
                    formData.append('name', name);
                    formData.append('type', type);

                    selectedItems.forEach(item => {
                        formData.append('items[]', item);
                    });

                    fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                bootstrap.Modal.getInstance(document.getElementById('compressModal')).hide();

                                // Generate the correct extension based on compression type
                                let extension;
                                switch (type) {
                                    case 'zip':
                                        extension = '.zip';
                                        break;
                                    case 'tar':
                                        extension = '.tar';
                                        break;
                                    case 'gzip':
                                        extension = '.gz';
                                        break;
                                    default:
                                        extension = '.zip';
                                }

                                // Download the compressed file - make sure to include the current path
                                const encodedPath = encodeURIComponent(currentPath);
                                const encodedFile = encodeURIComponent(name + extension);
                                window.location.href = `${window.location.pathname}?action=download&path=${encodedPath}&file=${encodedFile}`;

                                // Reset the compress modal
                                document.getElementById('compressAndDownloadBtn').style.display = 'none';
                                document.getElementById('compressBtn').style.display = 'block';
                            } else {
                                showAlert('Error', data.message);
                            }
                        })
                        .catch(error => {
                            showAlert('Error', 'Failed to compress items');
                        });
                }


                // Initialize permissions modal
                function initPermissionsModal() {
                    const permValue = document.getElementById('permValue');
                    const permChecks = document.querySelectorAll('.perm-check');

                    // Update permission value when checkboxes change
                    permChecks.forEach(check => {
                        check.addEventListener('change', function() {
                            let value = 0;

                            permChecks.forEach(c => {
                                if (c.checked) {
                                    value += parseInt(c.getAttribute('data-value'));
                                }
                            });

                            permValue.value = '0' + value.toString(8).padStart(3, '0');
                        });
                    });

                    // Update checkboxes when permission value changes
                    permValue.addEventListener('input', function() {
                        const match = this.value.match(/^0([0-7]{3})$/);

                        if (match) {
                            const octal = match[1];
                            const decimal = parseInt(octal, 8);

                            permChecks.forEach(check => {
                                const checkValue = parseInt(check.getAttribute('data-value'));
                                check.checked = (decimal & checkValue) === checkValue;
                            });
                        }
                    });
                }

                // Initialize drag and drop
                function initDragAndDrop() {
                    const dropzone = document.getElementById('dropzone');

                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                        dropzone.addEventListener(eventName, function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }, false);
                    });

                    ['dragenter', 'dragover'].forEach(eventName => {
                        dropzone.addEventListener(eventName, function() {
                            this.classList.add('drag-over');
                        }, false);
                    });

                    ['dragleave', 'drop'].forEach(eventName => {
                        dropzone.addEventListener(eventName, function() {
                            this.classList.remove('drag-over');
                        }, false);
                    });

                    dropzone.addEventListener('drop', function(e) {
                        const files = e.dataTransfer.files;
                        uploadFiles(files);
                    }, false);
                }

                // Toggle dark mode
                function toggleDarkMode() {
                    const isDark = document.getElementById('darkModeSwitch').checked;
                    document.body.setAttribute('data-theme', isDark ? 'dark' : 'light');

                    // Save preference
                    localStorage.setItem('darkMode', isDark ? 'dark' : 'light');
                }

                // Initialize
                function init() {
                    // Load current path from localStorage if available
                    currentPath = localStorage.getItem('currentPath') || '';


                    // Load preferences
                    const darkMode = localStorage.getItem('darkMode');
                    if (darkMode === 'dark') {
                        document.getElementById('darkModeSwitch').checked = true;
                        document.body.setAttribute('data-theme', 'dark');
                    }

                    // Initialize permissions modal
                    initPermissionsModal();

                    // Initialize drag and drop
                    initDragAndDrop();

                    // Load directory tree
                    loadDirectoryTree();

                    // Load file list
                    loadFileList();
                }

                // Event listeners
                document.getElementById('sidebarCollapse').addEventListener('click', function() {
                    document.getElementById('sidebar').classList.toggle('active');
                    document.getElementById('content').classList.toggle('active');
                });

                document.getElementById('refresh-btn').addEventListener('click', function() {
                    loadFileList();
                });

                document.getElementById('new-folder-btn').addEventListener('click', function() {
                    document.getElementById('folderName').value = '';
                    const newFolderModal = new bootstrap.Modal(document.getElementById('newFolderModal'));
                    newFolderModal.show();
                });

                document.getElementById('createFolderBtn').addEventListener('click', createFolder);

                document.getElementById('new-file-btn').addEventListener('click', function() {
                    document.getElementById('fileName').value = '';
                    document.getElementById('fileContent').value = '';
                    const newFileModal = new bootstrap.Modal(document.getElementById('newFileModal'));
                    newFileModal.show();
                });

                document.getElementById('createFileBtn').addEventListener('click', createFile);

                document.getElementById('upload-btn').addEventListener('click', function() {
                    document.getElementById('file-upload').click();
                });

                document.getElementById('file-upload').addEventListener('change', function() {
                    uploadFiles(this.files);
                    this.value = '';
                });

                document.getElementById('select-all-checkbox').addEventListener('change', function() {
                    document.querySelectorAll('.item-check').forEach(check => {
                        check.checked = this.checked;
                        const event = new Event('change');
                        check.dispatchEvent(event);
                    });
                });

                document.getElementById('delete-btn').addEventListener('click', deleteItems);
                document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

                document.getElementById('download-btn').addEventListener('click', downloadFiles);

                document.getElementById('copy-btn').addEventListener('click', function() {
                    performFileOperation('copy');
                });
                document.getElementById('cut-btn').addEventListener('click', function() {
                    performFileOperation('move');
                });
                // Add event listener for the file operation execute button
                document.getElementById('executeFileOpBtn').addEventListener('click', executeFileOperation);

                // Add event listener for compress and download button
                document.getElementById('compressAndDownloadBtn').addEventListener('click', compressAndDownload);

                // Show current path in the file operation modal
                document.getElementById('fileOperationModal').addEventListener('show.bs.modal', function() {
                    document.getElementById('currentPathDisplay').textContent = currentPath;
                });

                document.getElementById('compress-btn').addEventListener('click', function() {
                    if (selectedItems.length === 0) return;

                    document.getElementById('compressName').value = selectedItems.length === 1 ? selectedItems[0] : 'archive';

                    const compressModal = new bootstrap.Modal(document.getElementById('compressModal'));
                    compressModal.show();
                });

                document.getElementById('compressBtn').addEventListener('click', compressItems);

                document.getElementById('permissions-btn').addEventListener('click', function() {
                    if (selectedItems.length !== 1) return;

                    document.getElementById('permItem').textContent = selectedItems[0];

                    // Get current permissions
                    const item = fileList.find(item => item.name === selectedItems[0]);
                    if (item) {
                        // Convert permission string to octal
                        const permString = item.permissions;
                        let octal = '0755'; // Default

                        // Simple conversion from rwx notation to octal
                        // This is a simplification - real conversion should handle all permission bits
                        if (permString.length >= 10) {
                            let owner = 0,
                                group = 0,
                                world = 0;

                            // Owner permissions (1-3)
                            if (permString[1] === 'r') owner += 4;
                            if (permString[2] === 'w') owner += 2;
                            if (permString[3] === 'x' || permString[3] === 's') owner += 1;

                            // Group permissions (4-6)
                            if (permString[4] === 'r') group += 4;
                            if (permString[5] === 'w') group += 2;
                            if (permString[6] === 'x' || permString[6] === 's') group += 1;

                            // World permissions (7-9)
                            if (permString[7] === 'r') world += 4;
                            if (permString[8] === 'w') world += 2;
                            if (permString[9] === 'x' || permString[9] === 't') world += 1;

                            octal = '0' + owner.toString() + group.toString() + world.toString();
                        }

                        document.getElementById('permValue').value = octal;

                        // Update checkboxes based on octal value
                        const decimal = parseInt(octal.substring(1), 8);
                        document.querySelectorAll('.perm-check').forEach(check => {
                            const checkValue = parseInt(check.getAttribute('data-value'));
                            check.checked = (decimal & checkValue) === checkValue;
                        });
                    }

                    const permissionsModal = new bootstrap.Modal(document.getElementById('permissionsModal'));
                    permissionsModal.show();
                });

                document.getElementById('changePermBtn').addEventListener('click', changePermissions);

                document.getElementById('search-btn').addEventListener('click', searchFiles);
                document.getElementById('search-input').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        searchFiles();
                    }
                });

                document.getElementById('darkModeSwitch').addEventListener('change', toggleDarkMode);

                // Context menu event listeners
                document.getElementById('ctx-open').addEventListener('click', function() {
                    if (contextTarget) {
                        navigateTo(currentPath + '/' + contextTarget.name);
                    }
                });

                // Context menu download fix
                document.getElementById('ctx-download').addEventListener('click', function() {
                    if (contextTarget) {
                        const encodedPath = encodeURIComponent(currentPath);
                        const encodedFile = encodeURIComponent(contextTarget.name);
                        window.location.href = `${window.location.pathname}?action=download&path=${encodedPath}&file=${encodedFile}`;
                    }
                });

                document.getElementById('ctx-edit').addEventListener('click', function() {
                    if (contextTarget && contextTarget.type === 'file') {
                        openFileEditor(contextTarget.name);
                    }
                });

                document.getElementById('ctx-copy').addEventListener('click', function() {
                    performFileOperation('copy');
                });

                document.getElementById('ctx-cut').addEventListener('click', function() {
                    performFileOperation('move');
                });



                // Add event listener for the rename button
                document.getElementById('renameBtn').addEventListener('click', renameItem);

                // Add event listener for the rename context menu item if not already present
                document.getElementById('ctx-rename').addEventListener('click', function() {
                    if (contextTarget) {
                        document.getElementById('oldName').value = contextTarget.name;
                        document.getElementById('newName').value = contextTarget.name;

                        const renameModal = new bootstrap.Modal(document.getElementById('renameModal'));
                        renameModal.show();
                    }
                });

                // Also add Enter key support for the rename dialog
                document.getElementById('newName').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        renameItem();
                    }
                });

                document.getElementById('ctx-permissions').addEventListener('click', function() {
                    if (contextTarget) {
                        // Select the item
                        selectedItems = [contextTarget.name];

                        // Show permissions modal
                        document.getElementById('permissions-btn').click();
                    }
                });

                document.getElementById('ctx-compress').addEventListener('click', function() {
                    if (contextTarget) {
                        // Select the item
                        selectedItems = [contextTarget.name];

                        // Show compress modal
                        document.getElementById('compress-btn').click();
                    }
                });

                document.getElementById('ctx-delete').addEventListener('click', function() {
                    if (contextTarget) {
                        // Select the item
                        selectedItems = [contextTarget.name];

                        // Show delete modal
                        deleteItems();
                    }
                });

                // Navigation event delegation
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('navigate-link') || e.target.parentElement.classList.contains('navigate-link')) {
                        e.preventDefault();

                        const link = e.target.classList.contains('navigate-link') ? e.target : e.target.parentElement;
                        const path = link.getAttribute('data-path');

                        navigateTo(path);
                    }
                });

                // Close context menu when clicking outside
                document.addEventListener('click', function() {
                    document.getElementById('context-menu').style.display = 'none';
                });

                // Sort items
                document.querySelectorAll('.sort-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();

                        // Update active state
                        document.querySelectorAll('.sort-item').forEach(i => i.classList.remove('active'));
                        this.classList.add('active');

                        // Update sort parameters
                        currentSort = this.getAttribute('data-sort');
                        currentOrder = this.getAttribute('data-order');

                        // Reload file list
                        loadFileList();
                    });
                });



                init();
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarToggle = document.getElementById('sidebarCollapse');
                const sidebar = document.getElementById('sidebar');

                if (sidebarToggle && sidebar) {


                    sidebarToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        sidebar.classList.add('active');
                        document.body.classList.toggle('sidebar-active');
                    });

                    // Close sidebar when clicking on body (outside sidebar) when sidebar is active
                    document.body.addEventListener('click', function(e) {
                        // Only if we're on mobile
                        if (window.innerWidth <= 768) {
                            // Only if sidebar is active and click is not on sidebar or sidebar toggle
                            if (document.body.classList.contains('sidebar-active') &&
                                !sidebar.contains(e.target) &&
                                e.target !== sidebarToggle &&
                                !sidebarToggle.contains(e.target)) {
                                sidebar.classList.remove('active');
                                document.body.classList.remove('sidebar-active');
                            }
                        }
                    });

                    // Close sidebar when clicking a navigation link on mobile
                    const navLinks = document.querySelectorAll('.navigate-link, .dir-link');
                    navLinks.forEach(link => {
                        link.addEventListener('click', function() {
                            if (window.innerWidth <= 768) {
                                sidebar.classList.remove('active');
                                document.body.classList.remove('sidebar-active');
                            }
                        });
                    });


                    if (window.innerWidth <= 768) {
                        const closeSidebar = document.getElementById('closeSidebar');


                        closeSidebar.addEventListener('click', function() {
                            sidebar.classList.remove('active');
                            document.body.classList.remove('sidebar-active');
                        });

                    }
                }
            });
        </script>
    </body>

    </html>
<?php } ?>