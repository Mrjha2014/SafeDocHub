<?php
// Include AWS SDK and configure it with your credentials
require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();
// Include database configuration
require_once '../includes/config.php';
require_once '../includes/db.php';


// Create an S3 client
$s3 = new S3Client([
    'version' => 'latest',
    'region' => AWS_REGION,
    'credentials' => [
        'key' => AWS_ACCESS_KEY,
        'secret' => AWS_SECRET_KEY,
    ],
]);
$user_id = $_SESSION['user_id']; // Default to 0 if not set

// Handle document upload to S3 and store S3 key in the database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document']) && isset($_POST['docName'])) {
    $docName = $_POST['docName'];

    // Check if the document name is not empty
    if (empty($docName)) {
        echo '<div class="alert alert-danger" role="alert">Please enter a document name.</div>';
    } else {
        try {
            // Upload the document to S3
            $s3Key = 'documents/' . uniqid() . '/' . basename($_FILES['document']['name']);
            $result = $s3->putObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $s3Key,
                'SourceFile' => $_FILES['document']['tmp_name'], // Use the temporary file directly
            ]);

            // Now, you can store $s3Key in your database along with other details
            // For example, you might have a 'documents' table with columns 'user_id', 'name', and 'file_key'
            // Here, 'user_id' corresponds to the user's ID, 'name' corresponds to the document name,
            // and 'file_key' corresponds to the S3 key

            // Insert document details into the database
            $name = $docName;
            $fileKey = $s3Key;


            $sql = "INSERT INTO documents (user_id, name, file_key) VALUES ('$user_id', '$name', '$fileKey')";

            if ($conn->query($sql) === TRUE) {
                echo '<div class="alert alert-success" role="alert">Document uploaded successfully!</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Error inserting document details into the database.</div>';
            }
        } catch (AwsException $e) {
            echo '<div class="alert alert-danger" role="alert">Error uploading document to S3.</div>';
        }
    }
}
// Assume you have a user_id stored in the session after the user logs in
// For example, if you store the user_id during login, you might have:
// $_SESSION['user_id'] = 1; // Replace with your actual session variable

// Get the user_id from the session or any other source

// Fetch documents for the current user
$sql = "SELECT name, file_key FROM documents WHERE user_id = '$user_id'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch both the name and file_key for each document
    $uploadedDocuments = [];
    while ($row = $result->fetch_assoc()) {
        $uploadedDocuments[] = [
            'name' => $row['name'],
            'file_key' => $row['file_key'],
        ];
    }

    // Fetch additional details from S3 for each file_key
    foreach ($uploadedDocuments as &$document) {
        try {
            $s3Key = $document['file_key'];

            // Use the S3 getObject command to fetch details
            $result = $s3->getObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $s3Key,
            ]);

            // Assuming you want to retrieve the last modified timestamp
            $lastModified = $result['LastModified'];

            // Add the additional detail to the document array
            $document['last_modified'] = $lastModified;
            // Function to generate a signed S3 URL for downloading
            if (!function_exists('getDownloadLink')) {
                function getDownloadLink($s3Key)
                {
                    global $s3;

                    try {
                        $cmd = $s3->getCommand('GetObject', [
                            'Bucket' => AWS_BUCKET,
                            'Key' => $s3Key,
                        ]);

                        $request = $s3->createPresignedRequest($cmd, '+15 minutes');
                        return (string) $request->getUri();
                    } catch (AwsException $e) {
                        echo '<div class="alert alert-danger" role="alert">Error generating download link for ' . $s3Key . '.</div>';
                        return '#';
                    }
                }
            }
            // Function to generate a pre-signed S3 URL for viewing
            if (!function_exists('getPresignedViewLink')) {
                function getPresignedViewLink($s3Key)
                {
                    global $s3;

                    try {
                        $cmd = $s3->getCommand('GetObject', [
                            'Bucket' => AWS_BUCKET,
                            'Key' => $s3Key,
                        ]);

                        $presignedUrl = $s3->createPresignedRequest($cmd, '+15 minutes')->getUri();
                        return (string) $presignedUrl;
                    } catch (AwsException $e) {
                        echo '<div class="alert alert-danger" role="alert">Error generating view link for ' . $s3Key . '.</div>';
                        return '#';
                    }
                }
            }
        } catch (AwsException $e) {
            // Handle errors if necessary
            echo '<div class="alert alert-danger" role="alert">Error fetching details from S3 for ' . $s3Key . '.</div>';
        }
    }
} else {
    $uploadedDocuments = [];
}

$conn->close();



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <!-- Bootstrap CSS from CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome CSS from CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">SafeDocHub</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Welcome, <?php echo $_SESSION['username']; ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Dashboard</h2>
        <p>Welcome to your dashboard, <?php echo $_SESSION['username']; ?>!</p>

        <!-- Document Upload Form -->
        <form method="post" action="" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="document" class="form-label">Upload Document</label>
                <input type="file" class="form-control" id="document" name="document" accept=".pdf, .doc, .docx" required>
            </div>
            <div class="mb-3">
                <label for="docName" class="form-label">Document Name</label>
                <input type="text" class="form-control" id="docName" name="docName" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
        <!-- Display Uploaded Documents with Additional Details -->
        <?php if (!empty($uploadedDocuments)) : ?>
            <h4>Uploaded Documents</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>File Name</th>
                            <th>Last Modified</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadedDocuments as $document) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($document['name']); ?></td>
                                <td><?php echo htmlspecialchars(basename($document['file_key'])); ?></td>
                                <td><?php echo htmlspecialchars($document['last_modified']); ?></td>
                                <td>
                                    <a href="<?php echo getDownloadLink($document['file_key']); ?>" class="btn btn-primary btn-sm" download>
                                        Download
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p>No documents uploaded yet.</p>
        <?php endif; ?>


        <!-- Bootstrap JS and Popper.js from CDN -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
        <!-- jQuery from CDN -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Font Awesome JS from CDN -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>
</body>

</html>