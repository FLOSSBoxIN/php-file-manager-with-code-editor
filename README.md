# PHP File Manager + Code Editor

This PHP File Manager is a web-based application that allows users to manage files and directories on a server. It provides a user-friendly interface for performing various file operations such as creating, renaming, deleting, copying, moving, compressing, and extracting files and directories. It is accompanied by a cool code editor based on Ace Editor. You can find the code editor repository [here](https://github.com/JosephChuks/cwp-codeEditor).

## Features

- **Directory Listing**: View and navigate through directories.
- **File Operations**: Create, rename, delete, copy, and move files and directories.
- **File Upload**: Upload multiple files to the server.
- **File Download**: Download files from the server.
- **File Compression**: Compress files and directories into ZIP, TAR, or GZIP formats.
- **File Extraction**: Extract files from ZIP, TAR, GZIP, BZIP2, RAR, and 7Zip archives.
- **Trash Management**: Move files to trash and restore them.
- **Search Functionality**: Search for files and directories.
- **Permissions Management**: Change file and directory permissions.
- **Dark Mode**: Toggle between light and dark themes for better visibility.

## Requirements

- PHP 7.4 or higher
- Web server (e.g., Apache, Nginx)
- PHP extensions: `zip`, `phar`, `rar` (optional for RAR extraction)

## Installation

1. Clone the repository to your web server's document root:

   ```bash
   git clone https://github.com/JosephChuks/php-file-manager.git
   ```

2. Set the appropriate permissions for the `root_path` directory to allow file operations.

3. Configure the `username` and `root_path` in the `filemanager.php` file.

4. Access the file manager through your web browser.

## Configuration

The configuration settings are located at the top of the `filemanager.php` file. Key settings include:

- `root_path`: The root directory for file operations.
- `max_upload_size`: Maximum file size for uploads.
- `allowed_extensions`: Allowed file extensions for uploads.
- `date_format`: Date format for displaying file dates.
- `theme`: Default theme for the interface (e.g., "dark").

## Usage

- **Navigating Directories**: Use the sidebar to navigate through directories.
- **File Operations**: Use the action buttons to perform file operations.
- **Uploading Files**: Click the "Upload" button and select files to upload.
- **Downloading Files**: Select a file and click the "Download" button.
- **Compressing Files**: Select files/directories and click the "Compress" button.
- **Extracting Archives**: Select an archive file and click the "Extract" button.
- **Managing Trash**: Use the "Trash" button to move items to trash and restore them.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request for any improvements or bug fixes.

## Support

For support or questions, please open an issue on the GitHub repository.
